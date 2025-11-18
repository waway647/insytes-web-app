#!/usr/bin/env python3
"""
convert_events_to_csv.py - Dynamic JSON to CSV converter for football event data

Usage:
  python convert_events_to_csv.py                                    # Auto-discover and convert all JSON files
  python convert_events_to_csv.py --match-id match_1                 # Convert specific match by ID
  python convert_events_to_csv.py path/to/events.json out.csv        # Direct file conversion
  python convert_events_to_csv.py --check                            # Check status of all datasets
  python convert_events_to_csv.py --from-controller match_1          # Convert from controller output (UI tagging)
"""

import json
import csv
import sys
import os
import argparse
from pathlib import Path
from typing import Dict, Any, List, Optional, Union, Tuple
import math
from datetime import datetime
import glob

# ---------- DYNAMIC CONFIGURATION ----------
class ConversionConfig:
    """Dynamic configuration for JSON to CSV conversion"""
    
    def __init__(self):
        # Base directories
        self.base_dir = Path(__file__).resolve().parent.parent
        self.events_input_dir = self.base_dir / "writable_data" / "events"  # Controller outputs go here
        self.csv_output_dir = self.base_dir / "output_dataset"
        self.config_dir = self.base_dir / "writable_data" / "configs"
        
        # Ensure directories exist
        self.events_input_dir.mkdir(parents=True, exist_ok=True)
        self.csv_output_dir.mkdir(parents=True, exist_ok=True)
        self.config_dir.mkdir(parents=True, exist_ok=True)
        
    def discover_json_files(self) -> List[Dict[str, Any]]:
        """Discover all JSON event files from writable_data/events/"""
        datasets = []
        
        # Scan writable_data/events/ for all JSON files (both controller outputs and manual files)
        if self.events_input_dir.exists():
            json_files = list(self.events_input_dir.glob("*_events.json"))
            for json_file in json_files:
                match_id = json_file.stem.replace("_events", "")
                
                # Determine if this is fresh from controller or existing
                # Check file modification time - if very recent (< 1 hour), likely from controller
                file_age_hours = (datetime.now().timestamp() - json_file.stat().st_mtime) / 3600
                source_type = "controller" if file_age_hours < 1 else "manual"
                
                datasets.append({
                    "match_id": match_id,
                    "json_path": json_file,
                    "csv_path": self.csv_output_dir / f"{match_id}_events.csv",
                    "config_path": self.config_dir / f"config_{match_id}.json",
                    "source": source_type,
                    "last_modified": json_file.stat().st_mtime,
                    "exists_csv": (self.csv_output_dir / f"{match_id}_events.csv").exists(),
                    "file_age_hours": file_age_hours
                })
        
        # Sort by modification time (newest first)
        datasets.sort(key=lambda x: x["last_modified"], reverse=True)
        return datasets
    
    def get_dataset_info(self, match_id: str) -> Optional[Dict[str, Any]]:
        """Get information about a specific dataset"""
        datasets = self.discover_json_files()
        return next((d for d in datasets if d["match_id"] == match_id), None)
    
    def check_conversion_needed(self, dataset: Dict[str, Any]) -> Tuple[bool, str]:
        """Check if conversion is needed and return reason"""
        json_path = dataset["json_path"]
        csv_path = dataset["csv_path"]
        
        if not csv_path.exists():
            return True, "CSV file does not exist"
        
        # Check if JSON is newer than CSV
        json_mtime = json_path.stat().st_mtime
        csv_mtime = csv_path.stat().st_mtime
        
        if json_mtime > csv_mtime:
            return True, "JSON file is newer than CSV"
        
        return False, "CSV is up to date"
    
    def validate_json_structure(self, json_path: Path) -> Tuple[bool, str, Dict[str, Any]]:
        """Validate JSON structure and return data"""
        try:
            with open(json_path, 'r', encoding='utf-8') as f:
                data = json.load(f)
            
            if not isinstance(data, dict):
                return False, "JSON root must be an object", {}
            
            if "events" not in data:
                return False, "Missing 'events' array in JSON", {}
            
            if not isinstance(data["events"], list):
                return False, "'events' must be an array", {}
            
            match_id = data.get("match_id")
            if not match_id:
                return False, "Missing 'match_id' in JSON", {}
            
            return True, f"Valid JSON with {len(data['events'])} events", data
            
        except json.JSONDecodeError as e:
            return False, f"Invalid JSON: {e}", {}
        except Exception as e:
            return False, f"Error reading file: {e}", {}

