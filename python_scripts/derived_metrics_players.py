# ============================================================
# File: python_scripts/derive_metrics_players.py
# Purpose: Compute REALISTIC, DETERMINISTIC RATINGS
# FINAL: 100% PERFORMANCE-BASED — NO RANDOMNESS
# Position-aware overall ratings included
# Professional-grade defender adjustments added
# Professional forward/ST/LW/RW attack scoring added
# Includes ASSISTS under distribution
# ============================================================

import pandas as pd
import json
import os
import numpy as np

def clean_number(x):
    if isinstance(x, (int, float, np.integer, np.floating)):
        return round(float(x), 2)
    if isinstance(x, str):
        x = x.strip().replace("E", "e")
        if x.startswith('[') and x.endswith(']'): x = x[1:-1]
        try: return round(float(x), 2)
        except: return 0.0
    return 0.0

# ---------- CONFIG ----------
#events, events_vs_siniloan, sanbeda_vs_up
EVENTS_PATH = "data/events.csv"
OUTPUT_JSON = "python_scripts/sanbeda_players_derived_metrics.json"
OUTPUT_CSV = "python_scripts/sanbeda_players_derived_metrics.csv"
TEAM_NAME = "San Beda"

# ---------- POSITION WEIGHTS ----------
POSITION_WEIGHTS = {
    "GK": {"attack": 0.05, "defense": 0.55, "distribution": 0.25, "discipline": 0.15},
    "CB": {"attack": 0.10, "defense": 0.45, "distribution": 0.25, "discipline": 0.20},
    "LWB": {"attack": 0.15, "defense": 0.40, "distribution": 0.25, "discipline": 0.20},
    "RWB": {"attack": 0.15, "defense": 0.40, "distribution": 0.25, "discipline": 0.20},
    "CDM": {"attack": 0.20, "defense": 0.35, "distribution": 0.30, "discipline": 0.15},
    "CAM": {"attack": 0.35, "defense": 0.15, "distribution": 0.30, "discipline": 0.20},
    "LW": {"attack": 0.45, "defense": 0.10, "distribution": 0.25, "discipline": 0.20},
    "RW": {"attack": 0.45, "defense": 0.10, "distribution": 0.25, "discipline": 0.20},
    "ST": {"attack": 0.50, "defense": 0.05, "distribution": 0.25, "discipline": 0.20},
    "DEFAULT": {"attack": 0.35, "defense": 0.25, "distribution": 0.25, "discipline": 0.15}
}

# ---------- LOAD ----------
if not os.path.exists(EVENTS_PATH):
    raise FileNotFoundError(f"No events file found at {EVENTS_PATH}")

print(f"📂 Loading events from: {EVENTS_PATH}")
events = pd.read_csv(EVENTS_PATH)

required_cols = ["team", "player_name", "event", "duration", "in_possession", "outcome", "type",
                 "origin_x", "origin_y", "pass_end_x", "pass_end_y", "is_key_pass", "is_opponent_half",
                 "position", "blocker_name", "keeper_name"]

for col in required_cols:
    if col not in events.columns:
        raise KeyError(f"Missing column: {col}")

team_events = events[events["team"].str.lower() == TEAM_NAME.lower()].copy()
opponent_events = events[events["team"].str.lower() != TEAM_NAME.lower()].copy()

# ---------- DERIVED METRICS ----------
player_metrics = {}
rows_for_csv = []

total_match_time = clean_number(team_events["duration"].fillna(0).sum())

