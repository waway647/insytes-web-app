# python_scripts/regression_model.py
import json
import pandas as pd
import os
import pickle
import numpy as np
from datetime import datetime, timezone

# ML libs
from sklearn.linear_model import LinearRegression
from sklearn.metrics import r2_score

# Try to import XGBoost; fallback to linear regression if missing
try:
    from xgboost import XGBRegressor
    XGBOOST_AVAILABLE = True
except Exception:
    XGBOOST_AVAILABLE = False

# Try to import shap for explainability (optional)
try:
    import shap
    SHAP_AVAILABLE = True
except Exception:
    SHAP_AVAILABLE = False

# ---------- CONFIG ----------
INPUT_JSON = "python_scripts/sanbeda_derived_metrics.json"
TEAM_NAME = "San Beda"
OUTPUT_TRAIN_PATH = "data/sanbeda_training_data.csv"
OUTPUT_MODEL_DIR = "python_scripts/models"
OUTPUT_MODEL_PREFIX = "sanbeda"  # models saved as sanbeda_general.pkl, etc.
OUTPUT_RESULTS_JSON = "python_scripts/model_results.json"
INSIGHTS_PATH = "python_scripts/performance_insights.json"
SHAP_SUMMARY_PATH = "python_scripts/shap_summary.json"

USE_XGBOOST = True and XGBOOST_AVAILABLE  # set False to force LinearRegression
MIN_REAL_MATCHES_FOR_NO_SIM = 5  # if real matches >= this, DO NOT simulate

RANDOM_SEED = 42
np.random.seed(RANDOM_SEED)


# ---------- HELPERS ----------
def flatten_dict(d, parent_key="", sep="__"):
    """Flatten nested dict to single level with separator"""
    items = []
    for k, v in d.items():
        new_key = f"{parent_key}{sep}{k}" if parent_key else k
        if isinstance(v, dict):
            items.extend(flatten_dict(v, new_key, sep=sep).items())
        else:
            items.append((new_key, v))
    return dict(items)


def compute_composite_scores(flat_row):
    """
    Compute fallback composite scores (general, attack, defense) from flattened row.
    Returns scores on 0-100 scale.
    """
    get = lambda k, default=0.0: float(flat_row.get(k, default) or 0.0)

    # Attack composite: prioritized on finishing & shot quality
    attack_score = (
        0.35 * get("attack__goal_conversion_pct") +
        0.25 * get("attack__shot_accuracy_pct") +
        0.15 * get("attack__key_passes") +     # numeric count influence
        0.15 * (get("attack__shots_on_target")) +  # raw shot quality
        0.10 * get("attack__shots") * 0.01
    )

    # Defense composite: tackling, recoveries, clearances
    defense_score = (
        0.35 * get("defense__tackles_success_rate_pct") +
        0.25 * (get("defense__recoveries") * 0.5) +  # scale raw counts
        0.20 * get("defense__recoveries_attacking_third_pct") +
        0.10 * (get("defense__clearances") * 0.3) +
        0.10 * (100 - get("discipline__fouls_conceded", 0)) * 0.01  # fewer fouls better
    )

    # General/balanced composite: possession + passing + duels
    general_score = (
        0.35 * get("distribution__passing_accuracy_pct") +
        0.25 * get("possession__possession_pct") +
        0.2 * get("general__duels_success_rate_pct") +
        0.15 * get("distribution__passes") * 0.01 +
        0.05 * (100 - get("discipline__fouls_conceded", 0)) * 0.01
    )

    # Clip to 0-100
    attack_score = float(np.clip(attack_score, 0, 100))
    defense_score = float(np.clip(defense_score, 0, 100))
    general_score = float(np.clip(general_score, 0, 100))

    return general_score, attack_score, defense_score


