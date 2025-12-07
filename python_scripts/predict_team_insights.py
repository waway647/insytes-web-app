import os
import json
import pickle
import numpy as np
import pandas as pd
import sys
from datetime import datetime

# Command line argument handling
if len(sys.argv) > 1:
    if sys.argv[1] == "--dataset" and len(sys.argv) > 2:
        MATCH_NAME = sys.argv[2]
    else:
        MATCH_NAME = sys.argv[1]
else:
    MATCH_NAME = "sbu_vs_2worlds"

# ---------- PATHS ----------
MODELS_DIR = "python_scripts/models_team"

# Load team name from config file (use home team as featured team)
TEAM_CONFIG_JSON = f"writable_data/configs/config_{MATCH_NAME}.json"
try:
    with open(TEAM_CONFIG_JSON, "r", encoding="utf-8") as f:
        config_data = json.load(f)
    TEAM_NAME = config_data["home"]["name"]
    print(f"Using home team as featured team: {TEAM_NAME}")
except (FileNotFoundError, KeyError) as e:
    print(f"Could not load team name from config: {e}")
    TEAM_NAME = "San Beda"  # Fallback
    print(f"Using fallback team name: {TEAM_NAME}")

# Load match_id from events file
EVENTS_JSON = f"writable_data/events/{MATCH_NAME}_events.json"
MATCH_ID = None
try:
    with open(EVENTS_JSON, "r", encoding="utf-8") as f:
        events_data = json.load(f)
    MATCH_ID = events_data.get("match_id", MATCH_NAME)
    print(f"Loaded match ID: {MATCH_ID}")
except (FileNotFoundError, KeyError) as e:
    print(f"Could not load match ID from events: {e}")
    MATCH_ID = MATCH_NAME  # Fallback to dataset name
    print(f"Using fallback match ID: {MATCH_ID}")

team_name_safe = TEAM_NAME.lower().replace(" ", "_")
INPUT_METRICS_PATH = f"output/matches/{MATCH_NAME}/{team_name_safe}_team_derived_metrics.json"
OUTPUT_INSIGHTS_PATH = f"output/matches/{MATCH_NAME}/{team_name_safe}_team_insights.json"

# Ensure output directory exists
os.makedirs(os.path.dirname(OUTPUT_INSIGHTS_PATH), exist_ok=True)

# ---------- LOAD MODELS ----------
def load_models(models_dir):
    models = {}
    for fname in os.listdir(models_dir):
        if fname.endswith(".pkl"):
            category = fname.replace("team_", "").replace(".pkl", "")
            path = os.path.join(models_dir, fname)
            with open(path, "rb") as f:
                models[category] = pickle.load(f)
    return models


# ---------- FLATTEN JSON (from derived_metrics.py output) ----------
def flatten_metrics(metrics_json):
    flattened = {}
    for category, submetrics in metrics_json.items():
        if isinstance(submetrics, dict):
            for key, val in submetrics.items():
                flattened[f"{category}__{key}"] = val
    return flattened


# ---------- PREDICTION PIPELINE ----------
def predict_performance():
    print("Loading derived match metrics...")
    if not os.path.exists(INPUT_METRICS_PATH):
        print(f"Error: {INPUT_METRICS_PATH} not found. Run derived_metrics.py first.")
        return

    with open(INPUT_METRICS_PATH, "r") as f:
        data = json.load(f)

    # Expect structure like {"match_id": "...", "match_name": "...", "Mendiola": {...}}
    # Find the specific team data based on TEAM_NAME from config
    team_name = None
    team_metrics = None
    
    # First try to find exact match for TEAM_NAME
    if TEAM_NAME in data and isinstance(data[TEAM_NAME], dict):
        team_name = TEAM_NAME
        team_metrics = data[TEAM_NAME]
    else:
        # If exact match not found, look for the home team among available teams
        for key, value in data.items():
            if key not in ['match_id', 'match_name', 'match_duration_seconds'] and isinstance(value, dict):
                team_name = key
                team_metrics = value
                break
    
    if team_name is None or team_metrics is None:
        print(f"Error: Could not find team data in {INPUT_METRICS_PATH}")
        return

    print(f"Loaded metrics for team: {team_name}")

    # Flatten and convert to DataFrame
    X_input = pd.DataFrame([flatten_metrics(team_metrics)])
    
    # Add missing features that models expect (with default values)
    if 'outcome_score' not in X_input.columns:
        X_input['outcome_score'] = 0.5  # Neutral outcome score
    if 'match_rating_overall' not in X_input.columns:
        overall_rating = X_input.get('overall_rating', [0.0])
        if isinstance(overall_rating, pd.Series) and len(overall_rating) > 0:
            X_input['match_rating_overall'] = overall_rating.iloc[0]
        else:
            X_input['match_rating_overall'] = 0.0

    # Load models
    models = load_models(MODELS_DIR)
    print(f"Loaded {len(models)} trained models: {', '.join(models.keys())}")

    results = {}
    for category, model in models.items():
        try:
            preds = model.predict(X_input)
            results[category] = float(np.clip(preds[0], 0, 100))  # normalize 0–100 scale
        except Exception as e:
            print(f"Prediction failed for {category}: {e}")
            results[category] = None

    # Compute weighted overall score if not already predicted
    if "team_overall" not in results or results["team_overall"] is None:
        results["overall"] = round((
            (results.get("team_attack", 0) * 0.25) +
            (results.get("team_defense", 0) * 0.25) +
            (results.get("team_distribution", 0) * 0.20) +
            (results.get("team_discipline", 0) * 0.10) +
            (results.get("team_general", 0) * 0.20)
        ), 2)
    else:
        results["overall"] = results.get("team_overall", 0.0)

    # Generate detailed qualitative insights
    insights = {
        "match_id": MATCH_ID,
        "match_name": MATCH_NAME,
        "team": team_name,
        "timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        "predicted_scores": results,
        "detailed_analysis": {
            "attack": analyze_attack(team_metrics, results.get("attack")),
            "defense": analyze_defense(team_metrics, results.get("defense")),
            "distribution": analyze_distribution(team_metrics, results.get("distribution")),
            "discipline": analyze_discipline(team_metrics, results.get("discipline")),
            "general": analyze_general(team_metrics, results.get("general")),
            "overall": analyze_overall(team_metrics, results.get("overall"))
        },
        "coaching_assessment": {
            "attack": generate_coach_analysis("attack", team_metrics, results.get("attack")),
            "defense": generate_coach_analysis("defense", team_metrics, results.get("defense")),
            "distribution": generate_coach_analysis("distribution", team_metrics, results.get("distribution")),
            "discipline": generate_coach_analysis("discipline", team_metrics, results.get("discipline")),
            "general": generate_coach_analysis("general", team_metrics, results.get("general")),
            "overall": generate_coach_analysis("overall", team_metrics, results.get("overall"))
        },
        "interpretation": {
            "attack": generate_rich_interpretation("attack", team_metrics, results.get("attack")),
            "defense": generate_rich_interpretation("defense", team_metrics, results.get("defense")),
            "distribution": generate_rich_interpretation("distribution", team_metrics, results.get("distribution")),
            "discipline": generate_rich_interpretation("discipline", team_metrics, results.get("discipline")),
            "general": generate_rich_interpretation("general", team_metrics, results.get("general")),
            "overall": generate_rich_interpretation("overall", team_metrics, results.get("overall")),
        }
    }

    # Save results
    with open(OUTPUT_INSIGHTS_PATH, "w") as f:
        json.dump(insights, f, indent=4)

    print(f"Match performance insights saved to: {OUTPUT_INSIGHTS_PATH}")
    print(json.dumps(insights, indent=4))


