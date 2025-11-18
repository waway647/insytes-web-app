# ============================================================
# File: python_scripts/derived_metrics_players.py
# Purpose: Enhanced DPR + per-90 + lineup + Event-based analysis
# Features: Position-specific ratings, heatmap analysis, advanced metrics
# Enhanced: Defender/Attacker/Midfielder/Goalkeeper specific calculations
# ============================================================

import pandas as pd
import json
import os
import sys
import numpy as np
from collections import defaultdict
from scipy import stats
import math

# Command line argument handling
if len(sys.argv) > 1:
    if sys.argv[1] == "--dataset" and len(sys.argv) > 2:
        MATCH_NAME = sys.argv[2]
    else:
        MATCH_NAME = sys.argv[1]
else:
    MATCH_NAME = "sbu_vs_2worlds"

# ========== ENHANCED RATING FUNCTIONS ==========

def calculate_heatmap_entropy(positions, pitch_length=105, pitch_width=68, bins=10):
    """Calculate positioning discipline from coordinate entropy"""
    if len(positions) < 2:
        return 0.0
    
    try:
        x_coords = [p[0] for p in positions if p[0] is not None]
        y_coords = [p[1] for p in positions if p[1] is not None]
        
        if len(x_coords) < 2:
            return 0.0
            
        # Create 2D histogram
        hist, _, _ = np.histogram2d(x_coords, y_coords, bins=bins, 
                                  range=[[0, pitch_length], [0, pitch_width]])
        
        # Normalize and calculate entropy
        hist = hist + 1e-10  # Avoid log(0)
        hist_norm = hist / hist.sum()
        entropy = -np.sum(hist_norm * np.log2(hist_norm))
        
        # Normalize entropy (max entropy for uniform distribution)
        max_entropy = math.log2(bins * bins)
        return entropy / max_entropy if max_entropy > 0 else 0.0
        
    except Exception as e:
        return 0.5  # Default moderate entropy

def calculate_zone_coverage(positions, zone_x_range, zone_y_range):
    """Calculate percentage of time in specific zone"""
    if not positions:
        return 0.0
    
    zone_count = 0
    total_count = len(positions)
    
    for x, y in positions:
        if (x is not None and y is not None and 
            zone_x_range[0] <= x <= zone_x_range[1] and 
            zone_y_range[0] <= y <= zone_y_range[1]):
            zone_count += 1
    
    return zone_count / total_count if total_count > 0 else 0.0

def clean_number(x):
    if isinstance(x, (int, float, np.integer, np.floating)):
        return round(float(x), 2)
    if isinstance(x, str):
        x = x.strip().replace("E", "e")
        if x.startswith('[') and x.endswith(']'): x = x[1:-1]
        try: return round(float(x), 2)
        except: return 0.0
    return 0.0

def clamp_pct(val, min_val=0, max_val=100):
    return max(min_val, min(max_val, val))

# ---------- CONFIG ----------
# MATCH_NAME is set by command line argument handling above
EVENTS_CSV = f"output_dataset/{MATCH_NAME}_events.csv"
TEAM_CONFIG_JSON = f"writable_data/configs/config_{MATCH_NAME}.json"

# Load team name from config file (use home team as featured team)
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

# Create match-specific output directory
MATCH_OUTPUT_DIR = f"output/matches/{MATCH_NAME}"
os.makedirs(MATCH_OUTPUT_DIR, exist_ok=True)

# Use dynamic team name for output files
team_name_safe = TEAM_NAME.lower().replace(" ", "_")
OUTPUT_JSON = f"{MATCH_OUTPUT_DIR}/{team_name_safe}_players_derived_metrics.json"
OUTPUT_CSV = f"{MATCH_OUTPUT_DIR}/{team_name_safe}_players_derived_metrics.csv"

# ---------- BENCHMARKS ----------
COLLEGE_BENCHMARKS = {
    "attacker":   {"goals":0.4, "assists":0.3, "key_passes":2.0, "prog":6.0},
    "midfielder": {"key_passes":2.5, "prog":7.0, "duels_won":2.0},
    "defender":   {"duels_won":2.5, "interceptions":1.5, "prog":5.0},
    "goalkeeper": {"saves":2.0, "pass_acc":0.70}
}

POSITION_ROLE = {
    "LW":"attacker","RW":"attacker","ST":"attacker",
    "CAM":"midfielder","CDM":"midfielder",
    "CB":"defender","LB":"defender","RB":"defender",
    "LWB":"defender","RWB":"defender","GK":"goalkeeper"
}

# ---------- LOAD ----------
if not os.path.exists(EVENTS_CSV):
    raise FileNotFoundError(f"No events file found at {EVENTS_CSV}")

print(f"Loading events from: {EVENTS_CSV}")
events = pd.read_csv(EVENTS_CSV)

required_cols = ["team", "player_name", "event", "duration", "in_possession", "outcome", "type",
                 "origin_x", "origin_y", "pass_end_x", "pass_end_y", "is_key_pass", "is_opponent_half",
                 "position", "blocker_name", "keeper_name", "match_time_minute", "half_period", "created_at", "id"]

for col in required_cols:
    if col not in events.columns:
        raise KeyError(f"Missing column: {col}")

