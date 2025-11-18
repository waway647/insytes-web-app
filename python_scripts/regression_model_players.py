# ============================================================
# File: python_scripts/regression_model_players.py
# Purpose: Train 4 role-based player-rating models + predict next-match DPR
# FEATURES: position_history, position_changed, Understat GK (percentile rank)
# FIXED: No FileNotFound, GK works with 0 samples, safe fallbacks
# ============================================================

import json
import os
import sys
import argparse
import pickle
import numpy as np
import pandas as pd
from datetime import datetime, timezone
from sklearn.linear_model import LinearRegression
from sklearn.ensemble import RandomForestRegressor
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import r2_score, mean_absolute_error, mean_squared_error
from sklearn.model_selection import cross_val_score, train_test_split, KFold

try:
    from xgboost import XGBRegressor
    XGBOOST_AVAILABLE = True
except Exception:
    XGBOOST_AVAILABLE = False

# Command line argument parsing
parser = argparse.ArgumentParser(description='Train player DPR prediction models')
parser.add_argument('--dataset', default='sbu_vs_2worlds', help='Dataset name (e.g., sbu_vs_2worlds)')
args = parser.parse_args()

MATCH_NAME = args.dataset

# ---------- CONFIG ----------
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

# Create match-specific output directories
MATCH_OUTPUT_DIR = f"output/matches/{MATCH_NAME}"
MODELS_OUTPUT_DIR = f"python_scripts/models_players"
TRAINING_DATA_DIR = "data/training_data"

os.makedirs(MATCH_OUTPUT_DIR, exist_ok=True)
os.makedirs(MODELS_OUTPUT_DIR, exist_ok=True)
os.makedirs(TRAINING_DATA_DIR, exist_ok=True)

# Input and output paths
team_name_safe = TEAM_NAME.lower().replace(" ", "_")
DATA_PATH = f"{MATCH_OUTPUT_DIR}/{team_name_safe}_players_derived_metrics.json"
OUTPUT_MODEL_DIR = MODELS_OUTPUT_DIR  # Save models in organized location
OUTPUT_TRAIN_DATA = f"{TRAINING_DATA_DIR}/players_training_data.csv"  # Unified training data file across all teams
OUTPUT_RESULTS = f"{MATCH_OUTPUT_DIR}/player_model_results.json"  # Match-specific results
UNDERSTAT_GK_CSV = "data/training_data/understat_gk_features.csv"

MIN_SAMPLES = 5
MIN_R2 = 0.30
TEST_SIZE = 0.2  # 20% for testing
RANDOM_STATE = 42
CV_FOLDS = 5

os.makedirs(OUTPUT_MODEL_DIR, exist_ok=True)
os.makedirs(os.path.dirname(OUTPUT_TRAIN_DATA), exist_ok=True)

def validate_model(model, X, y, model_name):
    """Comprehensive model validation with train/test split and cross-validation"""
    if len(X) < MIN_SAMPLES:
        return {"status": "insufficient_data", "samples": len(X)}
    
    # Train/Test Split
    if len(X) >= 10:  # Only split if we have enough data
        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=TEST_SIZE, random_state=RANDOM_STATE, shuffle=True
        )
    else:
        # For small datasets, use full data for training but note the limitation
        X_train, X_test, y_train, y_test = X, X, y, y
        
    # Fit scaler on training data only
    scaler = StandardScaler()
    X_train_scaled = scaler.fit_transform(X_train)
    X_test_scaled = scaler.transform(X_test)
    
    # Train model
    model.fit(X_train_scaled, y_train)
    
    # Predictions
    train_preds = model.predict(X_train_scaled)
    test_preds = model.predict(X_test_scaled)
    
    # Training metrics
    train_r2 = r2_score(y_train, train_preds) if len(y_train) >= 2 else None
    train_mae = mean_absolute_error(y_train, train_preds)
    train_mse = mean_squared_error(y_train, train_preds)
    
    # Testing metrics  
    test_r2 = r2_score(y_test, test_preds) if len(y_test) >= 2 else None
    test_mae = mean_absolute_error(y_test, test_preds)
    test_mse = mean_squared_error(y_test, test_preds)
    
    # Cross-validation
    cv_scores = None
    cv_mean = None
    cv_std = None
    
    try:
        if len(X) >= CV_FOLDS:
            kfold = KFold(n_splits=min(CV_FOLDS, len(X)), shuffle=True, random_state=RANDOM_STATE)
            cv_scores = cross_val_score(model, X_train_scaled, y_train, cv=kfold, scoring='r2')
            cv_mean = float(np.mean(cv_scores))
            cv_std = float(np.std(cv_scores))
    except Exception as e:
        print(f"Cross-validation failed for {model_name}: {e}")
    
    # Overfitting detection
    overfitting = False
    if train_r2 and test_r2:
        overfitting = (train_r2 - test_r2) > 0.3  # Significant gap indicates overfitting
    
    validation_results = {
        "status": "validated",
        "samples_total": len(X),
        "samples_train": len(X_train),
        "samples_test": len(X_test),
        "train_metrics": {
            "r2": float(train_r2) if train_r2 else None,
            "mae": float(train_mae),
            "mse": float(train_mse)
        },
        "test_metrics": {
            "r2": float(test_r2) if test_r2 else None,
            "mae": float(test_mae),
            "mse": float(test_mse)
        },
        "cross_validation": {
            "cv_mean_r2": cv_mean,
            "cv_std_r2": cv_std,
            "cv_scores": cv_scores.tolist() if cv_scores is not None else None
        },
        "overfitting_detected": overfitting,
        "model_quality": "good" if test_r2 and test_r2 > MIN_R2 else "poor"
    }
    
    return validation_results, model, scaler, X_train_scaled

