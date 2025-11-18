# Player Evaluation & Scoring System Documentation

## Overview

The Insytes player evaluation system uses a comprehensive **Dynamic Performance Rating (DPR)** that combines traditional statistics with advanced position-specific enhanced ratings. The system evaluates players based on their specific roles and positions, using event-level data to calculate nuanced performance metrics.

## Core Evaluation Framework

### 1. Dynamic Performance Rating (DPR)
The base DPR score (0-100) is calculated using position-specific formulas and benchmarks:

**DPR Formula**: `Component Value / Position Benchmark * 100`

### 2. Enhanced Position-Specific Ratings
Each position has specialized rating systems that analyze detailed event data:

- **EAPR**: Enhanced Attacker Performance Rating
- **EMPR**: Enhanced Midfielder Performance Rating  
- **EDPR**: Enhanced Defender Performance Rating
- **EGPR**: Enhanced Goalkeeper Performance Rating

---

## Position-Based Evaluation Systems

### 🎯 ATTACKERS (ST, LW, RW)
**Role**: Goal-scoring and chance creation

#### DPR Components:
- **Goals per 90** (Benchmark: 0.4)
- **Assists per 90** (Benchmark: 0.3)  
- **Key passes per 90** (Benchmark: 2.0)
- **Progressive passes per 90** (Benchmark: 6.0)

#### Enhanced Attacker Rating (EAPR) Breakdown:

##### 1. Finishing Quality (35% weight)
**Event columns used**: `shots`, `shots_on_target`, `goals`, `outcome`
- **Goals per 90**: Direct goal scoring ability
- **Shot accuracy**: `shots_on_target / total_shots`
- **Goal conversion**: `goals / shots_on_target`
- **Formula**: `0.40 * (goals_p90 * 25) + 0.35 * shot_accuracy + 0.25 * goal_conversion`

##### 2. Chance Creation (25% weight) 
**Event columns used**: `event`, `key_pass`, `assist`, `progressive_pass`
- **Key passes per 90**: Passes leading to shots
- **Assists per 90**: Passes leading to goals
- **Progressive passes**: Forward passes advancing play
- **Formula**: `0.40 * key_passes + 0.30 * assists + 0.30 * progressive_passes`

##### 3. Movement Threat (20% weight)
**Event columns used**: `origin_x`, `origin_y`, `event`
- **Final third presence**: Actions in attacking zones (x >= 70)
- **Positioning quality**: Time spent in dangerous areas
- **Formula**: `0.60 * final_third_actions + 0.40 * positioning_quality`

##### 4. Link-up Play (15% weight)
**Event columns used**: `passes`, `successful_passes`, `duels`, `outcome`
- **Pass success rate**: Ball retention ability
- **Hold-up play**: Winning duels to maintain possession
- **Formula**: `0.60 * pass_success + 0.40 * duel_wins`

##### 5. Work Rate (5% weight)
**Event columns used**: `tackles`, `interceptions`, `recoveries`
- **Defensive actions**: Contribution to team defense
- **Formula**: `defensive_actions * 20` (capped at 100)

---

### 🎛️ MIDFIELDERS (CAM, CDM)
**Role**: Game control and transition play

#### DPR Components:
- **Key passes per 90** (Benchmark: 2.5)
- **Progressive passes per 90** (Benchmark: 7.0)
- **Duels won per 90** (Benchmark: 2.0)

#### Enhanced Midfielder Rating (EMPR) Breakdown:

##### 1. Game Control (30% weight)
**Event columns used**: `passes`, `successful_passes`, `possession`
- **Pass success rate**: Ball retention and distribution
- **Possession retention**: Maintaining team control
- **Formula**: `0.50 * pass_success + 0.50 * possession_retention`

##### 2. Transition Play (25% weight)
**Event columns used**: `progressive_passes`, `duels`, `outcome`
- **Progressive passes**: Forward distribution
- **Duels won**: Winning midfield battles
- **Formula**: `0.60 * progressive_passes + 0.40 * duels_won`

##### 3. Defensive Contribution (20% weight)
**Event columns used**: `tackles`, `interceptions`, `recoveries`
- **Tackles per 90**: Direct defensive actions
- **Interceptions per 90**: Reading the game
- **Recoveries per 90**: Ball winning ability
- **Formula**: `0.40 * tackles + 0.30 * interceptions + 0.30 * recoveries`

