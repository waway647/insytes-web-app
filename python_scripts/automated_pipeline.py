# ============================================================
# File: python_scripts/automated_pipeline.py
# Purpose: Dynamic pipeline automation for multiple datasets
# Features: Auto-detection, skip processed, end-to-end workflow
# ============================================================

import os
import json
import glob
import hashlib
import pandas as pd
from datetime import datetime
import subprocess
import sys
import shutil
from pathlib import Path

# ---------- CONFIGURATION ----------
DATA_DIR = "data"
TEAM_DATA_DIR = os.path.join(DATA_DIR, "team_data")
PYTHON_SCRIPTS_DIR = "python_scripts"
PIPELINE_STATE_FILE = os.path.join(PYTHON_SCRIPTS_DIR, "pipeline_state.json")
OUTPUT_DIR = "output"
MATCHES_OUTPUT_DIR = os.path.join(OUTPUT_DIR, "matches")

# Ensure output directories exist
os.makedirs(OUTPUT_DIR, exist_ok=True)
os.makedirs(MATCHES_OUTPUT_DIR, exist_ok=True)

# Pipeline scripts
SCRIPTS = {
    "derived_metrics": os.path.join(PYTHON_SCRIPTS_DIR, "derived_metrics_players.py"),
    "regression_model": os.path.join(PYTHON_SCRIPTS_DIR, "regression_model_players.py"),
    "insights": os.path.join(PYTHON_SCRIPTS_DIR, "predict_player_insights.py"),
    "heatmaps": os.path.join(PYTHON_SCRIPTS_DIR, "generate_heatmaps.py"),
    "team_summary": os.path.join(PYTHON_SCRIPTS_DIR, "heatmap_analysis.py"),
    "team_metrics": os.path.join(PYTHON_SCRIPTS_DIR, "derived_metrics_team.py"),
    "team_model": os.path.join(PYTHON_SCRIPTS_DIR, "regression_model_team.py"),
    "team_insights": os.path.join(PYTHON_SCRIPTS_DIR, "predict_team_insights.py")
}

