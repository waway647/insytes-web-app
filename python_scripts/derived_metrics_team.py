import pandas as pd
import numpy as np
import json
import os
import sys

# Command line argument handling
if len(sys.argv) > 1:
    if sys.argv[1] == "--dataset" and len(sys.argv) > 2:
        MATCH_NAME = sys.argv[2]
    else:
        MATCH_NAME = sys.argv[1]
else:
    MATCH_NAME = "sbu_vs_2worlds"

# ---------- CONFIG ----------
DATA_PATH = f"output_dataset/{MATCH_NAME}_events.csv"

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

# Create match-specific output directory
MATCH_OUTPUT_DIR = f"output/matches/{MATCH_NAME}"
os.makedirs(MATCH_OUTPUT_DIR, exist_ok=True)

team_name_safe = TEAM_NAME.lower().replace(" ", "_")
OUTPUT_PATH = f"{MATCH_OUTPUT_DIR}/{team_name_safe}_team_derived_metrics.json"

# ---------- LOAD ----------
if not os.path.exists(DATA_PATH):
    print(f"Error: dataset not found at {DATA_PATH}")
    raise SystemExit(1)

df = pd.read_csv(DATA_PATH)
df.columns = [c.strip() for c in df.columns]

# ---------- HELPERS ----------
def s(df, col):
    return df[col] if col in df.columns else pd.Series([np.nan]*len(df), index=df.index)

event = s(df, "event").fillna("").astype(str).str.strip()
event_l = event.str.lower()
outcome = s(df, "outcome").fillna("").astype(str).str.strip()
outcome_l = outcome.str.lower()
etype = s(df, "type").fillna("").astype(str).str.strip()
etype_l = etype.str.lower()
pass_type = s(df, "pass_type").fillna("").astype(str).str.strip()
pass_type_l = pass_type.str.lower()
pass_direction = s(df, "pass_direction").fillna("").astype(str).str.strip()
pass_direction_l = pass_direction.str.lower()
receiver_name = s(df, "receiver_name").fillna("").astype(str).str.strip()
player_name = s(df, "player_name").fillna("").astype(str).str.strip()
team_col = s(df, "team").fillna("").astype(str).str.strip()
origin_third = s(df, "origin_third").fillna("").astype(str).str.strip()
origin_third_l = origin_third.str.lower()
in_possession = s(df, "in_possession")
duration = s(df, "duration")
if "duration" not in df.columns:
    duration = pd.Series([5.0]*len(df), index=df.index)
else:
    duration = pd.to_numeric(duration, errors="coerce").fillna(5.0)

# ---------- PLAYER TO TEAM ----------
player_to_team = {}
for pn, t in zip(player_name, team_col):
    if pn and pn not in player_to_team:
        player_to_team[pn] = t

teams = df["team"].dropna().unique().tolist()

# Ensure home team (TEAM_NAME) is first in the list
if TEAM_NAME in teams:
    # Remove TEAM_NAME and put it first
    teams.remove(TEAM_NAME)
    teams.insert(0, TEAM_NAME)

team_a_name, team_b_name = teams[0], teams[1] if len(teams) > 1 else ("Unknown", "Unknown")

# ---------- SHOT-CREATING ACTIONS ----------
def compute_scas(df):
    df = df.sort_values("created_at").reset_index(drop=True)
    df["shot_creating_actions"] = 0

    for idx, row in df.iterrows():
        if row["event"].lower() == "shot":
            prev_events = df[max(0, idx-3):idx]  # last 3 events
            sca_count = prev_events[
                (prev_events["team"] == row["team"]) &
                (prev_events["event"].str.lower().isin(["pass", "dribble", "foul won"]))
            ].shape[0]
            df.at[idx, "shot_creating_actions"] = sca_count
    return df.groupby("team")["shot_creating_actions"].sum().to_dict()

sca_per_team = compute_scas(df)

