# ============================================================
# File: python_scripts/predict_player_insights.py
# Purpose: Generate player insights using DPR + predictions
# FIXED: Use DPR for defenders, predicted DPR for next match
# ============================================================

import json
import os
import sys
import argparse
import pandas as pd
from datetime import datetime, timezone

# Command line argument parsing
parser = argparse.ArgumentParser(description='Generate player insights')
parser.add_argument('--dataset', default='sbu_vs_2worlds', help='Dataset name (e.g., sbu_vs_2worlds)')
args = parser.parse_args()

MATCH_NAME = args.dataset
TEAM_NAME = "San Beda"
# Create match-specific output directory
MATCH_OUTPUT_DIR = f"output/matches/{MATCH_NAME}"
os.makedirs(MATCH_OUTPUT_DIR, exist_ok=True)

DERIVED_METRICS_PATH = f"{MATCH_OUTPUT_DIR}/sanbeda_players_derived_metrics.json"
PREDICTIONS_CSV = "python_scripts/player_dpr_predictions.csv"
OUTPUT_PATH = f"{MATCH_OUTPUT_DIR}/sanbeda_player_insights.json"

# Also save to main directory for compatibility
# MAIN_OUTPUT_PATH = "python_scripts/sanbeda_player_insights.json"

# ---------- LOAD DATA ----------
print(f"Loading metrics: {DERIVED_METRICS_PATH}")
with open(DERIVED_METRICS_PATH, "r", encoding="utf-8") as f:
    metrics = json.load(f)[TEAM_NAME]

print(f"Loading predictions: {PREDICTIONS_CSV}")
pred_df = pd.read_csv(PREDICTIONS_CSV)
pred_df = pred_df.rename(columns={"player": "name"})
pred_dict = pred_df.set_index("name").to_dict("index")

# ---------- BUILD PLAYER LIST ----------
players = []
for name, stats in metrics.items():
    if name.startswith("_"): continue
    
    # Check if this is the new format (already has dpr, minutes_played, etc.)
    if "dpr" in stats and "minutes_played" in stats:
        # New format - use directly
        player = {
            "name": name,
            "position": stats["position"],
            "dpr": stats["dpr"],
            "minutes": stats["minutes_played"],
            "status": stats["status"],
            "number": stats.get("number", 1),
            "dpr_breakdown": stats.get("dpr_breakdown", {}),
            "key_stats_p90": stats.get("key_stats_p90", {})
        }
    else:
        # Old format - convert from individual stat categories
        overall_rating = stats.get("match_rating_overall", 0)
        
        # Build DPR breakdown from individual ratings
        dpr_breakdown = {
            "attack": stats.get("match_rating_attack", 0),
            "defense": stats.get("match_rating_defense", 0), 
            "distribution": stats.get("match_rating_distribution", 0),
            "discipline": stats.get("match_rating_discipline", 0)
        }
        
        # Calculate minutes played from possession time (rough estimate)
        possession_time = stats.get("possession", {}).get("your_possession_time_seconds", 0)
        estimated_minutes = min(90, max(1, possession_time / 60 * 10))
        
        # Build consolidated stats for per-90 calculations
        key_stats_p90 = {}
        
        # Distribution stats
        if "distribution" in stats:
            dist = stats["distribution"]
            key_stats_p90.update({
                "passes_p90": dist.get("passes", 0) * (90 / estimated_minutes),
                "successful_passes_p90": dist.get("successful_passes", 0) * (90 / estimated_minutes),
                "passing_accuracy_pct": dist.get("passing_accuracy_pct", 0),
                "key_passes_p90": dist.get("key_passes", 0) * (90 / estimated_minutes),
                "assists_p90": dist.get("assists", 0) * (90 / estimated_minutes),
                "avg_pass_distance": dist.get("avg_pass_distance", 0),
                "progressive_passes_p90": dist.get("progressive_passes", 0) * (90 / estimated_minutes)
            })
        
        # Attack stats
        if "attack" in stats:
            att = stats["attack"]
            key_stats_p90.update({
                "shots_p90": att.get("shots", 0) * (90 / estimated_minutes),
                "shots_on_target_p90": att.get("shots_on_target", 0) * (90 / estimated_minutes),
                "goals_p90": att.get("goals", 0) * (90 / estimated_minutes),
                "shot_accuracy_pct": att.get("shot_accuracy_pct", 0),
                "goal_conversion_pct": att.get("goal_conversion_pct", 0)
            })
        
        # Defense stats
        if "defense" in stats:
            def_stats = stats["defense"]
            key_stats_p90.update({
                "duels_p90": def_stats.get("duels", 0) * (90 / estimated_minutes),
                "duels_won_p90": def_stats.get("duels_won", 0) * (90 / estimated_minutes),
                "duel_success_rate_pct": def_stats.get("duel_success_rate_pct", 0),
                "tackles_p90": def_stats.get("tackles", 0) * (90 / estimated_minutes),
                "successful_tackles_p90": def_stats.get("successful_tackles", 0) * (90 / estimated_minutes),
                "interceptions_p90": def_stats.get("interceptions", 0) * (90 / estimated_minutes),
                "clearances_p90": def_stats.get("clearances", 0) * (90 / estimated_minutes),
                "recoveries_p90": def_stats.get("recoveries", 0) * (90 / estimated_minutes),
                "blocked_shots_p90": def_stats.get("blocked_shots", 0) * (90 / estimated_minutes)
            })
            
            # Goalkeeper stats if present
            if "saves" in def_stats:
                key_stats_p90.update({
                    "saves_p90": def_stats.get("saves", 0) * (90 / estimated_minutes),
                    "goals_conceded_p90": def_stats.get("goals_conceded", 0) * (90 / estimated_minutes),
                    "save_pct": def_stats.get("save_pct", 0) / 100 if def_stats.get("save_pct", 0) > 1 else def_stats.get("save_pct", 0)
                })

        player = {
            "name": name,
            "position": stats["position"],
            "dpr": overall_rating,
            "minutes": estimated_minutes,
            "status": "starter" if estimated_minutes > 45 else "substitute",
            "number": 1,
            "dpr_breakdown": dpr_breakdown,
            "key_stats_p90": key_stats_p90
        }
    # Add predicted DPR
    if name in pred_dict:
        player["predicted_dpr"] = pred_dict[name]["predicted_dpr"]
        player["dpr_change"] = pred_dict[name]["dpr_change"]
    else:
        player["predicted_dpr"] = player["dpr"]
        player["dpr_change"] = 0.0
    players.append(player)

