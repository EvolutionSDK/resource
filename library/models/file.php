<?php

namespace Bundles\Resource\Models;
use Bundles\SQL\Model;
use Exception;
use e;

class File extends Model {
	
	/**
	 * Handle the file upload and save the file to the proper location
	 * @author: Kelly Lauren Summer Becker
	 */
	public function upload($file, $owner) {

		/**
		 * Create the Filename from the Owner map and a random hash
		 */
		$upload_dir = e::$resource->requireDir();
		$extension = '.'.pathinfo($file['name'], PATHINFO_EXTENSION);
		$filename = $upload_dir.'/'.str_replace(':','_',$owner->__map()).'_'.md5(time().rand(10,99)).$extension;

		/**
		 * Move the uploaded file & check for any file upload errors
		 */
		if(!is_writable(dirname($filename)))
			throw new Exception('Make upload directory writable: `dir=' . dirname($filename) . ';mkdir -p $dir;chmod 0777 $dir`');

		if(!move_uploaded_file($file['tmp_name'], $filename)) throw new Exception("Uploaded file `".$file['name']."` could not be uploaded.");
		if($file['error'] > 0) throw new Exception(e::$resource->file_upload_errors[$file['error']]);

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

		return $this;
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