##### 4. Creativity (15% weight)
**Event columns used**: `key_passes`, `assists`, `through_balls`
- **Key passes**: Creating chances
- **Assists**: Final ball quality
- **Formula**: `0.70 * key_passes + 0.30 * assists`

##### 5. Work Rate (10% weight)
**Event columns used**: All action events
- **Total actions**: Overall involvement in play
- **Formula**: `total_actions / 50 * 100` (capped at 100)

---

### 🛡️ DEFENDERS (CB, LB, RB, LWB, RWB)
**Role**: Defensive stability and build-up play

#### DPR Components:
- **Duels won per 90** (Benchmark: 2.5)
- **Interceptions per 90** (Benchmark: 1.5)
- **Progressive passes per 90** (Benchmark: 5.0)

#### Enhanced Defender Rating (EDPR) Breakdown:

##### 1. Defensive Impact (30% weight)
**Event columns used**: `tackles`, `duels`, `pressure`, `outcome`
- **Tackle success rate**: Defensive interventions
- **Duel success rate**: Physical contests won
- **Pressure success**: Disrupting opponents
- **Formula**: `0.50 * tackle_success + 0.30 * pressure_success + 0.20 * duel_success`

##### 2. Build-Up Quality (25% weight)
**Event columns used**: `passes`, `successful_passes`, `progressive_passes`
- **Pass accuracy**: Distribution quality
- **Progressive passes**: Forward distribution
- **Long pass accuracy**: Switching play ability
- **Formula**: `0.40 * pass_accuracy + 0.40 * progressive_passes + 0.20 * long_pass_accuracy`

##### 3. Positioning Score (20% weight)
**Event columns used**: `origin_x`, `origin_y`, `event`
- **Defensive zone time**: Time in defensive third (x <= 35)
- **Positioning discipline**: Heatmap entropy analysis
- **Formula**: `0.70 * defensive_zone_time + 0.30 * positioning_discipline`

##### 4. Recovery Efficiency (15% weight)
**Event columns used**: `recoveries`, `fouls`, `cards`
- **Ball recoveries**: Regaining possession
- **Foul efficiency**: Clean defending (fewer fouls)
- **Formula**: `0.60 * recoveries + 0.40 * foul_efficiency`

##### 5. Aerial Dominance (10% weight)
**Event columns used**: `aerial_duels`, `headers`, `outcome`
- **Aerial duel success**: Winning headers
- **Formula**: `aerial_duels_won / total_aerial_duels`

---

### 🥅 GOALKEEPERS (GK)
**Role**: Shot-stopping and distribution

#### DPR Components:
- **Saves per 90** (Benchmark: 2.0)
- **Pass accuracy** (Benchmark: 0.70)

#### Enhanced Goalkeeper Rating (EGPR) Breakdown:

##### 1. Shot Stopping (40% weight)
**Event columns used**: `shots_faced`, `saves`, `goals_conceded`, `outcome`
- **Save percentage**: Shots saved vs shots faced
- **Formula**: `saves / shots_faced * 100`

##### 2. Distribution Quality (25% weight)
**Event columns used**: `passes`, `successful_passes`, `progressive_passes`
- **Pass accuracy**: Distribution precision
- **Long ball accuracy**: Launching attacks
- **Formula**: `0.60 * pass_accuracy + 0.40 * long_balls`

##### 3. Command of Area (20% weight)
**Event columns used**: `saves`, `clearances`, `punches`
- **Save activity**: Involvement in defense
- **Formula**: `saves / 10 * 100` (scaled)

##### 4. Ball Playing (10% weight)
**Event columns used**: `passes`, `short_passes`, `long_passes`
- **Passing involvement**: Modern goalkeeper play
- **Formula**: `passes_p90 / 20 * 100` (scaled)

##### 5. Consistency (5% weight)
**Event columns used**: `errors`, `mistakes`
- **Error rate**: Reliability metric
- **Formula**: `100 - (errors * 50)` (penalty for mistakes)

---

## Data Sources & Event Columns

