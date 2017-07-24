<?php
ilScanAssessmentPlugin::getInstance()->includeClass('../interfaces/interface.ilScanAssessmentImageWrapper.php');

/**
 * Class ilScanAssessmentImagemagickWrapper
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentImagemagickWrapper implements ilScanAssessmentImageWrapper
{

	/**
	 * @var Imagick
	 */
	protected $image;

	/**
	 * ilScanAssessmentImagemagickWrapper constructor.
	 * @param string $fn
	 */
	public function __construct($fn)
	{
		$img = new Imagick(realpath($fn));
		$this->setImage($img);
	}

	/**
	 * @return Imagick
	 */
	public function getImage()
	{
		return $this->image;
	}

	/**
	 * @param Imagick $image
	 */
	public function setImage($image)
	{
		$this->image = $image;
	}

	/**
	 * @param ilScanAssessmentPoint $point
	 * @return array
	 */
	public function getColor(ilScanAssessmentPoint $point) 
	{
		$pixel = $this->getImage()->getImagePixelColor($point->getX(), $point->getY());
		$color = $pixel->getColor();
		return(array($color['r'], $color['g'], $color['b']));
	}

	/**
	 * @param ilScanAssessmentPoint $point
	 * @return float|int
	 */
	public function getGrey(ilScanAssessmentPoint $point)
	{
		$rgb = $this->getColor($point);
		$grey =  ($rgb[0] + $rgb[1] + $rgb[2]) / 3 ;
		return $grey;
	}

	/**
	 * @return Imagick
	 */
	public function removeBlackBorder() 
	{
		$img2 = $this->getImage();
/*
		for($y = imagesy($img) - 1; $y > imagesy($img) - 100; $y--) 
		{
			if($this->getGrey($img, new ilScanAssessmentPoint(round(imagesx($img)) / 2 , $y)) > 50) 
			{
				$img2 = imagecreatetruecolor(imagesx($img), $y);
				imagecopy($img2, $img, 0,0,0,0,imagesx($img), $y);
				break;
			}
		}

		for($x = imagesx($img) - 1;  $x > imagesx($img) - 100; $x--) 
		{
			if($this->getGrey($img, new ilScanAssessmentPoint($x, round( imagesy ( $img) ) / 2 ) ) > 50 ) 
			{
				$img2 = imagecreatetruecolor($x, imagesy($img));
				imagecopy($img2, $img, 0,0,0,0,$x, imagesy($img));
				break;
			}
		}
*/
		return $img2;
	}

	/**
	 * @param $rad
	 * @return mixed
	 */
	public function rotate($rad)
	{
		return $this->getImage()->rotateImage(new ImagickPixel($this->getWhite()), $rad);
	}

	/**
	 * @param Imagick $img
	 * @param $fn
	 */
	public function drawTempImage($img, $fn)
	{
		$img->writeImage($fn);
	}

	/**
	 * @param Imagick $temp_img
	 * @param $start_x
	 * @param $start_y
	 * @param $end_x
	 * @param $end_y
	 * @param $color
	 */
	public function drawLine($temp_img, $start_x, $start_y, $end_x, $end_y, $color)
	{
		$draw = new ImagickDraw();
		$draw->setStrokeColor(new ImagickPixel($color));
		$draw->setStrokeWidth(1);
		$draw->line($start_x ,$start_y ,$end_x , $end_y );
		$temp_img->drawImage($draw);
	}

	/**
	 * @param Imagick $temp_img
	 * @param ilScanAssessmentPoint $point
	 * @param $color
	 */
	public function drawPixel($temp_img, ilScanAssessmentPoint $point, $color)
	{

		$draw = new ImagickDraw();
		$draw->setStrokeColor(new ImagickPixel($color));
		$draw->point($point->getX(), $point->getY());
		$temp_img->drawImage($draw);
	}

	/**
	 * @param Imagick $temp_img
	 * @param ilScanAssessmentVector $vector
	 * @param $color
	 */
	public function drawSquareFromVector($temp_img, ilScanAssessmentVector $vector, $color)
	{
		$draw = new ImagickDraw();
		$draw->setStrokeColor(new ImagickPixel($color));
		$draw->setFillOpacity(0);
		$draw->rectangle(	
			$vector->getPosition()->getX() - $vector->getLength() / 2,
			$vector->getPosition()->getY() - $vector->getLength() / 2,
			$vector->getPosition()->getX() + $vector->getLength() / 2,
			$vector->getPosition()->getY() + $vector->getLength() / 2
		);
		//$temp_img->drawImage($draw);
	}

	/**
	 * @param Imagick $temp_img
	 * @param ilScanAssessmentPoint $first
	 * @param ilScanAssessmentPoint $second
	 * @param $color
	 */
	public function drawSquareFromTwoPoints($temp_img, ilScanAssessmentPoint $first, ilScanAssessmentPoint $second, $color)
	{
		$draw = new ImagickDraw();
		$draw->setStrokeColor(new ImagickPixel($color));
		$draw->setFillAlpha(0.0);
		$draw->setFillOpacity(0.0);
		$draw->rectangle($first->getX(), $first->getY(), $second->getX(), $second->getY());
		//$temp_img->drawImage($draw);

	}

	/**
	 * @return int
	 */
	public function getImageSizeY()
	{
		$size = $this->getImage()->getImageGeometry();
		return $size['height'];
	}

	/**
	 * @return int
	 */
	public function getImageSizeX()
	{
		$size = $this->getImage()->getImageGeometry();
		return $size['width'];
	}

	/**
	 * @return string
	 */
	public function getWhite()
	{
		return '#FFFFFF';
	}

	/**
	 * @return string
	 */
	public function getBlack()
	{
		return '#000000';
	}

	/**
	 * @return string
	 */
	public function getRed()
	{
		return '#FF0000';
	}

	/**
	 * @return string
	 */
	public function getGreen()
	{
		return '#00FF00';
	}

	/**
	 * @return string
	 */
	public function getPink()
	{
		return '#FF00FF';
	}

	/**
	 * @return string
	 */
	public function getYellow()
	{
		return '#FFFF00';
	}

	/**
	 * @return string
	 */
	public function getBlue()
	{
		return '#0000FF';
	}

	/**
	 * @param Imagick $image
	 * @param ilScanAssessmentVector $vector
	 * @return Imagick
	 */
	function imageCrop($image, $vector)
	{
		$image->cropImage($vector->getLength(), $vector->getLength(), $vector->getPosition()->getX(), $vector->getPosition()->getY());
		return $image;
	}

	/**
	 * @param $filename
	 * @return Imagick
	 */
	public function createNewImageInstanceFromFileName($filename)
	{
		return new Imagick($filename);
	}

	/**
	 * @param Imagick $image
	 * @param ilScanAssessmentPoint $point1
	 * @param ilScanAssessmentPoint $point2
	 * @return Imagick
	 */
	function imageCropByPoints($image, $point1, $point2)
	{
		if($point1->getX() <= 0)
		{
			$point1->setX(1);
		}
		if($point1->getY() <= 0 || $point1->getY() > $point2->getY())
		{
			$point1->setY(1);
		}

		$width = $point2->getX() - $point1->getX();
		$height = $point2->getY() - $point1->getY();

		if($width <= 0)
		{
			$width = 1;
		}
		if($height <= 0)
		{
			$height = 1;
		}

		$image->cropImage($width, $height, $point1->getX(), $point1->getY());
		return $image;
	}

	/**
	 * @param self $image
	 * @param      $src_x
	 * @param      $src_y
	 * @param      $dest_x
	 * @param      $dest_y
	 * @param null $filename
	 * @return Imagick
	 */
	function imageCropWithSource($image, $src_x, $src_y, $dest_x, $dest_y, $filename = null)
	{
		if($src_x < 0)
		{
			$src_x = 0;
		}
		if($src_y < 0)
		{
			$src_y = 0;
		}
		if($dest_x < 0)
		{
			$dest_x = 0;
		}
		if($dest_y < 0)
		{
			$dest_y = 0;
		}
		$height = $image->getImageSizeY() - $src_y - $dest_y;
		$width = $image->getImageSizeX() - $src_x - $dest_x;
		$scaled_image = new Imagick($image);
		$scaled_image->cropImage(0,0 , $height, $width);
		$scaled_image->newImage(100, 100, new ImagickPixel('white'));
		$scaled_image->compositeImage($image, Imagick::COMPOSITE_OVER, $src_x, $src_y);
		if($filename != null)
		{
			$this->drawTempImage($scaled_image, $filename);
		}
		return $scaled_image;
	}

}