# ---------- PROFESSIONAL COACHING ANALYSIS ----------
def generate_coach_analysis(area, metrics, score):
    """Generate professional coaching assessment in structured format"""
    
    if score is None:
        return {
            "performance_rating": "Unable to assess",
            "tactical_summary": "Insufficient data for analysis",
            "key_observations": [],
            "coaching_priorities": [],
            "next_training_focus": "Gather more performance data"
        }
    
    if area == "attack":
        return generate_attack_coaching_analysis(metrics, score)
    elif area == "defense":
        return generate_defense_coaching_analysis(metrics, score)
    elif area == "distribution":
        return generate_distribution_coaching_analysis(metrics, score)
    elif area == "discipline":
        return generate_discipline_coaching_analysis(metrics, score)
    elif area == "general":
        return generate_general_coaching_analysis(metrics, score)
    elif area == "overall":
        return generate_overall_coaching_analysis(metrics, score)
    
    return {"performance_rating": "Analysis not available"}

def generate_attack_coaching_analysis(metrics, score):
    attack = metrics.get("attack", {})
    goals = attack.get("goals", 0)
    shots = attack.get("shots", 0)
    shots_on_target = attack.get("shots_on_target", 0)
    shot_creating_actions = attack.get("shot_creating_actions", 0)
    
    shooting_accuracy = (shots_on_target / shots * 100) if shots > 0 else 0
    conversion_rate = (goals / shots * 100) if shots > 0 else 0
    
    # Performance rating
    if score >= 8.0:
        rating = "Excellent attacking display"
    elif score >= 7.0:
        rating = "Strong offensive performance"
    elif score >= 6.0:
        rating = "Adequate attacking output"
    elif score >= 5.0:
        rating = "Struggling in the final third"
    else:
        rating = "Poor attacking performance"
    
    # Tactical summary
    if goals >= 3 and shooting_accuracy >= 40:
        tactical = f"Clinical finishing combined with quality service created {goals} goals from {shots} attempts. The team demonstrated excellent composure in front of goal."
    elif goals >= 2:
        tactical = f"Solid attacking foundation with {goals} goals, though conversion efficiency at {conversion_rate:.1f}% suggests room for improvement in clinical finishing."
    elif shots >= 15:
        tactical = f"Generated substantial attacking opportunities ({shots} shots) but struggled with end product, converting only {goals} goals."
    else:
        tactical = f"Limited attacking threat with minimal shot creation ({shots} attempts). The team needs to establish more consistent offensive patterns."
    
    # Key observations
    observations = []
    if shooting_accuracy >= 50:
        observations.append(f"Outstanding shot accuracy of {shooting_accuracy:.1f}% demonstrates excellent decision-making in the final third")
    if shot_creating_actions >= 25:
        observations.append(f"Strong creative play with {shot_creating_actions} shot-creating actions shows good attacking movement and combination play")
    if goals >= 3:
        observations.append(f"Clinical finishing with {goals} goals indicates good striker positioning and composure")
    if conversion_rate < 15 and shots > 8:
        observations.append(f"Low conversion rate of {conversion_rate:.1f}% highlights need for improved finishing under pressure")
    
    # Ensure minimum key observations
    if len(observations) == 0:
        if goals > 0 and shots > 0:
            observations.append(f"Converted {goals} of {shots} attempts showing {conversion_rate:.1f}% efficiency")
        if shot_creating_actions > 0:
            observations.append(f"Generated {shot_creating_actions} shot-creating actions indicating attacking involvement")
        if len(observations) == 0:
            observations.append("Team showed attacking intent with forward movement and positioning")
    
    # Coaching priorities
    priorities = []
    if shooting_accuracy < 30:
        priorities.append("Improve shot selection and technique through targeted finishing drills")
    if goals < 2 and shots >= 10:
        priorities.append("Focus on clinical finishing in 1v1 situations and close-range opportunities")
    if shot_creating_actions < 15:
        priorities.append("Develop attacking patterns and movement in the final third")
    if shots < 10:
        priorities.append("Increase attacking volume through better build-up play and forward runs")
    
    # Add priorities for strong performances to continue development
    if len(priorities) == 0:
        if goals >= 3 and shooting_accuracy >= 40:
            priorities.append("Maintain clinical finishing while developing set piece routines")
            priorities.append("Work on creating chances from wide areas and crosses")
        elif goals >= 2:
            priorities.append("Improve consistency in final third decision-making")
            priorities.append("Develop alternative attacking patterns for different opponents")
        else:
            priorities.append("Focus on converting more scoring opportunities")
            priorities.append("Enhance movement and positioning in the penalty area")
    
    # Training focus
    training_focus = f"Primary focus: {priorities[0].lower()}"
    
    return {
        "performance_rating": rating,
        "tactical_summary": tactical,
        "key_observations": observations,
        "coaching_priorities": priorities,
        "next_training_focus": training_focus
    }

def generate_defense_coaching_analysis(metrics, score):
    defense = metrics.get("defense", {})
    tackles = defense.get("tackles", 0)
    tackle_success_rate = defense.get("tackles_success_rate_pct", 0)
    interceptions = defense.get("interceptions", 0)
    recoveries = defense.get("recoveries", 0)
    clearances = defense.get("clearances", 0)
    
    # Performance rating
    if score >= 8.0:
        rating = "Dominant defensive performance"
    elif score >= 7.0:
        rating = "Solid defensive foundation"
    elif score >= 6.0:
        rating = "Competent defensive display"
    elif score >= 5.0:
        rating = "Vulnerable defensive structure"
    else:
        rating = "Defensive frailties exposed"
    
    # Tactical summary
    if tackle_success_rate >= 75 and interceptions >= 5:
        tactical = f"Excellent defensive discipline with {tackle_success_rate:.1f}% tackle success rate and {interceptions} interceptions. The defensive unit showed great anticipation and timing."
    elif tackle_success_rate >= 60:
        tactical = f"Solid defensive performance with {tackle_success_rate:.1f}% tackle success rate. Good defensive positioning helped limit opponent opportunities."
    else:
        tactical = f"Defensive struggles evident with {tackle_success_rate:.1f}% tackle success rate. The team needs to improve defensive timing and positioning."
    
    # Key observations
    observations = []
    if tackle_success_rate >= 80:
        observations.append(f"Exceptional tackling efficiency at {tackle_success_rate:.1f}% shows excellent defensive timing")
    if interceptions >= 8:
        observations.append(f"Outstanding anticipation with {interceptions} interceptions demonstrates strong defensive reading of the game")
    if recoveries >= 20:
        observations.append(f"Excellent ball recovery with {recoveries} regains shows good pressing coordination")
    if tackle_success_rate < 50:
        observations.append(f"Poor tackling success rate of {tackle_success_rate:.1f}% indicates timing and positioning issues")
    
    # Ensure minimum key observations
    if len(observations) == 0:
        if tackles > 0:
            observations.append(f"Attempted {tackles} tackles with {tackle_success_rate:.1f}% success rate showing defensive engagement")
        if interceptions > 0:
            observations.append(f"Registered {interceptions} interceptions demonstrating defensive awareness")
        if clearances > 0:
            observations.append(f"Made {clearances} defensive clearances to relieve pressure")
        if len(observations) == 0:
            observations.append("Maintained defensive structure and organization")
    
    # Coaching priorities
    priorities = []
    if tackle_success_rate < 60:
        priorities.append("Improve defensive timing and body positioning during tackles")
    if interceptions < 3:
        priorities.append("Enhance reading of the game and anticipation skills")
    if recoveries < 10:
        priorities.append("Coordinate pressing triggers and defensive transitions")
    if clearances < 5 and tackles < 8:
        priorities.append("Increase defensive aggression and proactive defending")
    
    # Add priorities for strong performances to continue development
    if len(priorities) == 0:
        if tackle_success_rate >= 80 and interceptions >= 8:
            priorities.append("Develop distribution skills from defensive positions")
            priorities.append("Work on defensive leadership and communication")
        elif tackle_success_rate >= 70:
            priorities.append("Maintain defensive discipline while improving aerial dominance")
            priorities.append("Enhance transition play from defense to attack")
        else:
            priorities.append("Focus on consistent defensive positioning and shape")
            priorities.append("Improve coordination between defensive lines")
    
    # Training focus
    training_focus = f"Primary focus: {priorities[0].lower()}"
    
    return {
        "performance_rating": rating,
        "tactical_summary": tactical,
        "key_observations": observations,
        "coaching_priorities": priorities,
        "next_training_focus": training_focus
    }

