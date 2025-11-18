#!/usr/bin/env python3
"""
regression_model.py

Advanced simulation + aggregation + training.

Automatically tracks synthetic matches via 'is_synthetic' column.
"""

import json
import os
import pickle
import sys
from datetime import datetime, timezone
import numpy as np
import pandas as pd

from sklearn.model_selection import train_test_split, cross_val_score
from sklearn.linear_model import LinearRegression
from sklearn.metrics import r2_score

# Optional XGBoost
try:
    from xgboost import XGBRegressor
    XGBOOST_AVAILABLE = True
except Exception:
    XGBOOST_AVAILABLE = False

# Command line argument handling
if len(sys.argv) > 1:
    if sys.argv[1] == "--dataset" and len(sys.argv) > 2:
        MATCH_NAME = sys.argv[2]
    else:
        MATCH_NAME = sys.argv[1]
else:
    MATCH_NAME = "sbu_vs_2worlds"

# ---------------- CONFIG ----------------
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

team_name_safe = TEAM_NAME.lower().replace(" ", "_")
DATA_PATH = f"output/matches/{MATCH_NAME}/{team_name_safe}_team_derived_metrics.json"
OUTPUT_MODEL_DIR = "python_scripts/models_team"
OUTPUT_TRAIN_DATA = "data/training_data/team_training_data.csv"  # Unified training data
OUTPUT_RESULTS = f"output/matches/{MATCH_NAME}/team_model_results.json"

# Simulation controls
MIN_REAL_MATCHES = 5
SIMULATED_MATCHES_TO_ADD = 50
SIM_VARIATION_SCALE = 0.20

# Training controls
TEST_SIZE = 0.2
CROSS_VALIDATE = True
CV_FOLDS = 4
MIN_TRAIN_FOR_SPLIT = 6

os.makedirs(OUTPUT_MODEL_DIR, exist_ok=True)
os.makedirs(os.path.dirname(OUTPUT_TRAIN_DATA), exist_ok=True)
os.makedirs(os.path.dirname(OUTPUT_RESULTS), exist_ok=True)

# ---------------- HELPERS ----------------
def safe_float(x, default=0.0):
    try:
        return float(x)
    except Exception:
        return default

def make_match_id(team, match_number):
    ts = datetime.now().strftime("%Y%m%d%H%M%S")
    return f"{team.replace(' ','_')}_{ts}_{match_number}"

def flatten_match_obj(match_obj):
    r = {}
    for cat, sub in match_obj.items():
        if isinstance(sub, dict):
            for k, v in sub.items():
                r[f"{cat}__{k}"] = v
        else:
            r[cat] = sub
    return r

def synthesize_match_from_template(template, scale=SIM_VARIATION_SCALE):
    out = {}
    defaults = {
        "attack__goals": 1.0,
        "attack__shots": 8.0,
        "attack__shots_on_target": 3.0,
        "attack__blocked_shots": 1.0,
        "attack__shot_creating_actions": 4.0,
        "distribution__passes": 250.0,
        "distribution__successful_passes": 200.0,
        "distribution__passing_accuracy_pct": 80.0,
        "distribution__key_passes": 3.0,
        "defense__tackles": 18.0,
        "defense__successful_tackles": 12.0,
        "defense__clearances": 15.0,
        "defense__interceptions": 8.0,
        "general__duels": 60.0,
        "general__duels_won": 34.0,
        "discipline__fouls_conceded": 10.0,
        "discipline__yellow_cards": 1.0,
        "discipline__red_cards": 0.0,
        "possession__your_possession_time_seconds": 1800.0,
        "possession__possession_pct": 50.0,
        "attack__shots_off_target": 4.0,
        "defense__saves": 3.0
    }

    base = {k: float(v) for k,v in template.items() if isinstance(v, (int,float,np.integer,np.floating))}
    for k,v in defaults.items():
        if k not in base:
            base[k] = float(v)

    for k,b in base.items():
        if b == 0:
            val = np.abs(np.random.normal(loc=0.0, scale=1.0))
        else:
            sd = max(abs(b) * scale, 0.5)
            val = np.random.normal(loc=b, scale=sd)
        if any(token in k for token in ["goals","shots","passes","tackles","clearances","interceptions","duels","saves","key_passes","yellow_cards","red_cards","fouls_conceded","blocked_shots","shots_on_target","shots_off_target"]):
            val = max(0,int(round(val)))
        else:
            if "possession_pct" in k:
                val = float(np.clip(val, 10.0, 90.0))
            else:
                val = float(np.clip(val, 0.0, None))
        out[k] = val

    if out.get("distribution__passes", None) and out.get("distribution__successful_passes", None) is None:
        acc = template.get("distribution__passing_accuracy_pct", defaults["distribution__passing_accuracy_pct"])
        out["distribution__successful_passes"] = int(round(out["distribution__passes"]*(safe_float(acc)/100.0)))
    if "distribution__passing_accuracy_pct" not in out and out.get("distribution__passes",0)>0:
        out["distribution__passing_accuracy_pct"] = round(out.get("distribution__successful_passes",0)/max(out.get("distribution__passes",1),1)*100.0,2)

    atk_score = (out.get("attack__goals",0)*5.0 + out.get("attack__shots_on_target",0)*1.0 + out.get("attack__shot_creating_actions",0)*0.8 + out.get("distribution__key_passes",0)*1.5)
    atk_score *= 2.2
    def_score = (out.get("defense__tackles",0)*0.6 + out.get("defense__clearances",0)*0.4 + out.get("defense__interceptions",0)*0.9 + out.get("defense__saves",0)*1.8)
    def_score *= 1.4
    dist_score = out.get("distribution__passing_accuracy_pct", 70.0)
    disc_pen = out.get("discipline__fouls_conceded",0)*0.4 + out.get("discipline__yellow_cards",0)*1.2 + out.get("discipline__red_cards",0)*3.0
    gen_score = out.get("general__duels_won",0)/max(out.get("general__duels",1),1)*100.0

    match_rating_attack = np.clip(50 + atk_score/(max(1.0,np.mean(list(base.values())))/3.0)+np.random.normal(0,5),30,95)
    match_rating_defense = np.clip(50 + def_score/(max(1.0,np.mean(list(base.values())))/4.0)+np.random.normal(0,5),30,95)
    match_rating_distribution = np.clip(dist_score + np.random.normal(0,4),30,95)
    match_rating_discipline = np.clip(80-disc_pen + np.random.normal(0,3),20,95)
    match_rating_general = np.clip(50+gen_score/1.5 + np.random.normal(0,4),30,95)
    overall = np.clip(0.25*match_rating_attack + 0.25*match_rating_defense + 0.2*match_rating_distribution + 0.1*match_rating_discipline + 0.2*match_rating_general + np.random.normal(0,3),30,95)

    out["match_rating_attack"] = round(float(match_rating_attack),2)
    out["match_rating_defense"] = round(float(match_rating_defense),2)
    out["match_rating_distribution"] = round(float(match_rating_distribution),2)
    out["match_rating_discipline"] = round(float(match_rating_discipline),2)
    out["match_rating_general"] = round(float(match_rating_general),2)
    out["match_rating_overall"] = round(float(overall),2)

    return out

