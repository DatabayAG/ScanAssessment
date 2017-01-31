<?php
ilScanAssessmentPlugin::getInstance()->includeClass('../interfaces/interface.ilScanAssessmentImageWrapper.php');

/**
 * Class ilScanAssessmentGDWrapper
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentGDWrapper implements ilScanAssessmentImageWrapper
{

	/**
	 * @var
	 */
	protected $image;

	/**
	 * ilScanAssessmentGDWrapper constructor.
	 * @param string $fn
	 */
	public function __construct($fn)
	{
		$img = imagecreatefromjpeg($fn);
		$this->setImage($img);
	}

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

	/**
	 * @return resource
	 */
	public function removeBlackBorder()
	{
		$img = $this->getImage();

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

	/**
	 * @param $rad
	 * @return resource
	 */
	public function rotate($rad)
	{
		$white = imagecolorallocate($this->getImage(), 0,255,255);
		$rotated = imagerotate($this->getImage(), $rad, $white);
		$this->setImage($rotated);
		return $rotated;
	}

	/**
	 * @param $temp_img
	 * @param $start_x
	 * @param $start_y
	 * @param $end_x
	 * @param $end_y
	 * @param $color
	 */
	public function drawLine($temp_img, $start_x, $start_y, $end_x, $end_y, $color)
	{
		imageline(
			$temp_img, $start_x, $start_y,
			$end_x, $end_y,
			$color
		);
	}

	/**
	 * @param                       $temp_img
	 * @param ilScanAssessmentPoint $point
	 * @param                       $color
	 */
	public function drawPixel($temp_img, ilScanAssessmentPoint $point, $color)
	{
		imagesetpixel($temp_img, $point->getX(), $point->getY(), $color);
	}

	/**
	 * @param                        $temp_img
	 * @param ilScanAssessmentVector $vector
	 * @param                        $color
	 */
	public function drawSquareFromVector($temp_img, ilScanAssessmentVector $vector, $color)
	{
		imagerectangle($temp_img,
			$vector->getPosition()->getX() - $vector->getLength() / 2,
			$vector->getPosition()->getY() - $vector->getLength() / 2,
			$vector->getPosition()->getX() + $vector->getLength() / 2,
			$vector->getPosition()->getY() + $vector->getLength() / 2,
			$color);
	}

	/**
	 * @param                       $temp_img
	 * @param ilScanAssessmentPoint $first
	 * @param ilScanAssessmentPoint $second
	 * @param                       $color
	 */
	public function drawSquareFromTwoPoints($temp_img, ilScanAssessmentPoint $first, ilScanAssessmentPoint $second, $color)
	{
		imagerectangle($temp_img, $first->getX(), $first->getY(), $second->getX(), $second->getY(), $color);
	}

	/**
	 * @return int
	 */
	public function getImageSizeY()
	{
		return imagesy($this->getImage());
	}

	/**
	 * @return int
	 */
	public function getImageSizeX()
	{
		return imagesx($this->getImage());
	}

	/**
	 * @param $img
	 * @param $fn
	 */
	public function drawTempImage($img, $fn)
	{
		imagejpeg($img, $fn);
	}

	/**
	 * @return string
	 */
	public function getWhite()
	{
		return '0xffffff';
	}

	/**
	 * @return string
	 */
	public function getBlack()
	{
		return '0x000000';
	}

	/**
	 * @return string
	 */
	public function getRed()
	{
		return '0xff0000';
	}

	/**
	 * @return string
	 */
	public function getGreen()
	{
		return '0x00ff00';
	}

	/**
	 * @return string
	 */
	public function getPink()
	{
		return '0xff00ff';
	}

	/**
	 * @return string
	 */
	public function getYellow()
	{
		return '0xffff00';
	}

	/**
	 * @return string
	 */
	public function getBlue()
	{
		return '0x0000ff';
	}

	/**
	 * @param $image
	 * @param ilScanAssessmentVector $vector
	 * @return mixed
	 */
	function imageCrop($image, $vector)
	{
		return imagecrop($image, array('x' => $vector->getPosition()->getX(), 'y' => $vector->getPosition()->getY(), 'width' => $vector->getLength(), 'height' => $vector->getLength()));
	}

	/**
	 * @param $image
	 * @param ilScanAssessmentPoint $point1
	 * @param ilScanAssessmentPoint $point2
	 * @return mixed
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

		return imagecrop($image, array('x' => $point1->getX(), 'y' => $point1->getY(), 'width' => $width, 'height' => $height));
	}

}