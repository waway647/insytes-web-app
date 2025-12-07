<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PlayerPerformanceController extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
    }

    public function index()
    {
        // 1. Get Match ID/Name (Prefer GET, fallback to Session)
        $raw_match_id   = $this->input->get('match_id', true);
        $raw_match_name = $this->input->get('match_name', true);

        $match_id = $raw_match_id ?: $this->session->userdata('current_match_id');
        $match_name = $raw_match_name ?: $this->session->userdata('current_match_name');

        // 2. Sanitize for file/view usage
        $sanitized_match_id = $match_id ? preg_replace('/[^A-Za-z0-9_\-]/', '', $match_id) : null;
        $sanitized_match_name = $match_name ? preg_replace('/[^A-Za-z0-9_\-]/', '', $match_name) : null;

        // 3. Load Session Configs
        $session_match_config = $this->session->userdata('current_match_config');
        $session_team_metrics = $this->session->userdata('current_team_metrics');
        $session_metrics_file_path = $this->session->userdata('current_metrics_file_path');
        $session_metrics_file_url = $this->session->userdata('current_metrics_file_url');

        // --- DATA PROCESSING START ---
        $json_file_path = null;
        if (!empty($sanitized_match_name)) {
            $json_file_path = 'output/matches/' . $sanitized_match_name . '/san_beda_university_player_insights.json';
        }

        $processed_players = [];

        if ($json_file_path && file_exists(FCPATH . $json_file_path)) {
            $json_content = file_get_contents(FCPATH . $json_file_path);
            if ($json_content !== false) {
                $insights_data = json_decode($json_content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($insights_data)) {

                    // helper: parse a field that might be an array or a delimited string into an array
                    $toArray = function($val) {
                        if (is_array($val)) {
                            return array_values(array_unique(array_filter(array_map('trim', $val))));
                        }
                        if (is_string($val) && $val !== '') {
                            $parts = preg_split('/[;,\|\n]+/', $val);
                            return array_values(array_unique(array_filter(array_map('trim', $parts))));
                        }
                        return [];
                    };

                    // Normalizer: convert varying shapes to a single canonical shape
                    $normalize = function(array $p) use ($toArray) {
                        $raw_name = trim($p['name'] ?? $p['player_name'] ?? '');
                        $surname = $given_name = '';
                        if ($raw_name !== '') {
                            if (strpos($raw_name, ',') !== false) {
                                $parts = array_map('trim', explode(',', $raw_name, 2));
                                $surname = $parts[0] ?? '';
                                $given_name = $parts[1] ?? '';
                            } else {
                                $parts = array_values(array_filter(array_map('trim', explode(' ', $raw_name))));
                                $given_name = $parts[0] ?? '';
                                $surname = isset($parts[1]) ? $parts[1] : '';
                            }
                        }

                        $position = $p['position'] ?? $p['pos'] ?? $p['role'] ?? '';
                        $minutes_played = $p['minutes_played'] ?? $p['minutes'] ?? null;
                        $jersey_number = null;
                        if (isset($p['number']) && $p['number'] !== '') {
                            $jersey_number = $p['number'];
                        } elseif (isset($p['jersey']) && $p['jersey'] !== '') {
                            $jersey_number = $p['jersey'];
                        }

                        // Normalize development_areas and key_strengths separately
                        $devAreas = $toArray($p['development_areas'] ?? $p['development'] ?? null);
                        $keyStrengths = $toArray($p['key_strengths'] ?? null);

                        return [
                            'raw_name'            => $raw_name,
                            'surname'             => $surname,
                            'given_name'          => $given_name,
                            'full_name'           => trim(($given_name ? $given_name . ' ' : '') . $surname),
                            'position'            => $position,
                            'jersey_number'       => $jersey_number,
                            'minutes_played'      => $minutes_played,
                            'rating'              => $p['rating'] ?? null,
                            'dpr'                 => $p['dpr'] ?? ($p['predicted_dpr'] ?? null),
                            'predicted_dpr'       => $p['predicted_dpr'] ?? ($p['dpr'] ?? null),
                            'dpr_change'          => $p['dpr_change'] ?? null,
                            'notes'               => $p['notes'] ?? null,
                            'dpr_breakdown'       => $p['dpr_breakdown'] ?? null,
                            'key_stats_p90'       => $p['key_stats_p90'] ?? null,
                            'development_areas'   => $devAreas,     // normalized array (may be empty)
                            'key_strengths'       => $keyStrengths, // normalized array (may be empty)
                            'raw'                 => $p,
                        ];
                    };

                    // Combine both arrays into one iterable list (works when one is missing)
                    $combined_source = array_merge(
                        $insights_data['players'] ?? [],
                        $insights_data['ui_table'] ?? []
                    );

                    $unique_players = [];
                    $fallback_index = 0;

                    foreach ($combined_source as $p) {
                        $nameCheck = trim($p['name'] ?? $p['player_name'] ?? '');
                        if ($nameCheck === '' || strcasecmp($nameCheck, 'Unnamed') === 0) {
                            continue;
                        }

                        $norm = $normalize($p);

                        // DEDUPE KEY: use normalized name ONLY (collapse spaces, remove commas, lowercase)
                        $key_name = strtolower(preg_replace('/\s+/', ' ', trim(str_replace(',', '', $norm['raw_name'] ?? ''))));
                        if ($key_name === '') {
                            // fallback to jersey if name missing; still unlikely
                            $jersey = ($norm['jersey_number'] !== null && $norm['jersey_number'] !== '') ? (string)$norm['jersey_number'] : '';
                            if ($jersey === '') {
                                $key = '__unknown__' . (++$fallback_index);
                            } else {
                                $key = '__jerseyonly__' . $jersey;
                            }
                        } else {
                            $key = $key_name; // name-only dedupe
                        }

                        if (isset($unique_players[$key])) {
                            // Merge: prefer existing non-empty; fill missing from $norm
                            $existing = $unique_players[$key];

                            // Fields to merge; include both development_areas and key_strengths
                            $fields = [
                                'surname','given_name','full_name','position','jersey_number',
                                'minutes_played','rating','dpr','notes','dpr_breakdown','key_stats_p90'
                            ];

                            foreach ($fields as $field) {
                                $existingVal = $existing[$field] ?? null;
                                $newVal = $norm[$field] ?? null;

                                // If both are arrays -> merge unique values
                                if (is_array($existingVal) && is_array($newVal)) {
                                    $merged = array_values(array_unique(array_filter(array_map('trim', array_merge($existingVal, $newVal)))));
                                    $existing[$field] = $merged;
                                    continue;
                                }

                                // If existing is array but new is scalar, append
                                if (is_array($existingVal) && !is_array($newVal) && $newVal !== null && $newVal !== '') {
                                    $existing[$field] = array_values(array_unique(array_filter(array_map('trim', array_merge($existingVal, [ (string)$newVal ])))));
                                    continue;
                                }

                                // If existing is scalar and new is array, prefer non-empty new array
                                if (!is_array($existingVal) && is_array($newVal) && count($newVal) > 0) {
                                    $existing[$field] = array_values(array_unique(array_filter(array_map('trim', $newVal))));
                                    continue;
                                }

                                // If existing is empty/null and new has value -> set it
                                $isExistingEmpty = $existingVal === null || $existingVal === '' || (is_array($existingVal) && count($existingVal) === 0);
                                if ($isExistingEmpty && ($newVal !== null && $newVal !== '')) {
                                    $existing[$field] = $newVal;
                                    continue;
                                }

                                // otherwise keep existing
                            }

                            // --- Merge array fields that are separate: development_areas + key_strengths ---
                            $existingDev = $existing['development_areas'] ?? [];
                            $newDev = $norm['development_areas'] ?? [];
                            if (!is_array($existingDev)) $existingDev = $toArray($existingDev ?? []);
                            if (!is_array($newDev)) $newDev = $toArray($newDev ?? []);
                            $existing['development_areas'] = array_values(array_unique(array_filter(array_map('trim', array_merge($existingDev, $newDev)))));

                            $existingKS = $existing['key_strengths'] ?? [];
                            $newKS = $norm['key_strengths'] ?? [];
                            if (!is_array($existingKS)) $existingKS = $toArray($existingKS ?? []);
                            if (!is_array($newKS)) $newKS = $toArray($newKS ?? []);
                            $existing['key_strengths'] = array_values(array_unique(array_filter(array_map('trim', array_merge($existingKS, $newKS)))));

                            // track raw sources
                            if (!isset($existing['raw_merged'])) $existing['raw_merged'] = [$existing['raw']];
                            $existing['raw_merged'][] = $norm['raw'];

                            $unique_players[$key] = $existing;
                        } else {
                            $norm['raw_merged'] = [$norm['raw']];
                            // ensure development_areas and key_strengths are arrays
                            if (!is_array($norm['development_areas'])) $norm['development_areas'] = is_null($norm['development_areas']) ? [] : [$norm['development_areas']];
                            if (!is_array($norm['key_strengths'])) $norm['key_strengths'] = is_null($norm['key_strengths']) ? [] : [$norm['key_strengths']];
                            $unique_players[$key] = $norm;
                        }
                    }

                    // Final processed players array
                    $processed_players = array_values($unique_players);

                } else {
                    log_message('error', 'Player insights JSON invalid for ' . ($sanitized_match_name ?? 'unknown'));
                }
            } else {
                log_message('error', 'Failed to read JSON file: ' . FCPATH . $json_file_path);
            }
        } else {
            if ($json_file_path) {
                log_message('info', 'Player insights file not found: ' . FCPATH . $json_file_path);
            } else {
                log_message('info', 'No sanitized match name provided, skipping file load.');
            }
        }

        // --- insert after you read $insights_data and before you build $data ---
        // Build a lightweight summary (top 3 items per category, single metric shown)
        $match_summary = [
            'top_scorers' => ['players' => [], 'insights' => []],
            'top_defenders' => ['players' => [], 'insights' => []],
            'top_passers' => ['players' => [], 'insights' => []],
            'match_recommendations' => $insights_data['match_recommendations'] ?? []
        ];

        // Helper to pick main metric for each category
        if (!empty($insights_data['top_scorers']['players'])) {
            $i = 0;
            foreach ($insights_data['top_scorers']['players'] as $p) {
                if ($i++ >= 3) break;
                $match_summary['top_scorers']['players'][] = [
                    'name' => $p['name'] ?? '',
                    'goals_p90' => $p['goals_p90'] ?? ($p['shots_p90'] ?? null)
                ];
            }
            // keep short insights (rank, name, predicted_dpr) — trim long text
            foreach (array_slice($insights_data['top_scorers']['insights'] ?? [], 0, 3) as $ins) {
                $match_summary['top_scorers']['insights'][] = [
                    'rank' => $ins['rank'] ?? null,
                    'name' => $ins['name'] ?? '',
                    'position' => $ins['position'] ?? '',
                    'predicted_dpr' => $ins['predicted_dpr'] ?? null
                ];
            }
        }

        if (!empty($insights_data['top_defenders']['players'])) {
            $i = 0;
            foreach ($insights_data['top_defenders']['players'] as $p) {
                if ($i++ >= 3) break;
                $match_summary['top_defenders']['players'][] = [
                    'name' => $p['name'] ?? '',
                    'interceptions_p90' => $p['interceptions_p90'] ?? null
                ];
            }
            foreach (array_slice($insights_data['top_defenders']['insights'] ?? [], 0, 3) as $ins) {
                $match_summary['top_defenders']['insights'][] = [
                    'rank' => $ins['rank'] ?? null,
                    'name' => $ins['name'] ?? '',
                    'position' => $ins['position'] ?? '',
                    'predicted_dpr' => $ins['predicted_dpr'] ?? null
                ];
            }
        }

        if (!empty($insights_data['top_passers']['players'])) {
            $i = 0;
            foreach ($insights_data['top_passers']['players'] as $p) {
                if ($i++ >= 3) break;
                $match_summary['top_passers']['players'][] = [
                    'name' => $p['name'] ?? '',
                    'passes_p90' => $p['passes_p90'] ?? null
                ];
            }
            foreach (array_slice($insights_data['top_passers']['insights'] ?? [], 0, 3) as $ins) {
                $match_summary['top_passers']['insights'][] = [
                    'rank' => $ins['rank'] ?? null,
                    'name' => $ins['name'] ?? '',
                    'position' => $ins['position'] ?? '',
                    'predicted_dpr' => $ins['predicted_dpr'] ?? null
                ];
            }
        }

        // Later when you build $data, add 'match_summary' => $match_summary
        // (if you use the $data array later, merge this key into it)

        // Compute counts from normalized players
        $defenders_count = $midfielders_count = $attackers_count = 0;
        $def_positions = ['CB','LB','RB','LWB','RWB','SW'];
        $mid_positions = ['CDM','CM','CAM','RM','LM','DM','AM'];

        foreach ($processed_players as $pp) {
            $pos = strtoupper(trim($pp['position'] ?? ''));
            if (in_array($pos, $def_positions)) {
                $defenders_count++;
            } elseif (in_array($pos, $mid_positions)) {
                $midfielders_count++;
            } elseif ($pos !== '' && $pos !== 'N/A') {
                $attackers_count++;
            }
        }

        $players_count = count($processed_players);
        // --- DATA PROCESSING END ---

        $data = [
            'title' => 'Player Performance',
            'main_content' => 'reports/report',
            'report_content' => 'reports/player_performance/player_performance',

            'defenders_count'   => $defenders_count,
            'midfielders_count' => $midfielders_count,
            'attackers_count'   => $attackers_count,
            'players_count'     => $players_count,
            'players'           => $processed_players,
            'match_summary' => $match_summary,

            'requested_match_id' => $raw_match_id ?? null,
            'requested_match_name' => $raw_match_name ?? null,
            'match_id' => $sanitized_match_id ?? null,
            'match_name' => $sanitized_match_name ?? null,
            'match_config' => $session_match_config ?? null,
            'team_metrics' => $session_team_metrics ?? null,
            'metrics_file_path' => $session_metrics_file_path ?? '',
            'metrics_file_url' => $session_metrics_file_url ?? null,
        ];

        $this->load->view('layouts/main', $data);
    }
}