# ---------- ENHANCED INSIGHT ENGINE ----------
def analyze_strengths_weaknesses(p):
    """Analyze player's strengths and weaknesses based on DPR breakdown and stats"""
    breakdown = p.get("dpr_breakdown", {})
    stats = p.get("key_stats_p90", {})
    pos = p["position"]
    
    strengths = []
    weaknesses = []
    
    # Analyze DPR components
    if breakdown.get("attack", 0) >= 70: strengths.append("Clinical finishing")
    elif breakdown.get("attack", 0) <= 20 and pos in ["ST", "LW", "RW", "CAM"]: 
        weaknesses.append("Goal threat")
    
    if breakdown.get("defense", 0) >= 70: strengths.append("Defensive solidity")
    elif breakdown.get("defense", 0) <= 20 and pos in ["CB", "CDM"]: 
        weaknesses.append("Defensive coverage")
    
    if breakdown.get("distribution", 0) >= 90: strengths.append("Ball distribution")
    elif breakdown.get("distribution", 0) <= 60: 
        weaknesses.append("Passing accuracy")
    
    if breakdown.get("discipline", 0) >= 95: strengths.append("Disciplined play")
    elif breakdown.get("discipline", 0) <= 70: 
        weaknesses.append("Discipline issues")
    
    # Analyze specific stats
    if stats.get("goal_conversion_pct_p90", 0) >= 30: strengths.append("Efficient shooting")
    if stats.get("duel_success_rate_pct_p90", 0) >= 70: strengths.append("Duel dominance")
    if stats.get("tackle_success_rate_pct_p90", 0) >= 70: strengths.append("Tackling ability")
    if stats.get("save_pct", 0) >= 0.7: strengths.append("Shot-stopping")
    
    # Position-specific analysis
    if pos == "GK":
        save_pct = stats.get("save_pct", 0)
        if save_pct >= 0.75: strengths.append("Elite shot-stopping")
        elif save_pct <= 0.5: weaknesses.append("Save percentage")
    
    if pos in ["ST", "LW", "RW"]:
        if stats.get("shots_p90", 0) <= 1: weaknesses.append("Shot volume")
        if stats.get("goals_p90", 0) >= 1: strengths.append("Consistent scorer")
    
    return strengths[:2], weaknesses[:2]  # Top 2 each

