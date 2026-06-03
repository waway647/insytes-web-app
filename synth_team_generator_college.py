#!/usr/bin/env python3
"""
synth_team_generator_college.py

Generate synthetic college-level team-match rows (Manila collegiate intensity).

Usage:
    python synth_team_generator_college.py --rows 200 --out synthetic_college_teams.csv --seed 42
"""
import argparse
import csv
import random
from datetime import datetime, timedelta
import math

# College-friendly team list (mix of Manila universities + local clubs)
DEFAULT_TEAMS = [
    "San Beda University","Ateneo de Manila University","De La Salle University",
    "University of the Philippines","Far Eastern University","University of Santo Tomas",
    "Adamson University","National University","Mapúa University","Lyceum",
    "2 Worlds FC","City United","Metro FC","Riverside FC","Lakeside Athletic",
    "Coastal Town","Valley Rovers","Highland Wanderers","Northbridge FC","Brookside United"
]

# Output header (keeps your schema)
HEADER = [
"team_name","possession__your_possession_time_seconds","possession__attacking_third_possession_seconds",
"possession__attacking_third_possession_pct_of_team","possession__possession_pct",
"distribution__passes","distribution__successful_passes","distribution__intercepted_passes",
"distribution__unsuccessful_passes","distribution__short_passes","distribution__long_passes",
"distribution__crosses","distribution__through_passes","distribution__header_passes",
"distribution__forward_passes","distribution__lateral_passes","distribution__back_passes",
"distribution__passing_accuracy_pct","distribution__assists","distribution__key_passes",
"attack__goals","attack__shots","attack__shots_on_target","attack__shots_off_target",
"attack__blocked_shots","attack__shot_creating_actions",
"defense__tackles","defense__successful_tackles","defense__tackles_success_rate_pct",
"defense__clearances","defense__interceptions","defense__recoveries",
"defense__recoveries_attacking_third","defense__recoveries_attacking_third_pct","defense__blocks","defense__saves",
"general__duels","general__duels_won","general__duels_success_rate_pct","general__aerial_duels",
"general__ground_duels","general__offsides","general__corner_awarded",
"discipline__fouls_conceded","discipline__yellow_cards","discipline__red_cards",
"match_rating_attack","match_rating_defense","match_rating_distribution","match_rating_general","match_rating_discipline",
"overall_rating","outcome_score","added_at","match_number","match_id","is_synthetic","match_rating_overall"
]

# helpers
def clamp(v, lo, hi):
    return max(lo, min(hi, v))

def int_near(rnd, mean, spread_ratio=0.5, minimum=0):
    sd = max(1.0, abs(mean) * spread_ratio)
    val = int(round(rnd.gauss(mean, sd)))
    return max(minimum, val)

def pct_near(rnd, mean_pct, spread=8.0):
    val = rnd.gauss(mean_pct, spread)
    return round(clamp(val, 0.0, 100.0), 2)