# ---------- MAIN METRIC FUNCTION ----------
def calculate_team_metrics(df_team, full_df, team_name, teams):
    team_mask = full_df["team"] == team_name

    # Possession
    possession_mask = team_mask & (in_possession.astype(bool))
    possession_time = float(duration[possession_mask].sum())

    attacking_possession_mask = team_mask & (in_possession.astype(bool)) & (
        origin_third_l.str.contains("attacking|final third|attack", case=False, regex=True)
    )
    attacking_time = float(duration[attacking_possession_mask].sum())
    attacking_pct = (attacking_time / possession_time * 100) if possession_time > 0 else 0.0

    # Passes
    pass_mask = team_mask & event_l.str.contains("pass|cross|through", case=False, regex=True)
    total_passes = int(pass_mask.sum())
    successful_passes = int(((outcome_l == "successful") & pass_mask).sum())
    intercepted_passes = int(((outcome_l == "intercepted") & pass_mask).sum())
    unsuccessful_passes = int(((outcome_l == "unsuccessful") & pass_mask).sum())
    short_passes = int(((pass_type_l == "short") & pass_mask).sum())
    long_passes = int(((pass_type_l == "long") & pass_mask).sum())
    cross_passes = int(((pass_type_l == "cross") & pass_mask).sum())
    through_passes = int(((pass_type_l == "through ball") & pass_mask).sum())
    forward_passes = int(((pass_direction_l == "forward pass") & pass_mask).sum())
    lateral_passes = int(((pass_direction_l == "lateral pass") & pass_mask).sum())
    back_passes = int(((pass_direction_l == "back pass") & pass_mask).sum())
    header_passes = int(((etype_l == "header") & pass_mask).sum())
    passing_accuracy = (successful_passes / max(total_passes,1)) * 100

    # Key Passes
    key_passes = int(((df["is_key_pass"].astype(str).str.lower() == "true") & team_mask & pass_mask).sum()) if "is_key_pass" in df.columns else 0

    # Shots
    shot_mask = team_mask & event_l.str.contains("shot", case=False, regex=True)
    total_shots = int(shot_mask.sum())
    shots_on_target = int((((outcome_l == "on target") | (outcome_l == "goal") | outcome_l.str.contains("save")) & shot_mask).sum())
    shots_off_target = int(((outcome_l == "off target") & shot_mask).sum())
    blocked_shots = int(((outcome_l == "blocked") & shot_mask).sum())
    goals = int(((outcome_l == "goal") & team_mask).sum())

    # Assists
    df_team_sorted = df_team.sort_values("created_at").reset_index(drop=True)
    assists = 0
    for idx_row, row in df_team_sorted.iterrows():
        if row["event"].lower() == "shot" and row["outcome"].lower() == "goal":
            if idx_row > 0:
                prev_row = df_team_sorted.iloc[idx_row-1]
                if prev_row["event"].lower() == "pass":
                    assists += 1

    # Saves
    team_saves = 0
    opponent_team_name = [t for t in teams if t != team_name][0] if len(teams) > 1 else None
    if opponent_team_name:
        opponent_events = df[df["team"] == opponent_team_name]
        if "keeper_name" in df.columns and df_team["player_name"].notna().any():
            gks = df_team["player_name"].dropna().unique()
            for gk in gks:
                team_saves += opponent_events[
                    (opponent_events["event"].str.lower() == "shot") &
                    (opponent_events["outcome"].str.lower() == "on target") &
                    (opponent_events["keeper_name"].fillna("").str.strip().str.lower() == gk.strip().lower())
                ].shape[0]
        else:
            team_saves = opponent_events[
                (opponent_events["event"].str.lower() == "shot") &
                (opponent_events["outcome"].str.lower() == "on target")
            ].shape[0]

    # Defensive metrics
    tackles = int(((event_l == "tackle") & team_mask).sum())
    successful_tackles = int(((outcome_l == "successful") & (event_l == "tackle") & team_mask).sum())
    tackles_pct = (successful_tackles / max(tackles,1)) * 100
    clearances = int(((event_l == "clearance") & team_mask).sum())
    interceptions = int(((event_l == "interception") & team_mask).sum())
    recoveries = int(((event_l == "recovery") & team_mask).sum())
    recoveries_att = int(((event_l == "recovery") & (origin_third_l.str.contains("attacking|final third|attack", regex=True)) & team_mask).sum())
    recoveries_att_pct = (recoveries_att / max(recoveries,1)) * 100

    duels = int(((event_l.str.contains("duel|challenge|tackle", regex=True)) & team_mask).sum())
    duels_won = int(((outcome_l == "successful") & (event_l.str.contains("duel|challenge|tackle", regex=True)) & team_mask).sum())
    duels_pct = (duels_won / max(duels,1)) * 100
    aerial_duels = int(((etype_l == "aerial") & team_mask).sum())
    ground_duels = int(((etype_l == "ground") & team_mask).sum())
    fouls_conceded = int((((event_l=="foul") | (event_l=="yellow card")) & team_mask).sum())
    yellow_cards = int(((event_l=="yellow card") & team_mask).sum())
    red_cards = int(((event_l=="red card") & team_mask).sum())
    offsides = int(((event_l=="offside") & team_mask).sum())
    corners = int(((event_l=="corner") & team_mask).sum())

    metrics = {
        "team_name": team_name,
        "possession": {
            "your_possession_time_seconds": possession_time,
            "attacking_third_possession_seconds": attacking_time,
            "attacking_third_possession_pct_of_team": round(attacking_pct,2),
            "possession_pct": 0.0,
        },
        "distribution": {
            "passes": total_passes,
            "successful_passes": successful_passes,
            "intercepted_passes": intercepted_passes,
            "unsuccessful_passes": unsuccessful_passes,
            "short_passes": short_passes,
            "long_passes": long_passes,
            "crosses": cross_passes,
            "through_passes": through_passes,
            "header_passes": header_passes,
            "forward_passes": forward_passes,
            "lateral_passes": lateral_passes,
            "back_passes": back_passes,
            "passing_accuracy_pct": round(passing_accuracy,2),
            "assists": assists,
            "key_passes": key_passes,
        },
        "attack": {
            "goals": goals,
            "shots": total_shots,
            "shots_on_target": shots_on_target,
            "shots_off_target": shots_off_target,
            "blocked_shots": blocked_shots,
            "shot_creating_actions": sca_per_team.get(team_name,0),
        },
        "defense": {
            "tackles": tackles,
            "successful_tackles": successful_tackles,
            "tackles_success_rate_pct": round(tackles_pct,2),
            "clearances": clearances,
            "interceptions": interceptions,
            "recoveries": recoveries,
            "recoveries_attacking_third": recoveries_att,
            "recoveries_attacking_third_pct": round(recoveries_att_pct,2),
            "blocks": 0,
            "saves": team_saves,
        },
        "general": {
            "duels": duels,
            "duels_won": duels_won,
            "duels_success_rate_pct": round(duels_pct,2),
            "aerial_duels": aerial_duels,
            "ground_duels": ground_duels,
            "offsides": offsides,
            "corner_awarded": corners,
        },
        "discipline": {
            "fouls_conceded": fouls_conceded,
            "yellow_cards": yellow_cards,
            "red_cards": red_cards,
        },
    }
    return metrics