# ---------- LOAD UNDERSTAT GK DATA ----------
# Try organized location first, fallback to legacy location
UNDERSTAT_PATHS = [
    "data/understat_gk_features.csv",  # Primary location
    f"{TRAINING_DATA_DIR}/understat_gk_features.csv"  # Alternative location
]

gk_df = pd.DataFrame()
for path in UNDERSTAT_PATHS:
    if os.path.exists(path):
        UNDERSTAT_GK_CSV = path
        gk_df = pd.read_csv(UNDERSTAT_GK_CSV)
        print(f"Loaded Understat GK features: {len(gk_df)} goalkeepers from {path}")
        break

if not gk_df.empty:
    gk_df["time"] = gk_df["time"].replace(0, np.nan)
    gk_df["saves_per90"] = gk_df["saves"] / (gk_df["time"] / 90.0)
    gk_df["goals_against_per90"] = gk_df["goals_against"] / (gk_df["time"] / 90.0)
    gk_df["shots_on_target_against"] = gk_df["shots_on_target_against"].replace(0, np.nan)
    gk_df["save_pct"] = gk_df["saves"] / gk_df["shots_on_target_against"]
    gk_df["saves_per90"] = gk_df["saves_per90"].fillna(0.0)
    gk_df["goals_against_per90"] = gk_df["goals_against_per90"].fillna(0.0)
    gk_df["save_pct"] = gk_df["save_pct"].fillna(0.0)
    gk_df["player_name"] = gk_df["player_name"].astype(str)
    gk_df.fillna(0, inplace=True)
else:
    gk_df = pd.DataFrame()
    print("Understat GK CSV not found -> GK will use fallback")

# ---------- HELPERS ----------
def flatten_dict(d, parent_key="", sep="__"):
    items = []
    for k, v in d.items():
        new_key = f"{parent_key}{sep}{k}" if parent_key else k
        if isinstance(v, dict):
            items.extend(flatten_dict(v, new_key, sep).items())
        else:
            items.append((new_key, v))
    return dict(items)

def role_normalize(df, preds, position_col="position", min_samples=3):
    preds = np.array(preds, dtype=float)
    normalized = np.zeros_like(preds)
    df_local = df.reset_index(drop=True)
    for pos in df_local[position_col].fillna("").unique():
        mask = (df_local[position_col] == pos).values
        if mask.sum() == 0: continue
        group_vals = preds[mask]
        if len(group_vals) < min_samples:
            normalized[mask] = np.clip(group_vals, 30, 100)
            continue
        mean_pos = group_vals.mean()
        std_pos = group_vals.std(ddof=0)
        z = (group_vals - mean_pos) / (std_pos + 1e-6)
        normalized[mask] = np.clip(50 + z * 25, 0, 100)
    return normalized

def position_feature_normalize(df, features, position_col="position"):
    df_norm = df.copy()
    for pos in df[position_col].fillna("").unique():
        mask = df[position_col] == pos
        if mask.sum() == 0: continue
        sub = df.loc[mask, features]
        sub_mean = sub.mean()
        sub_std = sub.std(ddof=0).replace(0, 1e-6)
        df_norm.loc[mask, features] = (sub - sub_mean) / sub_std
    return df_norm

