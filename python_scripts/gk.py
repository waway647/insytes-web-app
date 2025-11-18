import requests
import pandas as pd
import json
import re

# -----------------------------
# CONFIGURATION
# -----------------------------
SEASONS = ["2022", "2023", "2024"]
LEAGUE = "EPL"
OUTPUT_FILE = "data/understat_gk_features.csv"

# -----------------------------
# HELPER: Fetch GK data safely
# -----------------------------
def fetch_understat_data(league, season):
    url = f"https://understat.com/league/{league}/{season}"
    headers = {"User-Agent": "Mozilla/5.0"}
    r = requests.get(url, headers=headers)
    if r.status_code != 200:
        raise Exception(f"Failed to fetch: {r.status_code}")
    
    # Extract JSON inside <script> tag
    match = re.search(r"var playersData\s*=\s*JSON\.parse\('(.+)'\);", r.text)
    if not match:
        raise Exception("Could not find playersData JSON.")
    
    json_str = match.group(1).encode().decode("unicode_escape")
    data = json.loads(json_str)

    # Return only goalkeepers
    gk_data = [p for p in data if p.get("position") == "GK"]
    return gk_data

# -----------------------------
# Build DataFrame with per-90 metrics
# -----------------------------
def build_gk_dataframe(gk_data, season):
    rows = []
    for p in gk_data:
        # Safely get keys with .get()
        minutes = float(p.get("time", 0))
        shots_against = float(p.get("shots_on_target_against", 0))
        saves = float(p.get("saves", 0))
        goals_against = float(p.get("goals_against", 0))
        post_shot_xG = float(p.get("post_shot_xG", 0))
        xG_against = float(p.get("xG_against", 0))
        
        row = {
            "player_name": p.get("player_name"),
            "team_title": p.get("team_title"),
            "games": p.get("games"),
            "time": minutes,
            "shots_on_target_against": shots_against,
            "goals_against": goals_against,
            "xG_against": xG_against,
            "post_shot_xG": post_shot_xG,
            "saves": saves,
            "penalty_saved": p.get("penalty_saved"),
            "saves_per90": (saves / minutes * 90) if minutes > 0 else None,
            "shots_on_target_against_per90": (shots_against / minutes * 90) if minutes > 0 else None,
            "goals_against_per90": (goals_against / minutes * 90) if minutes > 0 else None,
            "save_pct": (saves / shots_against) if shots_against > 0 else None,
            "season": season
        }
        rows.append(row)
    
    return pd.DataFrame(rows)

# -----------------------------
# MAIN
# -----------------------------
if __name__ == "__main__":
    all_dfs = []
    for season in SEASONS:
        print(f"📥 Fetching Understat GK data for {LEAGUE} {season}...")
        try:
            gk_data = fetch_understat_data(LEAGUE, season)
            print(f"✅ Fetched {len(gk_data)} goalkeepers.")
            df = build_gk_dataframe(gk_data, season)
            all_dfs.append(df)
        except Exception as e:
            print(f"⚠️ Skipped season {season} due to error: {e}")
    
    if all_dfs:
        final_df = pd.concat(all_dfs, ignore_index=True)
        final_df.to_csv(OUTPUT_FILE, index=False)
        print(f"💾 Saved CSV → {OUTPUT_FILE}")
        print("🏁 Done!")
    else:
        print("❌ No data fetched for any season.")