def get_tactical_role(p):
    """Determine player's tactical role and fit"""
    pos = p["position"]
    breakdown = p.get("dpr_breakdown", {})
    stats = p.get("key_stats_p90", {})
    
    if pos == "GK":
        if breakdown.get("distribution", 0) >= 80:
            return "Ball-playing goalkeeper"
        return "Shot-stopping specialist"
    
    elif pos == "CB":
        if breakdown.get("distribution", 0) >= 90:
            return "Ball-playing center-back"
        elif breakdown.get("defense", 0) >= 70:
            return "Defensive anchor"
        return "Developing defender"
    
    elif pos == "CDM":
        if stats.get("progressive_passes_p90", 0) >= 8:
            return "Deep-lying playmaker"
        elif breakdown.get("defense", 0) >= 70:
            return "Defensive midfielder"
        return "Box-to-box midfielder"
    
    elif pos == "CAM":
        if stats.get("key_passes_p90", 0) >= 2:
            return "Creative playmaker"
        elif stats.get("goals_p90", 0) >= 0.5:
            return "Attacking midfielder"
        return "Support player"
    
    elif pos in ["LW", "RW"]:
        if stats.get("goals_p90", 0) >= 1:
            return "Goal-scoring winger"
        elif stats.get("assists_p90", 0) >= 0.5:
            return "Creative winger"
        return "Support winger"
    
    elif pos == "ST":
        if stats.get("goals_p90", 0) >= 1:
            return "Clinical striker"
        elif breakdown.get("distribution", 0) >= 80:
            return "Link-up striker"
        return "Target man"
    
    return "Versatile player"

def get_development_focus(p):
    """Suggest areas for development based on weaknesses"""
    pos = p["position"]
    breakdown = p.get("dpr_breakdown", {})
    stats = p.get("key_stats_p90", {})
    
    focus_areas = []
    
    if breakdown.get("attack", 0) <= 30 and pos in ["ST", "LW", "RW", "CAM"]:
        focus_areas.append("Finishing and shot selection")
    
    if breakdown.get("defense", 0) <= 30 and pos in ["CB", "CDM"]:
        focus_areas.append("Defensive positioning")
    
    if breakdown.get("distribution", 0) <= 70:
        focus_areas.append("Passing accuracy and decision-making")
    
    if breakdown.get("discipline", 0) <= 80:
        focus_areas.append("Tactical discipline")
    
    if stats.get("duel_success_rate_pct_p90", 0) <= 40:
        focus_areas.append("Physical duels and strength")
    
    return focus_areas[:2]  # Top 2 priorities

def get_enhanced_insight(p):
    """Generate comprehensive player insight"""
    dpr = p["dpr"]
    pred = p["predicted_dpr"]
    change = p["dpr_change"]
    pos = p["position"]
    mins = p["minutes"]
    
    # Performance level
    if dpr >= 85: level = "Elite"
    elif dpr >= 70: level = "Strong"
    elif dpr >= 50: level = "Solid"
    elif dpr >= 35: level = "Developing"
    else: level = "Needs Focus"
    
    # Trend analysis
    if change > 10: trend_desc = "Major improvement expected"
    elif change > 3: trend_desc = "Positive trajectory"
    elif change > -3: trend_desc = "Maintaining level"
    elif change > -10: trend_desc = "Slight decline expected"
    else: trend_desc = "Significant adjustment needed"
    
    # Get analysis components
    strengths, weaknesses = analyze_strengths_weaknesses(p)
    role = get_tactical_role(p)
    focus_areas = get_development_focus(p)
    
    # Build comprehensive insight
    insight_parts = []
    
    # Base performance
    insight_parts.append(f"**{p['name']}** ({pos}) - {role}")
    insight_parts.append(f"Current: {level} DPR {dpr:.1f}   Predicted: {pred:.1f} ({change:+.1f})")
    insight_parts.append(f"Trend: {trend_desc}")
    
    # Strengths
    if strengths:
        insight_parts.append(f"Strengths: {', '.join(strengths)}")
    
    # Development areas
    if focus_areas:
        insight_parts.append(f"Focus: {', '.join(focus_areas)}")
    
    return " | ".join(insight_parts)

