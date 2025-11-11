<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class TaggingController extends CI_Controller {

	private $data_folder;

	public function __construct() {
		parent::__construct();
		$this->load->library('session');

		// location for JSON files - ensure writable
        $this->data_folder = FCPATH . 'writable_data/';
        if (!is_dir($this->data_folder)) {
            mkdir($this->data_folder, 0775, true);
        }
	}
	public function index()
	{
		$this->load->helper('url');

		$data['title'] = 'Tagging';
		$data['main_content'] = 'studio/tagging/tagging';
		$this->load->view('layouts/studio', $data);
	}

	// GET: /tagging/get_config
    public function get_config()
    {
        $fn = $this->data_folder . 'config.json';
        if (!file_exists($fn)) {
            // respond with error but allow client to create/modify config
            $this->output
                ->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success'=>false,'message'=>'Config not found','path'=>$fn]));
            return;
        }
        $json = file_get_contents($fn);
        $this->output->set_content_type('application/json')->set_output($json);
    }

    // POST: /tagging/save_positions
    // Body: JSON { positions: [ {id, x, y} ], match_id: "..."}
    public function save_positions()
    {
        $input = json_decode(trim(file_get_contents('php://input')), true);
        if (!$input || !isset($input['positions'])) {
            $this->output->set_status_header(400)->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Invalid payload']));
            return;
        }

        $positions = $input['positions'];
        $lock = isset($input['lock']) ? (bool)$input['lock'] : false;

        $fn = FCPATH . 'writable_data/config.json';
        if (!file_exists($fn)) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['success'=>false,'message'=>'Config not found']));
            return;
        }

        $cfg = json_decode(file_get_contents($fn), true);
        if (!$cfg) $cfg = [];

        // update positions
        $map = [];
        foreach ($positions as $p) {
            $map[$p['id']] = ['x' => (float)$p['x'], 'y' => (float)$p['y']];
        }
        foreach (['home','away'] as $side) {
            if (!empty($cfg[$side]['starting11'])) {
                foreach ($cfg[$side]['starting11'] as &$player) {
                    if (isset($map[$player['id']])) {
                        $player['x'] = $map[$player['id']]['x'];
                        $player['y'] = $map[$player['id']]['y'];
                    }
                }
                unset($player);
            }
        }

        // set locked flag if requested
        if ($lock) $cfg['positions_locked'] = true;

        file_put_contents($fn, json_encode($cfg, JSON_PRETTY_PRINT));
        $this->output->set_content_type('application/json')->set_output(json_encode(['success'=>true]));
    }

    // POST: /tagging/save_event
    // Body: JSON event object (see sample). This appends to events.json and returns created event id.
    public function save_event()
    {
        $input = json_decode(trim(file_get_contents('php://input')), true);
        if (!$input || !isset($input['event'])) {
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode(['success'=>false,'message'=>'Invalid payload']));
            return;
        }
        $event = $input['event'];

        $eventsFile = $this->data_folder . 'events.json';
        // create skeleton if missing
        if (!file_exists($eventsFile)) {
            $skeleton = ['match_id' => (isset($event['match_id']) ? $event['match_id'] : ''), 'events' => []];
            file_put_contents($eventsFile, json_encode($skeleton, JSON_PRETTY_PRINT));
        }

        // read & lock
        $fp = fopen($eventsFile, 'c+');
        if (!$fp) {
            $this->output->set_status_header(500)->set_content_type('application/json')->set_output(json_encode(['success'=>false,'message'=>'Cannot open events file']));
            return;
        }
        flock($fp, LOCK_EX);
        $contents = stream_get_contents($fp);
        $data = json_decode($contents, true);
        if (!$data) $data = ['match_id'=>'','events'=>[]];

        // create id
        $id = 'evt_' . str_pad(count($data['events']) + 1, 4, '0', STR_PAD_LEFT);
        $event['id'] = $id;
        $event['created_at'] = date('c');

        $data['events'][] = $event;

        // write
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        $this->output->set_content_type('application/json')->set_output(json_encode(['success'=>true,'id'=>$id,'event'=>$event]));
    }

    // POST: /tagging/undo_event
    public function undo_event()
    {
        $eventsFile = $this->data_folder . 'events.json';
        if (!file_exists($eventsFile)) {
            $this->output->set_status_header(404)->set_content_type('application/json')->set_output(json_encode(['success'=>false,'message'=>'Events file not found']));
            return;
        }
        $fp = fopen($eventsFile, 'c+');
        if (!$fp) {
            $this->output->set_status_header(500)->set_content_type('application/json')->set_output(json_encode(['success'=>false,'message'=>'Cannot open events file']));
            return;
        }
        flock($fp, LOCK_EX);
        $contents = stream_get_contents($fp);
        $data = json_decode($contents, true);
        if (!$data || !isset($data['events']) || count($data['events'])===0) {
            flock($fp, LOCK_UN);
            fclose($fp);
            $this->output->set_status_header(400)->set_content_type('application/json')->set_output(json_encode(['success'=>false,'message'=>'No events to undo']));
            return;
        }
        $removed = array_pop($data['events']);
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        $this->output->set_content_type('application/json')->set_output(json_encode(['success'=>true,'removed'=>$removed]));
    }

    // GET: /tagging/get_events
    public function get_events()
    {
        $eventsFile = $this->data_folder . 'events.json';
        if (!file_exists($eventsFile)) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['match_id'=>'','events'=>[]]));
            return;
        }
        $this->output->set_content_type('application/json')->set_output(file_get_contents($eventsFile));
    }
}
