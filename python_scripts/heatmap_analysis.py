# ============================================================
# File: python_scripts/heatmap_analysis.py
# Purpose: Generate comprehensive heatmap analysis combining position data with enhanced insights
# Features: Tactical analysis, position compliance, coaching recommendations
# ============================================================

import pandas as pd
import numpy as np
import json
import os
import sys
import argparse
from datetime import datetime

# Command line argument parsing
parser = argparse.ArgumentParser(description='Generate heatmap analysis')
parser.add_argument('--dataset', default='sbu_vs_2worlds', help='Dataset name (e.g., sbu_vs_2worlds)')
args = parser.parse_args()

MATCH_NAME = args.dataset

# ---------- CONFIG ----------
# Use match-specific output directories
MATCH_OUTPUT_DIR = f"output/matches/{MATCH_NAME}"
EVENTS_CSV = f"data/{MATCH_NAME}.csv"
PLAYER_METRICS_JSON = f"{MATCH_OUTPUT_DIR}/sanbeda_players_derived_metrics.json"
INSIGHTS_JSON = f"{MATCH_OUTPUT_DIR}/sanbeda_player_insights.json"
OUTPUT_FILE = f"{MATCH_OUTPUT_DIR}/heatmap_analysis_report.json"

def analyze_positioning_compliance(player_name, events_df, position):
    """Analyze how well a player stayed in their expected position zones"""
    player_events = events_df[events_df['player_name'] == player_name].copy()
    valid_events = player_events.dropna(subset=['origin_x', 'origin_y'])
    
    if len(valid_events) == 0:
        return {"compliance_score": 0, "zone_distribution": {}, "insights": []}
    
    # Define expected zones for each position
    position_zones = {
        "GK": {"primary": (0, 20), "secondary": (20, 35), "danger": (35, 105)},
        "CB": {"primary": (0, 35), "secondary": (35, 55), "danger": (55, 105)},
        "LWB": {"primary": (15, 70), "secondary": (70, 90), "danger": (0, 15)},
        "RWB": {"primary": (15, 70), "secondary": (70, 90), "danger": (0, 15)},
        "CDM": {"primary": (25, 75), "secondary": (15, 25), "danger": (75, 105)},
        "CAM": {"primary": (45, 85), "secondary": (25, 45), "danger": (85, 105)},
        "LW": {"primary": (60, 105), "secondary": (40, 60), "danger": (0, 40)},
        "RW": {"primary": (60, 105), "secondary": (40, 60), "danger": (0, 40)},
        "ST": {"primary": (70, 105), "secondary": (50, 70), "danger": (0, 50)}
    }
    
    zones = position_zones.get(position, position_zones["CB"])  # Default to CB
    
    # Count events in each zone
    zone_counts = {}
    insights = []
    
    for zone_type, (min_x, max_x) in zones.items():
        zone_events = valid_events[
            (valid_events['origin_x'] >= min_x) & 
            (valid_events['origin_x'] < max_x)
        ]
        zone_counts[zone_type] = len(zone_events)
    
    total_events = sum(zone_counts.values())
    zone_percentages = {k: (v/total_events)*100 if total_events > 0 else 0 for k, v in zone_counts.items()}
    
    # Calculate compliance score
    primary_pct = zone_percentages.get("primary", 0)
    secondary_pct = zone_percentages.get("secondary", 0)
    danger_pct = zone_percentages.get("danger", 0)
    
    # Score based on expected position behavior
    compliance_score = primary_pct * 0.7 + secondary_pct * 0.3 - danger_pct * 0.5
    compliance_score = max(0, min(100, compliance_score))
    
    # Generate tactical insights
    if primary_pct < 40:
        insights.append(f"POSITIONING ISSUE: Only {primary_pct:.1f}% of actions in primary zone - needs to maintain positional discipline")
    elif primary_pct > 70:
        insights.append(f"EXCELLENT positioning: {primary_pct:.1f}% of actions in correct zone - maintains tactical discipline")
    
    if danger_pct > 25:
        insights.append(f"HIGH RISK: {danger_pct:.1f}% of actions in danger zone - avoid leaving position unattended")
    
    if secondary_pct > 40:
        insights.append(f"GOOD mobility: {secondary_pct:.1f}% in secondary zone shows good tactical flexibility")
    
    return {
        "compliance_score": round(compliance_score, 1),
        "zone_distribution": zone_percentages,
        "total_events": total_events,
        "insights": insights
    }

