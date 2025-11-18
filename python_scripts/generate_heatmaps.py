# ============================================================
# File: python_scripts/generate_heatmaps.py
# Purpose: Generate player heatmaps using tagged event coordinates
# Features: Position heatmaps, pass networks, zone analysis
# ============================================================

import pandas as pd
import numpy as np
import matplotlib.pyplot as plt
import matplotlib.patches as patches
from matplotlib.patches import Rectangle, Circle, FancyBboxPatch
import seaborn as sns
from scipy.stats import gaussian_kde
import os
import json
import argparse

# Command line argument parsing
parser = argparse.ArgumentParser(description='Generate player heatmaps and visualizations')
parser.add_argument('--dataset', default='sbu_vs_2worlds', help='Dataset name (e.g., sbu_vs_2worlds)')
args = parser.parse_args()

MATCH_NAME = args.dataset

# ---------- CONFIG ----------
EVENTS_CSV = f"output_dataset/{MATCH_NAME}_events.csv"
MATCH_CONFIG_JSON = f"writable_data/configs/config_{MATCH_NAME}.json"

# Load team name from config file (use home team as featured team)
try:
    with open(MATCH_CONFIG_JSON, "r", encoding="utf-8") as f:
        config_data = json.load(f)
    TEAM_NAME = config_data["home"]["name"]
    print(f"Using home team as featured team: {TEAM_NAME}")
except (FileNotFoundError, KeyError) as e:
    print(f"Could not load team name from config: {e}")
    TEAM_NAME = "San Beda"  # Fallback
    print(f"Using fallback team name: {TEAM_NAME}")

team_name_safe = TEAM_NAME.lower().replace(" ", "_")
PLAYER_METRICS_JSON = f"output/matches/{MATCH_NAME}/{team_name_safe}_players_derived_metrics.json"
OUTPUT_DIR = f"output/matches/{MATCH_NAME}/heatmaps"

# Pitch dimensions (based on tagging system)
PITCH_LENGTH = 100
PITCH_WIDTH = 68

# Load match configuration
MATCH_CONFIG = None
if os.path.exists(MATCH_CONFIG_JSON):
    try:
        with open(MATCH_CONFIG_JSON, 'r', encoding='utf-8') as f:
            MATCH_CONFIG = json.load(f)
        print(f"Loaded match config: attacking_direction = {MATCH_CONFIG.get('attacking_direction', 'not specified')}")
    except Exception as e:
        print(f"Warning: Could not load match config {MATCH_CONFIG_JSON}: {e}")
        MATCH_CONFIG = None
else:
    print(f"Warning: Match config file {MATCH_CONFIG_JSON} not found")

def normalize_coordinates(x_coords, y_coords, half_period, attacking_direction=None):
    """
    Normalize coordinates to maintain consistent field representation
    regardless of which side the team is attacking in each half.
    
    Args:
        x_coords: Array of x coordinates
        y_coords: Array of y coordinates  
        half_period: Array of half periods (1 or 2)
        attacking_direction: "left-to-right" or "right-to-left" from config
    
    Returns:
        Tuple of (normalized_x, normalized_y) arrays
    """
    if attacking_direction is None:
        # No normalization if direction not specified
        return x_coords.copy(), y_coords.copy()
    
    x_norm = x_coords.copy()
    y_norm = y_coords.copy()
    
    # For consistency, we want to normalize so that team always appears 
    # to be attacking from left to right in the visualization
    if attacking_direction == "right-to-left":
        # In 1st half: team attacks right-to-left (towards x=0)
        # In 2nd half: team attacks left-to-right (towards x=105) 
        # We want to show them consistently attacking left-to-right
        
        # Normalize 1st half: flip coordinates horizontally
        first_half_mask = (half_period == 1)
        x_norm[first_half_mask] = PITCH_LENGTH - x_coords[first_half_mask]
        y_norm[first_half_mask] = PITCH_WIDTH - y_coords[first_half_mask]
        
        # 2nd half coordinates are already correct (team attacking left-to-right)
        
    elif attacking_direction == "left-to-right":
        # In 1st half: team attacks left-to-right (towards x=105)
        # In 2nd half: team attacks right-to-left (towards x=0)
        # We want to show them consistently attacking left-to-right
        
        # 1st half coordinates are already correct
        
        # Normalize 2nd half: flip coordinates horizontally  
        second_half_mask = (half_period == 2)
        x_norm[second_half_mask] = PITCH_LENGTH - x_coords[second_half_mask]
        y_norm[second_half_mask] = PITCH_WIDTH - y_coords[second_half_mask]
    
    return x_norm, y_norm