# FIX: Strip whitespace
team_events = events[events["team"].str.lower() == TEAM_NAME.lower()].copy()
team_events["player_name"] = team_events["player_name"].str.strip()

opponent_events = events[events["team"].str.lower() != TEAM_NAME.lower()].copy()

# ---------- DERIVED METRICS ----------
player_metrics = {}
rows_for_csv = []

total_match_time = clean_number(team_events["duration"].fillna(0).sum())

for player, df in team_events.groupby("player_name"):
    if not player: continue

    minutes_played = clean_number(df["duration"].fillna(0).sum() / 60)
    player_metrics[player] = {}

    position = str(df["position"].iloc[0]).strip().upper() if "position" in df.columns else "DEFAULT"
    player_metrics[player]["position"] = position

    # --- POSSESSION ---
    possession_time = clean_number(df[df["in_possession"] == True]["duration"].fillna(0).sum())
    player_metrics[player]["possession"] = {
        "your_possession_time_seconds": possession_time,
        "possession_pct": clean_number(round((possession_time / total_match_time) * 100, 2) if total_match_time > 0 else 0)
    }

    # --- DISTRIBUTION ---
    passes = df[df["event"].str.lower() == "pass"]
    successful_passes = passes[passes["outcome"].str.lower() == "successful"]
    key_passes = passes[passes["is_key_pass"] == True]

    dist = {
        "passes": clean_number(len(passes)),
        "successful_passes": clean_number(len(successful_passes)),
        "unsuccessful_passes": clean_number(len(passes) - len(successful_passes)),
        "passing_accuracy_pct": clamp_pct(clean_number(round((len(successful_passes) / len(passes)) * 100, 2)) if len(passes) > 0 else 0),
        "key_passes": clean_number(len(key_passes)),
        "key_pass_rate_pct": clamp_pct(clean_number(round((len(key_passes) / len(passes)) * 100, 2)) if len(passes) > 0 else 0),
        "assists": 0
    }

    if not passes.empty:
        passes_copy = passes.copy()
        passes_copy["progress_distance"] = passes_copy["pass_end_x"].fillna(0) - passes_copy["origin_x"].fillna(0)
        dist["avg_pass_distance"] = clean_number(round(passes_copy["progress_distance"].mean(), 2))
        dist["progressive_passes"] = clean_number(len(passes_copy[passes_copy["progress_distance"] > 10]))
    else:
        dist["avg_pass_distance"] = 0.0
        dist["progressive_passes"] = 0.0

    player_metrics[player]["distribution"] = dist

    # --- ATTACK ---
    shots = df[df["event"].str.lower() == "shot"]
    on_target = shots[shots["outcome"].str.lower().isin(["on target", "goal"])]
    goals = shots[shots["outcome"].str.lower() == "goal"]

    player_metrics[player]["attack"] = {
        "shots": clean_number(len(shots)),
        "shots_on_target": clean_number(len(on_target)),
        "goals": clean_number(len(goals)),
        "shot_accuracy_pct": clamp_pct(clean_number(round((len(on_target) / len(shots)) * 100, 2)) if len(shots) > 0 else 0),
        "goal_conversion_pct": clamp_pct(clean_number(round((len(goals) / len(shots)) * 100, 2)) if len(shots) > 0 else 0)
    }

    # --- DRIBBLES ---
    dribbles = df[df["event"].str.lower() == "dribble"]
    successful_dribbles = dribbles[dribbles["outcome"].str.lower() == "successful"]

    player_metrics[player]["dribbles"] = {
        "dribbles": clean_number(len(dribbles)),
        "successful_dribbles": clean_number(len(successful_dribbles)),
        "dribble_success_rate_pct": clamp_pct(clean_number(round((len(successful_dribbles) / len(dribbles)) * 100, 2)) if len(dribbles) > 0 else 0)
    }

    # --- DEFENSE ---
    duels = df[df["event"].str.lower() == "duel"]
    tackles = df[df["event"].str.lower() == "tackle"]
    interceptions = df[df["event"].str.lower() == "interception"]
    clearances = df[df["event"].str.lower() == "clearance"]
    recoveries = df[df["event"].str.lower() == "recovery"]

    successful_duels = duels[duels["outcome"].str.lower() == "successful"].shape[0]
    successful_tackles = tackles[tackles["outcome"].str.lower() == "successful"].shape[0]
    blocked_shots = opponent_events[opponent_events["blocker_name"].fillna("").str.strip().str.lower() == player.lower()].shape[0]

    saves = 0
    goals_conceded = 0
    if position == "GK":
        saves = opponent_events[
            (opponent_events["outcome"].str.lower() == "on target") &
            (opponent_events["keeper_name"].fillna("").str.strip().str.lower() == player.lower())
        ].shape[0]
        goals_conceded = opponent_events[opponent_events["outcome"].str.lower() == "goal"].shape[0]

    defense = {
        "duels": clean_number(len(duels)),
        "duels_won": clean_number(successful_duels),
        "duel_success_rate_pct": clamp_pct(clean_number(round((successful_duels / len(duels)) * 100, 2)) if len(duels) > 0 else 0),
        "tackles": clean_number(len(tackles)),
        "successful_tackles": clean_number(successful_tackles),
        "tackle_success_rate_pct": clamp_pct(clean_number(round((successful_tackles / len(tackles)) * 100, 2)) if len(tackles) > 0 else 0),
        "interceptions": clean_number(len(interceptions)),
        "clearances": clean_number(len(clearances)),
        "recoveries": clean_number(len(recoveries)),
        "blocked_shots": clean_number(blocked_shots)
    }
    if position == "GK":
        defense["saves"] = clean_number(saves)
        defense["goals_conceded"] = clean_number(goals_conceded)

    player_metrics[player]["defense"] = defense

    # --- GOALKEEPER STATS ---
        # --- GOALKEEPER STATS (from OPPONENT shots where keeper_name == player) ---
    if position == "GK":
        # Filter opponent shots and penalties where this GK was named as keeper
        opp_shots = opponent_events[
            opponent_events["event"].str.lower().isin(["shot", "penalty"])
        ].copy()
        
        # Safely handle non-string keeper_name
        if "keeper_name" in opp_shots.columns:
            opp_shots["keeper_name"] = opp_shots["keeper_name"].astype(str).str.strip()
            mask_keeper = opp_shots["keeper_name"] == player
        else:
            mask_keeper = pd.Series([False] * len(opp_shots))

        # Count saves from keeper-attributed shots
        saves_from_named = len(opp_shots[
            mask_keeper & 
            opp_shots["outcome"].astype(str).str.lower().isin(["on target"])  # Only saves, not goals
        ])
        
        # Count ALL opponent goals and shots on target (since goal attribution is inconsistent)
        all_opp_shots_on_target = opp_shots[
            opp_shots["outcome"].astype(str).str.lower().isin(["on target", "goal"])
        ]
        all_opp_goals = opp_shots[
            opp_shots["outcome"].astype(str).str.lower() == "goal"
        ]
        
        # Use hybrid approach: saves from named data + all goals from opponent
        total_shots_faced = len(all_opp_shots_on_target)
        total_goals_conceded = len(all_opp_goals)
        total_saves = max(0, total_shots_faced - total_goals_conceded)
        save_pct = (total_saves / total_shots_faced * 100) if total_shots_faced > 0 else 0.0
        
        player_metrics[player]["goalkeeper"] = {
            "shots_faced": clean_number(total_shots_faced),
            "saves": clean_number(total_saves),
            "goals_conceded": clean_number(total_goals_conceded),
            "save_pct": clamp_pct(clean_number(round(save_pct, 2))),
            # Add Understat-compatible features
            "shots_on_target_against": clean_number(total_shots_faced),
            "goals_against": clean_number(total_goals_conceded)
        }

    # --- DISCIPLINE ---
    fouls = df[df["event"].str.lower() == "foul"]
    yellows = df[df["event"].str.lower() == "yellow card"]
    reds = df[df["event"].str.lower() == "red card"]
    player_metrics[player]["discipline"] = {
        "fouls_conceded": clean_number(len(fouls) + len(yellows) + len(reds)),
        "yellow_cards": clean_number(len(yellows)),
        "red_cards": clean_number(len(reds))
    }

