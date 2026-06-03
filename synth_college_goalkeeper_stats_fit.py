#!/usr/bin/env python3
"""
synth_college_goalkeeper_stats_fit.py

Generate realistic college-level goalkeeper season rows (Manila / Philippine collegiate).

This variant can FIT a small set of real example rows (CSV) and then generate synthetic rows
whose numeric stats are sampled from distributions inferred from those examples (means/stddevs).
Consistency rules:
 - saves + goals_against == shots_on_target_against (by construction)
 - per-90 fields are derived from totals and time
 - save_pct computed when shots_on_target_against > 0

Columns (CSV header used for examples and output):
player_name,team_title,games,time,shots_on_target_against,goals_against,xG_against,
post_shot_xG,saves,penalty_saved,saves_per90,shots_on_target_against_per90,
goals_against_per90,save_pct,season,league

Usage:
    python synth_college_goalkeeper_stats_fit.py --rows 50 --out college_gk.csv --seed 42
    python synth_college_goalkeeper_stats_fit.py --rows 200 --out out.csv --seed 42 --examples-file examples.csv
"""

import csv
import random
import argparse
from datetime import datetime, timedelta
import math
import statistics
import sys

DEFAULT_PLAYERS = [
    "Mapula, Paul","Aningalan, Jul","Barro, Harvy","Digal, Diegocarl","Encaya, Pius",
    "Escodo, Bel","Fernandez, Laurence","Libarnes, Jaro","Omitade, Edvard","Salingay, Sam",
    "Tabelin, Kirvy","Tagara, Pedro","Acebedo, James","Bacaycay, Kurt","Bacnis, Hugo",
    "Perez, Angelo","Reyes, Mark","Cruz, Jan","Lopez, Ryan","Santos, Luis"
]

DEFAULT_TEAMS = [
    "San Beda University","Ateneo de Manila University","De La Salle University",
    "University of the Philippines","Far Eastern University","University of Santo Tomas",
    "Adamson University","National University","Mapúa University","Lyceum"
]

DEFAULT_LEAGUES = ["Philippine College","NCAA","UAAP","Manila Collegiate League"]
DEFAULT_SEASONS = ["2023","2024","2025"]

OUT_HEADER = [
    "player_name","team_title","games","time","shots_on_target_against","goals_against",
    "xG_against","post_shot_xG","saves","penalty_saved","saves_per90",
    "shots_on_target_against_per90","goals_against_per90","save_pct","season","league"
]

NUMERIC_FIELDS = [
    "games","time","shots_on_target_against","goals_against","xG_against","post_shot_xG","saves","penalty_saved"
]

def clamp(x, lo, hi):
    return max(lo, min(hi, x))

def safe_mean_std(values):
    """Return (mean, std) with guard for small samples."""
    if not values:
        return (0.0, 0.0)
    if len(values) == 1:
        return (values[0], max(1.0, abs(values[0]) * 0.1))
    mean = statistics.mean(values)
    std = statistics.pstdev(values)
    # guard tiny std
    std = max(std, max(1.0, abs(mean) * 0.05))
    return (mean, std)

def read_examples_csv(path):
    """Read examples CSV and extract numeric columns if available.
    Accepts header that includes at least the numeric field names used here.
    Returns list of row dicts (strings) and a numeric-values dict-of-lists.
    """
    rows = []
    numeric_data = {f: [] for f in NUMERIC_FIELDS}
    header = None
    try:
        with open(path, newline='', encoding='utf-8') as f:
            reader = csv.DictReader(f)
            header = reader.fieldnames
            if not header:
                raise ValueError("examples file has no header")
            for r in reader:
                rows.append(r)
                # parse numeric fields if present
                for f in NUMERIC_FIELDS:
                    if f in r and r[f] != "":
                        try:
                            numeric_data[f].append(float(r[f]))
                        except:
                            # try to strip non-numeric and parse
                            try:
                                val = ''.join(ch for ch in r[f] if (ch.isdigit() or ch in ".-"))
                                numeric_data[f].append(float(val))
                            except:
                                pass
    except Exception as e:
        raise
    return rows, numeric_data

def infer_profile_from_examples(example_numeric):
    """Compute mean/std for each numeric field from example numeric lists.
    Return dict mapping field -> (mean,std)
    """
    stats = {}
    for f in NUMERIC_FIELDS:
        vals = example_numeric.get(f, [])
        mean, std = safe_mean_std(vals)
        stats[f] = (mean, std)
    # Derive sensible derived ratios if available
    # e.g., if we have shots_on_target_against and time we can compute per90 mean
    # but generation samples totals and recomputes per90, so we just return totals stats.
    return stats