# Create output directories
os.makedirs(OUTPUT_DIR, exist_ok=True)

# ---------- PITCH DRAWING FUNCTIONS ----------
def draw_pitch(ax, pitch_color='#2e8b57', line_color='white', linewidth=2):
    """Draw a football pitch on the given axes"""
    # Pitch outline
    pitch = Rectangle((0, 0), PITCH_LENGTH, PITCH_WIDTH, linewidth=linewidth,
                     edgecolor=line_color, facecolor=pitch_color)
    ax.add_patch(pitch)
    
    # Centre line
    ax.plot([PITCH_LENGTH/2, PITCH_LENGTH/2], [0, PITCH_WIDTH], 
            color=line_color, linewidth=linewidth)
    
    # Centre circle
    centre_circle = Circle((PITCH_LENGTH/2, PITCH_WIDTH/2), 9.15,
                          linewidth=linewidth, color=line_color, fill=False)
    ax.add_patch(centre_circle)
    
    # Centre spot
    ax.plot(PITCH_LENGTH/2, PITCH_WIDTH/2, 'o', color=line_color, markersize=3)
    
    # Left penalty area (scaled to 100-unit pitch)
    left_penalty = Rectangle((0, 13.84), 15.7, 40.32,
                           linewidth=linewidth, edgecolor=line_color, facecolor='none')
    ax.add_patch(left_penalty)
    
    # Left 6-yard box (scaled to 100-unit pitch)
    left_six_yard = Rectangle((0, 24.84), 5.2, 18.32,
                             linewidth=linewidth, edgecolor=line_color, facecolor='none')
    ax.add_patch(left_six_yard)
    
    # Right penalty area (scaled to 100-unit pitch)
    right_penalty = Rectangle((84.3, 13.84), 15.7, 40.32,
                            linewidth=linewidth, edgecolor=line_color, facecolor='none')
    ax.add_patch(right_penalty)
    
    # Right 6-yard box (scaled to 100-unit pitch)
    right_six_yard = Rectangle((94.8, 24.84), 5.2, 18.32,
                              linewidth=linewidth, edgecolor=line_color, facecolor='none')
    ax.add_patch(right_six_yard)
    
    # Goal posts
    ax.plot([0, 0], [30.34, 37.66], color=line_color, linewidth=linewidth+1)
    ax.plot([PITCH_LENGTH, PITCH_LENGTH], [30.34, 37.66], color=line_color, linewidth=linewidth+1)
    
    # Set limits and aspect
    ax.set_xlim(0, PITCH_LENGTH)
    ax.set_ylim(0, PITCH_WIDTH)
    ax.set_aspect('equal')
    ax.axis('off')

def add_pitch_zones(ax, alpha=0.1):
    """Add zone overlays for analysis (40% defensive, 35% middle, 25% attacking)"""
    # Defensive third (40% of pitch)
    defensive_zone = Rectangle((0, 0), 40, PITCH_WIDTH, 
                              facecolor='red', alpha=alpha)
    ax.add_patch(defensive_zone)
    
    # Middle third (35% of pitch)  
    middle_zone = Rectangle((40, 0), 35, PITCH_WIDTH,
                           facecolor='yellow', alpha=alpha)
    ax.add_patch(middle_zone)
    
    # Attacking third (25% of pitch)
    attacking_zone = Rectangle((75, 0), 25, PITCH_WIDTH,
                             facecolor='green', alpha=alpha)
    ax.add_patch(attacking_zone)

