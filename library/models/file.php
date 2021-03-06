<?php

namespace Bundles\Resource\Models;
use Bundles\SQL\Model;
use Exception;
use e;

class File extends Model {

	/**
	 * Handle the file upload and save the file to the proper location
	 * @author: David Boskovic
	 */
	public function move($file, $owner, $ext, $type) {

		/**
		 * Create the Filename from the Owner map and a random hash
		 */
		$extension = '.'.$ext;
		$filename = str_replace(':','_',$owner->__map()).'_'.md5(time().rand(10,99)).$extension;

		/**
		 * Use an external file storage bundle
		 */
		$eventResponse = e::$events->uploadFile($file, $filename);

		/**
		 * Use built in local file storage
		 */
		if(empty($eventResponse)) {

			/**
			 * Get the upload location
			 */
			$upload_dir = e::$resource->requireDir();

			/**
			 * Append the file upload location
			 */
			$filename = $upload_dir.'/'.$filename;

			/**
			 * Is the upload directory writable
			 */
			if(!is_writable(dirname($filename)))
				throw new Exception('Make upload directory writable: `dir=' . dirname($filename) . ';mkdir -p $dir;chmod 0777 $dir`');

			/**
			 * Move the uploaded file & check for any file upload errors
			 */
			if(!rename($file, $filename)) throw new Exception("Uploaded file `".$file."` could not be moved.");
		}

		/**
		 * Get the response from the external file storage bundle
		 */
		else foreach($eventResponse as $event) {

			/**
			 * If the bundle did not handle the upload
			 */
			if(empty($event)) continue;

			/**
			 * Get the filename returned by the bundle
			 */
			if(!isset($_filename)) $_filename = $event['filename'];
			else $_filename .= ':'.$_filename;
		}

		/**
		 * If the event response created the filenames
		 */
		if(isset($_filename)) $filename = $_filename;

		/**
		 * Save the model with the file information
		 */
		$this->filename = $filename;
		$this->hash = md5($filename);
		$this->type = $type;
		$this->save();

		/**
		 * Tag the owner
		 */
		$this->_->taxonomy->addTag($owner);

		/**
		 * Return the model
		 */
		return $this;
	}

	/**
	 * Handle the file upload and save the file to the proper location
	 * @author: Kelly Lauren Summer Becker
	 */
	public function upload($file, $owner) {

		/**
		 * Create the Filename from the Owner map and a random hash
		 */
		$extension = '.'.pathinfo($file['name'], PATHINFO_EXTENSION);
		$filename = str_replace(':','_',$owner->__map()).'_'.md5(time().rand(10,99)).$extension;

		/**
		 * Use an external file storage bundle
		 */
		$eventResponse = e::$events->uploadFile($file['tmp_name'], $filename);

		/**
		 * Use built in local file storage
		 */
		if(empty($eventResponse)) {

			/**
			 * Get the upload location
			 */
			$upload_dir = e::$resource->requireDir();

			/**
			 * Append the file upload location
			 */
			$filename = $upload_dir.'/'.$filename;

			/**
			 * Is the upload directory writable
			 */
			if(!is_writable(dirname($filename)))
				throw new Exception('Make upload directory writable: `dir=' . dirname($filename) . ';mkdir -p $dir;chmod 0777 $dir`');

			/**
			 * Move the uploaded file & check for any file upload errors
			 */
			if(!move_uploaded_file($file['tmp_name'], $filename)) throw new Exception("Uploaded file `".$file['name']."` could not be uploaded.");
			if($file['error'] > 0) throw new Exception(e::$resource->file_upload_errors[$file['error']]);
		}

		/**
		 * Get the response from the external file storage bundle
		 */
		else foreach($eventResponse as $event) {

			/**
			 * If the bundle did not handle the upload
			 */
			if(empty($event)) continue;

			/**
			 * Get the filename returned by the bundle
			 */
			if(!isset($_filename)) $_filename = $event['filename'];
			else $_filename .= ':'.$_filename;
		}

		/**
		 * If the event response created the filenames
		 */
		if(isset($_filename)) $filename = $_filename;

		/**
		 * Save the model with the file information
		 */
		$this->filename = $filename;
		$this->origname = $file['name'];
		$this->hash = md5($filename);
		$this->type = $file['type'];
		$this->save();

		/**
		 * Tag the owner
		 */
		$this->_->taxonomy->addTag($owner);

		/**
		 * Return the model
		 */
		return $this;
	}

	public function delete() {

		/**
		 * Remove all tags
		 */
		$this->_->taxonomy->removeAllTags();

		/**
		 * Run a deletion event
		 */
		if(!is_file($file->filename) || strpos($file->filename, ':') !== FALSE) {
			$eventResponse = e::$events->deleteFile($file->filename);

			foreach($eventResponse as $event) {
				if(empty($event)) continue;

				$filename = $event['filename'];
			}
		}

		/**
		 * Delete the photo from the filesystem (if exists)
		 */
		unlink($photo->filename);

		/**
		 * Delete the photo from the db
		 */
		parent::delete();
	}

	public function url() {
		$tags = $this->_->taxonomy->list();
		dump($tags);
	}

	public function photo($x = null, $y = null, $type = false) {
		if(strpos($this->type, 'image/') !== 0)
			return "Not an image";

		if($type === 'b64') return e::$resource->renderPhoto($this->filename, $x, $y, $type);
		else return $this->url();
	}
 
}