# ---------- RUN METRICS ----------
team_a_df = df[df["team"] == team_a_name]
team_b_df = df[df["team"] == team_b_name]

team_a_metrics = calculate_team_metrics(team_a_df, df, team_a_name, teams)
team_b_metrics = calculate_team_metrics(team_b_df, df, team_b_name, teams)

# Assign blocks to opponent
team_a_metrics["defense"]["blocks"] = team_b_metrics["attack"]["blocked_shots"]
team_b_metrics["defense"]["blocks"] = team_a_metrics["attack"]["blocked_shots"]

# Possession %
total_pos_time = team_a_metrics["possession"]["your_possession_time_seconds"] + team_b_metrics["possession"]["your_possession_time_seconds"]
if total_pos_time > 0:
    team_a_metrics["possession"]["possession_pct"] = round(team_a_metrics["possession"]["your_possession_time_seconds"] / total_pos_time * 100,3)
    team_b_metrics["possession"]["possession_pct"] = round(team_b_metrics["possession"]["your_possession_time_seconds"] / total_pos_time * 100,3)

# ---------- COMPUTE RELATIVE RATINGS (Improved Version) ----------
def compute_relative_ratings(team_metrics, opponent_metrics):
    atk = team_metrics["attack"]
    defn = team_metrics["defense"]
    dist = team_metrics["distribution"]
    pos = team_metrics["possession"]
    gen = team_metrics["general"]
    disc = team_metrics["discipline"]

    opp_atk = opponent_metrics["attack"]
    opp_def = opponent_metrics["defense"]
    opp_dist = opponent_metrics["distribution"]
    opp_pos = opponent_metrics["possession"]
    opp_gen = opponent_metrics["general"]
    opp_disc = opponent_metrics["discipline"]

    # --- ATTACK RATING ---
    atk_ratio = min(
        (atk["goals"] + atk["shots_on_target"] + dist.get("key_passes",0) + atk["shot_creating_actions"]) /
        max((opp_atk["goals"] + opp_atk["shots_on_target"] + opp_dist.get("key_passes",0) + opp_atk["shot_creating_actions"]),1),
        2.0
    )

    # --- DEFENSE RATING ---
    def_ratio = min(
        (0.4*defn["tackles_success_rate_pct"] + 0.3*defn["recoveries"] + 0.2*defn["saves"] + 0.1*defn["blocks"] + 0.1*gen.get("duels_success_rate_pct",0)) /
        max((0.4*opp_def["tackles_success_rate_pct"] + 0.3*opp_def["recoveries"] + 0.2*opp_def["saves"] + 0.1*opp_def["blocks"] + 0.1*opp_gen.get("duels_success_rate_pct",0)),1),
        2.0
    )

    # --- DISTRIBUTION RATING ---
    dist_ratio = min(
        (0.8*dist["passing_accuracy_pct"] + 0.2*dist.get("key_passes",0)*10) /
        max((0.8*opp_dist["passing_accuracy_pct"] + 0.2*opp_dist.get("key_passes",0)*10),1),
        1.5
    )

    # --- GENERAL / POSSESSION RATING ---
    pos_ratio = min(
        (0.7*pos["attacking_third_possession_pct_of_team"] + 0.3*pos["possession_pct"]) /
        max((0.7*opp_pos["attacking_third_possession_pct_of_team"] + 0.3*opp_pos["possession_pct"]),1),
        1.5
    )

    gen_ratio = min(gen.get("duels_success_rate_pct",0) / max(opp_gen.get("duels_success_rate_pct",1),1), 1.5)

    # --- DISCIPLINE RATING ---
    disc_score = max(
        10 - (0.4*disc.get("fouls_conceded",0) + 0.3*disc.get("yellow_cards",0) + 0.3*disc.get("red_cards",0)),
        0
    )

    # --- FINAL RATINGS ---
    ratings = {
        "match_rating_attack": round(5 + 3 * np.clip(atk_ratio/2,0,1),2),
        "match_rating_defense": round(5 + 3 * np.clip(def_ratio/2,0,1),2),
        "match_rating_distribution": round(5 + 3 * np.clip(dist_ratio/1.5,0,1),2),
        "match_rating_general": round(5 + 3 * np.clip(pos_ratio/1.5 * 0.5 + gen_ratio/1.5 * 0.5,0,1),2),
        "match_rating_discipline": round(5 + 3 * (disc_score/10),2),
    }

    # --- OVERALL ---
    ratings["overall_rating"] = round(np.mean(list(ratings.values())),2)

    return ratings

