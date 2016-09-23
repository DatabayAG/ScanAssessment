<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/imageWrapper/interface.ilScanAssessmentImageHelper.php';

/**
 * Class ilScanAssessmentImageHelper
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentGDImageHelper implements ilScanAssessmentImageHelper
{
	protected $image;

	/**
	 * @return mixed
	 */
	public function getImage()
	{
		return $this->image;
	}

	/**
	 * @param mixed $image
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
		$color	= imagecolorat($this->getImage(), $point->getX(), $point->getY());
		$blue	= 0x0000ff & $color;
		$green	= 0x00ff00 & $color;
		$green	= $green >> 8;
		$red	= 0xff0000 & $color;
		$red	= $red >> 16;

		return(array($red, $green, $blue));
	}

	/**
	 * @param ilScanAssessmentPoint $point
	 * @return float|int
	 */
	public function getGrey(ilScanAssessmentPoint $point)
	{
		$rgb	= $this->getColor($point);
		$grey	= ($rgb[0] + $rgb[1] + $rgb[2]) / 3 ;
		return $grey;
	}

	public function removeBlackBorder() 
	{
		$img2 = $this->getImage();

		for($y = $this->getImageSizeY() - 1; $y > $this->getImageSizeY() - 100; $y--) 
		{
			if($this->getGrey( new ilScanAssessmentPoint(round($this->getImageSizeX()) / 2 , $y)) > 50) 
			{
				$img2 = imagecreatetruecolor($this->getImageSizeX(), $y);
				imagecopy($img2, $this->getImage(), 0,0,0,0,$this->getImageSizeX(), $y);
				break;
			}
		}

		for($x = $this->getImageSizeX() - 1;  $x > $this->getImageSizeX() - 100; $x--) 
		{
			if($this->getGrey(new ilScanAssessmentPoint($x, round( $this->getImageSizeY() ) / 2 ) ) > 50 ) 
			{
				$img2 = imagecreatetruecolor($x, $this->getImageSizeY());
				imagecopy($img2, $this->getImage(), 0,0,0,0,$x, $this->getImageSizeY());
				break;
			}
		}

		return $img2;
	}

	public function rotate($rad)
	{
		$white = imagecolorallocate($this->getImage(), 255,255,255);
		return imagerotate($this->getImage(), $rad, $white);
	}

	public function drawLine($temp_img, $start_x, $start_y, $end_x, $end_y, $color)
	{
		imageline(
			$temp_img, $start_x, $start_y,
			$end_x, $end_y,
			$color
		);
	}
	
	public function drawPixel($temp_img, $point, $color)
	{
		imagesetpixel($temp_img, $point->getX(), $point->getY(), $color);
	}
	
	public function drawSquareFromVector($temp_img, $vector, $color)
	{
		imagerectangle($temp_img,
			$vector->getPosition()->getX() - $vector->getLength() / 2,
			$vector->getPosition()->getY() - $vector->getLength() / 2,
			$vector->getPosition()->getX() + $vector->getLength() / 2,
			$vector->getPosition()->getY() + $vector->getLength() / 2,
			$color);
	}
	
	public function drawSquareFromTwoPoints($temp_img, $first, $second, $color)
	{
		imagerectangle($temp_img, $first->getX(), $first->getY(), $second->getX(), $second->getY(), 0x0000dd);
	}

	public function getImageSizeY()
	{
		return imagesy($this->getImage());
	}

	public function getImageSizeX()
	{
		return imagesx($this->getImage());
	}

	public function __construct($fn)
	{
		$img = imagecreatefromjpeg($fn);
		$this->setImage($img);
	}

	public function drawTempImage($img, $fn)
	{
		imagejpeg($img, '/tmp/'  . $fn);
	}
}