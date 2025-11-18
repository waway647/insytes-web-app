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
EVENTS_CSV = f"data/{MATCH_NAME}.csv"
PLAYER_METRICS_JSON = f"output/matches/{MATCH_NAME}/sanbeda_players_derived_metrics.json"
OUTPUT_DIR = f"output/matches/{MATCH_NAME}/heatmaps"

# Pitch dimensions (standard football pitch)
PITCH_LENGTH = 105
PITCH_WIDTH = 68

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
    
    # Left penalty area
    left_penalty = Rectangle((0, 13.84), 16.5, 40.32,
                           linewidth=linewidth, edgecolor=line_color, facecolor='none')
    ax.add_patch(left_penalty)
    
    # Left 6-yard box  
    left_six_yard = Rectangle((0, 24.84), 5.5, 18.32,
                             linewidth=linewidth, edgecolor=line_color, facecolor='none')
    ax.add_patch(left_six_yard)
    
    # Right penalty area
    right_penalty = Rectangle((88.5, 13.84), 16.5, 40.32,
                            linewidth=linewidth, edgecolor=line_color, facecolor='none')
    ax.add_patch(right_penalty)
    
    # Right 6-yard box
    right_six_yard = Rectangle((99.5, 24.84), 5.5, 18.32,
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
    """Add zone overlays for analysis"""
    # Defensive third
    defensive_zone = Rectangle((0, 0), 35, PITCH_WIDTH, 
                              facecolor='red', alpha=alpha)
    ax.add_patch(defensive_zone)
    
    # Middle third  
    middle_zone = Rectangle((35, 0), 35, PITCH_WIDTH,
                           facecolor='yellow', alpha=alpha)
    ax.add_patch(middle_zone)
    
    # Attacking third
    attacking_zone = Rectangle((70, 0), 35, PITCH_WIDTH,
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
    valid_coords = player_events.dropna(subset=['origin_x', 'origin_y'])
    if len(valid_coords) == 0:
        print(f"No valid coordinates for {player_name}")
        return None
    
    x_coords = valid_coords['origin_x'].values
    y_coords = valid_coords['origin_y'].values
    
    # Create figure
    fig, ax = plt.subplots(figsize=(12, 8))
    
    # Draw pitch
    draw_pitch(ax)
    add_pitch_zones(ax, alpha=0.05)
    
    # Create 2D histogram for heatmap
    bins_x = np.linspace(0, PITCH_LENGTH, 21)  # 5m bins
    bins_y = np.linspace(0, PITCH_WIDTH, 15)   # ~4.5m bins
    
    heatmap, xedges, yedges = np.histogram2d(x_coords, y_coords, bins=[bins_x, bins_y])
    
    # Smooth with Gaussian if enough points
    if len(x_coords) > 5:
        try:
            # Create a kde and evaluate on a grid
            values = np.vstack([x_coords, y_coords])
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
        ax.scatter(x_coords, y_coords, c='red', s=50, alpha=0.8, edgecolors='darkred')
    
    # Add player position points as overlay
    ax.scatter(x_coords, y_coords, c='darkred', s=20, alpha=0.4, edgecolors='black', linewidth=0.5)
    
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

def generate_pass_network(team_name, events_df, save_path=None):
    """Generate pass network visualization"""
    # Filter pass events for the team
    team_passes = events_df[
        (events_df['team'] == team_name) & 
        (events_df['event'] == 'Pass') &
        (events_df['outcome'] == 'Successful')
    ].copy()
    
    if len(team_passes) == 0:
        print(f"No successful passes found for {team_name}")
        return None
    
    # Calculate average positions for each player
    player_positions = {}
    pass_connections = {}
    
    for _, pass_event in team_passes.iterrows():
        passer = pass_event['player_name']
        receiver = pass_event.get('receiver_name', '')
        
        # Store passer position
        if passer and pd.notna(pass_event['origin_x']) and pd.notna(pass_event['origin_y']):
            if passer not in player_positions:
                player_positions[passer] = {'x': [], 'y': [], 'position': pass_event.get('position', '')}
            player_positions[passer]['x'].append(pass_event['origin_x'])
            player_positions[passer]['y'].append(pass_event['origin_y'])
        
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
        print(f"Not enough players with position data for {team_name}")
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
    
    ax.set_title(f'{team_name} - Pass Network\nAverage positions and pass connections (3+ passes)', 
                fontsize=14, fontweight='bold')
    
    plt.tight_layout()
    
    if save_path:
        plt.savefig(save_path, dpi=300, bbox_inches='tight')
        print(f"Pass network saved: {save_path}")
    
    return fig, ax

def generate_team_overall_heatmap(team_name, events_df, save_path=None):
    """Generate overall team heatmap showing collective positioning"""
    # Filter all events for the team (not just passes)
    team_events = events_df[
        (events_df['team'] == team_name) & 
        (events_df['player_name'].notna()) & 
        (events_df['origin_x'].notna()) & 
        (events_df['origin_y'].notna())
    ].copy()
    
    if len(team_events) == 0:
        print(f"No position data found for {team_name}")
        return None
    
    # Extract all team coordinates
    x_coords = team_events['origin_x'].values
    y_coords = team_events['origin_y'].values
    
    print(f"Team events with coordinates: {len(x_coords)}")  # Debug info
    print(f"Coordinate ranges: X({x_coords.min():.1f}-{x_coords.max():.1f}), Y({y_coords.min():.1f}-{y_coords.max():.1f})")
    
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
        heatmap, xedges, yedges = np.histogram2d(x_coords, y_coords, bins=[bins_x, bins_y])
        
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
    ax.set_title(f'{team_name} - Team Activity Heatmap {attacking_direction}\n'
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
    
    # Define zones
    zones = {
        'Defensive Third': (0, 35),
        'Middle Third': (35, 70), 
        'Attacking Third': (70, 105)
    }
    
    # Count events in each zone
    zone_counts = {}
    valid_events = player_events.dropna(subset=['origin_x'])
    
    for zone_name, (min_x, max_x) in zones.items():
        zone_events = valid_events[
            (valid_events['origin_x'] >= min_x) & 
            (valid_events['origin_x'] < max_x)
        ]
        zone_counts[zone_name] = len(zone_events)
    
    # Create figure with subplots
    fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(16, 8))
    
    # Left plot: Pitch with zone overlay
    draw_pitch(ax1)
    add_pitch_zones(ax1, alpha=0.2)
    
    # Plot player events colored by zone
    colors = {'Defensive Third': 'red', 'Middle Third': 'yellow', 'Attacking Third': 'green'}
    
    for zone_name, (min_x, max_x) in zones.items():
        zone_events = valid_events[
            (valid_events['origin_x'] >= min_x) & 
            (valid_events['origin_x'] < max_x)
        ]
        if len(zone_events) > 0:
            ax1.scatter(zone_events['origin_x'], zone_events['origin_y'], 
                       c=colors[zone_name], alpha=0.7, s=30, 
                       label=f"{zone_name}: {len(zone_events)} events")
    
    ax1.set_title(f'{player_name} - Zone Activity', fontsize=12, fontweight='bold')
    ax1.legend()
    
    # Right plot: Zone distribution bar chart
    zone_names = list(zone_counts.keys())
    zone_values = list(zone_counts.values())
    zone_colors = [colors[zone] for zone in zone_names]
    
    bars = ax2.bar(zone_names, zone_values, color=zone_colors, alpha=0.7, edgecolor='black')
    ax2.set_title(f'{player_name} - Zone Distribution', fontsize=12, fontweight='bold')
    ax2.set_ylabel('Number of Events')
    
    # Add value labels on bars
    for bar, value in zip(bars, zone_values):
        height = bar.get_height()
        ax2.text(bar.get_x() + bar.get_width()/2., height + 0.1,
                f'{value}', ha='center', va='bottom', fontweight='bold')
    
    # Add percentage labels
    total_events = sum(zone_values) 
    for i, (bar, value) in enumerate(zip(bars, zone_values)):
        if total_events > 0:
            pct = (value / total_events) * 100
            ax2.text(bar.get_x() + bar.get_width()/2., height/2,
                    f'{pct:.1f}%', ha='center', va='center', fontweight='bold', color='white')
    
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
        fallback_csv = f"data/sanbeda_vs_up.csv"  # Legacy fallback
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
            player_data = data.get("San Beda", {})
        print(f"Loaded player metrics from: {PLAYER_METRICS_JSON}")
    elif os.path.exists("python_scripts/sanbeda_players_derived_metrics.json"):
        with open("python_scripts/sanbeda_players_derived_metrics.json", 'r') as f:
            data = json.load(f)
            player_data = data.get("San Beda", {})
        print("Loaded player metrics from main directory")
    
    # Get players who actually played (had meaningful events, not just listed in data)
    # Filter for players with actual game events (exclude empty events, kickoffs without player names)
    playing_events = events_df[
        (events_df['player_name'].notna()) & 
        (events_df['player_name'] != '') &
        (events_df['team'] == 'San Beda')  # Focus on our team
    ].copy()
    
    # Count events per player to filter out minimal participation
    player_event_counts = playing_events['player_name'].value_counts()
    
    # Only include players with at least 3 events (meaningful participation)
    meaningful_players = player_event_counts[player_event_counts >= 3].index.tolist()
    
    print(f"Found {len(player_event_counts)} players with events")
    print(f"Players with meaningful participation (≥3 events): {len(meaningful_players)}")
    
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
    network_filename = "San_Beda_pass_network.png"
    network_path = f"{OUTPUT_DIR}/{network_filename}"
    
    fig, ax = generate_pass_network("San Beda", events_df, network_path)
    if fig:
        plt.close(fig)
        heatmap_files.append(network_filename)
    
    # Generate team overall heatmap
    print("Generating team overall heatmap...")
    team_heatmap_filename = "San_Beda_team_heatmap.png"
    team_heatmap_path = f"{OUTPUT_DIR}/{team_heatmap_filename}"
    
    fig, ax = generate_team_overall_heatmap("San Beda", events_df, team_heatmap_path)
    if fig:
        plt.close(fig)
        heatmap_files.append(team_heatmap_filename)
    
    print(f"\nAll visualizations saved to:")
    print(f"  Match archive: {OUTPUT_DIR}")
    print(f"Generated {len(heatmap_files)} heatmap files for {MATCH_NAME}")

if __name__ == "__main__":
    main()