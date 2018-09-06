<?php
	
	/*
	This file is part of xmplay-skin-scaler by Thomas Radeke.
	It contains a few PHP functions that map to ImageMagick's
	commandline "convert" utility.
	*/
	
	//-------------------------------------------------------------------------
	
	// gather some information about a file
	function test($image){
		$result = array();
		
		$info = pathinfo($image);
		$result["filename"] = $info["filename"];
		$extension = strtolower($info["extension"]);
		$result["extension"] = $extension;
		if($extension == "png"){
			$result["has_alpha"] = im_hasAlpha($image);
		}
		
		return $result;
	}
	
	//-------------------------------------------------------------------------

	// get the RGB color of an image at the top left corner
	function im_getTopLeftPixelColor($image, $as_hex = true){
		$result = false;
		$command = 'convert "'.$image.'"[1x1+0+0] -format "%[fx:int(255*r)],%[fx:int(255*g)],%[fx:int(255*b)]" info:';
		$return = exec($command);
		$result = explode(",", $return);
		if($as_hex){
			$result = sprintf("%02X%02X%02X", $result[0], $result[1], $result[2]);
		}
		return $result;
	}
	
	//-------------------------------------------------------------------------
	
	// check if an image has an alpha channel
	function im_hasAlpha($image){
		$result = false;
		$command = 'convert "'.$image.'" -ping -format "%A" info:';
		$return = exec($command);
		if($return == "True"){
			$result = true;
		}
		return $result;
	}
	
	//-------------------------------------------------------------------------
	
	// get dimensions of an image
	function im_getImageSize($image){
		$result = "0x0";
		$command = 'convert "'.$image.'" -ping -format "%G" info:';
		$return = exec($command);
		$parts = explode("x", $return);
		$result = array("width" => $parts[0], "height" => $parts[1]);
		return $result;
	}
	
	//-------------------------------------------------------------------------
	
	// get type of an image, e.g. "Palette", "TrueColor" etc.
	function im_getType($image){
		$result = 0;
		$command = 'convert "'.$image.'" -ping -format "%[type]" info:';
		$result = exec($command);
		return $result;
	}
	
	//-------------------------------------------------------------------------
	
	// resize an indexed image and keep the exact palette (needed for masks)
	function im_resizePaletteImage($scale, $infile, $outfile){
		// interpret float scale as percentage
		$percent = $scale*100;
		$command = 'convert -alpha off -define png:preserve-colormap=true -define filter:filter=Point -resize '.$percent.'% -compress none -type Palette -define bmp:subtype=RGB555 "'.$infile.'" "bmp:'.$outfile.'"';
		exec($command);
	}
	
	//-------------------------------------------------------------------------
	
	// general image resize function with or without alpha channel and some filtering options
	function im_resizeImage($scale, $infile, $outfile, $alpha=false, $filter="Triangle", $blur=1.5){
		// interpret float scale as percentage
		$percent = $scale*100;
		
		// convert $alpha bool to "on" or "off"
		$alpha_str = "off";
		$bits = "24";
		if($alpha){
			$alpha_str = "on";
			$bits = "32";
		}
		$command = 'convert -alpha '.$alpha_str.' -define filter:filter='.$filter.' -define filter:blur='.$blur.' -resize '.$percent.'% "'.$infile.'" "png'.$bits.':'.$outfile.'"';
		exec($command);
	}
	
	
	//-------------------------------------------------------------------------
	
	// make a specific color transparent, then resize the image
	function im_resize1BitAlphaImage($scale, $infile, $outfile, $transparency="00ff00", $filter="Triangle", $blur=1.5){
		//print_r($transparency);
		// interpret float scale as percentage
		$percent = $scale*100;
		$command = 'convert -alpha on -fill "rgba(0, 0, 0, 0)" -transparent "#'.$transparency.'" -define filter:filter='.$filter.' -define filter:blur='.$blur.' -resize '.$percent.'% "'.$infile.'" "png32:'.$outfile.'"';
		exec($command);
	}
	
	//-------------------------------------------------------------------------

?>