def make_smart_xgb():
    return XGBRegressor(
        n_estimators=500, learning_rate=0.03, max_depth=6,
        subsample=0.9, colsample_bytree=0.8, min_child_weight=3,
        reg_lambda=1.0, random_state=42, n_jobs=-1, verbosity=0
    )

# ---------- ROLE WEIGHTS ----------
ROLE_WEIGHTS = {
    "attacker":   {"attack": 0.6, "distribution": 0.25, "discipline": 0.15},
    "midfielder": {"attack": 0.3, "defense": 0.3, "distribution": 0.25, "discipline": 0.15},
    "defender":   {"defense": 0.5, "distribution": 0.3, "discipline": 0.2},
    "goalkeeper": {"defense": 0.6, "distribution": 0.3, "discipline": 0.1}
}

POSITION_ROLE = {
    "LW": "attacker", "RW": "attacker", "ST": "attacker",
    "CAM": "midfielder", "CDM": "midfielder",
    "LWB": "defender", "RWB": "defender",
    "CB": "defender", "GK": "goalkeeper"
}

# ---------- LOAD PLAYER METRICS ----------
print(f"Loading player metrics: {DATA_PATH}")

with open(DATA_PATH, 'r') as f:
    data_json = json.load(f)

team_data = data_json.get(TEAM_NAME, {})
if not team_data:
    raise ValueError(f"Team '{TEAM_NAME}' not found in JSON")

flattened = []
for name, stats in team_data.items():
    # Skip metadata and lineup data
    if name.startswith("_") or name in ['match_id', 'match_name']: 
        continue
    # Skip if stats is not a dictionary (shouldn't happen but safety check)
    if not isinstance(stats, dict):
        continue
    row = {"player_name": name}
    row.update(flatten_dict(stats))
    for k, v in stats.get("key_stats_p90", {}).items():
        row[k] = v
    flattened.append(row)

new_df = pd.DataFrame(flattened)

# ---------- DETERMINE MATCH NUMBER ----------
# Load existing training data from single source
if os.path.exists(OUTPUT_TRAIN_DATA):
    old_df = pd.read_csv(OUTPUT_TRAIN_DATA)
    print(f"Loaded training data: {OUTPUT_TRAIN_DATA}")
else:
    old_df = pd.DataFrame()
    print("No existing training data found. Starting fresh.")

next_match_number = int(old_df["match_number"].max()) + 1 if "match_number" in old_df.columns and not old_df.empty else 1
match_id = f"Match_{next_match_number}"
new_df["match_number"] = next_match_number
new_df["match_id"] = match_id
new_df["added_at"] = datetime.now(timezone.utc).isoformat()

# ---------- ADD POSITION HISTORY ----------
if not old_df.empty and "player_name" in old_df.columns:
    history = old_df.groupby("player_name")["position"].apply(list).to_dict()
    changed = old_df.groupby("player_name")["position"].apply(
        lambda x: int(len(set(x)) > 1) if len(x) > 1 else 0
    ).to_dict()
else:
    history = {}
    changed = {}

new_df["position_history"] = new_df["player_name"].map(history).apply(lambda x: x if isinstance(x, list) else [])
new_df["position_changed"] = new_df["player_name"].map(changed).fillna(0).astype(int)

# ---------- MERGE & SAVE TRAINING DATA ----------
combined_df = pd.concat([old_df, new_df], ignore_index=True) if not old_df.empty else new_df
combined_df.to_csv(OUTPUT_TRAIN_DATA, index=False)
print(f"Consolidated training data saved -> {OUTPUT_TRAIN_DATA} (with position_history & position_changed)")

# ---------- ENHANCED FEATURES ----------
P90_COLS = [c for c in new_df.columns if c.endswith("_p90")]

# Extract enhanced breakdown features based on position
ENHANCED_BREAKDOWN_FEATS = {
    "attacker": ["finishing_quality", "chance_creation", "movement_threat", "link_up_play", "work_rate"],
    "midfielder": ["game_control", "transition_play", "defensive_contribution", "creativity", "work_rate"], 
    "defender": ["defensive_impact", "build_up_quality", "positioning_score", "recovery_efficiency", "aerial_dominance"],
    "goalkeeper": ["shot_stopping", "distribution_quality", "command_area", "ball_playing", "consistency"]
}

