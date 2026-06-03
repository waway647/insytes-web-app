#!/usr/bin/env python3
"""
clean_goalkeeper_samples.py

Parse messy sample goalkeeper lines and output a cleaned examples.csv suitable
for the synth generator.

Usage:
    python clean_goalkeeper_samples.py --in raw_samples.txt --out examples.csv
If --in is omitted the script uses two embedded sample lines (the ones you pasted).
"""
import csv
import argparse
import re
from datetime import datetime

# Defaults that will be written to each cleaned row if not discoverable
DEFAULT_SEASON = "2025"
DEFAULT_LEAGUE = "Philippine College"
DEFAULT_MINUTES_PER_GAME = 82.0  # used to compute 'time' seconds if not inferable

# Teams list used to find team tokens in the messy rows (expand if needed)
KNOWN_TEAMS = [
    "San Beda University","Ateneo de Manila University","De La Salle University",
    "University of the Philippines","Far Eastern University","University of Santo Tomas",
    "Adamson University","National University","Mapúa University","Lyceum",
    "2 Worlds FC","City United","Metro FC","Riverside FC","Lakeside Athletic"
]

# Target output header
OUT_HEADER = [
    "player_name","team_title","games","time",
    "shots_on_target_against","goals_against","xG_against","post_shot_xG",
    "saves","penalty_saved","season","league"
]

EMBEDDED_LINES = [
    # line 1 (Tagara, Pedro) — exactly as pasted by the user
    '"Tagara, Pedro",GK,95.35,41.5,,,,,starter,San Beda University,15,0.0,0.0,0.0,17.93,17.93,0.0,0.0,0.0,-6.42,0.94,0.0,0.0,0.0,0.0,0.0,1.89,0.0,0.94,0.0,0.0,0.0,0.0,0.0,0.0,100.0,0.0,0.0,166.0,5.59,19.0,19.0,0.0,100.0,0.0,0.0,0.0,-6.8,1.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,2.0,0.0,1.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,17.93,17.93,0.0,0.0,0.0,-6.42,0.94,0.0,0.0,0.0,0.0,0.0,1.89,0.0,0.94,0.0,0.0,0.0,0.0,0.0,0.0,100.0,0.0,0.0,,,,,,,,,,0.6,63.8,56.6,89.6,100.0,10.0,6.0,4.0,60.0,10.0,4.0,5.66,3.78,9.44,9.44,3.78,60.0,5.66,3.78,9.44,0.6,24,Match_24,2025-12-08T18:15:57.300516+00:00,"[\'CF\', \'LWB\', \'RW\', \'CDM\', \'CB\', \'GK\', \'CAM\', \'GK\']",1,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,5.66,3.78,9.44,9.44,3.78,60.0,5.66,3.78,9.44,0.6,0.0,4.0,,,,' ,
    # line 2 (Dilodilo, Austin)
    '"Dilodilo, Austin",GK,91.93,34.6,,,,,starter,San Beda University,1,0.0,0.0,0.0,22.52,14.68,7.83,0.0,0.0,12.57,9.79,0.98,0.98,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,65.22,0.0,100.0,355.0,10.84,23.0,15.0,8.0,65.22,0.0,0.0,0.0,12.84,10.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,1.0,1.0,100.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,22.52,14.68,7.83,0.0,0.0,12.57,9.79,0.98,0.98,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,0.0,65.22,0.0,100.0,,,,,,,,,,0.0,78.3,0.0,100.0,100.0,1.0,0.0,1.0,0.0,1.0,1.0,0.0,0.98,0.98,0.98,0.98,0.0,0.0,0.98,0.98,0.0,22,Match_22,2025-12-08T18:14:44.286346+00:00,[],0,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,0.0,0.98,0.98,0.98,0.98,0.0,0.0,0.98,0.98,0.0,0.0,1.0,,,' 
]

# helpers
def is_float_token(tok):
    """Return True if token looks like a float number (contains a dot and digits)."""
    if tok is None:
        return False
    tok = tok.strip()
    # negative allowed, allow trailing/leading spaces
    return bool(re.match(r"^-?\d+\.\d+$", tok))

def is_int_token(tok):
    if tok is None:
        return False
    tok = tok.strip()
    return bool(re.match(r"^-?\d+$", tok))

def to_float(tok):
    try:
        return float(tok)
    except:
        return None

