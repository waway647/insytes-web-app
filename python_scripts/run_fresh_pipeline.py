#!/usr/bin/env python3
"""
Fresh Pipeline Runner - Process all datasets from scratch
"""

import os
import sys
import json
import subprocess
from pathlib import Path
import shutil

# Set up paths
BASE_DIR = Path(__file__).parent.parent
PYTHON_SCRIPTS_DIR = BASE_DIR / "python_scripts"
OUTPUT_DIR = BASE_DIR / "output" / "matches"
DATA_DIR = BASE_DIR / "output_dataset" 
CONFIG_DIR = BASE_DIR / "writable_data" / "configs"

def run_command(cmd, description):
    """Run a command and handle errors"""
    print(f"🔄 {description}")
    print(f"   Command: {' '.join(cmd)}")
    
    try:
        result = subprocess.run(cmd, capture_output=True, text=True, cwd=BASE_DIR)
        if result.returncode != 0:
            print(f"❌ Failed: {description}")
            print(f"   Error: {result.stderr}")
            return False
        else:
            print(f"✅ Success: {description}")
            if result.stdout.strip():
                print(f"   Output: {result.stdout.strip()}")
            return True
    except Exception as e:
        print(f"❌ Exception: {description} - {e}")
        return False

def process_dataset(dataset_name, events_csv, config_json):
    """Process a single dataset through the full pipeline"""
    print(f"\n🎯 Processing: {dataset_name}")
    
    # Create output directory
    match_output_dir = OUTPUT_DIR / dataset_name
    match_output_dir.mkdir(parents=True, exist_ok=True)
    
    python_exe = sys.executable
    
    # Step 1: Run derived metrics
    derived_cmd = [
        python_exe, 
        str(PYTHON_SCRIPTS_DIR / "derived_metrics_players.py"),
        str(events_csv),
        str(config_json),
        str(match_output_dir)
    ]
    
    if not run_command(derived_cmd, f"Generate derived metrics for {dataset_name}"):
        return False
    
    # Check if derived metrics file was created
    derived_files = list(match_output_dir.glob("*_players_derived_metrics.json"))
    if not derived_files:
        print(f"❌ No derived metrics file found in {match_output_dir}")
        return False
    
    print(f"✅ Generated: {derived_files[0].name}")
    return True

def main():
    """Main pipeline execution"""
    print("🚀 FRESH PIPELINE START")
    print("=" * 50)
    
    # Clear pipeline state
    pipeline_state = PYTHON_SCRIPTS_DIR / "pipeline_state.json"
    if pipeline_state.exists():
        pipeline_state.unlink()
        print("🗑️  Cleared pipeline state")
    
    # Clear output directories
    if OUTPUT_DIR.exists():
        shutil.rmtree(OUTPUT_DIR)
    OUTPUT_DIR.mkdir(parents=True)
    print("🗑️  Cleared output directories")
    
    # Find all datasets
    datasets = []
    for csv_file in DATA_DIR.glob("match_*_events.csv"):
        dataset_name = csv_file.stem.replace("_events", "")
        config_name = f"config_{dataset_name}.json"
        config_file = CONFIG_DIR / config_name
        
        if config_file.exists():
            datasets.append({
                'name': dataset_name,
                'csv': csv_file,
                'config': config_file
            })
            print(f"📋 Found: {dataset_name}")
        else:
            print(f"⚠️  Missing config for {dataset_name}")
    
    print(f"\n📊 Processing {len(datasets)} datasets")
    
    # Process each dataset
    success_count = 0
    for dataset in datasets:
        if process_dataset(dataset['name'], dataset['csv'], dataset['config']):
            success_count += 1
    
    # Summary
    print("\n" + "=" * 50)
    print("🏆 FRESH PIPELINE SUMMARY")
    print("=" * 50)
    print(f"✅ Successful: {success_count}/{len(datasets)}")
    print(f"❌ Failed: {len(datasets) - success_count}/{len(datasets)}")
    
    if success_count == len(datasets):
        print("🎉 All datasets processed successfully!")
        return True
    else:
        print("💥 Some datasets failed to process")
        return False

if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1)