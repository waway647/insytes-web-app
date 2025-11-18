# ============================================================
# File: python_scripts/predict_player_insights.py
# Purpose: Generate player insights, top performers, and performance badges
# Context-aware: Adjusts insights per player role
# Updated: Fully aligned with role-based model (attacker/midfielder/defender/GK)
#          Absolute rescaling across all players and dynamic top highlights
# ============================================================

import json
import os
import pickle
import numpy as np
import pandas as pd
from datetime import datetime, timezone

TEAM_NAME = "San Beda"
DERIVED_METRICS_PATH = "python_scripts/sanbeda_players_derived_metrics.json"
MODELS_DIR = "python_scripts/models_players"
OUTPUT_PATH = "python_scripts/sanbeda_player_insights.json"

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

# ---------- SAFE SCALER ----------
class SafeScaler:
    """Wraps a scikit-learn scaler to safely handle missing columns."""
    def __init__(self, scaler):
        self.scaler = scaler
        self.features = getattr(scaler, "feature_names_in_", None)
        if self.features is not None:
            self.features = list(self.features)

    def transform(self, X):
        X_new = X.copy()
        for f in self.features:
            if f not in X_new.columns:
                X_new[f] = 0
        X_new = X_new[self.features]
        return self.scaler.transform(X_new)

# ---------- LOAD PLAYER METRICS ----------
print(f"📂 Loading derived metrics: {DERIVED_METRICS_PATH}")
if not os.path.exists(DERIVED_METRICS_PATH):
    raise FileNotFoundError(f"❌ Metrics JSON not found: {DERIVED_METRICS_PATH}")

with open(DERIVED_METRICS_PATH, "r", encoding="utf-8") as f:
    data_json = json.load(f)

team_data = data_json.get(TEAM_NAME, {})
if not team_data:
    raise ValueError(f"❌ Team '{TEAM_NAME}' not found in derived metrics")

players_list = []
for name, stats in team_data.items():
    flat = flatten_dict(stats)
    players_list.append({"name": name, "flat": flat, "raw": stats})

print(f"✅ Loaded {len(players_list)} players from {TEAM_NAME}")

# ---------- LOAD MODELS ----------
print(f"📂 Loading models from {MODELS_DIR}")
if not os.path.exists(MODELS_DIR):
    raise FileNotFoundError(f"❌ Models directory not found: {MODELS_DIR}")

loaded_models = {}
for fname in os.listdir(MODELS_DIR):
    path = os.path.join(MODELS_DIR, fname)
    if not os.path.isfile(path):
        continue
    if not (fname.endswith(".pkl") or fname.endswith(".pickle") or fname.endswith(".joblib")):
        continue
    role = os.path.splitext(fname)[0]
    try:
        with open(path, "rb") as mf:
            obj = pickle.load(mf)
    except Exception as e:
        print(f"⚠️ Skipping {fname}: {e}")
        continue

    model = None
    scaler = None
    features = None

    if isinstance(obj, dict):
        model = obj.get("model") or obj.get("estimator")
        scaler = obj.get("scaler")
        features = obj.get("features") or obj.get("columns") or obj.get("X_columns")
    elif isinstance(obj, (list, tuple)):
        if len(obj) >= 3:
            model, scaler, features = obj[0], obj[1], obj[2]
        elif len(obj) == 2:
            model, scaler = obj[0], obj[1]
    else:
        model = obj

    if model is None:
        print(f"⚠️ No model found in {fname}, skipping")
        continue

    if scaler is None:
        class _IdentityScaler:
            def transform(self, X):
                return X
        scaler = _IdentityScaler()
    else:
        scaler = SafeScaler(scaler)

    if features is None:
        features = getattr(model, "feature_names_in_", None)
        if features is None:
            features = list(players_list[0]["flat"].keys()) if players_list else []
    features = list(features)

    loaded_models[role] = {"model": model, "scaler": scaler, "features": features}

targets = list(loaded_models.keys())
print(f"✅ Loaded {len(loaded_models)} models: {', '.join(targets) if targets else 'none'}")

# ---------- POSITION TO ROLE MAPPING ----------
POSITION_ROLE = {
    "ST": "attacker", "LW": "attacker", "RW": "attacker", "CAM": "midfielder",
    "CDM": "midfielder", "CB": "defender", "LB": "defender", "RB": "defender",
    "LWB": "defender", "RWB": "defender", "GK": "goalkeeper"
}

# ---------- GENERATE ROLE-BASED PREDICTIONS ----------
for player in players_list:
    flat = player["flat"]
    preds = {}
    for key in loaded_models.keys():
        model_data = loaded_models[key]
        X = pd.DataFrame([flat])
        for f in model_data["features"]:
            if f not in X.columns:
                X[f] = 0
        X = X[model_data["features"]]
        X_scaled = model_data["scaler"].transform(X)
        pred_value = float(model_data["model"].predict(X_scaled)[0])
        preds[key] = pred_value
    player["predictions"] = preds

# ---------- ABSOLUTE RESCALING ACROSS ALL PLAYERS ----------
for role in loaded_models.keys():
    values = [p["predictions"][role] for p in players_list]
    min_val = min(values)
    max_val = max(values)
    for player in players_list:
        raw = player["predictions"][role]
        if max_val - min_val < 1e-6:
            scaled = 50.0
        else:
            scaled = 100.0 * (raw - min_val) / (max_val - min_val)
        player["predictions"][role] = round(scaled, 2)

