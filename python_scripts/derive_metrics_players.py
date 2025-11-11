# ============================================================
# File: python_scripts/derive_metrics_players.py
# Purpose: Compute per-player derived metrics from raw event data
# ============================================================

import pandas as pd
import json
import os
from collections import defaultdict

# ---------- CONFIG ----------
EVENTS_PATH = "data/events.csv"        # your tagging output
OUTPUT_PATH = "data/sanbeda_players_derived_metrics.json"
TEAM_NAME = "San Beda"

# ---------- LOAD ----------
if not os.path.exists(EVENTS_PATH):
    raise FileNotFoundError(f"❌ No events file found at {EVENTS_PATH}")

print(f"📂 Loading tagged events from: {EVENTS_PATH}")
events = pd.read_csv(EVENTS_PATH)

if "team" not in events.columns or "player" not in events.columns:
    raise KeyError("❌ Missing 'team' or 'player' columns in events.csv")

# Filter by team (San Beda)
team_events = events[events["team"] == TEAM_NAME]

# ---------- DERIVED METRICS PER PLAYER ----------
player_metrics = defaultdict(lambda: defaultdict(dict))

for player, df in team_events.groupby("player"):
    # Possession
    player_metrics[player]["possession"]["your_possession_time_seconds"] = df[df["event"] == "possession"]["duration"].sum()
    player_metrics[player]["possession"]["possession_pct"] = round(
        (df[df["event"] == "possession"]["duration"].sum() / team_events["duration"].sum()) * 100, 2
    ) if "duration" in df.columns and team_events["duration"].sum() > 0 else 0

    # Passing
    passes = df[df["event"] == "pass"]
    player_metrics[player]["distribution"]["passes"] = len(passes)
    player_metrics[player]["distribution"]["successful_passes"] = passes[passes["outcome"] == "successful"].shape[0]
    player_metrics[player]["distribution"]["unsuccessful_passes"] = passes[passes["outcome"] == "unsuccessful"].shape[0]
    player_metrics[player]["distribution"]["passing_accuracy_pct"] = round(
        (player_metrics[player]["distribution"]["successful_passes"] / len(passes)) * 100, 2
    ) if len(passes) > 0 else 0

    # Attack
    shots = df[df["event"] == "shot"]
    player_metrics[player]["attack"]["shots"] = len(shots)
    player_metrics[player]["attack"]["shots_on_target"] = shots[shots["outcome"] == "on_target"].shape[0]
    player_metrics[player]["attack"]["goals"] = shots[shots["outcome"] == "goal"].shape[0]
    player_metrics[player]["attack"]["shot_accuracy_pct"] = round(
        (player_metrics[player]["attack"]["shots_on_target"] / len(shots)) * 100, 2
    ) if len(shots) > 0 else 0
    player_metrics[player]["attack"]["goal_conversion_pct"] = round(
        (player_metrics[player]["attack"]["goals"] / len(shots)) * 100, 2
    ) if len(shots) > 0 else 0

    # Defense
    tackles = df[df["event"] == "tackle"]
    player_metrics[player]["defense"]["tackles"] = len(tackles)
    player_metrics[player]["defense"]["successful_tackles"] = tackles[tackles["outcome"] == "successful"].shape[0]
    player_metrics[player]["defense"]["tackles_success_rate_pct"] = round(
        (player_metrics[player]["defense"]["successful_tackles"] / len(tackles)) * 100, 2
    ) if len(tackles) > 0 else 0

    # Discipline
    fouls = df[df["event"] == "foul"]
    cards = df[df["event"].isin(["yellow_card", "red_card"])]
    player_metrics[player]["discipline"]["fouls_conceded"] = len(fouls)
    player_metrics[player]["discipline"]["yellow_cards"] = len(cards[cards["event"] == "yellow_card"])
    player_metrics[player]["discipline"]["red_cards"] = len(cards[cards["event"] == "red_card"])

print(f"✅ Derived metrics computed for {len(player_metrics)} players.")

# ---------- SAVE ----------
with open(OUTPUT_PATH, "w") as f:
    json.dump({TEAM_NAME: player_metrics}, f, indent=4)

print(f"✅ Saved player-derived metrics to: {OUTPUT_PATH}")