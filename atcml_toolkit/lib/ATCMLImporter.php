<?php

require_once('ATCMLParser.php');
require_once('functions.php');

/**
 * ATCML Importer
 *
 * @author Vincent Buzzano, ATC Future Medias SA
 * @version 1.0 - 2015-01-16
 */
class ATCMLImporter {
	private $error_callbacks = array();
	private $success_callbacks = array();

	public function __construct() {

	}

	public function addErrorCallback($callback) {
		array_push($this->error_callbacks, $callback);
	}

	public function addSuccessCallback($callback) {
		array_push($this->success_callbacks, $callback);
	}

    /**
     * Parse a directory and return all contents as array
     */
    public function parseDirectory($path, $autoclean = false) {
    	$contents = array();
		$parser = new ATCMLParser();

		$files = $this->scanDirForATCML($path);
        foreach($files as $filename) {
        	$file = $path . DIRECTORY_SEPARATOR . $filename;
        	$content = null;
        	try {
				$content = $parser->parseContent($file);
		        if (is_array($content)) {
					// check if delivery is competed
					if ($this->isDeliveryCompleted($content)) {

						// valid content - throw exception if not valid
		            	$this->validContent($content);

						// callback
				        $this->importSuccess($content, $file);
				        // autoclean
						if ($autoclean) $this->cleanAllFiles($content);
						// add content to array
		            	array_push($contents, $content);

					} else {
						$this->waitForCompletedDelivery($content);
					}

		        }

	        } catch (Exception $e) {
	            $this->importError($file, $e->getMessage(), $e);
	        }
        }
        return $contents;
    }

    /**
     * Parse Content
     * @param xml (SimpleXML)
     * @return array
     */
    public function parseContent($file) {
		$parser = new ATCMLParser();
        return $parser->parseContent($file);
    }

    public function validContent($content) {
    	if (!is_array($content))
    		throw new Exception("Imported ATCML must be an array type");

    	if (!array_key_exists('uid', $content) || strlen(trim($content['uid'])) == 0)
    		throw new Exception("ATCML contains no Content UID");

    	if (!array_key_exists('title', $content) || strlen(trim($content['title'])) == 0)
    		throw new Exception("ATCML contains no title");

		if (!array_key_exists('items', $content))
    		throw new Exception("ATCML contains no items");

		if ($this->isText($content)) {
			$text = $this->loadText($content);
			if (strlen($text) == 0)
				throw new Exception("ATCML contains no text for the content");
		}
    }

    /**
     * Check if all files are delivered
     * @array content
     * @return boolean
     */
    public function isDeliveryCompleted($content) {
    	$path = $content['_path'];

		$missingfiles = array();

		// check for attachments files
    	if (array_key_exists('attachments', $content)) {
	    	foreach($content['attachments'] as $a) {
	    		if (array_key_exists('filename', $a)) {
	    			$filename = $a['filename'];
	    			$file = $path . DIRECTORY_SEPARATOR . $filename;
	    			if (!file_exists($file))
	    				array_push($missingfiles, $filename);
	    		}
	    	}
    	}

		// check for items files
    	if (array_key_exists('items', $content)) {
	    	foreach($content['items'] as $a) {
	    		if (array_key_exists('filename', $a)) {
	    			$filename = $a['filename'];
	    			$file = $path . DIRECTORY_SEPARATOR . $filename;
	    			if (!file_exists($file))
	    				array_push($missingfiles, $filename);
	    		}
	    	}
    	}

    	return count($missingfiles) == 0;
    }

    /**
     * write a timestamp into a file
     * after 4 hours throw an error
     * @array content
     * @return boolean
     */
    public function waitForCompletedDelivery($content) {
    	$path = $content['_path'];
		$file = $content['_filename'];
		$tsfile = $path .DIRECTORY_SEPARATOR . $file . ".ts_";
		if (file_exists($tsfile)) {
			// get the timestamp in seconds
			$time = intval(file_get_contents($tsfile));
			$hours = ((int)(time()/360) - (int)($time / 360));
			if ($hours >= 4)
				throw new Exception("Time out delivery " . $content['_deliveryId'] . " !\n" .
					"After " . $hours . " hours of waiting, still missing files referenced in ATCML file '" . $content['_filename'] . "'.");
		} else {
			// the current time measured in the number of seconds
			$time_str = "" + time();
			file_put_contents($tsfile, $time_str);
		}
    }

	private function importSuccess($content, $file = null) {
		//   call error callbacks
		$args = array();
		$args['content'] = $content;
		$args['file']    = $file;
		foreach($this->success_callbacks as $c) {
			call_user_func_array($c, $args);
		}
	}