def generate_distribution_coaching_analysis(metrics, score):
    distribution = metrics.get("distribution", {})
    passes = distribution.get("passes", 0)
    passing_accuracy = distribution.get("passing_accuracy_pct", 0)
    assists = distribution.get("assists", 0)
    key_passes = distribution.get("key_passes", 0)
    through_passes = distribution.get("through_passes", 0)
    
    # Performance rating
    if score >= 8.0:
        rating = "Exceptional ball circulation"
    elif score >= 7.0:
        rating = "Strong passing display"
    elif score >= 6.0:
        rating = "Effective distribution"
    elif score >= 5.0:
        rating = "Inconsistent passing game"
    else:
        rating = "Poor ball retention"
    
    # Tactical summary
    if passing_accuracy >= 85 and assists >= 3:
        tactical = f"Masterful distribution with {passing_accuracy:.1f}% accuracy and {assists} assists. The team controlled the tempo and created numerous scoring opportunities."
    elif passing_accuracy >= 80:
        tactical = f"Solid passing foundation with {passing_accuracy:.1f}% accuracy from {passes} attempts. Good ball retention allowed for sustained pressure."
    else:
        tactical = f"Struggled with ball retention at {passing_accuracy:.1f}% accuracy. The team needs to improve passing under pressure and decision-making."
    
    # Key observations
    observations = []
    if passing_accuracy >= 85:
        observations.append(f"Excellent passing accuracy of {passing_accuracy:.1f}% demonstrates superior technical ability and composure")
    if through_passes >= 20:
        observations.append(f"Outstanding penetrative passing with {through_passes} through balls shows excellent vision and execution")
    if assists >= 3:
        observations.append(f"Strong creative output with {assists} assists indicates good final ball delivery")
    if key_passes < 3 and passes > 200:
        observations.append(f"High passing volume but limited key passes suggests need for more incisive distribution")
    
    # Ensure minimum key observations
    if len(observations) == 0:
        if passes > 100:
            observations.append(f"Completed {passes} passes with {passing_accuracy:.1f}% accuracy maintaining team possession")
        if through_passes > 0:
            observations.append(f"Attempted {through_passes} penetrating passes showing forward intent")
        if assists > 0:
            observations.append(f"Created {assists} assists demonstrating creative contribution")
        if len(observations) == 0:
            observations.append("Maintained ball circulation and team connectivity")
    
    # Coaching priorities
    priorities = []
    if passing_accuracy < 75:
        priorities.append("Improve passing technique and decision-making under pressure")
    if assists < 2 and passes > 150:
        priorities.append("Focus on final ball delivery and creating clear scoring chances")
    if key_passes < 4:
        priorities.append("Develop vision and execution for defense-splitting passes")
    if through_passes < 10:
        priorities.append("Practice penetrative passing and forward play patterns")
    
    # Add priorities for strong performances to continue development
    if len(priorities) == 0:
        if passing_accuracy >= 85 and assists >= 3:
            priorities.append("Develop quick passing combinations in tight spaces")
            priorities.append("Work on switching play and changing tempo")
        elif passing_accuracy >= 80:
            priorities.append("Enhance creativity and risk-taking in final third")
            priorities.append("Improve passing variety and range selection")
        else:
            priorities.append("Focus on maintaining possession under pressure")
            priorities.append("Develop better passing angles and movement")
    
    # Training focus
    training_focus = f"Primary focus: {priorities[0].lower()}"
    
    return {
        "performance_rating": rating,
        "tactical_summary": tactical,
        "key_observations": observations,
        "coaching_priorities": priorities,
        "next_training_focus": training_focus
    }

def generate_discipline_coaching_analysis(metrics, score):
    discipline = metrics.get("discipline", {})
    fouls_conceded = discipline.get("fouls_conceded", 0)
    yellow_cards = discipline.get("yellow_cards", 0)
    red_cards = discipline.get("red_cards", 0)
    
    # Performance rating
    if score >= 8.0:
        rating = "Exemplary discipline"
    elif score >= 7.0:
        rating = "Well-controlled performance"
    elif score >= 6.0:
        rating = "Acceptable discipline level"
    elif score >= 5.0:
        rating = "Discipline concerns"
    else:
        rating = "Poor emotional control"
    
    # Tactical summary
    if red_cards == 0 and yellow_cards <= 2:
        tactical = f"Excellent discipline with no red cards and {yellow_cards} bookings. The team maintained composure and controlled aggression throughout the match."
    elif red_cards == 0:
        tactical = f"Generally disciplined performance despite {yellow_cards} yellow cards. Good emotional control prevented serious disciplinary issues."
    else:
        tactical = f"Disciplinary problems with {red_cards} red card(s) and {yellow_cards} yellows. The team must improve emotional control and decision-making."
    
    # Key observations
    observations = []
    if red_cards == 0 and yellow_cards <= 1:
        observations.append("Outstanding emotional control and clean play throughout the match")
    if fouls_conceded <= 10:
        observations.append(f"Minimal fouling with only {fouls_conceded} infractions shows good defensive technique")
    if red_cards > 0:
        observations.append(f"Red card offense highlights need for better emotional regulation under pressure")
    if fouls_conceded >= 18:
        observations.append(f"High foul count of {fouls_conceded} suggests aggressive play needs better channeling")
    
    # Ensure minimum key observations
    if len(observations) == 0:
        if yellow_cards == 0:
            observations.append(f"Maintained clean record with no bookings and {fouls_conceded} fouls")
        elif yellow_cards <= 3:
            observations.append(f"Showed discipline with {yellow_cards} yellow cards and {fouls_conceded} total fouls")
        else:
            observations.append(f"Struggled with discipline: {yellow_cards} cards and {fouls_conceded} fouls committed")
        if len(observations) == 0:
            observations.append("Team competed with appropriate intensity levels")
    
    # Coaching priorities
    priorities = []
    if red_cards > 0:
        priorities.append("Address emotional control and decision-making in high-pressure situations")
    if yellow_cards >= 4:
        priorities.append("Reduce unnecessary bookings through better tactical discipline")
    if fouls_conceded >= 18:
        priorities.append("Improve defensive technique to reduce fouling frequency")
    
    # Add priorities for strong performances to continue development
    if len(priorities) == 0:
        if red_cards == 0 and yellow_cards <= 1:
            priorities.append("Maintain excellent discipline while increasing defensive aggression")
            priorities.append("Develop leadership skills to help teammates stay composed")
        elif fouls_conceded <= 12:
            priorities.append("Balance clean play with necessary tactical fouling")
            priorities.append("Work on game management and time-wasting techniques")
        else:
            priorities.append("Focus on channeling aggression more effectively")
            priorities.append("Improve decision-making in defensive situations")
    
    # Training focus
    training_focus = f"Primary focus: {priorities[0].lower()}"
    
    return {
        "performance_rating": rating,
        "tactical_summary": tactical,
        "key_observations": observations,
        "coaching_priorities": priorities,
        "next_training_focus": training_focus
    }

