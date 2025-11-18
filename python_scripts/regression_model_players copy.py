# ============================================================
# File: python_scripts/regression_model_players.py
# Purpose: Train 4 role-based player-rating models
# Version: Role-weighted overall + position-aware normalization +
#          Pure Understat GK model (Option B2)
# ============================================================

import json
import os
import pickle
import numpy as np
import pandas as pd
from datetime import datetime, timezone
from sklearn.linear_model import LinearRegression
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import r2_score, mean_absolute_error

# ---------- XGBoost (optional) ----------
try:
    from xgboost import XGBRegressor
    XGBOOST_AVAILABLE = True
except Exception:
    XGBOOST_AVAILABLE = False
    print("⚠️ XGBoost unavailable → using LinearRegression")

# ---------- CONFIG ----------
TEAM_NAME = "San Beda"
DATA_PATH = "python_scripts/sanbeda_players_derived_metrics.json"
OUTPUT_MODEL_DIR = "python_scripts/models_players"
OUTPUT_TRAIN_DATA = "data/sanbeda_players_training_data.csv"
OUTPUT_RESULTS = "python_scripts/player_model_results.json"
UNDERSTAT_GK_CSV = "data/understat_gk_features.csv"

os.makedirs(OUTPUT_MODEL_DIR, exist_ok=True)
os.makedirs(os.path.dirname(OUTPUT_TRAIN_DATA), exist_ok=True)

# ---------- LOAD UNDERSTAT GK DATA ----------
if os.path.exists(UNDERSTAT_GK_CSV):
    gk_df = pd.read_csv(UNDERSTAT_GK_CSV)
    print(f"📂 Loaded Understat GK features: {len(gk_df)} goalkeepers")
    gk_df["saves_per90"] = gk_df["saves"] / (gk_df["time"] / 90.0)
    gk_df["goals_against_per90"] = gk_df["goals_against"] / (gk_df["time"] / 90.0)
    gk_df["save_pct"] = gk_df["saves"] / gk_df["shots_on_target_against"].replace(0, 1)
    gk_df["player_name"] = gk_df["player_name"].astype(str)
    gk_df.fillna(0, inplace=True)
else:
    gk_df = pd.DataFrame()
    print("⚠️ Understat GK CSV not found, GK models will rely on fallback")

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

def logistic_scale(x, k=0.08, x0=50.0):
    try:
        s = 1.0 / (1.0 + np.exp(-k * (x - x0)))
    except Exception:
        s = 0.5
    return float(np.clip(s * 100.0, 0.0, 100.0))

def role_normalize(df, preds, position_col="position", min_samples=3):
    preds = np.array(preds, dtype=float)
    normalized = np.zeros_like(preds)
    df_local = df.reset_index(drop=True)
    for pos in df_local[position_col].fillna("").unique():
        mask = (df_local[position_col] == pos).values
        if mask.sum() == 0:
            continue
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
        if mask.sum() == 0:
            continue
        sub = df.loc[mask, features]
        sub_mean = sub.mean()
        sub_std = sub.std(ddof=0).replace(0, 1e-6)
        df_norm.loc[mask, features] = (sub - sub_mean) / sub_std
    return df_norm

def make_smart_xgb():
    return XGBRegressor(
        n_estimators=500,
        learning_rate=0.03,
        max_depth=6,
        subsample=0.9,
        colsample_bytree=0.8,
        min_child_weight=3,
        reg_lambda=1.0,
        random_state=42,
        n_jobs=-1,
        verbosity=0
    )

def rescale_gk_to_college(preds, target_median=50.0, target_std=15.0):
    """Rescale GK predictions to college-level rating scale."""
    if len(preds) == 0: return preds
    median_pred = np.median(preds)
    std_pred = np.std(preds, ddof=0) or 1.0
    rescaled = (preds - median_pred) / std_pred * target_std + target_median
    return np.clip(rescaled, 0, 100)

# ---------- ROLE WEIGHTS ----------
ROLE_WEIGHTS = {
    "attacker": {"attack": 0.6, "distribution": 0.25, "discipline": 0.15},
    "midfielder": {"attack": 0.3, "defense": 0.3, "distribution": 0.25, "discipline": 0.15},
    "defender": {"defense": 0.5, "distribution": 0.3, "discipline": 0.2},
    "goalkeeper": {"defense": 0.6, "distribution": 0.3, "discipline": 0.1}
}

POSITION_ROLE = {
    "LW": "attacker", "RW": "attacker", "ST": "attacker",
    "CAM": "midfielder", "CDM": "midfielder",
    "LWB": "defender", "RWB": "defender",
    "CB": "defender", "GK": "goalkeeper"
}