def to_int(tok):
    try:
        return int(round(float(tok)))
    except:
        return None

def find_match_number_and_games(tokens):
    """Find token 'Match_' and return (games, match_index) if found."""
    for i, t in enumerate(tokens):
        if isinstance(t, str) and t.startswith("Match_"):
            # try to parse previous token as integer games
            if i-1 >= 0 and is_int_token(tokens[i-1]):
                return int(tokens[i-1]), i
            # fallback: search left for nearest int within 4 tokens
            for j in range(max(0, i-4), i):
                if is_int_token(tokens[j]):
                    return int(tokens[j]), i
    return None, None

def find_team(tokens):
    """Try to find a known team string among tokens; otherwise return first long word token after starter/substitute."""
    for t in KNOWN_TEAMS:
        for tok in tokens:
            if isinstance(tok, str) and tok.strip() == t:
                return t
    # fallback heuristic: find a token with two words and a capitalized pattern
    for tok in tokens:
        if isinstance(tok, str) and " " in tok and len(tok) > 6 and tok[0].isupper():
            return tok.strip()
    return ""

def find_minutes_per_game(tokens):
    """Search early tokens for a float that plausibly represents minutes per match (e.g. 80-110)."""
    for tok in tokens[:8]:
        if is_float_token(tok):
            val = float(tok)
            if 50 <= val <= 120:
                return val
    return DEFAULT_MINUTES_PER_GAME

def find_sot_saves_goals_triple(tokens):
    """
    Search for three nearby numeric tokens a,b,c such that a ≈ b + c (integer rounding).
    Return (sot, saves, goals, index_of_a) or (None,...).
    """
    n = len(tokens)
    for i in range(n-2):
        a_tok, b_tok, c_tok = tokens[i], tokens[i+1], tokens[i+2]
        a = to_int(a_tok) if a_tok is not None else None
        b = to_int(b_tok) if b_tok is not None else None
        c = to_int(c_tok) if c_tok is not None else None
        if a is None or b is None or c is None:
            continue
        # restrict plausible ranges
        if not (0 <= a <= 200 and 0 <= b <= 200 and 0 <= c <= 200):
            continue
        # check additive relation
        if a == b + c:
            return a, b, c, i
    # no exact triple found; try loosened tolerance where a ≈ b+c within 1
    for i in range(n-2):
        a_tok, b_tok, c_tok = tokens[i], tokens[i+1], tokens[i+2]
        a = to_int(a_tok) if a_tok is not None else None
        b = to_int(b_tok) if b_tok is not None else None
        c = to_int(c_tok) if c_tok is not None else None
        if a is None or b is None or c is None:
            continue
        if abs(a - (b + c)) <= 1 and 0 <= a <= 200:
            return a, b, c, i
    return None, None, None, None

def find_xg_pair(tokens, anchor_index=None):
    """
    Find two float tokens that plausibly are xG and post-shot xG.
    Strategy:
      - If anchor_index provided, look left of anchor for two decimal floats.
      - Otherwise search for first pair of floats > 0.5 and < 100.
    """
    floats = []
    for i, tok in enumerate(tokens):
        if is_float_token(tok):
            val = float(tok)
            if 0.1 <= val <= 120.0:
                floats.append((i, val))
    if not floats:
        return 0.0, 0.0
    if anchor_index is not None:
        # look for floats left of anchor
        left = [f for f in floats if f[0] < anchor_index]
        if len(left) >= 2:
            # take the last two floats before anchor
            return round(left[-2][1], 3), round(left[-1][1], 3)
        elif len(left) == 1:
            return round(left[-1][1], 3), 0.0
    # fallback: take the first two floats
    if len(floats) >= 2:
        return round(floats[0][1], 3), round(floats[1][1], 3)
    return round(floats[0][1], 3), 0.0