def generate_general_coaching_analysis(metrics, score):
    general = metrics.get("general", {})
    possession = metrics.get("possession", {})
    duel_success_rate = general.get("duels_success_rate_pct", 0)
    possession_pct = possession.get("possession_pct", 0)
    attacking_third_possession_pct = possession.get("attacking_third_possession_pct_of_team", 0)
    corners_awarded = general.get("corner_awarded", 0)  # Fix missing variable
    
    # Performance rating
    if score >= 8.0:
        rating = "Commanding overall display"
    elif score >= 7.0:
        rating = "Strong all-round performance"
    elif score >= 6.0:
        rating = "Balanced team performance"
    elif score >= 5.0:
        rating = "Inconsistent team display"
    else:
        rating = "Poor overall execution"
    
    # Tactical summary
    if possession_pct >= 55 and duel_success_rate >= 55:
        tactical = f"Controlled the match with {possession_pct:.1f}% possession and {duel_success_rate:.1f}% duels won. The team imposed their game plan effectively."
    elif possession_pct >= 50:
        tactical = f"Maintained good territorial control with {possession_pct:.1f}% possession. Solid foundation for building attacks and controlling tempo."
    else:
        tactical = f"Struggled to control the match with only {possession_pct:.1f}% possession. The team needs to improve ball retention and territorial dominance."
    
    # Key observations
    observations = []
    if duel_success_rate >= 60:
        observations.append(f"Excellent physical dominance with {duel_success_rate:.1f}% duels won shows superior fitness and commitment")
    if possession_pct >= 60:
        observations.append(f"Outstanding ball control with {possession_pct:.1f}% possession demonstrates excellent technical ability")
    if attacking_third_possession_pct >= 15:
        observations.append(f"Strong attacking territory control with {attacking_third_possession_pct:.1f}% of possession in final third")
    if duel_success_rate < 45:
        observations.append(f"Poor physical battles with only {duel_success_rate:.1f}% duels won indicates fitness or commitment issues")
    
    # Ensure minimum key observations
    if len(observations) == 0:
        if possession_pct > 50:
            observations.append(f"Controlled match tempo with {possession_pct:.1f}% possession and {duel_success_rate:.1f}% duels won")
        elif possession_pct > 40:
            observations.append(f"Maintained competitive presence with {possession_pct:.1f}% possession")
        if corners_awarded > 0:
            observations.append(f"Generated {corners_awarded} corner opportunities showing attacking intent")
        if len(observations) == 0:
            observations.append("Team showed competitive spirit and organizational structure")
    
    # Coaching priorities
    priorities = []
    if duel_success_rate < 50:
        priorities.append("Improve physical preparation and commitment in duels")
    if possession_pct < 45:
        priorities.append("Develop ball retention skills and passing under pressure")
    if attacking_third_possession_pct < 10:
        priorities.append("Create more attacking presence and forward movement patterns")
    
    # Add priorities for strong performances to continue development
    if len(priorities) == 0:
        if duel_success_rate >= 60 and possession_pct >= 55:
            priorities.append("Develop game management skills for controlling match tempo")
            priorities.append("Work on transitioning between different phases of play")
        elif possession_pct >= 50:
            priorities.append("Improve efficiency in final third possession")
            priorities.append("Enhance physical conditioning for sustained pressure")
        else:
            priorities.append("Focus on winning more territorial battles")
            priorities.append("Develop better pressing coordination and triggers")
    
    # Training focus
    training_focus = f"Primary focus: {priorities[0].lower()}"
    
    return {
        "performance_rating": rating,
        "tactical_summary": tactical,
        "key_observations": observations,
        "coaching_priorities": priorities,
        "next_training_focus": training_focus
    }

def generate_overall_coaching_analysis(metrics, score):
    # Get individual ratings
    attack_rating = metrics.get("match_rating_attack", 0)
    defense_rating = metrics.get("match_rating_defense", 0)
    distribution_rating = metrics.get("match_rating_distribution", 0)
    general_rating = metrics.get("match_rating_general", 0)
    discipline_rating = metrics.get("match_rating_discipline", 0)
    
    # Performance rating
    if score >= 8.0:
        rating = "Outstanding team performance"
    elif score >= 7.0:
        rating = "Strong collective display"
    elif score >= 6.0:
        rating = "Solid team effort"
    elif score >= 5.0:
        rating = "Mixed team performance"
    else:
        rating = "Poor collective display"
    
    # Find strongest and weakest areas
    ratings = {
        "attacking play": attack_rating,
        "defensive organization": defense_rating,
        "ball distribution": distribution_rating,
        "general team play": general_rating,
        "team discipline": discipline_rating
    }
    
    sorted_ratings = sorted(ratings.items(), key=lambda x: x[1], reverse=True)
    strongest_areas = [area for area, rating in sorted_ratings[:2] if rating >= 6.5]
    weakest_areas = [area for area, rating in sorted_ratings[-2:] if rating < 6.5]
    
    # Tactical summary
    if score >= 7.0:
        tactical = f"Commanding team performance across multiple phases of play. The squad demonstrated tactical maturity and executed the game plan with precision."
    elif score >= 6.0:
        tactical = f"Solid team foundation with clear strengths in {strongest_areas[0] if strongest_areas else 'several areas'}. Good platform for continued development."
    else:
        tactical = f"Inconsistent team display with significant room for improvement. The squad needs to address fundamental tactical and technical deficiencies."
    
    # Key observations
    observations = []
    for area in strongest_areas:
        rating_val = ratings[area]
        observations.append(f"Excellent {area} (rating: {rating_val:.1f}) provided strong foundation for team performance")
    
    for area in weakest_areas:
        rating_val = ratings[area]
        observations.append(f"Weak {area} (rating: {rating_val:.1f}) undermined overall team effectiveness")
    
    # Coaching priorities
    priorities = []
    if len(weakest_areas) > 0:
        priorities.append(f"Address deficiencies in {weakest_areas[0]} through targeted tactical work")
    if len(weakest_areas) > 1:
        priorities.append(f"Strengthen {weakest_areas[1]} to improve team balance")
    if score < 6.0:
        priorities.append("Improve overall team cohesion and tactical understanding")
    
    # Ensure overall priorities always exist
    if len(priorities) == 0:
        if score >= 7.5:
            priorities.append("Fine-tune tactical details while maintaining performance level")
            priorities.append("Develop squad rotation to manage player workload")
        elif score >= 6.5:
            priorities.append("Build consistency across all phases of play")
            priorities.append("Strengthen tactical flexibility for different opponents")
        else:
            priorities.append("Address fundamental tactical understanding")
            priorities.append("Improve basic technical skills across the squad")
    
    # Training focus
    training_focus = f"Primary focus: {priorities[0].lower()}"
    
    return {
        "performance_rating": rating,
        "tactical_summary": tactical,
        "key_observations": observations,
        "coaching_priorities": priorities,
        "next_training_focus": training_focus
    }


