<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MediaController extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->library('session');
		$this->load->model('Match_Model');
	}

	public function index($match_id = null)
    {
        // prefer explicit URI segment, otherwise fall back to query string ?match_id=...
        $match_id = $match_id ?: $this->input->get('match_id') ?: $this->session->userdata('current_tagging_match_id');

        // normalize empty string -> null
        if ($match_id === '') $match_id = null;

        $data['title'] = 'Media';
        $data['main_content'] = 'studio/media/media';

		// persist for subsequent page loads
		if ($match_id) {
			$this->session->set_userdata('current_tagging_match_id', $match_id);
		}

        // pass match id to view (may be null)
        $data['match_id'] = $match_id;

        $this->load->view('layouts/studio', $data);
    }

	public function upload_video($match_id = null){
		try {
			$user_id = $this->session->userdata('user_id');
			$match_id = $match_id ?: $this->input->get('match_id') ?: $this->session->userdata('current_tagging_match_id');

			date_default_timezone_set('Asia/Manila');
			$timestamp = date('Ymd_His');

			// 1️⃣ Validate upload
			if (!isset($_FILES['video_file']) || $_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
				throw new Exception('No video file uploaded', 400);
			}

			// 2️⃣ Increase timeout
			ini_set('max_execution_time', 300);
			set_time_limit(300);

			// 3️⃣ Prepare match folder
			$match_folder = FCPATH . 'assets/videos/matches/match_' . $match_id . '/';
			if (!is_dir($match_folder) && !mkdir($match_folder, 0755, true)) {
				throw new Exception("Failed to create match folder at {$match_folder}", 500);
			}
			if (!is_writable($match_folder)) {
				@chmod($match_folder, 0755);
				if (!is_writable($match_folder)) {
					throw new Exception("Match folder not writable: {$match_folder}", 500);
				}
			}

			// 4️⃣ Configure upload
			$config = [
				'upload_path'   => $match_folder,
				'allowed_types' => 'mp4|mov|avi|mkv',
				'max_size'      => 5242880, // 5 GB (in KB)
				'file_name'     => 'video_' . $timestamp,
			];
			$this->load->library('upload', $config);

			if (!$this->upload->do_upload('video_file')) {
				throw new Exception('File upload failed: ' . strip_tags($this->upload->display_errors()), 400);
			}

			$upload_data     = $this->upload->data();
			$video_full_path = $upload_data['full_path'];
			$video_url       = 'assets/videos/matches/match_' . $match_id . '/' . $upload_data['file_name'];

			// 5️⃣ ffprobe: get duration
			$ffprobe_path = trim(shell_exec('which ffprobe 2>/dev/null')) ?: 'ffprobe';
			$cmd_duration  = escapeshellcmd($ffprobe_path) . ' -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' . escapeshellarg($video_full_path) . ' 2>&1';
			exec($cmd_duration, $out_duration, $ret_duration);
			if ($ret_duration !== 0) {
				throw new Exception('ffprobe failed: ' . implode("\n", $out_duration), 500);
			}

			$duration = floatval(implode("\n", $out_duration));
			$time_position = ($duration > 0)
				? ($duration < 20 * 60 ? max(0.1, $duration - 0.5) : 20 * 60)
				: 0;

			// 6️⃣ ffmpeg: generate thumbnail
			$ffmpeg_path   = trim(shell_exec('which ffmpeg 2>/dev/null')) ?: 'ffmpeg';
			$thumbnail_name = 'thumbnail_' . $timestamp . '.jpg';
			$thumbnail_path = $match_folder . $thumbnail_name;
			$thumbnail_url  = 'assets/videos/matches/match_' . $match_id . '/' . $thumbnail_name;

			$cmd = escapeshellcmd($ffmpeg_path)
				. ' -ss ' . escapeshellarg($time_position)
				. ' -i ' . escapeshellarg($video_full_path)
				. ' -vf "scale=trunc(iw/2)*2:trunc(ih/2)*2,format=yuvj420p"'
				. ' -vframes 1 -q:v 2 -threads 1 -y '
				. escapeshellarg($thumbnail_path)
				. ' 2>&1';

			exec($cmd, $ffmpeg_output, $ffmpeg_return);
			if ($ffmpeg_return !== 0 || !file_exists($thumbnail_path)) {
				throw new Exception('Thumbnail generation failed: ' . implode("\n", $ffmpeg_output), 500);
			}

			// 7️⃣ Save to DB
			$data = [
				'match_id'        => $match_id,
				'video_url'       => $video_url,
				'video_thumbnail' => $thumbnail_url,
				'uploaded_by'     => $user_id,
			];
			$this->Match_Model->save_video_to_db($data);
			$this->Match_Model->update_match_status($match_id, 'Ready');

			// store to session
			$video_file_name_for_tagging = $upload_data['file_name'];
			
			$this->session->set_userdata('tagging_video_url', $video_url);
			$this->session->set_userdata('tagging_thumbnail_url', $thumbnail_url);
			$this->session->set_userdata('video_file_name_for_tagging', $video_file_name_for_tagging);

			// 8️⃣ Success response
			$response = [
				'success'          => true,
				'video_url'        => $video_url,
				'video_thumbnail'  => $thumbnail_url,
			];
			$status_code = 200;
		} catch (Exception $e) {
			$response = [
				'error'   => $e->getMessage(),
				'trace'   => (ENVIRONMENT === 'development') ? $e->getTraceAsString() : null,
			];
			$status_code = $e->getCode() ?: 500;
		}

		// Unified JSON output
		$this->output
			->set_status_header($status_code)
			->set_content_type('application/json')
			->set_output(json_encode($response));
	}

	public function get_match_my_team_players()
	{
		$match_id = $this->session->userdata('active_match_id');
		$team_id  = $this->session->userdata('my_team_id');

		if (empty($match_id) || empty($team_id)) {
			$response = [
				'success' => false,
				'error'   => 'Missing match_id or my_team_id in session.'
			];

			$this->output
				->set_status_header(400)
				->set_content_type('application/json')
				->set_output(json_encode($response));
			return;
		}

		$status_code = 200;
		try {
			$players = $this->Match_Model->get_my_team_players($match_id, $team_id);

			if ($players === false || $players === null) {
				$players = [];
			}

			$response = [
				'success' => true,
				'players' => $players
			];
		} catch (Exception $e) {
			$response = [
				'success' => false,
				'error'   => $e->getMessage(),
			];
			$status_code = $e->getCode() ?: 500;
		}

		$this->output
			->set_status_header($status_code)
			->set_content_type('application/json')
			->set_output(json_encode($response));
	}

	public function get_match_opponent_team_players()
	{
		$match_id = $this->session->userdata('active_match_id');
		$team_id  = $this->session->userdata('opponent_team_id');

		if (empty($match_id) || empty($team_id)) {
			$response = [
				'success' => false,
				'error'   => 'Missing match_id or my_team_id in session.'
			];

			$this->output
				->set_status_header(400)
				->set_content_type('application/json')
				->set_output(json_encode($response));
			return;
		}

		$status_code = 200;
		try {
			$players = $this->Match_Model->get_my_team_players($match_id, $team_id);

			if ($players === false || $players === null) {
				$players = [];
			}

			$response = [
				'success' => true,
				'players' => $players
			];
		} catch (Exception $e) {
			$response = [
				'success' => false,
				'error'   => $e->getMessage(),
			];
			$status_code = $e->getCode() ?: 500;
		}

		$this->output
			->set_status_header($status_code)
			->set_content_type('application/json')
			->set_output(json_encode($response));
	}
}