# ---------- HEATMAP GENERATION ----------
def generate_player_heatmap(player_name, events_df, save_path=None):
    """Generate position heatmap for a specific player"""
    # Filter events for the player
    player_events = events_df[events_df['player_name'] == player_name].copy()
    
    if len(player_events) == 0:
        print(f"No events found for {player_name}")
        return None

    # Extract valid coordinates
    valid_coords = player_events.dropna(subset=['origin_x', 'origin_y']).copy()
    if len(valid_coords) == 0:
        print(f"No valid coordinates for {player_name}")
        return None

    # Get attacking direction from config
    attacking_direction = MATCH_CONFIG.get('attacking_direction') if MATCH_CONFIG else None
    
    # Get coordinates and half periods
    x_coords = valid_coords['origin_x'].values
    y_coords = valid_coords['origin_y'].values
    half_periods = valid_coords['half_period'].values if 'half_period' in valid_coords.columns else np.ones(len(x_coords))
    
    # Normalize coordinates for consistent field representation
    x_coords_norm, y_coords_norm = normalize_coordinates(x_coords, y_coords, half_periods, attacking_direction)
    
    # Create figure
    fig, ax = plt.subplots(figsize=(12, 8))
    
    # Draw pitch
    draw_pitch(ax)
    add_pitch_zones(ax, alpha=0.05)
    
    # Create 2D histogram for heatmap
    bins_x = np.linspace(0, PITCH_LENGTH, 21)  # 5m bins
    bins_y = np.linspace(0, PITCH_WIDTH, 15)   # ~4.5m bins
    
    heatmap, xedges, yedges = np.histogram2d(x_coords_norm, y_coords_norm, bins=[bins_x, bins_y])
    
    # Smooth with Gaussian if enough points
    if len(x_coords_norm) > 5:
        try:
            # Create a kde and evaluate on a grid
            values = np.vstack([x_coords_norm, y_coords_norm])
            kernel = gaussian_kde(values)
            
            # Create evaluation grid
            X, Y = np.meshgrid(np.linspace(0, PITCH_LENGTH, 50),
                              np.linspace(0, PITCH_WIDTH, 30))
            positions = np.vstack([X.ravel(), Y.ravel()])
            Z = np.reshape(kernel(positions), X.shape)
            
            # Plot smooth heatmap
            im = ax.contourf(X, Y, Z, levels=15, cmap='Reds', alpha=0.6)
            
        except Exception as e:
            print(f"KDE failed for {player_name}, using histogram: {e}")
            # Fallback to histogram
            extent = [0, PITCH_LENGTH, 0, PITCH_WIDTH]
            im = ax.imshow(heatmap.T, extent=extent, origin='lower', 
                          cmap='Reds', alpha=0.6, interpolation='bilinear')
    else:
        # Just plot points for low sample size
        ax.scatter(x_coords_norm, y_coords_norm, c='red', s=50, alpha=0.8, edgecolors='darkred')
    
    # Add player position points as overlay
    ax.scatter(x_coords_norm, y_coords_norm, c='darkred', s=20, alpha=0.4, edgecolors='black', linewidth=0.5)
    
    # Get player position and stats
    position = player_events['position'].iloc[0] if 'position' in player_events.columns else "Unknown"
    
    # Title and labels
    ax.set_title(f'{player_name} ({position}) - Position Heatmap\n{len(player_events)} total events, {len(valid_coords)} with coordinates', 
                fontsize=14, fontweight='bold')
    
    plt.tight_layout()
    
    # Save if path provided
    if save_path:
        plt.savefig(save_path, dpi=300, bbox_inches='tight')
        print(f"Heatmap saved: {save_path}")
    
    return fig, ax