# Global configuration instance
config = ConversionConfig()
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
    """Convert single JSON file to CSV"""
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

def convert_dataset(dataset: Dict[str, Any], force: bool = False) -> Tuple[bool, str]:
    """Convert a single dataset with validation and status checking"""
    match_id = dataset["match_id"]
    json_path = dataset["json_path"]
    csv_path = dataset["csv_path"]
    source = dataset["source"]
    
    print(f"\n[PROCESSING] {match_id} (from {source})...")
    
    # Validate JSON structure
    is_valid, validation_msg, data = config.validate_json_structure(json_path)
    if not is_valid:
        return False, f"[ERROR] JSON validation failed: {validation_msg}"
    
    # Check if conversion is needed
    if not force:
        needs_conversion, reason = config.check_conversion_needed(dataset)
        if not needs_conversion:
            return True, f"[SKIPPED] {reason}"
    
    try:
        # Perform conversion
        convert(json_path, csv_path)
        
        # Verify output
        if csv_path.exists() and csv_path.stat().st_size > 0:
            return True, f"[SUCCESS] Converted {len(data['events'])} events"
        else:
            return False, "[ERROR] Conversion failed: Empty or missing output file"
            
    except Exception as e:
        return False, f"[ERROR] Conversion error: {e}"

def convert_all_datasets(force: bool = False) -> Dict[str, Any]:
    """Auto-discover and convert all JSON datasets"""
    datasets = config.discover_json_files()
    
    if not datasets:
        return {"success": False, "message": "No JSON event files found", "results": []}
    
    print(f"[INFO] Found {len(datasets)} JSON datasets to process")
    
    results = []
    success_count = 0
    skip_count = 0
    error_count = 0
    
    for dataset in datasets:
        success, message = convert_dataset(dataset, force)
        
        result = {
            "match_id": dataset["match_id"],
            "source": dataset["source"],
            "success": success,
            "message": message,
            "json_path": str(dataset["json_path"]),
            "csv_path": str(dataset["csv_path"]),
            "timestamp": datetime.now().isoformat()
        }
        results.append(result)
        
        if success and "Converted" in message:
            success_count += 1
        elif success and "Skipped" in message:
            skip_count += 1
        else:
            error_count += 1
        
        print(f"  {message}")
    
    summary = f"[SUMMARY] {success_count} converted, {skip_count} skipped, {error_count} errors"
    print(f"\n{summary}")
    
    return {
        "success": error_count == 0,
        "message": summary,
        "results": results,
        "stats": {"converted": success_count, "skipped": skip_count, "errors": error_count}
    }

def convert_from_controller(match_id: str) -> Tuple[bool, str]:
    """Convert specific dataset from controller output (UI tagging)"""
    # Look in the standard events directory where controller saves files
    events_json = config.events_input_dir / f"{match_id}_events.json"
    
    if not events_json.exists():
        return False, f"[ERROR] Events JSON not found: {events_json}"
    
    dataset = {
        "match_id": match_id,
        "json_path": events_json,
        "csv_path": config.csv_output_dir / f"{match_id}_events.csv",
        "config_path": config.config_dir / f"config_{match_id}.json",
        "source": "controller",
        "last_modified": events_json.stat().st_mtime,
        "exists_csv": (config.csv_output_dir / f"{match_id}_events.csv").exists()
    }
    
    return convert_dataset(dataset, force=True)  # Always force conversion from controller

