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
	 * Require the directory to save uploaded files
	 * @author Kelly Becker
	 */
	public function requireDir() {
		return e::$environment->requireVar('resource.fileDir', '', "Resource Bundle: The absolute path to the upload directory for imports. (Recomended that this is outside the site directory)");
	}

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

		/**
		 * Require the upload directory to exist in the environments file
		 */
		$this->requireDir();
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
		/**
		 * Get the bundle, model, and id
		 */
		$bundle = array_shift($path);
		$model = array_shift($path);
		$id = array_shift($path);

		/**
		 * Turn it into a map
		 */
		$map = "$bundle.$model:$id";

		/**
		 * Get the image sizes/hash
		 */
		foreach($path as $seg) {
			$var = substr($seg, 0, 1);
			$check = substr($seg, 1, 1);
			$val = substr($seg, 2);

			if($check !== ':' && !isset($hash))
				$hash = $seg;

			if($check !== ':') continue;

			$$var = $val;
		}

		/**
		 * If x is set to auto ignore it
		 */
		if($x === 'auto')
			unset($x);

		/**
		 * If y is set to auto ignore it
		 */
		if($y === 'auto')
			unset($y);

		/**
		 * If neither x nor y are set, then make them 240
		 */
		if(!isset($x) && !isset($y))
			$x = 240;

		/**
		 * Load the images / download the files
		 */
		$this->loadFile($map, isset($hash) ? $hash : null, false, isset($x) ? $x : null, isset($y) ? $y : null);
	}

	/**
	 * Handle File Retrieval and Display
	 * @author Nate Ferrero
	 * @author Kelly Becler
	 */
	public function loadFile($map, $hash = null, $ret = false, $x = null, $y = null) {
		/**
		 * Get the images associated with the map passed
		 */
		$file = $this->getFiles()->_->taxonomy->hasTag($map);
		
		/**
		 * If there is no hash get the first image
		 */
		if(empty($hash)) $file = $file->condition('`type` LIKE', 'image/%');

		/**
		 * Otherwise get the file with hash specified
		 */
		else $file = $file->condition('hash', $hash);

		/**
		 * Return the first object
		 */
		$file = $file->first();

		/**
		 * If we just need to return then return the object
		 */
		if($ret) return $file;

		/**
		 * Ping external file storage bundle
		 */
		if(is_object($file) && strpos($file->filename, ':') !== FALSE) {
			$eventResponse = e::$events->loadFile($file->filename);

			foreach($eventResponse as $event) {
				if(empty($event)) continue;

				$filename = $event['filename'];
			}
		}

		/**
		 * Use local file storage
		 */
		if(!isset($filename)) {

			/**
			 * Do we need to show a placeholder
			 */
			$placehold = false;
			if(!is_object($file) && empty($hash))
				$placehold = true;
			if(!is_file($file->filename) && strpos($file->type, 'image/') === 0)
				$placehold = true;

			/**
			 * Show a place holder image
			 */
			if($placehold) {

				/**
				 * If no image dimensions are set defaults
				 */
				if(is_null($x) && is_null($y)) {
					$x = 240;
					$y = 240;
				}

				/**
				 * Only show the placeholder if the dimensions are set
				 */
				else {
					$phash = "placehold.it";
					$headers = getallheaders();

					/**
					 * Use HTTP cache headers to speed up display
					 */
					if(isset($headers['If-None-Match']) && $headers['If-None-Match'] === $phash) {
						header("HTTP/1.1 304 Not Modified");
						exit;
					}

					header("ETag: {$phash}");
					header("HTTP/1.1 307 Temporary Redirect");

					/**
					 * If there are null values make square
					 */
					if(is_null($x))
						$x = $y;
					if(is_null($y))
						$y = $x;

					/**
					 * Redirect to placehold.it
					 */
					header("Location: http://placehold.it/".$x."x".$y);
					exit;
				}

			}

		}

		/**
		 * If this is an image output here and now
		 */
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

			/**
			 * Render photo and output
			 */
			if(!isset($filename)) $filename = $file->filename;
			$this->renderPhoto($file->filename, false, $x, $y);
		}
		
		/**
		 * Start the file download
		 */
		else {
			if(!isset($filename)) $filename = $file->filename;
			header("Content-disposition: attachment; filename=".$file->origname);
			header("Content-Length: ".filesize($filename));
			header("Content-type: ".$file->type);
			header("Pragma: no-cache");
			readfile($filename);
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

		/**
		 * Load the image into the manipulator
		 */
		$photo = new Manipulator($file);

		/**
		 * Resize the photos
		 */
		if($x > $y)
			$photo->resize($x, 'max');
		else
			$photo->resize($y, 'min');
		
		/**
		 * Crop the photo if needed
		 */
		if($x !== null && $y !== null)
			$photo->crop($x, $y > 0 ? $y : $x);

		/**
		 * If a base64 is requested then return the image as b64 string
		 */
		if($type === 'b64') return $photo->base64();

		/**
		 * Else output the photo and die
		 */
		$photo->show();
	}
	
}