# --- NEW: compute category ratings using same formulas as derived-metrics script
def compute_category_ratings_from_flat(flat):
    """
    Given a flattened match dictionary (keys like 'attack__goal_conversion_pct'), compute:
    - match_rating_attack
    - match_rating_defense
    - match_rating_distribution
    - match_rating_general
    - match_rating_discipline
    All returned on 0-10 scale (float).
    These formulas mirror the ones in your derived metrics script.
    """
    get = lambda k, default=0.0: float(flat.get(k, default) or 0.0)

    # Attack rating (scale/weights) -> base 0-100 then divided by 10 => 0-10
    atk_score = (
        0.30 * get("attack__goal_conversion_pct") +
        0.25 * get("attack__shot_accuracy_pct") +
        0.20 * get("attack__key_passes") +
        0.15 * get("possession__attacking_third_possession_pct_of_team") +
        0.10 * get("attack__shots_on_target")
    )
    match_rating_attack = float(np.clip(atk_score / 10.0, 0, 10))

    # Defense rating
    def_score = (
        0.35 * get("defense__tackles_success_rate_pct") +
        0.25 * get("defense__interceptions") +
        0.20 * get("defense__recoveries_attacking_third_pct") +
        0.10 * get("defense__clearances") -
        0.10 * (get("discipline__yellow_cards") + get("discipline__red_cards"))
    )
    match_rating_defense = float(np.clip(def_score / 10.0, 0, 10))

    # Distribution rating (use successful/intercepted/unsuccessful composition if available)
    passes = max(get("distribution__passes"), 1.0)
    success_rate = (get("distribution__successful_passes") / passes) * 100.0 if passes > 0 else get("distribution__passing_accuracy_pct")
    intercept_rate = (get("distribution__intercepted_passes") / passes) * 100.0 if passes > 0 else 0.0
    unsuccess_rate = (get("distribution__unsuccessful_passes") / passes) * 100.0 if passes > 0 else (100.0 - success_rate)

    dist_score = (0.7 * success_rate) - (0.2 * intercept_rate) - (0.1 * unsuccess_rate)
    if get("distribution__passing_accuracy_pct") > 0:
        dist_score = 0.5 * get("distribution__passing_accuracy_pct") + 0.5 * (dist_score)

    match_rating_distribution = float(np.clip(dist_score / 10.0, 0, 10))

    # General rating
    gen_score = (
        0.40 * get("general__duels_success_rate_pct") +
        0.20 * get("possession__possession_pct") +
        0.20 * get("general__corner_awarded") -
        0.10 * (get("discipline__yellow_cards") + get("discipline__red_cards"))
    )
    match_rating_general = float(np.clip(gen_score / 10.0, 0, 10))

    # Discipline rating (inverse of fouls/cards)
    fouls = get("discipline__fouls_conceded")
    ycards = get("discipline__yellow_cards")
    rcards = get("discipline__red_cards")
    disc_penalty = (0.4 * fouls) + (0.8 * ycards) + (1.5 * rcards)
    match_rating_discipline = float(np.clip(10.0 - (disc_penalty / 10.0), 0, 10))

    # Round to 2 decimals to match JSON formatting used elsewhere
    return {
        "match_rating_attack": round(match_rating_attack, 2),
        "match_rating_defense": round(match_rating_defense, 2),
        "match_rating_distribution": round(match_rating_distribution, 2),
        "match_rating_general": round(match_rating_general, 2),
        "match_rating_discipline": round(match_rating_discipline, 2)
    }


def safe_mkdir(path):
    os.makedirs(path, exist_ok=True)


# ---------- LOAD & PREP ----------
print(f"📂 Loading match JSON(s) for {TEAM_NAME}...")

if not os.path.exists(INPUT_JSON):
    raise FileNotFoundError(f"❌ File not found: {INPUT_JSON}")

with open(INPUT_JSON, "r") as f:
    match_data = json.load(f)

if TEAM_NAME not in match_data:
    raise KeyError(f"❌ '{TEAM_NAME}' not found in sanbeda_derived_metrics.json keys: {list(match_data.keys())}")

team_metrics = match_data[TEAM_NAME]

# Accept both single-match dict or list of matches
if isinstance(team_metrics, dict):
    real_matches = [team_metrics]
else:
    # Could be already a list of match dicts
    real_matches = team_metrics

n_real = len(real_matches)
print(f"ℹ️ Real matches found for {TEAM_NAME}: {n_real}")