def get_match_recommendation(p):
    """Generate specific match recommendations"""
    dpr = p["dpr"]
    change = p["dpr_change"]
    pos = p["position"]
    mins = p["minutes"]
    breakdown = p.get("dpr_breakdown", {})
    
    if change > 10:
        return f"Strong candidate for increased responsibility - trending upward significantly"
    elif change < -10:
        return f"Monitor closely - may need tactical adjustment or rest"
    elif dpr >= 70:
        return f"Key player - maintain current role and build team play around strengths"
    elif dpr >= 50:
        return f"Reliable performer - good for consistent minutes and team balance"
    elif mins < 45:
        return f"Impact sub potential - consider specific situational deployment"
    else:
        return f"Development focus - use in lower-pressure situations to build confidence"

def get_badge(p):
    """Generate performance badge"""
    dpr = p["dpr"]
    change = p["dpr_change"]
    
    if dpr >= 85: return "Elite Performer"
    elif dpr >= 70: return "Impact Player"
    elif dpr >= 50: return "Solid Contributor"
    elif change > 10: return "Rising Star"
    elif dpr >= 35: return "Developing"
    else: return "Needs Focus"

# ---------- APPLY INSIGHTS ----------
for p in players:
    p["enhanced_insight"] = get_enhanced_insight(p)
    p["match_recommendation"] = get_match_recommendation(p)
    p["badge"] = get_badge(p)
    p["tactical_role"] = get_tactical_role(p)
    
    # Get strengths and weaknesses
    strengths, weaknesses = analyze_strengths_weaknesses(p)
    p["key_strengths"] = strengths
    p["development_areas"] = get_development_focus(p)

# ---------- TEAM ANALYSIS ----------
def analyze_team_balance():
    """Analyze overall team balance and tactical setup"""
    total_players = len(players)
    avg_dpr = sum(p["dpr"] for p in players) / total_players
    
    # Position balance
    position_count = {}
    position_strength = {}
    for p in players:
        pos = p["position"]
        position_count[pos] = position_count.get(pos, 0) + 1
        position_strength[pos] = position_strength.get(pos, [])
        position_strength[pos].append(p["dpr"])
    
    # Calculate average DPR by position
    position_avg = {pos: sum(dprs)/len(dprs) for pos, dprs in position_strength.items()}
    
    # Identify strongest and weakest areas
    strongest_position = max(position_avg.items(), key=lambda x: x[1])
    weakest_position = min(position_avg.items(), key=lambda x: x[1])
    
    # Form analysis
    improving_players = [p for p in players if p["dpr_change"] > 3]
    declining_players = [p for p in players if p["dpr_change"] < -5]
    
    return {
        "team_strength": strongest_position[0],
        "team_weakness": weakest_position[0],
        "avg_dpr_by_position": position_avg,
        "form_trending_up": len(improving_players),
        "form_trending_down": len(declining_players),
        "tactical_balance": "Balanced" if len(set(position_count.values())) <= 2 else "Unbalanced"
    }

team_analysis = analyze_team_balance()

# ---------- ENHANCED CATEGORIZATION ----------
def categorize_players():
    """Categorize players for different tactical insights"""
    
    # Performance tiers based on PREDICTED DPR (not current DPR)
    elite_players = [p for p in players if p["predicted_dpr"] >= 70]
    solid_players = [p for p in players if 50 <= p["predicted_dpr"] < 70]
    developing_players = [p for p in players if p["predicted_dpr"] < 50]
    
    # Trend analysis
    rising_stars = sorted([p for p in players if p["dpr_change"] > 5], 
                         key=lambda x: x["dpr_change"], reverse=True)
    declining_form = sorted([p for p in players if p["dpr_change"] < -5], 
                           key=lambda x: x["dpr_change"])
    
    # Role-based grouping
    attackers = [p for p in players if p["position"] in ["ST", "LW", "RW", "CAM"]]
    midfielders = [p for p in players if p["position"] in ["CDM"]]
    defenders = [p for p in players if p["position"] in ["CB", "LWB", "RWB"]]
    goalkeeper = [p for p in players if p["position"] == "GK"]
    
    return {
        "by_performance": {
            "elite": [{"name": p["name"], "predicted_dpr": p["predicted_dpr"]} for p in elite_players],
            "solid": [{"name": p["name"], "predicted_dpr": p["predicted_dpr"]} for p in solid_players],
            "developing": [{"name": p["name"], "predicted_dpr": p["predicted_dpr"]} for p in developing_players]
        },
        "by_trend": {
            "rising_stars": [{"name": p["name"], "change": p["dpr_change"]} for p in rising_stars],
            "declining_form": [{"name": p["name"], "change": p["dpr_change"]} for p in declining_form]
        },
        "by_position": {
            "attack": {"count": len(attackers), "avg_dpr": sum(p["dpr"] for p in attackers)/len(attackers) if attackers else 0},
            "midfield": {"count": len(midfielders), "avg_dpr": sum(p["dpr"] for p in midfielders)/len(midfielders) if midfielders else 0},
            "defense": {"count": len(defenders), "avg_dpr": sum(p["dpr"] for p in defenders)/len(defenders) if defenders else 0},
            "goalkeeper": {"count": len(goalkeeper), "avg_dpr": sum(p["dpr"] for p in goalkeeper)/len(goalkeeper) if goalkeeper else 0}
        }
    }

