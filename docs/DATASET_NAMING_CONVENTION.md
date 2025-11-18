# Dataset Naming Convention

## Overview
To ensure consistency and eliminate hardcoded file mappings, all datasets follow a single, standardized naming convention.

## Naming Rules

### 1. CSV Event Files
**Location**: `data/`
**Pattern**: `{dataset_name}.csv`

**Examples**:
- `sbu_vs_2worlds.csv`
- `sbu_vs_siniloan.csv` 
- `sbu_vs_up.csv`

### 2. Team Configuration Files
**Location**: `data/team_data/`
**Pattern**: `config_{dataset_name}.json`

**Examples**:
- `config_sbu_vs_2worlds.json`
- `config_sbu_vs_siniloan.json`
- `config_sbu_vs_up.json`

### 3. Output Directories
**Location**: `output/matches/`
**Pattern**: `{dataset_name}/`

**Examples**:
- `output/matches/sbu_vs_2worlds/`
- `output/matches/sbu_vs_siniloan/`
- `output/matches/sbu_vs_up/`

## Benefits of Standardized Naming

✅ **No Hardcoded Mappings**: Pipeline automatically discovers files
✅ **Predictable Structure**: Easy to add new datasets
✅ **Reduced Errors**: No manual mapping updates needed
✅ **Scalable**: Works with any number of matches
✅ **Maintainable**: Single source of truth for file names

## Adding New Datasets

To add a new dataset (e.g., `sbu_vs_newteam`):

1. **Create CSV file**: `data/sbu_vs_newteam.csv`
2. **Create config file**: `data/team_data/config_sbu_vs_newteam.json`
3. **Run pipeline**: `python python_scripts/automated_pipeline.py --dataset sbu_vs_newteam`

That's it! No code changes required.

## Validation Commands

### Check naming compliance:
```bash
python python_scripts/automated_pipeline.py --check-naming
```

### List discovered datasets:
```bash
python python_scripts/automated_pipeline.py --list
```

### Check processing status:
```bash
python python_scripts/automated_pipeline.py --status
```

## Legacy File Migration

If you have existing files with non-standard names, the `--check-naming` command will provide exact commands to rename them:

```bash
# Example output:
mv "data/events_vs_siniloan.csv" "data/sbu_vs_siniloan.csv"
mv "data/sanbeda_vs_up.csv" "data/sbu_vs_up.csv"
```

## File Organization Best Practices

```
project/
├── data/
│   ├── sbu_vs_2worlds.csv           # Event data
│   ├── sbu_vs_siniloan.csv          # Event data
│   ├── sbu_vs_up.csv                # Event data
│   └── team_data/
│       ├── config_sbu_vs_2worlds.json
│       ├── config_sbu_vs_siniloan.json
│       └── config_sbu_vs_up.json
├── output/
│   └── matches/
│       ├── sbu_vs_2worlds/          # Generated outputs
│       ├── sbu_vs_siniloan/         # Generated outputs
│       └── sbu_vs_up/               # Generated outputs
└── python_scripts/
    └── automated_pipeline.py        # Auto-discovery pipeline
```

## Migration from Legacy Naming

If you have existing files with different naming patterns, follow these steps:

1. **Check current state**:
   ```bash
   python python_scripts/automated_pipeline.py --check-naming
   ```

2. **Follow the suggested rename commands**

3. **Verify standardization**:
   ```bash
   python python_scripts/automated_pipeline.py --list
   ```

4. **Process datasets**:
   ```bash
   python python_scripts/automated_pipeline.py
   ```

This standardized approach eliminates the need for hardcoded file mappings and makes your pipeline truly dynamic and scalable!