# XMPlay Skin Scaler

By Thomas Radeke, 2018

This script automatically scales XMPlay skins to arbitrary sizes.

The Windows music player XMPlay has existed for a very long time and many of its skins have been created many years ago - on much smaller, lower-resolution screens that what's available today. The emergence of 4K screens has made some of these skins all but unusable.

This script provides an automated solution: it takes a .xmpskin file, unpacks its contents, resizes all skin images, modifies the skin configuration and packs everything up again. The resulting .xmpskin file is ready to use by XMPlay!

## Set up
The script is based on PHP and does all its conversions using ImageMagick.
There are two backends for the image conversion: one is using ImageMagick's command line interface "convert" (default) and the other is using the PHP extension "php-imagick". Both do the same job, but being able to choose allows for more flexibility in case one or the other is not installed.
### Windows
On Windows, you need to install:
- PHP 7
- ImageMagick

Warning: In case you want to use the "php-imagick" backend, getting the PHP imagick module working on Windows is quite complicated. I'm using XAMPP as my webdev system on Windows and had success following this guide:
- [How to install and enable the Imagick extension in XAMPP for Windows](https://ourcodeworld.com/articles/read/349/how-to-install-and-enable-the-imagick-extension-in-xampp-for-windows) 

### Linux
Setting up a suitable environment is much easier on Linux, just install the following packages:
- php7.0-cli
- imagemagick
- php-zip (already included with PHP7 on Windows)
- php-imagick (for the "php-imagick" backend only)

To test if PHP works, just enter `php --version` in the terminal.

## Running

### Command Line

Once everything is set up, the script can be run from the command prompt:
```
php xmplay-skin-scaler.php -i input -s scale [-f filter]
  -i: can be either an .xmpskin file or a directory with skin files.
  -s: any (float) number greater than 0.
  -f (optional): one of the following: point|triangle|hermite. (default: point)
  Please refer to the ImageMagick reference to learn more about the filter characteristics:
    http://www.imagemagick.org/Usage/filter/#interpolated
  Example: php xmplay-skin-scaler.php -i "iXMPlay.xmpskin" -s 2.0 -f triangle
```

### Web UI

Version 0.3 includes a web interface that can be hosted on a suitable server with the neccessary PHP extensions installed. The actual interface is "xmplay-skin-scaler-web.php", but a redirect from "index.php" has been included for convenience.

The web UI supports uploading multiple skins, selecting the scale factor and the image filter and converts all uploaded files automatically. The results of previous conversions are displayed as a downloadable list.