# ---------- INSIGHT AND BADGE GENERATION ----------
def overall_insight(player_name, stats, preds):
    position = stats.get("position", "Unknown")
    role = POSITION_ROLE.get(position, "midfielder")
    rating = round(preds.get(role, 50), 2)
    level = "Excellent" if rating >= 85 else "Strong" if rating >= 70 else "Developing" if rating >= 50 else "Struggling"
    focus = {
        "attacker": "attacking output and creativity",
        "midfielder": "overall team contribution and transitions",
        "defender": "defensive contribution and positioning",
        "goalkeeper": "goalkeeping performance and command of area"
    }.get(role, "overall team play")
    summary_templates = {
        "Excellent": f"{player_name} delivered an outstanding {focus}, showing consistency across match phases with an overall rating of {rating}.",
        "Strong": f"{player_name} showed strong {focus}, contributing effectively to team structure with an overall rating of {rating}.",
        "Developing": f"{player_name} displayed moments of promise in {focus} but can aim for greater consistency with an overall rating of {rating}.",
        "Struggling": f"{player_name} had a challenging match but maintained effort in {focus}, ending with an overall rating of {rating}.",
    }
    return summary_templates[level]

def category_badge(role, stats, rating):
    label = "Excellent" if rating >= 85 else "Good" if rating >= 70 else "Needs Work" if rating >= 50 else "Urgent"
    reason = ""
    if role == "attacker":
        shots = stats.get("attack", {}).get("shots", 0)
        goals = stats.get("attack", {}).get("goals", 0)
        reason = f"Strong attacking performance. Goals: {goals}, Shots: {shots}" if shots or goals else "Good attacking awareness."
    elif role == "midfielder":
        key_passes = stats.get("distribution", {}).get("key_passes", 0)
        progressive = stats.get("distribution", {}).get("progressive_passes", 0)
        reason = f"Key passes: {key_passes}, Progressive passes: {progressive}"
    elif role == "defender":
        duels = stats.get("defense", {}).get("duels_won", 0)
        recoveries = stats.get("defense", {}).get("recoveries", 0)
        reason = f"Duels won: {duels}, Recoveries: {recoveries}"
    elif role == "goalkeeper":
        saves = stats.get("goalkeeper", {}).get("saves", 0)
        reason = f"Saves: {saves}" if saves else "Maintained goalkeeping duties effectively."
    return f"{label}: {reason}"

def overall_badge(preds):
    rating = preds.get("overall", 50)
    if rating >= 85:
        return f"Excellent: Standout performer with elite consistency. Overall rating {rating:.1f}."
    elif rating >= 70:
        return f"Good: Reliable and impactful across multiple areas. Overall rating {rating:.1f}."
    elif rating >= 50:
        return f"Needs Work: Showing progress but needs greater consistency. Overall rating {rating:.1f}."
    else:
        return f"Urgent: Underperformed; needs to raise tactical and technical output. Overall rating {rating:.1f}."

# ---------- GENERATE INSIGHTS AND BADGES ----------
for player in players_list:
    stats = player["raw"]
    preds = player["predictions"]
    role = POSITION_ROLE.get(stats.get("position", ""), "midfielder")
    rating = preds.get(role, 50)
    player["insights"] = {
        role: overall_insight(player["name"], stats, preds),
        "overall": overall_insight(player["name"], stats, preds)
    }
    player["badges"] = {
        role: category_badge(role, stats, rating),
        "overall": overall_badge(preds)
    }

# ---------- TOP PERFORMERS ----------
def extract_top(role):
    # sort by rescaled predictions
    sorted_players = sorted(players_list, key=lambda x: x["predictions"].get(role, 0), reverse=True)
    top3 = sorted_players[:3]

    def make_highlight(p, r):
        stats = p["raw"]
        if role == "attacker":
            goals = stats.get("attack", {}).get("goals", 0)
            shots = stats.get("attack", {}).get("shots", 0)
            return f"Goals: {goals}, Shots: {shots}" if shots or goals else ""
        elif role == "defender":
            duels = stats.get("defense", {}).get("duels_won", 0)
            rec = stats.get("defense", {}).get("recoveries", 0)
            return f"Duels won: {duels}, Recoveries: {rec}" if duels or rec else ""
        return ""

    return [
        {
            "name": p["name"],
            "rating": round(p["predictions"].get(role, 0), 2),
            "highlight": make_highlight(p, role)
        }
        for p in top3
    ]

# Only top attackers and top defenders
top_attackers = extract_top("sanbeda_role_attacker")
top_defenders = extract_top("sanbeda_role_defender")

# ---------- FINAL OUTPUT ----------
output = {
    "team": TEAM_NAME,
    "generated_at": datetime.now(timezone.utc).isoformat(),
    "players": players_list,
    "top_attackers": top_attackers,
    "top_defenders": top_defenders
}


with open(OUTPUT_PATH, "w", encoding="utf-8") as f:
    json.dump(output, f, indent=4)

print(f"\n✅ Player insights generated: {OUTPUT_PATH}")