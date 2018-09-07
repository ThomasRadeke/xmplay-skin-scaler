<?php

	/*
	XMPlay Skin Scaler by Thomas Radeke
	This script must be run in the command line.
	Required packages: php7.0 php-imagick
	On Linux, you might have to install php-zip, too.
	*/

	require_once("functions.inc.php");

	$title = "XMPlay Skin Scaler $version by Thomas Radeke";
	$self = $_SERVER["SCRIPT_FILENAME"];
	$help ="Usage: php $self -i input -s scale [-f filter] [-b blur]
  -i: can be either an .xmpskin file or a directory with unpacked skin files.
      Also works with wildcards, e.g. \"*.xmpskin\" (use quotes!)
  -s: any (float) number greater than 0.
  -f (optional): one of the following: point|triangle|hermite. (default: point)
  -b (optional): amount of pixel blurring for filters (not point). (default: 1.5)
  Please refer to the ImageMagick reference to learn more about the filter characteristics:
    http://www.imagemagick.org/Usage/filter/#interpolated
  Example: php $self -i \"iXMPlay.xmpskin\" -s 2.0 -f triangle -b 1.2\n";
  
	if($argc == 1){
		echo("$title\n$help");
	} else {
		// check arguments
		echo($title."\n");
		
		// init empty final values
		$inputs = array();
		$scale = "";
		$filter = "point";
		$blur = 1.5;
		$allowedfilters = array("point", "box", "triangle", "hermite");
		
		// get command line options
		$options = getopt("i:s:f:b:");
		$i = $options["i"];
		$s = $options["s"];
		if(isset($options["f"])){
			$f = $options["f"];
		} else {
			$f = $filter;
		}
		
		if(isset($options["b"])){
			$b = $options["b"];
		} else {
			$b = $blur;
		}
		
		// call the main conversion function from "functions.inc.php"
		xmplay_skin_scaler($i, $s, $f, $b);
	
	}

?>