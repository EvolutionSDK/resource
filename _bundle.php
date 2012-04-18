<?php

namespace Bundles\Resource;
use Bundles\SQL\SQLBundle;
use Exception;
use StdClass;
use e;

class Bundle extends SQLBundle {

	# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
	#  _____                   _     _   _                 _ _ _              #
	# |_   _|                 | |   | | | |               | | (_)             #
	#   | |  _ __  _ __  _   _| |_  | |_| | __ _ _ __   __| | |_ _ __   __ _  #
	#   | | | '_ \| '_ \| | | | __| |  _  |/ _` | '_ \ / _` | | | '_ \ / _` | #
	#  _| |_| | | | |_) | |_| | |_  | | | | (_| | | | | (_| | | | | | | (_| | #
	#  \___/|_| |_| .__/ \__,_|\__| \_| |_/\__,_|_| |_|\__,_|_|_|_| |_|\__, | #
	#             | |                                                   __/ | #
	#             |_|                                                  |___/  #
	# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

	private $sources;
	private $scan = true;
	private $scanned = false;

	/**
	 * File upload errors based on key
	 * @author Kelly Becker
	 */
	public $file_upload_errors = array(
		0 => 'There is no error, the file uploaded with success.',
		1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
		2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
		3 => 'The uploaded file was only partially uploaded.',
		4 => 'No file was uploaded.',
		6 => 'Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.',
		7 => 'Failed to write file to disk. Introduced in PHP 5.1.0.',
		8 => 'A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help.'
	);

	/**
	 * Retrieves the input arrays and sets them
	 * to the source object
	 * @author Kelly Becker
	 */
	public function __initBundle() {
		$input = file_get_contents('php://input');

		$sources = new StdClass;
		$sources->stream 	= $input;
		$sources->post 		= $_POST;
		$sources->get 		= $_GET;
		$sources->cookie	= $_COOKIE;
		$sources->files		= $this->files();
		$sources->all		= e\array_merge_recursive_simple($_REQUEST, array('files'=>$this->files()));

		$this->sources = $sources;
	}

	/**
	 * Returns a specific source from the list of sources
	 * @author Kelly Becker
	 */
	public function __get($source) {
		if($source === 'noscan') {
			$this->scan = false;
			return $this;
		}

		else if($this->scan && !$this->scanned) {
			$scanner = new Scanner($this->sources->all);
			$scanner->scan();
			$this->scanned = true;
		}

		$this->scan = true;
		return $this->sources->$source;
	}

	/**
	 * Restructures the files array
	 * @author Kelly Becker
	 */
	private function files($lfiles = false, $top = true) {
		$files = array();
		foreach((!$lfiles ? $_FILES : $lfiles) as $name=>$file){
			if($top) $sub_name = $file['name'];
			else $sub_name = $name;
			
			if(is_array($sub_name)){
				foreach(array_keys($sub_name) as $key){
					$files[$name][$key] = array(
						'name'     => $file['name'][$key],
						'type'     => $file['type'][$key],
						'tmp_name' => $file['tmp_name'][$key],
						'error'    => $file['error'][$key],
						'size'     => $file['size'][$key],
						);
					$files[$name] = $this->files($files[$name], false);
				}
			}
			else $files[$name] = $file;
		}
		return $files;
	}

	# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
	# ______                    _                 _     ________ _           _              ______ _ _            #
	# |  _  \                  | |               | |   / /|  _  (_)         | |             |  ___(_) |           #
	# | | | |_____      ___ __ | | ___   __ _  __| |  / / | | | |_ ___ _ __ | | __ _ _   _  | |_   _| | ___ ___   #
	# | | | / _ \ \ /\ / / '_ \| |/ _ \ / _` |/ _` | / /  | | | | / __| '_ \| |/ _` | | | | |  _| | | |/ _ | __|  #
	# | |/ / (_) \ V  V /| | | | | (_) | (_| | (_| |/ /   | |/ /| \__ \ |_) | | (_| | |_| | | |   | | |  __|__ \  #
	# |___/ \___/ \_/\_/ |_| |_|_|\___/ \__,_|\__,_/_/    |___/ |_|___/ .__/|_|\__,_|\__, | \_|   |_|_|\___|___/  #
	#                                                                 | |             __/ |                       #
	#                                                                 |_|            |___/                        #
	# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

