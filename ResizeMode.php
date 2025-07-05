<?php
/***
 * This is the ResizeMode Class built by Omamode Israel Joseph.
 * It is simple, lightweight, minimal and powerful.
***/
define("DIRECTORY", dirname(__FILE__));
define('DEFAULT_Q', 85);
define('DEFAULT_ZC', 1);
define('DEFAULT_F', '');
define('DEFAULT_S', 0);
define('DEFAULT_CC', 'ffffff');

if (!defined('PNG_IS_TRANSPARENT')):
define('PNG_IS_TRANSPARENT', false);
endif;

if (!defined('MAX_WIDTH')):
define('MAX_WIDTH', 3000);
endif;

if (!defined('MAX_HEIGHT')):
define('MAX_HEIGHT', 3000);
endif;

if (!defined('MEMORY_LIMIT')):
define('MEMORY_LIMIT', '2000M');
endif;

ini_set('memory_limit', MEMORY_LIMIT);

class ResizeMode{
    protected $src = "";
    protected $dest = "";
    protected $dir = "";
    protected $w = 0;
    protected $h = 0;
    public $status = 0;
    protected $cropTop = false;
    public $msg = "";
    public function __construct($src, $dest, $w, $h, $dir = DIRECTORY){
        $this->src = $src;
        $this->dest = $dest;
        $this->w = (isset($w) && !empty($w)? $w : 0);
        $this->h = (isset($h) && !empty($h)? $h : 0);
        $this->dir = $dir;
        $this->start();
    }

