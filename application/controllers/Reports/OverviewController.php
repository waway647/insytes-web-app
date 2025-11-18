<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class OverviewController extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
    }

    public function index()
    {
        $raw_match_id   = $this->input->get('match_id', true);
        $raw_match_name = $this->input->get('match_name', true);

        if (!$raw_match_id && !$raw_match_name) {
            $data = [
                'title' => 'Match Overview',
                'main_content' => 'reports/report',
                'report_content' => 'reports/match_overview/match_overview',
                'match_config' => null,
                'team_metrics' => null,
                'debug_attempted_paths' => []
            ];
            $this->load->view('layouts/main', $data);
            return;
        }

        // sanitize
        $token_id = $raw_match_id ? preg_replace('/[^A-Za-z0-9_\-]/', '', $raw_match_id) : null;
        $folder   = $raw_match_name ? preg_replace('/[^A-Za-z0-9_\-]/', '', $raw_match_name) : null;

        if (!$folder && $token_id) {
            // extract likely folder suffix from match id, e.g. match_11_sbu_vs_rma -> sbu_vs_rma
            $parts = explode('_', $token_id);
            if (count($parts) >= 3) {
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
            // your actual live path (singular "output")
            rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'output' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR,
            // older/alternate path (plural "outputs")
            rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'outputs' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR,
            // common other location (assets)
            rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'results' . DIRECTORY_SEPARATOR,
        ];

        $attempted_paths = [];
        $RESULTS_DIR = null;
        foreach ($candidate_bases as $base) {
            $candidate = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;
            $attempted_paths[] = $candidate;
            if (is_dir($candidate) && is_readable($candidate)) {
                $RESULTS_DIR = $candidate;
                break;
            }
        }

        if ($RESULTS_DIR === null) {
            // log all tried paths for debugging
            foreach ($attempted_paths as $p) {
                log_message('error', "OverviewController: attempted results path (not found or unreadable): {$p}");
            }
            // Pass attempted paths to the view for temporary debugging
            $data = [
                'title' => 'Match Overview',
                'main_content' => 'reports/report',
                'report_content' => 'reports/match_overview/match_overview',
                'match_config' => null,
                'team_metrics' => null,
                'debug_attempted_paths' => $attempted_paths
            ];
            // friendly error shown to user
            show_error('Report data not available (looked in several locations).', 404);
            return;
        }

        // load sanbeda_team_derived_metrics.json
        $metrics_file = $RESULTS_DIR . 'sanbeda_team_derived_metrics.json';
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

        // === start: load config file from writable_data/configs ===
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
        // --- NEW: locate heatmap + pass-network visuals under RESULTS_DIR/heatmaps ---
        //
        $team_heatmap_path = null;
        $pass_network_path = null;

        $heatmaps_dir = rtrim($RESULTS_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'heatmaps' . DIRECTORY_SEPARATOR;

        if (is_dir($heatmaps_dir) && is_readable($heatmaps_dir)) {
            // explicit filenames to check (as you specified)
            $candidates_heatmap = [
                $heatmaps_dir . 'San_Beda_team_heatmap.png',
                $heatmaps_dir . 'team_heatmap.png',
                $heatmaps_dir . 'sanbeda_team_heatmap.png',
            ];

            foreach ($candidates_heatmap as $c) {
                if (is_file($c) && is_readable($c)) {
                    $team_heatmap_path = $c;
                    break;
                }
            }

            // fallback: glob for anything with "heatmap" in name (case-insensitive)
            if ($team_heatmap_path === null) {
                $found = glob($heatmaps_dir . '*heatmap*.png');
                if (!empty($found)) {
                    // pick newest
                    usort($found, function($a,$b){ return filemtime($b) - filemtime($a); });
                    $team_heatmap_path = $found[0];
                }
            }

            // pass network candidates
            $candidates_network = [
                $heatmaps_dir . 'San_Beda_pass_network.png',
                $heatmaps_dir . 'pass_network.png',
                $heatmaps_dir . 'sanbeda_pass_network.png',
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

        // Build public URLs (only if files are inside publically accessible "output/matches/<folder>/heatmaps/..." path)
        $team_heatmap_url = null;
        $pass_network_url = null;

        // If RESULTS_DIR maps under 'output/matches/<folder>/' we generate base_url() for that.
        // Build the relative URL using the same pattern we used for metrics_file_url earlier.
        if ($team_heatmap_path !== null) {
            $public_rel_heatmap = 'output' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . 'heatmaps' . DIRECTORY_SEPARATOR . basename($team_heatmap_path);
            $public_rel_heatmap = str_replace(DIRECTORY_SEPARATOR, '/', $public_rel_heatmap);
            $team_heatmap_url = base_url($public_rel_heatmap);
        }

        if ($pass_network_path !== null) {
            $public_rel_network = 'output' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . 'heatmaps' . DIRECTORY_SEPARATOR . basename($pass_network_path);
            $public_rel_network = str_replace(DIRECTORY_SEPARATOR, '/', $public_rel_network);
            $pass_network_url = base_url($public_rel_network);
        }

        // pass to view (include debug info to inspect from browser)
        $data = [
            'title' => 'Match Overview',
            'main_content' => 'reports/report',
            'report_content' => 'reports/match_overview/match_overview',
            'match_config' => $match_config,
            'team_metrics' => $team_metrics,
            'results_dir' => $RESULTS_DIR,
            'metrics_file_path' => $metrics_file,
            'metrics_file_url' => base_url(str_replace(DIRECTORY_SEPARATOR, '/', 'output' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . 'sanbeda_team_derived_metrics.json')),
            'config_file_path' => $config_file_path,
            'team_heatmap_path' => $team_heatmap_path,
            'pass_network_path' => $pass_network_path,
            'team_heatmap_url' => $team_heatmap_url,
            'pass_network_url' => $pass_network_url,
            'debug_attempted_paths' => $attempted_paths,
        ];

        // store tokens in session for later convenience
        if (!empty($token_id)) {
            $this->session->set_userdata('current_match_id', $token_id);
        }
        if (!empty($folder)) {
            $this->session->set_userdata('current_match_name', $folder);
        }

        $this->load->view('layouts/main', $data);
    }
}