# ---------- DETAILED ANALYSIS FUNCTIONS ----------
def analyze_attack(metrics, score):
    analysis = {
        "overall_assessment": interpret_score(score),
        "strengths": [],
        "areas_for_improvement": [],
        "key_metrics": {}
    }
    
    if score is None:
        return {"overall_assessment": "No prediction available", "strengths": [], "areas_for_improvement": [], "key_metrics": {}}
    
    attack = metrics.get("attack", {})
    
    # Extract key metrics
    goals = attack.get("goals", 0)
    shots = attack.get("shots", 0)
    shots_on_target = attack.get("shots_on_target", 0)
    shots_off_target = attack.get("shots_off_target", 0)
    blocked_shots = attack.get("blocked_shots", 0)
    shot_creating_actions = attack.get("shot_creating_actions", 0)
    
    analysis["key_metrics"] = {
        "goals_scored": goals,
        "total_shots": shots,
        "shots_on_target": shots_on_target,
        "shooting_accuracy": round((shots_on_target / shots * 100) if shots > 0 else 0, 1),
        "shot_creating_actions": shot_creating_actions
    }
    
    # Analyze strengths
    if goals >= 3:
        analysis["strengths"].append(f"Excellent goal conversion with {goals} goals scored")
    elif goals >= 2:
        analysis["strengths"].append(f"Good attacking output with {goals} goals")
    
    shooting_accuracy = (shots_on_target / shots * 100) if shots > 0 else 0
    if shooting_accuracy >= 50:
        analysis["strengths"].append(f"High shooting accuracy at {shooting_accuracy:.1f}%")
    elif shooting_accuracy >= 35:
        analysis["strengths"].append(f"Decent shooting accuracy at {shooting_accuracy:.1f}%")
    
    if shot_creating_actions >= 25:
        analysis["strengths"].append(f"Strong creative play with {shot_creating_actions} shot-creating actions")
    
    if shots >= 15:
        analysis["strengths"].append(f"Good attacking volume with {shots} total shots")
    
    # Ensure minimum strengths for any attack
    if len(analysis["strengths"]) == 0:
        if shots > 0:
            analysis["strengths"].append(f"Generated attacking opportunities with {shots} attempts")
        if shot_creating_actions > 0:
            analysis["strengths"].append(f"Created attacking moments with {shot_creating_actions} shot-creating actions")
        if len(analysis["strengths"]) == 0:
            analysis["strengths"].append("Maintained attacking intent throughout the match")
    
    # Analyze areas for improvement
    if goals < 2:
        analysis["areas_for_improvement"].append(f"Low goal output with only {goals} goals - need better finishing")
    
    if shooting_accuracy < 30:
        analysis["areas_for_improvement"].append(f"Poor shooting accuracy at {shooting_accuracy:.1f}% - need more precision")
    
    if shots < 10:
        analysis["areas_for_improvement"].append(f"Limited attacking threat with only {shots} shots - need more attempts")
    
    if blocked_shots >= shots_on_target and blocked_shots > 3:
        analysis["areas_for_improvement"].append(f"Too many shots blocked ({blocked_shots}) - need better positioning")
    
    if shot_creating_actions < 15:
        analysis["areas_for_improvement"].append(f"Limited creativity with only {shot_creating_actions} shot-creating actions")
    
    # Ensure minimum areas for improvement
    if len(analysis["areas_for_improvement"]) == 0:
        if shooting_accuracy < 60:
            analysis["areas_for_improvement"].append(f"Could improve shooting accuracy from {shooting_accuracy:.1f}% to be more clinical")
        if goals < 4:
            analysis["areas_for_improvement"].append(f"Opportunity to increase goal output beyond {goals} goals")
        if len(analysis["areas_for_improvement"]) == 0:
            analysis["areas_for_improvement"].append("Continue developing attacking patterns and movement")
    
    return analysis

def analyze_defense(metrics, score):
    analysis = {
        "overall_assessment": interpret_score(score),
        "strengths": [],
        "areas_for_improvement": [],
        "key_metrics": {}
    }
    
    if score is None:
        return {"overall_assessment": "No prediction available", "strengths": [], "areas_for_improvement": [], "key_metrics": {}}
    
    defense = metrics.get("defense", {})
    
    # Extract key metrics
    tackles = defense.get("tackles", 0)
    successful_tackles = defense.get("successful_tackles", 0)
    tackle_success_rate = defense.get("tackles_success_rate_pct", 0)
    clearances = defense.get("clearances", 0)
    interceptions = defense.get("interceptions", 0)
    recoveries = defense.get("recoveries", 0)
    blocks = defense.get("blocks", 0)
    saves = defense.get("saves", 0)
    
    analysis["key_metrics"] = {
        "tackles_attempted": tackles,
        "tackle_success_rate": f"{tackle_success_rate:.1f}%",
        "interceptions": interceptions,
        "recoveries": recoveries,
        "clearances": clearances,
        "blocks": blocks,
        "saves": saves
    }
    
    # Analyze strengths
    if tackle_success_rate >= 80:
        analysis["strengths"].append(f"Excellent tackling efficiency at {tackle_success_rate:.1f}%")
    elif tackle_success_rate >= 65:
        analysis["strengths"].append(f"Good tackling success rate at {tackle_success_rate:.1f}%")
    
    if interceptions >= 5:
        analysis["strengths"].append(f"Strong anticipation with {interceptions} interceptions")
    
    if recoveries >= 15:
        analysis["strengths"].append(f"Good ball recovery with {recoveries} recoveries")
    
    if clearances >= 8:
        analysis["strengths"].append(f"Solid defensive clearances with {clearances} clearances")
    
    if saves >= 5:
        analysis["strengths"].append(f"Good goalkeeping with {saves} saves")
    
    # Ensure minimum strengths for any defense
    if len(analysis["strengths"]) == 0:
        if tackle_success_rate >= 50:
            analysis["strengths"].append(f"Maintained defensive pressure with {tackle_success_rate:.1f}% tackle success")
        if interceptions > 0:
            analysis["strengths"].append(f"Showed defensive awareness with {interceptions} interceptions")
        if clearances > 0:
            analysis["strengths"].append(f"Provided defensive security with {clearances} clearances")
        if len(analysis["strengths"]) == 0:
            analysis["strengths"].append("Maintained defensive organization throughout the match")
    
    # Analyze areas for improvement
    if tackle_success_rate < 50:
        analysis["areas_for_improvement"].append(f"Poor tackling efficiency at {tackle_success_rate:.1f}% - need better timing")
    
    if tackles < 5:
        analysis["areas_for_improvement"].append(f"Low defensive engagement with only {tackles} tackles attempted")
    
    if interceptions < 3:
        analysis["areas_for_improvement"].append(f"Poor anticipation with only {interceptions} interceptions")
    
    if recoveries < 10:
        analysis["areas_for_improvement"].append(f"Limited ball recovery with only {recoveries} recoveries")
    
    if blocks < 2:
        analysis["areas_for_improvement"].append("Limited shot blocking - need better defensive positioning")
    
    # Ensure minimum areas for improvement
    if len(analysis["areas_for_improvement"]) == 0:
        if tackle_success_rate < 90:
            analysis["areas_for_improvement"].append(f"Could improve tackle efficiency beyond {tackle_success_rate:.1f}%")
        if interceptions < 10:
            analysis["areas_for_improvement"].append(f"Opportunity to increase defensive reading with more than {interceptions} interceptions")
        if len(analysis["areas_for_improvement"]) == 0:
            analysis["areas_for_improvement"].append("Continue developing defensive communication and coordination")
    
    return analysis