### Core Event Data Structure
The system processes CSV files with these key columns:

#### Player Identification
- `player_name`: Player identity
- `position`: Playing position
- `team`: Team affiliation
- `match_time_minute`: Time of action
- `half_period`: Match period

#### Action Data
- `event`: Type of action (shot, pass, tackle, etc.)
- `outcome`: Result of action (successful, goal, save, etc.)
- `origin_x`, `origin_y`: Field coordinates (0-105 x 0-68)
- `destination_x`, `destination_y`: End coordinates
- `distance`: Action distance

#### Specialized Columns
- `key_pass`: Boolean for chance creation
- `progressive_pass`: Boolean for forward progress
- `keeper_name`: Goalkeeper involved in action
- `assist`: Boolean for goal assistance

### Statistical Calculations

#### Per-90 Minute Normalization
All statistics are normalized to 90-minute equivalents:
```
stat_per_90 = (raw_stat_count / minutes_played) * 90
```

#### Success Rate Calculations
```
success_rate = (successful_actions / total_actions) * 100
```

#### Positional Heatmap Analysis
Using coordinate data to calculate:
- **Zone coverage**: Percentage of actions in specific field areas
- **Positioning entropy**: Measure of positional discipline
- **Movement patterns**: Tracking player positioning tendencies

---

## Benchmarking System

### College-Level Benchmarks
Performance expectations for each position:

```python
COLLEGE_BENCHMARKS = {
    "attacker":   {"goals":0.4, "assists":0.3, "key_passes":2.0, "prog":6.0},
    "midfielder": {"key_passes":2.5, "prog":7.0, "duels_won":2.0},
    "defender":   {"duels_won":2.5, "interceptions":1.5, "prog":5.0},
    "goalkeeper": {"saves":2.0, "pass_acc":0.70}
}
```

### Score Scaling
- **0-30**: Needs Focus - Below expectations
- **30-50**: Developing - Meeting some expectations  
- **50-70**: Solid - Good performance level
- **70-85**: Strong - Above average performance
- **85-100**: Elite - Exceptional performance

---

## Output Metrics

### Player Profile Structure
Each player evaluation includes:

```json
{
    "player_name": "Player Name",
    "position": "Position",
    "minutes_played": 90.0,
    "dpr": 65.3,
    "dpr_breakdown": {
        "component1": 80.0,
        "component2": 66.2,
        "component3": 51.3
    },
    "key_stats_p90": {
        "goals_p90": 0.5,
        "assists_p90": 0.3,
        "passes_p90": 35.8
    },
    "enhanced_rating": 72.4,
    "tactical_role": "Deep-lying playmaker",
    "performance_tier": "Solid"
}
```

### Training Data Generation
The system generates comprehensive training datasets with:
- **48+ features per player**
- **Position-specific metrics**
- **Enhanced rating breakdowns** 
- **Temporal tracking (multiple matches)**
- **Performance predictions**

---

## Usage Examples

### Analyzing an Attacker
**Edvard Omitade (RW)**:
- **Goals**: 1.97 per 90 (excellent finishing)
- **Finishing Quality**: 49.5/100 (good but needs more shots on target)
- **Chance Creation**: 17.7/100 (weak - needs to create more for teammates)
- **Movement Threat**: 11.9/100 (poor positioning in final third)
- **Final DPR**: 36.5 (developing, but strong goal-scoring potential)

### Analyzing a Midfielder  
**Harvy Barro (CDM)**:
- **Game Control**: 89.2/100 (excellent tempo control)
- **Progressive Passes**: 10.37 per 90 (strong forward distribution)
- **Defensive Contribution**: 51.3/100 (solid defensive work)
- **Final DPR**: 61.0 (reliable central midfielder)

### Analyzing a Defender
**Jaro Libarnes (CB)**:
- **Build-up Quality**: 64.4/100 (good passing from the back)
- **Defensive Impact**: 18.6/100 (needs more aggressive defending)
- **Recovery Efficiency**: 65.5/100 (good at regaining possession)
- **Final DPR**: 36.5 (developing with good potential)

---

This evaluation system provides comprehensive, position-aware player assessment that considers both traditional statistics and advanced performance indicators derived from detailed event data.