def analyze_heat_patterns(player_name, events_df):
    """Analyze heat patterns and concentration areas"""
    player_events = events_df[events_df['player_name'] == player_name].copy()
    valid_events = player_events.dropna(subset=['origin_x', 'origin_y'])
    
    if len(valid_events) == 0:
        return {"concentration_score": 0, "coverage_score": 0, "patterns": []}
    
    x_coords = valid_events['origin_x'].values
    y_coords = valid_events['origin_y'].values
    
    # Calculate concentration (how focused the player's activity is)
    x_std = np.std(x_coords)
    y_std = np.std(y_coords) 
    concentration_score = max(0, 100 - (x_std + y_std))  # Lower std = higher concentration
    
    # Calculate coverage (how much area the player covers)
    x_range = np.ptp(x_coords)  # Peak-to-peak (max - min)
    y_range = np.ptp(y_coords)
    coverage_score = min(100, (x_range + y_range) * 1.5)  # Higher range = better coverage
    
    # Analyze movement patterns
    patterns = []
    
    if concentration_score > 70:
        patterns.append(f"HIGH CONCENTRATION: Stays disciplined in core area (score: {concentration_score:.1f})")
    elif concentration_score < 30:
        patterns.append(f"LOW CONCENTRATION: Scattered positioning needs improvement (score: {concentration_score:.1f})")
    
    if coverage_score > 60:
        patterns.append(f"EXCELLENT COVERAGE: Good tactical mobility (score: {coverage_score:.1f})")
    elif coverage_score < 30:
        patterns.append(f"LIMITED COVERAGE: May be too static in positioning (score: {coverage_score:.1f})")
    
    # Check for balance between concentration and coverage
    if abs(concentration_score - coverage_score) < 20:
        patterns.append("BALANCED: Good mix of positional discipline and tactical mobility")
    elif concentration_score > coverage_score + 30:
        patterns.append("TOO STATIC: Consider more tactical movement when appropriate")
    elif coverage_score > concentration_score + 30:
        patterns.append("TOO ROAMING: Focus on maintaining core position")
    
    return {
        "concentration_score": round(concentration_score, 1),
        "coverage_score": round(coverage_score, 1),
        "x_range": round(x_range, 1),
        "y_range": round(y_range, 1),
        "patterns": patterns
    }

def analyze_match_phases(player_name, events_df):
    """Analyze player activity across match phases"""
    player_events = events_df[events_df['player_name'] == player_name].copy()
    
    # Define time phases
    phases = {
        "Early (0-30min)": (0, 30),
        "Middle (30-60min)": (30, 60), 
        "Late (60-90min)": (60, 90),
        "Extra Time": (90, 120)
    }
    
    phase_analysis = {}
    
    for phase_name, (start_min, end_min) in phases.items():
        phase_events = player_events[
            (player_events['time_min'] >= start_min) & 
            (player_events['time_min'] < end_min)
        ] if 'time_min' in player_events.columns else pd.DataFrame()
        
        if len(phase_events) > 0:
            # Analyze event types in this phase
            event_counts = phase_events['event'].value_counts().to_dict()
            total_events = len(phase_events)
            
            # Calculate activity intensity
            minutes_in_phase = min(end_min - start_min, 90 - start_min if start_min < 90 else 0)
            activity_rate = total_events / minutes_in_phase if minutes_in_phase > 0 else 0
            
            phase_analysis[phase_name] = {
                "total_events": total_events,
                "activity_rate": round(activity_rate, 2),
                "top_events": dict(list(event_counts.items())[:3]),
                "event_variety": len(event_counts)
            }
    
    # Generate insights about match phases
    insights = []
    if phase_analysis:
        activities = [data["activity_rate"] for data in phase_analysis.values()]
        if activities:
            max_activity = max(activities)
            min_activity = min(activities)
            
            if max_activity > min_activity * 2:
                peak_phase = max(phase_analysis.items(), key=lambda x: x[1]["activity_rate"])[0]
                insights.append(f"PEAK PERFORMANCE: Most active during {peak_phase}")
            
            if min_activity < max_activity * 0.5:
                low_phase = min(phase_analysis.items(), key=lambda x: x[1]["activity_rate"])[0]
                insights.append(f"LOW ACTIVITY: Least active during {low_phase} - monitor fatigue/involvement")
    
    return {
        "phases": phase_analysis,
        "insights": insights
    }