categorized_players = categorize_players()

# ---------- UI TABLE FORMAT ----------
def create_ui_table():
    """Create UI-ready table format for player statistics"""
    ui_table = []
    
    for p in players:
        stats = p.get("key_stats_p90", {})
        
        # Calculate passing accuracy
        successful_passes = stats.get("successful_passes_p90", 0)
        total_passes = stats.get("passes_p90", 1)  # Avoid division by zero
        passing_accuracy = round((successful_passes / total_passes) * 100, 1) if total_passes > 0 else 0.0
        
        # Calculate duels won percentage
        duels_won = stats.get("duels_won_p90", 0)
        total_duels = stats.get("duels_p90", 1)  # Avoid division by zero
        duels_won_pct = round((duels_won / total_duels) * 100, 1) if total_duels > 0 else 0.0
        
        # Generate contextual notes based on performance and role
        notes = generate_player_notes(p)
        
        # Convert per-90 stats to total stats based on minutes played
        minutes_played = p["minutes"]
        minutes_factor = minutes_played / 90.0
        
        ui_row = {
            "player_name": p["name"],
            "position": p["position"],
            "minutes_played": round(minutes_played, 0),
            "goals": round(stats.get("goals_p90", 0) * minutes_factor, 0),
            "assists": round(stats.get("assists_p90", 0) * minutes_factor, 0),
            "shots_on_target": round(stats.get("shots_on_target_p90", 0) * minutes_factor, 0),
            "key_passes": round(stats.get("key_passes_p90", 0) * minutes_factor, 0),
            "progressive_passes": round(stats.get("progressive_passes_p90", 0) * minutes_factor, 0),
            "passing_accuracy_pct": passing_accuracy,
            "duels_won_pct": duels_won_pct,
            "recoveries": round(stats.get("recoveries_p90", 0) * minutes_factor, 0),
            "dpr": p["dpr"],
            "notes": notes
        }
        ui_table.append(ui_row)
    
    # Sort by DPR descending for better presentation
    ui_table.sort(key=lambda x: x["dpr"], reverse=True)
    return ui_table