# ---------- SIMULATION (only when too few real matches) ----------
simulate = False
if n_real < MIN_REAL_MATCHES_FOR_NO_SIM:
    simulate = True
    print(f"⚠️ Only {n_real} real match(es) found. Simulating additional matches for testing and scalability...")

    base = real_matches[0] if n_real > 0 else {}
    simulated = []
    needed = max(MIN_REAL_MATCHES_FOR_NO_SIM - n_real, 50)

    for i in range(needed):
        varied = json.loads(json.dumps(base)) if base else {}
        for cat, sub in varied.items():
            if isinstance(sub, dict):
                for key, val in sub.items():
                    if isinstance(val, (int, float)):
                        # More realistic variability depending on feature type
                        if "shots" in key or "goals" in key:
                            varied[cat][key] = int(np.random.randint(0, 10))
                        elif "passes" in key or "duels" in key:
                            varied[cat][key] = int(np.random.randint(100, 500))
                        elif "recoveries" in key or "tackles" in key:
                            varied[cat][key] = int(np.random.randint(20, 100))
                        elif "possession" in key:
                            varied[cat][key] = round(np.random.uniform(30, 70), 2)
                        else:
                            varied[cat][key] = round(np.random.uniform(0, 50), 2)
                            
                    # 🔧 Apply ±20% variability factor to any numeric stat
                    if isinstance(varied[cat][key], (int, float)):
                        factor = np.random.uniform(0.8, 1.2)  # ±20% variance
                        varied[cat][key] = round(varied[cat][key] * factor, 2)


        simulated.append(varied)

    # Convert simulated dicts to flat DataFrame-like rows
    flattened = []
    for match in simulated:
        row = {}
        for cat, sub in match.items():
            if isinstance(sub, dict):
                for key, val in sub.items():
                    row[f"{cat}__{key}"] = val
        # Create synthetic ratings with partial correlation + noise
        base_score = np.random.uniform(60, 90)

        # Category weights simulate real contribution patterns
        attack_factor = np.random.uniform(0.8, 1.2)
        defense_factor = np.random.uniform(0.7, 1.1)
        dist_factor = np.random.uniform(0.9, 1.3)
        discip_factor = np.random.uniform(0.8, 1.2)
        general_factor = np.random.uniform(0.85, 1.15)

        row.update({
            "match_rating_attack": round(base_score * attack_factor + np.random.uniform(-15, 15)
, 2),
            "match_rating_defense": round(base_score * defense_factor + np.random.uniform(-15, 15)
, 2),
            "match_rating_distribution": round(base_score * dist_factor + np.random.uniform(-15, 15)
, 2),
            "match_rating_discipline": round(base_score * discip_factor + np.random.uniform(-15, 15)
, 2),
            "match_rating_general": round(base_score * general_factor + np.random.uniform(-15, 15)
, 2)
        })

        # Overall rating as weighted average with randomness
        row["match_rating_overall"] = round((
            row["match_rating_attack"] * 0.25 +
            row["match_rating_defense"] * 0.25 +
            row["match_rating_distribution"] * 0.20 +
            row["match_rating_discipline"] * 0.10 +
            row["match_rating_general"] * 0.20
        ) / 1.0 + np.random.uniform(-5, 5), 2)

        flattened.append(row)

    all_matches = real_matches + flattened
else:
    all_matches = real_matches

print(f"ℹ️ Total matches used for training: {len(all_matches)} (simulate={simulate})")

# ---------- FLATTEN MATCHES INTO DF ----------
# Determine starting match_id offset if training CSV already exists (so we append safely)
start_id = 1
if os.path.exists(OUTPUT_TRAIN_PATH):
    try:
        existing_df = pd.read_csv(OUTPUT_TRAIN_PATH)
        if "match_id" in existing_df.columns and not existing_df.empty:
            start_id = int(existing_df["match_id"].max()) + 1
        else:
            start_id = len(existing_df) + 1
        print(f"ℹ️ Existing training CSV found. New match_id will start at {start_id}.")
    except Exception:
        # if any problem reading existing file, fallback
        start_id = 1

# record real match ids (they will be assigned sequentially from start_id)
real_match_ids = list(range(start_id, start_id + n_real))

flattened = []
for i, match in enumerate(all_matches, start=start_id):
    flat = flatten_dict(match)
    flat["match_id"] = i
    flat["team_name"] = TEAM_NAME

    # If JSON already contained per-category match_rating_* use them.
    # If not, compute them from stats (use compute_category_ratings_from_flat).
    if not any(k in flat for k in ("match_rating_general", "match_rating_attack", "match_rating_defense")):
        computed = compute_category_ratings_from_flat(flat)
        # store per-category ratings on 0-10 scale
        flat.update(computed)
        # also compute an overall weighted match_rating consistent with derived script's weighted_overall
        weighted_overall = (
            0.25 * computed["match_rating_attack"] +
            0.25 * computed["match_rating_defense"] +
            0.20 * computed["match_rating_distribution"] +
            0.15 * computed["match_rating_general"] +
            0.15 * computed["match_rating_discipline"]
        )
        flat["match_rating"] = round(float(np.clip(weighted_overall, 0, 10)), 2)
    else:
        # If provided in JSON, ensure match_rating exists (fallback to average)
        if "match_rating" not in flat:
            vals = []
            for k in ("match_rating_attack", "match_rating_defense", "match_rating_general"):
                if k in flat:
                    try:
                        vals.append(float(flat[k]))
                    except Exception:
                        pass
            flat["match_rating"] = round(np.mean(vals) if vals else 0.0, 2)

    # Also compute score_* composites (0-100) used as fallback targets if desired
    g, a, d = compute_composite_scores(flat)
    flat["score_general"] = g
    flat["score_attack"] = a
    flat["score_defense"] = d

    flattened.append(flat)

df = pd.DataFrame(flattened)

# If existing training CSV exists, append only new rows (by match_id)
if os.path.exists(OUTPUT_TRAIN_PATH):
    try:
        existing_df = pd.read_csv(OUTPUT_TRAIN_PATH)
        # combine and drop duplicates on match_id and team_name
        combined = pd.concat([existing_df, df], ignore_index=True, sort=False)
        combined = combined.drop_duplicates(subset=["match_id", "team_name"], keep="first")
        df = combined.reset_index(drop=True)
        print(f"ℹ️ Appended to existing training CSV; total rows now: {len(df)}")
    except Exception as e:
        print("⚠️ Failed to append to existing CSV (will overwrite). Error:", str(e))
        # fallback: keep new df only

