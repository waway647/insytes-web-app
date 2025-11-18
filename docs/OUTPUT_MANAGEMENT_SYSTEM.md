# Output Management System Documentation

## Overview

The Insytes system now uses an organized folder structure to save match-specific outputs and prevent data overwriting. This allows for:

- **Historical tracking** of player performance across matches
- **Data preservation** preventing accidental overwrites
- **Easy comparison** between different matches
- **Organized archival** of all analysis outputs

## Folder Structure

```
output/
├── matches/
│   ├── sbu_vs_2worlds/
│   │   ├── sanbeda_players_derived_metrics.json
│   │   ├── sanbeda_players_derived_metrics.csv  
│   │   ├── sanbeda_player_insights.json
│   │   └── output_manifest.json
│   ├── sbu_vs_up/
│   │   └── [match-specific files]
│   └── events_vs_siniloan/
│       └── [match-specific files]
├── models/
│   ├── player_dpr_predictions_sbu_vs_2worlds_20251117_003610.csv
│   ├── player_model_results_sbu_vs_2worlds_20251117_003610.json
│   └── [timestamped model files]
└── training_data/
    ├── sanbeda_players_training_data_20251117_003610.csv
    └── [timestamped training datasets]
```

## Updated Scripts

### 1. `derived_metrics_players.py`
**Usage**: `python python_scripts/derived_metrics_players.py [match_name]`

**Features**:
- Accepts match name as command line argument
- Automatically creates `output/matches/[match_name]/` directory
- Saves to both match-specific and main directories
- Dynamic file paths based on match name

**Example**:
```bash
python python_scripts/derived_metrics_players.py sbu_vs_2worlds
```

### 2. `predict_player_insights.py`
**Usage**: `python python_scripts/predict_player_insights.py --dataset [match_name]`

**Features**:
- Uses `--dataset` flag for match specification
- Reads from match-specific derived metrics
- Saves insights to match-specific folder
- **Fixed shot accuracy calculation** (now shows correct percentages)

**Example**:
```bash
python python_scripts/predict_player_insights.py --dataset sbu_vs_2worlds
```

### 3. `organize_outputs.py` (NEW)
**Usage**: `python python_scripts/organize_outputs.py [match_name]`

**Features**:
- Copies models and training data to organized folders
- Adds timestamps to prevent overwrites
- Creates output manifest for tracking
- Maintains historical versions

**Example**:
```bash
python python_scripts/organize_outputs.py sbu_vs_2worlds
```

### 4. `automated_pipeline.py` (UPDATED)
**Features**:
- Updated to use new output structure
- Passes dataset names to scripts automatically
- Creates organized folder structure
- Maintains backward compatibility

## File Naming Convention

### Models
- Format: `[type]_[match_name]_[timestamp].extension`
- Example: `player_dpr_predictions_sbu_vs_2worlds_20251117_003610.csv`

### Training Data  
- Format: `[type]_[timestamp].extension`
- Example: `sanbeda_players_training_data_20251117_003610.csv`

### Match-Specific Files
- Stored in: `output/matches/[match_name]/`
- Consistent names within each match folder
- No timestamps needed (folder separation prevents conflicts)

## Output Manifest

Each match folder contains an `output_manifest.json` file:

```json
{
    "match_name": "sbu_vs_2worlds",
    "generated_at": "2025-11-17T00:36:10.123456",
    "files": {
        "metrics": "output/matches/sbu_vs_2worlds/sanbeda_players_derived_metrics.json",
        "insights": "output/matches/sbu_vs_2worlds/sanbeda_player_insights.json", 
        "models": [
            "output/models/player_dpr_predictions_sbu_vs_2worlds_20251117_003610.csv",
            "output/models/player_model_results_sbu_vs_2worlds_20251117_003610.json"
        ],
        "training_data": [
            "output/training_data/sanbeda_players_training_data_20251117_003610.csv"
        ]
    }
}
```

## Workflow Examples

### Process Single Match
```bash
# Step 1: Generate metrics  
python python_scripts/derived_metrics_players.py sbu_vs_2worlds

# Step 2: Generate insights
python python_scripts/predict_player_insights.py --dataset sbu_vs_2worlds

# Step 3: Organize outputs (optional)
python python_scripts/organize_outputs.py sbu_vs_2worlds
```

### Process Multiple Matches
```bash
# Process first match
python python_scripts/automated_pipeline.py --dataset sbu_vs_2worlds

# Process second match  
python python_scripts/automated_pipeline.py --dataset sbu_vs_up

# Outputs are automatically separated by match name
```

### Compare Across Matches
```bash
# View player performance across different matches
ls output/matches/*/sanbeda_player_insights.json

# Compare models from different matches
ls output/models/player_dpr_predictions_*
```

## Benefits

### 1. **Data Preservation**
- No more accidental overwrites
- Complete historical tracking  
- Easy rollback to previous analyses

### 2. **Organization**
- Clear separation by match
- Timestamped models and training data
- Searchable by match name or date

### 3. **Collaboration**
- Team can work on different matches simultaneously
- Share specific match analyses easily
- Track model evolution over time

### 4. **Fixed Issues**
- ✅ **Shot accuracy now displays correctly** (49.23% for Edvard, 75.67% for Laurence)
- ✅ **Proper lineup matching** with config files  
- ✅ **No more data overwrites** between matches
- ✅ **Organized archival** of all outputs

## Migration Notes

- **Backward Compatibility**: Scripts still save to main directory for existing workflows
- **Automatic Folders**: Directories are created automatically when needed
- **Optional Organization**: The `organize_outputs.py` script is optional but recommended
- **Existing Files**: Previous outputs remain in `python_scripts/` directory

This system ensures that your match analysis data is properly organized, preserved, and easily accessible for historical comparison and analysis! 🎯📊