# ---------- ASSISTS ----------
team_events_sorted = team_events.sort_values(by="created_at").reset_index(drop=True)
for idx, row in team_events_sorted.iterrows():
    if row["event"].lower() == "shot" and row["outcome"].lower() == "goal" and idx > 0:
        prev = team_events_sorted.iloc[idx - 1]
        if prev["event"].lower() == "pass":
            assister = prev["player_name"]
            if assister in player_metrics:
                player_metrics[assister]["distribution"]["assists"] += 1

# ---------- SUB TRACKING ----------
def abs_minute(row):
    return row["match_time_minute"] + (45 if row["half_period"] == 2 else 0)

team_events["abs_min"] = team_events.apply(abs_minute, axis=1)
team_events = team_events.sort_values(["abs_min", "id"])

timeline = defaultdict(list)
active = {}
last_min = team_events["abs_min"].max()

for _, row in team_events.iterrows():
    p, pos, t = row["player_name"], row["position"], row["abs_min"]
    if p not in active:
        active[p] = (t, pos)
    if active[p][1] != pos:
        enter, old_pos = active[p]
        timeline[p].append((enter, t, old_pos))
        active[p] = (t, pos)
for p, (enter, pos) in active.items():
    timeline[p].append((enter, last_min + 1, pos))

for player, periods in timeline.items():
    if player not in player_metrics: continue
    mins = sum(exit - enter for enter, exit, _ in periods) / 60.0
    player_metrics[player]["minutes_played_exact"] = round(mins, 2)
    player_metrics[player]["minutes_played"] = round(mins, 2)

    # --- PER-90 STATS ---
    # Only calculate per-90 stats for players with meaningful playing time (≥20 minutes)
    # This prevents misleading projections for substitute players with limited minutes
    MINIMUM_MINUTES_FOR_PER90 = 20
    
    if mins >= MINIMUM_MINUTES_FOR_PER90:
        per90 = {}
        for cat in ["attack", "distribution", "defense", "goalkeeper"]:
            if cat in player_metrics[player]:
                for k, v in player_metrics[player][cat].items():
                    # Exclude ALL percentage fields from per-90 scaling (percentages don't scale with time)
                    percentage_fields = ["passing_accuracy_pct", "save_pct", "shot_accuracy_pct", 
                                       "goal_conversion_pct", "key_pass_rate_pct", "duel_success_rate_pct"]
                    if isinstance(v, (int, float)) and k not in percentage_fields:
                        per90[f"{k}_p90"] = clean_number(round(v / (mins / 90.0), 2))
        
        # Add dribble stats to per-90 calculations
        if "dribbles" in player_metrics[player]:
            dribble_stats = player_metrics[player]["dribbles"]
            for k, v in dribble_stats.items():
                if k != "dribble_success_rate_pct" and isinstance(v, (int, float)):
                    per90[f"{k}_p90"] = clean_number(round(v / (mins / 90.0), 2))
            # Keep percentage as original value
            per90["dribble_success_rate_pct_p90"] = clean_number(dribble_stats.get("dribble_success_rate_pct", 0))

        # Add percentage fields back as their original values (percentages don't need per-90 scaling)
        for cat in ["attack", "distribution", "defense", "goalkeeper"]:
            if cat in player_metrics[player]:
                for k, v in player_metrics[player][cat].items():
                    percentage_fields = ["passing_accuracy_pct", "save_pct", "shot_accuracy_pct", 
                                       "goal_conversion_pct", "key_pass_rate_pct", "duel_success_rate_pct"]
                    if k in percentage_fields and isinstance(v, (int, float)):
                        per90[f"{k}_p90"] = clean_number(v)  # Keep original percentage value
        
        # Add Understat-compatible GK per-90 features
        if player_metrics[player].get("position") == "GK" and "goalkeeper" in player_metrics[player]:
            gk_stats = player_metrics[player]["goalkeeper"]
            per90["saves_per90"] = clean_number(round(gk_stats["saves"] / (mins / 90.0), 2))
            per90["goals_against_per90"] = clean_number(round(gk_stats["goals_against"] / (mins / 90.0), 2))
            per90["shots_on_target_against_per90"] = clean_number(round(gk_stats["shots_on_target_against"] / (mins / 90.0), 2))
            # Keep the percentage as is
            per90["save_pct"] = clean_number(gk_stats["save_pct"] / 100.0)  # Convert to 0-1 scale like Understat
        
        player_metrics[player]["key_stats_p90"] = per90
    else:
        # For players with <20 minutes: provide raw stats instead of misleading per-90 projections
        raw_stats = {}
        for cat in ["attack", "distribution", "defense", "goalkeeper", "dribbles"]:
            if cat in player_metrics[player]:
                for k, v in player_metrics[player][cat].items():
                    if isinstance(v, (int, float)):
                        raw_stats[f"{k}_raw"] = clean_number(v)
        
        # Add note about limited playing time
        raw_stats["note"] = f"Raw stats shown - only {mins} minutes played (minimum 20 minutes required for per-90 calculations)"
        player_metrics[player]["key_stats_p90"] = raw_stats

