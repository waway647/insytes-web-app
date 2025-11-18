#!/usr/bin/env python3
"""
regression_model.py

Advanced simulation + aggregation + training.

Simulation removed — only real matches are processed and appended.
"""

import json
import os
import pickle
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

# ---------------- CONFIG ----------------
DATA_PATH = "python_scripts/sanbeda_team_derived_metrics.json"
OUTPUT_MODEL_DIR = "python_scripts/models"
OUTPUT_TRAIN_DATA = "data/sanbeda_team_training_data.csv"
OUTPUT_RESULTS = "python_scripts/team_model_results.json"

TEAM_NAME = "San Beda"

# Training controls
TEST_SIZE = 0.2
CROSS_VALIDATE = True
CV_FOLDS = 4
MIN_TRAIN_FOR_SPLIT = 6

os.makedirs(OUTPUT_MODEL_DIR, exist_ok=True)
os.makedirs(os.path.dirname(OUTPUT_TRAIN_DATA), exist_ok=True)

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

# ---------------- MAIN ----------------
def main():
    if not os.path.exists(DATA_PATH):
        raise SystemExit(f"❌ Input JSON not found at {DATA_PATH}")

    with open(DATA_PATH,"r",encoding="utf-8") as f:
        raw = json.load(f)

    # Extract matches for TEAM_NAME
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
        raise SystemExit("❌ Unexpected JSON structure")

    # Load existing CSV or create empty
    if os.path.exists(OUTPUT_TRAIN_DATA):
        df_existing = pd.read_csv(OUTPUT_TRAIN_DATA)
    else:
        df_existing = pd.DataFrame()

    # Determine next match number
    next_match_number = int(df_existing["match_number"].max()+1) if not df_existing.empty and "match_number" in df_existing.columns else 1

    # Append real matches
    appended_rows = []
    flattened_real = [flatten_match_obj(m) for m in real_matches]
    for real in flattened_real:
        row = real.copy()
        row["added_at"] = datetime.now(timezone.utc).isoformat()
        row["team_name"] = TEAM_NAME
        row["match_number"] = next_match_number
        row["match_id"] = make_match_id(TEAM_NAME,next_match_number)
        appended_rows.append(row)
        next_match_number += 1

    # Append to existing dataset
    if appended_rows:
        df_new = pd.DataFrame(appended_rows)
        df_all = pd.concat([df_existing, df_new], ignore_index=True, sort=False) if not df_existing.empty else df_new
        df_all.to_csv(OUTPUT_TRAIN_DATA, index=False)
        print("🔄 Appended new match(es) to training dataset.")
    else:
        df_all = df_existing
        print("ℹ️ No new matches appended.")

    print(f"💾 Aggregated dataset saved: {OUTPUT_TRAIN_DATA} (total samples: {len(df_all)})")

    # ---------------- Prepare features & targets ----------------
    targets_map = {
        "general": "match_rating_general",
        "attack": "match_rating_attack",
        "defense": "match_rating_defense",
        "distribution": "match_rating_distribution",
        "discipline": "match_rating_discipline",
        "overall": "match_rating_overall"
    }

    for col in targets_map.values():
        if col not in df_all.columns:
            df_all[col] = 0.0
        rc = f"{col}_rounded"
        if rc not in df_all.columns:
            df_all[rc] = pd.to_numeric(df_all[col],errors="coerce").fillna(0.0).round(0).astype(int)

    exclude_cols = set(list(targets_map.values()) + [f"{t}_rounded" for t in targets_map.values()] + ["match_id","match_number","added_at","team_name"])
    features = [c for c in df_all.columns if c not in exclude_cols]
    X = df_all[features].apply(pd.to_numeric,errors="coerce").fillna(0.0)
    y_dict = {cat: pd.to_numeric(df_all[col],errors="coerce").fillna(0.0) for cat,col in targets_map.items()}

    n_samples = len(X)
    print(f"ℹ️ Features prepared ({len(features)} features). Samples: {n_samples}")

    # ---------------- Training ----------------
    results = {"team":TEAM_NAME,"timestamp":datetime.now(timezone.utc).isoformat(),"total_samples":n_samples,"models":{}}

    for cat,y in y_dict.items():
        print(f"\n🚀 Preparing model for '{cat}' (N={n_samples})")
        safe_X = X.copy()
        safe_y = y.copy()

        if XGBOOST_AVAILABLE:
            model = XGBRegressor(n_estimators=150,learning_rate=0.08,max_depth=5,subsample=0.9,colsample_bytree=0.9,random_state=42,verbosity=0)
        else:
            model = LinearRegression()

        model_path = os.path.join(OUTPUT_MODEL_DIR,f"{TEAM_NAME.replace(' ','_').lower()}_{cat}.pkl")
        r2_test = None
        cv_mean = None

        if n_samples >= MIN_TRAIN_FOR_SPLIT:
            try:
                X_train,X_test,y_train,y_test = train_test_split(safe_X,safe_y,test_size=TEST_SIZE,random_state=42)
            except ValueError:
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
            else:
                cv_mean = None

        try:
            with open(model_path,"wb") as fh:
                pickle.dump(model,fh)
            saved = True
        except Exception as e:
            print(f"⚠️ Failed to save model for {cat}: {e}")
            saved = False

        results["models"][cat] = {"model_path":model_path if saved else None,"R2_test":r2_test,"R2_CV_mean":cv_mean,"trained_on_samples":n_samples}
        print(f"✅ Model '{cat}' trained. R2_test={r2_test}, R2_CV_mean={cv_mean}, saved={saved}")

    with open(OUTPUT_RESULTS,"w",encoding="utf-8") as f:
        json.dump(results,f,indent=2)

    print("\n✅ Training complete — results saved to:",OUTPUT_RESULTS)
    print("Summary:")
    print(json.dumps(results,indent=2))

if __name__=="__main__":
    main()
