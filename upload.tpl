<form action="xmplay-skin-scaler-web.php" method="POST" enctype="multipart/form-data">
	<div class="field">
		<input type="file" multiple id="upload" name="upload[]" />
		<label for="upload" id="upload_label">Select one or more .xmpskin or .zip files for uploading.</label><br>
	</div>
	<div class="field">
		<input type="number" id="scale" name="scale" value="2.0" min="0.1" max="10" step="0.1" /><label for="scale" id="scale_label">Scale factor (fractions supported).</label>
	</div>
	<div class="field">
		<select id="filter" name="filter">
			<option name="point" value="point" selected>Point (no filtering, default)</option>
			<option name="triangle" value="triangle">Triangle (regular bilinear filtering)</option>
			<option name="hermite" value="hermite">Hermite (smoother gradients when enlarging)</option>
		</select>
		<span class="text">with</span>
		<input type="number" id="blur" name="blur" value="1.5" min="0" max="10" step="0.1" />
		<label for="blur">pixels filter blurring (not for Point)</label><br>
		<label for="filter" id="filter_label">
			Use "Point" if you want to scale a skin by whole numbers or don't want any smoothing.<br/>
			"Triangle" smooths both while shrinking and enlarging. It's a regular bilinear filter.<br>
			"Hermite" is similar to "Triangle", but produces smoother gradients while enlarging.
		</label>
	</div>
	<div class="field">
		<input type="submit" id="submit" value="Upload & Convert"/>
	</div>
</form>