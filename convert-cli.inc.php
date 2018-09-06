<?php

	/*
	This file is part of xmplay-skin-scaler by Thomas Radeke.
	It's using ImageMagick "convert" commands to resize
	XMPlay skin files in the exact format needed.
	To use this backend, install "imagemagick".
	*/

	include("convert_cli_commands.inc.php");
	
	// TODO: make blur configurable
	$blur = 1.5;
	
	// go through all images and decide what to do individually
	foreach($images as $image){
		$imgprops = test($image);
		$target = $newdir."/".$imgprops["filename"];
		$ext = $imgprops["extension"];
		
		// handle bitmaps
		if($ext == "bmp"){
		
			// masks and non-masks need to be dealt with differently
			if(!preg_match("/^mask/i", basename($image))){
				// deal with regular truecolor images
				
				// if "color_seethru" is defined in skinconfig.txt, use that for transparency
				if($color_seethru){
					$transparentcolor = $color_seethru;
				} else {
					// otherwise, get the top-left image color and use that as transparency
					$transparentcolor = im_getTopLeftPixelColor($image);
				}
				
				$target .= ".png";
				im_resize1BitAlphaImage($scale, $image, $target, $transparentcolor, $filter, $blur);
				
			} else {
				// Resize 8-bit palette masks
				if(im_getType($image) == "Palette"){
					$target .= ".bmp";
					im_resizePaletteImage($scale, $image, $target);				
				}
			}
			
			
		} elseif($ext == "png"){
			// handle PNGs
			$target .= ".png";			
			im_resizeImage($scale, $image, $target, $imgprops["has_alpha"], $filter, $blur);
		}
	}

?>