# ---------- TARGET SELECTION: use data-driven category ratings if present else fallback to computed scores ----------
# We will prefer match_rating_general / _attack / _defense (0-10) if present; otherwise use score_* (0-100)
def choose_target_columns(df):
    # prefer per-category match_rating columns if present (0-10 scale)
    if "match_rating_general" in df.columns:
        tg = "match_rating_general"
    else:
        tg = "score_general"

    if "match_rating_attack" in df.columns:
        ta = "match_rating_attack"
    else:
        ta = "score_attack"

    if "match_rating_defense" in df.columns:
        td = "match_rating_defense"
    else:
        td = "score_defense"

    # also support distribution and discipline as targets (new)
    if "match_rating_distribution" in df.columns:
        tdistr = "match_rating_distribution"
    else:
        tdistr = None  # we will not train distribution separately if no column or compute fallback later

    if "match_rating_discipline" in df.columns:
        tdisc = "match_rating_discipline"
    else:
        tdisc = None

    return tg, ta, td, tdistr, tdisc

target_general_col, target_attack_col, target_defense_col, target_distribution_col, target_discipline_col = choose_target_columns(df)
print(f"🎯 Targets chosen -> general: {target_general_col}, attack: {target_attack_col}, defense: {target_defense_col}, distribution: {target_distribution_col}, discipline: {target_discipline_col}")

# Ensure the target columns exist (should, but be defensive)
for col in [target_general_col, target_attack_col, target_defense_col]:
    if col not in df.columns:
        # create from computed scores
        if col.startswith("match_rating") and col.replace("match_rating_", "score_") in df.columns:
            df[col] = df[col.replace("match_rating_", "score_")]
        else:
            df[col] = 0.0

# If distribution/discipline targets missing, create them from computed category ratings we generated earlier
if target_distribution_col is None:
    df["match_rating_distribution"] = df.get("score_general", 0.0) * 0.1  # placeholder if truly missing (rare)
    target_distribution_col = "match_rating_distribution"

if target_discipline_col is None:
    # discipline ratings exist from compute_category_ratings_from_flat if not in JSON, so ensure present
    if "match_rating_discipline" not in df.columns:
        # approximate discipline from discipline__fouls_conceded / cards
        def approx_discipline(row):
            fouls = float(row.get("discipline__fouls_conceded", 0.0) or 0.0)
            y = float(row.get("discipline__yellow_cards", 0.0) or 0.0)
            r = float(row.get("discipline__red_cards", 0.0) or 0.0)
            penalty = (0.4 * fouls) + (0.8 * y) + (1.5 * r)
            return float(max(0.0, min(10.0, 10.0 - (penalty / 10.0))))
        df["match_rating_discipline"] = df.apply(approx_discipline, axis=1)
    target_discipline_col = "match_rating_discipline"

# ---------- SAVE TRAINING DATA ----------
safe_mkdir(os.path.dirname(OUTPUT_TRAIN_PATH) or ".")
df.to_csv(OUTPUT_TRAIN_PATH, index=False)
print(f"\n✅ Training dataset saved to: {OUTPUT_TRAIN_PATH}")

# ---------- FEATURE MATRIX ----------
# Build X from all numeric columns except match identifiers and the category targets (for per-category models)
exclude = {
    "match_id", "team_name",
    target_general_col, target_attack_col, target_defense_col,
    # exclude raw match_rating_* from features (we're predicting them)
    "match_rating", "match_rating_general", "match_rating_attack", "match_rating_defense",
    # exclude composite score columns if they are used as targets
    "score_general", "score_attack", "score_defense",
    # leave match_rating_distribution and match_rating_discipline excluded as well (we will predict them separately)
    "match_rating_distribution", "match_rating_discipline"
}
numeric_cols = []
for c in df.columns:
    if c in exclude:
        continue
    # keep numeric inputs only
    try:
        # try coercing to numeric to check if usable
        pd.to_numeric(df[c], errors='raise')
        numeric_cols.append(c)
    except Exception:
        # non-numeric column (e.g., team_name string) -> skip
        continue

X = df[numeric_cols].fillna(0.0).astype(float)
# Target series (per-category)
y_general = df[target_general_col].astype(float).fillna(0.0)
y_attack = df[target_attack_col].astype(float).fillna(0.0)
y_defense = df[target_defense_col].astype(float).fillna(0.0)
y_distribution = df[target_distribution_col].astype(float).fillna(0.0)
y_discipline = df[target_discipline_col].astype(float).fillna(0.0)

print(f"ℹ️ Features used ({len(X.columns)}): {list(X.columns)[:8]}{'...' if len(X.columns)>8 else ''}")

