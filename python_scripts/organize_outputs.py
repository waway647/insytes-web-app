#!/usr/bin/env python3
# ============================================================
# File: python_scripts/organize_outputs.py
# Purpose: Copy models and training data to organized output folders
# Usage: python organize_outputs.py [match_name]
# ============================================================

import os
import sys
import shutil
from datetime import datetime

def organize_outputs(match_name="sbu_vs_2worlds"):
    """Organize pipeline outputs into proper folder structure"""
    
    # Output directories
    output_dir = "output"
    matches_dir = f"{output_dir}/matches/{match_name}"
    models_dir = f"{output_dir}/model_results"
    
    # Ensure directories exist
    os.makedirs(matches_dir, exist_ok=True)
    os.makedirs(models_dir, exist_ok=True)
    
    # Files to organize
    files_to_copy = [
        # Models (copy to models directory with timestamp)
        {
            "source": f"output/matches/{match_name}/player_dpr_predictions.csv",
            "dest": f"{models_dir}/player_dpr_predictions_{match_name}_{datetime.now().strftime('%Y%m%d_%H%M%S')}.csv",
            "type": "model"
        },
        {
            "source": f"output/matches/{match_name}/player_model_results.json", 
            "dest": f"{models_dir}/player_model_results_{match_name}_{datetime.now().strftime('%Y%m%d_%H%M%S')}.json",
            "type": "model"
        },
        # Heatmaps (copy heatmap folder contents)
        {
            "source": f"output/matches/{match_name}/heatmaps",
            "dest": f"{models_dir}/heatmaps_{match_name}_{datetime.now().strftime('%Y%m%d_%H%M%S')}",
            "type": "heatmaps"
        },
        # Models with match names (copy to versioned location)
        {
            "source": f"output/model_results/sanbeda_role_attacker_{match_name}.pkl",
            "dest": f"{models_dir}/sanbeda_role_attacker_{match_name}_{datetime.now().strftime('%Y%m%d_%H%M%S')}.pkl",
            "type": "model"
        },
        {
            "source": f"output/model_results/sanbeda_role_midfielder_{match_name}.pkl",
            "dest": f"{models_dir}/sanbeda_role_midfielder_{match_name}_{datetime.now().strftime('%Y%m%d_%H%M%S')}.pkl",
            "type": "model"
        },
        {
            "source": f"output/model_results/sanbeda_role_defender_{match_name}.pkl",
            "dest": f"{models_dir}/sanbeda_role_defender_{match_name}_{datetime.now().strftime('%Y%m%d_%H%M%S')}.pkl",
            "type": "model"
        },
        {
            "source": f"output/model_results/sanbeda_role_goalkeeper_understat_{match_name}.pkl",
            "dest": f"{models_dir}/sanbeda_role_goalkeeper_understat_{match_name}_{datetime.now().strftime('%Y%m%d_%H%M%S')}.pkl",
            "type": "model"
        }
    ]
    
    copied_files = []
    
    for file_info in files_to_copy:
        source_path = file_info["source"]
        dest_path = file_info["dest"]
        file_type = file_info["type"]
        
        if file_type == "heatmaps":
            # Handle heatmap folder copying
            if os.path.exists(source_path) and os.path.isdir(source_path):
                try:
                    shutil.copytree(source_path, dest_path)
                    copied_files.append((file_type, dest_path))
                    print(f"✅ Copied {file_type} folder: {dest_path}")
                except Exception as e:
                    print(f"❌ Failed to copy heatmap folder {source_path}: {e}")
            else:
                print(f"⚠️ Heatmap folder not found: {source_path}")
        else:
            # Handle individual file copying
            if os.path.exists(source_path):
                try:
                    shutil.copy2(source_path, dest_path)
                    copied_files.append((file_type, dest_path))
                    print(f"✅ Copied {file_type}: {dest_path}")
                except Exception as e:
                    print(f"❌ Failed to copy {source_path}: {e}")
            else:
                print(f"⚠️ Source file not found: {source_path}")
    
    # Create a manifest file
    manifest_path = f"{matches_dir}/output_manifest.json"
    manifest = {
        "match_name": match_name,
        "generated_at": datetime.now().isoformat(),
        "files": {
            "metrics": f"{matches_dir}/sanbeda_players_derived_metrics.json",
            "insights": f"{matches_dir}/sanbeda_player_insights.json",
            "models": [f for t, f in copied_files if t == "model"],
            "training_data": [f for t, f in copied_files if t == "training_data"]
        }
    }
    
    import json
    with open(manifest_path, "w", encoding="utf-8") as f:
        json.dump(manifest, f, indent=4)
    
    print(f"📋 Output manifest created: {manifest_path}")
    print(f"🎉 Output organization complete for match: {match_name}")
    
    return copied_files

if __name__ == "__main__":
    match_name = sys.argv[1] if len(sys.argv) > 1 else "sbu_vs_2worlds"
    organize_outputs(match_name)