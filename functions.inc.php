<?php

	/*
	XMPlay Skin Scaler by Thomas Radeke
	This file provides all base functions for the skin conversion.
	It must be included and used in other scripts, like the CLI version.
	Required packages: php7.0, php-imagick (for php-imagick backend), imagemagick (for convert-cli backend)
	On Linux, you might have to install php-zip, too.
	*/
	
	$version = "0.4.1 (2018-09-07)";
	$backend = "convert-cli";

	// some functions ==========================================================================================
  
	// function to check if any given input is a valid XMPlay skin, by looking for "skinconfig.txt"
	function isValidXmpskin($filename){
		$result = false;
		$check = "skinconfig.txt";
		// check if input is a directory or a file
		if(is_file($filename)){
			// try to list ZIP index, search for $check
			$f = new ZipArchive();
			$res = $f->open($filename);
			if($res === true){
				if($f->statName($check)){
					$result = true;
				}
				$f->close();
			}
		} elseif(is_dir($filename)){
			// try a directory
			if(file_exists($filename."/".$check)){
				$result = true;
			}
		}
		
		return $result;
	}
	
	//-----------------------------------------------------------------------------------------------------------------
	
	function status($msg){
		$result = "";
		if(php_sapi_name() == 'cli'){
			echo($msg);
		} else {
			$result = $msg;
		}
		return $result;
	}
	
	//-----------------------------------------------------------------------------------------------------------------
	
	// escape brackets for use in glob()
	function glob_escape($string){
		$string = str_replace(array("[", "]"), array("\[", "\]"), $string);
		return $string;
	}
	
	//-----------------------------------------------------------------------------------------------------------------
	
	// remove any reserved or disruptive characters from filenames
	// to prevent directory traversal or other filename-based attacks
	function sanitizeFilename($str){
		$str = ltrim($str, "./\\");
		$str = preg_replace('/[\r\n\t]+/', '_', $str);
		$str = preg_replace('/[\\\\\"\*\/\:\<\>\?\'\|]+/', '_', $str);
		$str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8', false);
		return $str;
	}
	
	//-----------------------------------------------------------------------------------------------------------------
	
	// make human-readable file sizes instead of just bytes
	function humanReadableByteCount($bytes){
		$result = "";
		$unit = 1024;
		if($bytes < $unit) {
			$result = $bytes." B";
		} else {
			$exp = floor(log($bytes) / log($unit));
			$pre = substr("KMGTPE", $exp-1, 1);
			$result = sprintf("%.1f %sB", ($bytes / pow($unit, $exp)), $pre);
		}
		
		return $result;
		
	}
	
	// conversion process ==========================================================================================
	
	function xmplay_skin_scaler($input_str, $scale_str, $filter_str = "point", $blur_str=1.5){
	
		global $backend;
	
		$result = "";
		$filter = "point";
		$blur = 1.5;
		
		// init empty final values
		$allowedfilters = array("point", "triangle", "hermite");
		$inputs = array();
		
		if(is_string($input_str)){
			// check for wildcards
			if(is_numeric(strpos($input_str, "*"))){
				$result .= status("Wildcard conversion. Looking for candidates...\n");
				$wildcard = glob(glob_escape($input_str));
				if(count($wildcard) > 0){
					foreach($wildcard as $match){
						if(isValidXmpskin($match)){
							$result .= status("Found valid skin in '$match'\n");
							$inputs[] = $match;
						}
					}
				}
			} else {
				// check input file
				if(!file_exists($input_str)){
					$result .= status("Error: file or directory '$input_str' doesn't exist.\n");
				} elseif(!isValidXmpskin($input_str)){
					$result .= status("Error: search for 'skinconfig.txt' failed on '$input_str'.\nPlease note that this tool cannot unpack old XMPlay skins in DLL format.\n");
				} else	{
					$inputs[] = $input_str;
				}
			}
		} elseif(is_array($input_str)){
			// check input files
			foreach($input_str as $file){
				if(!file_exists($file)){
					$result .= status("Error: file or directory '$file' doesn't exist.\n");
				} elseif(!isValidXmpskin($file)){
					$result .= status("Error: search for 'skinconfig.txt' failed on '$file'.\nPlease note that this tool cannot unpack old XMPlay skins in DLL format.\n");
				} else	{
					$inputs[] = $file;
				}
			}
		}
		
		// check scale
		if(!is_numeric($scale_str)){
			$result .= status("Error: 'scale' must be a number (greater than 0).\n");
		} elseif($scale_str <= 0) {
			$result .= status("Error: 'scale' must be greater than 0.\n");
		} else {
			$scale = $scale_str;
		}
		
		// check filter
		if(in_array($filter_str, $allowedfilters)){
			$filter = $filter_str;
		} else {
			$result .= status("Notice: unrecognized filter '$filter_str', defaulting to '$filter'.\n");
		}
		
		// check blur
		if(is_numeric($blur_str) && ($blur_str >= 0)){
			$blur = $blur_str;
		} else {
			$result .= status("Notice: invalid blur value '$blur_str', defaulting to '$blur'.\n");
		}
		
		// if all values have been checked, continue
		if((count($inputs) > 0) && !empty($scale)){
		
			$cwd = getcwd();
			// go through all inputs (for wildcards)
			foreach($inputs as $input){
			
				$result .= status("Input: '$input'\n");
				$info = pathinfo($input);
				$target_dir = $info["filename"];
				// change into directory of target
				$subdir = $info["dirname"];
				$result .= status("Entering directory: '$subdir'...\n");
				chdir($subdir);
				$input = $info["basename"];
				
				// unpack .xmpskin files
				if(is_file($input)){
					$result .= status("Unpacking...\n");
					$zip = new ZipArchive();
					if($zip->open($input)){
						if(!is_dir($target_dir)){
							if(!mkdir($target_dir)){
								die("Error: cannot create directory '$target_dir'. Check permissions and available disk space.\n");
							}
						}
						if(!$zip->extractTo($target_dir)){
							die("Error: unpacking to '$target_dir' failed. Check permissions and available disk space.\n");
						} else {
							$zip->close();
						}
					} else {
						die("Error unpacking the file. Please make sure it's the newer ZIP-based .xmpskin format.");
					}
				}
				
				// at this point we can assume that $target_dir exists and contains the unpacked skin.
				// do the conversion
				
				//==============================================================================================
				
				// find all neccessary files ----------------------------------------------------------------
				$allfiles = glob(glob_escape($target_dir)."/*");
				
				$images = preg_grep("/(\.bmp$|\.png$)/i", $allfiles);
				$otherfiles = preg_grep("/(\.fon$|\.ttf$|\.txt$|\.otf$)/i", $allfiles);
				
				// create target directory ------------------------------------------------------------------
				// remove fractional part of $scale if it's ".0"
				if(floatval($scale) === floatval(intval($scale))){
					$scale = intval($scale);
				}
				$blur_str = "";
				if($filter !== "point"){
					$blur_str = "@".$blur."px";
				}
				$newdir = $target_dir." [scaled $scale, {$filter}{$blur_str}]";
				if(!is_dir($newdir)){
					$result .= status("Creating target directory '$newdir'...\n");
					mkdir($newdir);
				}
				
				// copy other files -------------------------------------------------------------------------
				$result .= status("Copying misc files to '$newdir'...\n");
				$fon_warning = false;
				foreach($otherfiles as $otherfile){
					$newfile = $newdir."/".basename($otherfile);
					copy($otherfile, $newfile);
					$info = pathinfo($otherfile);
					if($info["extension"] == 'fon'){
						$fon_warning = true;
					}
				}
				if($fon_warning){
					$result .= status("WARNING: This skin uses .FON fonts, which cannot be scaled automatically. You have to use FontForge to do it manually - sorry.\n");
				}
				
				// change skinconfig.txt to reflect scalings
				$skinconfig_file = $newdir."/skinconfig.txt";
				$result .= status("Modifying '$skinconfig_file'...\n");
				$scale_fields = array(
					"pos_left",
					"pos_leftdown",
					"pos_right",
					"pos_rightdown",
					"pos_litleft",
					"pos_litinfo",
					"pos_scrollinfo",
					"pos_infoframe",
					"pos_listnum",
					"pos_listoff",
					"font_titlesize",
					"font_titleminisize",
					"font_timesize",
					"font_timeminisize",
					"font_mainsize",
					"font_helpsize",
					"font_listsize",
					"font_listnumsize",
					"font_infosize",
					"font_modsize",
				);
				
				$skinconfig = file_get_contents($skinconfig_file);
				
				// while we're at it, watch for a transparency color
				$color_seethru = "";
				
				$newconfig = "";
				$lines = explode("\n", $skinconfig);
				
				// read through config line by line
				foreach($lines as $line){
					
					$line = str_replace("\t", " ", $line);
					
					$found = false;
					// search for a replaceable field in each line
					foreach($scale_fields as $scale_field){
						if(is_numeric(strpos($line, $scale_field))){
							$found = true;
							
							// split off comments. only $parts[0] can contain something useful.
							$parts = explode(";", $line);
							
							// check whether the whole line was commented out
							if(!empty($parts[0])){
								// run match against numbers on remaining text
								$key_value_pair = explode("=", trim($parts[0]));
								
								if(count($key_value_pair) > 1){
									$key = trim($key_value_pair[0]);
									$value = trim($key_value_pair[1]);
									
									// check if $value has multiple numbers
									if(is_numeric(strpos($value, " "))){
										$numbers = explode(" ", $value);
										
										$scaledvalue = array();
										foreach($numbers as $number){
											// multiply each number by $scale
											// always round down
											$scaledvalue[] = ceil(intval(trim($number))*$scale);
										}
									} else {
										$scaledvalue = array(ceil(intval($value)*$scale));
									}
									
									$comment = " ;[original was '".$value."']";
									
									$newconfig .= $key." = ".implode(" ", $scaledvalue).$comment."\n";
									
								} else {
									$newconfig .= " ;scaler error converting line: \n;$line";
								}
							}
							
						}
					}
					if(!$found){
						$newconfig .= $line."\n";
					}
					
					if(is_numeric(strpos($line, "color_seethru"))){
						if(preg_match("/[0123456789abcdef]{6}/i", $line, $matches)){
							$color_seethru = $matches[0];
						}
					}
				}
				$handle = fopen($skinconfig_file, "w");
				fwrite($handle, ";[Skin was automatically scaled by $scale]\n");
				fwrite($handle, $newconfig);
				fclose($handle);
				
				// convert images --------------------------------------------------------------------
				$blur_str = "";
				if($filter !== "point"){
					$blur_str = " with $blur pixels blurring";
				}
				$result .= status("Scaling and converting skin images using '$filter' filter{$blur_str}...\n");
				
				// include whichever backend was defined in xmplay-scaler-web.php
				include("$backend.inc.php");
				
				//==============================================================================================
				
				// repack everything
				$newname = $newdir.".xmpskin";
				$result .= status("Creating '$newname'...\n");
				
				$zip = new ZipArchive();
				$ret = $zip->open($newname, ZipArchive::CREATE | ZipArchive::OVERWRITE);
				if ($ret !== TRUE) {
					die("Error: zipping failed with code '$ret'.\n");
				} else {
					$options = array('remove_all_path' => TRUE);
					// apparently, the glob function needs brackets escaped in a directory name
					$zipdir = glob_escape($newdir);
					$zip->addGlob($zipdir."/*.*",0,$options);
					$zip->close();
				}
				// get back into original directory
				chdir($cwd);
			} // foreach $inputs as $input
			
			$result .= status("All done. Share and enjoy!\n");
		}
		return $result;
		
	}

?>