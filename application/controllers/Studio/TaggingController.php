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

        // read per-match config file
        $configFile = $this->configs_dir . 'match_' . $match_id . '.json';
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
        $eventsFile = $this->events_dir . 'match_' . $match_id . '_events.json';
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
        $positions = $input['positions'];
        $lockProvided = array_key_exists('lock', $input);
        $lock = $lockProvided ? (bool)$input['lock'] : null;

        $fn = $this->configs_dir . 'match_' . $match_id . '.json';
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

        $eventsFile = $this->events_dir . 'match_' . $match_id . '_events.json';
        if (!file_exists($eventsFile)) {
            // create an empty events file first
            file_put_contents($eventsFile, json_encode(['match_id' => $match_id, 'events' => []], JSON_PRETTY_PRINT));
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
        if (!$data || !isset($data['events'])) $data = ['match_id' => $match_id, 'events' => []];

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

        $eventsFile = $this->events_dir . 'match_' . $match_id . '_events.json';
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
        $eventsFile = $this->events_dir . 'match_' . $match_id . '_events.json';
        if (!file_exists($eventsFile)) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['match_id'=>$match_id,'events'=>[]]));
            return;
        }
        $this->output->set_content_type('application/json')->set_output(file_get_contents($eventsFile));
    }
}
