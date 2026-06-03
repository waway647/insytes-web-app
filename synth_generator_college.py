#!/usr/bin/env python3
"""
synth_generator_college.py

Generate synthetic college-level player rows (Manila collegiate intensity).
Usage:
    python synth_generator_college.py --rows 1000 --out synthetic_college.csv --seed 42
"""

import argparse
import random
import csv
from datetime import datetime, timedelta
from math import floor

# ======= CONFIG ========
POSITIONS = [
    "GK","CB","LB","LWB","RWB","RB","CDM","CM","CAM","RW","LW","ST","CF"
]
STATUSES = ["starter","substitute","DNP"]
TEAMS = [
    "San Beda University","Ateneo de Manila University","De La Salle University",
    "University of the Philippines","Far Eastern University","University of Santo Tomas",
    "Adamson University","National University","Mapúa University","Lyceum",
    "2 Worlds FC","City United","Metro FC","Riverside FC","Lakeside Athletic",
    "Coastal Town","Valley Rovers","Highland Wanderers","Northbridge FC",
    "Southgate United","Eastview Rangers","Westwood Athletic","Crown City FC",
    "Redwood Borough","Harborview United","Summit Peak FC","Goldenfield Rovers",
    "Hillcrest Athletic","Riverstone FC","Brookside United"
]

SAMPLE_NAMES = [
    "Aningalan, Jul","Barro, Harvy","Digal, Diegocarl","Encaya, Pius","Escodo, Bel",
    "Fernandez, Laurence","Libarnes, Jaro","Omitade, Edvard","Salingay, Sam",
    "Tabelin, Kirvy","Tagara, Pedro","Acebedo, James","Bacaycay, Kurt","Bacnis, Hugo",
    "Asignar, Josh Rupert","Unabia, Charles","Unabia, Mark","Ybanez, Moimoi","Lood, Trevor",
    "Tabay, Albin","Labrador, Sean","Ebrada, Zraim Uichi","Villacarlos, Uriel","Legara, Ezralph",
    "Nemenzo, Sirach Ethan","Taberos, Earl","Berciles, Earl","Espana, Dave","Diola, Ezekiel",
    "Darapan, Jermi","Soria, Stephen","Valdez, Alysa","Pedimonte, Nhiboy","Mapula, Paul Joshua",
    "Mapula, Paul Vincent","Lagaya, Rick Matthew","Ladines, Marvin","Santos, Aris","Moleje, Josh",
    "Garces, Josh","Merino, Josh","Pormento, Edzon","Abellana, Marco","Abad, Ryan",
    "Alcantara, Paolo","Alonzo, Kaye","Amador, Roniel","Andrada, Vince","Apostol, Carlo"
]