# ========== ENHANCED EVENT-BASED METRICS ==========

def calculate_enhanced_defender_rating(player_stats, player_events, position):
    """Enhanced Defender Performance Rating (EDPR) - optimized for available data"""
    ks = player_stats.get("key_stats_p90", {})
    
    # Extract positions from events for heatmap analysis
    positions = []
    pressure_events = []
    
    for event in player_events:
        if event.get("origin_x") and event.get("origin_y"):
            positions.append((float(event["origin_x"]), float(event["origin_y"])))
        if event.get("event") in ["pressure", "duel"] and event.get("outcome") == "successful":
            pressure_events.append(event)

    # 1. Defensive Actions (35%) - Core defending
    tackles = max(0, ks.get("tackles_p90", 0))
    interceptions = max(0, ks.get("interceptions_p90", 0))
    recoveries = max(0, ks.get("recoveries_p90", 0))
    
    # Weight based on actions that actually occurred
    defensive_actions = tackles + interceptions + recoveries
    if defensive_actions > 0:
        tackle_contribution = (tackles / defensive_actions) * min(100, tackles * 15)
        interception_contribution = (interceptions / defensive_actions) * min(100, interceptions * 20)
        recovery_contribution = (recoveries / defensive_actions) * min(100, recoveries * 10)
        defensive_impact = tackle_contribution + interception_contribution + recovery_contribution
    else:
        # Base score for defenders with low action counts (possibly good positioning)
        defensive_impact = 30.0

    # 2. Build-Up Quality (30%) - Progressive passing
    passes = max(1, ks.get("passes_p90", 1))
    successful_passes = max(0, ks.get("successful_passes_p90", 0))
    pass_acc = successful_passes / passes
    
    prog_passes = max(0, ks.get("progressive_passes_p90", 0))
    prog_contribution = min(1.0, prog_passes / 8.0)  # Scale to 0-1, peak at 8
    
    build_up_quality = (0.60 * pass_acc + 0.40 * prog_contribution) * 100

    # 3. Positioning & Discipline (20%) - Heatmap discipline 
    if positions:
        defensive_zone_time = calculate_zone_coverage(positions, [0, 35], [0, 68])  # Defensive third
        positioning_entropy = calculate_heatmap_entropy(positions)
        positioning_discipline = max(0, 1 - positioning_entropy)
        positioning_score = (0.70 * defensive_zone_time + 0.30 * positioning_discipline) * 100
    else:
        positioning_score = 50.0  # Default for missing position data

    # 4. Duels & Physical Play (15%) - Available duel data
    duels_won = max(0, ks.get("duels_won_p90", 0))
    duels_total = max(duels_won, ks.get("duels_p90", duels_won))  # Ensure total >= won
    
    if duels_total > 0:
        duel_success_rate = duels_won / duels_total
        duel_volume = min(1.0, duels_total / 8.0)  # Scale volume component
        duel_performance = (0.70 * duel_success_rate + 0.30 * duel_volume) * 100
    else:
        duel_performance = 40.0  # Base score for defenders not involved in duels
    
    # Aerial duels - use if available, otherwise skip
    aerial_duels_won = ks.get("aerial_duels_won_p90", 0)
    aerial_duels_total = ks.get("aerial_duels_p90", 0)
    
    if aerial_duels_total > 0:
        aerial_success = (aerial_duels_won / aerial_duels_total) * 100
        physical_play = 0.70 * duel_performance + 0.30 * aerial_success
    else:
        physical_play = duel_performance  # Only ground duels available
    
    # Final EDPR calculation
    edpr = (
        0.35 * defensive_impact +
        0.30 * build_up_quality + 
        0.20 * positioning_score +
        0.15 * physical_play
    )
    
    return {
        "edpr": round(min(100, max(20, edpr)), 1),  # Floor of 20 for defenders
        "breakdown": {
            "defensive_impact": round(defensive_impact, 1),
            "build_up_quality": round(build_up_quality, 1), 
            "positioning_score": round(positioning_score, 1),
            "physical_play": round(physical_play, 1)
        }
    }

