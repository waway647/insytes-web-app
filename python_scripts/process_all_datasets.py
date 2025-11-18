# ============================================================
# File: python_scripts/process_all_datasets.py
# Purpose: Simple batch processor for all datasets
# Usage: python process_all_datasets.py
# ============================================================

import os
import sys
import subprocess
from pathlib import Path

def run_automated_pipeline():
    """Run the automated pipeline with simple interface"""
    print("🚀 BATCH PROCESSING ALL DATASETS")
    print("=" * 50)
    
    # Check if automated pipeline exists
    pipeline_script = "python_scripts/automated_pipeline.py"
    if not os.path.exists(pipeline_script):
        print("❌ Automated pipeline script not found!")
        return False
    
    try:
        # Run the automated pipeline
        result = subprocess.run(
            [sys.executable, pipeline_script],
            capture_output=False,  # Show output in real-time
            text=True
        )
        
        return result.returncode == 0
        
    except Exception as e:
        print(f"💥 Error running pipeline: {e}")
        return False

def show_usage():
    """Show usage examples"""
    print("\n📚 USAGE EXAMPLES:")
    print("=" * 30)
    print("1. Process all datasets:")
    print("   python python_scripts/process_all_datasets.py")
    print("")
    print("2. List available datasets:")
    print("   python python_scripts/automated_pipeline.py --list")
    print("")
    print("3. Check processing status:")
    print("   python python_scripts/automated_pipeline.py --status")
    print("")
    print("4. Force reprocess all:")
    print("   python python_scripts/automated_pipeline.py --force")
    print("")
    print("5. Process specific dataset:")
    print("   python python_scripts/automated_pipeline.py --dataset sbu_vs_up")

if __name__ == "__main__":
    if len(sys.argv) > 1 and sys.argv[1] in ["-h", "--help", "help"]:
        show_usage()
    else:
        success = run_automated_pipeline()
        if success:
            print("\n🎉 Batch processing completed successfully!")
        else:
            print("\n💥 Batch processing failed!")
            sys.exit(1)