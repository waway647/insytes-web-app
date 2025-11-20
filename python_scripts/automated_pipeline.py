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
TEAM_DATA_DIR = "writable_data/configs"
EVENTS_DATA_DIR = "output_dataset"
PYTHON_SCRIPTS_DIR = "python_scripts"
PIPELINE_STATE_FILE = os.path.join(PYTHON_SCRIPTS_DIR, "pipeline_state.json")
OUTPUT_DIR = "output"
MATCHES_OUTPUT_DIR = os.path.join(OUTPUT_DIR, "matches")

# Ensure output directories exist
os.makedirs(OUTPUT_DIR, exist_ok=True)
os.makedirs(MATCHES_OUTPUT_DIR, exist_ok=True)
os.makedirs(TEAM_DATA_DIR, exist_ok=True)
os.makedirs(EVENTS_DATA_DIR, exist_ok=True)
       
# Pipeline scripts
SCRIPTS = {
    "convert_events": os.path.join(PYTHON_SCRIPTS_DIR, "convert_events_to_csv.py"),
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
        print("New naming convention:")
        print("  • Config: config_match_{number}_sbu_vs_{opponent}.json (in writable_data/configs/)")
        print("  • CSV: match_{number}_sbu_vs_{opponent}_events.csv (in output_dataset/)")
        print("=" * 50)
        
        suggestions = []
        all_csvs = [f for f in os.listdir(EVENTS_DATA_DIR) if f.endswith('.csv')] if os.path.exists(EVENTS_DATA_DIR) else []
        config_files = glob.glob(os.path.join(TEAM_DATA_DIR, "config_*.json"))
        
        for config_file in config_files:
            config_name = os.path.basename(config_file)
            dataset_name = None
            expected_csv = None
            
            # New naming convention: config_match_{number}_sbu_vs_{opponent}.json
            if config_name.startswith("config_match_") and config_name.endswith(".json"):
                dataset_name = config_name[7:-5]  # Remove "config_" and ".json"
                expected_csv = f"{dataset_name}_events.csv"
                print(f"✅ {dataset_name}: New naming convention - {expected_csv}")
                
            # Legacy naming convention: config_{old_name}.json
            elif config_name.startswith("config_") and config_name.endswith(".json"):
                old_dataset_name = config_name[7:-5]  # Remove "config_" and ".json"
                print(f"⚠️ Legacy config found: {config_name}")
                
                # Suggest new naming based on content or pattern
                if "sbu_vs_" in old_dataset_name:
                    # Extract opponent and suggest match number
                    opponent = old_dataset_name.replace("sbu_vs_", "")
                    suggested_name = f"match_1_{old_dataset_name}"  # Default to match_1
                    suggestions.append({
                        'type': 'config_rename',
                        'current': config_name,
                        'suggested': f"config_{suggested_name}.json",
                        'dataset': suggested_name
                    })
                    print(f"  💡 Suggest renaming to: config_{suggested_name}.json")
                    continue
            
            # Check if corresponding CSV exists
            if expected_csv and dataset_name:
                expected_csv_path = os.path.join(EVENTS_DATA_DIR, expected_csv)
                
                if os.path.exists(expected_csv_path):
                    print(f"  ✅ CSV exists: {expected_csv}")
                else:
                    # Find matching CSV with different name
                    matching_csv = None
                    if "_sbu_vs_" in dataset_name:
                        opponent_part = dataset_name.split("_sbu_vs_")[-1]
                        for csv_file in all_csvs:
                            if opponent_part.lower() in csv_file.lower() and "sbu" in csv_file.lower():
                                matching_csv = csv_file
                                break
                    
                    if matching_csv:
                        suggestions.append({
                            'type': 'csv_rename',
                            'current': matching_csv,
                            'expected': expected_csv,
                            'dataset': dataset_name
                        })
                        print(f"  ❌ Found {matching_csv} but should be {expected_csv}")
                    else:
                        print(f"  ❌ No matching CSV file found for {dataset_name}")
        
        if suggestions:
            print(f"\n💡 Standardization Commands:")
            for suggestion in suggestions:
                if suggestion['type'] == 'config_rename':
                    old_path = os.path.join(TEAM_DATA_DIR, suggestion['current'])
                    new_path = os.path.join(TEAM_DATA_DIR, suggestion['suggested'])
                    print(f"  mv \"{old_path}\" \"{new_path}\"")
                elif suggestion['type'] == 'csv_rename':
                    old_path = os.path.join(EVENTS_DATA_DIR, suggestion['current'])
                    new_path = os.path.join(EVENTS_DATA_DIR, suggestion['expected'])
                    print(f"  mv \"{old_path}\" \"{new_path}\"")
        else:
            print(f"\n✅ All files follow the new naming convention!")
        
        return suggestions

    def discover_datasets(self):
        """Auto-discover available datasets from both JSON and CSV sources"""
        datasets = {}
        
        # First priority: JSON files from event tagging (newest workflow)
        json_events_dir = "writable_data/events"
        if os.path.exists(json_events_dir):
            json_files = glob.glob(os.path.join(json_events_dir, "*_events.json"))
            for json_file in json_files:
                json_name = os.path.basename(json_file)
                # Extract dataset name from "match_11_events.json" -> "match_11"
                if json_name.endswith("_events.json"):
                    dataset_name = json_name[:-12]  # Remove "_events.json"
                    
                    # Look for corresponding config file
                    config_file = os.path.join(TEAM_DATA_DIR, f"config_{dataset_name}.json")
                    
                    if os.path.exists(config_file):
                        # Determine expected CSV output path
                        expected_csv = os.path.join(EVENTS_DATA_DIR, f"{dataset_name}_events.csv")
                        
                        datasets[dataset_name] = {
                            "json_file": json_file,
                            "config_file": config_file,
                            "csv_file": expected_csv,
                            "dataset_name": dataset_name,
                            "source_type": "json",
                            "file_hash": self.calculate_file_hash(json_file),
                            "last_modified": os.path.getmtime(json_file)
                        }
                        print(f"  📋 {dataset_name}: {json_name} + config_{dataset_name}.json (JSON workflow)")
                    else:
                        print(f"  ⚠️ {dataset_name}: Found JSON but missing config file: {config_file}")
        
        # Second priority: Existing CSV files (legacy workflow)
        csv_files = glob.glob(os.path.join(EVENTS_DATA_DIR, "*.csv")) if os.path.exists(EVENTS_DATA_DIR) else []
        config_files = glob.glob(os.path.join(TEAM_DATA_DIR, "config_*.json"))
        
        print(f"🔍 Discovered {len(json_files) if 'json_files' in locals() else 0} JSON files and {len(csv_files)} CSV files with {len(config_files)} config files")
        
        for config_file in config_files:
            # Extract dataset name from config filename
            config_name = os.path.basename(config_file)
            dataset_name = None
            
            # New naming convention: config_match_{number}_sbu_vs_{opponent}.json
            if config_name.startswith("config_match_") and config_name.endswith(".json"):
                # Extract from "config_match_1_sbu_vs_up.json" -> "match_1_sbu_vs_up"
                dataset_name = config_name[7:-5]  # Remove "config_" prefix and ".json" suffix
            
            # Legacy naming convention: config_{dataset_name}.json (for backward compatibility)
            elif config_name.startswith("config_") and config_name.endswith(".json"):
                dataset_name = config_name[7:-5]  # Remove "config_" and ".json"
            
            # Skip if already found as JSON workflow
            if dataset_name and dataset_name in datasets:
                continue
            
            if dataset_name:
                # Look for corresponding CSV file with new naming convention
                # For "match_1_sbu_vs_up" config, look for "match_1_sbu_vs_up_events.csv"
                expected_csv = f"{dataset_name}_events.csv"
                csv_file_path = os.path.join(EVENTS_DATA_DIR, expected_csv)
                
                # Fallback: if exact match not found, look for any CSV containing parts of the dataset name
                if not os.path.exists(csv_file_path) and os.path.exists(EVENTS_DATA_DIR):
                    all_csvs = [f for f in os.listdir(EVENTS_DATA_DIR) if f.endswith('.csv')]
                    for csv_file in all_csvs:
                        # Extract key parts for matching (e.g., "sbu_vs_up" from various formats)
                        if "_sbu_vs_" in dataset_name and "_sbu_vs_" in csv_file.lower():
                            opponent_part = dataset_name.split("_sbu_vs_")[-1]
                            if opponent_part.lower() in csv_file.lower():
                                csv_file_path = os.path.join(EVENTS_DATA_DIR, csv_file)
                                print(f"  ⚠️ Found legacy naming: {csv_file} (should be {expected_csv})")
                                break
                
                if os.path.exists(csv_file_path):
                    datasets[dataset_name] = {
                        "config_file": config_file,
                        "csv_file": csv_file_path,
                        "dataset_name": dataset_name,
                        "source_type": "csv",
                        "file_hash": self.calculate_file_hash(csv_file_path),
                        "last_modified": os.path.getmtime(csv_file_path)
                    }
                    print(f"  ✅ {dataset_name}: {os.path.basename(csv_file_path)} + {config_name} (CSV workflow)")
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
        required_steps = ["convert_events", "derived_metrics", "regression_model", "insights", 
                         "team_metrics", "team_model", "team_insights", "heatmaps", "team_summary"]
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
        if script_name == "convert_events":
            # For JSON-to-CSV conversion, no script modification needed
            # Will pass JSON and CSV paths as command line arguments
            pass
            
        elif script_name == "derived_metrics":
            # Update event CSV path
            csv_filename = os.path.basename(dataset_info["csv_file"])
            content = self.update_script_variable(content, 'EVENTS_CSV', f'"output_dataset/{csv_filename}"')
            
            # Update team config path  
            config_filename = os.path.basename(dataset_info["config_file"])
            content = self.update_script_variable(content, 'TEAM_CONFIG_JSON', f'"writable_data/configs/{config_filename}"')
            
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
            if script_name == "convert_events":
                # Special handling for JSON-to-CSV conversion using enhanced dynamic script
                # Use the --from-controller flag for controller outputs or --match-id for specific datasets
                dataset_info = self.datasets[dataset_name]
                if "json_file" in dataset_info:
                    # For JSON workflow, use --from-controller to ensure fresh conversion
                    cmd = [sys.executable, temp_script_path, "--from-controller", dataset_name]
                else:
                    print(f"    ⏭️ Skipping convert_events: No JSON file for {dataset_name} (CSV workflow)")
                    return True, "Skipped - CSV workflow"
                    
            elif script_name in ["derived_metrics"]:
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
            ("convert_events", "Convert JSON events to CSV"),
            ("derived_metrics", "Generate enhanced player metrics"),
            ("regression_model", "Train prediction models"),
            ("insights", "Generate player insights"),
            ("team_metrics", "Generate team performance metrics"),
            ("team_model", "Train team prediction models"),
            ("team_insights", "Generate team insights"),
            ("heatmaps", "Create player heatmaps"),
            ("team_summary", "Generate team analysis")
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