def generate_row(team_name, rnd, match_number=1, match_length_minutes=90):
    match_seconds = match_length_minutes * 60.0

    # possession: college teams centered ~48% with wider variance
    poss_pct = pct_near(rnd, 48.0, spread=14.0)
    possession_seconds = round(match_seconds * (poss_pct / 100.0) * rnd.uniform(0.92, 1.04), 3)

    # attacking-third possession: 8% - 35% of team possession for college matches
    att_third_pct_of_team = round(clamp(rnd.gauss(18.0, 7.0), 5.0, 40.0), 2)
    attacking_third_seconds = round(possession_seconds * (att_third_pct_of_team / 100.0), 3)

    # PASSES (college intensity)
    # pro baseline ~350 passes/90 -> college baseline scaled down ~240
    base_passes_90 = rnd.gauss(240, 50)
    possession_ratio = possession_seconds / (90.0*60.0)
    # team style factor (possession-oriented teams pass more)
    style_factor = clamp(rnd.gauss(1.0, 0.15), 0.7, 1.25)
    passes = int(max(0, round(base_passes_90 * possession_ratio * style_factor)))
    # pass splits
    short_frac = clamp(rnd.gauss(0.70, 0.07), 0.45, 0.95)
    long_frac = clamp(rnd.gauss(0.12, 0.04), 0.0, 0.35)
    cross_frac = clamp(rnd.gauss(0.06, 0.03), 0.0, 0.2)
    through_frac = clamp(rnd.gauss(0.03, 0.015), 0.0, 0.12)
    header_frac = clamp(rnd.gauss(0.02, 0.01), 0.0, 0.08)
    remaining = max(0.0, 1.0 - (short_frac + long_frac + cross_frac + through_frac + header_frac))
    forward_frac = clamp(rnd.gauss(0.45, 0.12), 0.1, 0.9) * remaining
    lateral_frac = remaining - forward_frac
    back_frac = clamp(rnd.gauss(0.08,0.04),0.0,0.5) * (passes > 0)

    short_passes = int(round(passes * short_frac))
    long_passes = int(round(passes * long_frac))
    crosses = int(round(passes * cross_frac))
    through_passes = int(round(passes * through_frac))
    header_passes = int(round(passes * header_frac))
    forward_passes = int(round(passes * forward_frac))
    lateral_passes = int(round(passes * lateral_frac))
    back_passes = int(round(passes * back_frac))

    # passing accuracy: college mean a bit lower
    passing_accuracy = pct_near(rnd, 74.0, spread=7.0)
    successful_passes = int(round(passes * (passing_accuracy / 100.0)))
    unsuccessful_passes = passes - successful_passes
    intercepted_passes = int(round(unsuccessful_passes * clamp(rnd.gauss(0.32, 0.10), 0.05, 0.85)))

    # KEY PASSES / ASSISTS (lower at college)
    key_passes = int(round(passes * clamp(rnd.gauss(0.018, 0.008), 0.002, 0.06)))
    assists = min(int(round(key_passes * clamp(rnd.gauss(0.12, 0.05), 0.05, 0.35))), key_passes)

    # ATTACK: shots lower than pro (college team shots/90 ~8-11)
    shots_per90 = clamp(rnd.gauss(10.0, 2.5), 4.0, 16.0)
    shots = int(round(shots_per90 * possession_ratio))
    if shots < 0: shots = 0
    on_target_frac = clamp(rnd.gauss(0.35, 0.09), 0.05, 0.75)
    shots_on_target = int(round(shots * on_target_frac))
    shots_off_target = shots - shots_on_target
    blocked_shots = int(round(shots * clamp(rnd.gauss(0.07, 0.04), 0.0, 0.35)))
    if blocked_shots > shots:
        blocked_shots = shots
    shot_creating_actions = int(round(shots * clamp(rnd.gauss(1.05, 0.22), 0.4, 1.9)))
    goal_conv_pct = clamp(rnd.gauss(9.0, 3.0), 1.0, 25.0)
    goals = int(round(shots * (goal_conv_pct / 100.0)))

    # DEFENSE: scaled down from pro
    tackles = int_near(rnd, mean=round(18 * possession_ratio))
    successful_tackles = int(round(tackles * clamp(rnd.gauss(0.56, 0.12), 0.25, 0.92)))
    tackle_success_rate_pct = round(100.0 * (successful_tackles / tackles) if tackles>0 else pct_near(rnd,50,18), 2)
    clearances = int_near(rnd, mean=round(14 * (1.0 - possession_ratio)), minimum=0)
    interceptions = int_near(rnd, mean=round(10 * (1.0 - possession_ratio)), minimum=0)
    recoveries = int_near(rnd, mean=round(14 * (1.0 - possession_ratio)), minimum=0)
    recoveries_att_third = int(round(recoveries * clamp(rnd.gauss(0.20,0.08), 0.03, 0.55)))
    recoveries_att_third_pct = round((recoveries_att_third / recoveries * 100.0) if recoveries>0 else 0.0, 2)
    blocks = int_near(rnd, mean=round(2 * (1.0 - possession_ratio)), minimum=0)

    # GOALKEEPER saves (team-level): small value or 0 if team didn't face many SOT (we don't model opponent though)
    saves = int_near(rnd, mean=int(round(shots_on_target * clamp(rnd.gauss(0.28,0.14),0.0,1.0))), minimum=0)

    # DUELS
    duels = int_near(rnd, mean=round(40 * possession_ratio))
    duels_won = int(round(duels * clamp(rnd.gauss(0.50, 0.12), 0.2, 0.85))) if duels>0 else 0
    duels_success_pct = round(100.0 * (duels_won / duels) if duels>0 else pct_near(rnd,48,18), 2)
    aerial_duels = int_near(rnd, mean=round(duels * 0.26))
    ground_duels = max(0, duels - aerial_duels)

    # OFFSIDES / CORNERS / DISCIPLINE
    offsides = int_near(rnd, mean=round(shots * 0.07), minimum=0)
    corners = int_near(rnd, mean=round(shots * 0.22), minimum=0)
    fouls_conceded = int_near(rnd, mean=round(10 * (1.0 - possession_ratio)))
    yellow_cards = int_near(rnd, mean=round(fouls_conceded * 0.08), minimum=0)
    red_cards = 1 if rnd.random() < 0.01 else 0

    # RATINGS (1-10), tuned for college (a bit lower / wider spread)
    def clamp_rating(x):
        return round(clamp(x, 1.0, 10.0), 2)

    shot_accuracy_pct = round(100.0 * (shots_on_target / shots) if shots>0 else pct_near(rnd,30,12), 2)
    attack_score = ( (goals * 4.0) + (shots_on_target * 0.85) + (shot_creating_actions * 0.6) + (key_passes * 0.5) )
    attack_rating = clamp_rating(3.2 + math.log1p(max(attack_score,0)) * 0.85 + rnd.gauss(0,0.6))

    distribution_score = successful_passes * 0.02 + (passing_accuracy - 68.0) * 0.06
    distribution_rating = clamp_rating(3.8 + math.log1p(max(distribution_score, 0.01)) * 0.85 + rnd.gauss(0,0.5))

    defense_score = (successful_tackles * 0.8 + interceptions * 0.6 + clearances * 0.5 + blocks * 0.9)
    defense_rating = clamp_rating(3.2 + math.log1p(max(defense_score,0)) * 0.7 + rnd.gauss(0,0.6))

    general_rating = clamp_rating(3.8 + math.log1p((duels_won + recoveries) * 0.025) * 0.85 + rnd.gauss(0,0.6))
    discipline_rating = clamp_rating(7.2 - (yellow_cards * 0.8 + red_cards * 2.0) + rnd.gauss(0,0.4))

    overall_rating = round(clamp(
        (attack_rating * 0.28 + defense_rating * 0.28 + distribution_rating * 0.2 + general_rating * 0.14 + discipline_rating * 0.1),
        1.0, 10.0
    ), 2)

    outcome_score = round(clamp((overall_rating - 1.0) / 9.0, 0.0, 1.0), 3)

    now = datetime.utcnow()
    added_at = (now - timedelta(days=rnd.randint(0, 365), seconds=rnd.randint(0,86400))).isoformat() + "+00:00"
    match_id = f"{team_name.replace(' ', '_')}_{now.strftime('%Y%m%d%H%M%S')}_{match_number}"

    row = {
        "team_name": team_name,
        "possession__your_possession_time_seconds": round(possession_seconds, 3),
        "possession__attacking_third_possession_seconds": round(attacking_third_seconds, 3),
        "possession__attacking_third_possession_pct_of_team": att_third_pct_of_team,
        "possession__possession_pct": round(poss_pct, 3),
        "distribution__passes": passes,
        "distribution__successful_passes": successful_passes,
        "distribution__intercepted_passes": intercepted_passes,
        "distribution__unsuccessful_passes": unsuccessful_passes,
        "distribution__short_passes": short_passes,
        "distribution__long_passes": long_passes,
        "distribution__crosses": crosses,
        "distribution__through_passes": through_passes,
        "distribution__header_passes": header_passes,
        "distribution__forward_passes": forward_passes,
        "distribution__lateral_passes": lateral_passes,
        "distribution__back_passes": back_passes,
        "distribution__passing_accuracy_pct": passing_accuracy,
        "distribution__assists": assists,
        "distribution__key_passes": key_passes,
        "attack__goals": goals,
        "attack__shots": shots,
        "attack__shots_on_target": shots_on_target,
        "attack__shots_off_target": shots_off_target,
        "attack__blocked_shots": blocked_shots,
        "attack__shot_creating_actions": shot_creating_actions,
        "defense__tackles": tackles,
        "defense__successful_tackles": successful_tackles,
        "defense__tackles_success_rate_pct": tackle_success_rate_pct,
        "defense__clearances": clearances,
        "defense__interceptions": interceptions,
        "defense__recoveries": recoveries,
        "defense__recoveries_attacking_third": recoveries_att_third,
        "defense__recoveries_attacking_third_pct": recoveries_att_third_pct,
        "defense__blocks": blocks,
        "defense__saves": saves,
        "general__duels": duels,
        "general__duels_won": duels_won,
        "general__duels_success_rate_pct": duels_success_pct,
        "general__aerial_duels": aerial_duels,
        "general__ground_duels": ground_duels,
        "general__offsides": offsides,
        "general__corner_awarded": corners,
        "discipline__fouls_conceded": fouls_conceded,
        "discipline__yellow_cards": yellow_cards,
        "discipline__red_cards": red_cards,
        "match_rating_attack": attack_rating,
        "match_rating_defense": defense_rating,
        "match_rating_distribution": distribution_rating,
        "match_rating_general": general_rating,
        "match_rating_discipline": discipline_rating,
        "overall_rating": overall_rating,
        "outcome_score": outcome_score,
        "added_at": added_at,
        "match_number": match_number,
        "match_id": match_id,
        "is_synthetic": 1,
        "match_rating_overall": overall_rating
    }

    # ensure all header keys present
    for h in HEADER:
        if h not in row:
            row[h] = 0 if h.startswith(("distribution__","attack__","defense__","general__","discipline__","possession__")) else ""

    return row