# ---------- TRAIN FUNCTION ----------
def train_and_save_model(X, y, target_name, use_xgb=USE_XGBOOST, prefix=OUTPUT_MODEL_PREFIX):
    safe_mkdir(OUTPUT_MODEL_DIR)
    model_path = os.path.join(OUTPUT_MODEL_DIR, f"{prefix}_{target_name}.pkl")
    if use_xgb and XGBOOST_AVAILABLE:
        model = XGBRegressor(
            n_estimators=250,
            learning_rate=0.08,
            max_depth=5,
            subsample=0.9,
            colsample_bytree=0.9,
            random_state=RANDOM_SEED,
            verbosity=0
        )
        model_name = "XGBoost"
    else:
        model = LinearRegression()
        model_name = "LinearRegression"

    print(f"🚀 Training {model_name} model for target '{target_name}' (rows={len(X)})...")
    # handle degenerate case (no features) gracefully
    if X.shape[1] == 0:
        print("⚠️ No numeric features found. Skipping training and returning defaults.")
        return {
            "model_path": None,
            "model_name": model_name,
            "r2": 0.0,
            "feature_importance_df": pd.DataFrame(columns=["feature", "coefficient"]),
            "model_obj": None,
            "preds": np.array([])
        }

    model.fit(X, y)
    preds = model.predict(X)
    # r2 may be nan if constant; handle
    try:
        r2 = float(r2_score(y, preds)) if len(y) > 0 else float("nan")
    except Exception:
        r2 = 0.0

    # Save model
    if model is not None:
        with open(model_path, "wb") as f:
            pickle.dump(model, f)
        print(f"✅ Model saved to: {model_path} (R2={r2:.4f})")
    else:
        print("⚠️ No model to save.")

    # Get feature importance or coef
    if use_xgb and XGBOOST_AVAILABLE and model is not None:
        importance = getattr(model, "feature_importances_", None)
        feat_imp = list(zip(X.columns.tolist(), importance.tolist() if importance is not None else [0.0]*X.shape[1]))
    else:
        coef = getattr(model, "coef_", None)
        feat_imp = list(zip(X.columns.tolist(), coef.tolist() if coef is not None else [0.0]*X.shape[1]))

    # Build DataFrame of importances (sort by abs)
    feat_df = pd.DataFrame(feat_imp, columns=["feature", "coefficient"])
    feat_df = feat_df.sort_values(by="coefficient", key=lambda s: np.abs(s), ascending=False)

    return {
        "model_path": model_path,
        "model_name": model_name,
        "r2": r2,
        "feature_importance_df": feat_df,
        "model_obj": model,
        "preds": preds
    }

# ---------- TRAIN CATEGORY MODELS ----------
# create results holder BEFORE training
results = {"team": TEAM_NAME, "trained_at": datetime.now(timezone.utc).isoformat(), "models": {}}

# train attack/defense/general/distribution/discipline using the same feature set X
general_res = train_and_save_model(X, y_general, "general")
attack_res = train_and_save_model(X, y_attack, "attack")
defense_res = train_and_save_model(X, y_defense, "defense")
distribution_res = train_and_save_model(X, y_distribution, "distribution")
discipline_res = train_and_save_model(X, y_discipline, "discipline")

# Populate results for category models
results["models"]["general"] = {
    "model_type": general_res["model_name"],
    "model_path": general_res["model_path"],
    "r2": round(general_res["r2"], 4),
    "top_features": general_res["feature_importance_df"].head(10).to_dict(orient="records")
}
results["models"]["attack"] = {
    "model_type": attack_res["model_name"],
    "model_path": attack_res["model_path"],
    "r2": round(attack_res["r2"], 4),
    "top_features": attack_res["feature_importance_df"].head(10).to_dict(orient="records")
}
results["models"]["defense"] = {
    "model_type": defense_res["model_name"],
    "model_path": defense_res["model_path"],
    "r2": round(defense_res["r2"], 4),
    "top_features": defense_res["feature_importance_df"].head(10).to_dict(orient="records")
}
results["models"]["distribution"] = {
    "model_type": distribution_res["model_name"],
    "model_path": distribution_res["model_path"],
    "r2": round(distribution_res["r2"], 4),
    "top_features": distribution_res["feature_importance_df"].head(10).to_dict(orient="records")
}
results["models"]["discipline"] = {
    "model_type": discipline_res["model_name"],
    "model_path": discipline_res["model_path"],
    "r2": round(discipline_res["r2"], 4),
    "top_features": discipline_res["feature_importance_df"].head(10).to_dict(orient="records")
}

# ---------- BUILD & TRAIN OVERALL MODEL ----------
# Overall model inputs: the 5 category ratings (ensure columns exist and are numeric)
cat_cols = [
    "match_rating_attack",
    "match_rating_defense",
    "match_rating_distribution",
    "match_rating_general",
    "match_rating_discipline"
]
# ensure these columns exist in df (they should, from earlier computation)
for c in cat_cols:
    if c not in df.columns:
        df[c] = 0.0