def generate_pass_network(events_df, save_path=None):
    """Generate pass network visualization"""
    # Filter pass events for the team
    team_passes = events_df[
        (events_df['team'].str.lower() == TEAM_NAME.lower()) & 
        (events_df['event'] == 'Pass') &
        (events_df['outcome'] == 'Successful')
    ].copy()
    
    if len(team_passes) == 0:
        print(f"No successful passes found for {TEAM_NAME}")
        return None
    
    # Calculate average positions for each player
    player_positions = {}
    pass_connections = {}
    
    # Get attacking direction from config
    attacking_direction = MATCH_CONFIG.get('attacking_direction') if MATCH_CONFIG else None
    
    for _, pass_event in team_passes.iterrows():
        passer = pass_event['player_name']
        receiver = pass_event.get('receiver_name', '')
        
        # Store passer position with normalization
        if passer and pd.notna(pass_event['origin_x']) and pd.notna(pass_event['origin_y']):
            if passer not in player_positions:
                player_positions[passer] = {'x': [], 'y': [], 'half_periods': [], 'position': pass_event.get('position', '')}
            
            # Get half period (default to 1 if not available)
            half_period = pass_event.get('half_period', 1)
            
            # Apply coordinate normalization
            x_norm, y_norm = normalize_coordinates(
                np.array([pass_event['origin_x']]), 
                np.array([pass_event['origin_y']]), 
                np.array([half_period]), 
                attacking_direction
            )
            
            player_positions[passer]['x'].append(x_norm[0])
            player_positions[passer]['y'].append(y_norm[0])
            player_positions[passer]['half_periods'].append(half_period)
        
        # Count pass connections
        if passer and receiver and passer != receiver:
            connection = (passer, receiver)
            pass_connections[connection] = pass_connections.get(connection, 0) + 1
    
    # Calculate average positions
    avg_positions = {}
    for player, data in player_positions.items():
        if data['x'] and data['y']:
            avg_positions[player] = {
                'x': np.mean(data['x']),
                'y': np.mean(data['y']),
                'position': data['position']
            }
    
    if len(avg_positions) < 2:
        print(f"Not enough players with position data for {TEAM_NAME}")
        return None
    
    # Create figure
    fig, ax = plt.subplots(figsize=(14, 9))
    
    # Draw pitch
    draw_pitch(ax)
    
    # Plot pass connections
    for (passer, receiver), count in pass_connections.items():
        if passer in avg_positions and receiver in avg_positions and count >= 3:  # Only show frequent connections
            x1, y1 = avg_positions[passer]['x'], avg_positions[passer]['y']
            x2, y2 = avg_positions[receiver]['x'], avg_positions[receiver]['y']
            
            # Line thickness based on pass frequency
            linewidth = min(count / 2, 5)
            ax.plot([x1, x2], [y1, y2], 'b-', alpha=0.6, linewidth=linewidth)
            
            # Add pass count annotation at midpoint
            mid_x, mid_y = (x1 + x2) / 2, (y1 + y2) / 2
            if count >= 5:  # Only label frequent connections
                ax.annotate(str(count), (mid_x, mid_y), fontsize=8, ha='center', va='center',
                           bbox=dict(boxstyle="round,pad=0.2", facecolor='white', alpha=0.7))
    
    # Plot player average positions
    for player, data in avg_positions.items():
        x, y = data['x'], data['y']
        position_color = {'GK': 'yellow', 'CB': 'red', 'LWB': 'orange', 'RWB': 'orange',
                         'CDM': 'blue', 'CAM': 'lightblue', 'LW': 'green', 'RW': 'green', 'ST': 'purple'}.get(data['position'], 'gray')
        
        # Player circle
        circle = Circle((x, y), 2, color=position_color, alpha=0.8, edgecolor='black', linewidth=2)
        ax.add_patch(circle)
        
        # Player name
        ax.annotate(player.split()[-1] if ' ' in player else player[:8], (x, y), 
                   fontsize=9, ha='center', va='center', fontweight='bold')
    
    ax.set_title(f'{TEAM_NAME} - Pass Network\nAverage positions and pass connections (3+ passes)', 
                fontsize=14, fontweight='bold')
    
    plt.tight_layout()
    
    if save_path:
        plt.savefig(save_path, dpi=300, bbox_inches='tight')
        print(f"Pass network saved: {save_path}")
    
    return fig, ax