	//The core method
    protected function resize(){
        $baseImagePath = $this->dir;
	    $localImage = $baseImagePath ."/". $this->src;
		$sData = getimagesize($localImage);
		$origType = $sData[2];
		$mimeType = $sData['mime'];
        $cacheDir = pathinfo($this->dest)["dirname"];
	    $cachedFilePath = $baseImagePath.'/'.$this->dest;
        $new_width =  $this->w;
		$new_height = $this->h;
        $zoom_crop = (int) $this->param('zc', DEFAULT_ZC);
		$quality = (int) abs ($this->param('q', DEFAULT_Q));
		$align = $this->cropTop ? 't' : $this->param('a', 'c');
		$filters = $this->param('f', DEFAULT_F);
		$sharpen = (bool) $this->param('s', DEFAULT_S);
		$canvas_color = $this->param('cc', DEFAULT_CC);
		$canvas_trans = (bool) $this->param('ct', '1');

		if (!function_exists ('imagecreatetruecolor')) {
		    return $this->write('GD Library Error: imagecreatetruecolor does not exist - please contact your webhost and ask them to install the GD library');
		}

		if (function_exists ('imagefilter') && defined ('IMG_FILTER_NEGATE')) {
			$imageFilters = array (
				1 => array (IMG_FILTER_NEGATE, 0),
				2 => array (IMG_FILTER_GRAYSCALE, 0),
				3 => array (IMG_FILTER_BRIGHTNESS, 1),
				4 => array (IMG_FILTER_CONTRAST, 1),
				5 => array (IMG_FILTER_COLORIZE, 4),
				6 => array (IMG_FILTER_EDGEDETECT, 0),
				7 => array (IMG_FILTER_EMBOSS, 0),
				8 => array (IMG_FILTER_GAUSSIAN_BLUR, 0),
				9 => array (IMG_FILTER_SELECTIVE_BLUR, 0),
				10 => array (IMG_FILTER_MEAN_REMOVAL, 0),
				11 => array (IMG_FILTER_SMOOTH, 0),
			);
        }

		// set default width and height if neither are set already
		if ($new_width == 0 && $new_height == 0) {
		    $new_width = 100;
		    $new_height = 100;
		}

		// ensure size limits can not be abused
		$new_width = min ($new_width, MAX_WIDTH);
		$new_height = min ($new_height, MAX_HEIGHT);

		// set memory limit to be able to have enough space to resize larger images
		$this->setMemoryLimit();

		// open the existing image
		$image = $this->openImage ($mimeType, $localImage);
		if ($image === false) {
			return $this->write('Unable to open image.');
		}
		
		//print_r($image);

		// Get original width and height
		$width = imagesx ($image);
		$height = imagesy ($image);
		$origin_x = 0;
		$origin_y = 0;

		// generate new w/h if not provided
		if ($new_width && !$new_height) {
			$new_height = floor ($height * ($new_width / $width));
		} else if ($new_height && !$new_width) {
			$new_width = floor ($width * ($new_height / $height));
		}

		// scale down and add borders
		if ($zoom_crop == 3) {

			$final_height = $height * ($new_width / $width);

			if ($final_height > $new_height) {
				$new_width = $width * ($new_height / $height);
			} else {
				$new_height = $final_height;
			}

		}

		// create a new true color image
		$canvas = imagecreatetruecolor ($new_width, $new_height);
		imagealphablending ($canvas, false);

		if (strlen($canvas_color) == 3) { //if is 3-char notation, edit string into 6-char notation
			$canvas_color =  str_repeat(substr($canvas_color, 0, 1), 2) . str_repeat(substr($canvas_color, 1, 1), 2) . str_repeat(substr($canvas_color, 2, 1), 2);
		} else if (strlen($canvas_color) != 6) {
			$canvas_color = DEFAULT_CC; // on error return default canvas color
 		}

		$canvas_color_R = hexdec (substr ($canvas_color, 0, 2));
		$canvas_color_G = hexdec (substr ($canvas_color, 2, 2));
		$canvas_color_B = hexdec (substr ($canvas_color, 4, 2));

		// Create a new transparent color for image
	    // If is a png and PNG_IS_TRANSPARENT is false then remove the alpha transparency
		// (and if is set a canvas color show it in the background)
		if(preg_match('/^image\/png$/i', $mimeType) && !PNG_IS_TRANSPARENT && $canvas_trans){
			$color = imagecolorallocatealpha ($canvas, $canvas_color_R, $canvas_color_G, $canvas_color_B, 127);
		}else{
			$color = imagecolorallocatealpha ($canvas, $canvas_color_R, $canvas_color_G, $canvas_color_B, 0);
		}


		// Completely fill the background of the new image with allocated color.
		imagefill ($canvas, 0, 0, $color);

		// scale down and add borders
		if ($zoom_crop == 2) {

			$final_height = $height * ($new_width / $width);

			if ($final_height > $new_height) {

				$origin_x = $new_width / 2;
				$new_width = $width * ($new_height / $height);
				$origin_x = round ($origin_x - ($new_width / 2));

			} else {

				$origin_y = $new_height / 2;
				$new_height = $final_height;
				$origin_y = round ($origin_y - ($new_height / 2));

			}

		}

		// Restore transparency blending
		imagesavealpha ($canvas, true);

		if ($zoom_crop > 0) {

			$src_x = $src_y = 0;
			$src_w = $width;
			$src_h = $height;

			$cmp_x = $width / $new_width;
			$cmp_y = $height / $new_height;

			// calculate x or y coordinate and width or height of source
			if ($cmp_x > $cmp_y) {

				$src_w = round ($width / $cmp_x * $cmp_y);
				$src_x = round (($width - ($width / $cmp_x * $cmp_y)) / 2);

			} else if ($cmp_y > $cmp_x) {

				$src_h = round ($height / $cmp_y * $cmp_x);
				$src_y = round (($height - ($height / $cmp_y * $cmp_x)) / 2);

			}

			// positional cropping!
			if ($align) {
				if (strpos ($align, 't') !== false) {
					$src_y = 0;
				}
				if (strpos ($align, 'b') !== false) {
					$src_y = $height - $src_h;
				}
				if (strpos ($align, 'l') !== false) {
					$src_x = 0;
				}
				if (strpos ($align, 'r') !== false) {
					$src_x = $width - $src_w;
				}
			}

			imagecopyresampled ($canvas, $image, $origin_x, $origin_y, $src_x, $src_y, $new_width, $new_height, $src_w, $src_h);

		} else {

			// copy and resize part of an image with resampling
			imagecopyresampled ($canvas, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

		}

		if ($filters != '' && function_exists ('imagefilter') && defined ('IMG_FILTER_NEGATE')) {
			// apply filters to image
			$filterList = explode ('|', $filters);
			foreach ($filterList as $fl) {

				$filterSettings = explode (',', $fl);
				if (isset ($imageFilters[$filterSettings[0]])) {

					for ($i = 0; $i < 4; $i ++) {
						if (!isset ($filterSettings[$i])) {
							$filterSettings[$i] = null;
						} else {
							$filterSettings[$i] = (int) $filterSettings[$i];
						}
					}

					switch ($imageFilters[$filterSettings[0]][1]) {

						case 1:

							imagefilter ($canvas, $imageFilters[$filterSettings[0]][0], $filterSettings[1]);
							break;

						case 2:

							imagefilter ($canvas, $imageFilters[$filterSettings[0]][0], $filterSettings[1], $filterSettings[2]);
							break;

						case 3:

							imagefilter ($canvas, $imageFilters[$filterSettings[0]][0], $filterSettings[1], $filterSettings[2], $filterSettings[3]);
							break;

						case 4:

							imagefilter ($canvas, $imageFilters[$filterSettings[0]][0], $filterSettings[1], $filterSettings[2], $filterSettings[3], $filterSettings[4]);
							break;

						default:

							imagefilter ($canvas, $imageFilters[$filterSettings[0]][0]);
							break;

					}
				}
			}
		}

		// sharpen image
		if ($sharpen && function_exists ('imageconvolution')) {

			$sharpenMatrix = array (
					array (-1,-1,-1),
					array (-1,16,-1),
					array (-1,-1,-1),
					);

			$divisor = 8;
			$offset = 0;

			imageconvolution ($canvas, $sharpenMatrix, $divisor, $offset);

		}
        
        imagedestroy($image);

    if (!file_exists($cacheDir)) {
        if (!mkdir($cacheDir, 0755, true)) {
            return $this->write("Failed to create destination directory.");
        }
    }

    switch ($mimeType) {
        case 'image/jpeg':
            imagejpeg($canvas, $cachedFilePath, 100);
            break;
        case 'image/png':
            imagepng($canvas, $cachedFilePath, 10);
            break;
        case 'image/gif':
            imagegif($canvas, $cachedFilePath);
            break;
        case 'image/webp':
            imagewebp($image, $cachedFilePath, 100);
            break;
    }

    $this->write("success", 1);
    
    imagedestroy($canvas);
    }

	//Open an image
    protected function openImage($mimeType, $src){
		switch ($mimeType) {
			case 'image/jpeg':
				$image = imagecreatefromjpeg ($src);
				break;

			case 'image/png':
				$image = imagecreatefrompng ($src);
				imagealphablending( $image, true );
				imagesavealpha( $image, true );
				break;

			case 'image/gif':
				$image = imagecreatefromgif ($src);
				break;
            case 'image/webp':
                $image = imagecreatefromwebp($src);
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;
			default:
				$this->write("Unrecognised mimeType");
				return false;
		}
		return $image;
	}

	//Entry point
    protected function start(){
        if (file_exists($this->src) && $this->isImage($this->src)){
            $this->resize();
        }else{
            $this->write("The source does not exist or is not an image on the server");
        }
    }

	//Check if provided file is a supported image
    protected function isImage($file){
        if (count(explode(".", $file)) > 1){
            $ext = strtolower($this->get_extension($file));
        }else{
            $ext = strtolower($file);
        }
        
        if (!in_array($ext, ["gif", "jpg", "png", "webp", "jpeg", "jfif"])){
            return false;
        }
        return true;
    }

	//Get extension
    protected function get_extension($file){
        return preg_split('/[\W\s]+/', pathinfo($file, PATHINFO_EXTENSION), -1, PREG_SPLIT_NO_EMPTY)[0];
    }    

	//Method to help send response
    protected function write($msg, $status = 0){
        $this->status = $status;
        $this->msg = $msg;
    }

	//Get URL parameters if any in the current URL
    protected function param($param, $default = ""){
        return $_GET[$param] ?? $default;
    }

	//Set Memory
    protected function setMemoryLimit(){
		$inimem = ini_get('memory_limit');
		$inibytes = ResizeMode::returnBytes($inimem);
		$ourbytes = ResizeMode::returnBytes(MEMORY_LIMIT);
		if($inibytes < $ourbytes){
			ini_set ('memory_limit', MEMORY_LIMIT);
		}
	}

	//Calculate memory size in bytes
    protected static function returnBytes($size_str){
		switch (substr ($size_str, -1))
		{
			case 'M': case 'm': return (int)$size_str * 1048576;
			case 'K': case 'k': return (int)$size_str * 1024;
			case 'G': case 'g': return (int)$size_str * 1073741824;
			default: return $size_str;
		}
	}
}
?>