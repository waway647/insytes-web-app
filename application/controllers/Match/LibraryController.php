<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LibraryController extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->library('session');
        // Allow JSON responses

	}
	public function index()
	{
		$data['title'] = 'Match Library';
		$data['main_content'] = 'match/library';
		$this->load->view('layouts/main', $data);
	}

	public function get_all_matches() {
		$data = [
            'season' => '2025/2026',
            'months' => [
                [
                    'monthName' => 'July',
                    'year' => 2026,
                    'matches' => [
                        [
                            'matchId' => 101,
                            'thumbnailUrl' => '<?php echo base_url("assets/images/thumbnails/match1.jpg"); ?>',
                            'status' => 'Ready',
                            'statusColor' => '#48ADF9', // Tailwind color class
                            'matchName' => 'vs. Ateneo',
                            'matchDate' => 'Jul 28'
                        ],
                        [
                            'matchId' => 102,
                            'thumbnailUrl' => '<?php echo base_url("assets/images/thumbnails/match2.jpg"); ?>',
                            'status' => 'Tagging in progress',
                            'statusColor' => '#B14D35',
                            'matchName' => 'vs. La Salle',
                            'matchDate' => 'Jul 15'
                        ]
                    ]
                ],
                [
                    'monthName' => 'August',
                    'year' => 2026,
                    'matches' => [
                        [
                            'matchId' => 103,
                            'thumbnailUrl' => '<?php echo base_url("assets/images/thumbnails/match3.jpg"); ?>',
                            'status' => 'Completed',
                            'statusColor' => '#209435',
                            'matchName' => 'vs. UP',
                            'matchDate' => 'Aug 05'
                        ],
                        [
                            'matchId' => 104,
                            'thumbnailUrl' => '<?php echo base_url("assets/images/thumbnails/match4.jpg"); ?>',
                            'status' => 'Waiting for video',
                            'statusColor' => '#B6BABD',
                            'matchName' => 'vs. FEU',
                            'matchDate' => 'Aug 12'
                        ]
                    ]
                ]
            ]
        ];
		
        // Set the content type header to application/json and output the JSON data
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
	}

    // Utility: return or initialize mock storage
    private function storage()
    {
        $storage = $this->session->userdata('library_storage');
        if (!$storage || !is_array($storage)) {
            // initial mock data
            $storage = [
                'season' => [
                    ['id' => 's1', 'name' => '2024/2025'],
                    ['id' => 's2', 'name' => '2025/2026']
                ],
                'competition' => [
                    ['id' => 'c1', 'name' => 'Premier League'],
                    ['id' => 'c2', 'name' => 'FA Cup']
                ],
                'venue' => [
                    ['id' => 'v1', 'name' => 'Old Trafford'],
                    ['id' => 'v2', 'name' => 'Anfield']
                ],
                'team' => [
                    ['id' => 't1', 'name' => 'My FC'],
                    ['id' => 't2', 'name' => 'Opponents United']
                ]
            ];
            $this->session->set_userdata('library_storage', $storage);
        }
        return $storage;
    }

    private function save_storage($storage)
    {
        $this->session->set_userdata('library_storage', $storage);
    }

    // GET /match/library_controller/get_items/{type}
    public function get_items($type = null)
    {
        $storage = $this->storage();

        // Allow query param ?type=...
        if (!$type && $this->input->get('type')) {
            $type = $this->input->get('type');
        }

        if (!$type || !isset($storage[$type])) {
            echo json_encode(['success' => false, 'message' => 'Invalid type', 'items' => []]);
            return;
        }

        echo json_encode(['success' => true, 'items' => $storage[$type]]);
    }

    // POST /match/library_controller/add_item
    public function add_item()
    {
        $payload = json_decode(trim(file_get_contents('php://input')), true);
        if (!$payload) $payload = $this->input->post();

        $type = isset($payload['type']) ? $payload['type'] : null;
        $name = isset($payload['name']) ? trim($payload['name']) : null;

        $storage = $this->storage();

        if (!$type || !$name || !isset($storage[$type])) {
            echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            return;
        }

        // produce an id
        $prefix = substr($type, 0, 1);
        $id = $prefix . uniqid();

        $item = ['id' => $id, 'name' => $name];
        $storage[$type][] = $item;
        $this->save_storage($storage);

        echo json_encode(['success' => true, 'item' => $item, 'message' => 'Added (mock)']);
    }

    // POST /match/library_controller/edit_item
    public function edit_item()
    {
        $payload = json_decode(trim(file_get_contents('php://input')), true);
        if (!$payload) $payload = $this->input->post();

        $type = isset($payload['type']) ? $payload['type'] : null;
        $id = isset($payload['id']) ? $payload['id'] : null;
        $name = isset($payload['name']) ? trim($payload['name']) : null;

        $storage = $this->storage();

        if (!$type || !$id || !$name || !isset($storage[$type])) {
            echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            return;
        }

        $updated = false;
        foreach ($storage[$type] as &$it) {
            if ($it['id'] === $id) {
                $it['name'] = $name;
                $updated = true;
                break;
            }
        }
        if ($updated) {
            $this->save_storage($storage);
            echo json_encode(['success' => true, 'message' => 'Updated (mock)', 'item' => ['id' => $id, 'name' => $name]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
        }
    }

    // POST /match/library_controller/delete_item
    public function delete_item()
    {
        $payload = json_decode(trim(file_get_contents('php://input')), true);
        if (!$payload) $payload = $this->input->post();

        $type = isset($payload['type']) ? $payload['type'] : null;
        $id = isset($payload['id']) ? $payload['id'] : null;

        $storage = $this->storage();

        if (!$type || !$id || !isset($storage[$type])) {
            echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            return;
        }

        $found = false;
        foreach ($storage[$type] as $idx => $it) {
            if ($it['id'] === $id) {
                array_splice($storage[$type], $idx, 1);
                $found = true;
                break;
            }
        }
        if ($found) {
            $this->save_storage($storage);
            echo json_encode(['success' => true, 'message' => 'Deleted (mock)']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
        }
    }
    public function load_modal()
    {
        $this->load->helper('url'); // if not already loaded
        $name = $this->input->get('name', true); // expected keys like 'add_new_season', 'edit_season', etc.
        if (!$name) {
            show_error('Missing modal name', 400);
            return;
        }

        // map valid modal keys to view paths
        $mapping = [
            'add_new_season'         => 'match/utils/modals/add_season',
            'edit_season'            => 'match/utils/modals/edit_season',
            'add_new_competition'    => 'match/utils/modals/add_competition',
            'edit_competition'       => 'match/utils/modals/edit_competition',
            'add_new_venue'          => 'match/utils/modals/add_venue',
            'edit_venue'             => 'match/utils/modals/edit_venue',
            'add_new_team'           => 'match/utils/modals/add_team',
            'edit_team'              => 'match/utils/modals/edit_team'
        ];

        if (!isset($mapping[$name])) {
            show_error('Modal not found', 404);
            return;
        }

        // render view as string and echo - do not run full layout
        $html = $this->load->view($mapping[$name], [], true);
        // optional: wrap with a container if modals do not include data-modal root
        echo $html;
    }

}