def calculate_enhanced_attacker_rating(player_stats, player_events, position):
    """Enhanced Attacker Performance Rating (EAPR) - optimized for available data"""
    ks = player_stats.get("key_stats_p90", {})
    
    # Extract positions for movement analysis
    positions = []
    final_third_actions = 0
    total_actions = len(player_events)
    
    for event in player_events:
        if event.get("origin_x") and event.get("origin_y"):
            x = float(event["origin_x"])
            positions.append((x, float(event["origin_y"])))
            if x >= 70:  # Final third
                final_third_actions += 1

    # 1. Finishing Quality (40%) - Primary metric for attackers
    shots = max(0, ks.get("shots_p90", 0))
    shots_on_target = max(0, ks.get("shots_on_target_p90", 0))
    goals = max(0, ks.get("goals_p90", 0))
    
    # More generous base scoring to avoid 0s
    if shots > 0:
        shot_accuracy = shots_on_target / shots
        conversion_rate = goals / shots
        shot_volume = min(1.0, shots / 3.0)  # Reduced peak from 5 to 3
        finishing_quality = (0.35 * shot_volume + 0.35 * shot_accuracy + 0.30 * conversion_rate) * 100
    else:
        # Base score for attackers with no shots (maybe played few minutes)
        finishing_quality = 25.0

    # 2. Offensive Contribution (35%) - Goals + involvement
    goal_contribution = min(100, goals * 30)  # Direct goal scoring
    
    # Progressive actions (passing forward, creating)
    key_passes = max(0, ks.get("key_passes_p90", 0))
    assists = max(0, ks.get("assists_p90", 0))
    prog_passes = max(0, ks.get("progressive_passes_p90", 0))
    
    creative_contribution = (assists * 25) + (key_passes * 8) + (prog_passes * 2)
    creative_contribution = min(100, creative_contribution)
    
    offensive_contribution = 0.6 * goal_contribution + 0.4 * creative_contribution

    # 3. Link-up Play (15%) - Ball handling and retention
    passes = max(1, ks.get("passes_p90", 1))
    successful_passes = max(0, ks.get("successful_passes_p90", 0))
    pass_success = successful_passes / passes
    
    duels_won = max(0, ks.get("duels_won_p90", 0))
    hold_up_play = min(1.0, duels_won / 3.0)  # Scale to 0-1
    
    link_up_play = (0.70 * pass_success + 0.30 * hold_up_play) * 100

    # 4. Positioning (10%) - Movement and presence
    if positions and total_actions > 0:
        final_third_presence = final_third_actions / total_actions
        positioning_quality = calculate_zone_coverage(positions, [70, 105], [0, 68])
        movement_threat = (0.70 * final_third_presence + 0.30 * positioning_quality) * 100
    else:
        movement_threat = 40.0  # Default for missing data

    # Final EAPR calculation
    eapr = (
        0.40 * finishing_quality +
        0.35 * offensive_contribution +
        0.15 * link_up_play +
        0.10 * movement_threat
    )
    
    return {
        "eapr": round(min(100, max(15, eapr)), 1),  # Floor of 15 to prevent extremely low scores
        "breakdown": {
            "finishing_quality": round(finishing_quality, 1),
            "offensive_contribution": round(offensive_contribution, 1),
            "link_up_play": round(link_up_play, 1),
            "movement_threat": round(movement_threat, 1)
        }
    }