def generate_team_overall_heatmap(events_df, save_path=None):
    """Generate overall team heatmap showing collective positioning"""
    # Filter all events for the team (not just passes)
    team_events = events_df[
        (events_df['team'].str.lower() == TEAM_NAME.lower()) & 
        (events_df['player_name'].notna()) & 
        (events_df['origin_x'].notna()) & 
        (events_df['origin_y'].notna())
    ].copy()
    
    if len(team_events) == 0:
        print(f"No position data found for {TEAM_NAME}")
        return None
    
    # Extract all team coordinates  
    x_coords = team_events['origin_x'].values
    y_coords = team_events['origin_y'].values
    half_periods = team_events['half_period'].values if 'half_period' in team_events.columns else np.ones(len(x_coords))
    
    # Get attacking direction from config and normalize coordinates
    attacking_direction = MATCH_CONFIG.get('attacking_direction') if MATCH_CONFIG else None
    x_coords_norm, y_coords_norm = normalize_coordinates(x_coords, y_coords, half_periods, attacking_direction)
    
    print(f"Team events with coordinates: {len(x_coords_norm)}")  # Debug info
    print(f"Normalized coordinate ranges: X({x_coords_norm.min():.1f}-{x_coords_norm.max():.1f}), Y({y_coords_norm.min():.1f}-{y_coords_norm.max():.1f})")
    print(f"Original coordinate ranges: X({x_coords.min():.1f}-{x_coords.max():.1f}), Y({y_coords.min():.1f}-{y_coords.max():.1f})")
    
    # Create figure with proper black background like the sample
    fig, ax = plt.subplots(figsize=(16, 10), facecolor='black')
    ax.set_facecolor('black')
    
    # Draw pitch with white lines on black background
    draw_pitch(ax, pitch_color='black', line_color='white', linewidth=2)
    # Skip pitch zones for cleaner look like the sample
    
    # Create smooth heatmap using a more direct approach for guaranteed visibility
    try:
        # Create a simple 2D histogram first to ensure we have visible data
        bins_x = np.linspace(0, PITCH_LENGTH, 50)
        bins_y = np.linspace(0, PITCH_WIDTH, 35)
        heatmap, xedges, yedges = np.histogram2d(x_coords_norm, y_coords_norm, bins=[bins_x, bins_y])
        
        # Apply Gaussian filter for smoothing
        from scipy import ndimage
        heatmap_smooth = ndimage.gaussian_filter(heatmap, sigma=2.0)
        
        # Aggressive normalization to ensure visibility
        if heatmap_smooth.max() > 0:
            heatmap_smooth = heatmap_smooth / heatmap_smooth.max()
            # Apply strong power transformation to boost visibility
            heatmap_smooth = np.power(heatmap_smooth, 0.3)
        
        extent = [0, PITCH_LENGTH, 0, PITCH_WIDTH]
        
        # Very simple, high-contrast colormap
        import matplotlib.colors as mcolors
        colors = [(0, 0, 0, 0), (1, 0, 0, 0.7), (1, 0.5, 0, 0.9), (1, 1, 0, 1)]
        n_bins = 256
        simple_cmap = mcolors.LinearSegmentedColormap.from_list('simple', colors, N=n_bins)
        
        # Use imshow with strong settings
        im = ax.imshow(heatmap_smooth.T, extent=extent, origin='lower', 
                      cmap=simple_cmap, alpha=1.0, interpolation='bilinear',
                      aspect='equal', vmin=0.01, vmax=1.0)  # Force vmin to show low values
        
        # Larger, more visible scatter points as backup
        ax.scatter(x_coords, y_coords, c='yellow', s=12, alpha=0.9, edgecolors='red', linewidths=0.8)
        
        print(f"Histogram heat data range: {heatmap_smooth.min():.3f} to {heatmap_smooth.max():.3f}")
        print(f"Number of non-zero bins: {np.count_nonzero(heatmap_smooth)}")
        
    except Exception as e:
        print(f"Even simple histogram failed: {e}")
        # Last resort: just show bigger scatter points
        ax.scatter(x_coords, y_coords, c='red', s=15, alpha=0.9, edgecolors='yellow', linewidths=1.2)
        im = None
        
    # Simple colorbar that will work even with scatter-only fallback
    if im is not None:
        cbar = plt.colorbar(im, ax=ax, shrink=0.6, pad=0.02, aspect=20)
        cbar.set_label('Activity Intensity', rotation=270, labelpad=20, fontsize=12, color='white')
        cbar.ax.tick_params(labelsize=10, colors='white')
        cbar.outline.set_edgecolor('white')
    
    # Calculate some team stats
    total_events = len(team_events)
    unique_players = team_events['player_name'].nunique()
    avg_x_position = np.mean(x_coords)
    
    # Determine team's attacking direction and overall positioning
    attacking_direction = "→" if avg_x_position > PITCH_LENGTH/2 else "←"
    field_coverage = f"{(np.max(x_coords) - np.min(x_coords)):.0f}m × {(np.max(y_coords) - np.min(y_coords)):.0f}m"
    
    # Title with enhanced styling to match sample appearance
    ax.set_title(f'{TEAM_NAME} - Team Activity Heatmap {attacking_direction}\n'
                f'{total_events} events from {unique_players} players | Coverage: {field_coverage}', 
                fontsize=18, fontweight='bold', pad=25, color='white')
    
    # Remove axis labels and ticks for cleaner look
    ax.set_xticks([])
    ax.set_yticks([])
    ax.set_xlabel('')
    ax.set_ylabel('')
    
    plt.tight_layout()
    
    # Save if path provided
    if save_path:
        plt.savefig(save_path, dpi=300, bbox_inches='tight')
        print(f"Team heatmap saved: {save_path}")
    
    return fig, ax

