<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<title>{title}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<link rel="stylesheet" href="style.css">
	</head>
	<body>
		<h1>XMPlay Skin Scaler {version}</h1>
		<p class="description">This tool can scale <a href="http://www.un4seen.com">XMPlay</a> skins to arbitrary sizes to accomodate larger screens or higher resolutions. It does this by unpacking .xmpskin files, resizing all individual skin files, modifying the skin configuration and packing everything up again.</p>
		<p>Grab the source code of this tool over at <a href="https://github.com/ThomasRadeke/xmplay-skin-scaler">GitHub</a>.</p>
		<div class="section upload">
			<h2>Upload</h2>
			<div class="uploadform">{upload}</div>
		</div>
		<div class="section status">
			<h2>Conversion status</h2>
			<pre>{status}</pre>
		</div>
		<div class="section download">
			<h2>Download</h2>
			<p>Showing newest files first. Files will be deleted after 24 hours.</p>
			{files_links}
		</div>
		<div id="footer">
			&copy; Thomas Radeke, {year}<br>
			<a href="https://www.rahdick.at">rahdick.at</a>
		</div>
	</body>
</html>