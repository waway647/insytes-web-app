#!/usr/bin/env python3
"""
convert_events.py

Usage:
  python convert_events_to_csv.py             # writes ./events.csv (reads ./events.json)
  python convert_events_to_csv.py path/to/events.json out.csv
"""

import json
import csv
import sys
from pathlib import Path
from typing import Dict, Any, List, Optional, Union
import math

# Helper function to safely convert a value to float, defaulting to None if conversion fails
def safe_float(v: Any) -> Union[float, None]:
    if v is None or v == "":
        return None
    try:
        return float(v)
    except (ValueError, TypeError):
        return None

# Corrected and updated DEFAULT_COLUMNS
DEFAULT_COLUMNS = [
    "id", "match_id", "team", "team_side", "event",
    "player_id", "player_name", "position",
    "match_time_minute", "half_period", "video_timestamp", "duration",
    "in_possession",
    "origin_x", "origin_y", "is_opponent_half", "origin_third", # 'is_opponent_half' used instead of 'origin_half'
    "outcome", "type",
    # pass additional attributes
    "pass_type", "receiver_name", "pass_end_x", "pass_end_y", "pass_end_third", "pass_direction", "is_key_pass",
    # shot additional attributes
    "shot_type", "is_outside_the_box", "blocker_name", "keeper_name",
    # duels additional attribute
    "duel_type", "defender_name",
    # substitutions
    "player_out", "player_out_position", "player_in", "player_in_position",
    "created_at"
]

def calculate_duration(event_list: List[Dict[str, Any]], current_event_id: str) -> Optional[float]:
    # 1. Find the index of the current event
    current_index = next((i for i, ev in enumerate(event_list) if ev.get("id") == current_event_id), -1)

    # 2. Check if there is a previous event
    if current_index <= 0:
        return None # No previous event (it's the first event or not found)
    
    # 3. Retrieve times
    current_event = event_list[current_index]
    previous_event = event_list[current_index - 1]
    
    current_time = current_event.get("match_time_minute")
    previous_time = previous_event.get("match_time_minute")

    # 4. Validate and calculate
    if isinstance(current_time, (int, float)) and isinstance(previous_time, (int, float)):
        return float(current_time - previous_time)
    
    return None

def calculate_in_possession(ev: Dict[str, Any]) -> str:
    """Rule 2: In Possession (true for Pass, Shot, Dribble, otherwise false)."""
    event_name = ev.get("event", "")
    if event_name in ["Pass", "Shot", "Dribble"]:
        return "true"
    return "false"

def calculate_is_opponent_half(team_side: str, origin_x: Union[float, None]) -> str:
    """Rule 3: Is Opponent Half calculation."""
    if origin_x is None:
        return ""
    
    is_opponent_half_val = None
    if team_side == "left":
        # Rule: true if origin_x > 50, false if origin_x < 50.
        is_opponent_half_val = origin_x > 50
    elif team_side == "right":
        # Rule: false if origin_x < 50, true if origin_x > 50.
        is_opponent_half_val = origin_x < 50
        
    return str(is_opponent_half_val).lower() if is_opponent_half_val is not None else ""

def calculate_third(team_side: str, x: Union[float, None]) -> str:
    """Helper for Origin Third (Rule 4) and Pass End Third (Rule 5)."""
    if x is None:
        return ""
        
    if team_side == "left":
        # Rule: Defensive third if x > 60, Middle third if 25 <= x <= 60, Attacking third if x < 25.
        if x < 40:
            return "defensive third"
        elif x >= 40 and x <= 75:
            return "middle third"
        elif x > 75:
            return "attacking third"
    elif team_side == "right":
        # Rule: Defensive third if x < 40, Middle third if 40 <= x <= 75, Attacking third if x > 75.
        if x > 60:
            return "defensive third"
        elif x >= 25 and x <= 60:
            return "middle third"
        elif x < 25:
            return "attacking third"
            
    return ""

