# ============================================================
# File: python_scripts/dataset_manager.py
# Purpose: Manage datasets and configurations for the pipeline
# Features: Add new datasets, validate configurations, cleanup
# ============================================================

import os
import json
import glob
from datetime import datetime
import shutil

class DatasetManager:
    def __init__(self):
        self.data_dir = "data"
        self.team_data_dir = os.path.join(self.data_dir, "team_data")
        self.ensure_directories()
    
    def ensure_directories(self):
        """Ensure required directories exist"""
        os.makedirs(self.data_dir, exist_ok=True)
        os.makedirs(self.team_data_dir, exist_ok=True)
    
    def create_config_template(self, dataset_name, team1, team2):
        """Create a config template for a new dataset"""
        config = {
            "match_info": {
                "team1": team1,
                "team2": team2,
                "date": datetime.now().strftime("%Y-%m-%d"),
                "dataset_name": dataset_name
            },
            "team_configs": {
                team1: {
                    "primary_color": "#FF0000",
                    "secondary_color": "#FFFFFF",
                    "formation": "4-3-3"
                },
                team2: {
                    "primary_color": "#0000FF", 
                    "secondary_color": "#FFFFFF",
                    "formation": "4-4-2"
                }
            },
            "pipeline_settings": {
                "auto_process": True,
                "skip_if_processed": True,
                "generate_heatmaps": True,
                "generate_insights": True
            }
        }
        
        config_filename = f"config_{dataset_name}.json"
        config_path = os.path.join(self.team_data_dir, config_filename)
        
        with open(config_path, 'w') as f:
            json.dump(config, f, indent=2)
        
        print(f"✅ Created config template: {config_path}")
        return config_path
    
    def add_dataset(self, csv_source, dataset_name, team1, team2, copy_csv=True):
        """Add a new dataset to the pipeline"""
        print(f"📁 Adding dataset: {dataset_name}")
        
        # Validate CSV source
        if not os.path.exists(csv_source):
            raise FileNotFoundError(f"CSV file not found: {csv_source}")
        
        # Determine target CSV filename
        target_csv_name = f"sanbeda_vs_{dataset_name.split('_vs_')[-1]}.csv" if "_vs_" in dataset_name else f"{dataset_name}.csv"
        target_csv_path = os.path.join(self.data_dir, target_csv_name)
        
        # Copy CSV file if needed
        if copy_csv:
            if csv_source != target_csv_path:
                shutil.copy2(csv_source, target_csv_path)
                print(f"📄 Copied CSV: {csv_source} → {target_csv_path}")
        
        # Create config file
        config_path = self.create_config_template(dataset_name, team1, team2)
        
        print(f"🎯 Dataset '{dataset_name}' added successfully!")
        print(f"   CSV: {target_csv_path}")
        print(f"   Config: {config_path}")
        
        return {
            "csv_path": target_csv_path,
            "config_path": config_path,
            "dataset_name": dataset_name
        }
    
    def list_datasets(self):
        """List all available datasets"""
        csv_files = glob.glob(os.path.join(self.data_dir, "*.csv"))
        config_files = glob.glob(os.path.join(self.team_data_dir, "config_*.json"))
        
        print("📊 AVAILABLE DATASETS:")
        print("=" * 40)
        
        # Group by dataset name
        datasets = {}
        
        # Process config files
        for config_file in config_files:
            config_name = os.path.basename(config_file)
            if config_name.startswith("config_") and config_name.endswith(".json"):
                dataset_name = config_name[7:-5]  # Remove "config_" and ".json"
                
                try:
                    with open(config_file, 'r') as f:
                        config_data = json.load(f)
                    
                    datasets[dataset_name] = {
                        "config_file": config_file,
                        "config_data": config_data,
                        "csv_file": None
                    }
                except Exception as e:
                    print(f"⚠️ Invalid config {config_name}: {e}")
        
        # Find matching CSV files
        for csv_file in csv_files:
            csv_name = os.path.basename(csv_file)
            
            # Try to match with dataset names
            for dataset_name in datasets.keys():
                potential_names = [
                    f"sanbeda_vs_{dataset_name.split('_vs_')[-1]}.csv",
                    f"events_vs_{dataset_name.split('_vs_')[-1]}.csv", 
                    f"{dataset_name}.csv"
                ]
                
                if csv_name in potential_names:
                    datasets[dataset_name]["csv_file"] = csv_file
                    break
        
        # Display results
        for dataset_name, info in datasets.items():
            config_data = info.get("config_data", {})
            match_info = config_data.get("match_info", {})
            team1 = match_info.get("team1", "Unknown")
            team2 = match_info.get("team2", "Unknown")
            
            status = "✅ Complete" if info["csv_file"] else "❌ Missing CSV"
            
            print(f"\n📁 {dataset_name}")
            print(f"   Teams: {team1} vs {team2}")
            print(f"   CSV: {os.path.basename(info['csv_file']) if info['csv_file'] else 'Not found'}")
            print(f"   Config: {os.path.basename(info['config_file'])}")
            print(f"   Status: {status}")
        
        return datasets
    
    def validate_dataset(self, dataset_name):
        """Validate a dataset configuration and files"""
        print(f"🔍 Validating dataset: {dataset_name}")
        
        config_path = os.path.join(self.team_data_dir, f"config_{dataset_name}.json")
        if not os.path.exists(config_path):
            print(f"❌ Config file not found: {config_path}")
            return False
        
        try:
            with open(config_path, 'r') as f:
                config = json.load(f)
            print(f"✅ Config file valid")
        except Exception as e:
            print(f"❌ Invalid config JSON: {e}")
            return False
        
        # Check for CSV file
        csv_candidates = [
            f"sanbeda_vs_{dataset_name.split('_vs_')[-1]}.csv",
            f"events_vs_{dataset_name.split('_vs_')[-1]}.csv",
            f"{dataset_name}.csv"
        ]
        
        csv_found = False
        for candidate in csv_candidates:
            csv_path = os.path.join(self.data_dir, candidate)
            if os.path.exists(csv_path):
                print(f"✅ CSV file found: {candidate}")
                csv_found = True
                
                # Validate CSV structure
                try:
                    import pandas as pd
                    df = pd.read_csv(csv_path)
                    required_columns = ['player_name', 'team', 'event', 'origin_x', 'origin_y']
                    missing_columns = [col for col in required_columns if col not in df.columns]
                    
                    if missing_columns:
                        print(f"⚠️ CSV missing columns: {missing_columns}")
                    else:
                        print(f"✅ CSV structure valid ({len(df)} events)")
                        
                except Exception as e:
                    print(f"⚠️ CSV validation error: {e}")
                
                break
        
        if not csv_found:
            print(f"❌ No CSV file found for dataset")
            return False
        
        print(f"✅ Dataset '{dataset_name}' validation complete")
        return True
    
    def cleanup_outputs(self, dataset_name=None):
        """Clean up generated output files"""
        if dataset_name:
            print(f"🧹 Cleaning outputs for dataset: {dataset_name}")
        else:
            print(f"🧹 Cleaning all output files")
        
        # Files to clean
        output_files = [
            "python_scripts/sanbeda_players_derived_metrics.json",
            "python_scripts/player_model_results.json", 
            "python_scripts/sanbeda_player_insights.json",
            "python_scripts/team_heatmap_summary.json",
            "python_scripts/heatmap_analysis_report.json",
            "python_scripts/pipeline_state.json"
        ]
        
        cleaned_count = 0
        for file_path in output_files:
            if os.path.exists(file_path):
                os.remove(file_path)
                cleaned_count += 1
                print(f"  🗑️ Removed: {file_path}")
        
        # Clean heatmaps directory
        heatmaps_dir = f"output/matches/{match_name}/heatmaps"
        if os.path.exists(heatmaps_dir):
            shutil.rmtree(heatmaps_dir)
            print(f"  🗑️ Removed: {heatmaps_dir}/")
        
        print(f"✅ Cleaned {cleaned_count} files")

