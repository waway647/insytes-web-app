<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class TeamSummaryController extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
    }
    public function index()
    {
        // incoming (XSS-cleaned) GET params
        $raw_match_id   = $this->input->get('match_id', true);
        $raw_match_name = $this->input->get('match_name', true);

        // prefer explicit GET params, fall back to session
        $match_id   = $raw_match_id ?: $this->session->userdata('current_match_id');
        $match_name = $raw_match_name ?: $this->session->userdata('current_match_name');

        // sanitized tokens for filesystem lookup
        $sanitized_match_id   = $match_id ? preg_replace('/[^A-Za-z0-9_\-]/', '', $match_id) : null;
        $sanitized_match_name = $match_name ? preg_replace('/[^A-Za-z0-9_\-]/', '', $match_name) : null;

        // session-provided values (may have been set by OverviewController)
        $session_match_config      = $this->session->userdata('current_match_config');
        $session_team_metrics      = $this->session->userdata('current_team_metrics');
        $session_metrics_file_path = $this->session->userdata('current_metrics_file_path');
        $session_metrics_file_url  = $this->session->userdata('current_metrics_file_url');

        //
        // --- Robust derived metrics load (san_beda_university_team_derived_metrics.json) ---
        //
        // This block tries:
        //  1) session decoded metrics array ($session_team_metrics)
        //  2) session_metrics_file_path (can be a file OR a directory)
        //  3) fallback search in common output paths using sanitized tokens
        //
        $derived_metrics = null;

        // 1) prefer session-decoded metrics when present
        if (!empty($session_team_metrics) && is_array($session_team_metrics)) {
            $derived_metrics = $session_team_metrics;
        }

        // helper to read a json file and decode it to array (returns array|null)
        $try_read_json_file = function($path) {
            if (empty($path)) return null;
            $p = rtrim($path, '/\\');

            // if it's a directory, append expected filename
            if (is_dir($p)) {
                $p = $p . DIRECTORY_SEPARATOR . 'san_beda_university_team_derived_metrics.json';
            }
            // if it's a file path already, try it as-is (could be named exactly that file)
            if (is_file($p) && is_readable($p)) {
                $raw = @file_get_contents($p);
                if ($raw !== false) {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) return $decoded;
                    log_message('error', "TeamSummaryController::index invalid JSON in derived metrics file: {$p}");
                } else {
                    log_message('error', "TeamSummaryController::index could not read derived metrics file: {$p}");
                }
            }
            return null;
        };

        // 2) if session path exists, try reading it (file or dir)
        if ($derived_metrics === null && !empty($session_metrics_file_path)) {
            $maybe = $try_read_json_file($session_metrics_file_path);
            if (is_array($maybe)) {
                $derived_metrics = $maybe;
            } else {
                log_message('info', "TeamSummaryController::index could not load derived metrics from session path: {$session_metrics_file_path}");
            }
        }

        // 3) final fallback: attempt to discover the match folder under output/ or outputs/ or assets/results/
        if ($derived_metrics === null) {
            $token  = $sanitized_match_id ?? null;
            $folder = $sanitized_match_name ?? null;

            if (!empty($token) || !empty($folder)) {
                $candidate_bases = [
                    rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'output' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR,
                    rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'outputs' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR,
                    rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'results' . DIRECTORY_SEPARATOR,
                ];

                $cands = [];
                if (!empty($folder)) $cands[] = $folder;
                if (!empty($token)) $cands[] = $token;
                if (!empty($token) && !empty($folder)) {
                    $cands[] = 'match_' . $token . '_' . $folder;
                    $cands[] = $token . '_' . $folder;
                }
                $cands = array_values(array_unique(array_filter($cands)));

                foreach ($candidate_bases as $base) {
                    if (!is_dir($base)) continue;
                    foreach ($cands as $cand) {
                        $trydir = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $cand;
                        $maybe = $try_read_json_file($trydir);
                        if (is_array($maybe)) {
                            $derived_metrics = $maybe;
                            // store discovered directory (ensure trailing separator)
                            $this->session->set_userdata('current_metrics_file_path', rtrim($trydir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
                            break 2;
                        }
                    }
                }
            }
        }

        // pick the team
        $team_name = $mc['home']['name'] ?? 'San Beda University'; // adjust according to your logic
        $team_metrics = is_array($derived_metrics) ? ($derived_metrics[$team_name] ?? null) : null;

        // assign ratings
        $team_ratings = [
            'overall'      => $team_metrics['overall_rating'] ?? null,
            'attack'       => $team_metrics['match_rating_attack'] ?? null,
            'defense'      => $team_metrics['match_rating_defense'] ?? null,
            'distribution' => $team_metrics['match_rating_distribution'] ?? null,
            'general'      => $team_metrics['match_rating_general'] ?? null,
            'discipline'   => $team_metrics['match_rating_discipline'] ?? null,
        ];

        if (is_array($derived_metrics)) {
            if (isset($derived_metrics['overall_rating'])) {
                $team_ratings['overall'] = $derived_metrics['overall_rating'];
            }
            if (isset($derived_metrics['match_rating_attack'])) {
                $team_ratings['attack'] = $derived_metrics['match_rating_attack'];
            }
            if (isset($derived_metrics['match_rating_defense'])) {
                $team_ratings['defense'] = $derived_metrics['match_rating_defense'];
            }
            if (isset($derived_metrics['match_rating_distribution'])) {
                $team_ratings['distribution'] = $derived_metrics['match_rating_distribution'];
            }
            if (isset($derived_metrics['match_rating_general'])) {
                $team_ratings['general'] = $derived_metrics['match_rating_general'];
            }
            if (isset($derived_metrics['match_rating_discipline'])) {
                $team_ratings['discipline'] = $derived_metrics['match_rating_discipline'];
            }
        }

        //
        // --- Prepare partialData for server-rendered insight templates (like match_overview) ---
        //
        $mc = $session_match_config ?? [];
        $partialData = [
            'team_metrics'      => $session_team_metrics ?? null,    // decoded team metrics (may be null)
            'mc'                => $mc,                             // match config (may be empty)
            'metrics_file_path' => $session_metrics_file_path ?? '',
            'metrics_file_url'  => $session_metrics_file_url ?? null,
            'team_ratings'      => $team_ratings,                   // ratings extracted from derived metrics
            // optionally include the raw derived_metrics for partials that might want it
            'derived_metrics'   => is_array($derived_metrics) ? $derived_metrics : null,
        ];

        // prepare view data (include already-computed items)
        $data = [
            'title' => 'Team Summary',
            'main_content' => 'reports/report',
            'report_content' => 'reports/team_summary/team_summary',
            'requested_match_id' => $raw_match_id ?? null,
            'requested_match_name' => $raw_match_name ?? null,
            'match_id' => $sanitized_match_id ?? null,
            'match_name' => $sanitized_match_name ?? null,
            'match_config' => $session_match_config ?? null,
            'team_metrics' => $session_team_metrics ?? null,
            'metrics_file_path' => $session_metrics_file_path ?? '',
            'metrics_file_url' => $session_metrics_file_url ?? null,
            // inject our new values for the view
            'team_ratings' => $team_ratings,
            'partialData'  => $partialData,
        ];

        // render the layout (the team_summary view can use $partialData and $team_ratings)
        $this->load->view('layouts/main', $data);
    }

    /**
     * AJAX endpoint: returns insight fragment for $type (general|distribution|attacking|defense|discipline)
     * Example: /index.php/reports/teamsummarycontroller/insight/attacking?match_id=19&match_name=match_19_sbu_vs_2wfc
     */
    public function insight($type = 'general')
    {
        $allowed = ['overall','general','distribution','attacking','defense','discipline'];
        if (!in_array($type, $allowed)) {
            show_404();
            return;
        }

        $this->load->helper('url');

        // Prefer explicit GET params, else session values
        $raw_match_id   = $this->input->get('match_id', true);
        $raw_match_name = $this->input->get('match_name', true);

        $match_id   = $raw_match_id ?: $this->session->userdata('current_match_id');
        $match_name = $raw_match_name ?: $this->session->userdata('current_match_name');

        // sanitize tokens
        $token = $match_id ? preg_replace('/[^A-Za-z0-9_\-]/', '', $match_id) : null;
        $folder = $match_name ? preg_replace('/[^A-Za-z0-9_\-]/', '', $match_name) : null;

        // candidate bases (same as OverviewController)
        $candidate_bases = [
            rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'output' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR,
            rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'outputs' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR,
            rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'results' . DIRECTORY_SEPARATOR,
        ];

        $attempted_paths = []; // collect for debugging
        $RESULTS_DIR = null;

        // 0) If session has current_metrics_file_path, prefer it
        $metrics_path = $this->session->userdata('current_metrics_file_path') ?? '';
        if (!empty($metrics_path) && is_dir($metrics_path)) {
            $RESULTS_DIR = rtrim($metrics_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }

        // 1) Try exact folder (match_name) directly (highest priority)
        if ($RESULTS_DIR === null && !empty($folder)) {
            foreach ($candidate_bases as $base) {
                $candidate = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;
                $attempted_paths[] = $candidate;
                if (is_dir($candidate) && is_readable($candidate)) {
                    $RESULTS_DIR = rtrim($candidate, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                    break;
                }
            }
        }

        // 2) Try deterministic token-based candidates (if token exists)
        if ($RESULTS_DIR === null && !empty($token)) {
            $cands = [$token];
            if (!empty($folder)) {
                $cands[] = 'match_' . $token . '_' . $folder;
                $cands[] = $token . '_' . $folder;
            } else {
                $cands[] = 'match_' . $token;
            }
            $cands = array_values(array_unique(array_filter($cands)));

            foreach ($candidate_bases as $base) {
                foreach ($cands as $cand) {
                    $candidate = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $cand . DIRECTORY_SEPARATOR;
                    $attempted_paths[] = $candidate;
                    if (is_dir($candidate) && is_readable($candidate)) {
                        $RESULTS_DIR = rtrim($candidate, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                        break 2;
                    }
                }
            }
        }

        // 3) Broader scan (case-insensitive substring match) as last resort
        if ($RESULTS_DIR === null) {
            foreach ($candidate_bases as $base) {
                if (!is_dir($base) || !is_readable($base)) {
                    $attempted_paths[] = rtrim($base, DIRECTORY_SEPARATOR) . ' (missing/unreadable)';
                    continue;
                }
                $entries = @scandir($base);
                if ($entries === false) {
                    $attempted_paths[] = rtrim($base, DIRECTORY_SEPARATOR) . ' (scandir failed)';
                    continue;
                }

                foreach ($entries as $entry) {
                    if ($entry === '.' || $entry === '..') continue;
                    $full = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $entry;
                    $attempted_paths[] = $full . DIRECTORY_SEPARATOR;
                    if (!is_dir($full)) continue;

                    $matchFolder = (!empty($folder) && stripos($entry, $folder) !== false);
                    $matchToken = (!empty($token) && stripos($entry, $token) !== false);

                    if ($matchFolder || $matchToken) {
                        $candidate_path = rtrim($full, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                        if (is_readable($candidate_path)) {
                            $RESULTS_DIR = $candidate_path;
                            break 2; // found
                        }
                    }
                }
            }
        }

        if ($RESULTS_DIR !== null) {
            $metrics_path = $RESULTS_DIR;
            // cache for subsequent calls this session (helps performance)
            $this->session->set_userdata('current_metrics_file_path', $metrics_path);
        }

        // If still no metrics_path — return debug info so you can see attempted locations
        if (empty($metrics_path)) {
            // log for server side debugging
            foreach ($attempted_paths as $p) {
                log_message('error', "TeamSummaryController::insight attempted path: {$p}");
            }

            // helpful HTML fragment for client-side debugging
            echo '<div class="text-sm text-red-400 p-6">No match metrics folder found for this match. Paths tried:<ul>';
            foreach ($attempted_paths as $p) {
                echo '<li><code>' . htmlspecialchars($p) . '</code></li>';
            }
            echo '</ul></div>';
            return;
        }

        // Normalize metrics path and try to find JSON / PHP file
        $metrics_path = rtrim($metrics_path, '/\\') . DIRECTORY_SEPARATOR;
        $jsonFile = $metrics_path . 'san_beda_university_team_insights.json';
        $phpFile  = $metrics_path . 'san_beda_university_team_insights.php';

        // add to attempted paths for transparency
        $attempted_paths[] = $jsonFile;
        $attempted_paths[] = $phpFile;

        $insights = [];

        if (is_file($jsonFile) && is_readable($jsonFile)) {
            $raw = @file_get_contents($jsonFile);
            $decoded = $raw ? json_decode($raw, true) : null;
            if (is_array($decoded)) {
                $insights = $decoded;
            } else {
                log_message('error', "TeamSummaryController::insight invalid JSON at {$jsonFile}");
            }
        } elseif (is_file($phpFile) && is_readable($phpFile)) {
            (function($file, &$out){
                include $file; // expected to set $team_insights
                if (isset($team_insights) && is_array($team_insights)) $out = $team_insights;
            })($phpFile, $insights);
        } else {
            // log attempted file checks
            log_message('error', "TeamSummaryController::insight did not find insights files. Checked: " . implode(', ', [$jsonFile, $phpFile]));
            // show helpful client fragment with attempted files
            echo '<div class="text-sm text-red-400 p-6">No insights file (san_beda_university_team_insights) found in match folder. Files checked:<ul>';
            echo '<li><code>' . htmlspecialchars($jsonFile) . '</code></li>';
            echo '<li><code>' . htmlspecialchars($phpFile) . '</code></li>';
            echo '</ul></div>';
            return;
        }

        // map incoming type to JSON keys (your JSON uses "attack" not "attacking")
        $typeKeyMap = [
            'overall'      => 'overall',
            'attacking'    => 'attack',
            'defense'      => 'defense',
            'distribution' => 'distribution',
            'discipline'   => 'discipline',
            'general'      => 'general',
        ];
        $typeKey = $typeKeyMap[$type] ?? $type;

        $detailed = $insights['detailed_analysis'][$typeKey] ?? [];
        $coaching = $insights['coaching_assessment'][$typeKey] ?? [];
        
        $overall  = $insights['overall'] ?? []; // in case match_summary lives under overall

        $raw_key_metrics = $detailed['key_metrics'] ?? null;
        $raw_match_summary = $detailed['match_summary'] ?? $overall['match_summary'] ?? '';

        // ensure key_metrics is an array
        $key_metrics = is_array($raw_key_metrics) ? $raw_key_metrics : [];

        // ensure match_summary is a string (fallback to empty)
        $match_summary = is_string($raw_match_summary) ? $raw_match_summary : (string)($raw_match_summary ?? '');

        $viewData = [
            'overall_assessment'      => $detailed['overall_assessment'] ?? '',
            'strength'                => $detailed['strengths'] ?? $detailed['strength'] ?? $detailed['key_strengths'] ?? [],
            'areas_for_improvement'   => $detailed['areas_for_improvement'] ?? $detailed['areas'] ?? $detailed['priority_improvements'] ?? [],
            'key_metrics'             => $key_metrics,
            'match_summary'           => $match_summary,
            'performance_rating'      => $coaching['performance_rating'] ?? '',
            'tactical_summary'        => $coaching['tactical_summary'] ?? '',
            'key_observations'        => $coaching['key_observations'] ?? [],
            'coaching_priorities'     => $coaching['coaching_priorities'] ?? [],
            'next_training_focus'     => $coaching['next_training_focus'] ?? '',
        ];

        // Ensure the view exists
        $view_path = 'reports/team_summary/insights/' . $type;
        if (!file_exists(APPPATH . "views/{$view_path}.php")) {
            echo '<div class="text-sm text-red-400 p-6">Insight template not available.</div>';
            return;
        }

        // Render only the fragment
        $this->load->view($view_path, $viewData);
    }
}