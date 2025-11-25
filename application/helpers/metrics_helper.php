<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * metrics_helper.php
 *
 * Reusable functions for locating team metrics in the derived metrics structure.
 * Keep this logic out of views.
 */

if (!function_exists('find_team_metrics')) {
    /**
     * Find a team's metrics in the provided metrics array.
     * Accepts case-insensitive exact matches and fallback partial matches.
     *
     * @param array|null $metrics  Associative array of metrics keyed by team name or numeric keys.
     * @param string|null $team_name  Team name to search for.
     * @return array|null  Found metrics or null.
     */
    function find_team_metrics($metrics, $team_name) {
        if (empty($metrics) || empty($team_name)) return null;

        // If metrics is an assoc array keyed by team name
        foreach ($metrics as $k => $v) {
            if (!is_string($k)) continue;
            if (strcasecmp(trim($k), trim($team_name)) === 0) return $v;
        }

        // If metrics keys are nested objects or different shapes, also test values
        foreach ($metrics as $k => $v) {
            // try matching against key or serialized forms
            if (is_string($k) && (stripos($k, $team_name) !== false || stripos($team_name, $k) !== false)) {
                return $v;
            }
            // sometimes metrics array entries use ['team'] key
            if (is_array($v) && isset($v['team']) && is_string($v['team'])) {
                if (strcasecmp(trim($v['team']), trim($team_name)) === 0) return $v;
                if (stripos($v['team'], $team_name) !== false || stripos($team_name, $v['team']) !== false) return $v;
            }
        }

        return null;
    }
}
