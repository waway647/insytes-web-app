#!/usr/bin/env python3
"""
Fix Git merge conflicts in JSON files
"""

import re
import sys
from pathlib import Path

def fix_json_conflicts(file_path):
    """Remove Git merge conflict markers from JSON files"""
    print(f"Fixing conflicts in: {file_path}")
    
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Count conflicts before
    conflicts_before = content.count('<<<<<<< ')
    
    # Remove conflict markers and keep the "upstream" version (first part)
    # Pattern: <<<<<<< ... \n content1 \n ======= \n content2 \n >>>>>>> ...
    pattern = r'<<<<<<< [^\n]*\n(.*?)\n=======\n.*?\n>>>>>>> [^\n]*\n'
    
    # Replace conflicts, keeping the upstream content
    content = re.sub(pattern, r'\1\n', content, flags=re.DOTALL)
    
    # Count conflicts after
    conflicts_after = content.count('<<<<<<< ')
    
    if conflicts_before > 0:
        print(f"  Fixed {conflicts_before} conflicts")
        
        # Write back the fixed content
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(content)
        
        if conflicts_after == 0:
            print(f"  ✅ All conflicts resolved")
        else:
            print(f"  ⚠️ {conflicts_after} conflicts remaining")
    else:
        print(f"  ✅ No conflicts found")

def main():
    """Main function"""
    if len(sys.argv) > 1:
        # Fix specific file
        file_path = Path(sys.argv[1])
        if file_path.exists():
            fix_json_conflicts(file_path)
        else:
            print(f"File not found: {file_path}")
    else:
        # Fix all JSON files in events directory
        events_dir = Path(__file__).parent.parent / "writable_data" / "events"
        json_files = list(events_dir.glob("*.json"))
        
        print(f"Checking {len(json_files)} JSON files...")
        
        for json_file in json_files:
            fix_json_conflicts(json_file)
        
        print("Done!")

if __name__ == "__main__":
    main()