X_overall = df[cat_cols].astype(float).fillna(0.0)
y_overall = df["match_rating"].astype(float).fillna(0.0)  # overall team rating is target

# Train overall model (saved as prefix_overall.pkl)
overall_res = train_and_save_model(X_overall, y_overall, "overall", prefix=OUTPUT_MODEL_PREFIX)

# Add overall model result entry
results["models"]["overall"] = {
    "model_type": overall_res["model_name"],
    "model_path": overall_res["model_path"],
    "r2": round(overall_res["r2"], 4),
    "top_features": overall_res["feature_importance_df"].head(10).to_dict(orient="records")
}

# ---------- PREDICTIONS FOR (REAL) MATCHES ----------
# Build full predictions table aligned with df rows (only if preds present)
predictions_output = []
try:
    # preds arrays correspond to X rows (in same order) for per-category models (they were trained on X)
    preds_general = general_res.get("preds", np.array([]))
    preds_attack = attack_res.get("preds", np.array([]))
    preds_defense = defense_res.get("preds", np.array([]))
    preds_distribution = distribution_res.get("preds", np.array([]))
    preds_discipline = discipline_res.get("preds", np.array([]))
    preds_overall = overall_res.get("preds", np.array([]))

    # assemble preds_all DataFrame - use appropriate columns depending on availability
    if preds_general.size and preds_attack.size and preds_defense.size and preds_distribution.size and preds_discipline.size and preds_overall.size:
        preds_all = df[["match_id", "team_name"]].copy().reset_index(drop=True)
        preds_all["pred_general"] = preds_general
        preds_all["pred_attack"] = preds_attack
        preds_all["pred_defense"] = preds_defense
        preds_all["pred_distribution"] = preds_distribution
        preds_all["pred_discipline"] = preds_discipline
        preds_all["pred_overall"] = preds_overall

        # collect predictions only for the real matches you provided (their match_id values)
        real_preds = preds_all[preds_all["match_id"].isin(real_match_ids)]
        for _, r in real_preds.iterrows():
            predictions_output.append({
                "match_id": int(r["match_id"]),
                "team_name": r["team_name"],
                # keep predicted scales as-is (these match the training target scale)
                "predicted_match_rating_general": float(round(r["pred_general"], 4)),
                "predicted_match_rating_attack": float(round(r["pred_attack"], 4)),
                "predicted_match_rating_defense": float(round(r["pred_defense"], 4)),
                "predicted_match_rating_distribution": float(round(r["pred_distribution"], 4)),
                "predicted_match_rating_discipline": float(round(r["pred_discipline"], 4)),
                "predicted_match_rating_overall": float(round(r["pred_overall"], 4))
            })
    else:
        # partial preds may exist; still attempt to build per-model prediction rows individually
        preds_all = df[["match_id", "team_name"]].copy().reset_index(drop=True)
        if preds_general.size:
            preds_all["pred_general"] = preds_general
        if preds_attack.size:
            preds_all["pred_attack"] = preds_attack
        if preds_defense.size:
            preds_all["pred_defense"] = preds_defense
        if preds_distribution.size:
            preds_all["pred_distribution"] = preds_distribution
        if preds_discipline.size:
            preds_all["pred_discipline"] = preds_discipline
        if preds_overall.size:
            preds_all["pred_overall"] = preds_overall

        real_preds = preds_all[preds_all["match_id"].isin(real_match_ids)]
        for _, r in real_preds.iterrows():
            out = {"match_id": int(r["match_id"]), "team_name": r["team_name"]}
            if "pred_general" in r:
                out["predicted_match_rating_general"] = float(round(r["pred_general"], 4))
            if "pred_attack" in r:
                out["predicted_match_rating_attack"] = float(round(r["pred_attack"], 4))
            if "pred_defense" in r:
                out["predicted_match_rating_defense"] = float(round(r["pred_defense"], 4))
            if "pred_distribution" in r:
                out["predicted_match_rating_distribution"] = float(round(r["pred_distribution"], 4))
            if "pred_discipline" in r:
                out["predicted_match_rating_discipline"] = float(round(r["pred_discipline"], 4))
            if "pred_overall" in r:
                out["predicted_match_rating_overall"] = float(round(r["pred_overall"], 4))
            predictions_output.append(out)
except Exception as e:
    print("⚠️ Failed to assemble predictions:", str(e))
    predictions_output = []