def default_college_profile():
    """Return a default profile tuned to Manila-college keepers (if no examples provided)."""
    # sensible defaults (totals)
    # games: typical college full-time keeper ~10-15 games, we'll set mean 12
    profile = {
        "games": (12.0, 2.5),
        # time in seconds: games * minutes_per_game * 60; minutes per game ~82
        "time": (12.0 * 82.0 * 60.0, 82.0 * 60.0 * 3.0),
        # shots_on_target_against total across season
        # per90 sot ~1.6-2.2 -> for 12 games -> ~1.9 * 12 ≈ 23 (but many of your examples look larger; we'll allow wide std)
        "shots_on_target_against": (28.0, 18.0),
        # goals against total (shots_on_target * (1 - save_pct))
        "goals_against": (8.0, 6.0),
        # xG total
        "xG_against": (6.0, 4.0),
        "post_shot_xG": (6.5, 4.0),
        # saves: shots_on_target - goals_against (but we can sample loosely)
        "saves": (20.0, 12.0),
        # penalties saved
        "penalty_saved": (0.05, 0.2)
    }
    return profile

def sample_int_from_stats(mean, std, rnd, minimum=0):
    """Sample integer given mean/std using gaussian but ensure >= minimum"""
    val = int(round(rnd.gauss(mean, std)))
    return max(minimum, val)

def generate_row_from_profile(rnd, profile, player_name=None, team=None, season=None, league=None):
    # sample totals
    games = sample_int_from_stats(*profile["games"], rnd, minimum=0)
    # ensure at least 1 game if we want to simulate starters; but we allow 0 too
    minutes_per_game = rnd.gauss(82, 6)
    minutes_per_game = clamp(minutes_per_game, 60, 95)
    time_seconds = int(round(games * minutes_per_game * 60.0, 2))

    # shots_on_target and goals and saves — we should ensure saves+goals == shots_on_target
    sot_mean, sot_std = profile["shots_on_target_against"]
    shots_on_target = sample_int_from_stats(sot_mean, sot_std, rnd, minimum=0)

    ga_mean, ga_std = profile["goals_against"]
    goals_against = sample_int_from_stats(ga_mean, ga_std, rnd, minimum=0)
    # clamp goals to not exceed shots_on_target
    goals_against = min(goals_against, shots_on_target)

    # saves computed to match
    saves = shots_on_target - goals_against

    # xG & post_shot_xG sampling: ensure plausible relation to shots
    xg_mean, xg_std = profile["xG_against"]
    xG_against = round(max(0.0, rnd.gauss(xg_mean, xg_std)), 3)
    psxg_mean, psxg_std = profile["post_shot_xG"]
    post_shot_xG = round(max(0.0, rnd.gauss(psxg_mean, psxg_std)), 3)

    # penalty_saved sampling
    pen_mean, pen_std = profile["penalty_saved"]
    penalty_saved = max(0, int(round(abs(rnd.gauss(pen_mean, max(pen_std, 0.1))))))

    # compute per-90 fields if time_seconds>0
    minutes_total = time_seconds / 60.0 if time_seconds > 0 else 0.0
    if minutes_total > 0:
        saves_per90 = round(saves * 90.0 / minutes_total, 3)
        sot_per90_calc = round(shots_on_target * 90.0 / minutes_total, 3)
        goals_against_per90 = round(goals_against * 90.0 / minutes_total, 3)
    else:
        saves_per90 = sot_per90_calc = goals_against_per90 = 0.0

    save_pct = round(100.0 * (saves / shots_on_target), 2) if shots_on_target > 0 else 0.0

    # fill names
    player_name = player_name or rnd.choice(DEFAULT_PLAYERS)
    team = team or rnd.choice(DEFAULT_TEAMS)
    season = season or rnd.choice(DEFAULT_SEASONS)
    league = league or rnd.choice(DEFAULT_LEAGUES)

    row = {
        "player_name": player_name,
        "team_title": team,
        "games": games,
        "time": time_seconds,
        "shots_on_target_against": shots_on_target,
        "goals_against": goals_against,
        "xG_against": xG_against,
        "post_shot_xG": post_shot_xG,
        "saves": saves,
        "penalty_saved": penalty_saved,
        "saves_per90": saves_per90,
        "shots_on_target_against_per90": sot_per90_calc,
        "goals_against_per90": goals_against_per90,
        "save_pct": save_pct,
        "season": season,
        "league": league
    }
    return row

