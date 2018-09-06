<?php

	/*
	This file is part of xmplay-skin-scaler by Thomas Radeke.
	It's using the "imagick" PHP extension to resize
	XMPlay skin files in the exact format needed.
	To use this backend, install "php-imagick".
	*/

	foreach($images as $image){
		// read image
		list($width, $height) = getimagesize($image);
		$pathinfo = pathinfo($image);
		$filename = $pathinfo["filename"];
		$extension = $pathinfo["extension"];
		
		if(!preg_match("/^mask/i", $filename)){
			$im = new Imagick();
			$im->readImage($image);
			$has_alpha = $im->getImageAlphaChannel();
			// possible replacement: "identify -format '%[channels]' $filename", gives "srgb" or "srgba"
			
			$im->setImageType(Imagick::IMGTYPE_TRUECOLOR); // "-type type"
			$im->setImageDepth(32); // "-depth value"
			$im->setImageFormat("png32"); // use "PNG32:" prefix for filename
			
			// only process transparency if source image doesn't already have an alpha channel
			if(!$has_alpha){
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
			}
			
			$size = $im->getImageGeometry();
			$w = ceil($size["width"]*$scale);
			$h = ceil($size["height"]*$scale);
			
			// determine filter constant
			
			$blur = 1;
			switch($filter){
				case "point": $filterconstant = imagick::FILTER_POINT; break;
				case "triangle": $filterconstant = imagick::FILTER_TRIANGLE; break;
				case "hermite": $filterconstant = imagick::FILTER_HERMITE; $blur = 1.2; break;
			}
			
			// do the actual resize
			$im->resizeImage($w, $h, $filterconstant, $blur);
			
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
?>