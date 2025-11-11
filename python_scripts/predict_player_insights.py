# ============================================================
# File: python_scripts/predict_player_insights.py
# Purpose: Generate performance predictions for each player
# ============================================================

import json
import os
import pickle
import numpy as np
import pandas as pd
from datetime import datetime

TEAM_NAME = "San Beda"
MODELS_DIR = "python_scripts/models"
DERIVED_PATH = f"data/{TEAM_NAME.lower()}_players_derived_metrics.json"
OUTPUT_PATH = f"python_scripts/{TEAM_NAME.lower()}_player_insights.json"

CATEGORY_MODELS = {
    "general": "sanbeda_general.pkl",
    "attack": "sanbeda_attack.pkl",
    "defense": "sanbeda_defense.pkl",
    "distribution": "sanbeda_distribution.pkl",
    "discipline": "sanbeda_discipline.pkl",
    "overall": "sanbeda_overall.pkl"
}

# ---------- LOAD MODELS ----------
def load_models():
    models = {}
    for cat, filename in CATEGORY_MODELS.items():
        path = os.path.join(MODELS_DIR, filename)
        if os.path.exists(path):
            with open(path, "rb") as f:
                models[cat] = pickle.load(f)
    print(f"✅ Loaded {len(models)} models.")
    return models


# ---------- LOAD PLAYER METRICS ----------
def load_player_data():
    if not os.path.exists(DERIVED_PATH):
        raise FileNotFoundError(f"❌ Missing player metrics file: {DERIVED_PATH}")

    with open(DERIVED_PATH, "r") as f:
        data = json.load(f)

    return data[TEAM_NAME]


# ---------- PREDICT FOR EACH PLAYER ----------
def predict_player(models, features):
    df = pd.DataFrame([features])
    results = {}

    for cat in ["general", "attack", "defense", "distribution", "discipline"]:
        if cat in models:
            try:
                results[cat] = round(float(models[cat].predict(df)[0]), 2)
            except Exception:
                results[cat] = None
        else:
            results[cat] = None

    # Compute overall
    if "overall" in models:
        overall_features = np.array([
            results.get("attack", 0),
            results.get("defense", 0),
            results.get("distribution", 0),
            results.get("discipline", 0),
            results.get("general", 0)
        ]).reshape(1, -1)
        results["overall"] = round(float(models["overall"].predict(overall_features)[0]), 2)
    else:
        results["overall"] = round(np.mean([v for v in results.values() if v is not None]), 2)

    return results


# ---------- MAIN ----------
if __name__ == "__main__":
    print(f"⚽ Generating player-level performance insights for {TEAM_NAME}...")
    models = load_models()
    players_data = load_player_data()

    all_player_insights = {}
    for player, categories in players_data.items():
        # Flatten features
        features = {}
        for cat, sub in categories.items():
            for key, val in sub.items():
                features[f"{cat}__{key}"] = val

        preds = predict_player(models, features)
        all_player_insights[player] = preds

    # ---------- SAVE ----------
    output = {
        "team": TEAM_NAME,
        "timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        "player_insights": all_player_insights
    }

    with open(OUTPUT_PATH, "w") as f:
        json.dump(output, f, indent=4)

    print(f"✅ Player insights saved to: {OUTPUT_PATH}")
    print(json.dumps(output, indent=4))