def main():
    parser = argparse.ArgumentParser(description="Generate synthetic college-level team-match rows.")
    parser.add_argument("--rows", "-n", type=int, default=100, help="Number of rows to generate")
    parser.add_argument("--out", "-o", type=str, default="synthetic_college_teams.csv", help="Output CSV file")
    parser.add_argument("--seed", type=int, default=None, help="Random seed (optional)")
    parser.add_argument("--match-length", type=float, default=90.0, help="Match length in minutes (default 90)")
    parser.add_argument("--teams-file", type=str, default=None, help="Optional file with team names (one per line)")
    args = parser.parse_args()

    rnd = random.Random(args.seed)

    teams = list(DEFAULT_TEAMS)
    if args.teams_file:
        try:
            with open(args.teams_file, "r", encoding="utf-8") as tf:
                custom = [line.strip() for line in tf if line.strip()]
                if custom:
                    teams = custom
        except Exception as e:
            print("Warning: couldn't read teams-file, falling back to defaults:", e)

    with open(args.out, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=HEADER, extrasaction="ignore")
        writer.writeheader()
        for i in range(args.rows):
            team = rnd.choice(teams)
            match_num = rnd.randint(1, 1000)
            row = generate_row(team, rnd, match_number=match_num, match_length_minutes=args.match_length)
            writer.writerow({k: row.get(k, "") for k in HEADER})

    print(f"Generated {args.rows} college-level team rows -> {args.out}")

if __name__ == "__main__":
    main()
