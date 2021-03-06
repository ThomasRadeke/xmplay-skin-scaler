<?php
	/*
	XMPlay Skin Scaler 0.4.1 (2018-09-07) by Thomas Radeke
	This script is supposed to be run on a web server and provides
	a web form for uploading files to be converted.
	Required packages: php7.0, php-imagick (for php-imagick backend), imagemagick (for convert-cli backend)
	On Linux, you might have to install php-zip, too.
	*/

	require_once("HTMLfromTemplate.class.php");
	require_once("functions.inc.php");
	
	$title = "XMPlay Skin Scaler $version";
	$upload = HTMLfromTemplate::template("upload.tpl", array());
	$upload_dir = "uploads";
	$status = "Waiting for uploads.";
	
	if((count($_FILES) > 0) && isset($_POST["filter"]) && isset($_POST["scale"])){
		$new_files = array();
		
		// scale and filter are type-checked in the actual conversion routine
		$scale = $_POST["scale"];
		$filter = $_POST["filter"];
		$blur = $_POST["blur"];
	
		// processing mode
		$num_files = count($_FILES["upload"]["name"]);
		$allowed_types = array("application/octet-stream", "application/zip");
		
		for($i = 0; $i < $num_files; $i++){
			$file = $_FILES["upload"];
			if(is_uploaded_file($file["tmp_name"][$i])){
				if(in_array($file["type"][$i], $allowed_types)){
				
					// make upload_dir if it doesn't exist
					if(!is_dir($upload_dir)){
						if(!mkdir($upload_dir)){
							// error making upload dir
						}
					}
					// escape uploaded filenames
					$filename = sanitizeFilename($file["name"][$i]);
					$destination = $upload_dir."/".$filename;
					if(move_uploaded_file($file["tmp_name"][$i], $destination)){
						$new_files[] = $destination;
					} else {
						// error moving uploaded file to upload dir
					}
				} else {
					// uploaded file type is not "application/zip"
				}
			} else {
				// error while uploading
			}
		}
		
		// process all successfully uploaded files
		if(!empty($new_files)){
			$status = xmplay_skin_scaler($new_files, $scale, $filter, $blur);
		} else {
			// none of the files was successfully uploaded
			$status = "None of the files were successfully uploaded, sorry!";
		}
		
	}
	
	$files_links = array("(No files currently.)");
	if(is_dir($upload_dir)){
		
		// clean up files and directories that are older than 24 hours
		$cleanupfiles = glob(glob_escape($upload_dir."/*"));
		if(count($cleanupfiles) > 0){
			foreach($cleanupfiles as $cleanupfile){
				$difference = time() - filemtime($cleanupfile);
				if($difference > 86400){
					//echo("cleaning up '$cleanupfile' (age: ".($difference/86400)." days)\n");
					$recursive = "";
					if(is_dir($cleanupfile)){
						$recursive = " -R";
					}
					exec('rm'.$recursive.' "'.$cleanupfile.'"');
				}
			}
		}
		
		$files = glob(glob_escape($upload_dir."/*.xmpskin"));
		// list remaining files
		if(count($files) > 0){
			usort($files, create_function('$a,$b', 'return filemtime($a) - filemtime($b);'));
			$files = array_reverse($files);
			$files_links = array();
			foreach($files as $file){
				$files_links[] = '<a href="'.$file.'">'.basename($file).'</a> <span class="text">('.humanReadableByteCount(filesize($file)).', '.date("Y-m-d, H:i:s", filemtime($file)).')</span>';
			}
		}
	}
	$files_links = implode("<br>", $files_links);
	
	$elements = array(
		"title" => $title,
		"upload" => $upload,
		"status" => $status,
		"files_links" => $files_links,
		"version" => $version,
		"year" => date("Y")
	);
	$result = HTMLfromTemplate::template("page.tpl", $elements);
	echo($result);

?>