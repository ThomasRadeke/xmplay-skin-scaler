<?php

	/*
	This file is part of xmplay-skin-scaler by Thomas Radeke.
	It's using ImageMagick "convert" commands to resize
	XMPlay skin files in the exact format needed.
	To use this backend, install "imagemagick".
	*/

	include("convert_cli_commands.inc.php");
	
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
				// for ALL skin images (except masks of course)
				$with_alpha = true;
				if($color_seethru){
					$transparentcolor = $color_seethru;
				} else {
					// if "color_seethru" is NOT defined, pick the transparent color from
					// the top-left pixel of "panel_" images only. All other images
					// (except masks) just get resized normally, without alpha.
					if(preg_match("/^panel/i", basename($image))){
						// otherwise, get the top-left image color and use that as transparency
						$transparentcolor = im_getTopLeftPixelColor($image);
					} else {
						$with_alpha = false;
					}
				}
				
				$target .= ".png";
				if($with_alpha){
					im_resize1BitAlphaImage($scale, $image, $target, $transparentcolor, $filter, $blur);
				} else {
					im_resizeImage($scale, $image, $target, false, $filter, $blur);
				}
				
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