# Minimal header adapted from your large header (keeps original ordering)
HEADER = [
"player_name","position","minutes_played","dpr","dpr_breakdown__defensive_impact","dpr_breakdown__build_up_quality",
"dpr_breakdown__positioning_score","dpr_breakdown__physical_play","status","team","number",
"key_stats_p90__shots_p90","key_stats_p90__shots_on_target_p90","key_stats_p90__goals_p90",
"key_stats_p90__passes_p90","key_stats_p90__successful_passes_p90","key_stats_p90__unsuccessful_passes_p90",
"key_stats_p90__key_passes_p90","key_stats_p90__assists_p90","key_stats_p90__avg_pass_distance_p90",
"key_stats_p90__progressive_passes_p90","key_stats_p90__duels_p90","key_stats_p90__duels_won_p90",
"key_stats_p90__tackles_p90","key_stats_p90__successful_tackles_p90","key_stats_p90__tackle_success_rate_pct_p90",
"key_stats_p90__interceptions_p90","key_stats_p90__clearances_p90","key_stats_p90__recoveries_p90",
"key_stats_p90__blocked_shots_p90","key_stats_p90__dribbles_p90","key_stats_p90__successful_dribbles_p90",
"key_stats_p90__dribble_success_rate_pct_p90","key_stats_p90__shot_accuracy_pct_p90","key_stats_p90__goal_conversion_pct_p90",
"key_stats_p90__passing_accuracy_pct_p90","key_stats_p90__key_pass_rate_pct_p90","key_stats_p90__duel_success_rate_pct_p90",
"raw_stats__possession__your_possession_time_seconds","raw_stats__possession__possession_pct",
"raw_stats__distribution__passes","raw_stats__distribution__successful_passes","raw_stats__distribution__unsuccessful_passes",
"raw_stats__distribution__passing_accuracy_pct","raw_stats__distribution__key_passes","raw_stats__distribution__key_pass_rate_pct",
"raw_stats__distribution__assists","raw_stats__distribution__avg_pass_distance","raw_stats__distribution__progressive_passes",
"raw_stats__attack__shots","raw_stats__attack__shots_on_target","raw_stats__attack__goals","raw_stats__attack__shot_accuracy_pct",
"raw_stats__attack__goal_conversion_pct","raw_stats__dribbles__dribbles","raw_stats__dribbles__successful_dribbles",
"raw_stats__dribbles__dribble_success_rate_pct","raw_stats__defense__duels","raw_stats__defense__duels_won",
"raw_stats__defense__duel_success_rate_pct","raw_stats__defense__tackles","raw_stats__defense__successful_tackles",
"raw_stats__defense__tackle_success_rate_pct","raw_stats__defense__interceptions","raw_stats__defense__clearances",
"raw_stats__defense__recoveries","raw_stats__defense__blocked_shots","raw_stats__discipline__fouls_conceded",
"raw_stats__discipline__yellow_cards","raw_stats__discipline__red_cards",
"shots_p90","shots_on_target_p90","goals_p90","passes_p90","successful_passes_p90","unsuccessful_passes_p90",
"key_passes_p90","assists_p90","avg_pass_distance_p90","progressive_passes_p90","duels_p90","duels_won_p90",
"tackles_p90","successful_tackles_p90","tackle_success_rate_pct_p90","interceptions_p90","clearances_p90","recoveries_p90",
"blocked_shots_p90","dribbles_p90","successful_dribbles_p90","dribble_success_rate_pct_p90","shot_accuracy_pct_p90",
"goal_conversion_pct_p90","passing_accuracy_pct_p90","key_pass_rate_pct_p90","duel_success_rate_pct_p90",
"dpr_breakdown__game_control","dpr_breakdown__transition_play","dpr_breakdown__defensive_contribution","dpr_breakdown__creativity",
"dpr_breakdown__work_rate","dpr_breakdown__finishing_quality","dpr_breakdown__offensive_contribution","dpr_breakdown__link_up_play",
"dpr_breakdown__movement_threat","dpr_breakdown__shot_stopping","dpr_breakdown__distribution_quality","dpr_breakdown__command_area",
"dpr_breakdown__ball_playing","dpr_breakdown__consistency",
"raw_stats__goalkeeper__shots_faced","raw_stats__goalkeeper__saves","raw_stats__goalkeeper__goals_conceded","raw_stats__goalkeeper__save_pct",
"raw_stats__goalkeeper__shots_on_target_against","raw_stats__goalkeeper__goals_against",
"saves_p90","goals_conceded_p90","shots_faced_p90","shots_on_target_against_p90","goals_against_p90","save_pct_p90","saves_per90",
"goals_against_per90","shots_on_target_against_per90","save_pct",
"match_number","match_id","added_at","position_history","position_changed",
"shots_raw","shots_on_target_raw","goals_raw","passes_raw","successful_passes_raw","unsuccessful_passes_raw",
"passing_accuracy_pct_raw","key_passes_raw","assists_raw","avg_pass_distance_raw","progressive_passes_raw","duels_raw",
"duels_won_raw","tackles_raw","successful_tackles_raw","interceptions_raw","clearances_raw","recoveries_raw","blocked_shots_raw",
"dribbles_raw","successful_dribbles_raw","dribble_success_rate_pct_raw","note"
]

# === Helper functions ===