def generate_specialized_insights(category, players_list, stats_key=""):
    """Generate enhanced performance insights using position-specific breakdowns"""
    insights = []
    
    for i, player_data in enumerate(players_list[:3], 1):
        name = player_data["name"]
        
        # Find full player data
        full_player = next((p for p in players if p["name"] == name), None)
        if not full_player:
            continue
            
        stats = full_player.get("key_stats_p90", {})
        position = full_player.get("position", "")
        dpr = full_player.get("predicted_dpr", 0)
        breakdown = full_player.get("dpr_breakdown", {})
        
        if category == "attackers":
            # Use enhanced attacker breakdown for specific insights
            finishing = breakdown.get("finishing_quality", 0)
            chance_creation = breakdown.get("chance_creation", 0)
            movement = breakdown.get("movement_threat", 0)
            link_up = breakdown.get("link_up_play", 0)
            work_rate = breakdown.get("work_rate", 0)
            
            # Check if this is a goalless match for the team
            team_total_goals = sum([p.get("key_stats_p90", {}).get("goals_p90", 0) for p in players])
            is_goalless_match = team_total_goals == 0
            
            insight = f"**{name}** ({position}) - "
            
            # Specific coaching based on breakdown components
            if finishing > 60:
                insight += "Clinical finisher with excellent goal conversion. "
            elif finishing > 30:
                insight += "Good finishing ability showing consistency in front of goal. "
            elif finishing > 10:
                insight += "Developing finishing skills but needs more clinical edge. "
            else:
                if is_goalless_match:
                    insight += "Must focus on finishing quality (0.0/100) - needs extensive shooting practice. "
                else:
                    insight += "Finishing quality needs significant improvement. "
            
            if chance_creation > 50:
                insight += f"Excellent creative contribution ({chance_creation:.1f}/100). "
            elif chance_creation > 25:
                insight += f"Good chance creation abilities ({chance_creation:.1f}/100). "
            else:
                insight += f"Needs to improve chance creation ({chance_creation:.1f}/100). "
                
            if movement > 40:
                insight += "Shows good movement in dangerous areas. "
            elif movement < 25:
                insight += f"Positioning in final third needs work ({movement:.1f}/100). "
                
            if link_up > 70:
                insight += "Outstanding link-up play and hold-up abilities. "
            elif link_up > 40:
                insight += "Good team connection and build-up contribution. "
            else:
                insight += "Needs to improve link-up play with teammates. "
            
            insight += f" (Predicted DPR: {dpr:.1f})"
            
        elif category == "defenders":
            # Use enhanced defender breakdown for specific insights  
            defensive_impact = breakdown.get("defensive_impact", 0)
            build_up = breakdown.get("build_up_quality", 0)
            positioning = breakdown.get("positioning_score", 0)
            recovery = breakdown.get("recovery_efficiency", 0)
            aerial = breakdown.get("aerial_dominance", 0)
            
            insight = f"**{name}** ({position}) - "
            
            if defensive_impact > 50:
                insight += f"Strong defensive impact ({defensive_impact:.1f}/100) with excellent dueling. "
            elif defensive_impact > 30:
                insight += f"Solid defensive contributions ({defensive_impact:.1f}/100). "
            else:
                insight += f"Defensive impact needs improvement ({defensive_impact:.1f}/100) - focus on tackle timing and pressure. "
                
            if build_up > 80:
                insight += f"Excellent build-up play ({build_up:.1f}/100) - key in possession phases. "
            elif build_up > 60:
                insight += f"Good passing contribution ({build_up:.1f}/100). "
            else:
                insight += f"Needs work on build-up quality ({build_up:.1f}/100). "
                
            if positioning > 60:
                insight += f"Strong positional discipline ({positioning:.1f}/100). "
            elif positioning > 30:
                insight += f"Adequate positioning ({positioning:.1f}/100). "
            else:
                insight += f"Must improve positioning discipline ({positioning:.1f}/100) - stay in defensive zones. "
                
            if recovery > 50:
                insight += f"Efficient ball recovery ({recovery:.1f}/100). "
            else:
                insight += f"Recovery efficiency needs work ({recovery:.1f}/100). "
                
            insight += f" (Predicted DPR: {dpr:.1f})"
            
        elif category == "passers":
            # Use enhanced breakdown based on position
            if "game_control" in breakdown:  # Midfielder
                game_control = breakdown.get("game_control", 0)
                transition = breakdown.get("transition_play", 0)
                creativity = breakdown.get("creativity", 0)
                
                insight = f"**{name}** ({position}) - "
                
                if game_control > 80:
                    insight += f"Exceptional game control ({game_control:.1f}/100) - dictates tempo excellently. "
                elif game_control > 60:
                    insight += f"Strong possession control ({game_control:.1f}/100). "
                else:
                    insight += f"Game control needs improvement ({game_control:.1f}/100) - focus on pass accuracy under pressure. "
                    
                if transition > 70:
                    insight += f"Excellent transition play ({transition:.1f}/100). "
                elif transition > 40:
                    insight += f"Good progressive passing ({transition:.1f}/100). "
                else:
                    insight += f"Transition play needs work ({transition:.1f}/100). "
                    
                if creativity > 30:
                    insight += f"Shows creative passing ({creativity:.1f}/100). "
                else:
                    insight += f"Lacks creativity ({creativity:.1f}/100) - needs more key passes and assists. "
                    
            else:  # Defender/Attacker with passing focus
                build_up = breakdown.get("build_up_quality", breakdown.get("link_up_play", 0))
                insight = f"**{name}** ({position}) - "
                
                if build_up > 70:
                    insight += f"Outstanding passing contribution ({build_up:.1f}/100). "
                elif build_up > 50:
                    insight += f"Good distribution skills ({build_up:.1f}/100). "
                else:
                    insight += f"Passing accuracy needs improvement ({build_up:.1f}/100). "
            
            insight += f" (Predicted DPR: {dpr:.1f})"
        
        insights.append({
            "rank": i,
            "name": name,
            "position": position,
            "insight": insight,
            "predicted_dpr": dpr
        })
    
    return insights