def fit_profile_from_examples_numeric_stats(example_numeric):
    """Given numeric lists for fields, produce profile dictionary with (mean,std) pairs for fields we need."""
    # If examples are present, compute mean/std for these totals; otherwise fallback to defaults.
    # We require shots_on_target_against and goals_against ideally.
    # Example numeric dict keys: games, time, shots_on_target_against, goals_against, xG_against, post_shot_xG, saves, penalty_saved
    profile = {}
    # compute for each numeric field
    for f in NUMERIC_FIELDS:
        vals = example_numeric.get(f, [])
        if vals:
            mean, std = safe_mean_std(vals)
        else:
            # fallback defaults for missing fields
            # we'll compute sensible defaults for the fields if no example available
            if f == "games":
                mean, std = 12.0, 2.5
            elif f == "time":
                mean, std = 12.0 * 82.0 * 60.0, 82.0 * 60.0 * 3.0
            elif f == "shots_on_target_against":
                mean, std = 28.0, 18.0
            elif f == "goals_against":
                mean, std = 8.0, 6.0
            elif f == "xG_against":
                mean, std = 6.0, 4.0
            elif f == "post_shot_xG":
                mean, std = 6.5, 4.0
            elif f == "saves":
                mean, std = 20.0, 12.0
            elif f == "penalty_saved":
                mean, std = 0.05, 0.2
            else:
                mean, std = 0.0, 1.0
        profile[f] = (mean, std)
    # to be explicit rename some keys to the profile usage above
    out_profile = {
        "games": profile["games"],
        "time": profile["time"],
        "shots_on_target_against": profile["shots_on_target_against"],
        "goals_against": profile["goals_against"],
        "xG_against": profile["xG_against"],
        "post_shot_xG": profile["post_shot_xG"],
        "saves": profile["saves"],
        "penalty_saved": profile["penalty_saved"]
    }
    return out_profile

def main():
    parser = argparse.ArgumentParser(description="Generate college-level goalkeeper season rows fit to example(s).")
    parser.add_argument("--rows","-n",type=int,default=50,help="How many rows to generate")
    parser.add_argument("--out","-o",type=str,default="college_gk_fit.csv",help="Output CSV filename")
    parser.add_argument("--seed",type=int,default=None,help="Random seed")
    parser.add_argument("--examples-file",type=str,default=None,help="Optional examples CSV file to fit profile from")
    parser.add_argument("--players-file",type=str,default=None,help="Optional players CSV (Name,Team) to sample players from")
    args = parser.parse_args()

    rnd = random.Random(args.seed)

    # load players pool if provided
    players_pool = None
    if args.players_file:
        try:
            pool = []
            with open(args.players_file, newline='', encoding='utf-8') as f:
                r = csv.reader(f)
                for row in r:
                    if not row: continue
                    name = row[0].strip()
                    team = row[1].strip() if len(row) > 1 else None
                    pool.append((name, team))
            if pool:
                players_pool = pool
        except Exception as e:
            print("Warning: couldn't read players-file:", e, file=sys.stderr)
            players_pool = None

    # If examples provided, read and infer profile
    profile = None
    if args.examples_file:
        try:
            example_rows, numeric = read_examples_csv(args.examples_file)
            profile = fit_profile_from_examples_numeric_stats(numeric)
            print(f"Fitted profile from {len(example_rows)} example rows (using fields: {', '.join(NUMERIC_FIELDS)}).")
        except Exception as e:
            print("Warning: failed to read/fit examples file, falling back to defaults:", e, file=sys.stderr)
            profile = default_college_profile()
    else:
        profile = default_college_profile()

    # prepare output
    with open(args.out, "w", newline='', encoding='utf-8') as f:
        writer = csv.DictWriter(f, fieldnames=OUT_HEADER)
        writer.writeheader()
        for i in range(args.rows):
            # sample player/team
            if players_pool:
                name, team = rnd.choice(players_pool)
                if team is None:
                    team = rnd.choice(DEFAULT_TEAMS)
            else:
                name = rnd.choice(DEFAULT_PLAYERS)
                team = rnd.choice(DEFAULT_TEAMS)
            season = rnd.choice(DEFAULT_SEASONS)
            league = rnd.choice(DEFAULT_LEAGUES)

            row = generate_row_from_profile(rnd, profile, player_name=name, team=team, season=season, league=league)
            writer.writerow(row)

    print(f"Generated {args.rows} rows -> {args.out}")

if __name__ == "__main__":
    main()