def main():
    """Main CLI interface"""
    import argparse
    
    parser = argparse.ArgumentParser(description="Dataset Manager for Football Analytics")
    subparsers = parser.add_subparsers(dest='command', help='Available commands')
    
    # Add dataset command
    add_parser = subparsers.add_parser('add', help='Add new dataset')
    add_parser.add_argument('csv_file', help='Path to CSV file')
    add_parser.add_argument('dataset_name', help='Name for the dataset')
    add_parser.add_argument('team1', help='First team name')
    add_parser.add_argument('team2', help='Second team name')
    add_parser.add_argument('--no-copy', action='store_true', help='Don\'t copy CSV file')
    
    # List datasets command
    list_parser = subparsers.add_parser('list', help='List all datasets')
    
    # Validate dataset command
    validate_parser = subparsers.add_parser('validate', help='Validate dataset')
    validate_parser.add_argument('dataset_name', help='Dataset name to validate')
    
    # Cleanup command
    cleanup_parser = subparsers.add_parser('cleanup', help='Clean output files')
    cleanup_parser.add_argument('--dataset', help='Specific dataset to clean')
    
    args = parser.parse_args()
    
    manager = DatasetManager()
    
    if args.command == 'add':
        try:
            result = manager.add_dataset(
                args.csv_file, 
                args.dataset_name, 
                args.team1, 
                args.team2,
                copy_csv=not args.no_copy
            )
            print(f"\n🎉 Ready to process! Run:")
            print(f"   python python_scripts/automated_pipeline.py --dataset {args.dataset_name}")
        except Exception as e:
            print(f"💥 Error: {e}")
    
    elif args.command == 'list':
        manager.list_datasets()
    
    elif args.command == 'validate':
        manager.validate_dataset(args.dataset_name)
    
    elif args.command == 'cleanup':
        manager.cleanup_outputs(args.dataset)
    
    else:
        parser.print_help()

if __name__ == "__main__":
    main()