def rand_name(rnd):
    # pick existing sample or synthesize "Lastname, First"
    if rnd.random() < 0.75:
        return rnd.choice(SAMPLE_NAMES)
    # synthesize
    firsts = ["John","Luis","Marco","Diego","Harvy","Jul","Pedro","Jaro","Sam","Edvard","Laurence","Pius","Bel","Kirvy","Hugo","Miguel","Nico"]
    lasts = ["Garcia","Santos","Reyes","Dela Cruz","Lopez","Fernandez","Aningalan","Digal","Barro","Escodo","Perez","Reyes"]
    return f"{rnd.choice(lasts)}, {rnd.choice(firsts)}"

def rand_minutes(rnd):
    # College-level: players play ~10-15 matches/season; minutes mean ~900 (12 * 75)
    minutes = rnd.gauss(900, 250)  # mean 900, std 250
    minutes = max(0.0, minutes)
    # clamp to maximum of 15 matches * 90 = 1350
    minutes = min(minutes, 1350.0)
    return round(minutes, 2)

def pct01(rnd, low=0, high=100):
    return round(rnd.uniform(low, high), 2)

def int_by_mean(rnd, mean, allow_zero=False):
    # approximate integer around mean
    if mean <= 0:
        return 0
    val = max(0, int(round(rnd.gauss(mean, max(1, mean*0.55)))))
    if not allow_zero and val == 0:
        if rnd.random() < 0.6:
            return max(1, val + int(abs(rnd.gauss(0, mean*0.25))))
    return val

