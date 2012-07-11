<?php

namespace Bundles\Resource;
use Exception;
use e;

class Scan_Exception extends Exception {}

class Scanner {

	public function __construct($input) {
		$this->input = $input;
	}

	public function scan() {

		try {
			array_walk_recursive($this->input, array($this, 'detectXSS'));	
		}
		catch(Scan_Exception $e) {
			if($e->getCode() > 0)
				throw $e;
			else Trace_Exception($e);
		}

	}

	/**
	 * XSS Detections Script
	 * @author Symphony CMS
	 */
	public function detectXSS($string) {
		$contains_xss = FALSE;

		if(!is_string($string)) {
			if(is_numeric($string))
				$string = (string) $string;

			else throw new Scan_Exception('XSS Scanner: Passed parameter is not a string.', 0);
		}

		// Keep a copy of the original string before cleaning up
		$orig = $string;

		// URL decode
		$string = urldecode($string);

		// Convert Hexadecimals
		$string = preg_replace('!(&#|\\\)[xX]([0-9a-fA-F]+);?!e','chr(hexdec("$2"))', $string);

		// Clean up entities
		$string = preg_replace('!(&#0+[0-9]+)!','$1;',$string);

		// Decode entities
		$string = html_entity_decode($string, ENT_NOQUOTES, 'UTF-8');

		// Strip whitespace characters
		$string = preg_replace('!\s!','',$string);

		// Set the patterns we'll test against
		$patterns = array(
			// Match any attribute starting with "on" or xmlns
			'#(<[^>]+[\x00-\x20\"\'\/])(on|xmlns)[^>]*>?#iUu',

			// Match javascript:, livescript:, vbscript: and mocha: protocols
			'!((java|live|vb)script|mocha):(\w)*!iUu',
			'#-moz-binding[\x00-\x20]*:#u',

			// Match style attributes
			'#(<[^>]+[\x00-\x20\"\'\/])style=[^>]*>?#iUu',

			// Match unneeded tags
			'#</*(applet|meta|xml|blink|link|style|script|embed|object|iframe|frame|frameset|ilayer|layer|bgsound|title|base)[^>]*>?#i'
		);

		foreach($patterns as $pattern) {
			// Test both the original string and clean string
			if(preg_match($pattern, $string) || preg_match($pattern, $orig))
				$contains_xss = true;

			if($contains_xss === true) 
				throw new Scan_Exception('XSS Scanner: Detected a XSS vunerability @ '.date("Y-m-d H:i:s.").'.', 1);
		}

		return false;
	}

}