import pandas as pd
import numpy as np
import json
import os

# ---------- CONFIG ----------
DATA_PATH = "data/events.csv"
OUTPUT_PATH = "python_scripts/match_metrics.json"

# ---------- LOAD ----------
if not os.path.exists(DATA_PATH):
    print(f"❌ Error: dataset not found at {DATA_PATH}")
    raise SystemExit(1)

df = pd.read_csv(DATA_PATH)
df.columns = [c.strip() for c in df.columns]

# ---------- Helpers ----------
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

# Coordinate columns
origin_x = s(df, "origin_x").fillna(0).astype(float)
origin_y = s(df, "origin_y").fillna(0).astype(float)

# Map player -> team
player_to_team = {}
for pn, t in zip(player_name, team_col):
    if pn and pn not in player_to_team:
        player_to_team[pn] = t

teams = df["team"].dropna().unique().tolist()
team_a_name, team_b_name = teams[0], teams[1] if len(teams) > 1 else ("Unknown", "Unknown")

# ---------- MAIN METRIC FUNCTION ----------
def calculate_team_metrics(df_team, full_df, team_name, teams, match_metrics):
    idx = df_team.index
    team_mask = full_df.index.isin(idx)

    # Possession
    possession_mask = team_mask & (in_possession.astype(bool))
    possession_time = float(duration[possession_mask].sum())

    team_attacking_pos_mask = (
        (team_mask)
        & (in_possession.astype(bool))
        & (origin_third_l.str.contains("attacking|final third|attack", case=False, regex=True))
    )
    attacking_possession_time = float(duration[team_attacking_pos_mask].sum())
    attacking_possession_pct_of_team = (
        attacking_possession_time / possession_time * 100 if possession_time > 0 else 0.0
    )

    # Passes
    pass_mask = (event_l.str.contains("pass|cross|through", case=False, regex=True)) & team_mask
    total_passes = int(pass_mask.sum())
    successful_passes = int(((outcome_l == "successful") & pass_mask).sum())
    intercepted_passes = int(((outcome_l == "intercepted") & pass_mask).sum())
    unsuccessful_passes = int(((outcome_l == "unsuccessful") & pass_mask).sum())
    short_passes = int(((pass_type_l == "short") & pass_mask).sum())
    long_passes = int(((pass_type_l == "long") & pass_mask).sum())
    cross_passes = int(((pass_type_l == "cross") & pass_mask).sum())
    through_passes = int(((pass_type_l == "through ball") & pass_mask).sum())
    header_passes = int(((etype_l == "header") & pass_mask).sum())
    forward_passes = int(((pass_direction_l == "forward pass") & pass_mask).sum())
    lateral_passes = int(((pass_direction_l == "lateral pass") & pass_mask).sum())
    back_passes = int(((pass_direction_l == "back pass") & pass_mask).sum())
    passing_accuracy = (successful_passes / total_passes * 100) if total_passes > 0 else 0.0

    # ✅ Key Passes
    if "is_key_pass" in df.columns:
        key_passes = int(
            ((df["is_key_pass"].astype(str).str.lower() == "true") & team_mask & pass_mask).sum()
        )
    else:
        key_passes = 0

    # Shots
    shot_mask = (event_l.str.contains("shot", case=False, regex=True)) & team_mask
    total_shots = int(shot_mask.sum())
    shots_on_target = int((((outcome_l == "on target") | (outcome_l == "goal")) & shot_mask).sum())
    shots_off_target = int(((outcome_l == "off target") & shot_mask).sum())
    blocked_shots = int(((outcome_l == "blocked") & shot_mask).sum())
    goals = int(((outcome_l == "goal") & team_mask).sum())

    header_shots = int(((etype_l == "header") & shot_mask).sum())
    right_foot_shots = int(((etype_l == "right foot") & shot_mask).sum())
    left_foot_shots = int(((etype_l == "left foot") & shot_mask).sum())

    team_side = str(df_team["team_side"].mode()[0]).lower() if "team_side" in df_team.columns else "right"
    if team_side == "left":
        inside_box_mask = (origin_x <= 17) & (origin_y.between(21, 79))
    else:
        inside_box_mask = (origin_x >= 83) & (origin_y.between(21, 79))

    inside_box_mask &= shot_mask
    shots_inside_box = int(inside_box_mask.sum())
    shots_outside_box = total_shots - shots_inside_box

    shot_accuracy = (shots_on_target / total_shots * 100) if total_shots > 0 else 0.0
    goal_conversion = (goals / total_shots * 100) if total_shots > 0 else 0.0
    shot_accuracy_excl_blocked = (
        shots_on_target / (total_shots - blocked_shots) * 100
        if (total_shots - blocked_shots) > 0
        else 0.0
    )

    opponent_team = [t for t in teams if t != team_name][0] if len(teams) > 1 else None
    if opponent_team:
        if opponent_team not in match_metrics:
            match_metrics[opponent_team] = {"defense": {"blocks": 0}}
        if "defense" not in match_metrics[opponent_team]:
            match_metrics[opponent_team]["defense"] = {}
        match_metrics[opponent_team]["defense"]["blocks"] = (
            match_metrics[opponent_team]["defense"].get("blocks", 0) + blocked_shots
        )

    tackle_mask = (event_l == "tackle") & team_mask
    total_tackles = int(tackle_mask.sum())
    successful_tackles = int(((outcome_l == "successful") & tackle_mask).sum())
    tackles_success_rate = (successful_tackles / total_tackles * 100) if total_tackles > 0 else 0.0

    clearances = int(((event_l == "clearance") & team_mask).sum())
    interceptions = int(((event_l == "interception") & team_mask).sum())
    recoveries = int(((event_l == "recovery") & team_mask).sum())
    recoveries_attacking_mask = (
        (event_l == "recovery")
        & (origin_third_l.str.contains("attacking|final third|attack", case=False, regex=True))
        & team_mask
    )
    recoveries_attacking = int(recoveries_attacking_mask.sum())
    recoveries_attacking_pct = (recoveries_attacking / recoveries * 100) if recoveries > 0 else 0.0

    duel_mask = (event_l.str.contains("duel|challenge|tackle", case=False, regex=True)) & team_mask
    total_duels = int(duel_mask.sum())
    duels_won = int(((outcome_l == "successful") & duel_mask).sum())
    duels_success_rate = (duels_won / total_duels * 100) if total_duels > 0 else 0.0
    aerial_duels = int(((etype_l == "aerial") & duel_mask).sum())
    ground_duels = int(((etype_l == "ground") & duel_mask).sum())

    fouls_conceded = int((((event_l == "foul") | (event_l == "yellow card")) & team_mask).sum())
    yellow_cards = int(((event_l == "yellow card") & team_mask).sum())
    red_cards = int(((event_l == "red card") & team_mask).sum())
    offsides = int(((event_l == "offside") & team_mask).sum())
    corners = int(((event_l == "corner") & team_mask).sum())

    metrics = {
        "team_name": team_name,
        "possession": {
            "your_possession_time_seconds": possession_time,
            "attacking_third_possession_seconds": attacking_possession_time,
            "attacking_third_possession_pct_of_team": round(attacking_possession_pct_of_team, 2),
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
            "passing_accuracy_pct": round(passing_accuracy, 2),
        },
        "attack": {
            "goals": goals,
            "shots": total_shots,
            "shots_on_target": shots_on_target,
            "shots_off_target": shots_off_target,
            "blocked_shots": blocked_shots,
            "header_shots": header_shots,
            "right_foot_shots": right_foot_shots,
            "left_foot_shots": left_foot_shots,
            "shots_inside_box": shots_inside_box,
            "shots_outside_box": shots_outside_box,
            "shot_accuracy_pct": round(shot_accuracy, 2),
            "goal_conversion_pct": round(goal_conversion, 2),
            "shot_accuracy_excl_blocked_pct": round(shot_accuracy_excl_blocked, 2),
            "key_passes": key_passes,
        },
        "defense": {
            "tackles": total_tackles,
            "successful_tackles": successful_tackles,
            "tackles_success_rate_pct": round(tackles_success_rate, 2),
            "clearances": clearances,
            "interceptions": interceptions,
            "recoveries": recoveries,
            "recoveries_attacking_third": recoveries_attacking,
            "recoveries_attacking_third_pct": round(recoveries_attacking_pct, 2),
            "blocks": 0,
        },
        "general": {
            "duels": total_duels,
            "duels_won": duels_won,
            "duels_success_rate_pct": round(duels_success_rate, 2),
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


# ---------- RUN ----------
match_metrics = {}
team_a_df = df[df["team"] == team_a_name]
team_b_df = df[df["team"] == team_b_name]

team_a_metrics = calculate_team_metrics(team_a_df, df, team_a_name, teams, match_metrics)
team_b_metrics = calculate_team_metrics(team_b_df, df, team_b_name, teams, match_metrics)

team_a_blocks = team_a_metrics["attack"]["blocked_shots"]
team_b_blocks = team_b_metrics["attack"]["blocked_shots"]

team_a_metrics["defense"]["blocks"] = team_b_blocks
team_b_metrics["defense"]["blocks"] = team_a_blocks

total_pos_time = (
    team_a_metrics["possession"]["your_possession_time_seconds"]
    + team_b_metrics["possession"]["your_possession_time_seconds"]
)
if total_pos_time > 0:
    team_a_metrics["possession"]["possession_pct"] = round(
        team_a_metrics["possession"]["your_possession_time_seconds"] / total_pos_time * 100, 3
    )
    team_b_metrics["possession"]["possession_pct"] = round(
        team_b_metrics["possession"]["your_possession_time_seconds"] / total_pos_time * 100, 3
    )


# ---------- ✅ BALANCED DATA-DRIVEN RATINGS ----------
def compute_category_ratings(m):
    atk, defn, dist, pos, gen, disc = (
        m["attack"], m["defense"], m["distribution"], m["possession"], m["general"], m["discipline"]
    )

    ratings = {
        "match_rating_attack": round(np.clip((
            0.4 * atk["goal_conversion_pct"] +
            0.3 * atk["shot_accuracy_pct"] +
            0.2 * atk["shots_on_target"] -
            0.1 * atk["blocked_shots"]
        ) / 10, 0, 10), 2),

        "match_rating_defense": round(np.clip((
            0.4 * defn["tackles_success_rate_pct"] +
            0.2 * defn["recoveries"] +
            0.2 * defn["blocks"] +
            0.2 * defn["clearances"]
        ) / 10, 0, 10), 2),

        "match_rating_distribution": round(np.clip((
            (0.7 * (dist["successful_passes"] / max(dist["passes"], 1) * 100)) -
            (0.2 * (dist["intercepted_passes"] / max(dist["passes"], 1) * 100)) -
            (0.1 * (dist["unsuccessful_passes"] / max(dist["passes"], 1) * 100))
        ) / 10, 0, 10), 2),

        "match_rating_general": round(np.clip((
            0.3 * gen["duels_success_rate_pct"] +
            0.4 * pos["possession_pct"] +
            0.2 * gen["corner_awarded"] -
            0.1 * gen["offsides"]
        ) / 10, 0, 10), 2),

        "match_rating_discipline": round(np.clip(
            10 - abs((
                0.4 * disc["fouls_conceded"] +
                0.3 * disc["yellow_cards"] +
                0.3 * disc["red_cards"]
            ) / 10), 0, 10), 2),
    }

    return ratings


def compute_match_outcome_and_rating(team_a_metrics, team_b_metrics):
    a_goals = team_a_metrics["attack"]["goals"]
    b_goals = team_b_metrics["attack"]["goals"]

    if a_goals > b_goals:
        team_a_outcome, team_b_outcome = 1.0, 0.0
    elif a_goals < b_goals:
        team_a_outcome, team_b_outcome = 0.0, 1.0
    else:
        team_a_outcome, team_b_outcome = 0.5, 0.5

    a_ratings = compute_category_ratings(team_a_metrics)
    b_ratings = compute_category_ratings(team_b_metrics)

    def weighted_overall(r):
        return round(np.clip((
            0.25 * r["match_rating_attack"] +
            0.25 * r["match_rating_defense"] +
            0.20 * r["match_rating_distribution"] +
            0.20 * r["match_rating_general"] +
            0.10 * r["match_rating_discipline"]
        ), 0, 10), 2)

    team_a_rating = weighted_overall(a_ratings)
    team_b_rating = weighted_overall(b_ratings)

    team_a_metrics.update(a_ratings)
    team_b_metrics.update(b_ratings)

    return team_a_outcome, team_b_outcome, team_a_rating, team_b_rating


# ---------- EXECUTE RATINGS ----------
team_a_outcome, team_b_outcome, team_a_rating, team_b_rating = compute_match_outcome_and_rating(
    team_a_metrics, team_b_metrics
)

team_a_metrics["match_rating"] = team_a_rating
team_b_metrics["match_rating"] = team_b_rating
team_a_metrics["outcome_score"] = team_a_outcome
team_b_metrics["outcome_score"] = team_b_outcome

# ---------- SAVE ----------
match_duration_seconds = (
    float(pd.to_numeric(df["match_time_minute"], errors="coerce").max()) * 60.0
    if "match_time_minute" in df.columns else 0.0
)

final_output = {
    team_a_name: team_a_metrics,
    team_b_name: team_b_metrics,
    "match_duration_seconds": match_duration_seconds,
}

os.makedirs(os.path.dirname(OUTPUT_PATH), exist_ok=True)
with open(OUTPUT_PATH, "w") as f:
    json.dump(final_output, f, indent=2)

print("✅ Metrics computed and saved to", OUTPUT_PATH)
print(f"Outcome: {team_a_name}={team_a_metrics['outcome_score']} | {team_b_name}={team_b_metrics['outcome_score']}")
print(f"🏁 {team_a_name} {team_a_metrics['attack']['goals']} - {team_b_metrics['attack']['goals']} {team_b_name}")
print(f"⭐ {team_a_name} Rating: {team_a_rating}/10 | {team_b_name} Rating: {team_b_rating}/10")