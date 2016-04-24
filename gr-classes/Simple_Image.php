<?php
class SimpleImage {

	var $image;
	var $image_type;

	var $GD = true;
	
	var $file;

	const MAX_AREA = 64802500;	# 8050 * 8050
	const BACKUP_SIZE = 2048;	# Fall back to this size squared for images which are too big

	public static function memoryNeeded($w, $h){
		return ceil($w * $h * 4 / 1048576) + 8;
	}

	function  __construct($try_imagick = false) {
		if ($try_imagick){
			$this->GD = !extension_loaded("imagick");
		}
	}

	function load($filename) {
		$this->file = $filename;
		# Depends on extension
		$image_info = getimagesize($filename);
		if ($this->GD){
			$need = self::memoryNeeded($image_info[0], $image_info[1]);
			if ($need > ini_get("memory_limit")){
				ini_set("memory_limit", $need*2.3 . "M");
			}
			$this->image_type = $image_info[2];
			if( $this->image_type == IMAGETYPE_JPEG ) {
				$this->image = imagecreatefromjpeg($filename);
			}
			elseif( $this->image_type == IMAGETYPE_GIF ) {
				$this->image = imagecreatefromgif($filename);
				imagealphablending($this->image, false);
				imagesavealpha($this->image, true);
			}
			elseif( $this->image_type == IMAGETYPE_PNG ) {
				$this->image = imagecreatefrompng($filename);
				imagealphablending($this->image, false);
				imagesavealpha($this->image, true);
			}
		}
		else {
			if (self::memoryNeeded($image_info[0], $image_info[1]) > ini_get("memory_limit")){
				$nname = "temp/r_$filename";
				$size = self::BACKUP_SIZE;
				$size = $size."x".$size;
				shell_exec("/usr/bin/convert -limit memory 32 -limit map 64 -resize $size $filename $nname");
				$filename = $nname;
			}
			$this->image = new Imagick($filename);
		}
	}

	function save($filename, $compression = 100, $permissions = null) {
		# Depends on extension
		if ($this->GD){
			if( $this->image_type == IMAGETYPE_JPEG ) {
				imagejpeg($this->image,$filename,$compression);
			}
			elseif( $this->image_type == IMAGETYPE_GIF ) {
				imagegif($this->image,$filename);
			}
			elseif( $this->image_type == IMAGETYPE_PNG ) {
				imagepng($this->image,$filename);
			}
			if( $permissions != null) {
				chmod($filename,$permissions);
			}
		}
		else {
			$this->image->writeImage($filename);
		}
	}

	function output() {
		# Depends on extension
		if ($this->GD){
			if( $this->image_type == IMAGETYPE_JPEG ) {
				imagejpeg($this->image);
			}
			elseif( $this->image_type == IMAGETYPE_GIF ) {
				imagegif($this->image);
			}
			elseif( $this->image_type == IMAGETYPE_PNG ) {
				imagepng($this->image);
			}
		}
		else {
			echo $this->image;
		}
	}

	function getWidth() {
		# Depends on extension
		
		if ($this->image == null){
			return 0;
		}
		
		if ($this->GD){
			return imagesx($this->image);
		}
		else {
			return $this->image->getImageWidth();
		}
	}

	function getHeight() {
		
		if ($this->image == null){
			return 0;
		}
		
		# Depends on extension
		if ($this->GD){
			return imagesy($this->image);
		}
		else {
			return $this->image->getImageHeight();
		}
	}

	function resizeToHeight($height) {
		# Independent
		$ratio = $height / $this->getHeight();
		$width = $this->getWidth() * $ratio;
		$this->resize($width,$height);
	}

	function resizeToWidth($width) {
		# Independent
		$ratio = $width / $this->getWidth();
		$height = $this->getheight() * $ratio;
		$this->resize($width,$height);
	}

	/**
	 *
	 * @param float $scale - float number by which to multiply image's dimension
	 */
	function scale($scale) {
		# Independent
		$width = $this->getWidth() * $scale;
		$height = $this->getheight() * $scale;
		$this->resize($width,$height);
	}

	function resize($width,$height) {
		# Depends on extension
		$width = intval($width);
		$height = intval($height);
		if ($this->GD){
			$new_image = imagecreatetruecolor($width, $height);
			imagealphablending($new_image, false);
			imagesavealpha($new_image,true);
			$transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
			imagefilledrectangle($new_image, 0, 0, $width, $height, $transparent);
			imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
			$this->image = $new_image;
		}
		else {
			$this->image->resizeImage($width,$height,Imagick::FILTER_LANCZOS,false);
		}

	}

	/**
	 * Turns current image into a square, using the shortest side of original image as new side for the square
	 */
	function square() {
		# Depends on extension
		$ow = $this->getWidth();
		$oh = $this->getHeight();
		$h = ($ow>$oh)?true:false; # horizontal image
		$side = min(array($ow,$oh)); # side of new image
		$x = $y = 0;
		if ($h){
			$x = ($ow-$oh)/2;
		}
		else {
			$y = ($oh-$ow)/2;
		}

		if ($this->GD){
			$new_image = imagecreatetruecolor($side, $side);
			imagecopyresampled($new_image, $this->image, 0, 0, $x, $y, $side, $side, $side, $side);
			$this->image = $new_image;
		}
		else {
			$this->image->cropImage($side,$side,$x,$y);
		}
	}

	function resizeProportional($max_width,$max_height){
		# Independent
		$w = $this->getWidth();
		if ($w>$max_width){
			$this->resizeToWidth($max_width);
		}
		$h = $this->getheight();
		if ($h>$max_height){
			$this->resizeToHeight($max_height);
		}
	}

