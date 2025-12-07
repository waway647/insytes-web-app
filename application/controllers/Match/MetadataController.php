<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MetadataController extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->library('session');
        $this->load->model('Match_model');
        $this->load->model('Admin/Logs_Model');
        $this->load->helper('log_helper');
	}

	public function create_match()
    {
        // Always return JSON
        $this->output->set_content_type('application/json');

        $season_id      = $this->input->post('season_id', TRUE);
        $season_name    = $this->input->post('season_name', TRUE);

        $competition_id   = $this->input->post('competition_id', TRUE);
        $competition_name = $this->input->post('competition_name', TRUE);

        $venue_id    = $this->input->post('venue_id', TRUE);
        $venue_name  = $this->input->post('venue_name', TRUE);

        $match_date  = $this->input->post('match_date', TRUE);

        $my_team_id    = $this->input->post('my_team_id', TRUE);
        $my_team_name  = $this->input->post('my_team_name', TRUE);
        $my_team_goals = $this->input->post('my_team_goals', TRUE);
        $my_team_result= $this->input->post('my_team_result', TRUE);

        $op_team_id    = $this->input->post('opponent_team_id', TRUE);
        $op_team_name  = $this->input->post('opponent_team_name', TRUE);
        $op_team_goals = $this->input->post('opponent_team_goals', TRUE);

        // Players arrays — CI will parse nested inputs named like my_players[0][name]
        $raw_my_players = $this->input->post('my_players');           // may be array|null
        $raw_opponent_players = $this->input->post('opponent_players');

        // --- Basic validation ---
        $errors = [];

        if (empty($match_date)) {
            $errors[] = 'match_date is required';
        }

        if (empty($my_team_id) && empty($my_team_name)) {
            $errors[] = 'my_team is required';
        }

        if (empty($op_team_id) && empty($op_team_name)) {
            $errors[] = 'opponent_team is required';
        }

        if (!empty($errors)) {
            $payload = [
                'success' => false,
                'message' => implode('; ', $errors)
            ];
            $this->output->set_status_header(400)
                         ->set_output(json_encode($payload));
            return;
        }

        // Normalize numeric fields if provided
        $my_team_goals = ($my_team_goals === '' || $my_team_goals === null) ? null : intval($my_team_goals);
        $op_team_goals = ($op_team_goals === '' || $op_team_goals === null) ? null : intval($op_team_goals);

        if (!empty($my_team_result)) {
            if ($my_team_result == 'Win') {
                $opponent_team_result = 'Lose';
            } elseif ($my_team_result == 'Lose') {
                $opponent_team_result = 'Win';
            } elseif ($my_team_result == 'Draw') {
                $opponent_team_result = 'Win';
            }
        }

        // Build match data array (adjust column names to your schema)
        $matchData = [
            'season_id'           => $season_id ?: null,
            'competition_id'      => $competition_id ?: null,
            'venue_id'            => $venue_id ?: null,
            'match_date'          => $match_date ?: null,
            'my_team_id'          => $my_team_id ?: null,
            'opponent_team_id'    => $op_team_id ?: null,
            'my_team_goals'       => $my_team_goals,
            'opponent_team_goals' => $op_team_goals,
            'my_team_result'      => $my_team_result ?: null,
            'opponent_team_result'=> $opponent_team_result  ?: null,
            'status'              => 'Waiting for video',
            'created_by'          => $this->session->userdata('user_id') ?: null,
            'created_at'          => date('Y-m-d H:i:s')
        ];

        // Normalize players: use model helper so server enforces fallback team_id, shape, etc.
        $myPlayers = [];
        $opponentPlayers = [];

        if (is_array($raw_my_players)) {
            $myPlayers = $this->Match_model->normalize_players($raw_my_players, $matchData['my_team_id']);
        }

        if (is_array($raw_opponent_players)) {
            $opponentPlayers = $this->Match_model->normalize_players($raw_opponent_players, $matchData['opponent_team_id']);
        }

        // --- Call model inside try/catch so we can log and return proper JSON ---
        try {
            // The model should throw Exceptions on failure and return the inserted match_id on success
            $match_id = $this->Match_model->create_match_with_players($matchData, $myPlayers, $opponentPlayers);

            if ($match_id) {
                $metadata = [
                    'match_id' => $match_id,
                    'season' => $season_name ?: '',
                    'competition' => $competition_name ?: '',
                    'venue' => $venue_name ?: '',
                    'date' => $match_date ?: '',
                    'home_team' => $my_team_name ?: '',
                    'away_team' => $op_team_name ?: '',
                    'status' => $matchData['status'],
                    'home_players' => $myPlayers,
                    'away_players' => $opponentPlayers,
                    'created_at' => date('c'),
                    'updated_at' => date('c')
                ];

                $this->create_match_config_json($metadata);
                
                // Log match creation
                LogHelper::logMatchCreated(
                    $match_id,
                    $my_team_name . ' vs ' . $op_team_name,
                    $this->session->userdata('user_id'),
                    $this->session->userdata('email')
                );
            }

            // success response
            $payload = [
                'success' => true,
                'id'      => $match_id,
                'message' => 'Match created successfully'
            ];

            $this->output->set_status_header(201) // Created
                         ->set_output(json_encode($payload));
            return;
        } catch (Exception $ex) {
            // Log the full exception for debugging; don't expose stack traces to clients
            log_message('error', 'MetadataController::create_match failed: ' . $ex->getMessage());
            log_message('error', $ex->getTraceAsString());

            $payload = [
                'success' => false,
                'message' => 'Failed to create match'
            ];

            $this->output->set_status_header(500)
                         ->set_output(json_encode($payload));
            return;
        }
    }

    public function create_match_config_json(array $match) {
        // Base directories (relative to project root)
        $baseDir = FCPATH . 'writable_data';
        $configsDir = $baseDir . '/configs';
        $eventsDir = $baseDir . '/events';

        // Ensure directories exist
        if (!is_dir($configsDir)) {
            if (!mkdir($configsDir, 0755, true) && !is_dir($configsDir)) {
                log_message('error', 'Failed to create configs directory: ' . $configsDir);
                return false;
            }
        }
        if (!is_dir($eventsDir)) {
            if (!mkdir($eventsDir, 0755, true) && !is_dir($eventsDir)) {
                log_message('error', 'Failed to create events directory: ' . $eventsDir);
                return false;
            }
        }

        // Build file names
        $match_id = isset($match['match_id']) ? $match['match_id'] : null;
        if ($match_id === null) {
            log_message('error', 'create_match_config_json: missing match_id');
            return false;
        }

        $opponent = $this->Match_model->get_opponent_name($match_id);
        $opponent = strtolower($opponent);

        // If match_id is numeric and you want file name like "match_123", prefix "match_"
        // If your DB already returns a string id like "match_20252026_001", skip prefixing.
        $file_id = (string)$match_id;
        if (!preg_match('/^match_/', $file_id . '_sbu_vs_' . $opponent)) {
            $file_id = 'match_' . $file_id . '_sbu_vs_' . strtolower(preg_replace('/\s+/', '_', $opponent));
        }

        $configFile = $configsDir . '/' . 'config_' . $file_id . '.json';
        $registryFile = $configsDir . '/registry.json';
        $eventsFileRelative = '../events/' . $file_id . '_events.json'; // stored in registry files.path
        $eventsFile = $eventsDir . '/' . $file_id . '_events.json'; // actual events file path (can be created later)

        // Build match config structure per your spec
        $meta = [
            'id' => $file_id,
            'season' => isset($match['season']) ? $match['season'] : '',
            'competition' => isset($match['competition']) ? $match['competition'] : '',
            'venue' => isset($match['venue']) ? $match['venue'] : '',
            'date' => isset($match['date']) ? $match['date'] : '',
            'attacking_direction' => isset($match['attacking_direction']) ? $match['attacking_direction'] : 'left-to-right',
            'positions_locked' => isset($match['positions_locked']) ? (bool)$match['positions_locked'] : false,
        ];

        // Helper to split players into starting11 and bench using heuristics:
        $splitPlayers = function(array $players, $sidePrefix = '') {
            $starting = [];
            $bench = [];
            foreach ($players as $index => $p) {
                // Expected normalized keys: player_id, name, jersey, position, xi, row_index, team_id
                $id = isset($p['player_id']) && $p['player_id'] !== '' ? $p['player_id'] : ($sidePrefix . ($index + 1));
                $name = isset($p['name']) ? $p['name'] : '';
                $number = isset($p['jersey']) && $p['jersey'] !== '' ? (is_numeric($p['jersey']) ? intval($p['jersey']) : $p['jersey']) : null;
                $position = isset($p['position']) ? $p['position'] : '';
                $x = isset($p['x']) ? $p['x'] : null;
                $y = isset($p['y']) ? $p['y'] : null;
                $rowIndex = isset($p['row_index']) ? $p['row_index'] : null;

                // Heuristics:
                // - if xi === '1' or xi === 1 => starting
                // - else if numeric row_index and row_index <= 11 => starting
                // - else => bench
                $isStarting = false;
                if (isset($p['xi']) && ((string)$p['xi'] === '1' || (int)$p['xi'] === 1)) {
                    $isStarting = true;
                } elseif (is_numeric($rowIndex) && intval($rowIndex) <= 11 && intval($rowIndex) >= 1) {
                    $isStarting = true;
                }

                $playerObj = [
                    'id' => (string)$id,
                    'name' => $name,
                    'number' => $number,
                    'position' => $position,
                    // include x/y only if present (we will fill defaults later for starting players)
                    'x' => $x,
                    'y' => $y
                ];

                if ($isStarting) {
                    $starting[] = $playerObj;
                } else {
                    $bench[] = [
                        'id' => (string)$id,
                        'name' => $name,
                        'number' => $number,
                        'position' => $position
                    ];
                }
            }

            // Assign defaults to starting XI if x/y are missing (do not overwrite values that are present)
            // For home ('h'):
            //   starters 1-6 -> x = 7, y = 23 + (0..5)*11
            //   starters 7-11 -> x = 16, y = 23 + (0..4)*11
            // For away ('a'):
            //   starters 1-6 -> x = 93, y = 23 + (0..5)*11
            //   starters 7-11 -> x = 84, y = 23 + (0..4)*11
            for ($i = 0, $n = count($starting); $i < $n; $i++) {
                // default X
                if (!isset($starting[$i]['x']) || $starting[$i]['x'] === null || $starting[$i]['x'] === '') {
                    if ($sidePrefix === 'h') {
                        $starting[$i]['x'] = ($i < 6) ? 7 : 16;
                    } elseif ($sidePrefix === 'a') {
                        $starting[$i]['x'] = ($i < 6) ? 93 : 84;
                    } else {
                        // fallback if sidePrefix not provided
                        $starting[$i]['x'] = ($i < 6) ? 7 : 16;
                    }
                }

                // default Y
                if (!isset($starting[$i]['y']) || $starting[$i]['y'] === null || $starting[$i]['y'] === '') {
                    $indexInRow = ($i < 6) ? $i : ($i - 6);
                    $starting[$i]['y'] = 23 + ($indexInRow * 11);
                }

                // optional: ensure numeric types
                $starting[$i]['x'] = is_numeric($starting[$i]['x']) ? (float)$starting[$i]['x'] : $starting[$i]['x'];
                $starting[$i]['y'] = is_numeric($starting[$i]['y']) ? (float)$starting[$i]['y'] : $starting[$i]['y'];
            }

            return ['starting11' => $starting, 'bench' => $bench];
        };

        $homePlayers = isset($match['home_players']) && is_array($match['home_players']) ? $match['home_players'] : [];
        $awayPlayers = isset($match['away_players']) && is_array($match['away_players']) ? $match['away_players'] : [];

        // Use prefix h / a for fallback ids
        $homeSplit = $splitPlayers($homePlayers, 'h');
        $awaySplit = $splitPlayers($awayPlayers, 'a');

        $config = [
            'match' => $meta,
            'home' => [
                'name' => isset($match['home_team']) ? $match['home_team'] : '',
                'jersey_color' => isset($match['home_jersey_color']) ? $match['home_jersey_color'] : '',
                'jersey_text_color' => isset($match['home_jersey_text_color']) ? $match['home_jersey_text_color'] : '',
                'starting11' => $homeSplit['starting11'],
                'bench' => $homeSplit['bench']
            ],
            'away' => [
                'name' => isset($match['away_team']) ? $match['away_team'] : '',
                'jersey_color' => isset($match['away_jersey_color']) ? $match['away_jersey_color'] : '',
                'jersey_text_color' => isset($match['away_jersey_text_color']) ? $match['away_jersey_text_color'] : '',
                'starting11' => $awaySplit['starting11'],
                'bench' => $awaySplit['bench']
            ]
        ];

        // Write the match config file
        $encoded = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            log_message('error', 'create_match_config_json: json_encode failed: ' . json_last_error_msg());
            return false;
        }

        $bytes = file_put_contents($configFile, $encoded, LOCK_EX);
        if ($bytes === false) {
            log_message('error', 'create_match_config_json: failed to write config file: ' . $configFile);
            return false;
        }

        // Ensure registry exists and then append/insert the entry
        if (!file_exists($registryFile)) {
            $initial = json_encode(['matches' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents($registryFile, $initial, LOCK_EX);
        }

        $registryJson = file_get_contents($registryFile);
        $registry = json_decode($registryJson, true);
        if (!is_array($registry)) {
            // reset registry if corrupted
            $registry = ['matches' => []];
        }
        if (!isset($registry['matches']) || !is_array($registry['matches'])) {
            $registry['matches'] = [];
        }

        // Build registry entry (per your requested format)
        $now = isset($match['created_at']) ? $match['created_at'] : date('c');
        $updated = isset($match['updated_at']) ? $match['updated_at'] : $now;

        $entry = [
            'id' => $file_id,
            'season' => isset($match['season']) ? $match['season'] : '',
            'competition' => isset($match['competition']) ? $match['competition'] : '',
            'date' => isset($match['date']) ? $match['date'] : '',
            'venue' => isset($match['venue']) ? $match['venue'] : '',
            'home_team' => isset($match['home_team']) ? $match['home_team'] : '',
            'away_team' => isset($match['away_team']) ? $match['away_team'] : '',
            'status' => isset($match['status']) ? $match['status'] : '',
            'files' => [
                'config' => $file_id . '.json',
                'events' => $eventsFileRelative
            ],
            'created_at' => $now,
            'updated_at' => $updated
        ];

        // Replace existing entry if present (so updates won't duplicate)
        $found = false;
        foreach ($registry['matches'] as $i => $m) {
            if (isset($m['id']) && $m['id'] === $file_id) {
                $registry['matches'][$i] = $entry;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $registry['matches'][] = $entry;
        }

        // Write registry file back
        $registryEncoded = json_encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($registryEncoded === false) {
            log_message('error', 'create_match_config_json: registry json_encode failed: ' . json_last_error_msg());
            return false;
        }
        $bytes = file_put_contents($registryFile, $registryEncoded, LOCK_EX);
        if ($bytes === false) {
            log_message('error', 'create_match_config_json: failed to write registry file: ' . $registryFile);
            return false;
        }

        // Optionally create an empty events file for this match (comment out if not desired)
        if (!file_exists($eventsFile)) {
            $initialEvents = json_encode(['match_id' => $file_id, 'events' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents($eventsFile, $initialEvents, LOCK_EX);
        }

        // Success
        return;
    }

    public function update_match_config_json() {
        // Always return JSON
        $this->output->set_content_type('application/json');

        // Read JSON body first (preferred), fall back to CI post() for form-encoded requests
        $raw = $this->input->raw_input_stream;
        $input = json_decode($raw, true);
        if (!is_array($input)) {
            // try to read from normal POST (e.g. form submit)
            $input = array_merge($_POST, $_REQUEST);
        }

        $match_id = isset($input['match_id']) ? $input['match_id'] : null;
        $newConfig = isset($input['config']) ? $input['config'] : (isset($input['new_config']) ? $input['new_config'] : null);

        // If config was passed as JSON string, decode it
        if (is_string($newConfig) && trim($newConfig) !== '') {
            $decoded = json_decode($newConfig, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $newConfig = $decoded;
            } else {
                // invalid JSON in config string
                $payload = ['success' => false, 'message' => 'Invalid JSON supplied for config'];
                $this->output->set_status_header(400)->set_output(json_encode($payload));
                return;
            }
        }

        if (empty($match_id)) {
            $payload = ['success' => false, 'message' => 'match_id is required'];
            $this->output->set_status_header(400)->set_output(json_encode($payload));
            return;
        }

        $opponent = $this->session->userdata('opponent_team_abbreviation') ?? $this->session->userdata('opponent_team_name');
        $opponent = strtolower($opponent);

        // If match_id is numeric and you want file name like "match_123", prefix "match_"
        // If your DB already returns a string id like "match_20252026_001", skip prefixing.
        $file_id = (string)$match_id;
        if (!preg_match('/^match_/', $file_id . '_sbu_vs_' . $opponent)) {
            $file_id = 'match_' . $file_id . '_sbu_vs_' . strtolower(preg_replace('/\s+/', '_', $opponent));
        }

        $baseDir = FCPATH . 'writable_data';
        $configsDir = $baseDir . '/configs';
        $registryFile = $configsDir . '/registry.json';
        $configFile = $configsDir . '/' . 'config_' . $file_id . '.json';
        $eventsFileRelative = '../events/' . $file_id . '_events.json';
        $eventsFile = $baseDir . '/events/' . $file_id . '_events.json';

        // Ensure configs dir exists
        if (!is_dir($configsDir)) {
            if (!mkdir($configsDir, 0755, true) && !is_dir($configsDir)) {
                log_message('error', 'update_match_config_json: failed to create configs dir: ' . $configsDir);
                $payload = ['success' => false, 'message' => 'Server error: cannot create configs directory'];
                $this->output->set_status_header(500)->set_output(json_encode($payload));
                return;
            }
        }

        // Config file must exist to update (avoid creating unexpected files accidentally)
        if (!file_exists($configFile)) {
            $payload = ['success' => false, 'message' => 'Config file not found for given match_id', 'file' => $configFile];
            $this->output->set_status_header(404)->set_output(json_encode($payload));
            return;
        }

        // Read existing config
        $existingJson = @file_get_contents($configFile);
        if ($existingJson === false) {
            log_message('error', 'update_match_config_json: failed to read config file: ' . $configFile);
            $payload = ['success' => false, 'message' => 'Failed to read existing config file'];
            $this->output->set_status_header(500)->set_output(json_encode($payload));
            return;
        }

        $existing = json_decode($existingJson, true);
        if (!is_array($existing)) {
            log_message('error', 'update_match_config_json: existing config JSON invalid: ' . $configFile);
            $payload = ['success' => false, 'message' => 'Existing config file is corrupted'];
            $this->output->set_status_header(500)->set_output(json_encode($payload));
            return;
        }

        // If no new config provided, but maybe top-level fields included instead (backwards compatibility)
        if (empty($newConfig)) {
            // Try to extract known top-level keys from $input (e.g. 'match', 'home', 'away')
            $candidates = ['match', 'home', 'away'];
            foreach ($candidates as $k) {
                if (isset($input[$k])) {
                    $newConfig[$k] = $input[$k];
                }
            }
        }

        if (empty($newConfig) || !is_array($newConfig)) {
            $payload = ['success' => false, 'message' => 'No config payload supplied'];
            $this->output->set_status_header(400)->set_output(json_encode($payload));
            return;
        }

        // Merge incoming config into existing config. We use array_replace_recursive so supplied nested values override existing ones.
        $merged = array_replace_recursive($existing, $newConfig);

        // Ensure essential meta stays consistent: set match.id to file_id
        if (!isset($merged['match']) || !is_array($merged['match'])) $merged['match'] = [];
        $merged['match']['id'] = $file_id;

        // If merged match.attacking_direction missing, default to left-to-right (consistent with create)
        if (empty($merged['match']['attacking_direction'])) {
            $merged['match']['attacking_direction'] = 'left-to-right';
        }
        // Ensure positions_locked boolean exists
        if (!isset($merged['match']['positions_locked'])) {
            $merged['match']['positions_locked'] = false;
        } else {
            $merged['match']['positions_locked'] = (bool)$merged['match']['positions_locked'];
        }

        $merged['match']['positions_locked'] = true;
        $merged['mode'] = 'tagging';

        // Encode and write back to file
        $encoded = json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            log_message('error', 'update_match_config_json: json_encode failed: ' . json_last_error_msg());
            $payload = ['success' => false, 'message' => 'Failed to encode merged config'];
            $this->output->set_status_header(500)->set_output(json_encode($payload));
            return;
        }

        $bytes = @file_put_contents($configFile, $encoded, LOCK_EX);
        if ($bytes === false) {
            log_message('error', 'update_match_config_json: failed to write config file: ' . $configFile);
            $payload = ['success' => false, 'message' => 'Failed to write config file'];
            $this->output->set_status_header(500)->set_output(json_encode($payload));
            return;
        }

        // Update registry entry's updated_at and ensure files.path matches (create registry if missing)
        if (!file_exists($registryFile)) {
            $initial = json_encode(['matches' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            @file_put_contents($registryFile, $initial, LOCK_EX);
        }

        $registryJson = @file_get_contents($registryFile);
        $registry = json_decode($registryJson, true);
        if (!is_array($registry)) {
            $registry = ['matches' => []];
        }
        if (!isset($registry['matches']) || !is_array($registry['matches'])) {
            $registry['matches'] = [];
        }

        $now = date('c');
        $found = false;
        foreach ($registry['matches'] as $i => $m) {
            if (isset($m['id']) && $m['id'] === $file_id) {
                // preserve other registry fields, but update updated_at and files config path if necessary
                $registry['matches'][$i]['updated_at'] = $now;
                if (!isset($registry['matches'][$i]['files']) || !is_array($registry['matches'][$i]['files'])) {
                    $registry['matches'][$i]['files'] = ['config' => $file_id . '.json', 'events' => $eventsFileRelative];
                } else {
                    $registry['matches'][$i]['files']['config'] = $file_id . '.json';
                    if (!isset($registry['matches'][$i]['files']['events'])) {
                        $registry['matches'][$i]['files']['events'] = $eventsFileRelative;
                    }
                }
                // optional: update status/title fields from merged config.match
                if (isset($merged['match']['season'])) $registry['matches'][$i]['season'] = $merged['match']['season'];
                if (isset($merged['match']['competition'])) $registry['matches'][$i]['competition'] = $merged['match']['competition'];
                if (isset($merged['match']['date'])) $registry['matches'][$i]['date'] = $merged['match']['date'];
                if (isset($merged['home']['name'])) $registry['matches'][$i]['home_team'] = $merged['home']['name'];
                if (isset($merged['away']['name'])) $registry['matches'][$i]['away_team'] = $merged['away']['name'];

                $found = true;
                break;
            }
        }

        if (!$found) {
            // Insert new entry if not found (mirrors create behavior)
            $entry = [
                'id' => $file_id,
                'season' => isset($merged['match']['season']) ? $merged['match']['season'] : '',
                'competition' => isset($merged['match']['competition']) ? $merged['match']['competition'] : '',
                'date' => isset($merged['match']['date']) ? $merged['match']['date'] : '',
                'venue' => isset($merged['match']['venue']) ? $merged['match']['venue'] : '',
                'home_team' => isset($merged['home']['name']) ? $merged['home']['name'] : '',
                'away_team' => isset($merged['away']['name']) ? $merged['away']['name'] : '',
                'status' => isset($merged['match']['status']) ? $merged['match']['status'] : '',
                'files' => [
                    'config' => $file_id . '.json',
                    'events' => $eventsFileRelative
                ],
                'created_at' => $now,
                'updated_at' => $now
            ];
            $registry['matches'][] = $entry;
        }

        // Write registry back
        $registryEncoded = json_encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($registryEncoded === false) {
            log_message('error', 'update_match_config_json: registry json_encode failed: ' . json_last_error_msg());
            $payload = ['success' => false, 'message' => 'Failed to encode registry'];
            $this->output->set_status_header(500)->set_output(json_encode($payload));
            return;
        }
        $bytes = @file_put_contents($registryFile, $registryEncoded, LOCK_EX);
        if ($bytes === false) {
            log_message('error', 'update_match_config_json: failed to write registry file: ' . $registryFile);
            $payload = ['success' => false, 'message' => 'Failed to write registry file'];
            $this->output->set_status_header(500)->set_output(json_encode($payload));
            return;
        }

        // Optionally ensure events file exists
        if (!file_exists($eventsFile)) {
            $initialEvents = json_encode(['match_id' => $file_id, 'events' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            @file_put_contents($eventsFile, $initialEvents, LOCK_EX);
        }

        $payload = ['success' => true, 'message' => 'Config updated', 'id' => $file_id];
        $this->output->set_status_header(200)->set_output(json_encode($payload));
        return;
    }

    public function remove_match($match_id = null)
    {
        // Always return JSON
        $this->output->set_content_type('application/json');

        // 1) Try to read JSON body (preferred)
        $raw = $this->input->raw_input_stream;
        $body = json_decode($raw, true);
        if (is_array($body) && isset($body['match_id'])) {
            $match_id = $body['match_id'];
        }

        // 2) Fallback to normal POST body
        if (empty($match_id)) {
            $match_id = $this->input->post('match_id', TRUE);
        }

        // 3) Final validation
        if (empty($match_id)) {
            $payload = ['success' => false, 'message' => 'match_id is required'];
            $this->output->set_status_header(400)->set_output(json_encode($payload));
            return;
        }

        // Normalize match_id to string
        $match_id = (string)$match_id;

        // Prepare paths early (we will try to resolve file_id from registry first)
        $baseDir = FCPATH . 'writable_data';
        $configsDir = $baseDir . '/configs';
        $eventsDir = $baseDir . '/events';
        $registryFile = $configsDir . '/registry.json';

        // Helper: try to find file_id in registry.json by matching numeric match_id inside entry id or equal
        $found_file_id = null;
        if (file_exists($registryFile)) {
            $registryJson = @file_get_contents($registryFile);
            if ($registryJson !== false) {
                $registry = json_decode($registryJson, true);
                if (is_array($registry) && isset($registry['matches']) && is_array($registry['matches'])) {
                    foreach ($registry['matches'] as $entry) {
                        if (!isset($entry['id'])) continue;
                        $id = (string)$entry['id'];
                        // exact or contains numeric id (handles both "match_123..." and "123")
                        if ($id === $match_id || strpos($id, $match_id) !== false) {
                            $found_file_id = $id;
                            break;
                        }
                    }
                }
            } else {
                log_message('debug', 'remove_match: registry.json exists but failed to read it.');
            }
        }

        // If registry did not provide file_id, try to get opponent name from model (before DB deletion)
        $opponent = '';
        if ($found_file_id === null) {
            try {
                if (method_exists($this->Match_model, 'get_opponent_name')) {
                    $opponent = $this->Match_model->get_opponent_name($match_id);
                    $opponent = is_string($opponent) ? strtolower($opponent) : '';
                }
            } catch (Exception $ex) {
                log_message('warning', 'remove_match - get_opponent_name failed (pre-delete): ' . $ex->getMessage());
                $opponent = '';
            }

            if (!empty($opponent)) {
                // build file id same as create_match_config_json uses
                $candidate = (string)$match_id;
                if (!preg_match('/^match_/', $candidate . '_sbu_vs_' . $opponent)) {
                    $candidate = 'match_' . $candidate . '_sbu_vs_' . strtolower(preg_replace('/\s+/', '_', $opponent));
                }
                $found_file_id = $candidate;
            }
        }

        // If still not found, fallback to numeric-based file_id (best-effort)
        if ($found_file_id === null) {
            $found_file_id = 'match_' . $match_id . '_sbu_vs_';
        }

        // Now compute file paths using the resolved file id
        $file_id = (string)$found_file_id;
        $configFile = $configsDir . '/config_' . $file_id . '.json';
        $eventsFile = $eventsDir . '/' . $file_id . '_events.json';

        // --- Delete DB record via model ---
        try {
            if (!method_exists($this->Match_model, 'delete_match')) {
                log_message('error', 'remove_match - Match_model::delete_match not found');
                $payload = ['success' => false, 'message' => 'Server error: delete method not implemented'];
                $this->output->set_status_header(500)->set_output(json_encode($payload));
                return;
            }

            $deleted = $this->Match_model->delete_match($match_id);

            // If model returns 0 or false, consider not found
            if ($deleted === false || $deleted === 0) {
                $payload = ['success' => false, 'message' => 'Match not found or could not be deleted from database'];
                $this->output->set_status_header(404)->set_output(json_encode($payload));
                return;
            }
        } catch (Exception $ex) {
            log_message('error', 'remove_match - DB delete failed: ' . $ex->getMessage());
            $payload = ['success' => false, 'message' => 'Failed to delete match from database'];
            $this->output->set_status_header(500)->set_output(json_encode($payload));
            return;
        }

        // --- File cleanup (best-effort) ---
        $configRemoved = null;
        if (file_exists($configFile)) {
            if (@unlink($configFile)) {
                $configRemoved = true;
                log_message('info', "remove_match - Removed config file: {$configFile}");
            } else {
                $configRemoved = false;
                log_message('error', "remove_match - Failed to remove config file: {$configFile}");
            }
        } else {
            $configRemoved = null; // not present
            log_message('debug', "remove_match - Config file not found: {$configFile}");
        }

        $eventsRemoved = null;
        if (file_exists($eventsFile)) {
            if (@unlink($eventsFile)) {
                $eventsRemoved = true;
                log_message('info', "remove_match - Removed events file: {$eventsFile}");
            } else {
                $eventsRemoved = false;
                log_message('error', "remove_match - Failed to remove events file: {$eventsFile}");
            }
        } else {
            $eventsRemoved = null;
            log_message('debug', "remove_match - Events file not found: {$eventsFile}");
        }

        // --- Remove match folder (assets/videos/matches/match_<match_id>/) recursively ---
        $match_folder = FCPATH . 'assets/videos/matches/match_' . $match_id . '/';
        $folderRemoved = null;
        if (is_dir($match_folder)) {
            try {
                $it = new RecursiveDirectoryIterator($match_folder, FilesystemIterator::SKIP_DOTS);
                $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($files as $file) {
                    $path = $file->getRealPath();
                    if ($file->isDir()) {
                        @rmdir($path);
                    } else {
                        @unlink($path);
                    }
                }
                if (@rmdir($match_folder)) {
                    $folderRemoved = true;
                    log_message('info', "remove_match - Removed match folder: {$match_folder}");
                } else {
                    $folderRemoved = false;
                    log_message('error', "remove_match - Failed to remove match folder (rmdir): {$match_folder}");
                }
            } catch (Exception $ex) {
                $folderRemoved = false;
                log_message('error', "remove_match - Failed to remove match folder '{$match_folder}': " . $ex->getMessage());
            }
        } else {
            $folderRemoved = null;
            log_message('debug', "remove_match - Match folder not found: {$match_folder}");
        }

        // --- Update registry.json (remove entry) ---
        $registryUpdated = null;
        if (!is_dir($configsDir)) {
            $registryUpdated = null;
            log_message('debug', 'remove_match - configs dir missing, skipping registry update');
        } else if (!file_exists($registryFile)) {
            $registryUpdated = null;
            log_message('debug', 'remove_match - registry.json not found, skipping registry update');
        } else {
            $registryJson = @file_get_contents($registryFile);
            if ($registryJson === false) {
                log_message('error', 'remove_match - Failed to read registry.json: ' . $registryFile);
                $registryUpdated = false;
            } else {
                $registry = json_decode($registryJson, true);
                if (!is_array($registry) || !isset($registry['matches']) || !is_array($registry['matches'])) {
                    log_message('warning', 'remove_match - registry.json corrupted or unexpected format, resetting matches array');
                    $registry = ['matches' => []];
                }

                $initialCount = count($registry['matches']);
                // remove entries that exactly match file_id
                $registry['matches'] = array_values(array_filter($registry['matches'], function ($m) use ($file_id, $match_id) {
                    if (!isset($m['id'])) return true;
                    $id = (string)$m['id'];
                    if ($id === $file_id) return false;
                    if ($id === (string)$match_id) return false;
                    if (strpos($id, (string)$match_id) !== false) return false;
                    return true;
                }));

                $encoded = json_encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                if ($encoded === false) {
                    log_message('error', 'remove_match - registry json_encode failed: ' . json_last_error_msg());
                    $registryUpdated = false;
                } else {
                    $written = @file_put_contents($registryFile, $encoded, LOCK_EX);
                    if ($written === false) {
                        log_message('error', 'remove_match - Failed to write registry.json: ' . $registryFile);
                        $registryUpdated = false;
                    } else {
                        $registryUpdated = true;
                        log_message('info', 'remove_match - Registry updated, removed match entry if present: ' . $file_id);
                    }
                }
            }
        }

        // Return a non-sensitive summary
        $payload = [
            'success' => true,
            'message' => 'Match removed from database; attempted to remove related files/registry entry and folder.',
            'files' => [
                'config' => file_exists($configFile) ? 'still_exists' : ($configRemoved === true ? 'removed' : ($configRemoved === false ? 'failed' : 'not_found')),
                'events' => file_exists($eventsFile) ? 'still_exists' : ($eventsRemoved === true ? 'removed' : ($eventsRemoved === false ? 'failed' : 'not_found')),
                'registry' => $registryUpdated === true ? 'updated' : ($registryUpdated === false ? 'failed' : 'skipped'),
                'folder' => is_dir($match_folder) ? 'still_exists' : ($folderRemoved === true ? 'removed' : ($folderRemoved === false ? 'failed' : 'not_found'))
            ],
            'match_id' => $match_id,
            'file_id' => $file_id
        ];

        $this->output->set_status_header(200)->set_output(json_encode($payload));
        return;
    }
}