# ---------------- Synthetic match generator (incremental-safe) ----------------
def generate_synthetic_matches(df_existing, flattened_real):
    if df_existing.empty:
        next_match_number = 1
    else:
        real_matches_csv = df_existing[df_existing["is_synthetic"]==0]
        if not real_matches_csv.empty:
            next_match_number = real_matches_csv["match_number"].max() + 1
        else:
            next_match_number = 1

    latest_real = flattened_real[-1]
    latest_real_numeric = {k: safe_float(v) for k,v in latest_real.items()}

    synthetic_rows = []
    for _ in range(SIMULATED_MATCHES_TO_ADD):
        synth = synthesize_match_from_template(latest_real_numeric)
        synth["added_at"] = datetime.now(timezone.utc).isoformat()
        synth["team_name"] = TEAM_NAME
        synth["match_number"] = next_match_number
        synth["match_id"] = make_match_id(TEAM_NAME,next_match_number)
        synth["is_synthetic"] = 1
        synthetic_rows.append(synth)
        next_match_number += 1

    return synthetic_rows

# ---------------- MAIN ----------------
def main():
    if not os.path.exists(DATA_PATH):
        raise SystemExit(f"Input JSON not found at {DATA_PATH}")

    with open(DATA_PATH,"r",encoding="utf-8") as f:
        raw = json.load(f)

    # Extract real matches
    if TEAM_NAME in raw and isinstance(raw[TEAM_NAME], dict):
        match_obj_raw = raw[TEAM_NAME]
        real_matches = [match_obj_raw]
    elif "matches" in raw and isinstance(raw["matches"], list):
        real_matches = []
        for m in raw["matches"]:
            if isinstance(m, dict) and TEAM_NAME in m and isinstance(m[TEAM_NAME], dict):
                real_matches.append(m[TEAM_NAME])
            elif isinstance(m, dict) and m.get("team_name","").lower()==TEAM_NAME.lower():
                real_matches.append(m)
        if not real_matches:
            real_matches = raw["matches"]
    else:
        raise SystemExit("Unexpected JSON structure")

    # Load existing CSV
    if os.path.exists(OUTPUT_TRAIN_DATA):
        df_existing = pd.read_csv(OUTPUT_TRAIN_DATA)
        if "is_synthetic" in df_existing.columns:
            n_real_csv = len(df_existing[df_existing["is_synthetic"]==0])
        else:
            df_existing["is_synthetic"] = 0
            n_real_csv = len(df_existing)
    else:
        df_existing = pd.DataFrame()
        n_real_csv = 0

    n_real_current = 1
    n_real_total = n_real_csv + n_real_current

    print(f"Real matches in CSV: {n_real_csv}")
    print(f"Including current match: {n_real_total}")

    use_simulation = n_real_total < MIN_REAL_MATCHES

    flattened_real = [flatten_match_obj(m) for m in real_matches]
    appended_rows = []

    # Append new real matches
    next_match_number = int(df_existing["match_number"].max())+1 if not df_existing.empty else 1
    for real in flattened_real:
        row = real.copy()
        row["added_at"] = datetime.now(timezone.utc).isoformat()
        row["team_name"] = TEAM_NAME
        row["match_number"] = next_match_number
        row["match_id"] = make_match_id(TEAM_NAME,next_match_number)
        row["is_synthetic"] = 0
        appended_rows.append(row)
        next_match_number += 1

    # Generate synthetic matches
    print(f"Generating {SIMULATED_MATCHES_TO_ADD} synthetic matches based on latest real match...")
    synthetic_rows = generate_synthetic_matches(df_existing, flattened_real)
    appended_rows.extend(synthetic_rows)

    # Aggregate
    df_new = pd.DataFrame(appended_rows)
    if not df_existing.empty:
        df_all = pd.concat([df_existing, df_new], ignore_index=True, sort=False)
    else:
        df_all = df_new

    df_all.to_csv(OUTPUT_TRAIN_DATA,index=False)
    print(f"Aggregated dataset saved: {OUTPUT_TRAIN_DATA} (total samples: {len(df_all)})")

    # -------------- Prepare features & targets --------------
    targets_map = {
        "general": "match_rating_general",
        "attack": "match_rating_attack", 
        "defense": "match_rating_defense",
        "distribution": "match_rating_distribution",
        "discipline": "match_rating_discipline",
        "overall": "overall_rating"  # This is the correct key name from JSON
    }

    for col in targets_map.values():
        if col not in df_all.columns:
            df_all[col] = 0.0

    exclude_cols = set(list(targets_map.values()) + ["match_id","match_number","added_at","team_name","is_synthetic","overall_rating"])
    features = [c for c in df_all.columns if c not in exclude_cols]
    X = df_all[features].apply(pd.to_numeric,errors="coerce").fillna(0.0)
    y_dict = {cat: pd.to_numeric(df_all[col],errors="coerce").fillna(0.0) for cat,col in targets_map.items()}

    n_samples = len(X)
    print(f"Features prepared ({len(features)} features). Samples: {n_samples}")

    # -------------- Training --------------
    results = {"team":TEAM_NAME,"timestamp":datetime.now(timezone.utc).isoformat(),"total_samples":n_samples,"simulate_used":True,"models":{}}

    for cat,y in y_dict.items():
        print(f"\nPreparing model for '{cat}' (N={n_samples})")
        safe_X = X.copy()
        safe_y = y.copy()

        if XGBOOST_AVAILABLE:
            model = XGBRegressor(n_estimators=150,learning_rate=0.08,max_depth=5,subsample=0.9,colsample_bytree=0.9,random_state=42,verbosity=0)
        else:
            model = LinearRegression()

        # Use persistent model filename (not match-specific)
        model_path = os.path.join(OUTPUT_MODEL_DIR, f"team_{cat}.pkl")
        r2_test = None
        cv_mean = None

        if n_samples >= MIN_TRAIN_FOR_SPLIT:
            try:
                X_train,X_test,y_train,y_test = train_test_split(safe_X,safe_y,test_size=TEST_SIZE,random_state=42)
            except Exception:
                X_train,X_test,y_train,y_test = safe_X,None,safe_y,None

            model.fit(X_train,y_train)

            if X_test is not None and len(X_test)>0:
                try:
                    y_pred = model.predict(X_test)
                    r2_test = float(round(r2_score(y_test,y_pred),4))
                except Exception:
                    r2_test = None
            else:
                r2_test = None

            if CROSS_VALIDATE and len(X_train)>=CV_FOLDS:
                try:
                    cv_scores = cross_val_score(model,X_train,y_train,cv=CV_FOLDS,scoring="r2")
                    cv_mean = float(round(np.nanmean(cv_scores),4))
                except Exception:
                    cv_mean = None
        else:
            model.fit(safe_X,safe_y)
            r2_test = None
            if CROSS_VALIDATE and n_samples>=CV_FOLDS:
                try:
                    cv_scores = cross_val_score(model,safe_X,safe_y,cv=CV_FOLDS,scoring="r2")
                    cv_mean = float(round(np.nanmean(cv_scores),4))
                except Exception:
                    cv_mean = None

        try:
            with open(model_path,"wb") as fh:
                pickle.dump(model,fh)
            saved = True
        except Exception as e:
            print(f"Failed to save model for {cat}: {e}")
            saved = False

        results["models"][cat] = {"model_path":model_path if saved else None,"R2_test":r2_test,"R2_CV_mean":cv_mean,"trained_on_samples":n_samples}
        print(f"Model '{cat}' trained. R2_test={r2_test}, R2_CV_mean={cv_mean}, saved={saved}")

    with open(OUTPUT_RESULTS,"w",encoding="utf-8") as f:
        json.dump(results,f,indent=2)

    print("\nTraining complete — results saved to:",OUTPUT_RESULTS)
    print("Summary:")
    print(json.dumps(results,indent=2))

if __name__=="__main__":
    main()