def clean_line_to_record(line):
    """
    Parse a single messy CSV line and return a cleaned dict with OUT_HEADER keys.
    Uses heuristics documented above.
    """
    # parse tokens robustly using csv.reader
    reader = csv.reader([line])
    tokens = next(reader)
    # normalize tokens (strip)
    tokens = [t.strip() for t in tokens]

    # player name: first field (may be quoted)
    player_name = tokens[0].strip('"').strip()

    # team: search known teams
    team = find_team(tokens)

    # find match/games
    games, match_idx = find_match_number_and_games(tokens)
    if games is None:
        # fallback: use an integer token at position 10 if plausible
        if len(tokens) > 10 and is_int_token(tokens[10]):
            games = int(tokens[10])
        else:
            games = 12  # fallback default

    # minutes per game — try to extract from early tokens
    mpg = find_minutes_per_game(tokens)
    time_seconds = round(games * mpg * 60.0, 2)

    # find shots_on_target, saves, goals triple
    sot, saves, goals, triple_idx = find_sot_saves_goals_triple(tokens)

    # if not found, fallback heuristics:
    if sot is None:
        # attempt: locate pattern like "<int>,<int>,<int>" anywhere with values >0
        ints = [(i, to_int(t)) for i, t in enumerate(tokens) if is_int_token(t) or (t.replace('.0','').isdigit())]
        for i in range(len(ints)-2):
            a = ints[i][1]; b = ints[i+1][1]; c = ints[i+2][1]
            if a is None or b is None or c is None: continue
            if 0 <= a <= 200 and 0 <= b <= 200 and 0 <= c <= 200 and a == b + c:
                sot, saves, goals = a, b, c
                triple_idx = ints[i][0]
                break
    if sot is None:
        # last resort: search for numbers that look like 19,23 etc. pick two nearby
        maybe_ints = [to_int(t) for t in tokens if (t.replace('.0','').isdigit())]
        if maybe_ints:
            # choose the largest-ish as sot, then split roughly 60/40 for saves/goals
            maxv = max(maybe_ints)
            sot = int(maxv)
            goals = int(round(sot * 0.3))
            saves = sot - goals

    # find xG and post_shot_xG (prefer floats left of the triple)
    anchor = triple_idx if triple_idx is not None else None
    xg, psxg = find_xg_pair(tokens, anchor_index=anchor)

    # penalty_saved: try to find small integer tokens in row that are 0/1/2 near end
    penalty_saved = 0
    for tok in reversed(tokens[-40:]):
        if is_int_token(tok):
            v = int(tok)
            if 0 <= v <= 2:
                # ignore very large series of zeros at end; pick first small int if not obviously match_count
                penalty_saved = v
                break

    # ensure internal consistency
    if sot is None:
        sot = saves + goals
    if saves + goals != sot:
        # adjust saves preferentially (keep saves >= 0)
        saves = max(0, sot - goals)

    # round numeric fields
    try:
        shots_on_target_against = int(sot)
        goals_against = int(goals)
        saves = int(saves)
    except:
        shots_on_target_against = int(round(float(sot))) if sot else 0
        goals_against = int(round(float(goals))) if goals else 0
        saves = int(round(float(saves))) if saves else 0

    # build cleaned row dict
    row = {
        "player_name": player_name,
        "team_title": team,
        "games": games,
        "time": time_seconds,
        "shots_on_target_against": shots_on_target_against,
        "goals_against": goals_against,
        "xG_against": xg,
        "post_shot_xG": psxg,
        "saves": saves,
        "penalty_saved": penalty_saved,
        "season": DEFAULT_SEASON,
        "league": DEFAULT_LEAGUE
    }
    return row

def process_lines(lines):
    records = []
    for ln in lines:
        ln = ln.strip()
        if not ln:
            continue
        try:
            rec = clean_line_to_record(ln)
            records.append(rec)
        except Exception as e:
            print("Warning: failed to parse line:", ln[:120], "...", e)
    return records

def main():
    p = argparse.ArgumentParser(description="Clean messy goalkeeper sample lines and output examples.csv")
    p.add_argument("--in", dest="infile", help="Input file containing raw sample lines (one per line). If omitted uses embedded samples.", default=None)
    p.add_argument("--out", dest="outfile", help="Output examples CSV filename", default="examples.csv")
    args = p.parse_args()

    if args.infile:
        with open(args.infile, "r", encoding="utf-8") as f:
            raw_lines = [l.rstrip("\n") for l in f if l.strip()]
    else:
        raw_lines = EMBEDDED_LINES

    records = process_lines(raw_lines)

    # Write CSV
    with open(args.outfile, "w", newline='', encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=OUT_HEADER)
        writer.writeheader()
        for r in records:
            writer.writerow(r)

    print(f"Wrote {len(records)} cleaned rows to {args.outfile}")
    # Print sanitized rows to stdout as preview
    for r in records:
        print(r)

if __name__ == "__main__":
    main()