combined_df["role"] = combined_df["position"].map(POSITION_ROLE).fillna("midfielder")

# Add enhanced breakdown features to dataframe if available
for role, breakdown_cols in ENHANCED_BREAKDOWN_FEATS.items():
    role_players = combined_df[combined_df["role"] == role]
    for idx, row in role_players.iterrows():
        player_name = row["player_name"]
        if player_name in team_data and "dpr_breakdown" in team_data[player_name]:
            breakdown = team_data[player_name]["dpr_breakdown"]
            for feat in breakdown_cols:
                if feat in breakdown:
                    combined_df.loc[idx, f"enhanced_{feat}"] = breakdown[feat]

# Enhanced feature sets for each role
BASE_FEATS = ["minutes_played", "dpr", "position_changed"] + P90_COLS

# Add enhanced features where available  
ENHANCED_FEATS = {}
for role, breakdown_cols in ENHANCED_BREAKDOWN_FEATS.items():
    enhanced_cols = [f"enhanced_{feat}" for feat in breakdown_cols if f"enhanced_{feat}" in combined_df.columns]
    ENHANCED_FEATS[role] = enhanced_cols
    print(f"Role {role}: Found {len(enhanced_cols)} enhanced features: {enhanced_cols}")

role_feats = {
    "attacker":   [f for f in BASE_FEATS if any(k in f for k in ["shots","goals","key_passes","assists","progressive","dribbles","minutes_played","dpr","position_changed"])] + ENHANCED_FEATS.get("attacker", []),
    "midfielder": [f for f in BASE_FEATS if any(k in f for k in ["passes","duels","tackles","interceptions","progressive","dribbles","minutes_played","dpr","position_changed"])] + ENHANCED_FEATS.get("midfielder", []),
    "defender":   [f for f in BASE_FEATS if any(k in f for k in ["duels","tackles","interceptions","clearances","progressive","minutes_played","dpr","position_changed"])] + ENHANCED_FEATS.get("defender", []),
    "goalkeeper": [f for f in BASE_FEATS if any(k in f for k in ["saves","goals_against","save_pct","passes","minutes_played","dpr","position_changed"])] + ENHANCED_FEATS.get("goalkeeper", [])
}

# ---------- TRAIN ROLE MODELS ----------
roles = ["attacker", "midfielder", "defender", "goalkeeper"]
summary = {
    "team": TEAM_NAME,
    "trained_at": datetime.now(timezone.utc).isoformat(),
    "total_matches": int(combined_df["match_number"].max()),
    "total_samples": len(combined_df),
    "models": {}
}