# ---------- SHAP (optional, best-effort) ----------
shap_summary = {}
# We'll compute SHAP where sensible: category-general (if XGBoost) and overall (if XGBoost)
if SHAP_AVAILABLE and XGBOOST_AVAILABLE:
    try:
        if general_res.get("model_obj") is not None and getattr(general_res["model_obj"], "__class__", None) is not None:
            try:
                print("🧾 Computing SHAP for general...")
                explainer = shap.TreeExplainer(general_res["model_obj"])
                shap_vals = explainer.shap_values(X)
                mean_abs = np.mean(np.abs(shap_vals), axis=0)
                shap_df = pd.DataFrame({"feature": X.columns, "mean_abs_shap": mean_abs})
                shap_df = shap_df.sort_values(by="mean_abs_shap", ascending=False)
                shap_summary["general"] = shap_df.head(20).to_dict(orient="records")
            except Exception as e:
                print("⚠️ SHAP for general failed:", str(e))

        if overall_res.get("model_obj") is not None and getattr(overall_res["model_obj"], "__class__", None) is not None:
            try:
                print("🧾 Computing SHAP for overall...")
                explainer_o = shap.TreeExplainer(overall_res["model_obj"])
                shap_vals_o = explainer_o.shap_values(X_overall)
                mean_abs_o = np.mean(np.abs(shap_vals_o), axis=0)
                shap_df_o = pd.DataFrame({"feature": X_overall.columns, "mean_abs_shap": mean_abs_o})
                shap_summary["overall"] = shap_df_o.sort_values(by="mean_abs_shap", ascending=False).head(20).to_dict(orient="records")
            except Exception as e:
                print("⚠️ SHAP for overall failed:", str(e))

        # Save shap summary if anything computed
        if shap_summary:
            with open(SHAP_SUMMARY_PATH, "w") as f:
                json.dump(shap_summary, f, indent=2)
            print(f"✅ SHAP summary saved to: {SHAP_SUMMARY_PATH}")
    except Exception as e:
        print("⚠️ SHAP analysis failed:", str(e))
        # don't disable SHAP globally; just skip
else:
    if not SHAP_AVAILABLE:
        print("ℹ️ shap not installed — skipping SHAP explainability.")
    if not XGBOOST_AVAILABLE:
        print("ℹ️ XGBoost not available — SHAP TreeExplainer requires tree model.")

# ---------- INSIGHTS GENERATION ----------
def summarize_category(feat_df, category_label, top_n=5):
    # Convert feature names -> more readable and filter by category prefix
    feats = feat_df.copy()
    # Filter only features that start with category_label__ if that category exists; else take top overall
    filtered = feats[feats["feature"].str.startswith(f"{category_label}__")]
    if filtered.empty:
        top = feats.head(top_n)
    else:
        top = filtered.head(top_n)
    # readable list
    readable = []
    for _, r in top.iterrows():
        name = r["feature"].split("__", 1)[1].replace("_", " ")
        coef = float(r["coefficient"])
        readable.append({"metric": name, "impact": round(float(coef), 6)})
    # short summary sentence generation
    if not readable:
        summary = f"{category_label.capitalize()} has neutral contribution (no top numeric features found)."
    else:
        top_metrics = ", ".join([r["metric"] for r in readable[:3]])
        summary = f"Top {category_label} contributors: {top_metrics}."
    return {"summary": summary, "top_metrics": readable}

insights = {"team": TEAM_NAME, "generated_at": datetime.now(timezone.utc).isoformat(), "categories": {}}

# Build per-category feature frames
gen_feat_df = general_res["feature_importance_df"]
atk_feat_df = attack_res["feature_importance_df"]
def_feat_df = defense_res["feature_importance_df"]
distr_feat_df = distribution_res["feature_importance_df"]
disc_feat_df = discipline_res["feature_importance_df"]

insights["categories"]["general"] = summarize_category(gen_feat_df, "distribution")
insights["categories"]["attack"] = summarize_category(atk_feat_df, "attack")
insights["categories"]["defense"] = summarize_category(def_feat_df, "defense")
insights["categories"]["distribution"] = summarize_category(distr_feat_df, "distribution")
insights["categories"]["discipline"] = summarize_category(disc_feat_df, "discipline")

# Overall model: compute category contribution percentages.
# Prefer SHAP if available (and shap+XGBoost computed for overall), otherwise fallback to normalized absolute coefficients.
def compute_category_contributions_from_coeffs(overall_model_res, feature_names):
    contributions = []
    feat_df = overall_model_res["feature_importance_df"]
    if feat_df.empty:
        return []
    feat_df = feat_df[feat_df["feature"].isin(feature_names)].copy()
    if feat_df.empty:
        return []
    feat_df["abs_imp"] = feat_df["coefficient"].abs()
    s = float(feat_df["abs_imp"].sum())
    if s == 0:
        # equal shares if all zero
        for _, r in feat_df.iterrows():
            contributions.append({"category": r["feature"], "contribution_pct": round(100.0 / len(feat_df), 2)})
    else:
        for _, r in feat_df.iterrows():
            contributions.append({"category": r["feature"], "contribution_pct": round((r["abs_imp"] / s) * 100.0, 2)})
    contributions = sorted(contributions, key=lambda x: x["contribution_pct"], reverse=True)
    return contributions