def check_all_datasets() -> None:
    """Check status of all datasets without converting"""
    datasets = config.discover_json_files()
    
    if not datasets:
        print("📂 No JSON event files found")
        return
    
    print(f"📋 Dataset Status Report ({len(datasets)} datasets found)\n")
    
    for dataset in datasets:
        match_id = dataset["match_id"]
        source = dataset["source"]
        json_path = dataset["json_path"]
        csv_path = dataset["csv_path"]
        config_path = dataset["config_path"]
        
        # Validate JSON
        is_valid, validation_msg, data = config.validate_json_structure(json_path)
        
        # Check conversion status
        needs_conversion, conv_reason = config.check_conversion_needed(dataset)
        
        # Check config file
        has_config = config_path.exists()
        
        status_icon = "✅" if is_valid and not needs_conversion and has_config else "⚠️"
        
        print(f"{status_icon} {match_id} (from {source})")
        print(f"   JSON: {json_path} - {validation_msg}")
        print(f"   CSV: {csv_path} - {'Exists' if csv_path.exists() else 'Missing'}")
        print(f"   Config: {config_path} - {'Exists' if has_config else 'Missing'}")
        
        if needs_conversion:
            print(f"   Status: Needs conversion - {conv_reason}")
        else:
            print(f"   Status: Up to date")
        print()

def main():
    """Main function with enhanced argument parsing"""
    parser = argparse.ArgumentParser(
        description="Dynamic JSON to CSV converter for football event data",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  python convert_events_to_csv.py                        # Auto-convert all datasets
  python convert_events_to_csv.py --match-id match_1     # Convert specific match
  python convert_events_to_csv.py --check                # Check status only
  python convert_events_to_csv.py --from-controller match_1  # Convert from UI tagging
  python convert_events_to_csv.py --force                # Force reconversion of all
        """
    )
    
    parser.add_argument("input_file", nargs="?", help="Input JSON file path (legacy mode)")
    parser.add_argument("output_file", nargs="?", help="Output CSV file path (legacy mode)")
    parser.add_argument("--match-id", help="Convert specific match by ID")
    parser.add_argument("--from-controller", help="Convert specific match from controller output")
    parser.add_argument("--check", action="store_true", help="Check status of all datasets")
    parser.add_argument("--force", action="store_true", help="Force reconversion even if up to date")
    parser.add_argument("--list", action="store_true", help="List all discovered datasets")
    
    args = parser.parse_args()
    
    try:
        # Legacy mode: direct file conversion
        if args.input_file and args.output_file:
            input_path = Path(args.input_file)
            output_path = Path(args.output_file)
            
            if not input_path.exists():
                print(f"❌ Input file not found: {input_path}")
                sys.exit(1)
            
            convert(input_path, output_path)
            return
        
        # Check mode
        if args.check:
            check_all_datasets()
            return
        
        # List mode
        if args.list:
            datasets = config.discover_json_files()
            print(f"📋 Discovered {len(datasets)} datasets:")
            for dataset in datasets:
                print(f"  • {dataset['match_id']}: {dataset['json_path'].name} (from {dataset['source']})")
            return
        
        # Controller conversion mode
        if args.from_controller:
            success, message = convert_from_controller(args.from_controller)
            print(message)
            sys.exit(0 if success else 1)
        
        # Specific match mode
        if args.match_id:
            dataset = config.get_dataset_info(args.match_id)
            if not dataset:
                print(f"[ERROR] Dataset '{args.match_id}' not found")
                sys.exit(1)
            
            success, message = convert_dataset(dataset, args.force)
            print(message)
            sys.exit(0 if success else 1)
        
        # Default: convert all datasets
        result = convert_all_datasets(args.force)
        
        if result["success"]:
            print(f"\n[SUCCESS] All conversions completed successfully!")
        else:
            print(f"\n[WARNING] Some conversions had errors. Check output above.")
            sys.exit(1)
            
    except KeyboardInterrupt:
        print("\n[CANCELLED] Conversion cancelled by user")
        sys.exit(1)
    except Exception as e:
        print(f"\n[ERROR] Unexpected error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()