def generate_row(rnd):
    row = {}
    # baseline categorical fields
    row['player_name'] = rand_name(rnd)
    pos = rnd.choice(POSITIONS)
    row['position'] = pos
    minutes = rand_minutes(rnd)
    row['minutes_played'] = round(minutes,2)
    row['status'] = rnd.choices(STATUSES, weights=[0.6,0.32,0.08])[0]
    row['team'] = rnd.choice(TEAMS)
    row['number'] = rnd.randint(1,99)

    # DPR breakdowns 0-100 but college players less extreme
    row['dpr'] = round(pct01(rnd, 25, 75),2)
    for c in HEADER:
        if c.startswith("dpr_breakdown__"):
            row[c] = round(pct01(rnd, 20, 80),2)

    def set_if_present(col, val):
        if col in HEADER:
            row[col] = val

    # --- PASSES ---
    # passes roughly proportional to minutes but lower intensity than pro
    # per-minute multipliers tuned for college (per90 around 30-55 depending on pos)
    base_pass_rate_min = 0.45  # passes per minute baseline -> 40.5 per90
    if pos == "GK":
        base_pass_rate_min = 0.24
    elif pos in ['CAM','CM','CDM','LWB','RWB','LB','RB']:
        base_pass_rate_min = 0.6
    elif pos in ['ST','RW','LW','CF']:
        base_pass_rate_min = 0.38

    passes_raw = int_by_mean(rnd, mean=round(minutes * base_pass_rate_min))
    set_if_present("raw_stats__distribution__passes", passes_raw)

    successful_passes_raw = int(min(passes_raw, int_by_mean(rnd, passes_raw * (0.72 + rnd.gauss(0,0.06)), allow_zero=True)))
    unsuccessful_passes_raw = max(0, passes_raw - successful_passes_raw)
    set_if_present("raw_stats__distribution__successful_passes", successful_passes_raw)
    set_if_present("raw_stats__distribution__unsuccessful_passes", unsuccessful_passes_raw)
    set_if_present("raw_stats__distribution__passing_accuracy_pct", round(100 * (successful_passes_raw / passes_raw) if passes_raw>0 else pct01(rnd,60,85), 2) if passes_raw>0 else pct01(rnd,60,85))

    key_passes = int_by_mean(rnd, mean=round(passes_raw*0.018))  # fewer key passes at college
    assists_raw = int_by_mean(rnd, mean=round(key_passes*0.12))
    set_if_present("raw_stats__distribution__key_passes", key_passes)
    set_if_present("raw_stats__distribution__key_pass_rate_pct", round(100 * (key_passes / passes_raw) if passes_raw>0 else pct01(rnd,0,5),2))
    set_if_present("raw_stats__distribution__assists", assists_raw)
    set_if_present("raw_stats__distribution__avg_pass_distance", round(pct01(rnd, 4, 30),2))
    set_if_present("raw_stats__distribution__progressive_passes", int_by_mean(rnd, mean=round(passes_raw*0.04)))

    # --- ATTACK ---
    # lower shot frequency per90 for college; per-minute multiplier tuned accordingly
    shot_rate_min = 0.012  # ~1.08 shots/90 baseline
    if pos in ['ST','CF','RW','LW']:
        shot_rate_min = 0.028  # ~2.52 shots/90 for attackers
    shots = int_by_mean(rnd, mean=round(minutes * shot_rate_min))
    set_if_present("raw_stats__attack__shots", shots)
    shots_on_target = min(shots, int_by_mean(rnd, mean=round(shots * (0.30 + rnd.gauss(0,0.08)))))
    set_if_present("raw_stats__attack__shots_on_target", shots_on_target)
    goals = min(shots, int_by_mean(rnd, mean=round(shots * (0.06 + rnd.gauss(0,0.03)))))
    set_if_present("raw_stats__attack__goals", goals)
    set_if_present("raw_stats__attack__shot_accuracy_pct", round(100*(shots_on_target/shots) if shots>0 else pct01(rnd,20,50),2) if shots>0 else pct01(rnd,20,50))
    set_if_present("raw_stats__attack__goal_conversion_pct", round(100*(goals/shots) if shots>0 else pct01(rnd,2,10),2) if shots>0 else pct01(rnd,2,10))

    # --- DRIBBLES ---
    dribble_rate_min = 0.008
    if pos in ['RW','LW','CAM','ST']:
        dribble_rate_min = 0.02
    dribbles = int_by_mean(rnd, mean=round(minutes * dribble_rate_min))
    set_if_present("raw_stats__dribbles__dribbles", dribbles)
    succ_dribbles = min(dribbles, int_by_mean(rnd, mean=round(dribbles * (0.55 + rnd.gauss(0,0.12)))))
    set_if_present("raw_stats__dribbles__successful_dribbles", succ_dribbles)
    set_if_present("raw_stats__dribbles__dribble_success_rate_pct", round(100*(succ_dribbles/dribbles) if dribbles>0 else pct01(rnd,40,80),2) if dribbles>0 else pct01(rnd,40,80))

    # --- DEFENSE ---
    # scale duels/tackles lower than pro
    duel_rate_min = 0.02
    if pos in ['CB','CDM','LB','RB','LWB','RWB']:
        duel_rate_min = 0.04
    duels = int_by_mean(rnd, mean=round(minutes * duel_rate_min))
    duels_won = min(duels, int_by_mean(rnd, mean=round(duels * (0.48 + rnd.gauss(0,0.12)))))
    tackles = int_by_mean(rnd, mean=round(minutes * 0.015))
    succ_tackles = min(tackles, int_by_mean(rnd, mean=round(tackles * (0.55 + rnd.gauss(0,0.12)))))
    interceptions = int_by_mean(rnd, mean=round(minutes * 0.004))
    clearances = int_by_mean(rnd, mean=round(minutes * 0.015))
    recoveries = int_by_mean(rnd, mean=round(minutes * 0.008))
    blocked_shots = int_by_mean(rnd, mean=round(minutes * 0.0008))
    set_if_present("raw_stats__defense__duels", duels)
    set_if_present("raw_stats__defense__duels_won", duels_won)
    set_if_present("raw_stats__defense__duel_success_rate_pct", round(100*(duels_won/duels) if duels>0 else pct01(rnd,30,70),2) if duels>0 else pct01(rnd,30,70))
    set_if_present("raw_stats__defense__tackles", tackles)
    set_if_present("raw_stats__defense__successful_tackles", succ_tackles)
    set_if_present("raw_stats__defense__tackle_success_rate_pct", round(100*(succ_tackles/tackles) if tackles>0 else pct01(rnd,40,80),2) if tackles>0 else pct01(rnd,40,80))
    set_if_present("raw_stats__defense__interceptions", interceptions)
    set_if_present("raw_stats__defense__clearances", clearances)
    set_if_present("raw_stats__defense__recoveries", recoveries)
    set_if_present("raw_stats__defense__blocked_shots", blocked_shots)

    # --- DISCIPLINE ---
    fouls = int_by_mean(rnd, mean=round(minutes*0.0018))
    yellow = 0 if minutes < 30 else int_by_mean(rnd, mean=max(0.2, round(minutes/700)))
    red = 1 if rnd.random() < 0.008 else 0
    set_if_present("raw_stats__discipline__fouls_conceded", fouls)
    set_if_present("raw_stats__discipline__yellow_cards", yellow)
    set_if_present("raw_stats__discipline__red_cards", red)

    # --- POSSESSION ---
    possession_seconds = int(round(minutes * 60 * rnd.uniform(0.03, 0.36)))
    set_if_present("raw_stats__possession__your_possession_time_seconds", possession_seconds)
    set_if_present("raw_stats__possession__possession_pct", round(100*(possession_seconds/(minutes*60)) if minutes>0 else pct01(rnd,30,60),2) if minutes>0 else pct01(rnd,30,60))

    # --- GOALKEEPER (college) ---
    if pos == "GK":
        # shots faced per minute lowered for college -> ~2.8-3.4 sot/90 baseline
        shots_faced = int_by_mean(rnd, mean=round(minutes * 0.033))  # 0.033 per min -> ~2.97 per90
        # save chance slightly wider; college keepers average 58-78% typically
        save_rate = clamp = None  # shadow var prevent lint noise; we'll compute
        save_rate = max(0.45, min(0.82, rnd.gauss(0.64, 0.08)))
        saves = min(shots_faced, int_by_mean(rnd, mean=round(shots_faced * save_rate)))
        goals_against = max(0, shots_faced - saves)
        save_pct = round(100*(saves/shots_faced) if shots_faced>0 else pct01(rnd,50,75),2) if shots_faced>0 else pct01(rnd,50,75)
        set_if_present("raw_stats__goalkeeper__shots_faced", shots_faced)
        set_if_present("raw_stats__goalkeeper__saves", saves)
        set_if_present("raw_stats__goalkeeper__goals_conceded", goals_against)
        set_if_present("raw_stats__goalkeeper__save_pct", save_pct)
        set_if_present("raw_stats__goalkeeper__shots_on_target_against", shots_faced)
        set_if_present("raw_stats__goalkeeper__goals_against", goals_against)
    else:
        for c in ["raw_stats__goalkeeper__shots_faced","raw_stats__goalkeeper__saves","raw_stats__goalkeeper__goals_conceded","raw_stats__goalkeeper__save_pct","raw_stats__goalkeeper__shots_on_target_against","raw_stats__goalkeeper__goals_against"]:
            if c in HEADER:
                row[c] = 0

    # fill *_raw mapping pairs (keeps some backward compatibility)
    mapping_pairs = [
        ("shots_raw", "raw_stats__attack__shots"),
        ("shots_on_target_raw", "raw_stats__attack__shots_on_target"),
        ("goals_raw", "raw_stats__attack__goals"),
        ("passes_raw", "raw_stats__distribution__passes"),
        ("successful_passes_raw", "raw_stats__distribution__successful_passes"),
        ("unsuccessful_passes_raw", "raw_stats__distribution__unsuccessful_passes"),
        ("key_passes_raw", "raw_stats__distribution__key_passes"),
        ("assists_raw", "raw_stats__distribution__assists"),
        ("dribbles_raw", "raw_stats__dribbles__dribbles"),
        ("successful_dribbles_raw", "raw_stats__dribbles__successful_dribbles"),
        ("duels_raw", "raw_stats__defense__duels"),
        ("duels_won_raw", "raw_stats__defense__duels_won"),
        ("tackles_raw", "raw_stats__defense__tackles"),
        ("successful_tackles_raw", "raw_stats__defense__successful_tackles"),
        ("interceptions_raw", "raw_stats__defense__interceptions"),
        ("clearances_raw", "raw_stats__defense__clearances"),
        ("recoveries_raw", "raw_stats__defense__recoveries"),
        ("blocked_shots_raw", "raw_stats__defense__blocked_shots"),
    ]
    for raw_name, source in mapping_pairs:
        if raw_name in HEADER:
            row[raw_name] = row.get(source, 0)

    # small helper: set note/position_history fields if present
    if "note" in HEADER:
        row["note"] = ""
    if "position_history" in HEADER:
        row["position_history"] = "[]"
    if "position_changed" in HEADER:
        row["position_changed"] = 0

    # match info
    if "match_number" in HEADER:
        row["match_number"] = random.randint(1, 20)  # college seasons shorter
    if "match_id" in HEADER:
        row["match_id"] = f"Match_{random.randint(1,400)}"
    if "added_at" in HEADER:
        days_back = random.randint(0, 365)
        ts = datetime.utcnow() - timedelta(days=days_back, seconds=random.randint(0,86400))
        row["added_at"] = ts.isoformat() + "+00:00"

    # compute per-90 fields from raw fields
    for col in HEADER:
        if col in row:
            continue
        if col.endswith("_p90"):
            base = col.replace("_p90","")
            candidate = None
            raw_candidate_1 = base + "_raw"
            raw_candidate_2 = "raw_stats__" + base
            raw_candidate_3 = "raw_stats__attack__" + base
            if raw_candidate_1 in row:
                candidate = raw_candidate_1
            elif raw_candidate_2 in row:
                candidate = raw_candidate_2
            else:
                for h in HEADER:
                    if "raw" in h and base in h:
                        candidate = h
                        break
            raw_val = row.get(candidate, 0) if candidate else 0
            minutes_for_calc = row.get("minutes_played", 0.0)
            if minutes_for_calc and minutes_for_calc > 0:
                p90_val = raw_val * 90.0 / minutes_for_calc
            else:
                p90_val = 0.0
            if "pct" in col or "rate" in col or "success" in col:
                if raw_val == 0:
                    row[col] = round(pct01(random, 30, 80), 2)
                else:
                    row[col] = round(min(100.0, p90_val), 2)
            else:
                row[col] = round(p90_val, 3)

    # Fill any remaining headers with sensible defaults
    for col in HEADER:
        if col not in row:
            lc = col.lower()
            if any(x in lc for x in ["pct","rate","success","accuracy"]):
                row[col] = round(pct01(random, 20, 90), 2)
            elif any(x in lc for x in ["shots","goals","passes","dribbles","duels","tackles","interceptions","clearances","recoveries","saves","fouls","assists","blocked"]):
                row[col] = int_by_mean(random, mean=3)
            elif "match_id" in lc:
                row[col] = f"Match_{random.randint(1,400)}"
            else:
                row[col] = round(random.uniform(0,5),2)

    # ensure passes accuracy consistency
    if "raw_stats__distribution__passes" in row:
        passes = int(row["raw_stats__distribution__passes"])
        sp = int(row.get("raw_stats__distribution__successful_passes", 0))
        up = max(0, passes - sp)
        row["raw_stats__distribution__unsuccessful_passes"] = up
        row["raw_stats__distribution__passing_accuracy_pct"] = round(100*(sp/passes) if passes>0 else pct01(random,60,85),2)

    return row

def main():
    parser = argparse.ArgumentParser(description="Generate college-level synthetic player rows.")
    parser.add_argument("--rows", "-n", type=int, default=100, help="Number of rows to generate")
    parser.add_argument("--out", "-o", type=str, default="synthetic_college_players.csv", help="Output CSV filename")
    parser.add_argument("--seed", type=int, default=None, help="Random seed (optional)")
    args = parser.parse_args()

    rnd = random.Random(args.seed)

    with open(args.out, "w", newline='', encoding='utf-8') as f:
        writer = csv.DictWriter(f, fieldnames=HEADER, extrasaction='ignore')
        writer.writeheader()
        for i in range(args.rows):
            row = generate_row(rnd)
            writer.writerow({k: row.get(k, "") for k in HEADER})

    print(f"Generated {args.rows} college-level rows -> {args.out}")

if __name__ == "__main__":
    main()