# ---------- LOAD PLAYER METRICS ----------
print(f"📂 Loading player metrics: {DATA_PATH}")
if not os.path.exists(DATA_PATH):
    raise FileNotFoundError(f"❌ JSON not found: {DATA_PATH}")

with open(DATA_PATH, "r", encoding="utf-8") as f:
    data_json = json.load(f)

team_data = data_json.get(TEAM_NAME, {})
if not team_data:
    raise ValueError(f"❌ Team '{TEAM_NAME}' not found in JSON")

flattened = []
for name, stats in team_data.items():
    row = {"player_name": name}
    row.update(flatten_dict(stats))
    flattened.append(row)
new_df = pd.DataFrame(flattened)
new_df["position"] = [team_data[name].get("position", "") for name in team_data]

# ---------- DETERMINE MATCH NUMBER ----------
if os.path.exists(OUTPUT_TRAIN_DATA):
    old_df = pd.read_csv(OUTPUT_TRAIN_DATA)
    next_match_number = int(old_df["match_number"].max()) + 1 if "match_number" in old_df.columns else 1
else:
    old_df = pd.DataFrame()
    next_match_number = 1

match_id = f"Match_{next_match_number}"
new_df["match_number"] = next_match_number
new_df["match_id"] = match_id
new_df["added_at"] = datetime.now(timezone.utc).isoformat()

# ---------- MERGE TRAINING DATA ----------
combined_df = pd.concat([old_df, new_df], ignore_index=True) if not old_df.empty else new_df
position_history = combined_df.groupby("player_name")["position"].apply(lambda x: list(x.unique())).to_dict()
combined_df["position_history"] = combined_df["player_name"].map(position_history)
combined_df["position_changed"] = combined_df.apply(lambda row: bool(row["position_history"] and row["position"] != row["position_history"][0]), axis=1)
combined_df.to_csv(OUTPUT_TRAIN_DATA, index=False)
print(f"💾 Training data saved → {OUTPUT_TRAIN_DATA}")

# ---------- ROLE FEATURES ----------
all_columns = list(combined_df.columns)
role_feats = {
    "attacker": [c for c in all_columns if c.startswith(("attack__", "distribution__", "discipline__"))] + [c for c in all_columns if c.startswith("possession__")],
    "midfielder": [c for c in all_columns if c.startswith(("attack__", "distribution__", "defense__", "discipline__"))] + [c for c in all_columns if c.startswith("possession__")],
    "defender": [c for c in all_columns if c.startswith(("defense__", "distribution__", "discipline__"))] + [c for c in all_columns if c.startswith("possession__")],
    "goalkeeper": [c for c in all_columns if c.startswith(("defense__", "distribution__", "discipline__"))] + [c for c in all_columns if c.startswith("possession__")]
}
for role in role_feats:
    seen = []
    cleaned = []
    for f in role_feats[role]:
        if f in all_columns and f not in seen:
            cleaned.append(f)
            seen.append(f)
    role_feats[role] = cleaned

# ---------- ROLE-OVERALL ----------
def compute_role_overall(row):
    role = row.get("role") or POSITION_ROLE.get(row.get("position"), "midfielder")
    weights = ROLE_WEIGHTS.get(role, ROLE_WEIGHTS["midfielder"])
    comps = {
        "attack": float(row.get("match_rating_attack", np.nan)),
        "defense": float(row.get("match_rating_defense", np.nan)),
        "distribution": float(row.get("match_rating_distribution", np.nan)),
        "discipline": float(row.get("match_rating_discipline", np.nan))
    }
    present = {k: v for k, v in weights.items() if not np.isnan(comps.get(k, np.nan))}
    if not present:
        return float(row.get("match_rating_overall", 50.0))
    total_w = sum(present.values())
    overall = sum((v / total_w) * comps[k] for k, v in present.items())
    return float(np.clip(overall, 0, 100))

combined_df["role"] = combined_df["position"].map(POSITION_ROLE).fillna("midfielder")
combined_df["role_overall"] = combined_df.apply(compute_role_overall, axis=1)

# ---------- TRAIN ROLE MODELS ----------
roles = ["attacker", "midfielder", "defender", "goalkeeper"]
summary = {"team": TEAM_NAME, "trained_at": datetime.now(timezone.utc).isoformat(),
           "total_matches": int(combined_df["match_number"].max()), "total_samples": len(combined_df), "models": {}}

