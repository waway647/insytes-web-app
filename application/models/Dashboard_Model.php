<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(E_ALL ^ E_DEPRECATED);

class Dashboard_Model extends CI_Model {

    public function __construct()
    {
        $this->load->database();
    }

    /**
     * Fetches detailed match data and metrics from view_matches_with_team_metrics.
     *
     * @param int $season_id Filter by season.
     * @param int $my_team_id Filter by team ID.
     * @return array Array of match objects.
     */
    public function get_matches_with_metrics($season_id, $my_team_id)
    {
        return $this->db
            ->select('v.*, m.season AS season_name, m.my_team_name, m.opponent_team_name, m.competition, m.venue, m.match_date AS vw_match_date')
            ->from('view_matches_with_team_metrics v')
            ->join('matches_vw m', 'v.match_id = m.match_id', 'left')
            ->where('v.season_id', (int)$season_id)
            ->where('v.my_team_id', (int)$my_team_id)
            // Emit the ORDER BY as a raw string so DESC doesn't get accidentally injected into the IFNULL() arguments.
            ->order_by("IFNULL(v.match_date, m.match_date) DESC, v.match_id DESC", NULL, FALSE)
            ->get()
            ->result();
    }

    /**
     * Fetches basic match data from view_tagged_matches.
     *
     * @param int $season_id
     * @param int $my_team_id
     * @return array
     */
    public function get_tagged_matches($season_id, $my_team_id)
    {
        return $this->db
            ->select('id, match_date, opponent_team_id, my_team_goals, opponent_team_goals, my_overall_rating, opp_overall_rating, status, created_at, updated_at')
            ->from('view_tagged_matches')
            ->where('season_id', (int)$season_id)
            ->where('my_team_id', (int)$my_team_id)
            ->order_by('match_date', 'DESC')
            ->get()
            ->result();
    }

    /**
     * Fetches aggregated season statistics from view_team_season_aggregates.
     *
     * @param int $season_id
     * @param int $my_team_id
     * @return object|null
     */
    public function get_season_aggregates($season_id, $my_team_id)
    {
        return $this->db
            ->select('*')
            ->from('view_team_season_aggregates')
            ->where('season_id', (int)$season_id)
            ->where('team_id', (int)$my_team_id)
            ->get()
            ->row();
    }

    /**
     * Fetches team dashboard summary (wins/draws/losses/clean sheets etc.)
     *
     * @param int $season_id
     * @param int $team_id
     * @return object|null
     */
    public function get_team_dashboard_summary($season_id, $team_id)
    {
        return $this->db
            ->select('*')
            ->from('view_team_dashboard_summary')
            ->where('season_id', (int)$season_id)
            ->where('team_id', (int)$team_id)
            ->get()
            ->row();
    }

    /**
     * Fetches position breakdown rows for a team-season.
     *
     * @param int $season_id
     * @param int $team_id
     * @return array rows (position_group, players_in_group, goalkeeper_save_rate, defenders_duel_win_rate, midfielders_pass_accuracy_pct, forwards_goals_per_90)
     */
    public function get_team_position_breakdown($season_id, $team_id)
    {
        return $this->db
            ->select('*')
            ->from('view_team_position_breakdown')
            ->where('season_id', (int)$season_id)
            ->where('team_id', (int)$team_id)
            ->get()
            ->result();
    }

    /**
     * Fetch top N players for a team-season (view_team_top_players).
     *
     * @param int $season_id
     * @param int $team_id
     * @param int $limit
     * @return array
     */
    public function get_team_top_players($season_id, $team_id, $limit = 3)
    {
        return $this->db
            ->select('team_id, season_id, player_id, first_name, last_name, goals, assists, key_passes, successful_dribbles, interceptions, tackles, avg_dpr, minutes_played, performance_score, goals_per_90, shots_on_target_per_90, summary_json')
            ->from('view_team_top_players')
            ->where('season_id', (int)$season_id)
            ->where('team_id', (int)$team_id)
            ->order_by('performance_score', 'DESC')
            ->limit((int)$limit)
            ->get()
            ->result();
    }

    /**
     * Resolve team name (simple helper).
     *
     * @param int $team_id
     * @return string|null
     */
    public function get_team_name($team_id)
    {
        if (!$team_id) return null;
        $row = $this->db->select('team_name')->from('teams')->where('id', (int)$team_id)->get()->row();
        return $row ? $row->team_name : null;
    }
}