def analyze_distribution(metrics, score):
    analysis = {
        "overall_assessment": interpret_score(score),
        "strengths": [],
        "areas_for_improvement": [],
        "key_metrics": {}
    }
    
    if score is None:
        return {"overall_assessment": "No prediction available", "strengths": [], "areas_for_improvement": [], "key_metrics": {}}
    
    distribution = metrics.get("distribution", {})
    
    # Extract key metrics
    passes = distribution.get("passes", 0)
    successful_passes = distribution.get("successful_passes", 0)
    passing_accuracy = distribution.get("passing_accuracy_pct", 0)
    assists = distribution.get("assists", 0)
    key_passes = distribution.get("key_passes", 0)
    long_passes = distribution.get("long_passes", 0)
    through_passes = distribution.get("through_passes", 0)
    crosses = distribution.get("crosses", 0)
    forward_passes = distribution.get("forward_passes", 0)
    
    analysis["key_metrics"] = {
        "total_passes": passes,
        "passing_accuracy": f"{passing_accuracy:.1f}%",
        "assists": assists,
        "key_passes": key_passes,
        "through_passes": through_passes,
        "crosses": crosses
    }
    
    # Analyze strengths
    if passing_accuracy >= 85:
        analysis["strengths"].append(f"Excellent passing accuracy at {passing_accuracy:.1f}%")
    elif passing_accuracy >= 75:
        analysis["strengths"].append(f"Good passing accuracy at {passing_accuracy:.1f}%")
    
    if assists >= 3:
        analysis["strengths"].append(f"Strong creative output with {assists} assists")
    
    if key_passes >= 5:
        analysis["strengths"].append(f"Good chance creation with {key_passes} key passes")
    
    if through_passes >= 15:
        analysis["strengths"].append(f"Excellent penetrating passes with {through_passes} through balls")
    
    if passes >= 250:
        analysis["strengths"].append(f"High passing volume with {passes} total passes")
    
    # Ensure minimum strengths for any distribution
    if len(analysis["strengths"]) == 0:
        if passing_accuracy >= 65:
            analysis["strengths"].append(f"Maintained ball circulation with {passing_accuracy:.1f}% accuracy")
        if through_passes >= 5:
            analysis["strengths"].append(f"Showed forward intent with {through_passes} penetrating passes")
        if passes >= 100:
            analysis["strengths"].append(f"Sustained possession with {passes} passing attempts")
        if len(analysis["strengths"]) == 0:
            analysis["strengths"].append("Maintained team connectivity through passing")
    
    # Analyze areas for improvement
    if passing_accuracy < 70:
        analysis["areas_for_improvement"].append(f"Poor passing accuracy at {passing_accuracy:.1f}% - need better ball control")
    
    if assists < 2:
        analysis["areas_for_improvement"].append(f"Limited assists ({assists}) - need better final ball delivery")
    
    if key_passes < 3:
        analysis["areas_for_improvement"].append(f"Poor chance creation with only {key_passes} key passes")
    
    if through_passes < 10:
        analysis["areas_for_improvement"].append("Limited penetrating passes - need more direct play")
    
    if crosses < 5 and forward_passes > 100:
        analysis["areas_for_improvement"].append("Limited crossing attempts - need better wide play")
    
    # Ensure minimum areas for improvement
    if len(analysis["areas_for_improvement"]) == 0:
        if passing_accuracy < 95:
            analysis["areas_for_improvement"].append(f"Could achieve higher passing accuracy than {passing_accuracy:.1f}%")
        if assists < 5:
            analysis["areas_for_improvement"].append(f"Opportunity to increase creative output beyond {assists} assists")
        if len(analysis["areas_for_improvement"]) == 0:
            analysis["areas_for_improvement"].append("Continue developing passing range and creativity")
    
    return analysis

def analyze_discipline(metrics, score):
    analysis = {
        "overall_assessment": interpret_score(score),
        "strengths": [],
        "areas_for_improvement": [],
        "key_metrics": {}
    }
    
    if score is None:
        return {"overall_assessment": "No prediction available", "strengths": [], "areas_for_improvement": [], "key_metrics": {}}
    
    discipline = metrics.get("discipline", {})
    
    # Extract key metrics
    fouls_conceded = discipline.get("fouls_conceded", 0)
    yellow_cards = discipline.get("yellow_cards", 0)
    red_cards = discipline.get("red_cards", 0)
    
    analysis["key_metrics"] = {
        "fouls_conceded": fouls_conceded,
        "yellow_cards": yellow_cards,
        "red_cards": red_cards,
        "total_disciplinary_actions": yellow_cards + red_cards
    }
    
    # Analyze strengths
    if red_cards == 0:
        analysis["strengths"].append("Good discipline with no red cards")
    
    if yellow_cards <= 2:
        analysis["strengths"].append(f"Controlled aggression with only {yellow_cards} yellow cards")
    
    if fouls_conceded <= 12:
        analysis["strengths"].append(f"Clean play with only {fouls_conceded} fouls conceded")
    
    # Ensure minimum strengths for discipline
    if len(analysis["strengths"]) == 0:
        if red_cards == 0:
            analysis["strengths"].append("Avoided serious disciplinary action")
        if fouls_conceded < 20:
            analysis["strengths"].append(f"Kept fouling manageable at {fouls_conceded} infractions")
        if len(analysis["strengths"]) == 0:
            analysis["strengths"].append("Maintained competitive intensity")
    
    # Analyze areas for improvement
    if red_cards > 0:
        analysis["areas_for_improvement"].append(f"Poor discipline with {red_cards} red card(s) - need better emotional control")
    
    if yellow_cards >= 4:
        analysis["areas_for_improvement"].append(f"Too many bookings with {yellow_cards} yellow cards")
    
    if fouls_conceded >= 20:
        analysis["areas_for_improvement"].append(f"Excessive fouling with {fouls_conceded} fouls - need cleaner tackling")
    elif fouls_conceded >= 15:
        analysis["areas_for_improvement"].append(f"High foul count at {fouls_conceded} - need more controlled defending")
    
    # Ensure minimum areas for improvement
    if len(analysis["areas_for_improvement"]) == 0:
        if yellow_cards > 0:
            analysis["areas_for_improvement"].append(f"Could reduce bookings below {yellow_cards} cards")
        if fouls_conceded > 5:
            analysis["areas_for_improvement"].append(f"Opportunity to play cleaner with fewer than {fouls_conceded} fouls")
        if len(analysis["areas_for_improvement"]) == 0:
            analysis["areas_for_improvement"].append("Continue maintaining excellent disciplinary standards")
    
    return analysis