def calculate_enhanced_midfielder_rating(player_stats, player_events, position):
    """Enhanced Midfielder Performance Rating (EMPR)"""
    ks = player_stats.get("key_stats_p90", {})
    
    # 1. Game Control (30%)
    pass_success = ks.get("successful_passes_p90", 0) / max(1, ks.get("passes_p90", 1))
    possession_retention = min(1.0, pass_success + 0.1)  # Bonus for high accuracy
    
    game_control = (0.50 * pass_success + 0.50 * possession_retention) * 100
    
    # 2. Transition Play (25%) - Progressive passing, duels, and dribbling
    prog_passes = min(1.0, ks.get("progressive_passes_p90", 0) / 5.0)  # Scale to 0-1
    duels_won = min(1.0, ks.get("duels_won_p90", 0) / 6.0)  # Scale to 0-1
    
    # Add dribbling for transition play (especially for CAMs)
    dribbles_p90 = max(0, ks.get("successful_dribbles_p90", 0))
    dribble_transition = min(1.0, dribbles_p90 / 3.0)  # Scale to 0-1 (3 successful dribbles/90 = good for midfielders)
    
    transition_play = (0.50 * prog_passes + 0.30 * duels_won + 0.20 * dribble_transition) * 100
    
    # 3. Defensive Contribution (20%)
    tackles = min(1.0, ks.get("tackles_p90", 0) / 2.0)  # Scale to 0-1
    interceptions = min(1.0, ks.get("interceptions_p90", 0) / 4.0)  # Scale to 0-1
    recoveries = min(1.0, ks.get("recoveries_p90", 0) / 5.0)  # Scale to 0-1
    
    defensive_contribution = (0.40 * tackles + 0.30 * interceptions + 0.30 * recoveries) * 100
    
    # 4. Creativity (15%)
    key_passes = min(1.0, ks.get("key_passes_p90", 0) / 3.0)  # Scale to 0-1
    assists = min(1.0, ks.get("assists_p90", 0) / 2.0)  # Scale to 0-1
    
    creativity = (0.70 * key_passes + 0.30 * assists) * 100
    
    # 5. Work Rate (10%) - Box-to-box coverage
    total_actions = ks.get("passes_p90", 0) + ks.get("duels_p90", 0) + ks.get("shots_p90", 0)
    work_rate = min(100, total_actions / 50.0 * 100)  # Scale based on typical action count
    
    # Final EMPR calculation  
    empr = (
        0.30 * game_control +
        0.25 * transition_play +
        0.20 * defensive_contribution +
        0.15 * creativity +
        0.10 * work_rate
    )
    
    return {
        "empr": round(min(100, max(0, empr)), 1),
        "breakdown": {
            "game_control": round(game_control, 1),
            "transition_play": round(transition_play, 1),
            "defensive_contribution": round(defensive_contribution, 1),
            "creativity": round(creativity, 1), 
            "work_rate": round(work_rate, 1)
        }
    }

def calculate_enhanced_goalkeeper_rating(player_stats, player_events, position):
    """Enhanced Goalkeeper Performance Rating (EGPR)"""
    ks = player_stats.get("key_stats_p90", {})
    
    # 1. Shot Stopping (40%)
    saves = ks.get("saves_p90", 0)
    shots_faced = ks.get("shots_faced_p90", saves + 1) 
    save_rate = saves / max(1, shots_faced)
    
    # Use save percentage if available
    save_pct = ks.get("save_pct", save_rate * 100) / 100.0
    
    shot_stopping = save_pct * 100
    
    # 2. Distribution Quality (25%)
    pass_accuracy = ks.get("successful_passes_p90", 0) / max(1, ks.get("passes_p90", 1))
    long_balls = min(1.0, ks.get("progressive_passes_p90", 0) / 10.0)  # Scale to 0-1
    
    distribution_quality = (0.60 * pass_accuracy + 0.40 * long_balls) * 100
    
    # 3. Command of Area (20%) - Proxy metrics
    command_area = min(100, saves / 10.0 * 100)  # Scale based on save activity
    
    # 4. Ball Playing (10%)
    ball_playing = min(100, ks.get("passes_p90", 0) / 20.0 * 100)  # Scale based on GK passing
    
    # 5. Consistency (5%)
    consistency = max(0, 100 - (ks.get("errors_p90", 0) * 50))  # Penalty for errors
    
    # Final EGPR calculation
    egpr = (
        0.40 * shot_stopping +
        0.25 * distribution_quality +
        0.20 * command_area +
        0.10 * ball_playing +
        0.05 * consistency
    )
    
    return {
        "egpr": round(min(100, max(0, egpr)), 1), 
        "breakdown": {
            "shot_stopping": round(shot_stopping, 1),
            "distribution_quality": round(distribution_quality, 1),
            "command_area": round(command_area, 1),
            "ball_playing": round(ball_playing, 1),
            "consistency": round(consistency, 1)
        }
    }

# ---------- DPR + ENHANCED RATINGS ----------
def dpr_component(val, bench, cap=100):
    return min(cap, val / bench * 100) if bench > 0 else 0

# Load event data for enhanced calculations
print(f"Loading event data for enhanced ratings: {EVENTS_CSV}")
try:
    events_df = pd.read_csv(EVENTS_CSV)
    events_available = True
    print(f"Loaded {len(events_df)} events for enhanced analysis")
except Exception as e:
    print(f"Could not load events: {e}, falling back to basic DPR")
    events_available = False
    events_df = pd.DataFrame()