    private function importError($file, $message, $exception = null) {
		//   call error callbacks
		$argerrs = array();
		$argerrs['file'] = $file;
		$argerrs['message'] = $message;
		$argerrs['exception'] = $exception;
		foreach($this->error_callbacks as $c) {
			call_user_func_array($c, $argerrs);
		}

		// move delivery to errors folder
        $filename = trim(basename($file));
        $path = trim(pathinfo ($file, PATHINFO_DIRNAME));
		$errorDir = $path . DIRECTORY_SEPARATOR . "errors";
    	// create error folder
        if (!is_dir($errorDir)) {
            $oldmask = umask(0);
            mkdir($errorDir, 0777);
            umask($oldmask);
        }

    	// move file to folder error
		$parser = new ATCMLParser();
		$delivery_id = $parser->getDeliveryId($filename);

		if (is_null($delivery_id))
			rename ($file, $errorDir . DIRECTORY_SEPARATOR . $filename);
		else {
			$this->moveAllDeliveryFiles($delivery_id, $path, $errorDir);
		}

		// add file error
		$errormsg = $message;
		if (!is_null($exception)) {
			$errormsg = $errormsg . "\n" . $exception->getTraceAsString();
		}

		file_put_contents ( $errorDir . DIRECTORY_SEPARATOR . $filename . ".error",
			$errormsg);
    }

	/**
	 * Load Content as Text
	 *
	 * return a empty string if no items found
	 * return a empty string if first item is not text
	 *
	 * @param array content
	 * @return string text
	 */
    public function loadText($content) {
        if (!array_key_exists('items', $content)) return '';
        $items = $content['items'];
        if (count($items) == 0) return '';
        $item = $items[0];
        if (!$this->isText($item)) return '';

        if (array_key_exists('content', $item)) {
            return $item['content'];
        } else if (array_key_exists('filename', $item)) {
            $file = $content['_path'] . DIRECTORY_SEPARATOR . $item['filename'];
            if (file_exists($file))
	            return $this->file_get_contents_utf8($file);
	        else return '';
        } else return '';
    }

    /**
     * delete content files
     *
     * @param array content
     */
    public function cleanAllFiles($content) {
        $path = $content['_path'];

        $filename = $content['_filename'];
        if (isset($filename)) {
            $file = $path . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($file))
            	unlink($file);
        }
        $this->cleanAttachmentFiles($content);
        $this->cleanItemsFiles($content);

		// clean ts file
		$tsfile = $file . ".ts_";
		if (file_exists($tsfile))
			unlink($tsfile);
    }

    /**
     * delete items files
     * @param array content
     */
    public function cleanItemsFiles($content) {
        $path = $content['_path'];
        foreach($content['items'] as $a)
			$this->cleanFile($a, 'filename', $path);
    }

    /**
     * delete attachments files
     * @param array content
     */
    public function cleanAttachmentFiles($content) {
        $path = $content['_path'];
        foreach($content['attachments'] as $a)
			$this->cleanFile($a, 'filename', $path);
    }

	/**
	 * clean filename find in an array
	 *
	 * @param $array
	 * @param $filenameKey (key in the array where to find filename)
	 * @path $path to file directory
	 */
    private function cleanFile($array, $filenameKey = 'filename', $path = null) {
    	if (array_key_exists($filenameKey, $array)) {
            $filename = $array[$filenameKey];
            if (isset($filename)) {
                if (isset($path))
	                $file = $path . DIRECTORY_SEPARATOR . $filename;
	            else $file = $filename;
				if (file_exists($file))
	                unlink($file);
            }
    	}
    }

    /**
     * Scan a directory an get a list of ATCML files ordered by date
     * @param $path
     */
    private function scanDirForATCML($path) {
        $files = array();
        if ($handle = opendir($path)) {
            while (false !== ($filename = readdir($handle))) {
                if (endsWith($filename, '.xml')) {
                    $filetime = filemtime($path . DIRECTORY_SEPARATOR .$filename);

                    if (!array_key_exists($filetime, $files))
                        $files[$filetime] = array();
                    array_push($files[$filetime], $filename);
                }
            }
            closedir($handle);
        }

        // sort
        ksort($files);

        // find the last modification
        $reallyLastModified = end($files);


        $list = array();
        foreach($files as $arr)
            foreach($arr as $file)
                array_push($list, $file);

        return $list;
    }

	public function moveAllDeliveryFiles($delivery_id, $srcDir, $destDir) {
        $files = array();
        if ($handle = opendir($srcDir)) {
            while (false !== ($filename = readdir($handle))) {
                if (strContains($filename, $delivery_id))
                    array_push($files, $filename);
            }
            closedir($handle);
        }

		if (count($files) > 0) {
			foreach($files as $filename) {
				$src = $srcDir  . DIRECTORY_SEPARATOR . $filename;
				$dst = $destDir . DIRECTORY_SEPARATOR . $filename;
				rename($src, $dst);
			}
		}
	}


    public function isText($el) {
        if (!isset($el)) return false;
        if (is_array($el) && array_key_exists('mimetype', $el)) {
            return startsWith($el['mimetype'], 'text');
        }

        if(is_array($el) && array_key_exists('coreMedia', $el)) {
            return $el['coreMedia'] == 'text';
        }

        return false;
    }

    public function isImage($el) {
        if (!isset($el)) return false;
        if (is_array($el) && array_key_exists('mimetype', $el)) {
            return startsWith($el['mimetype'], 'image');
        }

        if(is_array($el) && array_key_exists('coreMedia', $el)) {
            return $el['coreMedia'] == 'image';
        }

        return false;
    }

	function file_get_contents_utf8($fn) {
	     $content = file_get_contents($fn);
	      return mb_convert_encoding($content, 'UTF-8',
	          mb_detect_encoding($content, 'UTF-8, ISO-8859-1', true));
	}

}
?>