def analyze_general(metrics, score):
    analysis = {
        "overall_assessment": interpret_score(score),
        "strengths": [],
        "areas_for_improvement": [],
        "key_metrics": {}
    }
    
    if score is None:
        return {"overall_assessment": "No prediction available", "strengths": [], "areas_for_improvement": [], "key_metrics": {}}
    
    general = metrics.get("general", {})
    possession = metrics.get("possession", {})
    
    # Extract key metrics
    duels = general.get("duels", 0)
    duels_won = general.get("duels_won", 0)
    duel_success_rate = general.get("duels_success_rate_pct", 0)
    aerial_duels = general.get("aerial_duels", 0)
    possession_pct = possession.get("possession_pct", 0)
    attacking_third_possession_pct = possession.get("attacking_third_possession_pct_of_team", 0)
    offsides = general.get("offsides", 0)
    corners_awarded = general.get("corner_awarded", 0)
    
    analysis["key_metrics"] = {
        "duels_won_percentage": f"{duel_success_rate:.1f}%",
        "possession_percentage": f"{possession_pct:.1f}%",
        "attacking_third_possession": f"{attacking_third_possession_pct:.1f}%",
        "corners_awarded": corners_awarded,
        "offsides": offsides
    }
    
    # Analyze strengths
    if duel_success_rate >= 55:
        analysis["strengths"].append(f"Strong physical presence with {duel_success_rate:.1f}% duels won")
    
    if possession_pct >= 55:
        analysis["strengths"].append(f"Good ball control with {possession_pct:.1f}% possession")
    
    if attacking_third_possession_pct >= 15:
        analysis["strengths"].append(f"Strong attacking presence with {attacking_third_possession_pct:.1f}% attacking third possession")
    
    if corners_awarded >= 6:
        analysis["strengths"].append(f"Good attacking pressure with {corners_awarded} corners won")
    
    if offsides <= 2:
        analysis["strengths"].append(f"Good positioning discipline with only {offsides} offsides")
    
    # Ensure minimum strengths for general play
    if len(analysis["strengths"]) == 0:
        if duel_success_rate >= 45:
            analysis["strengths"].append(f"Competitive in physical battles with {duel_success_rate:.1f}% duels won")
        if possession_pct >= 40:
            analysis["strengths"].append(f"Maintained ball presence with {possession_pct:.1f}% possession")
        if corners_awarded > 0:
            analysis["strengths"].append(f"Generated attacking situations with {corners_awarded} corners")
        if len(analysis["strengths"]) == 0:
            analysis["strengths"].append("Maintained competitive engagement throughout the match")
    
    # Analyze areas for improvement
    if duel_success_rate < 45:
        analysis["areas_for_improvement"].append(f"Poor physical battles with only {duel_success_rate:.1f}% duels won")
    
    if possession_pct < 40:
        analysis["areas_for_improvement"].append(f"Poor ball retention with only {possession_pct:.1f}% possession")
    
    if attacking_third_possession_pct < 8:
        analysis["areas_for_improvement"].append("Limited attacking territory control - need more forward play")
    
    if corners_awarded < 3:
        analysis["areas_for_improvement"].append("Limited attacking pressure - need more corner-winning attacks")
    
    if offsides >= 5:
        analysis["areas_for_improvement"].append(f"Poor positioning with {offsides} offsides - need better timing")
    
    # Ensure minimum areas for improvement
    if len(analysis["areas_for_improvement"]) == 0:
        if duel_success_rate < 70:
            analysis["areas_for_improvement"].append(f"Could improve physical dominance beyond {duel_success_rate:.1f}% duels won")
        if possession_pct < 65:
            analysis["areas_for_improvement"].append(f"Opportunity to control more possession than {possession_pct:.1f}%")
        if len(analysis["areas_for_improvement"]) == 0:
            analysis["areas_for_improvement"].append("Continue developing overall game management skills")
    
    return analysis

def analyze_overall(metrics, score):
    analysis = {
        "overall_assessment": interpret_score(score),
        "key_strengths": [],
        "priority_improvements": [],
        "match_summary": ""
    }
    
    if score is None:
        return {"overall_assessment": "No prediction available", "key_strengths": [], "priority_improvements": [], "match_summary": ""}
    
    # Get ratings for each area
    attack_rating = metrics.get("match_rating_attack", 0)
    defense_rating = metrics.get("match_rating_defense", 0)
    distribution_rating = metrics.get("match_rating_distribution", 0)
    general_rating = metrics.get("match_rating_general", 0)
    discipline_rating = metrics.get("match_rating_discipline", 0)
    
    # Find strongest and weakest areas
    ratings = {
        "attack": attack_rating,
        "defense": defense_rating,
        "distribution": distribution_rating,
        "general": general_rating,
        "discipline": discipline_rating
    }
    
    sorted_ratings = sorted(ratings.items(), key=lambda x: x[1], reverse=True)
    strongest_area = sorted_ratings[0]
    weakest_area = sorted_ratings[-1]
    
    # Key strengths (top 2 areas)
    for area, rating in sorted_ratings[:2]:
        if rating >= 7.0:
            analysis["key_strengths"].append(f"{area.title()}: Excellent performance (rating: {rating:.1f})")
        elif rating >= 6.0:
            analysis["key_strengths"].append(f"{area.title()}: Solid performance (rating: {rating:.1f})")
    
    # Priority improvements (bottom 2 areas)
    for area, rating in sorted_ratings[-2:]:
        if rating < 5.5:
            analysis["priority_improvements"].append(f"{area.title()}: Critical improvement needed (rating: {rating:.1f})")
        elif rating < 6.5:
            analysis["priority_improvements"].append(f"{area.title()}: Room for improvement (rating: {rating:.1f})")
    
    # Match summary
    if score >= 7.5:
        performance_level = "outstanding"
    elif score >= 6.5:
        performance_level = "solid"
    elif score >= 5.5:
        performance_level = "mixed"
    else:
        performance_level = "concerning"
    
    analysis["match_summary"] = f"Overall {performance_level} team performance. Strongest in {strongest_area[0]} (rating: {strongest_area[1]:.1f}), needs most work in {weakest_area[0]} (rating: {weakest_area[1]:.1f})."
    
    return analysis


# ---------- RICH INTERPRETATION FUNCTIONS ----------
def generate_rich_interpretation(area, metrics, score):
    """Generate rich, data-driven interpretations that go beyond simple ratings"""
    
    if score is None:
        return "Performance data unavailable for comprehensive analysis"
    
    if area == "attack":
        return generate_attack_rich_interpretation(metrics, score)
    elif area == "defense":
        return generate_defense_rich_interpretation(metrics, score)
    elif area == "distribution":
        return generate_distribution_rich_interpretation(metrics, score)
    elif area == "discipline":
        return generate_discipline_rich_interpretation(metrics, score)
    elif area == "general":
        return generate_general_rich_interpretation(metrics, score)
    elif area == "overall":
        return generate_overall_rich_interpretation(metrics, score)
    
    return interpret_score(score)

def generate_attack_rich_interpretation(metrics, score):
    attack = metrics.get("attack", {})
    goals = attack.get("goals", 0)
    shots = attack.get("shots", 0)
    shots_on_target = attack.get("shots_on_target", 0)
    shot_creating_actions = attack.get("shot_creating_actions", 0)
    
    shooting_accuracy = (shots_on_target / shots * 100) if shots > 0 else 0
    conversion_rate = (goals / shots * 100) if shots > 0 else 0
    
    base_rating = interpret_score(score)
    
    # Add rich context based on metrics
    if goals >= 4 and shooting_accuracy >= 40:
        return f"{base_rating} - Clinical attacking display with {goals} goals from {shooting_accuracy:.1f}% shooting accuracy. Excellent combination of volume ({shots} shots) and precision."
    elif goals >= 3:
        return f"{base_rating} - Productive attacking performance with {goals} goals. Created {shot_creating_actions} attacking actions showing good creative movement."
    elif shots >= 15 and goals < 2:
        return f"{base_rating} - High attacking volume ({shots} shots) but poor conversion rate ({conversion_rate:.1f}%). Clinical finishing is the missing piece."
    elif shooting_accuracy < 25 and shots > 5:
        return f"{base_rating} - Struggled with shot accuracy ({shooting_accuracy:.1f}%) despite {shot_creating_actions} creative actions. Need better decision-making in final third."
    elif shots < 8:
        return f"{base_rating} - Limited attacking threat with only {shots} attempts. Team needs to create more goal-scoring opportunities through better build-up play."
    else:
        return f"{base_rating} - Balanced attacking approach with {goals} goals from {shots} attempts ({conversion_rate:.1f}% conversion)."

def generate_defense_rich_interpretation(metrics, score):
    defense = metrics.get("defense", {})
    tackles = defense.get("tackles", 0)
    tackle_success_rate = defense.get("tackles_success_rate_pct", 0)
    interceptions = defense.get("interceptions", 0)
    recoveries = defense.get("recoveries", 0)
    clearances = defense.get("clearances", 0)
    saves = defense.get("saves", 0)
    
    base_rating = interpret_score(score)
    
    # Add rich context based on metrics
    if tackle_success_rate >= 85 and interceptions >= 10:
        return f"{base_rating} - Dominant defensive display with {tackle_success_rate:.1f}% tackle success and {interceptions} interceptions. Exceptional reading of the game."
    elif interceptions >= 20:
        return f"{base_rating} - Outstanding anticipation with {interceptions} interceptions. Defensive unit showed excellent positional awareness and game reading."
    elif tackle_success_rate >= 80:
        return f"{base_rating} - Highly efficient defending with {tackle_success_rate:.1f}% tackle success rate. Strong individual defensive duels throughout."
    elif tackles < 5 and interceptions < 5:
        return f"{base_rating} - Limited defensive engagement ({tackles} tackles, {interceptions} interceptions). May indicate good positioning or lack of defensive pressure."
    elif tackle_success_rate < 50:
        return f"{base_rating} - Struggling in defensive duels with {tackle_success_rate:.1f}% tackle success. Timing and positioning need improvement."
    else:
        return f"{base_rating} - Solid defensive structure with {recoveries} ball recoveries and {clearances} clearances providing stability."