class PipelineManager:
    def __init__(self):
        self.state = self.load_pipeline_state()
        self.datasets = self.discover_datasets()
        
    def load_pipeline_state(self):
        """Load pipeline processing state"""
        if os.path.exists(PIPELINE_STATE_FILE):
            with open(PIPELINE_STATE_FILE, 'r') as f:
                return json.load(f)
        return {"processed_datasets": {}, "last_updated": None}
    
    def save_pipeline_state(self):
        """Save pipeline processing state"""
        self.state["last_updated"] = datetime.now().isoformat()
        with open(PIPELINE_STATE_FILE, 'w') as f:
            json.dump(self.state, f, indent=2)
    
    def standardize_dataset_files(self):
        """Check and suggest standardization for dataset files"""
        print("\n📋 Dataset File Standardization Check:")
        print("=" * 50)
        
        suggestions = []
        all_csvs = [f for f in os.listdir(DATA_DIR) if f.endswith('.csv')]
        config_files = glob.glob(os.path.join(TEAM_DATA_DIR, "config_*.json"))
        
        for config_file in config_files:
            config_name = os.path.basename(config_file)
            if config_name.startswith("config_") and config_name.endswith(".json"):
                dataset_name = config_name[7:-5]  # Remove "config_" and ".json"
                expected_csv = f"{dataset_name}.csv"
                expected_csv_path = os.path.join(DATA_DIR, expected_csv)
                
                if os.path.exists(expected_csv_path):
                    print(f"✅ {dataset_name}: Correctly named as {expected_csv}")
                else:
                    # Find matching CSV with different name
                    matching_csv = None
                    for csv_file in all_csvs:
                        if dataset_name.lower() in csv_file.lower():
                            matching_csv = csv_file
                            break
                    
                    if matching_csv:
                        suggestions.append({
                            'current': matching_csv,
                            'expected': expected_csv,
                            'dataset': dataset_name
                        })
                        print(f"❌ {dataset_name}: Found {matching_csv} but should be {expected_csv}")
                    else:
                        print(f"❌ {dataset_name}: No matching CSV file found")
        
        if suggestions:
            print(f"\n💡 Standardization Suggestions:")
            print("To standardize your dataset files, run these commands:")
            for suggestion in suggestions:
                old_path = os.path.join(DATA_DIR, suggestion['current'])
                new_path = os.path.join(DATA_DIR, suggestion['expected'])
                print(f"  mv \"{old_path}\" \"{new_path}\"")
        else:
            print(f"\n✅ All dataset files follow the standard naming convention!")
        
        return suggestions

    def discover_datasets(self):
        """Auto-discover available datasets"""
        datasets = {}
        
        # Find CSV files in data directory
        csv_files = glob.glob(os.path.join(DATA_DIR, "*.csv"))
        config_files = glob.glob(os.path.join(TEAM_DATA_DIR, "config_*.json"))
        
        print(f"🔍 Discovered {len(csv_files)} CSV files and {len(config_files)} config files")
        
        for config_file in config_files:
            # Extract dataset name from config filename
            config_name = os.path.basename(config_file)
            if config_name.startswith("config_") and config_name.endswith(".json"):
                dataset_name = config_name[7:-5]  # Remove "config_" and ".json"
                
                # Use single consistent naming convention: {dataset_name}.csv
                # This eliminates the need for multiple naming patterns and makes it predictable
                csv_candidates = [f"{dataset_name}.csv"]
                
                # Fallback: if exact match not found, look for any CSV containing the dataset name
                if not any(os.path.exists(os.path.join(DATA_DIR, candidate)) for candidate in csv_candidates):
                    all_csvs = [f for f in os.listdir(DATA_DIR) if f.endswith('.csv')]
                    for csv_file in all_csvs:
                        if dataset_name.lower() in csv_file.lower():
                            csv_candidates.append(csv_file)
                            print(f"  ⚠️ Found non-standard naming: {csv_file} (should be {dataset_name}.csv)")
                            break
                
                csv_file = None
                for candidate in csv_candidates:
                    candidate_path = os.path.join(DATA_DIR, candidate)
                    if os.path.exists(candidate_path):
                        csv_file = candidate_path
                        break
                
                if csv_file:
                    datasets[dataset_name] = {
                        "config_file": config_file,
                        "csv_file": csv_file,
                        "dataset_name": dataset_name,
                        "file_hash": self.calculate_file_hash(csv_file),
                        "last_modified": os.path.getmtime(csv_file)
                    }
                    print(f"  ✅ {dataset_name}: {os.path.basename(csv_file)} + {config_name}")
                else:
                    print(f"  ❌ {dataset_name}: Config found but no matching CSV file")
        
        return datasets
    
    def calculate_file_hash(self, file_path):
        """Calculate MD5 hash of file for change detection"""
        hash_md5 = hashlib.md5()
        try:
            with open(file_path, "rb") as f:
                for chunk in iter(lambda: f.read(4096), b""):
                    hash_md5.update(chunk)
            return hash_md5.hexdigest()
        except Exception as e:
            print(f"Warning: Could not hash {file_path}: {e}")
            return None
    
    def is_dataset_processed(self, dataset_name, current_hash):
        """Check if dataset has been processed and is up to date"""
        if dataset_name not in self.state["processed_datasets"]:
            return False
        
        stored_info = self.state["processed_datasets"][dataset_name]
        stored_hash = stored_info.get("file_hash")
        
        # Check if file has changed
        if stored_hash != current_hash:
            print(f"  📁 {dataset_name}: File changed since last processing")
            return False
        
        # Check if all pipeline steps completed successfully
        required_steps = ["derived_metrics", "regression_model", "insights", "heatmaps", "team_summary", 
                         "team_metrics", "team_model", "team_insights"]
        completed_steps = stored_info.get("completed_steps", [])
        
        if not all(step in completed_steps for step in required_steps):
            missing_steps = [step for step in required_steps if step not in completed_steps]
            print(f"  ⚠️ {dataset_name}: Missing steps: {missing_steps}")
            return False
        
        print(f"  ✅ {dataset_name}: Already processed and up to date")
        return True
    
    def update_script_config(self, script_name, dataset_info):
        """Update script configuration for specific dataset"""
        script_path = SCRIPTS[script_name]
        
        if not os.path.exists(script_path):
            raise FileNotFoundError(f"Script not found: {script_path}")
        
        # Read current script
        with open(script_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Update paths based on script type
        if script_name == "derived_metrics":
            # Update event CSV path
            csv_filename = os.path.basename(dataset_info["csv_file"])
            content = self.update_script_variable(content, 'EVENTS_CSV', f'"output_dataset/{csv_filename}"')
            
            # Update team config path  
            config_filename = os.path.basename(dataset_info["config_file"])
            content = self.update_script_variable(content, 'TEAM_CONFIG_JSON', f'"data/team_data/{config_filename}"')
            
        elif script_name == "regression_model":
            # Regression model script uses match-specific output from derived_metrics
            # It reads from: output/matches/{dataset_name}/sanbeda_players_derived_metrics.json
            # No path updates needed as it uses --dataset parameter
            pass
            
        elif script_name == "insights":
            # Update paths for insights
            content = self.update_script_variable(content, 'PLAYER_METRICS_JSON', f'"python_scripts/sanbeda_players_derived_metrics.json"')
            content = self.update_script_variable(content, 'MODEL_RESULTS_JSON', f'"python_scripts/player_model_results.json"')
            
        elif script_name in ["heatmaps", "team_summary"]:
            # These scripts now use --dataset parameter and don't need path updates
            # They dynamically construct paths based on the dataset parameter
            pass
            
        elif script_name in ["team_metrics", "team_model", "team_insights"]:
            # Team scripts use --dataset parameter and don't need path updates
            pass
        
        # Write updated script to temp file
        temp_script_path = script_path + ".temp"
        with open(temp_script_path, 'w', encoding='utf-8') as f:
            f.write(content)
        
        return temp_script_path
    
    def update_script_variable(self, content, var_name, new_value):
        """Update a variable assignment in script content"""
        import re
        
        # Pattern to match variable assignment
        pattern = rf'^{var_name}\s*=\s*.*$'
        replacement = f'{var_name} = {new_value}'
        
        # Replace the line
        content = re.sub(pattern, replacement, content, flags=re.MULTILINE)
        
        return content
    
    def run_script(self, script_name, temp_script_path, dataset_name):
        """Run a pipeline script and capture output"""
        print(f"    ▶️ Running {script_name}...")
        
        try:
            # Build command based on script type
            if script_name in ["derived_metrics"]:
                # These scripts accept dataset name as positional argument
                cmd = [sys.executable, temp_script_path, dataset_name]
            elif script_name in ["insights", "regression_model", "heatmaps", "team_summary", 
                                 "team_metrics", "team_model", "team_insights"]:
                # These scripts use --dataset flag
                cmd = [sys.executable, temp_script_path, "--dataset", dataset_name]
            else:
                # Other scripts use original method
                cmd = [sys.executable, temp_script_path]
            
            # Run the script
            result = subprocess.run(
                cmd,
                capture_output=True,
                text=True,
                timeout=300  # 5 minute timeout
            )
            
            if result.returncode == 0:
                print(f"    ✅ {script_name} completed successfully")
                return True, result.stdout
            else:
                print(f"    ❌ {script_name} failed with error:")
                print(f"    {result.stderr}")
                return False, result.stderr
                
        except subprocess.TimeoutExpired:
            print(f"    ⏰ {script_name} timed out after 5 minutes")
            return False, "Script timeout"
        except Exception as e:
            print(f"    💥 {script_name} failed with exception: {e}")
            return False, str(e)
        finally:
            # Clean up temp file
            if os.path.exists(temp_script_path):
                os.remove(temp_script_path)
    
    def process_dataset(self, dataset_name, dataset_info):
        """Process a single dataset through the entire pipeline"""
        print(f"\n🎯 Processing dataset: {dataset_name}")
        print(f"   📁 CSV: {os.path.basename(dataset_info['csv_file'])}")
        print(f"   ⚙️ Config: {os.path.basename(dataset_info['config_file'])}")
        
        # Initialize dataset state if not exists
        if dataset_name not in self.state["processed_datasets"]:
            self.state["processed_datasets"][dataset_name] = {
                "file_hash": dataset_info["file_hash"],
                "completed_steps": [],
                "last_processed": None,
                "processing_history": []
            }
        
        dataset_state = self.state["processed_datasets"][dataset_name]
        dataset_state["file_hash"] = dataset_info["file_hash"]
        dataset_state["last_processed"] = datetime.now().isoformat()
        
        # Pipeline steps in order
        pipeline_steps = [
            ("derived_metrics", "Generate enhanced player metrics"),
            ("regression_model", "Train prediction models"),
            ("insights", "Generate player insights"),
            ("heatmaps", "Create player heatmaps"),
            ("team_summary", "Generate team analysis"),
            ("team_metrics", "Generate team performance metrics"),
            ("team_model", "Train team prediction models"),
            ("team_insights", "Generate team insights")
        ]
        
        completed_steps = []
        
        for step_name, step_description in pipeline_steps:
            print(f"\n  🔄 Step: {step_description}")
            
            # Skip if already completed and not forced
            if step_name in dataset_state["completed_steps"]:
                print(f"    ⏭️ Already completed, skipping...")
                completed_steps.append(step_name)
                continue
            
            # Update script configuration
            temp_script_path = self.update_script_config(step_name, dataset_info)
            
            # Run the script
            success, output = self.run_script(step_name, temp_script_path, dataset_name)
            
            if success:
                completed_steps.append(step_name)
                dataset_state["completed_steps"].append(step_name)
                
                # Save progress after each step
                self.save_pipeline_state()
                
            else:
                print(f"    💥 Pipeline failed at step: {step_name}")
                dataset_state["processing_history"].append({
                    "timestamp": datetime.now().isoformat(),
                    "step": step_name,
                    "status": "failed",
                    "error": output
                })
                self.save_pipeline_state()
                return False
        
        # Mark as completed
        dataset_state["processing_history"].append({
            "timestamp": datetime.now().isoformat(),
            "status": "completed",
            "steps": completed_steps
        })
        
        print(f"\n  🎉 Dataset {dataset_name} processed successfully!")
        return True
    
    def run_pipeline(self, force_reprocess=False, specific_dataset=None):
        """Run the entire pipeline for all datasets"""
        print("🚀 AUTOMATED PIPELINE STARTING")
        print(f"📊 Found {len(self.datasets)} datasets to process")
        
        if specific_dataset and specific_dataset not in self.datasets:
            print(f"❌ Specific dataset '{specific_dataset}' not found")
            return False
        
        datasets_to_process = [specific_dataset] if specific_dataset else list(self.datasets.keys())
        
        processed_count = 0
        skipped_count = 0
        failed_count = 0
        
        for dataset_name in datasets_to_process:
            dataset_info = self.datasets[dataset_name]
            
            # Check if processing needed
            if not force_reprocess and self.is_dataset_processed(dataset_name, dataset_info["file_hash"]):
                skipped_count += 1
                continue
            
            # Process dataset
            if self.process_dataset(dataset_name, dataset_info):
                processed_count += 1
            else:
                failed_count += 1
        
        # Final summary
        print(f"\n{'='*60}")
        print("🏆 PIPELINE EXECUTION SUMMARY")
        print(f"{'='*60}")
        print(f"✅ Processed: {processed_count} datasets")
        print(f"⏭️ Skipped: {skipped_count} datasets (already up to date)")
        print(f"❌ Failed: {failed_count} datasets")
        
        if processed_count > 0:
            print(f"\n📊 Output files updated:")
            print(f"  • Enhanced player metrics: output/matches/[match_name]/sanbeda_players_derived_metrics.json")
            print(f"  • Player model results: output/matches/[match_name]/player_model_results.json")
            print(f"  • Player insights: output/matches/[match_name]/sanbeda_player_insights.json")
            print(f"  • Enhanced team metrics: output/matches/[match_name]/sanbeda_team_derived_metrics.json")
            print(f"  • Team model results: output/matches/[match_name]/team_model_results.json")
            print(f"  • Team insights: output/matches/[match_name]/sanbeda_team_insights.json")
            print(f"  • Heatmaps: output/matches/[match_name]/heatmaps/")
            print(f"  • Team summary: python_scripts/team_heatmap_summary.json")
        
        self.save_pipeline_state()
        return failed_count == 0

def main():
    """Main execution function"""
    import argparse
    
    parser = argparse.ArgumentParser(description="Automated Pipeline for Football Analytics")
    parser.add_argument("--force", action="store_true", help="Force reprocess all datasets")
    parser.add_argument("--dataset", type=str, help="Process specific dataset only")
    parser.add_argument("--list", action="store_true", help="List available datasets")
    parser.add_argument("--status", action="store_true", help="Show processing status")
    parser.add_argument("--check-naming", action="store_true", help="Check and suggest file naming standardization")
    
    args = parser.parse_args()
    
    # Initialize pipeline manager
    pipeline = PipelineManager()
    
    if args.check_naming:
        suggestions = pipeline.standardize_dataset_files()
        return
    
    if args.list:
        print("📋 Available datasets:")
        for name, info in pipeline.datasets.items():
            print(f"  • {name}: {os.path.basename(info['csv_file'])}")
        return
    
    if args.status:
        print("📊 Processing status:")
        for name, info in pipeline.datasets.items():
            is_processed = pipeline.is_dataset_processed(name, info["file_hash"])
            status = "✅ Up to date" if is_processed else "❌ Needs processing"
            print(f"  • {name}: {status}")
        return
    
    # Run pipeline
    success = pipeline.run_pipeline(
        force_reprocess=args.force,
        specific_dataset=args.dataset
    )
    
    if success:
        print("\n🎉 All datasets processed successfully!")
    else:
        print("\n💥 Some datasets failed to process. Check logs above.")
        sys.exit(1)

if __name__ == "__main__":
    main()