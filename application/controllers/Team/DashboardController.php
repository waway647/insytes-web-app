<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class DashboardController extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Dashboard_Model', 'dashboard'); // alias for convenience
    }

    /**
     * Dashboard index - collects and prepares data for the view.
     */
    public function index()
    {
        // --- 1. Determine filters (prefer GET -> session -> fallback defaults)
        $current_season_id = $this->input->get('season_id') ?? $this->session->userdata('current_season_id') ?? 41;
        $user_team_id = $this->input->get('team_id') ?? $this->session->userdata('my_team_id') ?? 14;

        // Cast to int for safety
        $current_season_id = (int) $current_season_id;
        $user_team_id = (int) $user_team_id;

        // --- 2. Fetch raw data from views
        $match_metrics = $this->dashboard->get_matches_with_metrics($current_season_id, $user_team_id);
        $tagged_matches = $this->dashboard->get_tagged_matches($current_season_id, $user_team_id);
        $season_aggregates = $this->dashboard->get_season_aggregates($current_season_id, $user_team_id);

        // normalize ratings in fetched collections/objects
        $this->normalize_ratings_in_collection($match_metrics);
        $this->normalize_ratings_in_collection($tagged_matches);
        $this->normalize_ratings_in_item($season_aggregates);

        // ensure defensive metric defaults exist and compute season totals
        $total_interceptions = 0;
        $total_tackles = 0;
        $total_clearances = 0;

        if (is_array($match_metrics)) {
            foreach ($match_metrics as &$m) {
                // set safe numeric defaults for new defensive metrics
                $m->my_interceptions = isset($m->my_interceptions) && $m->my_interceptions !== null ? (int)$m->my_interceptions : 0;
                $m->my_tackles = isset($m->my_tackles) && $m->my_tackles !== null ? (int)$m->my_tackles : 0;
                $m->my_clearances = isset($m->my_clearances) && $m->my_clearances !== null ? (int)$m->my_clearances : 0;

                // also ensure opponent defensive metrics exist (useful for charts/export)
                $m->opp_interceptions = isset($m->opp_interceptions) && $m->opp_interceptions !== null ? (int)$m->opp_interceptions : 0;
                $m->opp_tackles = isset($m->opp_tackles) && $m->opp_tackles !== null ? (int)$m->opp_tackles : 0;
                $m->opp_clearances = isset($m->opp_clearances) && $m->opp_clearances !== null ? (int)$m->opp_clearances : 0;

                // accumulate season totals
                $total_interceptions += $m->my_interceptions;
                $total_tackles += $m->my_tackles;
                $total_clearances += $m->my_clearances;
            }
            unset($m); // break reference
        }

        // The summary view contains wins/draws/losses/clean_sheets/goals conceded etc
        $dashboard_summary = $this->dashboard->get_team_dashboard_summary($current_season_id, $user_team_id);
        $this->normalize_ratings_in_item($dashboard_summary);

        // Position breakdown rows (Goalkeepers, Defenders, Midfielders, Forwards)
        $position_breakdown_rows = $this->dashboard->get_team_position_breakdown($current_season_id, $user_team_id);

        // Convert position breakdown into associative array keyed by position_group for view consumption
        $position_breakdown = [
            'Goalkeepers' => null,
            'Defenders' => null,
            'Midfielders' => null,
            'Forwards' => null,
            'Other' => null
        ];
        foreach ($position_breakdown_rows as $row) {
            $pos = $row->position_group ?? 'Other';
            $position_breakdown[$pos] = $row;
        }

        // Top 3 performing players for the team-season
        $top_players = $this->dashboard->get_team_top_players($current_season_id, $user_team_id, 3);
        $this->normalize_ratings_in_collection($top_players);

        // Latest match (first in ordered match_metrics)
        $latest_match = !empty($match_metrics) ? $match_metrics[0] : null;

        // Team names (my team name + opponent name for latest match if available)
        $team_name = $this->dashboard->get_team_name($user_team_id);
        $opponent_name = null;
        if ($latest_match) {
            // prefer the opponent name that comes with the match (from joined matches_vw)
            if (!empty($latest_match->opponent_team_name)) {
                $opponent_name = $latest_match->opponent_team_name;
            } elseif (isset($latest_match->opponent_team_id) && (int)$latest_match->opponent_team_id > 0) {
                // fallback to DB lookup
                $opponent_name = $this->dashboard->get_team_name((int)$latest_match->opponent_team_id);
            }
            // also prefer my_team_name from the joined view if available (optional)
            if (empty($team_name) && !empty($latest_match->my_team_name)) {
                $team_name = $latest_match->my_team_name;
            }
        }

        // Prepare quick computed stats for the view (last 5 matches)
        $last_5_matches = array_slice($match_metrics, 0, 5);
        $record = ['Win' => 0, 'Draw' => 0, 'Lose' => 0];
        $sum_pass_accuracy = 0.0;
        $sum_shots = 0;
        $sum_shots_on_target = 0;
        $count_for_avg = 0;

        // For last-5 defensive aggregates
        $last5_interceptions = 0;
        $last5_tackles = 0;
        $last5_clearances = 0;

        foreach ($last_5_matches as $m) {
            if (!empty($m->my_team_result)) {
                $res = $m->my_team_result;
                if ($res === 'Win') $record['Win']++;
                elseif ($res === 'Draw') $record['Draw']++;
                elseif ($res === 'Lose') $record['Lose']++;
            }

            if (isset($m->my_passing_accuracy_pct) && $m->my_passing_accuracy_pct !== null) {
                $sum_pass_accuracy += (float) $m->my_passing_accuracy_pct;
                $count_for_avg++;
            }

            $sum_shots += isset($m->my_shots) ? (int)$m->my_shots : 0;
            $sum_shots_on_target += isset($m->my_shots_on_target) ? (int)$m->my_shots_on_target : 0;

            // defensive sums for last 5
            $last5_interceptions += isset($m->my_interceptions) ? (int)$m->my_interceptions : 0;
            $last5_tackles += isset($m->my_tackles) ? (int)$m->my_tackles : 0;
            $last5_clearances += isset($m->my_clearances) ? (int)$m->my_clearances : 0;
        }

        $last_5_pass_accuracy = $count_for_avg > 0 ? round($sum_pass_accuracy / $count_for_avg, 1) : 0.0;
        $last_5_shots_on_target_pct = $sum_shots > 0 ? round(($sum_shots_on_target / $sum_shots) * 100, 1) : 0.0;

        $last5_interceptions_avg = count($last_5_matches) > 0 ? round($last5_interceptions / count($last_5_matches), 1) : 0.0;
        $last5_tackles_avg = count($last_5_matches) > 0 ? round($last5_tackles / count($last_5_matches), 1) : 0.0;
        $last5_clearances_avg = count($last_5_matches) > 0 ? round($last5_clearances / count($last_5_matches), 1) : 0.0;

        // Unpack dashboard_summary for view-level top-level KPIs (safe defaults)
        $total_matches_played = 0;
        $wins = $draws = $losses = 0;
        $clean_sheets = 0;
        $goals_for = 0;
        $goals_against = 0;
        $avg_overall_rating = null;

        if ($dashboard_summary) {
            $total_matches_played = isset($dashboard_summary->matches_played) ? (int)$dashboard_summary->matches_played : 0;
            $wins = isset($dashboard_summary->wins) ? (int)$dashboard_summary->wins : 0;
            $draws = isset($dashboard_summary->draws) ? (int)$dashboard_summary->draws : 0;
            $losses = isset($dashboard_summary->losses) ? (int)$dashboard_summary->losses : 0;
            $clean_sheets = isset($dashboard_summary->clean_sheets) ? (int)$dashboard_summary->clean_sheets : 0;
            $goals_for = isset($dashboard_summary->total_goals_for) ? (int)$dashboard_summary->total_goals_for : 0;
            $goals_against = isset($dashboard_summary->total_goals_against) ? (int)$dashboard_summary->total_goals_against : 0;
            $avg_overall_rating = isset($dashboard_summary->avg_overall_rating) ? round((float)$dashboard_summary->avg_overall_rating, 1) : null;
        }

        // Season aggregates (from view_team_season_aggregates) - may be null if view missing row
        if (!$season_aggregates) {
            $season_aggregates = (object) [
                'matches_played' => 0,
                'total_goals' => 0,
                'total_shots' => 0,
                'total_shots_on_target' => 0,
                'total_passes' => 0,
                'avg_passing_accuracy' => 0,
                'avg_possession_pct' => 0
            ];
        }

        $season_aggregates->avg_possession_pct = isset($season_aggregates->avg_possession_pct)
            ? (int) round((float) $season_aggregates->avg_possession_pct)
            : 0;

        // Latest-match defensive metrics (if available)
        $latest_my_interceptions = $latest_match->my_interceptions ?? 0;
        $latest_my_tackles = $latest_match->my_tackles ?? 0;
        $latest_my_clearances = $latest_match->my_clearances ?? 0;

        // --- 5. Prepare data array for view
        $data = [
            'title' => 'Team Performance Dashboard',
            'main_content' => 'team/dashboard',
            'match_metrics' => $match_metrics,
            'tagged_matches' => $tagged_matches,
            'season_aggregates' => $season_aggregates,
            'dashboard_summary' => $dashboard_summary,
            'position_breakdown' => $position_breakdown,
            'top_players' => $top_players,
            'latest_match' => $latest_match,
            'team_name' => $team_name,
            'opponent_name' => $opponent_name,
            'latest_match_date' => $latest_match->match_date ?? null,
            'total_matches_played' => $total_matches_played,
            'wins' => $wins,
            'draws' => $draws,
            'losses' => $losses,
            'clean_sheets' => $clean_sheets,
            'goals_for' => $goals_for,
            'goals_against' => $goals_against,
            'avg_overall_rating' => $avg_overall_rating,
            'last_5_matches' => $last_5_matches,
            'last_5_pass_accuracy' => $last_5_pass_accuracy,
            'last_5_shots_on_target_pct' => $last_5_shots_on_target_pct,
            'last_5_record' => $record,

            // defensive metrics: season totals
            'total_interceptions' => $total_interceptions,
            'total_tackles' => $total_tackles,
            'total_clearances' => $total_clearances,

            // defensive metrics: last5 averages
            'last5_interceptions_avg' => $last5_interceptions_avg,
            'last5_tackles_avg' => $last5_tackles_avg,
            'last5_clearances_avg' => $last5_clearances_avg,

            // defensive metrics: latest match
            'latest_my_interceptions' => $latest_my_interceptions,
            'latest_my_tackles' => $latest_my_tackles,
            'latest_my_clearances' => $latest_my_clearances,
        ];

        // Render layout / view
        $this->load->view('layouts/main', $data);
    }

    /**
     * Generate CSV for the last 5 matches (view_matches_with_team_metrics joined to matches_vw),
     * save to uploads/reports/, and stream the file directly for download.
     *
     * Defaults: team_id=14, season_id=41 (can be overridden via GET).
     *
     * Usage:
     *   GET /team/dashboard/generate_last5_report
     *   GET /team/dashboard/generate_last5_report?team_id=14&season_id=41
     */
    public function generate_last5_report()
    {
        // --- 1. Filters (default to team_id=14, season_id=41 unless provided via GET)
        $season_id = $this->input->get('season_id') !== null ? (int)$this->input->get('season_id') : 41;
        $team_id   = $this->input->get('team_id')   !== null ? (int)$this->input->get('team_id')   : 14;
        $season_id = (int)$season_id;
        $team_id   = (int)$team_id;

        // --- 2. Query: select from view_matches_with_team_metrics (v) join matches_vw (m)
        $this->db->select('v.*, m.season AS season_name, m.my_team_name, m.opponent_team_name, m.competition, m.venue');
        $this->db->from('view_matches_with_team_metrics v');
        $this->db->join('matches_vw m', 'v.match_id = m.match_id', 'left');
        $this->db->where('v.season_id', $season_id);
        $this->db->where('v.my_team_id', $team_id);

        // order newest first
        $this->db->order_by('IFNULL(v.match_date, m.match_date) DESC, v.match_id DESC');
        $this->db->limit(5);

        $rows = $this->db->get()->result();

        if (!empty($rows)) {
            // round rating fields before writing CSV
            $this->normalize_ratings_in_collection($rows);
            // ensure defensive metrics exist
            foreach ($rows as &$r) {
                $r->my_interceptions = isset($r->my_interceptions) ? (int)$r->my_interceptions : 0;
                $r->my_tackles = isset($r->my_tackles) ? (int)$r->my_tackles : 0;
                $r->my_clearances = isset($r->my_clearances) ? (int)$r->my_clearances : 0;
                $r->opp_interceptions = isset($r->opp_interceptions) ? (int)$r->opp_interceptions : 0;
                $r->opp_tackles = isset($r->opp_tackles) ? (int)$r->opp_tackles : 0;
                $r->opp_clearances = isset($r->opp_clearances) ? (int)$r->opp_clearances : 0;
            }
            unset($r);
        }

        if (empty($rows)) {
            // nothing to export
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'error' => 'No matches found for team_id=' . $team_id . ' season_id=' . $season_id]));
            return;
        }

        // --- 3. Ensure uploads/reports exists
        $reports_dir = FCPATH . 'uploads/reports/';
        if (!is_dir($reports_dir)) {
            if (!mkdir($reports_dir, 0755, true) && !is_dir($reports_dir)) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'error' => 'Failed to create reports directory.']));
                return;
            }
        }

        // --- 4. Filename & path
        $team_label = isset($rows[0]->my_team_name) && $rows[0]->my_team_name ? $rows[0]->my_team_name : 'team';
        $filename = 'last5_' . $this->slugify($team_label) . '_' . date('Ymd_His') . '.csv';
        $filepath = $reports_dir . $filename;

        // --- 5. Open file for writing
        $fp = fopen($filepath, 'w');
        if ($fp === false) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'error' => 'Unable to open file for writing: ' . $filepath]));
            return;
        }

        // write UTF-8 BOM so Excel opens correctly
        fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // CSV headers (added competition & venue) + defensive metrics
        $headers = [
            'match_id',
            'season',               // from matches_vw -> season_name
            'match_date',
            'competition',
            'venue',
            'match_name',           // fallback to "my_team_name vs opponent_team_name"
            'my_team_name',
            'opponent_team_name',
            'my_team_goals',
            'opponent_team_goals',
            'match_result',
            'my_possession_pct',
            'my_passes',
            'my_goals',
            'my_shots',
            'my_shots_on_target',
            'my_shots_on_target_pct',
            'my_passing_accuracy_pct',
            'my_overall_rating',
            'my_match_rating_attack',
            'my_match_rating_defense',
            'my_duels_won',
            'my_duels',
            // new defensive metrics
            'my_interceptions',
            'my_tackles',
            'my_clearances',
            'opp_possession_pct',
            'opp_passes',
            'opp_goals',
            'opp_shots',
            'opp_shots_on_target',
            'opp_passing_accuracy_pct',
            'opp_overall_rating',
            'opp_match_rating_attack',
            'opp_match_rating_defense',
            'opp_duels_won',
            'opp_duels',
            // opponent defensive metrics
            'opp_interceptions',
            'opp_tackles',
            'opp_clearances'
        ];
        fputcsv($fp, $headers);

        // helper to safely get field (object or array)
        $get = function($obj, $key, $fallback = '') {
            if (is_object($obj) && isset($obj->$key)) return $obj->$key;
            if (is_array($obj) && isset($obj[$key])) return $obj[$key];
            return $fallback;
        };

        // write rows (rows are returned newest -> oldest)
        foreach ($rows as $r) {
            $match_name = $get($r, 'match_name', '');
            if (empty($match_name)) {
                $mt = $get($r, 'my_team_name', '');
                $ot = $get($r, 'opponent_team_name', '');
                if ($mt !== '' && $ot !== '') $match_name = $mt . ' vs ' . $ot;
            }

            $shots = (int)$get($r, 'my_shots', 0);
            $sot = (int)$get($r, 'my_shots_on_target', 0);
            $sot_pct = $shots > 0 ? round(($sot / $shots) * 100, 1) : 0.0;

            $row = [
                $get($r, 'match_id', ''),
                $get($r, 'season_name', $get($r, 'season', '')),
                $get($r, 'match_date', ''),
                $get($r, 'competition', ''),
                $get($r, 'venue', ''),
                $match_name,
                $get($r, 'my_team_name', ''),
                $get($r, 'opponent_team_name', ''),
                (int)$get($r, 'my_team_goals', ''),
                (int)$get($r, 'opponent_team_goals', ''),
                $get($r, 'my_team_result', ''),
                $get($r, 'my_possession_pct', ''),
                $get($r, 'my_passes', ''),
                $get($r, 'my_goals', ''),
                $shots,
                $sot,
                $sot_pct,
                $get($r, 'my_passing_accuracy_pct', ''),
                $get($r, 'my_overall_rating', ''),
                $get($r, 'my_match_rating_attack', ''),
                $get($r, 'my_match_rating_defense', ''),
                $get($r, 'my_duels_won', ''),
                $get($r, 'my_duels', ''),
                // new defensive metrics
                (int)$get($r, 'my_interceptions', 0),
                (int)$get($r, 'my_tackles', 0),
                (int)$get($r, 'my_clearances', 0),
                $get($r, 'opp_possession_pct', ''),
                $get($r, 'opp_passes', ''),
                $get($r, 'opp_goals', ''),
                $get($r, 'opp_shots', ''),
                $get($r, 'opp_shots_on_target', ''),
                $get($r, 'opp_passing_accuracy_pct', ''),
                $get($r, 'opp_overall_rating', ''),
                $get($r, 'opp_match_rating_attack', ''),
                $get($r, 'opp_match_rating_defense', ''),
                $get($r, 'opp_duels_won', ''),
                $get($r, 'opp_duels', ''),
                // opponent defensive metrics
                (int)$get($r, 'opp_interceptions', 0),
                (int)$get($r, 'opp_tackles', 0),
                (int)$get($r, 'opp_clearances', 0),
            ];

            fputcsv($fp, $row);
        }

        fclose($fp);

        // --- 6. Stream the file for direct download (but check headers)
        // clear any output buffers to make headers possible
        while (ob_get_level() > 0) { ob_end_clean(); }

        if (headers_sent($file, $line)) {
            // cannot stream; return JSON with file URL (file saved)
            log_message('error', "generate_last5_report: headers already sent in $file on line $line - returning file URL instead of direct download.");
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => true, 'file' => base_url('uploads/reports/' . $filename), 'note' => 'Headers already sent; cannot stream.']));
            return;
        }

        // send headers for download
        $fsize = filesize($filepath);
        header('Content-Description: File Transfer');
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '";');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        if ($fsize !== false) header('Content-Length: ' . $fsize);

        // flush system output buffers then stream file
        flush();
        $chunkSize = 1024 * 1024; // 1MB
        $handle = fopen($filepath, 'rb');
        if ($handle === false) {
            // couldn't open for streaming; return URL instead
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => true, 'file' => base_url('uploads/reports/' . $filename), 'note' => 'File saved but could not open for streaming.']));
            return;
        }

        while (!feof($handle)) {
            echo fread($handle, $chunkSize);
            flush();
        }
        fclose($handle);
        exit;
    }

    /**
     * Simple slugify helper for filenames (keeps unicode letters & numbers).
     */
    protected function slugify($text)
    {
        if (empty($text)) return 'team';
        $text = preg_replace('/[^\p{L}\p{Nd}]+/u', '_', $text);
        $text = trim($text, '_');
        if (function_exists('mb_strtolower')) $text = mb_strtolower($text, 'UTF-8');
        else $text = strtolower($text);
        return $text !== '' ? $text : 'team';
    }

    /**
     * Normalize rating-like fields in a single item (stdClass or array).
     * Rounds numeric fields that match /rating|overall|performance_score/i to 1 decimal place.
     *
     * @param object|array|null &$item
     * @return void
     */
    protected function normalize_ratings_in_item(&$item)
    {
        if ($item === null) return;

        if (is_object($item)) {
            foreach ($item as $k => $v) {
                if (preg_match('/rating|overall|performance_score/i', (string)$k)) {
                    if ($v !== null && $v !== '' && is_numeric($v)) {
                        $item->$k = round((float)$v, 1);
                    }
                }
            }
        } elseif (is_array($item)) {
            foreach ($item as $k => $v) {
                if (preg_match('/rating|overall|performance_score/i', (string)$k)) {
                    if ($v !== null && $v !== '' && is_numeric($v)) {
                        $item[$k] = round((float)$v, 1);
                    }
                }
            }
        }
    }

    /**
     * Normalize rating-like fields for every item in an array of items.
     *
     * @param array|null &$collection
     * @return void
     */
    protected function normalize_ratings_in_collection(&$collection)
    {
        if (!is_array($collection)) return;
        foreach ($collection as &$it) {
            $this->normalize_ratings_in_item($it);
        }
        // unset the reference to avoid later side-effects
        unset($it);
    }
}