def generate_distribution_rich_interpretation(metrics, score):
    distribution = metrics.get("distribution", {})
    passes = distribution.get("passes", 0)
    passing_accuracy = distribution.get("passing_accuracy_pct", 0)
    assists = distribution.get("assists", 0)
    key_passes = distribution.get("key_passes", 0)
    through_passes = distribution.get("through_passes", 0)
    
    base_rating = interpret_score(score)
    
    # Add rich context based on metrics
    if passing_accuracy >= 85 and assists >= 3:
        return f"{base_rating} - Masterful ball circulation with {passing_accuracy:.1f}% accuracy and {assists} assists. Excellent control of match tempo."
    elif through_passes >= 20:
        return f"{base_rating} - Exceptional penetrative passing with {through_passes} through balls. Team showed excellent vision and execution in breaking lines."
    elif passing_accuracy >= 80 and passes >= 250:
        return f"{base_rating} - High-volume passing display ({passes} attempts) with {passing_accuracy:.1f}% accuracy. Strong foundation for territorial control."
    elif assists == 0 and passes > 200:
        return f"{base_rating} - Strong ball retention ({passing_accuracy:.1f}% accuracy) but limited final ball delivery. Need more incisive creativity."
    elif passing_accuracy < 70:
        return f"{base_rating} - Struggled with ball control ({passing_accuracy:.1f}% accuracy). Decision-making under pressure needs improvement."
    else:
        return f"{base_rating} - Balanced distribution with {key_passes} key passes and {through_passes} penetrating balls from {passes} total attempts."

def generate_discipline_rich_interpretation(metrics, score):
    discipline = metrics.get("discipline", {})
    fouls_conceded = discipline.get("fouls_conceded", 0)
    yellow_cards = discipline.get("yellow_cards", 0)
    red_cards = discipline.get("red_cards", 0)
    
    base_rating = interpret_score(score)
    
    # Add rich context based on metrics
    if red_cards == 0 and yellow_cards == 0:
        return f"{base_rating} - Exemplary discipline with no cards and only {fouls_conceded} fouls. Perfect emotional control throughout the match."
    elif red_cards == 0 and yellow_cards <= 2:
        return f"{base_rating} - Well-controlled performance with {yellow_cards} bookings and {fouls_conceded} fouls. Good balance of aggression and restraint."
    elif red_cards > 0:
        return f"{base_rating} - Significant discipline breakdown with {red_cards} red card(s). Critical loss of emotional control undermined team performance."
    elif yellow_cards >= 4:
        return f"{base_rating} - Multiple booking issues with {yellow_cards} yellow cards. Team needs better decision-making in tackle timing."
    elif fouls_conceded >= 20:
        return f"{base_rating} - Overly aggressive approach with {fouls_conceded} fouls conceded. Defensive technique needs refinement."
    else:
        return f"{base_rating} - Reasonable discipline level with {yellow_cards} cards and {fouls_conceded} fouls in competitive match."

def generate_general_rich_interpretation(metrics, score):
    general = metrics.get("general", {})
    possession = metrics.get("possession", {})
    duel_success_rate = general.get("duels_success_rate_pct", 0)
    possession_pct = possession.get("possession_pct", 0)
    attacking_third_possession_pct = possession.get("attacking_third_possession_pct_of_team", 0)
    corners_awarded = general.get("corner_awarded", 0)
    offsides = general.get("offsides", 0)
    
    base_rating = interpret_score(score)
    
    # Add rich context based on metrics
    if possession_pct >= 60 and duel_success_rate >= 60:
        return f"{base_rating} - Dominant overall display controlling {possession_pct:.1f}% possession and {duel_success_rate:.1f}% duels. Imposed game plan effectively."
    elif duel_success_rate >= 70:
        return f"{base_rating} - Excellent physical dominance with {duel_success_rate:.1f}% duels won. Superior fitness and commitment levels."
    elif possession_pct >= 55:
        return f"{base_rating} - Good territorial control with {possession_pct:.1f}% possession. Strong foundation for building sustained attacks."
    elif attacking_third_possession_pct >= 15:
        return f"{base_rating} - Strong attacking presence with {attacking_third_possession_pct:.1f}% possession in final third. Good forward movement patterns."
    elif possession_pct < 40:
        return f"{base_rating} - Struggled for territorial control with only {possession_pct:.1f}% possession. Need better ball retention skills."
    elif duel_success_rate < 45:
        return f"{base_rating} - Poor in physical battles ({duel_success_rate:.1f}% duels won). Fitness and commitment need improvement."
    else:
        return f"{base_rating} - Competitive balance with {possession_pct:.1f}% possession and {corners_awarded} corners won."

def generate_overall_rich_interpretation(metrics, score):
    # Get individual ratings for context
    attack_rating = metrics.get("match_rating_attack", 0)
    defense_rating = metrics.get("match_rating_defense", 0)
    distribution_rating = metrics.get("match_rating_distribution", 0)
    general_rating = metrics.get("match_rating_general", 0)
    discipline_rating = metrics.get("match_rating_discipline", 0)
    
    base_rating = interpret_score(score)
    
    # Find strongest and weakest areas
    ratings = {
        "attack": attack_rating,
        "defense": defense_rating,
        "distribution": distribution_rating,
        "general": general_rating,
        "discipline": discipline_rating
    }
    
    sorted_ratings = sorted(ratings.items(), key=lambda x: x[1], reverse=True)
    strongest_area = sorted_ratings[0]
    weakest_area = sorted_ratings[-1]
    
    # Generate rich context
    strength_gap = strongest_area[1] - weakest_area[1]
    
    if score >= 7.5:
        return f"{base_rating} - Excellent all-round team display led by outstanding {strongest_area[0]} play (rating: {strongest_area[1]:.1f}). Well-balanced performance across all phases."
    elif strength_gap >= 1.5:
        return f"{base_rating} - Unbalanced performance with excellent {strongest_area[0]} ({strongest_area[1]:.1f}) masking weaknesses in {weakest_area[0]} ({weakest_area[1]:.1f}). Need more consistency."
    elif score >= 6.5:
        return f"{base_rating} - Solid foundation with strong {strongest_area[0]} performance ({strongest_area[1]:.1f}). Good platform for tactical development."
    elif weakest_area[1] < 5.5:
        return f"{base_rating} - Performance undermined by critical weakness in {weakest_area[0]} ({weakest_area[1]:.1f}). Urgent tactical attention required."
    else:
        return f"{base_rating} - Mixed performance showing potential in {strongest_area[0]} but needing improvement in {weakest_area[0]} for better consistency."


# ---------- HELPER: Score Interpretation ----------
def interpret_score(score):
    if score is None:
        return "No prediction available"
    if score >= 8.5:
        return "Outstanding performance"
    elif score >= 7.0:
        return "Strong performance"
    elif score >= 5.5:
        return "Average, with room for improvement"
    else:
        return "Needs significant improvement"


# ---------- MAIN ----------
if __name__ == "__main__":
    predict_performance()