for role in roles:
    # ---------- PURE UNDERSTAT GK ----------
    if role == "goalkeeper" and not gk_df.empty:
        print(f"📌 Training pure Understat GK model ({len(gk_df)} samples)")
        gk_feats = ["saves_per90", "goals_against_per90", "save_pct"]
        gk_df_numeric = gk_df[gk_feats].apply(pd.to_numeric, errors="coerce").fillna(0)
        gk_target = (gk_df_numeric["save_pct"] * 100).clip(0, 100)

        scaler = StandardScaler()
        X_scaled = scaler.fit_transform(gk_df_numeric)
        model = make_smart_xgb() if XGBOOST_AVAILABLE else LinearRegression()
        model.fit(X_scaled, gk_target)
        preds = model.predict(X_scaled)
        r2 = float(r2_score(gk_target, preds)) if len(gk_target) >= 2 else None
        mae = float(mean_absolute_error(gk_target, preds)) if len(gk_target) >= 1 else None
        preds_norm = rescale_gk_to_college(preds, target_median=50.0, target_std=15.0)

        model_artifact = {
            "model": model,
            "scaler": scaler,
            "features": gk_feats,
            "r2": r2,
            "mae": mae,
            "role": role,
            "role_weights": ROLE_WEIGHTS.get(role),
            "fallback": False,
            "note": "Pure Understat GK model"
        }

        model_path = os.path.join(OUTPUT_MODEL_DIR, f"sanbeda_role_{role}_understat.pkl")
        with open(model_path, "wb") as f:
            pickle.dump(model_artifact, f)

        summary["models"][role] = {
            "R2": r2,
            "MAE": mae,
            "model_path": model_path,
            "pos_R2": None,
            "role_weights": ROLE_WEIGHTS.get(role),
            "trained_samples": len(gk_df),
            "fallback_used": False
        }
        print(f"  ✅ GOALKEEPER (Understat) model saved (R²={r2}, MAE={mae})")
        continue

    # ---------- OTHER ROLES ----------
    subset = combined_df[combined_df["role"] == role].copy()
    if subset.empty:
        print(f"⚠️ [SKIP] role '{role}' → no samples")
        continue

    feats = role_feats.get(role, [])
    if not feats:
        feats = [c for c in all_columns if c.startswith(("match_rating_", "possession__", "distribution__", "defense__", "attack__", "discipline__"))][:10]

    X_role = position_feature_normalize(subset, feats, position_col="position")[feats].fillna(0)
    X_numeric = X_role.apply(pd.to_numeric, errors="coerce").fillna(0).astype(float)
    y = pd.to_numeric(subset["role_overall"], errors="coerce").fillna(0).astype(float)

    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X_numeric)
    model = make_smart_xgb() if XGBOOST_AVAILABLE else LinearRegression()
    model.fit(X_scaled, y)
    preds = model.predict(X_scaled)
    preds_norm = role_normalize(subset.reset_index(drop=True), preds, position_col="position")
    r2 = float(r2_score(y, preds)) if len(y) >= 2 else None
    mae = float(mean_absolute_error(y, preds)) if len(y) >= 1 else None

    model_artifact = {
        "model": model,
        "scaler": scaler,
        "features": feats,
        "r2": r2,
        "mae": mae,
        "role": role,
        "role_weights": ROLE_WEIGHTS.get(role),
        "fallback": False
    }

    model_path = os.path.join(OUTPUT_MODEL_DIR, f"sanbeda_role_{role}.pkl")
    with open(model_path, "wb") as f:
        pickle.dump(model_artifact, f)

    summary["models"][role] = {
        "R2": r2,
        "MAE": mae,
        "model_path": model_path,
        "pos_R2": None,
        "role_weights": ROLE_WEIGHTS.get(role),
        "trained_samples": int(len(subset)),
        "fallback_used": False
    }

    print(f"  ✅ {role.upper()} model saved (R²={r2}, MAE={mae})")

# ---------- SAVE SUMMARY ----------
def make_json_safe(o):
    if isinstance(o, (np.floating, np.float32, np.float64)): return float(o)
    if isinstance(o, (np.integer, np.int32, np.int64)): return int(o)
    if isinstance(o, np.ndarray): return o.tolist()
    return o

summary_serializable = json.loads(json.dumps(summary, default=make_json_safe))
with open(OUTPUT_RESULTS, "w", encoding="utf-8") as f:
    json.dump(summary_serializable, f, indent=4)

print(f"\n🏁 Role-based model retraining complete for {summary['total_matches']} matches! Summary → {OUTPUT_RESULTS}")