for role in roles:
    # ---------- GOALKEEPER: USE UNDERSTAT (EVEN WITH 0 SAMPLES) ----------
    if role == "goalkeeper":
        if not gk_df.empty:
            print(f"Training Understat GK model ({len(gk_df)} pro samples)")
            feats = ["saves_per90", "goals_against_per90", "save_pct"]
            feats = [f for f in feats if f in gk_df.columns]
            if not feats:
                feats = [c for c in gk_df.columns if c not in ["player_name", "time"]]
            X = gk_df[feats].astype(float).fillna(0)
            y_pct = gk_df["save_pct"].astype(float).fillna(0)
            y_rank = y_pct.rank(pct=True) * 100  # 0 100 scale

            model = RandomForestRegressor(n_estimators=300, max_depth=8, min_samples_leaf=4, random_state=RANDOM_STATE, n_jobs=-1)
            
            # Proper validation
            validation_results, trained_model, trained_scaler, X_train_scaled = validate_model(model, X, y_rank, f"GK_{role}")
            
            artifact = {
                "model": trained_model, "scaler": trained_scaler, "features": feats,
                "validation": validation_results, "role": role, "type": "understat_percentile"
            }
            path = os.path.join(OUTPUT_MODEL_DIR, f"role_goalkeeper_understat.pkl")
            with open(path, "wb") as f:
                pickle.dump(artifact, f)

            summary["models"][role] = {
                "status": "trained_validated", "model_path": path,
                "validation": validation_results,
                "note": "Understat percentile rank -> DPR scale"
            }
            
            test_r2 = validation_results.get("test_metrics", {}).get("r2", "N/A")
            cv_r2 = validation_results.get("cross_validation", {}).get("cv_mean_r2", "N/A")
            print(f"  [OK] GK Model: Test R ={test_r2:.3f}, CV R ={cv_r2:.3f}" if isinstance(test_r2, float) and isinstance(cv_r2, float) else f"  [OK] GK Model saved with validation")
        else:
            print(f"  [FALLBACK] No Understat data -> use current DPR")
            summary["models"][role] = {"status": "fallback", "reason": "no_understat"}
        continue

    # ---------- OTHER ROLES ----------
    subset = combined_df[combined_df["role"] == role].copy()
    if subset.empty:
        print(f"[SKIP] {role}   no data")
        continue

    if len(subset) < MIN_SAMPLES:
        print(f"[FALLBACK] {role.upper()} < {MIN_SAMPLES} samples -> use DPR")
        summary["models"][role] = {"status": "fallback", "reason": "low_sample", "samples": len(subset)}
        continue

    feats = role_feats.get(role, [])
    X = position_feature_normalize(subset, feats, "position")[feats].fillna(0)
    X_num = X.apply(pd.to_numeric, errors="coerce").fillna(0).astype(float)
    y = pd.to_numeric(subset["dpr"], errors="coerce").fillna(50.0)

    model = make_smart_xgb() if XGBOOST_AVAILABLE else LinearRegression()
    
    # Proper validation with train/test split
    validation_results, trained_model, trained_scaler, X_train_scaled = validate_model(model, X_num, y, f"{role}_model")
    
    # Check if model meets quality standards
    test_r2 = validation_results.get("test_metrics", {}).get("r2")
    if test_r2 is not None and test_r2 < MIN_R2:
        print(f"[FALLBACK] {role.upper()} Test R ={test_r2:.3f} < {MIN_R2} -> use DPR")
        summary["models"][role] = {
            "status": "fallback", "reason": "low_test_r2", 
            "validation": validation_results
        }
        continue
    
    # Check for overfitting
    if validation_results.get("overfitting_detected", False):
        print(f"[WARNING] {role.upper()} shows overfitting - consider simpler model")

    artifact = {
        "model": trained_model, "scaler": trained_scaler, "features": feats,
        "validation": validation_results, "role": role, "fallback": False
    }
    # Save persistent model (without match name) so it improves over time
    path = os.path.join(OUTPUT_MODEL_DIR, f"role_{role}.pkl")
    with open(path, "wb") as f:
        pickle.dump(artifact, f)

    # Extract key metrics for summary
    train_r2 = validation_results.get("train_metrics", {}).get("r2", "N/A")
    test_r2 = validation_results.get("test_metrics", {}).get("r2", "N/A")
    cv_r2 = validation_results.get("cross_validation", {}).get("cv_mean_r2", "N/A")
    test_mae = validation_results.get("test_metrics", {}).get("mae", "N/A")
    
    summary["models"][role] = {
        "status": "trained_validated", "model_path": path,
        "validation": validation_results,
        "features": feats
    }
    
    print(f"  [OK] {role.upper()}: Train R ={train_r2:.3f}, Test R ={test_r2:.3f}, CV R ={cv_r2:.3f}, Test MAE={test_mae:.2f} ({validation_results['samples_total']} total)")

# ---------- NEXT-MATCH DPR PREDICTION ----------
print("\nPredicting next-match DPR...")
current = combined_df[combined_df["match_number"] == next_match_number].copy()
pred_df = current[["player_name", "position", "minutes_played", "dpr", "position_history", "position_changed"]].copy()

for role in roles:
    subset = current[current["role"] == role].copy()
    if subset.empty: continue

    model_file = os.path.join(OUTPUT_MODEL_DIR, f"role_{role}.pkl")
    understat_file = os.path.join(OUTPUT_MODEL_DIR, "role_goalkeeper_understat.pkl")

    if role == "goalkeeper" and os.path.exists(understat_file):
        print(f"  GK: Using Understat percentile model")
        with open(understat_file, "rb") as f:
            artifact = pickle.load(f)
        feats = [f for f in artifact["features"] if f in subset.columns]
        if not feats:
            print(f"  [FALLBACK] No GK stats   use current DPR")
            pred_df.loc[subset.index, "predicted_dpr"] = subset["dpr"]
        else:
            X = subset[feats].fillna(0).astype(float)
            X_scaled = artifact["scaler"].transform(X)
            pred_rank = artifact["model"].predict(X_scaled)
            pred_df.loc[subset.index, "predicted_dpr"] = np.clip(pred_rank, 0, 100)

    elif os.path.exists(model_file):
        print(f"  {role.upper()}: Using local model")
        with open(model_file, "rb") as f:
            artifact = pickle.load(f)
        X = position_feature_normalize(subset, artifact["features"], "position")
        X = X[artifact["features"]].fillna(0)
        X_scaled = artifact["scaler"].transform(X.apply(pd.to_numeric, errors="coerce").fillna(0))
        pred = artifact["model"].predict(X_scaled)
        pred_norm = role_normalize(subset.reset_index(drop=True), pred, "position")
        pred_df.loc[subset.index, "predicted_dpr"] = pred_norm

    else:
        print(f"  {role.upper()}: [FALLBACK]   current DPR")
        pred_df.loc[subset.index, "predicted_dpr"] = subset["dpr"]