def generate_player_notes(p):
    """Generate contextual notes for each player"""
    role = p.get("tactical_role", "")
    dpr = p["dpr"]
    change = p["dpr_change"]
    stats = p.get("key_stats_p90", {})
    
    notes_parts = []
    
    # Performance level context
    if dpr >= 70:
        notes_parts.append("Elite performer")
    elif dpr >= 50:
        notes_parts.append("Solid contributor")
    elif change > 10:
        notes_parts.append("Rising star potential")
    
    # Role-specific highlights
    if "goalkeeper" in role.lower():
        save_pct = stats.get("save_pct", 0)
        if save_pct > 0:
            notes_parts.append(f"{save_pct*100:.0f}% save rate")
    elif "playmaker" in role.lower():
        key_passes = stats.get("key_passes_p90", 0)
        if key_passes > 1:
            notes_parts.append("Creative threat")
        notes_parts.append("Distribution focus")
    elif "striker" in role.lower() or "target" in role.lower():
        goals = stats.get("goals_p90", 0)
        if goals > 0.5:
            notes_parts.append("Goal threat")
        else:
            notes_parts.append("Link-up play")
    elif "winger" in role.lower():
        goals = stats.get("goals_p90", 0)
        if goals > 0.5:
            notes_parts.append("Goal-scoring threat")
        notes_parts.append("Wing contribution")
    elif "defender" in role.lower() or p["position"] == "CB":
        clearances = stats.get("clearances_p90", 0)
        if clearances > 1:
            notes_parts.append("Defensive actions")
        if stats.get("successful_passes_p90", 0) / max(stats.get("passes_p90", 1), 1) > 0.9:
            notes_parts.append("Accurate passing")
    
    # Form trend
    if change > 5:
        notes_parts.append("Trending up")
    elif change < -5:
        notes_parts.append("Needs support")
    
    return "; ".join(notes_parts) + "." if notes_parts else "Developing player."

ui_table_data = create_ui_table()