for player, s in player_metrics.items():
    role = POSITION_ROLE.get(s.get("position", ""), "midfielder")
    bench = COLLEGE_BENCHMARKS.get(role, {})

    # Get player events for enhanced calculations
    player_events = []
    if events_available and player:
        player_events = events_df[events_df['player_name'] == player].to_dict('records')
    
    # Calculate enhanced ratings if events are available
    if events_available and len(player_events) > 0:
        print(f"Calculating enhanced rating for {player} ({role}) with {len(player_events)} events")
        
        if role == "defender":
            enhanced_rating = calculate_enhanced_defender_rating(s, player_events, s.get("position"))
            s["edpr"] = enhanced_rating["edpr"]
            s["edpr_breakdown"] = enhanced_rating["breakdown"]
            # Use EDPR as primary DPR for defenders
            s["dpr"] = enhanced_rating["edpr"]
            s["dpr_breakdown"] = enhanced_rating["breakdown"]
            
        elif role == "attacker":
            enhanced_rating = calculate_enhanced_attacker_rating(s, player_events, s.get("position"))
            s["eapr"] = enhanced_rating["eapr"]
            s["eapr_breakdown"] = enhanced_rating["breakdown"]
            # Use EAPR as primary DPR for attackers
            s["dpr"] = enhanced_rating["eapr"]
            s["dpr_breakdown"] = enhanced_rating["breakdown"]
            
        elif role == "midfielder":
            enhanced_rating = calculate_enhanced_midfielder_rating(s, player_events, s.get("position"))
            s["empr"] = enhanced_rating["empr"]
            s["empr_breakdown"] = enhanced_rating["breakdown"]
            # Use EMPR as primary DPR for midfielders
            s["dpr"] = enhanced_rating["empr"]
            s["dpr_breakdown"] = enhanced_rating["breakdown"]
            
        elif role == "goalkeeper":
            enhanced_rating = calculate_enhanced_goalkeeper_rating(s, player_events, s.get("position"))
            s["egpr"] = enhanced_rating["egpr"]
            s["egpr_breakdown"] = enhanced_rating["breakdown"]
            # Use EGPR as primary DPR for goalkeepers
            s["dpr"] = enhanced_rating["egpr"]
            s["dpr_breakdown"] = enhanced_rating["breakdown"]
    
    # Fallback to original DPR calculation if no events or enhanced calculation fails
    elif "dpr" not in s or s.get("dpr", 0) == 0:
        att = defn = dist = disc = 0

        # per-90 values are stored under key_stats_p90; use safe lookups to avoid KeyError
        ks = s.get("key_stats_p90", {})
        dist_stats = s.get("distribution", {})
        def_stats = s.get("defense", {})
        disc_stats = s.get("discipline", {})

        def kp(name):
            try:
                return float(ks.get(name, 0.0))
            except:
                return 0.0

        if role == "attacker":
            att = (dpr_component(kp("goals_p90"), bench.get("goals", 0.0), 40) * 0.35 +
                   dpr_component(kp("shots_on_target_p90"), 2.5, 30) * 0.25 +
                   dpr_component(kp("key_passes_p90"), bench.get("key_passes", 0.0), 30) * 0.25 +
                   dpr_component(kp("successful_dribbles_p90"), 2.0, 40) * 0.15)  # Add dribbles for attackers
        elif role == "midfielder":
            att = (dpr_component(kp("key_passes_p90"), bench.get("key_passes", 0.0), 40) * 0.45 +
                   dpr_component(kp("goals_p90"), 0.2, 30) * 0.25 +
                   dpr_component(kp("assists_p90"), 0.2, 30) * 0.20 +
                   dpr_component(kp("successful_dribbles_p90"), 1.5, 30) * 0.10)  # Add dribbles for creative midfielders
        else:
            att = dpr_component(kp("goals_p90"), 0.15, 20) * 0.5

        if role == "defender":
            defn = (dpr_component(kp("duels_won_p90"), bench.get("duels_won", 0.0), 40) * 0.4 +
                    dpr_component(kp("interceptions_p90"), bench.get("interceptions", 0.0), 30) * 0.3 +
                    dpr_component(kp("recoveries_p90"), 4.0, 30) * 0.3)
        elif role == "midfielder":
            defn = dpr_component(kp("duels_won_p90"), bench.get("duels_won", 0.0), 60) * 0.6
        elif role == "goalkeeper":
            defn = (dpr_component(kp("saves_p90"), bench.get("saves", 0.0), 60) * 0.6 +
                    (dist_stats.get("passing_accuracy_pct", 0) / 100.0) * 40)

        dist = (dist_stats.get("passing_accuracy_pct", 0) / 100.0) * 50 + \
               dpr_component(kp("progressive_passes_p90"), bench.get("prog", 5.0), 50)

        disc = max(0, 100 - (disc_stats.get("fouls_conceded", 0) * 8 + disc_stats.get("yellow_cards", 0) * 25 + disc_stats.get("red_cards", 0) * 60))

        weights = {"attacker":(0.40,0.30,0.20,0.10), "midfielder":(0.30,0.30,0.30,0.10),
                   "defender":(0.15,0.45,0.30,0.10), "goalkeeper":(0.05,0.55,0.30,0.10)}.get(role, (0.3,0.3,0.3,0.1))

        dpr = weights[0]*att + weights[1]*defn + weights[2]*dist + weights[3]*disc
        s["dpr"] = round(dpr, 1)
        s["dpr_breakdown"] = {"attack":round(att,1), "defense":round(defn,1),
                              "distribution":round(dist,1), "discipline":round(disc,1)}