def calculate_pass_direction(
    ev: Dict[str, Any],
    origin_x: Union[float, None],
    origin_y: Union[float, None],
    pass_end_x: Union[float, None],
    pass_end_y: Union[float, None]
) -> str:
    """Rule 6: Dynamic Pass Classification Rule. Uses resolved coordinates and R-L pitch system."""
    if ev.get("event") != "Pass":
        return ""

    team_side = ev.get("team_side") # 'left' or 'right' (tactical side)
    
    # Use the already resolved and passed-in coordinates
    if any(c is None for c in [origin_x, origin_y, pass_end_x, pass_end_y]):
        return ""

    # 1. Calculate the raw delta X (positive if moving toward X=100 / left side)
    raw_delta_x = pass_end_x - origin_x
    raw_delta_y = abs(pass_end_y - origin_y)
    
    # 2. Global Coordinate System Adjustment (R-L Pitch)
    # Since 0 is Right and 100 is Left (Right-to-Left pitch system), 
    # a positive raw_delta_x (moving to the left) means moving DOWNFIELD/Forward for the team attacking Left.
    # To normalize movement so "Forward" is always positive, we apply the Tactical Team Side logic directly.

    delta_x_attacking = 0
    
    if team_side == "left":
        # Team on the 'left' side attacks to the right (from X=100 towards X=0).
        # Forward pass is to the RIGHT (X decreases, raw_delta_x is negative).
        # To make "Forward" positive, we must invert raw_delta_x.
        delta_x_attacking = raw_delta_x
    
    elif team_side == "right":
        # Team on the 'right' side attacks to the left (from X=0 towards X=100).
        # Forward pass is to the LEFT (X increases, raw_delta_x is positive).
        # "Forward" is already positive, so we use raw_delta_x directly.
        delta_x_attacking = -raw_delta_x
    
    else:
        # Fallback or unhandled team_side
        return ""

    # Dynamic Threshold (alpha=0.05, Initial Threshold=1)
    alpha = 0.05
    initial_threshold = 1.0
    epsilon_x = initial_threshold + (alpha * raw_delta_y) # Use raw_delta_y (absolute y change)

    if delta_x_attacking > epsilon_x:
        return "Forward Pass"
    elif delta_x_attacking < -epsilon_x:
        return "Back Pass"
    else:
        return "Lateral Pass"

def calculate_is_outside_the_box(team_side: str, origin_x: Union[float, None], origin_y: Union[float, None]) -> str:
    """
    Rule 7: Is Outside The Box calculation. Pitch is Right (0) to Left (100).
    A shot is OUTSIDE THE BOX ('true') if it is NOT inside the opponent's 18-yard box.
    """
    if origin_x is None or origin_y is None:
        return ""
        
    y_bounds = (22.75, 77.25)
    y_in_range = (y_bounds[0] <= origin_y <= y_bounds[1])
    
    is_in_penalty_area = False

    if team_side == "left":
        # Team 'left' attacks Right (towards X=0). Penalty box X-boundary is X <= 17.5.
        # It's 'inside' only if BOTH X and Y are in range.
        is_in_penalty_area = (origin_x >= 82.5) and y_in_range
        
    elif team_side == "right":
        # Team 'right' attacks Left (towards X=100). Penalty box X-boundary is X >= 82.5.
        # It's 'inside' only if BOTH X and Y are in range.
        is_in_penalty_area = (origin_x <= 17.5) and y_in_range

    # The result is 'true' if it is NOT inside the penalty area.
    is_outside_the_box_val = not is_in_penalty_area

    return str(is_outside_the_box_val).lower()

# def assign_assist_to_player()

def get_secondary_player_name(ev: Dict[str, Any]) -> str:
    """Helper for Rule 8: extract secondary player name."""
    additional = ev.get("additional_attributes", {})
    # Check both 'additional_attributes' and the user's snippet which shows 'additional' as a list/dict
    if not additional and ev.get("additional") and isinstance(ev["additional"], dict):
        additional = ev["additional"]
    
    # Access nested key
    return additional.get("secondary_player", {}).get("name", "")

def get_secondary_player_position(ev: Dict[str, Any]) -> str:
    """Helper for Rule 8: extract secondary player name."""
    additional = ev.get("additional_attributes", {})
    # Check both 'additional_attributes' and the user's snippet which shows 'additional' as a list/dict
    if not additional and ev.get("additional") and isinstance(ev["additional"], dict):
        additional = ev["additional"]
    
    # Access nested key
    return additional.get("secondary_player", {}).get("position", "")

