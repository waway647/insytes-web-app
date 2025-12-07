<?php
defined('BASEPATH') OR exit('No direct script access allowed');

error_reporting(E_ALL ^ E_DEPRECATED);

class Match_Stats_Model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
    }

    /**
     * Main insert/upsert function called by controller.
     *
     * @param string $match_id (external match id like 'match_18_sbu_vs_kaya' or '19' or 'match_19_sbu_vs_2wfc')
     * @param array $insights decoded JSON from san_beda_university_team_insights.json
     * @param array $derived decoded JSON from san_beda_university_team_derived_metrics.json
     * @param array $players decoded JSON from san_beda_university_players_derived_metrics.json
     * @param string|null $files_base Optional base path of files (for logging)
     * @return array summary (success, counts, errors)
     */
    public function match_stats_json_to_db_inserts($match_id, $insights, $derived, $players, $files_base = null)
    {
        $summary = [
            'success' => false,
            'match_id' => $match_id,
            'files_base' => $files_base,
            'team_upserted' => 0,
            'team_updated' => 0,
            'players_inserted' => 0,
            'players_updated' => 0,
            'errors' => [],
            'db_error' => null
        ];

        // Basic validation
        if (empty($insights) || empty($derived) || empty($players)) {
            $summary['errors'][] = 'One or more JSON payloads are empty.';
            return $summary;
        }

        // Resolve numeric DB match id (handles '19' or 'match_19_sbu_vs_2wfc' etc.)
        $match_db_id = $this->resolve_match_db_id($match_id);

        if (!$match_db_id) {
            $summary['errors'][] = "Could not find a matches record for incoming identifier={$match_id}";
            return $summary;
        }

        // Team name from insights JSON (this is the "primary" team that insights contains)
        $team_name = $insights['team'] ?? null;
        if (!$team_name) {
            $summary['errors'][] = "Insufficient insights JSON: missing 'team' key.";
            return $summary;
        }

        // --- Process team blocks from derived JSON (both teams) ---
        // The derived payload uses top-level keys that are team names (e.g. "San Beda University", "Kaya FC - Iloilo")
        // plus some meta keys (e.g. match_duration_seconds). We'll iterate and upsert metrics for every team block.
        
        // mapping from derived team display name -> proc_team_id
        $team_name_to_proc_id = [];

        foreach ($derived as $derived_key => $derived_val) {
            // Skip non-array entries and meta keys
            if (!is_array($derived_val)) continue;

            // Heuristic: team blocks contain either 'team_name' or 'possession' or 'distribution' or 'attack'
            $is_team_block = isset($derived_val['team_name']) ||
                             isset($derived_val['possession']) ||
                             isset($derived_val['distribution']) ||
                             isset($derived_val['attack']);
            if (!$is_team_block) continue;

            // Determine team display name
            $derived_team_name = $derived_val['team_name'] ?? $derived_key;
            $derived_team_name = trim($derived_team_name);

            // find or create team in teams table
            $team_row = $this->db->get_where('teams', ['team_name' => $derived_team_name])->row();
            if (!$team_row) {
                $this->db->insert('teams', ['team_name' => $derived_team_name, 'abbreviation' => null, 'country' => null, 'city' => null]);
                $proc_team_id = (int)$this->db->insert_id();
                $summary['team_upserted']++;
            } else {
                $proc_team_id = (int)$team_row->id;
            }

            // remember which proc_team_id belongs to this derived team display name
            $team_name_to_proc_id[$derived_team_name] = $proc_team_id;


            // Build the team metric row using the derived block
            $d = $derived_val;

            $team_metric_row = [
                'match_id' => $match_db_id,
                'team_id' => $proc_team_id,
                'recorded_at' => $insights['timestamp'] ?? null,
                'outcome_score' => $d['outcome_score'] ?? 0,
                'overall_rating' => $d['overall_rating'] ?? null,
                'match_rating_attack' => $d['match_rating_attack'] ?? null,
                'match_rating_defense' => $d['match_rating_defense'] ?? null,
                'match_rating_distribution' => $d['match_rating_distribution'] ?? null,
                'match_rating_general' => $d['match_rating_general'] ?? null,
                'match_rating_discipline' => $d['match_rating_discipline'] ?? null,

                // possession
                'possession_seconds' => $d['possession']['your_possession_time_seconds'] ?? null,
                'possession_pct' => $d['possession']['possession_pct'] ?? ($d['possession']['possession_pct'] ?? null),
                'attacking_third_possession_seconds' => $d['possession']['attacking_third_possession_seconds'] ?? null,
                'attacking_third_possession_pct' => $d['possession']['attacking_third_possession_pct_of_team'] ?? null,

                // distribution
                'passes' => $d['distribution']['passes'] ?? $d['distribution']['total_passes'] ?? null,
                'successful_passes' => $d['distribution']['successful_passes'] ?? null,
                'intercepted_passes' => $d['distribution']['intercepted_passes'] ?? null,
                'unsuccessful_passes' => $d['distribution']['unsuccessful_passes'] ?? null,
                'short_passes' => $d['distribution']['short_passes'] ?? null,
                'long_passes' => $d['distribution']['long_passes'] ?? null,
                'through_passes' => $d['distribution']['through_passes'] ?? null,
                'crosses' => $d['distribution']['crosses'] ?? null,
                'passing_accuracy_pct' => isset($d['distribution']['passing_accuracy_pct']) ? floatval($d['distribution']['passing_accuracy_pct']) : null,
                'assists' => $d['distribution']['assists'] ?? null,
                'key_passes' => $d['distribution']['key_passes'] ?? null,

                // attack
                'goals' => $d['attack']['goals'] ?? null,
                'shots' => $d['attack']['shots'] ?? $d['attack']['total_shots'] ?? null,
                'shots_on_target' => $d['attack']['shots_on_target'] ?? null,
                'shots_off_target' => $d['attack']['shots_off_target'] ?? null,
                'blocked_shots' => $d['attack']['blocked_shots'] ?? null,
                'shot_creating_actions' => $d['attack']['shot_creating_actions'] ?? null,
                'shooting_accuracy_pct' => $d['attack']['shooting_accuracy_pct'] ?? null,

                // defense
                'tackles' => $d['defense']['tackles'] ?? null,
                'successful_tackles' => $d['defense']['successful_tackles'] ?? null,
                'tackle_success_rate_pct' => isset($d['defense']['tackles_success_rate_pct']) ? floatval($d['defense']['tackles_success_rate_pct']) : (isset($d['defense']['tackle_success_rate_pct']) ? floatval($d['defense']['tackle_success_rate_pct']) : null),
                'interceptions' => $d['defense']['interceptions'] ?? null,
                'recoveries' => $d['defense']['recoveries'] ?? null,
                'recoveries_attacking_third' => $d['defense']['recoveries_attacking_third'] ?? null,
                'clearances' => $d['defense']['clearances'] ?? null,
                'blocks' => $d['defense']['blocks'] ?? null,
                'saves' => $d['defense']['saves'] ?? null,

                // general
                'duels' => $d['general']['duels'] ?? null,
                'duels_won' => $d['general']['duels_won'] ?? null,
                'duels_win_pct' => isset($d['general']['duels_success_rate_pct']) ? floatval($d['general']['duels_success_rate_pct']) : (isset($d['general']['duels_win_pct']) ? floatval($d['general']['duels_win_pct']) : null),
                'aerial_duels' => $d['general']['aerial_duels'] ?? null,
                'ground_duels' => $d['general']['ground_duels'] ?? null,
                'offsides' => $d['general']['offsides'] ?? null,
                'corners_awarded' => $d['general']['corner_awarded'] ?? $d['general']['corners_awarded'] ?? null,

                // discipline
                'fouls_conceded' => $d['discipline']['fouls_conceded'] ?? null,
                'yellow_cards' => $d['discipline']['yellow_cards'] ?? null,
                'red_cards' => $d['discipline']['red_cards'] ?? null,

                // store json blobs
                // For the team that has 'insights' (primary team) we keep the insights JSON;
                // for other teams we store empty blobs (derived only)
                'predicted_scores' => ($derived_team_name === ($insights['team'] ?? '')) ? json_encode($insights['predicted_scores'] ?? []) : json_encode([]),
                'detailed_analysis' => ($derived_team_name === ($insights['team'] ?? '')) ? json_encode($insights['detailed_analysis'] ?? []) : json_encode([]),
                'coaching_assessment' => ($derived_team_name === ($insights['team'] ?? '')) ? json_encode($insights['coaching_assessment'] ?? []) : json_encode([]),
                'interpretation' => ($derived_team_name === ($insights['team'] ?? '')) ? json_encode($insights['interpretation'] ?? []) : json_encode([]),
                'raw_json' => json_encode($d)
            ];

            // upsert into match_team_metrics for this team block
            $existing_team_metric = $this->db->get_where('match_team_metrics', ['match_id' => $match_db_id, 'team_id' => $proc_team_id])->row();

            if ($existing_team_metric) {
                $this->db->where('id', $existing_team_metric->id);
                $this->db->update('match_team_metrics', $team_metric_row);
                $summary['team_updated']++;
                $team_metric_id = $existing_team_metric->id;
                $err = $this->db->error();
                if (!empty($err['code'])) {
                    $summary['errors'][] = 'DB error updating match_team_metrics for team ' . $derived_team_name . ': ' . $err['message'] . ' (code ' . $err['code'] . ')';
                }
            } else {
                $this->db->insert('match_team_metrics', $team_metric_row);
                $team_metric_id = (int)$this->db->insert_id();
                $summary['team_upserted']++;
                $err = $this->db->error();
                if (!empty($err['code'])) {
                    $summary['errors'][] = 'DB error inserting match_team_metrics for team ' . $derived_team_name . ': ' . $err['message'] . ' (code ' . $err['code'] . ')';
                }
            }
        } // end foreach derived teams

        // Determine primary team proc id (the one corresponding to insights['team'])
        $primary_team_proc_id = null;
        if (!empty($team_name) && isset($team_name_to_proc_id[$team_name])) {
            $primary_team_proc_id = (int)$team_name_to_proc_id[$team_name];
        } else {
            // fallback: if mapping has at least one entry, pick the first (safe fallback)
            $vals = array_values($team_name_to_proc_id);
            if (!empty($vals)) $primary_team_proc_id = (int)$vals[0];
        }

        // If we still don't have a primary team id, that's an error we should return early
        if (!$primary_team_proc_id) {
            $summary['errors'][] = "Could not determine primary team id for team '{$team_name}'";
            $summary['db_error'] = $this->db->error();
            return $summary;
        }

        // --- players: find correct block in players JSON for the primary team and insert/update player metrics ---
        $players_team_data = null;
        if (isset($players[$team_name])) {
            $players_team_data = $players[$team_name];
        } else {
            $topkeys = array_keys($players);
            if (count($topkeys) === 1) {
                $players_team_data = $players[$topkeys[0]];
            } else {
                foreach ($players as $k => $v) {
                    if (is_array($v) && isset($v['match_id'])) {
                        // prefer the block that matches our match id
                        $players_team_data = $v;
                        break;
                    }
                }
            }
        }

        if (!$players_team_data) {
            $summary['errors'][] = "Could not find player block for team '{$team_name}' in players JSON.";
            // We still attempt to complete DB transaction and return error info
            $summary['db_error'] = $this->db->error();
            return $summary;
        }

        // Build list of player rows
        $player_rows = [];
        foreach ($players_team_data as $key => $val) {
            if (in_array($key, ['match_id', 'match_name', $team_name])) continue;
            if (!is_array($val)) continue;
            if (!isset($val['position']) && !isset($val['raw_stats']) && !isset($val['minutes_played'])) continue;
            $player_rows[$key] = $val;
        }

        // Insert/update each player metric (unchanged logic)
        foreach ($player_rows as $player_key => $player_payload) {
            try {
                // Resolve player_id
                $player_db_id = null;

                // match_players table first (match-specific names)
                $q = $this->db->get_where('match_players', ['match_id' => $match_db_id, 'name' => $player_key]);
                if ($q && $q->num_rows() > 0) {
                    $player_db_id = (int)$q->row()->player_id;
                } else {
                    // name parsing "Last, First"
                    $pieces = array_map('trim', explode(',', $player_key, 2));
                    if (count($pieces) === 2) {
                        $last = $pieces[0];
                        $first = $pieces[1];
                        // try players table using team membership of the primary team
                        $q2 = $this->db->get_where('players', ['first_name' => $first, 'last_name' => $last, 'team_id' => $primary_team_proc_id]);
                        if ($q2 && $q2->num_rows() > 0) {
                            $player_db_id = (int)$q2->row()->id;
                        }
                    }
                }

                // fuzzy match
                if (!$player_db_id) {
                    $like = '%' . $this->db->escape_like_str($player_key) . '%';
                    $q3 = $this->db->query("SELECT id FROM players WHERE CONCAT(last_name, ', ', first_name) LIKE ? LIMIT 1", [$like]);
                    if ($q3 && $q3->num_rows() > 0) {
                        $player_db_id = (int)$q3->row()->id;
                    }
                }

                // create minimal player if not found
                if (!$player_db_id) {
                    $first = null; $last = $player_key;
                    if (isset($pieces) && count($pieces) === 2) {
                        $last = $pieces[0];
                        $first = $pieces[1];
                    } else {
                        $first = $player_key;
                        $last = null;
                    }
                    $ins = [
                        'team_id' => $primary_team_proc_id,
                        'first_name' => $first ?? null,
                        'last_name' => $last ?? null,
                        'position' => $player_payload['position'] ?? null,
                        'jersey' => $player_payload['number'] ?? null
                    ];
                    $this->db->insert('players', $ins);
                    $player_db_id = (int)$this->db->insert_id();
                    $err = $this->db->error();
                    if (!empty($err['code'])) {
                        $summary['errors'][] = 'DB error inserting new player: ' . $err['message'] . ' (code ' . $err['code'] . ')';
                    }
                }

                // Prepare player metric row mapping
                $pm = [];
                $pm['match_id'] = $match_db_id;
                $pm['team_id'] = $primary_team_proc_id;
                $pm['player_id'] = $player_db_id;
                $pm['recorded_at'] = $player_payload['timestamp'] ?? $insights['timestamp'] ?? null;
                $pm['minutes_played'] = $player_payload['minutes_played'] ?? 0;
                $pm['status'] = $player_payload['status'] ?? ($pm['minutes_played'] > 0 ? 'starter' : 'DNP');
                $pm['dpr'] = $player_payload['dpr'] ?? null;
                $pm['dpr_breakdown'] = isset($player_payload['dpr_breakdown']) ? json_encode($player_payload['dpr_breakdown']) : null;

                $raw = $player_payload['raw_stats'] ?? [];
                $dist = $raw['distribution'] ?? ($player_payload['distribution'] ?? []);
                $attack = $raw['attack'] ?? ($player_payload['attack'] ?? []);
                $def = $raw['defense'] ?? ($player_payload['defense'] ?? []);
                $drb = $raw['dribbles'] ?? ($player_payload['dribbles'] ?? []);

                $pm['passes'] = $dist['passes'] ?? null;
                $pm['successful_passes'] = $dist['successful_passes'] ?? null;
                $pm['unsuccessful_passes'] = $dist['unsuccessful_passes'] ?? null;
                $pm['passing_accuracy_pct'] = isset($dist['passing_accuracy_pct']) ? floatval($dist['passing_accuracy_pct']) : null;
                $pm['key_passes'] = $dist['key_passes'] ?? null;
                $pm['assists'] = $dist['assists'] ?? null;
                $pm['progressive_passes'] = $dist['progressive_passes'] ?? null;
                $pm['avg_pass_distance'] = $dist['avg_pass_distance'] ?? null;

                $pm['shots'] = $attack['shots'] ?? ($player_payload['key_stats_p90']['shots_p90'] ?? null);
                $pm['shots_on_target'] = $attack['shots_on_target'] ?? null;
                $pm['goals'] = $attack['goals'] ?? null;
                $pm['shot_accuracy_pct'] = $attack['shot_accuracy_pct'] ?? null;
                $pm['goal_conversion_pct'] = $attack['goal_conversion_pct'] ?? null;

                $pm['dribbles'] = $drb['dribbles'] ?? null;
                $pm['successful_dribbles'] = $drb['successful_dribbles'] ?? null;
                $pm['dribble_success_rate_pct'] = isset($drb['dribble_success_rate_pct']) ? floatval($drb['dribble_success_rate_pct']) : null;

                $pm['duels'] = $def['duels'] ?? null;
                $pm['duels_won'] = $def['duels_won'] ?? null;
                $pm['duel_success_rate_pct'] = isset($def['duel_success_rate_pct']) ? floatval($def['duel_success_rate_pct']) : null;
                $pm['tackles'] = $def['tackles'] ?? null;
                $pm['successful_tackles'] = $def['successful_tackles'] ?? null;
                $pm['tackle_success_rate_pct'] = isset($def['tackle_success_rate_pct']) ? floatval($def['tackle_success_rate_pct']) : null;
                $pm['interceptions'] = $def['interceptions'] ?? null;
                $pm['clearances'] = $def['clearances'] ?? null;
                $pm['recoveries'] = $def['recoveries'] ?? null;
                $pm['blocked_shots'] = $def['blocked_shots'] ?? null;

                $pm['saves'] = $raw['goalkeeper']['saves'] ?? ($player_payload['raw_stats']['goalkeeper']['saves'] ?? null);
                $pm['goals_conceded'] = $raw['goalkeeper']['goals_conceded'] ?? null;

                $pm['fouls_conceded'] = $player_payload['raw_stats']['discipline']['fouls_conceded'] ?? null;
                $pm['yellow_cards'] = $player_payload['raw_stats']['discipline']['yellow_cards'] ?? null;
                $pm['red_cards'] = $player_payload['raw_stats']['discipline']['red_cards'] ?? null;

                $pm['key_stats_p90'] = isset($player_payload['key_stats_p90']) ? json_encode($player_payload['key_stats_p90']) : null;
                $pm['raw_stats'] = isset($player_payload['raw_stats']) ? json_encode($player_payload['raw_stats']) : json_encode($player_payload);

                // upsert player metric
                $existing = $this->db->get_where('match_player_metrics', ['match_id' => $match_db_id, 'player_id' => $player_db_id])->row();
                if ($existing) {
                    $this->db->where('id', $existing->id);
                    $this->db->update('match_player_metrics', $pm);
                    $summary['players_updated']++;
                    $err = $this->db->error();
                    if (!empty($err['code'])) {
                        $summary['errors'][] = 'DB error updating match_player_metrics: ' . $err['message'] . ' (code ' . $err['code'] . ')';
                    }
                } else {
                    $this->db->insert('match_player_metrics', $pm);
                    $summary['players_inserted']++;
                    $err = $this->db->error();
                    if (!empty($err['code'])) {
                        $summary['errors'][] = 'DB error inserting match_player_metrics: ' . $err['message'] . ' (code ' . $err['code'] . ')';
                    }
                }

            } catch (Throwable $pex) {
                // catch PHP exceptions / throwables
                $summary['errors'][] = "Player {$player_key} processing error: " . $pex->getMessage();
                log_message('error', 'Player processing exception: ' . $pex->getMessage() . "\n" . $pex->getTraceAsString());
            }
        } // end foreach players

        // finalize transaction and capture DB-level errors
        if ($this->db->trans_status() === FALSE) {
            // If a transaction has been started outside this method (not in this refactor),
            // ensure trans_complete was called; but we rely on caller to wrap if needed.
            $dbErr = $this->db->error();
            $summary['errors'][] = 'DB transaction failed';
            $summary['db_error'] = $dbErr;
            $summary['success'] = false;
            return $summary;
        }

        $summary['success'] = true;
        return $summary;
    }

    /**
     * Resolve an incoming match identifier to numeric matches.id.
     * Handles numeric id, dataset string like "match_19_sbu_vs_2wfc", or var_dump wrappers.
     * Returns integer matches.id or null if not found.
     */
    protected function resolve_match_db_id($incoming)
    {
        if (is_null($incoming)) return null;
        $raw = $incoming;

        // var_dump wrapper: string(20) "match_19_sbu_vs_2wfc"
        if (is_string($raw) && preg_match('/^string\(\d+\)\s*"(.+)"$/', trim($raw), $m)) {
            $raw = $m[1];
        }

        // strip surrounding quotes
        if (is_string($raw)) {
            $raw = trim($raw);
            if ((substr($raw, 0, 1) === '"' && substr($raw, -1) === '"') || (substr($raw, 0, 1) === "'" && substr($raw, -1) === "'")) {
                $raw = substr($raw, 1, -1);
            }
        }

        // 1) numeric -> direct id
        if (is_numeric($raw)) {
            $id = (int)$raw;
            $q = $this->db->get_where('matches', ['id' => $id]);
            if ($q && $q->num_rows() > 0) return $id;
        }

        // 2) extract integer from string (match_19_...)
        if (is_string($raw) && preg_match('/\d+/', $raw, $m2)) {
            $candidateId = (int)$m2[0];
            $q3 = $this->db->get_where('matches', ['id' => $candidateId]);
            if ($q3 && $q3->num_rows() > 0) return $candidateId;
        }

        // 3) exact match on match_id column (if exists)
        if ($this->db->field_exists('match_id', 'matches')) {
            $q = $this->db->get_where('matches', ['match_id' => $raw]);
            if ($q && $q->num_rows() > 0) return (int)$q->row()->id;
        }

        // 4) exact match on match_name column (if exists)
        if ($this->db->field_exists('match_name', 'matches')) {
            $q = $this->db->get_where('matches', ['match_name' => $raw]);
            if ($q && $q->num_rows() > 0) return (int)$q->row()->id;
        }

        // 5) broad LIKE on match_name
        $like = '%' . $this->db->escape_like_str((string)$raw) . '%';
        $sql = "SELECT id FROM matches WHERE COALESCE(match_name,'') LIKE ? LIMIT 1";
        $q = $this->db->query($sql, [$like]);
        if ($q && $q->num_rows() > 0) return (int)$q->row()->id;

        // nothing matched
        return null;
    }
}