# ---------- LINEUP + DNP ----------
if os.path.exists(TEAM_CONFIG_JSON):
    print(f"Loading line-up from {TEAM_CONFIG_JSON}")
    with open(TEAM_CONFIG_JSON, "r", encoding="utf-8") as f:
        lineup_data = json.load(f)

    match_id_cfg = lineup_data["match"]["id"]
    home_team = lineup_data["home"]["name"]
    away_team = lineup_data["away"]["name"]

    roster = {}
    for prefix, team_name in [("h", home_team), ("a", away_team)]:
        section = "home" if prefix == "h" else "away"
        for p in lineup_data[section]["starting11"]:
            name = p["name"].strip()
            if name:
                roster[name] = {**p, "team": team_name, "status": "starter", "minutes": 0.0, "dpr": 0.0}
        for p in lineup_data[section].get("bench", []):
            name = p["name"].strip()
            if name:
                roster[name] = {**p, "team": team_name, "status": "bench", "minutes": 0.0, "dpr": 0.0}

    for name, info in roster.items():
        if name in player_metrics:
            s = player_metrics[name]
            info["minutes"] = s.get("minutes_played_exact", 0.0)
            info["dpr"] = s.get("dpr", 0.0)
            if info["status"] == "bench" and info["minutes"] > 0:
                info["status"] = "substitute"
        else:
            info["status"] = "DNP"
            info["minutes"] = 0.0
            info["dpr"] = 0.0
            player_metrics[name] = {
                "position": info["position"],
                "minutes_played": 0.0,
                "minutes_played_exact": 0.0,
                "dpr": 0.0,
                "dpr_breakdown": {"attack":0,"defense":0,"distribution":0,"discipline":0},
                "status": "DNP",
                "team": info["team"],
                "number": info.get("number", "")
            }

    player_metrics["_lineup"] = {
        "match_id": match_id_cfg,
        "home_team": home_team,
        "away_team": away_team,
        "players": [v for v in roster.values()]
    }
    print(f"Line-up merged: {len(roster)} players")
else:
    player_metrics["_lineup"] = None

# ---------- FINAL CLEAN OUTPUT + STATUS FIX ----------
final_players = {}
lineup_data = player_metrics.get("_lineup", {})
lineup_players = {p["name"]: p for p in lineup_data.get("players", [])} if lineup_data else {}

# Extract match_id from lineup data if available
lineup_match_id = lineup_data.get("match_id") if lineup_data else None
if lineup_match_id and not MATCH_ID:
    MATCH_ID = lineup_match_id

for player, s in player_metrics.items():
    if player.startswith("_"): 
        # Skip lineup and other metadata - don't include in final output
        continue

    key_p90 = {}
    # Get per-90 stats from the correct location
    if "key_stats_p90" in s:
        key_p90.update(s["key_stats_p90"])
    
    # Also check for any _p90 stats in categories (legacy compatibility)
    for cat in ["attack", "distribution", "defense", "goalkeeper"]:
        if cat in s:
            key_p90.update({k: v for k, v in s[cat].items() if k.endswith("_p90")})

    # FIX: Use lineup status & number
    lineup_info = lineup_players.get(player, {})
    status = lineup_info.get("status", "DNP")
    number = lineup_info.get("number", "")

    final_players[player] = {
        "position": s["position"],
        "minutes_played": s.get("minutes_played_exact", 0.0),
        "dpr": s.get("dpr", 0.0),
        "dpr_breakdown": s.get("dpr_breakdown", {}),
        "status": status,
        "team": s.get("team", TEAM_NAME),
        "number": number,
        "key_stats_p90": key_p90
    }

    flat = {"player_name": player, "minutes_played": final_players[player]["minutes_played"]}
    flat.update(final_players[player])
    flat.update(key_p90)
    rows_for_csv.append(flat)

# ---------- SAVE ----------
os.makedirs(os.path.dirname(OUTPUT_JSON), exist_ok=True)

with open(OUTPUT_JSON, "w", encoding="utf-8") as f:
    json.dump({
        TEAM_NAME: {
            "match_id": MATCH_ID,
            "match_name": MATCH_NAME,
            **final_players
        }
    }, f, indent=4)

df_metrics = pd.DataFrame(rows_for_csv)

# Add match_id to all rows in CSV
if not df_metrics.empty:
    df_metrics['match_id'] = MATCH_ID
    df_metrics['match_name'] = MATCH_NAME

# Apply clean_number only to numeric columns, preserve string columns
numeric_columns = df_metrics.select_dtypes(include=[np.number]).columns
string_columns = ['player_name', 'position', 'status', 'team', 'match_id', 'match_name']

for col in df_metrics.columns:
    if col in string_columns:
        # Keep string columns as-is
        continue
    else:
        # Clean numeric columns
        df_metrics[col] = df_metrics[col].apply(clean_number)

# Save to both match-specific and main locations
df_metrics.to_csv(OUTPUT_CSV, index=False)
# df_metrics.to_csv(MAIN_OUTPUT_CSV, index=False)

print(f"DPR-focused metrics saved to:")
print(f"  Match archive: {OUTPUT_JSON}")
# print(f"  Main directory: {MAIN_OUTPUT_JSON}")