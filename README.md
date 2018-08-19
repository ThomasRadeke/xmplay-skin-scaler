# XMPlay Skin Scaler

By Thomas Radeke, 2018

This script automatically scales XMPlay skins to arbitrary sizes.

The Windows music player XMPlay has existed for a very long time and many of its skins have been created many years ago - on much smaller, lower-resolution screens that what's available today. The emergence of 4K screens has made some of these skins all but unusable.

This script provides an automated solution: it takes a .xmpskin file, unpacks its contents, resizes all skin images, modifies the skin configuration and packs everything up again. The resulting .xmpskin file is ready to use by XMPlay!

## Set up
The script is based on PHP and does all its conversions using ImageMagick. The setup procedure is very different on Windows and Linux:
### Windows
On Windows, you need to install:
- PHP 7
- ImageMagick

Warning: getting the PHP imagick module working on Windows is quite complicated. I'm using XAMPP as my webdev system on Windows and had success following this guide:
- [How to install and enable the Imagick extension in XAMPP for Windows](https://ourcodeworld.com/articles/read/349/how-to-install-and-enable-the-imagick-extension-in-xampp-for-windows) 

### Linux
Setting up a suitable environment is much easier on Linux, just install the following packages:
- php7.0-cli
- php-imagick
- php-zip (included with PHP7 on Windows)

To test if PHP works, just enter `php --version` in the terminal.

## Running
Once everything is set up, the script can be run from the command prompt:
```
php xmplay-skin-scaler.php -i input -s scale [-f filter]
  -i: can be either an .xmpskin file or a directory with skin files.
  -s: any (float) number greater than 0.
  -f (optional): one of the following: point|box|triangle|hermite. (default: point)
  Please refer to the ImageMagick reference to learn more about the filter characteristics:
    http://www.imagemagick.org/Usage/filter/#interpolated
  Example: php xmplay-skin-scaler.php "iXMPlay.xmpskin" 2.0 box