	/**
	 * Get Files With Tag
	 * @author Nate Ferrero
	 * @author Kelly Becker
	 */
	public function getWithTag($map = false, $image = false) {
		if(!$map) throw new Exception("No map specified to get all photos with tag.");

		$file = $this->getFiles()
			->_->taxonomy->hasTag($map);
		
		if($image) $file = $file->condition('`type` LIKE', 'image/%');
		return $file;
	}

	/**
	 * Handle File Downloads and Photos via URL
	 * @author Nate Ferrero
	 * @author Kelly Becler
	 */
	public function route($path) {
		$bundle = array_shift($path);
		$model = array_shift($path);
		$id = array_shift($path);

		$map = "$bundle.$model:$id";

		foreach($path as $seg) {
			$var = substr($seg, 0, 1);
			$check = substr($seg, 1, 1);
			$val = substr($seg, 2);

			if($check !== ':' && !isset($hash))
				$hash = $seg;

			if($check !== ':') continue;

			$$var = $val;
		}

		if($x === 'auto')
			unset($x);

		if($y === 'auto')
			unset($y);

		if(!isset($x) && !isset($y))
			$x = 240;

		$this->loadFile($map, isset($hash) ? $hash : null, false, isset($x) ? $x : null, isset($y) ? $y : null);
	}

	/**
	 * Handle File Retrieval and Display
	 * @author Nate Ferrero
	 * @author Kelly Becler
	 */
	public function loadFile($map, $hash = null, $ret = false, $x = null, $y = null) {
		$file = $this->getFiles()->_->taxonomy
		->hasTag($map);
		
		if(empty($hash)) $file = $file->condition('`type` LIKE', 'image/%');
		else $file = $file->condition('hash', $hash);

		$file = $file->first();

		if($ret) return $file;

		$placehold = false;
		if(!is_object($file) && empty($hash))
			$placehold = true;
		if(!is_file($file->filename) && strpos($file->type, 'image/') === 0)
			$placehold = true;

		if($placehold) {
			if(is_null($x) && is_null($y)) {
				$x = 240;
				$y = 240;
			}

			else {
				$phash = "placehold.it";
				$headers = getallheaders();

				if(isset($headers['If-None-Match']) && $headers['If-None-Match'] === $phash) {
					header("HTTP/1.1 304 Not Modified");
					exit;
				}

				header("ETag: {$phash}");
				header("HTTP/1.1 307 Temporary Redirect");

				if(is_null($x))
					$x = $y;
				if(is_null($y))
					$y = $x;

				header("Location: http://placehold.it/".$x."x".$y);
				exit;
			}
		}

		if(strpos($file->type, 'image/') === 0) {
			
			/**
			 * Add HTTP Caching
			 * @author Nate Ferrero
			 * @author Kelly Becker
			 */
			$headers = getallheaders();
			if(isset($headers['If-None-Match']) && $headers['If-None-Match'] === $file->hash) {
				header("HTTP/1.1 304 Not Modified");
				exit;	
			}

			header("ETag: {$file->hash}");

			$this->renderPhoto($file->filename, false, $x, $y);
		}
			
		else {
			header("Content-disposition: attachment; filename=".$file->origname);
			header("Content-Length: ".filesize($file->filename));
			header("Content-type: ".$file->type);
			header("Pragma: no-cache");
			readfile($file->filename);
		}

		e\disable_trace();
		e\complete();
	}

	/**
	 * Render a photo if needed
	 * @author Nate Ferrero
	 * @author Kelly Becler
	 */
	public function renderPhoto($file, $type = false, $x = null, $y = null) {
		$photo = new Manipulator($file);

		if($x > $y)
			$photo->resize($x, 'max');
		else
			$photo->resize($y, 'min');
		
		if($x !== null && $y !== null)
			$photo->crop($x, $y > 0 ? $y : $x);

		if($type === 'b64') return $photo->base64();

		$photo->show();
	}
	
}