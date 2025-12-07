<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class TaggingController extends CI_Controller {

    private $data_folder;
    private $configs_dir;
    private $events_dir;

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        // If you want to fetch DB match info, ensure LibraryModel (or appropriate model) exists.
        $this->load->model('LibraryModel');
        $this->load->model('Match_Stats_Model');
        $this->load->model('Match_Model');

        // Base writable folder
        $this->data_folder = FCPATH . 'writable_data/';
        if (!is_dir($this->data_folder)) {
            mkdir($this->data_folder, 0775, true);
        }

        // subfolders
        $this->configs_dir = $this->data_folder . 'configs/';
        if (!is_dir($this->configs_dir)) {
            mkdir($this->configs_dir, 0775, true);
        }

        $this->events_dir = $this->data_folder . 'events/';
        if (!is_dir($this->events_dir)) {
            mkdir($this->events_dir, 0775, true);
        }
    }

    public function index($match_id = null)
    {
        // prefer explicit URI segment, otherwise fall back to query string ?match_id=...
        $match_id = $match_id ?: $this->input->get('match_id') ?: $this->session->userdata('current_tagging_match_id');

        // normalize empty string -> null
        if ($match_id === '') $match_id = null;

        $data['title'] = 'Tagging';
        $data['main_content'] = 'studio/tagging/tagging';

        // persist for subsequent page loads
		if ($match_id) {
			$this->session->set_userdata('current_tagging_match_id', $match_id);
		}

        // pass match id to view (may be null)
        $data['match_id'] = $match_id;

        // You can also pass base API URLs if helpful
        $data['api_get_match_data'] = site_url('tagging/get_match_data');
        $data['api_get_events'] = site_url('tagging/get_events');

        $this->load->view('layouts/studio', $data);
    }

    // -------------------------
    // GET: /tagging/get_match_data/{match_id}
    // Returns DB match info + config json + events json for the match
    // -------------------------
    public function get_match_data($match_id = null)
    {
        // accept either URI segment or ?match_id=...
        $match_id = $match_id ?: $this->input->get('match_id');

        if (!$match_id) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Missing match_id']));
            return;
        }

        // Optional DB fetch (LibraryModel must implement get_match_details)
        $dbMatch = null;
        try {
            if (method_exists($this->LibraryModel, 'get_match_details')) {
                $dbMatch = $this->LibraryModel->get_match_details($match_id);
            } else {
                // model method missing — return null and keep going
                log_message('debug', "LibraryModel::get_match_details not found. Skipping DB fetch for match {$match_id}");
            }
        } catch (Exception $ex) {
            log_message('debug', "Error fetching DB match details for {$match_id}: " . $ex->getMessage());
        }

        $opponent = $dbMatch['opponent_team_abbreviation'] ?? $dbMatch['opponent_team_name'] ?? $this->session->userdata('opponent_team_abbreviation') ?? $this->session->userdata('opponent_team_name');

        // read per-match config file
        $configFile = $this->configs_dir . 'config_match_' . $match_id . '_sbu_vs_' . $opponent . '.json';
        $config = null;
        if (file_exists($configFile)) {
            $cfgRaw = file_get_contents($configFile);
            $config = json_decode($cfgRaw, true);
            if ($config === null) {
                log_message('debug', "Config JSON decode error for {$configFile}: " . json_last_error_msg());
                $config = null;
            }
        }

        // read per-match events file
        $eventsFile = $this->events_dir . 'match_' . $match_id . '_sbu_vs_' . $opponent . '_events.json';
        $events = ['match_id' => $match_id, 'events' => []];
        if (file_exists($eventsFile)) {
            $evRaw = file_get_contents($eventsFile);
            $evJson = json_decode($evRaw, true);
            if ($evJson !== null && isset($evJson['events'])) {
                $events = $evJson;
            } else {
                log_message('debug', "Events JSON decode error for {$eventsFile}: " . json_last_error_msg());
            }
        }

        $payload = [
            'success' => true,
            'match_id' => $match_id,
            'db' => $dbMatch,    // may be null
            'config' => $config, // may be null if not created
            'events' => $events
        ];

        if (ob_get_level()) {
            @ob_clean();
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }

    // -------------------------
    // POST: /tagging/save_positions
    // Body: JSON { positions: [ {id, x, y} ], match_id: "..." , lock: bool (optional) }
    // -------------------------
    public function save_positions()
    {
        $input = json_decode(trim(file_get_contents('php://input')), true);
        if (!$input || !isset($input['positions']) || !isset($input['match_id'])) {
            $this->output->set_status_header(400)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Invalid payload: positions + match_id required']));
            return;
        }

        $match_id = $input['match_id'];
        
        // Optional DB fetch (LibraryModel must implement get_match_details)
        $dbMatch = null;
        try {
            if (method_exists($this->LibraryModel, 'get_match_details')) {
                $dbMatch = $this->LibraryModel->get_match_details($match_id);
            } else {
                // model method missing — return null and keep going
                log_message('debug', "LibraryModel::get_match_details not found. Skipping DB fetch for match {$match_id}");
            }
        } catch (Exception $ex) {
            log_message('debug', "Error fetching DB match details for {$match_id}: " . $ex->getMessage());
        }

        $opponent = $dbMatch['opponent_team_abbreviation'] ?? $dbMatch['opponent_team_name'] ?? $this->session->userdata('opponent_team_abbreviation') ?? $this->session->userdata('opponent_team_name');

        $match_id = $input['match_id'];
        $positions = $input['positions'];
        $lockProvided = array_key_exists('lock', $input);
        $lock = $lockProvided ? (bool)$input['lock'] : null;

        $fn = $this->configs_dir . 'config_match_' . $match_id . '_sbu_vs_' . $opponent . '.json';
        if (!file_exists($fn)) {
            $this->output->set_status_header(404)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Config not found for given match', 'path' => $fn]));
            return;
        }

        // open + lock
        $fp = fopen($fn, 'c+');
        if (!$fp) {
            $this->output->set_status_header(500)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Cannot open config file']));
            return;
        }
        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            $this->output->set_status_header(500)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Cannot lock config file']));
            return;
        }

        // read current contents
        rewind($fp);
        $raw = stream_get_contents($fp);
        $cfg = $raw ? json_decode($raw, true) : null;
        if ($raw && $cfg === null) {
            // malformed JSON: return error rather than overwrite
            flock($fp, LOCK_UN);
            fclose($fp);
            $this->output->set_status_header(500)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Config JSON decode error', 'path' => $fn]));
            return;
        }
        if (!$cfg) $cfg = [];

        // build map from provided positions
        $map = [];
        foreach ($positions as $p) {
            if (!isset($p['id'])) continue;
            $id = (string)$p['id'];
            $x = isset($p['x']) ? (float)$p['x'] : 0.0;
            $y = isset($p['y']) ? (float)$p['y'] : 0.0;
            $map[$id] = ['x' => $x, 'y' => $y];
        }

        // update starting11 positions for home/away if present
        foreach (['home', 'away'] as $side) {
            if (!empty($cfg[$side]) && !empty($cfg[$side]['starting11']) && is_array($cfg[$side]['starting11'])) {
                foreach ($cfg[$side]['starting11'] as &$player) {
                    if (isset($player['id'])) {
                        $pid = (string)$player['id'];
                        if (isset($map[$pid])) {
                            $player['x'] = $map[$pid]['x'];
                            $player['y'] = $map[$pid]['y'];
                        }
                    }
                }
                unset($player);
            }
        }

        // set/clear locked flag in config.match.positions_locked if provided
        if (!isset($cfg['match']) || !is_array($cfg['match'])) {
            $cfg['match'] = [];
        }
        if ($lockProvided) {
            $cfg['match']['positions_locked'] = $lock ? true : false;
        }

        // write back safely (overwrite file)
        ftruncate($fp, 0);
        rewind($fp);
        $written = fwrite($fp, json_encode($cfg, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        if ($written === false) {
            $this->output->set_status_header(500)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Failed to write config file']));
            return;
        }

        $this->output->set_content_type('application/json')->set_output(json_encode(['success' => true]));
    }


    // -------------------------
    // POST: /tagging/save_event
    // Body: JSON { event: { ... }, match_id: "..." }
    // Appends event to per-match events file and returns created event id.
    // -------------------------
    public function save_event()
    {
        $input = json_decode(trim(file_get_contents('php://input')), true);
        if (!$input || !isset($input['event']) || !isset($input['match_id'])) {
            $this->output->set_status_header(400)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Invalid payload: event + match_id required']));
            return;
        }

        $match_id = $input['match_id'];
        $event = $input['event'];

        // Optional DB fetch (LibraryModel must implement get_match_details)
        $dbMatch = null;
        try {
            if (method_exists($this->LibraryModel, 'get_match_details')) {
                $dbMatch = $this->LibraryModel->get_match_details($match_id);
            } else {
                // model method missing — return null and keep going
                log_message('debug', "LibraryModel::get_match_details not found. Skipping DB fetch for match {$match_id}");
            }
        } catch (Exception $ex) {
            log_message('debug', "Error fetching DB match details for {$match_id}: " . $ex->getMessage());
        }

        $opponent = $dbMatch['opponent_team_abbreviation'] ?? $dbMatch['opponent_team_name'] ?? $this->session->userdata('opponent_team_abbreviation') ?? $this->session->userdata('opponent_team_name');

        // Try to read the per-match config so we can copy season/competition
        $configFile = $this->configs_dir . 'config_match_' . $match_id . '_sbu_vs_' . $opponent . '.json';
        $config = null;
        if (file_exists($configFile)) {
            $cfgRaw = @file_get_contents($configFile);
            if ($cfgRaw !== false) {
                $decoded = json_decode($cfgRaw, true);
                if ($decoded !== null) {
                    $config = $decoded;
                } else {
                    log_message('debug', "Config JSON decode error for {$configFile}: " . json_last_error_msg());
                }
            } else {
                log_message('debug', "Could not read config file: {$configFile}");
            }
        }

        // helper: lookup possible keys/locations inside config
        $lookupFromConfig = function ($cfg, $keys) {
            foreach ($keys as $k) {
                // direct key
                if (isset($cfg[$k])) return $cfg[$k];
                // nested under match
                if (isset($cfg['match']) && isset($cfg['match'][$k])) return $cfg['match'][$k];
                // nested under meta
                if (isset($cfg['meta']) && isset($cfg['meta'][$k])) return $cfg['meta'][$k];
                // competition.name shape
                if ($k === 'competition' && isset($cfg['competition']['name'])) return $cfg['competition']['name'];
            }
            return null;
        };

        $eventsFile = $this->events_dir . 'match_' . $match_id . '_sbu_vs_' . $opponent . '_events.json';

        // If events file doesn't exist, create an initial skeleton that includes season/competition when available
        if (!file_exists($eventsFile)) {
            $initial = ['match_id' => $match_id];

            if ($config !== null) {
                $season = $lookupFromConfig($config, ['season', 'season_name', 'season_id', 'year']);
                $competition = $lookupFromConfig($config, ['competition', 'competition_name', 'league', 'tournament']);
                if ($season !== null) $initial['season'] = $season;
                if ($competition !== null) $initial['competition'] = $competition;
            }

            $initial['events'] = [];
            file_put_contents($eventsFile, json_encode($initial, JSON_PRETTY_PRINT));
        }

        $fp = fopen($eventsFile, 'c+');
        if (!$fp) {
            $this->output->set_status_header(500)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Cannot open events file']));
            return;
        }

        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            $this->output->set_status_header(500)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Cannot lock events file']));
            return;
        }

        // read existing events
        rewind($fp);
        $contents = stream_get_contents($fp);
        $data = $contents ? json_decode($contents, true) : null;
        if ($contents && $data === null) {
            flock($fp, LOCK_UN);
            fclose($fp);
            $this->output->set_status_header(500)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Events JSON decode error', 'path' => $eventsFile]));
            return;
        }

        // ensure minimal structure
        if (!$data || !isset($data['events'])) $data = ['match_id' => $match_id, 'events' => []];

        // If config available, ensure season/competition are present (copy/overwrite from config)
        if ($config !== null) {
            $season = $lookupFromConfig($config, ['season', 'season_name', 'season_id', 'year']);
            $competition = $lookupFromConfig($config, ['competition', 'competition_name', 'league', 'tournament']);
            if ($season !== null) $data['season'] = $season;
            if ($competition !== null) $data['competition'] = $competition;
        }

        // generate a non-colliding id (evt_0001 ...)
        $n = count($data['events']) + 1;
        $existingIds = array_column($data['events'], 'id');
        do {
            $id = 'evt_' . str_pad($n, 4, '0', STR_PAD_LEFT);
            $n++;
        } while (in_array($id, $existingIds, true));

        $event['id'] = $id;
        $event['created_at'] = date('c');

        $data['events'][] = $event;

        // --- Ensure order: match_id, season, competition at top, preserve other keys, then events ---
        $ordered = ['match_id' => $match_id];
        if (isset($data['season'])) $ordered['season'] = $data['season'];
        if (isset($data['competition'])) $ordered['competition'] = $data['competition'];

        // append any other keys that aren't match_id/season/competition/events (preserve)
        foreach ($data as $k => $v) {
            if (in_array($k, ['match_id', 'season', 'competition', 'events'], true)) continue;
            $ordered[$k] = $v;
        }

        // finally append events
        $ordered['events'] = $data['events'];

        // write back the ordered structure
        ftruncate($fp, 0);
        rewind($fp);
        $written = fwrite($fp, json_encode($ordered, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        if ($written === false) {
            $this->output->set_status_header(500)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Failed to write events file']));
            return;
        }

        $this->output->set_content_type('application/json')->set_output(json_encode(['success' => true, 'id' => $id, 'event' => $event]));
    }


    // -------------------------
    // POST: /tagging/undo_event
    // Body: JSON { match_id: "..." } (optional: event_id to undo specific)
    // -------------------------
    public function undo_event()
    {
        $input = json_decode(trim(file_get_contents('php://input')), true);
        $match_id = isset($input['match_id']) ? $input['match_id'] : null;
        $event_id = isset($input['event_id']) ? $input['event_id'] : null;

        if (!$match_id) {
            $this->output->set_status_header(400)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Missing match_id']));
            return;
        }

        // Optional DB fetch (LibraryModel must implement get_match_details)
        $dbMatch = null;
        try {
            if (method_exists($this->LibraryModel, 'get_match_details')) {
                $dbMatch = $this->LibraryModel->get_match_details($match_id);
            } else {
                // model method missing — return null and keep going
                log_message('debug', "LibraryModel::get_match_details not found. Skipping DB fetch for match {$match_id}");
            }
        } catch (Exception $ex) {
            log_message('debug', "Error fetching DB match details for {$match_id}: " . $ex->getMessage());
        }

        $opponent = $dbMatch['opponent_team_abbreviation'] ?? $dbMatch['opponent_team_name'] ?? $this->session->userdata('opponent_team_abbreviation') ?? $this->session->userdata('opponent_team_name');

        $eventsFile = $this->events_dir . 'match_' . $match_id . '_sbu_vs_' . $opponent . '_events.json';
        if (!file_exists($eventsFile)) {
            $this->output->set_status_header(404)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Events file not found']));
            return;
        }

        $fp = fopen($eventsFile, 'c+');
        if (!$fp) {
            $this->output->set_status_header(500)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Cannot open events file']));
            return;
        }

        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            $this->output->set_status_header(500)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Cannot lock events file']));
            return;
        }

        // read
        rewind($fp);
        $contents = stream_get_contents($fp);
        $data = $contents ? json_decode($contents, true) : null;
        if ($contents && $data === null) {
            flock($fp, LOCK_UN);
            fclose($fp);
            $this->output->set_status_header(500)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Events JSON decode error', 'path' => $eventsFile]));
            return;
        }
        if (!$data || !isset($data['events']) || count($data['events']) === 0) {
            flock($fp, LOCK_UN);
            fclose($fp);
            $this->output->set_status_header(400)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'No events to undo']));
            return;
        }

        // remove event
        $removed = null;
        if ($event_id) {
            $found = false;
            for ($i = count($data['events']) - 1; $i >= 0; $i--) {
                if (isset($data['events'][$i]['id']) && $data['events'][$i]['id'] === $event_id) {
                    $removed = $data['events'][$i];
                    array_splice($data['events'], $i, 1);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                flock($fp, LOCK_UN);
                fclose($fp);
                $this->output->set_status_header(404)->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'message' => 'Event id not found']));
                return;
            }
        } else {
            $removed = array_pop($data['events']);
        }

        // write back
        ftruncate($fp, 0);
        rewind($fp);
        $written = fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        if ($written === false) {
            $this->output->set_status_header(500)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Failed to write events file']));
            return;
        }

        $this->output->set_content_type('application/json')->set_output(json_encode(['success' => true, 'removed' => $removed]));
    }

    // -------------------------
    // GET: /tagging/get_events/{match_id}
    // -------------------------
    public function get_events($match_id = null)
    {
        $match_id = $match_id ?: $this->input->get('match_id');
        if (!$match_id) {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode(['success'=>false,'message'=>'Missing match_id']));
            return;
        }

        // Optional DB fetch (LibraryModel must implement get_match_details)
        $dbMatch = null;
        try {
            if (method_exists($this->LibraryModel, 'get_match_details')) {
                $dbMatch = $this->LibraryModel->get_match_details($match_id);
            } else {
                // model method missing — return null and keep going
                log_message('debug', "LibraryModel::get_match_details not found. Skipping DB fetch for match {$match_id}");
            }
        } catch (Exception $ex) {
            log_message('debug', "Error fetching DB match details for {$match_id}: " . $ex->getMessage());
        }

        $opponent = $this->session->userdata('opponent_team_abbreviation') ?? $this->session->userdata('opponent_team_name');

        $eventsFile = $this->events_dir . 'match_' . $match_id . '_sbu_vs_' . $opponent . '_events.json';
        if (!file_exists($eventsFile)) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['match_id'=>$match_id,'events'=>[]]));
            return;
        }
        $this->output->set_content_type('application/json')->set_output(file_get_contents($eventsFile));
    }


    public function run_pipeline()
    {
        // Accept JSON body
        $input = json_decode(trim(file_get_contents('php://input')), true);

        // Accept either JSON body or query/URI param
        $match_id = $input['match_id'] ?? $this->input->get('match_id') ?? null;
        $background = isset($input['background']) ? (bool)$input['background'] : false;

        if (!$match_id) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Missing match_id']));
            return;
        }

        // Optional DB fetch (LibraryModel may implement get_match_details)
        $dbMatch = null;
        try {
            if (method_exists($this->LibraryModel, 'get_match_details')) {
                $dbMatch = $this->LibraryModel->get_match_details($match_id);
            }
        } catch (Exception $ex) {
            log_message('debug', "Error fetching DB match details for {$match_id}: " . $ex->getMessage());
        }

        // Resolve opponent from DB or session (same heuristic you use elsewhere)
        $opponent = $dbMatch['opponent_team_abbreviation'] 
                    ?? $dbMatch['opponent_team_name'] 
                    ?? $this->session->userdata('opponent_team_abbreviation') 
                    ?? $this->session->userdata('opponent_team_name')
                    ?? 'opponent';

        // normalize opponent -> safe slug (lowercase, alnum and underscores)
        $opponent_slug = strtolower($opponent);
        // replace non-alnum with underscore, collapse multiple underscores
        $opponent_slug = preg_replace('/[^a-z0-9]+/', '_', $opponent_slug);
        $opponent_slug = preg_replace('/_+/', '_', $opponent_slug);
        $opponent_slug = trim($opponent_slug, '_');
        if ($opponent_slug === '') $opponent_slug = 'opponent';

        // Build dataset name
        $dataset_name = 'match_' . preg_replace('/[^0-9a-zA-Z\-_.]/', '', $match_id) . '_sbu_vs_' . $opponent_slug;

        // Path to Python script
        $script_path = FCPATH . 'python_scripts' . DIRECTORY_SEPARATOR . 'automated_pipeline.py';
        if (!file_exists($script_path)) {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success'=>false,'message'=>'Pipeline script not found','path'=>$script_path]));
            return;
        }

        // -----------------------------------------
        // Prefer project venv Python, allow overrides
        // -----------------------------------------
        $is_windows = (stripos(PHP_OS, 'WIN') === 0);

        // expected venv python path inside project
        if ($is_windows) {
            $default_venv_python = FCPATH . 'venv' . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe';
        } else {
            $default_venv_python = FCPATH . 'venv' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'python';
        }

        // Allow environment var or CI config override
        $env_python = getenv('PYTHON_BIN') ?: null;
        $config_python = (method_exists($this, 'config') && $this->config->item('python_path')) ? $this->config->item('python_path') : null;

        // Choose python binary in this order:
        // 1) env var PYTHON_BIN
        // 2) config item python_path (if set)
        // 3) project venv python (if it exists)
        // 4) fallback: 'python' on Windows or '/usr/bin/env python3' on *nix
        $python_bin = null;
        if ($env_python) {
            $python_bin = $env_python;
        } elseif ($config_python) {
            $python_bin = $config_python;
        } elseif (file_exists($default_venv_python)) {
            $python_bin = $default_venv_python;
        } else {
            $python_bin = $is_windows ? 'python' : '/usr/bin/env python3';
        }

        // Validate python binary: if it's an absolute path, check it exists
        if (preg_match('/[\/\\\\]/', $python_bin) && !file_exists($python_bin)) {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Selected python executable not found',
                    'python_bin' => $python_bin,
                    'hint' => 'Set env PYTHON_BIN or CI config python_path, or create venv at project root'
                ]));
            return;
        }

        // Build command as an array (safer than a single shell string)
        $cmdArray = [$python_bin, $script_path, '--dataset', $dataset_name];

        // Background mode handling: platform-specific
        if ($background) {
            $logfile = FCPATH . 'writable_data' . DIRECTORY_SEPARATOR . 'pipeline_' . $dataset_name . '.log';

            if ($is_windows) {
                // Windows: use start /B to detach. Build a command-line string.
                // Note: we use cmd /c start to detach; start requires a "title" arg (empty "")
                $cmdLine = sprintf(
                    'start /B "" %s %s --dataset %s > "%s" 2>&1',
                    escapeshellarg($python_bin),
                    escapeshellarg($script_path),
                    escapeshellarg($dataset_name),
                    $logfile
                );

                // Launch detached
                pclose(popen($cmdLine, 'r'));

                $this->output->set_content_type('application/json')->set_output(json_encode([
                    'success' => true,
                    'background' => true,
                    'cmd' => $cmdLine,
                    'logfile' => $logfile,
                    'dataset' => $dataset_name
                ]));
                return;
            } else {
                // Unix: nohup & echo $!
                $cmdLine = sprintf(
                    'nohup %s %s --dataset %s > %s 2>&1 & echo $!',
                    escapeshellarg($python_bin),
                    escapeshellarg($script_path),
                    escapeshellarg($dataset_name),
                    escapeshellarg($logfile)
                );
                $pid = trim(shell_exec($cmdLine));
                $this->output->set_content_type('application/json')->set_output(json_encode([
                    'success' => true,
                    'background' => true,
                    'pid' => $pid,
                    'logfile' => $logfile,
                    'cmd' => $cmdLine,
                    'dataset' => $dataset_name
                ]));
                return;
            }
        }

        // --- Synchronous run using proc_open with array command ---
        // --- Synchronous run using proc_open with array command (UTF-8 enforced) ---
        $descriptorspec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $start = microtime(true);
        
        // Merge common env sources if present (you already have this)
        $childEnv = [];
        if (!empty($_ENV) && is_array($_ENV)) {
            $childEnv = array_merge($childEnv, $_ENV);
        }
        if (!empty($_SERVER) && is_array($_SERVER)) {
            $childEnv = array_merge($childEnv, $_SERVER);
        }

        // Force UTF-8 for Python stdio; also enable Python UTF-8 mode
        $childEnv['PYTHONIOENCODING'] = 'utf-8';
        $childEnv['PYTHONUTF8'] = '1';

        // On some systems, LANG/LC_ALL help; set reasonable defaults
        if (!isset($childEnv['LANG'])) $childEnv['LANG'] = 'en_US.UTF-8';
        if (!isset($childEnv['LC_ALL'])) $childEnv['LC_ALL'] = 'en_US.UTF-8';

        // --- NEW: ensure Python can determine a "home" dir and matplotlib config dir ---
        $tmp = sys_get_temp_dir();

        // If running on Windows, ensure USERPROFILE / HOMEDRIVE+HOMEPATH exist
        if ($is_windows) {
            if (!isset($childEnv['USERPROFILE'])) {
                // try to inherit from the webserver user profile if available, otherwise fallback to temp
                $childEnv['USERPROFILE'] = getenv('USERPROFILE') ?: $tmp;
            }
            if (!isset($childEnv['HOMEDRIVE']) && !isset($childEnv['HOMEPATH'])) {
                // set minimal HOMEDRIVE/HOMEPATH so Path.home() can form a path if needed
                $childEnv['HOMEDRIVE'] = substr($childEnv['USERPROFILE'], 0, 2); // e.g. "C:"
                $childEnv['HOMEPATH'] = substr($childEnv['USERPROFILE'], 2) ?: '\\';
            }

            // Windows TMP/TEMP
            if (!isset($childEnv['TMP'])) $childEnv['TMP'] = $tmp;
            if (!isset($childEnv['TEMP'])) $childEnv['TEMP'] = $tmp;
        } else {
            // Unix-like: ensure HOME exists
            if (!isset($childEnv['HOME'])) {
                $childEnv['HOME'] = getenv('HOME') ?: $tmp;
            }
        }

        // Force matplotlib to use a project-local config/cache directory to avoid touching HOME
        $mplDir = FCPATH . 'writable_data' . DIRECTORY_SEPARATOR . 'matplotlib';
        if (!is_dir($mplDir)) {
            // create and make writable; webserver user must be able to write here
            @mkdir($mplDir, 0777, true);
            @chmod($mplDir, 0777);
        }
        $childEnv['MPLCONFIGDIR'] = $mplDir;

        // Optionally make PYTHONUSERBASE point to a writable site-packages-ish dir (rarely needed)
        // $childEnv['PYTHONUSERBASE'] = FCPATH . 'writable_data' . DIRECTORY_SEPARATOR . 'pyuserbase';

        // --- Launch process with the child environment and explicit cwd ---
        $cwd = FCPATH; // or your project root where the script expects to run
        $process = @proc_open($cmdArray, $descriptorspec, $pipes, $cwd, $childEnv);

        if (!is_resource($process)) {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success'=>false,'message'=>'Failed to start process','cmdArray'=>$cmdArray]));
            return;
        }

        // non-blocking read
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $status = proc_get_status($process);
        $pid = $status['pid'] ?? null;
        $timeout = 300; // seconds

        while ($status['running']) {
            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);
            usleep(100000);

            if ((microtime(true) - $start) > $timeout) {
                proc_terminate($process, 9);
                $stdout .= stream_get_contents($pipes[1]);
                $stderr .= stream_get_contents($pipes[2]);
                foreach ($pipes as $p) { @fclose($p); }
                proc_close($process);

                $this->output
                    ->set_status_header(500)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Pipeline timed out',
                        'dataset' => $dataset_name,
                        'timeout_seconds' => $timeout,
                        'stdout' => $stdout,
                        'stderr' => $stderr
                    ]));
                return;
            }

            $status = proc_get_status($process);
        }

        // finish
        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);
        foreach ($pipes as $p) { @fclose($p); }
        $exit_code = proc_close($process);
        $runtime = microtime(true) - $start;
        $match_name_id = $dataset_name;

        $pipelineResult = [
            'success' => $exit_code === 0,
            'exit_code' => $exit_code,
            'dataset' => $dataset_name,
            'cmdArray' => $cmdArray,
            'stdout' => $stdout,
            'stderr' => $stderr,
            'runtime_seconds' => round($runtime, 3),
            'pid' => $pid
        ];

        // Try to detect pipeline-level success: prefer JSON output from Python if present
        $pipeline_claims_success = false;
        $pipeline_json = @json_decode($stdout, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($pipeline_json['success'])) {
            $pipeline_claims_success = (bool)$pipeline_json['success'];
        } else {
            // fallback to exit_code
            $pipeline_claims_success = ($exit_code === 0);
        }

        $pipelineResult['pipeline_claims_success'] = $pipeline_claims_success;

        // If pipeline succeeded, call the DB ingest handler synchronously
        if ($pipeline_claims_success) {
            // Ensure the controller's handler is available
            if (method_exists($this, 'process_match_stats_json_to_db_internal')) {
                // call and attach the model summary to response
                $ingestSummary = $this->process_match_stats_json_to_db_internal($match_name_id);
                $pipelineResult['ingest_summary'] = $ingestSummary;
            } else {
                $pipelineResult['ingest_summary'] = [
                    'success' => false,
                    'message' => 'No ingest handler available in controller'
                ];
            }
        } else {
            $pipelineResult['ingest_summary'] = [
                'success' => false,
                'message' => 'Pipeline did not report success, skipping DB ingest'
            ];
        }

        // finally output pipelineResult
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($pipelineResult));
        return;
    }

    /**
     * Internal helper to find the pipeline output JSON files for a given match_id.
     * Returns array with paths or null if not found.
     */
    protected function _find_pipeline_files($match_name_id)
    {
        // Candidate directories where the automated_pipeline.py writes outputs.
        $candidates = [
            FCPATH . 'output' . DIRECTORY_SEPARATOR . 'matches' . DIRECTORY_SEPARATOR . $match_name_id . DIRECTORY_SEPARATOR
        ];

        $f_insights = 'san_beda_university_team_insights.json';
        $f_derived  = 'san_beda_university_team_derived_metrics.json';
        $f_players  = 'san_beda_university_players_derived_metrics.json';

        foreach ($candidates as $base) {
            $p1 = $base . $f_insights;
            $p2 = $base . $f_derived;
            $p3 = $base . $f_players;
            if (file_exists($p1) && file_exists($p2) && file_exists($p3)) {
                return [
                    'insights_path' => $p1,
                    'derived_path'  => $p2,
                    'players_path'  => $p3,
                    'base' => $base
                ];
            }
        }

        // last-resort: glob search under output for a folder containing the match_id
        $globPaths = glob(FCPATH . 'output' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . $f_insights, GLOB_NOSORT);
        foreach ($globPaths as $g) {
            if (strpos($g, $match_name_id) !== false) {
                $basedir = dirname($g) . DIRECTORY_SEPARATOR;
                $p1 = $basedir . $f_insights;
                $p2 = $basedir . $f_derived;
                $p3 = $basedir . $f_players;
                if (file_exists($p1) && file_exists($p2) && file_exists($p3)) {
                    return [
                        'insights_path' => $p1,
                        'derived_path'  => $p2,
                        'players_path'  => $p3,
                        'base' => $basedir
                    ];
                }
            }
        }

        return null;
    }


    /**
     * Internal synchronous handler.
     * Finds the pipeline JSON outputs, decodes them, and calls the model insert method.
     * Returns an array summary (success, inserted/updated counts or errors).
     */
    protected function process_match_stats_json_to_db_internal($match_name_id)
    {
        $files = $this->_find_pipeline_files($match_name_id);
        if (!$files) {
            return [
                'success' => false,
                'message' => 'Pipeline output JSON files not found for match_id: ' . $match_name_id
            ];
        }

        // decode JSON safely
        $insights = json_decode(@file_get_contents($files['insights_path']), true);
        $derived  = json_decode(@file_get_contents($files['derived_path']), true);
        $players  = json_decode(@file_get_contents($files['players_path']), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'message' => 'JSON decode error: ' . json_last_error_msg(),
                'files' => $files
            ];
        }

        // sanitize match_id to numeric only for DB use
        // e.g. match_123_sbu_vs_opponent -> 123
        if (preg_match('/match_(\d+)_/', $match_name_id, $matches)) {
            $match_id = $matches[1];
        } else {
            // fallback if pattern not found
            $match_id = preg_replace('/[^0-9]/', '', $match_name_id);
        }

        // call model - your model function expects decoded arrays
        try {
            $summary = $this->Match_Stats_Model->match_stats_json_to_db_inserts($match_name_id, $insights, $derived, $players, $files['base']);
            $tagged = $this->Match_Model->update_match_status($match_id, 'Completed');
        } catch (Exception $ex) {
            log_message('error', 'process_match_stats_json_to_db_internal exception: ' . $ex->getMessage());
            $summary = ['success' => false, 'message' => 'Model exception: ' . $ex->getMessage()];
        }

        return $summary;
    }


    /**
     * Public endpoint to trigger processing (can be called separately).
     * Accepts POST JSON or query param ?match_id=...
     */
    public function match_stats_json_to_db()
    {
        $input = json_decode(trim(file_get_contents('php://input')), true);
        $match_id = $input['match_id'] ?? $this->input->get('match_id') ?? null;

        if (!$match_id) {
            return $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Missing match_id']));
        }

        $result = $this->process_match_stats_json_to_db_internal($match_id);

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }
}
