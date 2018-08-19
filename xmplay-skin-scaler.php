<?php

	/*
	XMPlay Skin Scaler 0.1 (2018-08-19) by Thomas Radeke
	This script must be run in the command line.
	Required packages: php7.0 php-imagick
	On Linux, you might have to install php-zip, too.
	*/

	$title = "XMPlay Skin Scaler 0.1 (2018-08-19) by Thomas Radeke";
	$self = $_SERVER["SCRIPT_FILENAME"];
	$help ="Usage: php $self -i input -s scale [-f filter]
  -i: can be either an .xmpskin file or a directory with skin files.
  -s: any (float) number greater than 0.
  -f (optional): one of the following: point|box|triangle|hermite. (default: point)
  Please refer to the ImageMagick reference to learn more about the filter characteristics:
    http://www.imagemagick.org/Usage/filter/#interpolated
  Example: php $self -i \"iXMPlay.xmpskin\" -s 2.0 -f triangle\n";
	
	// not enough arguments
	if($argc == 1){
		echo("$title\n$help");
	} else {
		// check arguments
		echo($title."\n");
		
		// init empty final values
		$input = "";
		$scale = "";
		$filter = "point";
		$allowedfilters = array("point", "box", "triangle", "hermite");
		
		// get command line options
		$options = getopt("i:s:f:");
		$i = $options["i"];
		$s = $options["s"];
		if(isset($options["f"])){
			$f = $options["f"];
		} else {
			$f = $filter;
		}
		
		// check input file
		if(!file_exists($i)){
			echo("Error: file or directory '$i' doesn't exist.\n");
		} else {
			$input = $i;
		}
		
		// check scale
		if(!is_numeric($s)){
			echo("Error: 'scale' must be a number (greater than 0).\n");
		} elseif($s <= 0) {
			echo("Error: 'scale' must be greater than 0.\n");
		} else {
			$scale = $s;
		}
		
		// check filter
		if(in_array($f, $allowedfilters)){
			$filter = $f;
		} else {
			echo("Notice: unrecognized filter '$f', defaulting to '$filter'.\n");
		}
		
		// if all values have been checked, continue
		if(!empty($input) && !empty($scale)){
		
			echo("Input: '$input'\n");
			$info = pathinfo($input);
			$target_dir = $info["filename"];
			
			// unpack .xmpskin files
			if(is_file($input)){
				echo("Unpacking...\n");
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
			$allfiles = glob($target_dir."/*");
			
			$images = preg_grep("/(\.bmp$|\.png$)/i", $allfiles);
			$otherfiles = preg_grep("/(\.fon$|\.ttf$|\.txt$|\.otf$)/i", $allfiles);
			
			// create target directory ------------------------------------------------------------------
			$newdir = $target_dir." [scaled $scale]";
			if(!is_dir($newdir)){
				echo("Creating target directory '$newdir'...\n");
				mkdir($newdir);
			}
			
			// copy other files -------------------------------------------------------------------------
			echo("Copying misc files to '$newdir'...\n");
			foreach($otherfiles as $otherfile){
				$newfile = $newdir."/".basename($otherfile);
				copy($otherfile, $newfile);
			}
			
			// change skinconfig.txt to reflect scalings
			$skinconfig_file = $newdir."/skinconfig.txt";
			echo("Modifying '$skinconfig_file'...\n");
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
			echo("Scaling and converting skin images using '$filter' filter...\n");
			foreach($images as $image){
				// read image
				list($width, $height) = getimagesize($image);
				$pathinfo = pathinfo($image);
				$filename = $pathinfo["filename"];
				$extension = $pathinfo["extension"];
				
				if(!preg_match("/^mask/i", $filename)){
					$im = new Imagick();
					$im->readImage($image);
					$im->setImageType(Imagick::IMGTYPE_TRUECOLOR);
					$im->setImageDepth(32);
					$im->setImageFormat("png32");
					$im->setImageAlphaChannel(imagick::ALPHACHANNEL_OPAQUE);
					
					// if we've found a "color_seethru" value while converting the config, use it here.
					// otherwise, sample the color from the top-left pixel of the current image, but only for panels (old skins).
					$color_transparency = "";
					if(!empty($color_seethru)){
						list($r, $g, $b) = sscanf($color_seethru, "%2s%2s%2s");
						$r = hexdec($r);
						$g = hexdec($g);
						$b = hexdec($b);
						$color_transparency = "$r,$g,$b";
						$im->transparentPaintImage('rgb('.$color_transparency.')', 0, 0, false);
					} else {
						// only apply color_seethru to panels
						if(preg_match("/^panel/i", $filename)){
							$pixel = $im->getImagePixelColor(0, 0);
							$tc = $pixel->getColor();
							$r = $tc["r"];
							$g = $tc["g"];
							$b = $tc["b"];
							$color_transparency = "$r,$g,$b";
							$im->transparentPaintImage('rgb('.$color_transparency.')', 0, 0, false);
						}
					}
					
					$size = $im->getImageGeometry();
					$w = ceil($size["width"]*$scale);
					$h = ceil($size["height"]*$scale);
					
					// determine filter constant
					switch($filter){
						case "point": $filterconstant = imagick::FILTER_POINT; break;
						case "box": $filterconstant = imagick::FILTER_BOX; break;
						case "triangle": $filterconstant = imagick::FILTER_TRIANGLE; break;
						case "hermite": $filterconstant = imagick::FILTER_HERMITE; break;
					}
					
					// do the actual resize
					$im->resizeImage($w, $h, $filterconstant , 1);
					
					//$im->setImageCompressionQuality(90);
					$im->writeImage("png32:".$newdir."/".$filename.".png");
				} else {
					$im = new Imagick();
					$im->readImage($image);

					$size = $im->getImageGeometry();
					$w = ceil($size["width"]*$scale);
					$h = ceil($size["height"]*$scale);
					$im->sampleImage($w, $h); // resize without interpolation, preserve color map
					
					$im->setImageType(Imagick::IMGTYPE_PALETTE);
					$im->setImageFormat("bmp");
					$im->setCompression(imagick::COMPRESSION_NO);
					$im->writeImage($newdir."/".basename($image));
				}
			}
			
			//==============================================================================================
			
			// repack everything
			$newname = $newdir.".xmpskin";
			echo("Creating '$newname'...\n");
			
			$zip = new ZipArchive();
			$ret = $zip->open($newname, ZipArchive::CREATE | ZipArchive::OVERWRITE);
			if ($ret !== TRUE) {
				die("Error: zipping failed with code '$ret'.\n");
			} else {
				$options = array('remove_all_path' => TRUE);
				// apparently, the glob function needs brackets escaped in a directory name
				$zipdir = str_replace(array("[", "]"), array("\[", "\]"), $newdir);
				$zip->addGlob($zipdir."/*.*",0,$options);
				$zip->close();
			}
			
			echo("Done. Share and enjoy!\n");
		}
		
	}

?>