def generate_comprehensive_analysis():
    """Generate comprehensive heatmap analysis report"""
    print("Loading data...")
    
    # Load event data
    events_df = pd.read_csv(EVENTS_CSV)
    
    # Load player metrics
    with open(PLAYER_METRICS_JSON, 'r') as f:
        metrics_data = json.load(f)
        player_metrics = metrics_data.get("San Beda", {})
    
    # Load insights
    insights_data = {}
    if os.path.exists(INSIGHTS_JSON):
        with open(INSIGHTS_JSON, 'r') as f:
            insights_raw = json.load(f)
            insights_data = insights_raw.get("San Beda", {})
    
    # Generate comprehensive analysis
    comprehensive_analysis = {
        "report_generated": datetime.now().isoformat(),
        "match_info": {
            "total_events": len(events_df),
            "players_analyzed": len(player_metrics),
            "events_with_coordinates": len(events_df.dropna(subset=['origin_x', 'origin_y']))
        },
        "players": {}
    }
    
    print("Analyzing players...")
    for player_name, metrics in player_metrics.items():
        print(f"Processing {player_name}...")
        
        position = metrics.get('position', 'Unknown')
        enhanced_rating = metrics.get('enhanced_dpr', metrics.get('dpr', 0))
        breakdown = metrics.get('enhanced_breakdown', metrics.get('dpr_breakdown', {}))
        
        # Get insights
        player_insights = insights_data.get(player_name, {})
        specialized_insight = player_insights.get('specialized_insight', '')
        
        # Perform heatmap analyses
        positioning_analysis = analyze_positioning_compliance(player_name, events_df, position)
        heat_analysis = analyze_heat_patterns(player_name, events_df)
        phase_analysis = analyze_match_phases(player_name, events_df)
        
        # Combine everything
        comprehensive_analysis["players"][player_name] = {
            "basic_info": {
                "position": position,
                "enhanced_rating": enhanced_rating,
                "enhanced_breakdown": breakdown
            },
            "positioning_analysis": positioning_analysis,
            "heat_patterns": heat_analysis,
            "match_phases": phase_analysis,
            "enhanced_insights": specialized_insight,
            "tactical_summary": generate_tactical_summary(
                position, enhanced_rating, positioning_analysis, heat_analysis, specialized_insight
            )
        }
    
    # Save comprehensive analysis
    with open(OUTPUT_FILE, 'w') as f:
        json.dump(comprehensive_analysis, f, indent=2)
    
    print(f"Comprehensive analysis saved to: {OUTPUT_FILE}")
    return comprehensive_analysis

def generate_tactical_summary(position, rating, positioning, heat_patterns, insights):
    """Generate tactical summary combining all analyses"""
    summary = []
    
    # Overall assessment
    if rating > 70:
        summary.append(f"[STRONG] PERFORMER: {rating:.1f}/100 overall rating")
    elif rating > 50:
        summary.append(f"[SOLID] CONTRIBUTOR: {rating:.1f}/100 overall rating")
    else:
        summary.append(f"[WARNING] NEEDS IMPROVEMENT: {rating:.1f}/100 overall rating")
    
    # Positioning assessment
    compliance = positioning["compliance_score"]
    if compliance > 75:
        summary.append("[EXCELLENT] positioning discipline")
    elif compliance > 50:
        summary.append("[GOOD] positional awareness") 
    else:
        summary.append("[POOR] positional discipline - key focus area")
    
    # Movement patterns
    concentration = heat_patterns["concentration_score"]
    coverage = heat_patterns["coverage_score"]
    
    if concentration > 70 and coverage > 60:
        summary.append("[PERFECT] balance of discipline and mobility")
    elif concentration > 70:
        summary.append("[DISCIPLINED] positioning but could be more mobile")
    elif coverage > 60:
        summary.append("[GOOD] mobility but needs better positioning discipline")
    else:
        summary.append("[NEEDS] tactical positioning improvement")
    
    # Add key insights
    if insights:
        key_points = insights.split('.')[0:2]  # First two sentences
        for point in key_points:
            if point.strip():
                summary.append(f"[TIP] {point.strip()}")
    
    return summary

def main():
    """Main execution function"""
    analysis = generate_comprehensive_analysis()
    
    print("\n" + "="*60)
    print("COMPREHENSIVE HEATMAP ANALYSIS COMPLETE")
    print("="*60)
    
    # Print key findings
    for player_name, data in list(analysis["players"].items())[:3]:  # Show first 3 players
        print(f"\n[ANALYSIS] {player_name} ({data['basic_info']['position']}):")
        for point in data["tactical_summary"]:
            print(f"   {point}")
    
    print(f"\nFull analysis available in: {OUTPUT_FILE}")

if __name__ == "__main__":
    main()