	function destroy(){
		# Depends on extension
		if ($this->image == null){
			return;
		}
		if ($this->GD){
			imagedestroy($this->image);
		}
		else {
			$this->image->clear();
			$this->image->destroy();
		}
	}
	
	/**
	 *
	 * @param SimpleImage $image2 
	 * @param int $x x coordinate where top-left corner of second image should be inside this image
	 * @param int $y y coordinate where top-left corner of second image should be inside this image
	 */
	public function copyImageOntoThis($image2, $x, $y, $fit_canvas = false){
		if ($fit_canvas){
			$old_w = $this->getWidth();
			$old_h = $this->getHeight();
			$new_x = 0;
			$new_y = 0;
			
			if ($x < 0){
				$new_x = $x*(-1);
				$new_width = max(array($new_x + $this->getWidth(),$image2->getWidth()));
			}
			else {
				$new_width = max(array($x + $image2->getWidth(),$this->getWidth()));
			}
			
			if ($y < 0){
				$new_y = $y*(-1);
				$new_height = max(array($new_y + $this->getHeight(),$image2->getHeight()));
			}
			else {
				$new_height = max(array($y + $image2->getHeight(),$this->getHeight()));
			}
			
			
			if ($new_width > $this->getWidth() || $new_height > $this->getHeight()){
				$new_background = simageCreateTransparentCanvas($new_width,$new_height);
				$old_image = new SimpleImage();
				$old_image->image = $this->image;
				$this->image = $new_background;
				$this->copyImageOntoThis($old_image, $new_x, $new_y);
			}
		}
		imagecopymerge_alpha($this->image, $image2->image, $x, $y, 0, 0, $image2->getWidth(), $image2->getHeight(),100);
	}

	function getExtension(){
		return image_type_to_extension($this->image_type, false);
	}
	
	function cropTo($width = 0,$height = 0){
		if ($width > $this->getWidth()){
			$width = $this->getWidth();
		}
		if ($height > $this->getHeight()){
			$height = $this->getHeight();
		}
		$new_image = imagecreatetruecolor($width, $height);
		imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $width, $height);
		$this->image = $new_image;
	}
	
	function setBackground($color = array(255,255,255)){
		$background = imagecreatetruecolor($this->getWidth(), $this->getHeight());
		$color = imagecolorallocate($background, $color[0], $color[1], $color[2]);
		imagefilledrectangle($background, 0, 0, $this->getWidth(), $this->getHeight(), $color);
		$bw = new SimpleImage();
		$bw->image = $background;
		$bw->copyImageOntoThis($this, 0, 0);
		$this->image = $bw->image;
	}
	
}

function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct, $trans = NULL){
	$dst_w = imagesx($dst_im);
	$dst_h = imagesy($dst_im);

	// bounds checking
	$src_x = max($src_x, 0);
	$src_y = max($src_y, 0);
	$dst_x = max($dst_x, 0);
	$dst_y = max($dst_y, 0);
	if ($dst_x + $src_w > $dst_w)
		$src_w = $dst_w - $dst_x;
	if ($dst_y + $src_h > $dst_h)
		$src_h = $dst_h - $dst_y;

	for($x_offset = 0; $x_offset < $src_w; $x_offset++)
		for($y_offset = 0; $y_offset < $src_h; $y_offset++){
			// get source & dest color
			$srccolor = imagecolorsforindex($src_im, imagecolorat($src_im, $src_x + $x_offset, $src_y + $y_offset));
			$dstcolor = imagecolorsforindex($dst_im, imagecolorat($dst_im, $dst_x + $x_offset, $dst_y + $y_offset));

			// apply transparency
			if (is_null($trans) || ($srccolor !== $trans)){
				$src_a = $srccolor['alpha'] * $pct / 100;
				// blend
				$src_a = 127 - $src_a;
				$dst_a = 127 - $dstcolor['alpha'];
				$dst_r = ($srccolor['red'] * $src_a + $dstcolor['red'] * $dst_a * (127 - $src_a) / 127) / 127;
				$dst_g = ($srccolor['green'] * $src_a + $dstcolor['green'] * $dst_a * (127 - $src_a) / 127) / 127;
				$dst_b = ($srccolor['blue'] * $src_a + $dstcolor['blue'] * $dst_a * (127 - $src_a) / 127) / 127;
				$dst_a = 127 - ($src_a + $dst_a * (127 - $src_a) / 127);
				$color = imagecolorallocatealpha($dst_im, $dst_r, $dst_g, $dst_b, $dst_a);
				// paint
				if (!imagesetpixel($dst_im, $dst_x + $x_offset, $dst_y + $y_offset, $color))
					return false;
				imagecolordeallocate($dst_im, $color);
			}
		}
	return true;
}

function simageCreateTransparentCanvas($width, $height){
	$new_background = imagecreatetruecolor($width, $height);
	imagesavealpha($new_background, true);
	$white = imagecolorallocate($new_background, 255, 255, 255);
	$grey = imagecolorallocate($new_background, 128, 128, 128);
	$black = imagecolorallocate($new_background, 0, 0, 0);
	imagefilledrectangle($new_background, 0, 0, 150, 25, $black);
	$trans_colour = imagecolorallocatealpha($new_background, 0, 0, 0, 127);
	imagefill($new_background, 0, 0, $trans_colour);
	return $new_background;
}

function simageTextBox($font,$size){
	$test_chars = 'abcdefghijklmnopqrstuvwxyz' .
			      'ABCDEFGHIJKLMNOPQRSTUVWXYZ' .
				  '1234567890' .
				  '!@#$%^&*()\'"\\/;.,`~<>[]{}-+_-=' ;
	return @ImageTTFBBox($size,0,$font,$test_chars) ;
}
?>