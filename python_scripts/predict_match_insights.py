import os
import json
import pickle
import numpy as np
import pandas as pd
from datetime import datetime

# ---------- PATHS ----------
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DATA_DIR = os.path.join(BASE_DIR, "..", "python_scripts")
MODELS_DIR = os.path.join(BASE_DIR, "models")

INPUT_METRICS_PATH = os.path.join(DATA_DIR, "sanbeda_team_derived_metrics.json")
OUTPUT_INSIGHTS_PATH = os.path.join(BASE_DIR, "sanbeda_match_insights.json")

# ---------- LOAD MODELS ----------
def load_models(models_dir):
    models = {}
    for fname in os.listdir(models_dir):
        if fname.endswith(".pkl"):
            category = fname.replace("sanbeda_", "").replace(".pkl", "")
            path = os.path.join(models_dir, fname)
            with open(path, "rb") as f:
                models[category] = pickle.load(f)
    return models


# ---------- FLATTEN JSON (from derived_metrics.py output) ----------
def flatten_metrics(metrics_json):
    flattened = {}
    for category, submetrics in metrics_json.items():
        if isinstance(submetrics, dict):
            for key, val in submetrics.items():
                flattened[f"{category}__{key}"] = val
    return flattened


# ---------- PREDICTION PIPELINE ----------
def predict_performance():
    print("📂 Loading derived match metrics...")
    if not os.path.exists(INPUT_METRICS_PATH):
        print(f"❌ Error: {INPUT_METRICS_PATH} not found. Run derived_metrics.py first.")
        return

    with open(INPUT_METRICS_PATH, "r") as f:
        data = json.load(f)

    # Expect structure like {"San Beda": {...}}
    team_name = list(data.keys())[0]
    team_metrics = data[team_name]

    print(f"🔍 Loaded metrics for team: {team_name}")

    # Flatten and convert to DataFrame
    X_input = pd.DataFrame([flatten_metrics(team_metrics)])

    # Load models
    models = load_models(MODELS_DIR)
    print(f"🧠 Loaded {len(models)} trained models: {', '.join(models.keys())}")

    results = {}
    for category, model in models.items():
        try:
            preds = model.predict(X_input)
            results[category] = float(np.clip(preds[0], 0, 100))  # normalize 0–100 scale
        except Exception as e:
            print(f"⚠️ Prediction failed for {category}: {e}")
            results[category] = None

    # Compute weighted overall score if not already predicted
    if "overall" not in results or results["overall"] is None:
        results["overall"] = round((
            (results.get("attack", 0) * 0.25) +
            (results.get("defense", 0) * 0.25) +
            (results.get("distribution", 0) * 0.20) +
            (results.get("discipline", 0) * 0.10) +
            (results.get("general", 0) * 0.20)
        ), 2)

    # Generate qualitative insights
    insights = {
        "team": team_name,
        "timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        "predicted_scores": results,
        "interpretation": {
            "attack": interpret_score(results.get("attack")),
            "defense": interpret_score(results.get("defense")),
            "distribution": interpret_score(results.get("distribution")),
            "discipline": interpret_score(results.get("discipline")),
            "general": interpret_score(results.get("general")),
            "overall": interpret_score(results.get("overall")),
        }
    }

    # Save results
    with open(OUTPUT_INSIGHTS_PATH, "w") as f:
        json.dump(insights, f, indent=4)

    print(f"✅ Match performance insights saved to: {OUTPUT_INSIGHTS_PATH}")
    print(json.dumps(insights, indent=4))


# ---------- HELPER: Score Interpretation ----------
def interpret_score(score):
    if score is None:
        return "No prediction available"
    if score >= 85:
        return "Outstanding performance"
    elif score >= 70:
        return "Strong performance"
    elif score >= 55:
        return "Average, with room for improvement"
    else:
        return "Needs significant improvement"


# ---------- MAIN ----------
if __name__ == "__main__":
    predict_performance()