# ---------- ENHANCED FINAL OUTPUT ----------
output = {
    "team": TEAM_NAME,
    "generated_at": datetime.now(timezone.utc).isoformat(),
    
    # Player-focused summary (team analysis separate)
    "player_summary": {
        "total_players": len(players),
        "players_improving": team_analysis["form_trending_up"],
        "players_declining": team_analysis["form_trending_down"],
        "key_performers_count": len([p for p in players if p["dpr"] >= 70])
    },
    
    # Detailed player insights
    "players": players,
    
    # Categorized analysis
    "performance_tiers": categorized_players["by_performance"],
    "form_analysis": categorized_players["by_trend"],
    "positional_strength": categorized_players["by_position"],
    
    # Quick reference lists
    "top_performers": [p["name"] for p in sorted(players, key=lambda x: x["dpr"], reverse=True)[:3]],
    "biggest_gainers": [{"name": p["name"], "change": p["dpr_change"]} for p in sorted(players, key=lambda x: x["dpr_change"], reverse=True)[:3]],
    "key_players": [p["name"] for p in players if p["dpr"] >= 70 or p["dpr_change"] > 10],
    
    # Specialized performance categories with insights
    "top_scorers": {
        "players": [{"name": p["name"], "goals_p90": p.get("key_stats_p90", {}).get("goals_p90", 0),
                     "shots_p90": p.get("key_stats_p90", {}).get("shots_p90", 0),
                     "shot_accuracy_pct": p.get("key_stats_p90", {}).get("shot_accuracy_pct_p90", 0)} 
                    for p in sorted(players, key=lambda x: x.get("key_stats_p90", {}).get("goals_p90", 0), reverse=True)[:3]
                    if p.get("key_stats_p90", {}).get("goals_p90", 0) > 0],
        "insights": generate_specialized_insights("attackers", 
                   [{"name": p["name"]} for p in sorted(players, key=lambda x: x.get("key_stats_p90", {}).get("goals_p90", 0) + x.get("key_stats_p90", {}).get("shots_p90", 0), reverse=True)[:3]
                    if (p.get("key_stats_p90", {}).get("goals_p90", 0) + p.get("key_stats_p90", {}).get("shots_p90", 0)) > 0])
    },
    
    "top_defenders": {
        "players": [{"name": p["name"], "tackles_p90": p.get("key_stats_p90", {}).get("tackles_p90", 0), 
                     "clearances_p90": p.get("key_stats_p90", {}).get("clearances_p90", 0),
                     "interceptions_p90": p.get("key_stats_p90", {}).get("interceptions_p90", 0),
                     "defensive_actions_p90": p.get("key_stats_p90", {}).get("tackles_p90", 0) + 
                                             p.get("key_stats_p90", {}).get("clearances_p90", 0) + 
                                             p.get("key_stats_p90", {}).get("interceptions_p90", 0)}
                    for p in sorted(players, key=lambda x: x.get("key_stats_p90", {}).get("tackles_p90", 0) + 
                                   x.get("key_stats_p90", {}).get("clearances_p90", 0) + 
                                   x.get("key_stats_p90", {}).get("interceptions_p90", 0), reverse=True)[:3]
                    if (p.get("key_stats_p90", {}).get("tackles_p90", 0) + 
                        p.get("key_stats_p90", {}).get("clearances_p90", 0) + 
                        p.get("key_stats_p90", {}).get("interceptions_p90", 0)) > 0],
        "insights": generate_specialized_insights("defenders",
                   [{"name": p["name"]} for p in sorted(players, key=lambda x: x.get("key_stats_p90", {}).get("tackles_p90", 0) + 
                                   x.get("key_stats_p90", {}).get("clearances_p90", 0) + 
                                   x.get("key_stats_p90", {}).get("interceptions_p90", 0), reverse=True)[:3]
                    if (p.get("key_stats_p90", {}).get("tackles_p90", 0) + 
                        p.get("key_stats_p90", {}).get("clearances_p90", 0) + 
                        p.get("key_stats_p90", {}).get("interceptions_p90", 0)) > 0])
    },
    
    "top_passers": {
        "players": [{"name": p["name"], "passes_p90": p.get("key_stats_p90", {}).get("passes_p90", 0),
                     "pass_accuracy": round(p.get("key_stats_p90", {}).get("successful_passes_p90", 0) / 
                                           max(p.get("key_stats_p90", {}).get("passes_p90", 1), 1) * 100, 1),
                     "key_passes_p90": p.get("key_stats_p90", {}).get("key_passes_p90", 0),
                     "progressive_passes_p90": p.get("key_stats_p90", {}).get("progressive_passes_p90", 0)}
                    for p in sorted(players, key=lambda x: x.get("key_stats_p90", {}).get("passes_p90", 0), reverse=True)[:3]
                    if p.get("key_stats_p90", {}).get("passes_p90", 0) > 0],
        "insights": generate_specialized_insights("passers",
                   [{"name": p["name"], "pass_accuracy": round(p.get("key_stats_p90", {}).get("successful_passes_p90", 0) / 
                                           max(p.get("key_stats_p90", {}).get("passes_p90", 1), 1) * 100, 1)} 
                    for p in sorted(players, key=lambda x: x.get("key_stats_p90", {}).get("passes_p90", 0), reverse=True)[:3]
                    if p.get("key_stats_p90", {}).get("passes_p90", 0) > 0])
    },
    
    # UI-ready table format for frontend consumption
    "ui_table": ui_table_data,
    
    # Match day recommendations
    "match_recommendations": {
        "must_start": [p["name"] for p in players if p["dpr"] >= 70],
        "impact_subs": [p["name"] for p in players if p["minutes"] < 60 and p["dpr_change"] > 5],
        "rotation_candidates": [p["name"] for p in players if p["dpr_change"] < -10],
        "development_opportunities": [p["name"] for p in players if p["dpr"] < 40 and p["dpr_change"] > 0]
    }
}

# Save insights to both match-specific and main locations
with open(OUTPUT_PATH, "w", encoding="utf-8") as f:
    json.dump(output, f, indent=4)

#with open(MAIN_OUTPUT_PATH, "w", encoding="utf-8") as f:
#    json.dump(output, f, indent=4)

print(f"\nEnhanced insights generated:")
print(f"  Match archive: {OUTPUT_PATH}")
#print(f"  Main directory: {MAIN_OUTPUT_PATH}")
print(f"Total players analyzed: {output['player_summary']['total_players']}")
print(f"Players improving: {output['player_summary']['players_improving']}")
print(f"Key performers: {', '.join(output['top_performers'])}")
if output['biggest_gainers']:
    top_gainer = output['biggest_gainers'][0]
    print(f"Biggest gainer: {top_gainer['name']} (+{top_gainer['change']:.1f})")

# Display sample enhanced insight
if players:
    sample_player = players[0]
    print(f"\nSample enhanced insight:")
    print(f"{sample_player['enhanced_insight']}")
    print(f"Match recommendation: {sample_player['match_recommendation']}")