for player, df in team_events.groupby("player_name"):
    if not isinstance(player, str) or player.strip() == "": continue

    minutes_played = clean_number(df["duration"].fillna(0).sum() / 60)
    player_metrics[player] = {}

    # --- POSITION ---
    position = str(df["position"].iloc[0]).strip().upper() if "position" in df.columns else "DEFAULT"
    pos_weights = POSITION_WEIGHTS.get(position, POSITION_WEIGHTS["DEFAULT"])
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

    player_metrics[player]["distribution"] = {
        "passes": clean_number(len(passes)),
        "successful_passes": clean_number(len(successful_passes)),
        "unsuccessful_passes": clean_number(len(passes) - len(successful_passes)),
        "passing_accuracy_pct": clean_number(round((len(successful_passes) / len(passes)) * 100, 2) if len(passes) > 0 else 0),
        "key_passes": clean_number(len(key_passes)),
        "key_pass_rate_pct": clean_number(round((len(key_passes) / len(passes)) * 100, 2) if len(passes) > 0 else 0)
    }

    if not passes.empty:
        passes_copy = passes.copy()
        passes_copy["progress_distance"] = passes_copy["pass_end_x"].fillna(0) - passes_copy["origin_x"].fillna(0)
        player_metrics[player]["distribution"]["avg_pass_distance"] = clean_number(round(passes_copy["progress_distance"].mean(), 2))
        player_metrics[player]["distribution"]["progressive_passes"] = clean_number(len(passes_copy[passes_copy["progress_distance"] > 10]))
    else:
        player_metrics[player]["distribution"]["avg_pass_distance"] = 0.0
        player_metrics[player]["distribution"]["progressive_passes"] = 0.0

    # Initialize assists
    player_metrics[player]["distribution"]["assists"] = 0

    # --- ATTACK ---
    shots = df[df["event"].str.lower() == "shot"]
    on_target = shots[shots["outcome"].str.lower().isin(["on target", "goal"])]
    goals = shots[shots["outcome"].str.lower() == "goal"]

    player_metrics[player]["attack"] = {
        "shots": clean_number(len(shots)),
        "shots_on_target": clean_number(len(on_target)),
        "goals": clean_number(len(goals)),
        "shot_accuracy_pct": clean_number(round((len(on_target) / len(shots)) * 100, 2) if len(shots) > 0 else 0),
        "goal_conversion_pct": clean_number(round((len(goals) / len(shots)) * 100, 2) if len(shots) > 0 else 0)
    }

    # --- DEFENSE ---
    duels = df[df["event"].str.lower() == "duel"]
    tackles = df[df["event"].str.lower() == "tackle"]
    interceptions = df[df["event"].str.lower() == "interception"]
    clearances = df[df["event"].str.lower() == "clearance"]
    recoveries = df[df["event"].str.lower() == "recovery"]

    successful_duels = duels[duels["outcome"].str.lower() == "successful"].shape[0]
    successful_tackles = tackles[tackles["outcome"].str.lower() == "successful"].shape[0]

    blocked_shots = opponent_events[opponent_events["blocker_name"].fillna("").str.strip().str.lower() == player.strip().lower()].shape[0]

    saves = 0
    goals_conceded = 0
    if position == "GK":
        saves = opponent_events[
            (opponent_events["outcome"].str.lower() == "on target") &
            (opponent_events["keeper_name"].fillna("").str.strip().str.lower() == player.strip().lower())
        ].shape[0]

        goals_conceded = opponent_events[
            (opponent_events["outcome"].str.lower() == "goal")
        ].shape[0]

    defense_dict = {
        "duels": clean_number(len(duels)),
        "duels_won": clean_number(successful_duels),
        "duel_success_rate_pct": clean_number(round((successful_duels / len(duels)) * 100, 2) if len(duels) > 0 else 0),
        "tackles": clean_number(len(tackles)),
        "successful_tackles": clean_number(successful_tackles),
        "tackle_success_rate_pct": clean_number(round((successful_tackles / len(tackles)) * 100, 2) if len(tackles) > 0 else 0),
        "interceptions": clean_number(len(interceptions)),
        "clearances": clean_number(len(clearances)),
        "recoveries": clean_number(len(recoveries)),
        "blocked_shots": clean_number(blocked_shots)
    }

    if position == "GK":
        defense_dict["saves"] = clean_number(saves)
        defense_dict["goals_conceded"] = clean_number(goals_conceded)

    player_metrics[player]["defense"] = defense_dict

    # --- DISCIPLINE ---
    fouls = df[df["event"].str.lower() == "foul"]
    yellows = df[df["event"].str.lower() == "yellow card"]
    reds = df[df["event"].str.lower() == "red card"]
    player_metrics[player]["discipline"] = {
        "fouls_conceded": clean_number(len(fouls) + len(yellows) + len(reds)),
        "yellow_cards": clean_number(len(yellows)),
        "red_cards": clean_number(len(reds))
    }

# ---------- ASSISTS CALCULATION ----------
team_events_sorted = team_events.sort_values(by="created_at").reset_index(drop=True)

for idx, row in team_events_sorted.iterrows():
    if row["event"].lower() == "shot" and row["outcome"].lower() == "goal":
        if idx > 0:
            prev_row = team_events_sorted.iloc[idx - 1]
            # Check if previous event is a pass from same team
            if prev_row["event"].lower() == "pass":
                assister = prev_row["player_name"]
                if assister in player_metrics:
                    player_metrics[assister]["distribution"]["assists"] += 1

# ---------- FINAL RATINGS ----------
defender_positions = ["CB", "CDM", "GK", "LWB", "RWB"]
forward_positions = ["ST", "LW", "RW", "CAM"]