def generate_zone_analysis(player_name, events_df, save_path=None):
    """Generate zone-based analysis for a player"""
    player_events = events_df[events_df['player_name'] == player_name].copy()
    
    if len(player_events) == 0:
        return None
    
    # Define zones (based on tagging system percentages: 40% defensive, 35% middle, 25% attacking)
    zones = {
        'Defensive Third': (0, 40),     # 40% of 100 = 0-40
        'Middle Third': (40, 75),       # 35% of 100 = 40-75  
        'Attacking Third': (75, 100)    # 25% of 100 = 75-100
    }
    
    # Get valid events and normalize coordinates
    valid_events = player_events.dropna(subset=['origin_x']).copy()
    if len(valid_events) == 0:
        return None
    
    # Get attacking direction from config and normalize coordinates
    attacking_direction = MATCH_CONFIG.get('attacking_direction') if MATCH_CONFIG else None
    half_periods = valid_events['half_period'].values if 'half_period' in valid_events.columns else np.ones(len(valid_events))
    
    x_coords_norm, y_coords_norm = normalize_coordinates(
        valid_events['origin_x'].values,
        valid_events['origin_y'].values, 
        half_periods,
        attacking_direction
    )
    
    # Add normalized coordinates to dataframe
    valid_events = valid_events.copy()
    valid_events['origin_x_norm'] = x_coords_norm
    valid_events['origin_y_norm'] = y_coords_norm
    
    # Count events in each zone using normalized coordinates
    zone_counts = {}
    for zone_name, (min_x, max_x) in zones.items():
        zone_events = valid_events[
            (valid_events['origin_x_norm'] >= min_x) & 
            (valid_events['origin_x_norm'] < max_x)
        ]
        zone_counts[zone_name] = len(zone_events)
    
    # Create figure with subplots
    fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(16, 8))
    
    # Left plot: Pitch with zone overlay
    draw_pitch(ax1)
    add_pitch_zones(ax1, alpha=0.2)
    
    # Plot player events colored by zone AND half using normalized coordinates
    colors = {'Defensive Third': 'red', 'Middle Third': 'yellow', 'Attacking Third': 'green'}
    half_markers = {1: 'o', 2: 's'}  # Circle for 1st half, square for 2nd half
    half_alphas = {1: 0.9, 2: 0.6}   # Different transparency for distinction
    
    for zone_name, (min_x, max_x) in zones.items():
        zone_events = valid_events[
            (valid_events['origin_x_norm'] >= min_x) & 
            (valid_events['origin_x_norm'] < max_x)
        ]
        
        if len(zone_events) > 0:
            # Plot events by half with different markers and transparency
            for half in [1, 2]:
                half_zone_events = zone_events[zone_events['half_period'] == half]
                if len(half_zone_events) > 0:
                    ax1.scatter(half_zone_events['origin_x_norm'], half_zone_events['origin_y_norm'], 
                               c=colors[zone_name], alpha=half_alphas[half], s=40, 
                               marker=half_markers[half], edgecolors='black', linewidth=1,
                               label=f"{zone_name} H{half}: {len(half_zone_events)} events")
            
            # Also add total zone count for clarity
            ax1.scatter([], [], c=colors[zone_name], alpha=0.0, s=0, 
                       label=f"Total {zone_name}: {len(zone_events)} events")
    
    ax1.set_title(f'{player_name} - Zone Activity', fontsize=12, fontweight='bold')
    ax1.legend()
    
    # Right plot: Zone distribution bar chart with half breakdown
    zone_names = list(zone_counts.keys())
    zone_values = list(zone_counts.values())
    zone_colors = [colors[zone] for zone in zone_names]
    
    # Calculate half-specific counts
    half_counts = {}
    for zone_name, (min_x, max_x) in zones.items():
        zone_events = valid_events[
            (valid_events['origin_x_norm'] >= min_x) & 
            (valid_events['origin_x_norm'] < max_x)
        ]
        half1_count = len(zone_events[zone_events['half_period'] == 1])
        half2_count = len(zone_events[zone_events['half_period'] == 2])
        half_counts[zone_name] = {'H1': half1_count, 'H2': half2_count}
    
    # Create stacked bar chart
    x_pos = np.arange(len(zone_names))
    h1_values = [half_counts[zone]['H1'] for zone in zone_names]
    h2_values = [half_counts[zone]['H2'] for zone in zone_names]
    
    bars1 = ax2.bar(x_pos, h1_values, color=zone_colors, alpha=0.9, 
                    label='1st Half (●)', edgecolor='black', linewidth=1)
    bars2 = ax2.bar(x_pos, h2_values, bottom=h1_values, color=zone_colors, alpha=0.6,
                    label='2nd Half (■)', edgecolor='black', linewidth=1)
    
    ax2.set_xticks(x_pos)
    ax2.set_xticklabels(zone_names)
    ax2.set_title(f'{player_name} - Zone Distribution by Half', fontsize=12, fontweight='bold')
    ax2.set_ylabel('Number of Events')
    ax2.legend()
    
    # Add value labels on bars
    for i, (zone, bar1, bar2) in enumerate(zip(zone_names, bars1, bars2)):
        h1_val = h1_values[i]
        h2_val = h2_values[i]
        total = h1_val + h2_val
        
        # Label for 1st half
        if h1_val > 0:
            ax2.text(bar1.get_x() + bar1.get_width()/2., h1_val/2,
                    f'H1: {h1_val}', ha='center', va='center', fontweight='bold', 
                    color='white' if h1_val > 2 else 'black', fontsize=9)
        
        # Label for 2nd half
        if h2_val > 0:
            ax2.text(bar2.get_x() + bar2.get_width()/2., h1_val + h2_val/2,
                    f'H2: {h2_val}', ha='center', va='center', fontweight='bold',
                    color='white' if h2_val > 2 else 'black', fontsize=9)
        
        # Total label above bar
        if total > 0:
            ax2.text(bar1.get_x() + bar1.get_width()/2., total + 0.5,
                    f'{total}', ha='center', va='bottom', fontweight='bold', fontsize=10)
    
    plt.tight_layout()
    
    if save_path:
        plt.savefig(save_path, dpi=300, bbox_inches='tight')
        print(f"Zone analysis saved: {save_path}")
    
    return fig, (ax1, ax2)