def get_goalkeeper_name(ev: Dict[str, Any]) -> str:
    """Helper for Rule 8: extract secondary player name."""
    additional = ev.get("additional_attributes", {})
    # Check both 'additional_attributes' and the user's snippet which shows 'additional' as a list/dict
    if not additional and ev.get("additional") and isinstance(ev["additional"], dict):
        additional = ev["additional"]
    
    # Access nested key
    return additional.get("opponent_goalkeeper", {}).get("name", "")

def flatten_event(ev: Dict[str, Any], event_list: List[Dict[str, Any]]) -> Dict[str, Any]:
    
    # Use 'team_side' if 'side' is missing (based on JSON snippet)
    team_side = ev.get("team_side", "")
    
    # Rule 9: successfully get additional attributes (Run this first to resolve pass end coords)
    additional_attrs = ev.get("additional_attributes", {})
    # Check if 'additional' key is present and is a dictionary
    if not additional_attrs and isinstance(ev.get("additional"), dict):
        additional_attrs = ev["additional"]
    
    # Pre-process coordinate data
    origin_x = safe_float(ev.get("origin_x"))
    origin_y = safe_float(ev.get("origin_y"))
    
    # 🌟 FIX: RESOLVE PASS END COORDINATES robustly before any calculation
    # Check primary key, then additional_attrs
    pass_end_x = (safe_float(ev.get("pass_end_x")) or 
                  safe_float(additional_attrs.get("pass_end_x")))
    pass_end_y = (safe_float(ev.get("pass_end_y")) or 
                  safe_float(additional_attrs.get("pass_end_y")))
    
    # --- Execute all calculation rules ---
    duration_val = calculate_duration(event_list, ev.get("id", "")) # Rule 1
    in_possession_val = calculate_in_possession(ev) # Rule 2
    is_opponent_half_val = calculate_is_opponent_half(team_side, origin_x) # Rule 3
    origin_third_val = calculate_third(team_side, origin_x) # Rule 4
    
    # Rule 5: Calculate Pass End Third using the resolved pass_end_x
    pass_end_third_val = calculate_third(team_side, pass_end_x) 
    
    # Rule 6: Calculate Pass Direction by PASSING the resolved coordinates
    pass_direction_val = calculate_pass_direction(ev, origin_x, origin_y, pass_end_x, pass_end_y)
    
    is_outside_the_box_val = calculate_is_outside_the_box(team_side, origin_x, origin_y) # Rule 7

    secondary_player_name = get_secondary_player_name(ev) # Rule 8 extraction
    secondary_player_position = get_secondary_player_position(ev)

    # NEW: prefer keeper name provided in additional.opponent_goalkeeper if present
    goalkeeper_name = get_goalkeeper_name(ev)

    # Rule 8: Populate player names based on event context
    receiver_name_val = ""
    blocker_name_val = ""
    keeper_name_val = ""
    defender_name_val = ""
    
    event_name = ev.get("event", "")
    outcome_lower = (ev.get("outcome") or "").strip().lower()

    # receiver / blocker / keeper / defender assignment
    if event_name == "Pass":
        receiver_name_val = secondary_player_name

    if event_name == "Shot":
        # blocker is always the secondary player (when provided)
        blocker_name_val = secondary_player_name

        # keeper: only set for non-blocked shot outcomes.
        # Prefer explicit opponent goalkeeper (additional.opponent_goalkeeper.name),
        # otherwise fall back to the secondary player (but not when outcome is 'blocked').
        if outcome_lower != "blocked":
            if goalkeeper_name:
                keeper_name_val = goalkeeper_name
            else:
                keeper_name_val = secondary_player_name or ""
        else:
            # blocked shots: goalkeeper is NOT the blocker — clear keeper_name
            keeper_name_val = ""

    if event_name == "Duel":
        defender_name_val = secondary_player_name

    # ALSO attach keeper_name for Penalty events (prefer explicit opponent_goalkeeper)
    if event_name == "Penalty":
        # for penalty outcomes saved/goal, prefer GK from opponent_goalkeeper if present
        if goalkeeper_name:
            keeper_name_val = goalkeeper_name
        elif secondary_player_name and not keeper_name_val:
            keeper_name_val = secondary_player_name


    # --- Only populate substitution fields when event is a substitution (case-insensitive) ---
    player_out_val = ""
    player_out_position_val = ""
    player_in_val = ""
    player_in_position_val = ""
    if isinstance(event_name, str) and event_name.lower() == "substitution":
        player_out_val = ev.get("player_name", "")
        player_out_position_val = ev.get("player_position", "")
        player_in_val = secondary_player_name
        player_in_position_val = secondary_player_position

    # --- Building the output dictionary ---
    out = {
        "id": ev.get("id",""),
        "match_id": ev.get("match_id",""),
        "team": ev.get("team",""),
        "team_side": ev.get("team_side",""), # Use the original key for output column
        "event": ev.get("event",""),
        "player_id": ev.get("player_id",""),
        "player_name": ev.get("player_name",""),
        "position": ev.get("player_position",""),
        "match_time_minute": ev.get("match_time_minute",""),
        "half_period": ev.get("half_period",""),
        "video_timestamp": ev.get("video_timestamp",""),
        "duration": duration_val, # CALCULATED (Rule 1)
        "in_possession": in_possession_val, # CALCULATED (Rule 2)
        "origin_x": ev.get("origin_x",""),
        "origin_y": ev.get("origin_y",""),
        "is_opponent_half": is_opponent_half_val, # CALCULATED (Rule 3)
        "origin_third": origin_third_val, # CALCULATED (Rule 4)
        "outcome": ev.get("outcome",""),
        "type": ev.get("type",""),
        # for pass
        "pass_type": additional_attrs.get("pass_type",""),
        "receiver_name": receiver_name_val, # CALCULATED (Rule 8)
        "pass_end_x": additional_attrs.get("pass_end_x", ""),
        "pass_end_y": additional_attrs.get("pass_end_y", ""),
        "pass_end_third": pass_end_third_val, # CALCULATED (Rule 5) - uses resolved coordinate
        "pass_direction": pass_direction_val, # CALCULATED (Rule 6) - uses resolved coordinates
        "is_key_pass": additional_attrs.get("key_pass", ""),
        # for shot
        "shot_type": additional_attrs.get("shot_type", ""),
        "is_outside_the_box": is_outside_the_box_val, # CALCULATED (Rule 7)
        "blocker_name": blocker_name_val, # CALCULATED (Rule 8)
        "keeper_name": keeper_name_val, # NOW uses opponent goalkeeper when available
        # for duel
        "duel_type": additional_attrs.get("duel_type", ""),
        "defender_name": defender_name_val, # CALCULATED (Rule 8)
        # substitution (only if event == "substitution")
        "player_out": player_out_val,
        "player_out_position": player_out_position_val,
        "player_in": player_in_val,
        "player_in_position": player_in_position_val,
        "created_at": ev.get("created_at",""),
    }
    
    # include any other additional attributes in a nested dict (Rule 9)
    for k, v in additional_attrs.items():
        if k not in out: # Only include if not already mapped
            out[k] = v
            
    return out