# Apply relative ratings
team_a_ratings = compute_relative_ratings(team_a_metrics, team_b_metrics)
team_b_ratings = compute_relative_ratings(team_b_metrics, team_a_metrics)

team_a_metrics.update(team_a_ratings)
team_b_metrics.update(team_b_ratings)

# ---------- OUTCOME ----------
a_goals = team_a_metrics["attack"]["goals"]
b_goals = team_b_metrics["attack"]["goals"]
team_a_metrics["outcome_score"] = 1.0 if a_goals>b_goals else 0.0 if a_goals<b_goals else 0.5
team_b_metrics["outcome_score"] = 1.0 if b_goals>a_goals else 0.0 if b_goals<a_goals else 0.5

# ---------- SAVE ----------
match_duration_seconds = float(pd.to_numeric(df["match_time_minute"], errors="coerce").max()*60) if "match_time_minute" in df.columns else 0.0

final_output = {
    "match_id": MATCH_ID,
    "match_name": MATCH_NAME,
    team_a_name: team_a_metrics,  # Home team first
    team_b_name: team_b_metrics,  # Away team second
    "match_duration_seconds": match_duration_seconds
}

# Helper to convert numpy types to native Python types
def convert_numpy_types(obj):
    if isinstance(obj, dict):
        return {k: convert_numpy_types(v) for k, v in obj.items()}
    elif isinstance(obj, list):
        return [convert_numpy_types(v) for v in obj]
    elif isinstance(obj, (np.integer, np.int64)):
        return int(obj)
    elif isinstance(obj, (np.floating, np.float64)):
        return float(obj)
    else:
        return obj

# Convert final_output
final_output = convert_numpy_types(final_output)

os.makedirs(os.path.dirname(OUTPUT_PATH), exist_ok=True)
with open(OUTPUT_PATH, "w") as f:
    json.dump(final_output, f, indent=2)

print("Metrics computed and saved to", OUTPUT_PATH)
print(f"Outcome: {team_a_name}={team_a_metrics['outcome_score']} | {team_b_name}={team_b_metrics['outcome_score']}")
print(f"Final Score: {team_a_name} {team_a_metrics['attack']['goals']} - {team_b_metrics['attack']['goals']} {team_b_name}")
print(f"Ratings: {team_a_name} Rating: {team_a_metrics['overall_rating']}/10 | {team_b_name} Rating: {team_b_metrics['overall_rating']}/10")
print("SCAs, Key Passes & Defensive Effectiveness included")