def compute_category_contributions_from_shap(shap_summary_overall):
    """
    shap_summary_overall: DataFrame-like iterable with columns 'feature' and 'mean_abs_shap'
    """
    contributions = []
    try:
        df_sh = pd.DataFrame(shap_summary_overall)
        if df_sh.empty:
            return []
        df_sh["abs_shap"] = df_sh["mean_abs_shap"].astype(float).abs()
        s = float(df_sh["abs_shap"].sum())
        if s == 0:
            for _, r in df_sh.iterrows():
                contributions.append({"category": r["feature"], "contribution_pct": round(100.0 / len(df_sh), 2)})
        else:
            for _, r in df_sh.iterrows():
                contributions.append({"category": r["feature"], "contribution_pct": round((r["abs_shap"] / s) * 100.0, 2)})
        contributions = sorted(contributions, key=lambda x: x["contribution_pct"], reverse=True)
        return contributions
    except Exception:
        return []

# compute category contributions (overall model uses cat_cols)
overall_contribs = []
# Try SHAP first if available and computed above
if SHAP_AVAILABLE and XGBOOST_AVAILABLE and shap_summary.get("overall"):
    try:
        overall_contribs = compute_category_contributions_from_shap(shap_summary["overall"])
        # map feature names to our category names (if necessary)
        # shap returns features as the same names as X_overall.columns (match_rating_*)
    except Exception:
        overall_contribs = compute_category_contributions_from_coeffs(overall_res, cat_cols)
else:
    overall_contribs = compute_category_contributions_from_coeffs(overall_res, cat_cols)

# also include within-category top metrics in the overall insights:
insights["overall"] = {
    "overall_model": {
        "model_type": overall_res["model_name"],
        "r2": round(overall_res["r2"], 4),
        "category_contributions": overall_contribs,
        "note": "Category contributions are derived from SHAP mean-absolute values when available (XGBoost+shap). Otherwise normalized absolute coefficients are used."
    },
    "by_category": {
        "general": insights["categories"]["general"]["top_metrics"],
        "attack": insights["categories"]["attack"]["top_metrics"],
        "defense": insights["categories"]["defense"]["top_metrics"],
        "distribution": insights["categories"]["distribution"]["top_metrics"],
        "discipline": insights["categories"]["discipline"]["top_metrics"]
    }
}

# Add top features to the overall summary (keeps backward compatibility)
insights["overall_summary"] = {
    "general_top": insights["categories"]["general"]["top_metrics"],
    "attack_top": insights["categories"]["attack"]["top_metrics"],
    "defense_top": insights["categories"]["defense"]["top_metrics"],
    "distribution_top": insights["categories"]["distribution"]["top_metrics"],
    "discipline_top": insights["categories"]["discipline"]["top_metrics"],
    "note": "Interpret coefficients / importances with caution for small datasets; retrain with more matches for robust models."
}

# Save insights together in one file (as requested)
with open(INSIGHTS_PATH, "w") as f:
    json.dump(insights, f, indent=2)
print(f"✅ Insights saved to: {INSIGHTS_PATH}")

# ---------- RESULTS OUTPUT ----------
# Collate results summary JSON
results_summary = {
    "team": TEAM_NAME,
    "trained_at": datetime.now(timezone.utc).isoformat(),
    "simulate_used": simulate,
    "n_real_matches": n_real,
    "n_used_for_training": len(df),
    "models": results["models"],
    "shap_available": SHAP_AVAILABLE,
    "insights_path": INSIGHTS_PATH,
    "training_dataset": OUTPUT_TRAIN_PATH,
    # include the model predictions for the real match(es)
    "predictions": predictions_output
}

with open(OUTPUT_RESULTS_JSON, "w") as f:
    json.dump(results_summary, f, indent=2)
print(f"✅ Model results saved to: {OUTPUT_RESULTS_JSON}")

# ---------- PRINT SUMMARY ----------
print("\n=== TRAINING SUMMARY ===")
print(f"Team: {TEAM_NAME}")
print(f"Real matches: {n_real} (simulate used: {simulate})")
print(f"Training rows: {len(df)}")
print(f"Model (general) R²: {results_summary['models']['general']['r2']}")
print(f"Model (attack) R²: {results_summary['models']['attack']['r2']}")
print(f"Model (defense) R²: {results_summary['models']['defense']['r2']}")
print(f"Model (distribution) R²: {results_summary['models']['distribution']['r2']}")
print(f"Model (discipline) R²: {results_summary['models']['discipline']['r2']}")
print(f"Model (overall) R²: {results_summary['models']['overall']['r2']}")
print("Top features (general):")
for r in results_summary["models"]["general"]["top_features"][:6]:
    print(" -", r["feature"], ":", round(r["coefficient"], 6))
print("\nDone.")