def convert(json_path: Path, out_csv: Path):
    data = json.loads(json_path.read_text(encoding='utf-8'))
    events = data.get("events", [])
    rows = [flatten_event(ev, events) for ev in events]

    with out_csv.open("w", encoding="utf-8", newline="") as fh:
        writer = csv.DictWriter(fh, fieldnames=DEFAULT_COLUMNS, extrasaction="ignore")
        writer.writeheader()
        for r in rows:
            # stringify nested values
            safe_row = {}
            for c in DEFAULT_COLUMNS:
                v = r.get(c,"")
                if isinstance(v, (dict, list)):
                    v = json.dumps(v, ensure_ascii=False)
                safe_row[c]=v if v is not None else ""
            writer.writerow(safe_row)

    print(f"Wrote {len(rows)} rows to: {out_csv}")

if __name__ == "__main__":
    # default input is events.json in same folder as script
    script_dir = Path(__file__).resolve().parent
    default_input = script_dir / "../writable_data/events_vs_siniloan.json"
    default_output = script_dir / "../output_dataset/events_vs_siniloan.csv"

    argv = sys.argv
    input_path = Path(argv[1]) if len(argv) > 1 else default_input
    output_path = Path(argv[2]) if len(argv) > 2 else default_output

    if not input_path.exists():
        # try ../events.json (as you wrote "../events.json")
        alt = script_dir.parent / "events.json"
        if alt.exists():
            input_path = alt
        else:
            print(f"ERROR: input file not found: {input_path}")
            sys.exit(1)

    convert(input_path, output_path)