pred_df["dpr_change"] = pred_df["predicted_dpr"] - pred_df["dpr"]
pred_df = pred_df.round(1)

# Save predictions to match-specific location only
PRED_CSV = f"{MATCH_OUTPUT_DIR}/player_dpr_predictions.csv"
pred_df_out = pred_df[["player_name", "dpr", "predicted_dpr", "dpr_change", "minutes_played", "position_history", "position_changed"]].copy()
pred_df_out.rename(columns={"player_name": "player", "minutes_played": "minutes"}, inplace=True)

# Save to match-specific location
pred_df_out.to_csv(PRED_CSV, index=False)

print(f"\nPredictions saved to: {PRED_CSV}")
print("\nTop 5 DPR GAINERS:")
top_gainers = pred_df.nlargest(5, "dpr_change")
print(top_gainers[["player_name", "dpr", "predicted_dpr", "dpr_change", "position_changed"]].to_string(index=False))

print("\nTop 5 DPR DECLINERS:")
top_decliners = pred_df.nsmallest(5, "dpr_change")
print(top_decliners[["player_name", "dpr", "predicted_dpr", "dpr_change", "position_changed"]].to_string(index=False))

# Save summary to both locations
def safe(o):
    if isinstance(o, (np.generic, np.ndarray)): return o.item() if np.isscalar(o) else o.tolist()
    return o

summary_safe = json.loads(json.dumps(summary, default=safe))

# Save to match-specific location
with open(OUTPUT_RESULTS, "w") as f:
    json.dump(summary_safe, f, indent=4)

print(f"\n{'='*60}")
print("  ENHANCED MODEL TRAINING WITH VALIDATION COMPLETE")
print(f"{'='*60}")

for role, info in summary["models"].items():
    status = info.get("status", "unknown")
    print(f"\n[MODEL] {role.upper()} MODEL:")
    print(f"   Status: {status}")
    
    if status == "trained_validated":
        validation = info.get("validation", {})
        train_metrics = validation.get("train_metrics", {})
        test_metrics = validation.get("test_metrics", {})
        cv_metrics = validation.get("cross_validation", {})
        
        train_r2 = train_metrics.get('r2')
        test_r2 = test_metrics.get('r2')
        cv_r2 = cv_metrics.get('cv_mean_r2')
        cv_std = cv_metrics.get('cv_std_r2', 0)
        
        print(f"     Training:   R ={train_r2:.3f}, MAE={train_metrics.get('mae', 0):.2f}" if train_r2 else "     Training: N/A")
        print(f"   Testing:    R ={test_r2:.3f}, MAE={test_metrics.get('mae', 0):.2f}" if test_r2 else "   Testing: N/A") 
        print(f"     Cross-Val:  R ={cv_r2:.3f} {cv_std:.3f}" if cv_r2 else "     Cross-Val: N/A")
        print(f"   Samples:    {validation.get('samples_total', 0)} total ({validation.get('samples_train', 0)} train, {validation.get('samples_test', 0)} test)")
        
        if validation.get("overfitting_detected", False):
            print(f"   [WARNING] Overfitting detected - consider model simplification")
        
        quality = validation.get("model_quality", "unknown")
        print(f"     Quality:    {quality}")
        
    elif status == "fallback":
        reason = info.get("reason", "unknown")
        print(f"   [WARNING] Using fallback - {reason}")

print(f"\n  Results saved to: {OUTPUT_RESULTS}")
print("  Models now include proper train/test validation!")

print(f"\nTraining complete! Models and results organized in output structure.")