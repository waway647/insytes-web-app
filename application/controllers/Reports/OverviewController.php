<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class OverviewController extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->helper('metrics');
    }

    public function index()
    {
        // Raw (original) incoming values (XSS cleaning enabled)
        $raw_match_id   = $this->input->get('match_id', true);
        $raw_match_name = $this->input->get('match_name', true);

        // If neither parameter supplied, show base report page (no payload)
        if (!$raw_match_id && !$raw_match_name) {
            $data = [
                'title' => 'Match Overview',
                'main_content' => 'reports/report',
                'report_content' => 'reports/match_overview/match_overview',
                'match_config' => null,
                'team_metrics' => null,
                'debug_attempted_paths' => [],
                // expose requested/sanitized values (empty)
                'requested_match_id' => $raw_match_id ?? null,
                'requested_match_name' => $raw_match_name ?? null,
                'match_id' => null,
                'match_name' => null,
            ];
            $this->load->view('layouts/main', $data);
            return;
        }

        // sanitize inputs (allow alphanumeric, underscore, hyphen)
        $token_id = $raw_match_id ? preg_replace('/[^A-Za-z0-9_\-]/', '', $raw_match_id) : null;
        $folder   = $raw_match_name ? preg_replace('/[^A-Za-z0-9_\-]/', '', $raw_match_name) : null;

        // If only token_id present, attempt to derive folder name from it
        if (!$folder && $token_id) {
            $parts = explode('_', $token_id);
            if (count($parts) >= 3) {
                // take last 3 parts as likely "<team>_vs_<team>"
                $folder_candidate = implode('_', array_slice($parts, -3));
                $folder = preg_replace('/[^A-Za-z0-9_\-]/', '', $folder_candidate);
            } else {
                $folder = $token_id;
            }
        }

        if (empty($folder)) {
            show_error('Invalid match identifier / folder', 400);
            return;
        }

        // candidate base directories to try (order matters)
        $candidate_bases = [
            rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'output' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR,
            rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'outputs' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR,
            rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'results' . DIRECTORY_SEPARATOR,
        ];

        // Build candidate folder name variations to try
        $folder_candidates = [$folder];
        if (!empty($token_id)) {
            $folder_candidates[] = 'match_' . $token_id . '_' . $folder;
            $folder_candidates[] = $token_id . '_' . $folder;
        }
        // ensure unique, non-empty
        $folder_candidates = array_values(array_unique(array_filter($folder_candidates)));

        $attempted_paths = [];
        $RESULTS_DIR = null;

        // 1) Try deterministic candidate paths first
        foreach ($candidate_bases as $base) {
            foreach ($folder_candidates as $candFolder) {
                $candidate = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $candFolder . DIRECTORY_SEPARATOR;
                $attempted_paths[] = $candidate;
                if (is_dir($candidate) && is_readable($candidate)) {
                    $RESULTS_DIR = rtrim($candidate, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                    break 2;
                }
            }
        }

        // 2) If not found yet, perform broader scan (case-insensitive substring match)
        if ($RESULTS_DIR === null) {
            foreach ($candidate_bases as $base) {
                // skip if base doesn't exist
                if (!is_dir($base) || !is_readable($base)) {
                    $attempted_paths[] = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ' (base missing or unreadable)';
                    continue;
                }

                $entries = @scandir($base);
                if ($entries === false) {
                    $attempted_paths[] = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ' (scandir failed)';
                    continue;
                }

                foreach ($entries as $entry) {
                    if ($entry === '.' || $entry === '..') continue;
                    $full = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $entry;
                    if (!is_dir($full)) continue;
                    // case-insensitive substring match against folder token or id
                    $matchFolder = (stripos($entry, $folder) !== false);
                    $matchId = (!empty($token_id) && stripos($entry, $token_id) !== false);
                    if ($matchFolder || $matchId) {
                        $candidate_path = rtrim($full, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                        $attempted_paths[] = $candidate_path . ' (matched via scan)';
                        if (is_readable($candidate_path)) {
                            $RESULTS_DIR = $candidate_path;
                            break;
                        } else {
                            $attempted_paths[] = $candidate_path . ' (found but not readable)';
                        }
                    } else {
                        $attempted_paths[] = rtrim($full, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ' (examined)';
                    }
                }
            }
        }

        // If still not found, log attempts and show friendly error (with debug info)
        if ($RESULTS_DIR === null) {
            foreach ($attempted_paths as $p) {
                log_message('error', "OverviewController: attempted results path (not found or unreadable): {$p}");
            }

            // For debugging during development, pass those attempted paths to the view.
            $data = [
                'title' => 'Match Overview',
                'main_content' => 'reports/report',
                'report_content' => 'reports/match_overview/match_overview',
                'match_config' => null,
                'team_metrics' => null,
                'debug_attempted_paths' => $attempted_paths,
                'requested_match_id' => $raw_match_id ?? null,
                'requested_match_name' => $raw_match_name ?? null,
                'match_id' => $token_id ?? null,
                'match_name' => $folder ?? null,
            ];
            // show_error will stop execution; useful to present friendly message
            show_error('Report data not available (looked in several locations).', 404);
            return;
        }

        // Determine the canonical folder name (basename of the discovered RESULTS_DIR)
        $found_folder_name = basename(rtrim($RESULTS_DIR, DIRECTORY_SEPARATOR));

        // load metrics file (common expected filename)
        $metrics_file = $RESULTS_DIR . 'san_beda_university_team_derived_metrics.json';
        $team_metrics = null;

        if (is_file($metrics_file) && is_readable($metrics_file)) {
            $raw_metrics = @file_get_contents($metrics_file);
            if ($raw_metrics !== false) {
                $team_metrics = json_decode($raw_metrics, true);
                if ($team_metrics === null) {
                    log_message('error', "OverviewController: invalid JSON in metrics file: {$metrics_file}");
                }
            } else {
                log_message('error', "OverviewController: could not read metrics file: {$metrics_file}");
            }
        } else {
            log_message('info', "OverviewController: metrics file not found at expected path: {$metrics_file}");
        }

        // === load config file from writable_data/configs ===
        $baseDir    = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'writable_data';
        $configsDir = $baseDir . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR;

        // ensure sanitized names (we built $token_id and $folder earlier)
        $match_id_token = $token_id ?? $raw_match_id;
        $folder_token   = $folder ?? $raw_match_name;

        // build expected filename: config_match_<id>_<folder>.json
        $config_file_candidate = $configsDir . 'config_match_' . $match_id_token . '_' . $folder_token . '.json';

        $match_config = null;
        $config_file_path = null;

        // 1) Try exact candidate (most common)
        if (is_file($config_file_candidate) && is_readable($config_file_candidate)) {
            $config_file_path = $config_file_candidate;
            $raw_cfg = @file_get_contents($config_file_path);
            if ($raw_cfg !== false) {
                $match_config = json_decode($raw_cfg, true);
                if ($match_config === null) {
                    log_message('error', "OverviewController: invalid JSON in config file: {$config_file_path}");
                }
            } else {
                log_message('error', "OverviewController: could not read config file: {$config_file_path}");
            }
        } else {
            // 2) Fallback: try any config_match_*.json that contains the folder token or id
            $globPattern = $configsDir . 'config_match_*' . $folder_token . '*.json';
            $found = glob($globPattern);
            if (empty($found)) {
                // try fallback by id too
                $found = glob($configsDir . 'config_match_*' . $match_id_token . '*.json');
            }
            if (!empty($found)) {
                // pick newest by mtime
                usort($found, function($a,$b){ return filemtime($b) - filemtime($a); });
                foreach ($found as $f) {
                    if (is_file($f) && is_readable($f)) {
                        $config_file_path = $f;
                        $raw_cfg = @file_get_contents($f);
                        if ($raw_cfg !== false) {
                            $match_config = json_decode($raw_cfg, true);
                            if ($match_config !== null) break; // good one
                        }
                    }
                }
            } else {
                log_message('info', "OverviewController: no config files found in {$configsDir} matching token '{$folder_token}' or id '{$match_id_token}'");
            }
        }

        //
        // locate heatmap + pass-network visuals under RESULTS_DIR/heatmaps
        //
        $team_heatmap_path = null;
        $pass_network_path = null;

        $heatmaps_dir = rtrim($RESULTS_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'heatmaps' . DIRECTORY_SEPARATOR;

        if (is_dir($heatmaps_dir) && is_readable($heatmaps_dir)) {
            // explicit filenames to check
            $candidates_heatmap = [
                $heatmaps_dir . 'San_Beda_team_heatmap.png',
                $heatmaps_dir . 'team_heatmap.png',
                $heatmaps_dir . 'san_beda_university_team_heatmap.png',
            ];

            foreach ($candidates_heatmap as $c) {
                if (is_file($c) && is_readable($c)) {
                    $team_heatmap_path = $c;
                    break;
                }
            }

            // fallback: glob for anything with "heatmap" in name
            if ($team_heatmap_path === null) {
                $found = glob($heatmaps_dir . '*heatmap*.png');
                if (!empty($found)) {
                    usort($found, function($a,$b){ return filemtime($b) - filemtime($a); });
                    $team_heatmap_path = $found[0];
                }
            }

            // pass network candidates
            $candidates_network = [
                $heatmaps_dir . 'San_Beda_pass_network.png',
                $heatmaps_dir . 'pass_network.png',
                $heatmaps_dir . 'san_beda_university_pass_network.png',
            ];

            foreach ($candidates_network as $c) {
                if (is_file($c) && is_readable($c)) {
                    $pass_network_path = $c;
                    break;
                }
            }

            // fallback: glob for anything with "pass_network" or "network" in name
            if ($pass_network_path === null) {
                $foundNet = glob($heatmaps_dir . '*pass_network*.png');
                if (empty($foundNet)) {
                    $foundNet = glob($heatmaps_dir . '*network*.png');
                }
                if (!empty($foundNet)) {
                    usort($foundNet, function($a,$b){ return filemtime($b) - filemtime($a); });
                    $pass_network_path = $foundNet[0];
                }
            }
        } else {
            log_message('info', "OverviewController: heatmaps directory not found or not readable: {$heatmaps_dir}");
        }

        // Build public URLs using the actual discovered folder name (found_folder_name).
        $team_heatmap_url = null;
        $pass_network_url = null;

        if ($team_heatmap_path !== null) {
            $public_rel_heatmap = 'output' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR . $found_folder_name . DIRECTORY_SEPARATOR . 'heatmaps' . DIRECTORY_SEPARATOR . basename($team_heatmap_path);
            $public_rel_heatmap = str_replace(DIRECTORY_SEPARATOR, '/', $public_rel_heatmap);
            $team_heatmap_url = base_url($public_rel_heatmap);
        }

        if ($pass_network_path !== null) {
            $public_rel_network = 'output' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR . $found_folder_name . DIRECTORY_SEPARATOR . 'heatmaps' . DIRECTORY_SEPARATOR . basename($pass_network_path);
            $public_rel_network = str_replace(DIRECTORY_SEPARATOR, '/', $public_rel_network);
            $pass_network_url = base_url($public_rel_network);
        }

        // Prepare data for view (expose both requested raw values and sanitized/found tokens)
        $data = [
            'title' => 'Match Overview',
            'main_content' => 'reports/report',
            'report_content' => 'reports/match_overview/match_overview',
            'match_config' => $match_config,
            'team_metrics' => $team_metrics,
            'results_dir' => $RESULTS_DIR,
            'metrics_file_path' => $metrics_file,
            'metrics_file_url' => base_url(str_replace(DIRECTORY_SEPARATOR, '/', 'output' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR . $found_folder_name . DIRECTORY_SEPARATOR . 'san_beda_university_team_derived_metrics.json')),
            'config_file_path' => $config_file_path,
            'team_heatmap_path' => $team_heatmap_path,
            'pass_network_path' => $pass_network_path,
            'team_heatmap_url' => $team_heatmap_url,
            'pass_network_url' => $pass_network_url,
            'debug_attempted_paths' => $attempted_paths,
            // expose incoming raw params (XSS-cleaned by input->get)
            'requested_match_id' => $raw_match_id ?? null,
            'requested_match_name' => $raw_match_name ?? null,
            // expose sanitized tokens and discovered canonical folder
            'match_id' => $token_id ?? null,
            'match_name' => $found_folder_name ?? ($folder ?? null),
        ];

        // store tokens in session for later convenience
        if (!empty($token_id)) {
            $this->session->set_userdata('current_match_id', $token_id);
        }
        if (!empty($found_folder_name)) {
            $this->session->set_userdata('current_match_name', $found_folder_name);
        } elseif (!empty($folder)) {
            $this->session->set_userdata('current_match_name', $folder);
        }

        $this->session->set_userdata('current_match_config', $match_config ?? null);
        $this->session->set_userdata('current_team_metrics', $team_metrics ?? null);
        $this->session->set_userdata('current_metrics_file_path', $metrics_file ?? null);
        $this->session->set_userdata('current_metrics_file_url', $data['metrics_file_url'] ?? null);

        $this->load->view('layouts/main', $data);
    }
}