# ---------- MAIN EXECUTION ----------
def main():
    """Generate all heatmaps and visualizations"""
    print(f"Generating heatmaps for dataset: {MATCH_NAME}")
    print("Loading event data...")
    
    # Try match-specific events file first, fallback to main directory
    if not os.path.exists(EVENTS_CSV):
        fallback_csv = f"data/{team_name_safe}_vs_up.csv"  # Legacy fallback
        if os.path.exists(fallback_csv):
            events_csv_path = fallback_csv
            print(f"Using fallback events file: {fallback_csv}")
        else:
            print(f"Events file not found: {EVENTS_CSV}")
            return
    else:
        events_csv_path = EVENTS_CSV
    
    events_df = pd.read_csv(events_csv_path)
    print(f"Loaded {len(events_df)} events")
    
    # Load player data for context - try match-specific first, fallback to main
    player_data = {}
    if os.path.exists(PLAYER_METRICS_JSON):
        with open(PLAYER_METRICS_JSON, 'r') as f:
            data = json.load(f)
            player_data = data.get(TEAM_NAME, {})
        print(f"Loaded player metrics from: {PLAYER_METRICS_JSON}")
    elif os.path.exists(f"python_scripts/{team_name_safe}_players_derived_metrics.json"):
        with open(f"python_scripts/{team_name_safe}_players_derived_metrics.json", 'r') as f:
            data = json.load(f)
            player_data = data.get(TEAM_NAME, {})
        print("Loaded player metrics from main directory")
    
    # Get players who actually played (had meaningful events, not just listed in data)
    # Filter for players with actual game events (exclude empty events, kickoffs without player names)
    playing_events = events_df[
        (events_df['player_name'].notna()) & 
        (events_df['player_name'] != '') &
        (events_df['team'].str.lower() == TEAM_NAME.lower())  # Focus on our team
    ].copy()
    
    # Count events per player to filter out minimal participation
    player_event_counts = playing_events['player_name'].value_counts()
    
    # Only include players with at least 3 events (meaningful participation)
    meaningful_players = player_event_counts[player_event_counts >= 3].index.tolist()
    
    print(f"Found {len(player_event_counts)} players with events")
    print(f"Players with meaningful participation (>=3 events): {len(meaningful_players)}")
    
    # Generate individual player heatmaps
    print("\nGenerating player heatmaps...")
    heatmap_files = []
    
    for player in meaningful_players:  # Process players who actually played
        if player and player in player_data:
            # Player heatmap
            heatmap_filename = f"{player.replace(' ', '_')}_heatmap.png"
            heatmap_path = f"{OUTPUT_DIR}/{heatmap_filename}"
            
            fig, ax = generate_player_heatmap(player, events_df, heatmap_path)
            if fig:
                plt.close(fig)  # Free memory
                heatmap_files.append(heatmap_filename)
            
            # Zone analysis
            zone_filename = f"{player.replace(' ', '_')}_zones.png"
            zone_path = f"{OUTPUT_DIR}/{zone_filename}"
            
            fig, axes = generate_zone_analysis(player, events_df, zone_path)
            if fig:
                plt.close(fig)
                heatmap_files.append(zone_filename)
    
    # Generate team pass network
    print("\nGenerating pass network...")
    network_filename = f"{team_name_safe}_pass_network.png"
    network_path = f"{OUTPUT_DIR}/{network_filename}"
    
    fig, ax = generate_pass_network(events_df, network_path)
    if fig:
        plt.close(fig)
        heatmap_files.append(network_filename)
    
    # Generate team overall heatmap
    print("Generating team overall heatmap...")
    team_heatmap_filename = f"{team_name_safe}_team_heatmap.png"
    team_heatmap_path = f"{OUTPUT_DIR}/{team_heatmap_filename}"
    
    fig, ax = generate_team_overall_heatmap(events_df, team_heatmap_path)
    if fig:
        plt.close(fig)
        heatmap_files.append(team_heatmap_filename)
    
    print(f"\nAll visualizations saved to:")
    print(f"  Match archive: {OUTPUT_DIR}")
    print(f"Generated {len(heatmap_files)} heatmap files for {MATCH_NAME}")

if __name__ == "__main__":
    main()