for player, s in player_metrics.items():

    # --- ATTACK ---
    shots = s["attack"]["shots"]
    on_target = s["attack"]["shots_on_target"]
    goals = s["attack"]["goals"]
    attack_score = 0

    if shots > 0:
        attack_score += min(shots * 3.5, 18)
        attack_score += min(on_target * 9, 27)

        if s["position"] in forward_positions:
            attack_score += min(goals * 18, 36)
            attack_score += (on_target / shots) * 18
            attack_score += min((shots - on_target) * 1.5, 5)
        else:
            attack_score += min(goals * 20, 40)
            attack_score += (on_target / shots) * 18

    s["match_rating_attack"] = clean_number(min(90, attack_score))

    # --- DEFENSE (UPDATED POSITION-AWARE) ---
    duels_won = s["defense"]["duels_won"]
    successful_tackles = s["defense"]["successful_tackles"]
    interceptions = s["defense"]["interceptions"]
    clearances = s["defense"]["clearances"]
    recoveries = s["defense"]["recoveries"]
    blocked_shots = s["defense"]["blocked_shots"]
    saves = s["defense"].get("saves", 0)

    position = s.get("position", "DEFAULT")
    defense_score = 0

    if position == "GK":
        defense_score += (
            s["defense"].get("duels_won", 0) * 4 +
            s["defense"].get("successful_tackles", 0) * 4 +
            s["defense"].get("interceptions", 0) * 12 +
            s["defense"].get("clearances", 0) * 6 +
            s["defense"].get("recoveries", 0) * 6 +
            s["defense"].get("saves", 0) * 15 -
            s["defense"].get("goals_conceded", 0) * 10
        )
    elif position in ["CB", "CDM", "LWB", "RWB"]:
        defense_score += duels_won * 8 + successful_tackles * 10 + interceptions * 17 + clearances * 10 + recoveries * 7 + blocked_shots * 6
    else:
        defense_score += duels_won * 2 + successful_tackles * 3 + interceptions * 4 + clearances * 3 + recoveries * 3 + blocked_shots * 2

    if position in defender_positions and defense_score < 30:
        if position == "CB":
            activity_level = s["distribution"]["passes"]/10 + s["possession"]["possession_pct"]/2
        elif position == "CDM":
            activity_level = s["distribution"]["progressive_passes"]/2 + s["possession"]["possession_pct"]/1.5
        elif position == "GK":
            activity_level = s["distribution"]["passes"]/15 + s["possession"]["possession_pct"]/3
        elif position in ["LWB", "RWB"]:
            activity_level = s["distribution"]["progressive_passes"]/1.5 + s["possession"]["possession_pct"]/2
        defense_score += min(activity_level, 15)

    s["match_rating_defense"] = clean_number(min(90, defense_score))

    # --- DISTRIBUTION ---
    acc = s["distribution"]["passing_accuracy_pct"] / 100
    prog = s["distribution"]["progressive_passes"]
    key = s["distribution"]["key_passes"]
    assists = s["distribution"]["assists"]
    dist_score = acc * 45 + min(prog * 2.8, 28) + key * 8 + assists * 6
    s["match_rating_distribution"] = clean_number(min(90, dist_score))

    # --- DISCIPLINE ---
    penalty = s["discipline"]["fouls_conceded"] * 6 + s["discipline"]["yellow_cards"] * 22 + s["discipline"]["red_cards"] * 55
    s["match_rating_discipline"] = clean_number(max(0, 100 - penalty))

    # --- GENERAL ---
    gen_score = s["possession"]["possession_pct"] * 2.8 + min(prog * 3.8, 38)
    s["match_rating_general"] = clean_number(min(90, gen_score))

    # --- OVERALL RATING ---
    w = POSITION_WEIGHTS.get(position, POSITION_WEIGHTS["DEFAULT"])
    overall = (
        s["match_rating_attack"] * w["attack"] +
        s["match_rating_defense"] * w["defense"] +
        s["match_rating_distribution"] * w["distribution"] +
        s["match_rating_discipline"] * w["discipline"]
    )
    s["match_rating_overall"] = clean_number(round(overall, 2))

    # --- CSV ROW ---
    flat = {"player_name": player, "minutes_played": minutes_played}
    for cat, vals in player_metrics[player].items():
        if isinstance(vals, dict):
            for k, v in vals.items():
                flat[f"{cat}_{k}"] = v
        else:
            flat[cat] = vals
    rows_for_csv.append(flat)

# ---------- SAVE ----------
os.makedirs(os.path.dirname(OUTPUT_JSON), exist_ok=True)

def deep_clean(obj):
    if isinstance(obj, dict):
        return {k: deep_clean(v) for k, v in obj.items()}
    elif isinstance(obj, (list, tuple)):
        return [deep_clean(v) for v in obj]
    else:
        return clean_number(obj) if isinstance(obj, (int, float, np.integer, np.floating)) else obj

with open(OUTPUT_JSON, "w", encoding="utf-8") as f:
    json.dump({TEAM_NAME: deep_clean(player_metrics)}, f, indent=4)

df_metrics = pd.DataFrame(rows_for_csv)
df_metrics = df_metrics.applymap(clean_number)
df_metrics.to_csv(OUTPUT_CSV, index=False)

print(f"✅ Realistic, position-aware ratings with assists saved → {OUTPUT_JSON}")