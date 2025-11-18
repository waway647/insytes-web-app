# Updated File Naming Convention

## Overview
The system now supports an improved naming convention for better match organization and identification.

## New Naming Convention

### Config Files (Team Data)
**Location**: `writable_data/configs/`
**Format**: `config_match_{number}_sbu_vs_{opponent-name}.json`

**Examples**:
- `config_match_1_sbu_vs_up.json`
- `config_match_2_sbu_vs_siniloan.json`
- `config_match_3_sbu_vs_2worlds.json`

### CSV Files (Event Data)
**Location**: `output_dataset/`
**Format**: `match_{number}_sbu_vs_{opponent-name}_events.csv`

**Examples**:
- `match_1_sbu_vs_up_events.csv`
- `match_2_sbu_vs_siniloan_events.csv` 
- `match_3_sbu_vs_2worlds_events.csv`

## Benefits

1. **Sequential Organization**: Match numbers provide chronological order
2. **Consistent Structure**: Predictable naming pattern for automation
3. **Clear Identification**: Easy to identify matches and opponents
4. **Scalability**: Supports unlimited matches with incremental numbering

## Automated Pipeline Support

The automated pipeline now supports both:
- ✅ **New naming convention** (recommended)
- ✅ **Legacy naming convention** (backward compatibility)

### Check Current Naming
```bash
python python_scripts/automated_pipeline.py --check-naming
```

### List Datasets
```bash
python python_scripts/automated_pipeline.py --list
```

## Migration from Legacy Format

The system automatically detects legacy files and provides migration commands:

```bash
# Example migration commands (auto-generated)
mv "data/team_data/config_sbu_vs_up.json" "data/team_data/config_match_1_sbu_vs_up.json"
mv "data/sbu_vs_up.csv" "data/match_1_sbu_vs_up.csv"
```

## Usage Examples

### Process a specific match
```bash
python python_scripts/automated_pipeline.py --dataset match_2_sbu_vs_siniloan
```

### Process all matches
```bash
python python_scripts/automated_pipeline.py
```

### Check processing status
```bash
python python_scripts/automated_pipeline.py --status
```

## File Structure
```
writable_data/
└── configs/
    ├── config_match_1_sbu_vs_up.json
    ├── config_match_2_sbu_vs_siniloan.json
    └── config_match_3_sbu_vs_2worlds.json

output_dataset/
├── match_1_sbu_vs_up_events.csv
├── match_2_sbu_vs_siniloan_events.csv 
└── match_3_sbu_vs_2worlds_events.csv
```

## Important Notes

1. **Match numbers** should be sequential (1, 2, 3...)
2. **Opponent names** should be lowercase with underscores
3. **File pairing** is automatic - each config file must have a corresponding CSV file
4. **Legacy files** continue to work but new naming is recommended for consistency

## Implementation Status

✅ Automated pipeline updated  
✅ Discovery system supports both formats  
✅ Standardization checker implemented  
✅ Migration suggestions automated  
✅ Backward compatibility maintained  

The system is now ready for the improved naming convention while maintaining full compatibility with existing files.