<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/imageWrapper/interface.ilScanAssessmentImageHelper.php';
/**
 * Class ilScanAssessmentImagemagickImageHelper
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentImagemagickImageHelper 
{


	protected $image;

	public function __construct($fn)
	{
		$img = new Imagick(realpath($fn));
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
	 * @param Imagick $im
	 * @param $rad
	 * @return mixed
	 */
	public function rotate($rad)
	{
		return $this->getImage()->rotateImage(new ImagickPixel('#ffffff'), $rad);
	}

	/**
	 * @param Imagick $img
	 * @param $fn
	 */
	public function drawTempImage($img, $fn)
	{
		$img->writeImage('/tmp/' . $fn);
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
		$draw->setStrokeColor(new ImagickPixel('#00ff00ff'));
		$draw->setStrokeWidth(1);
		$draw->line($start_x ,$start_y ,$end_x , $end_y );
		$temp_img->drawImage($draw);
	}

	/**
	 * @param Imagick $temp_img
	 * @param ilScanAssessmentPoint $point
	 * @param $color
	 */
	public function drawPixel($temp_img, $point, $color)
	{

		$draw = new ImagickDraw();
		$draw->setStrokeColor(new ImagickPixel('#ff0000ff'));
		$draw->point($point->getX(), $point->getY());
		$temp_img->drawImage($draw);
	}

	/**
	 * @param Imagick $temp_img
	 * @param ilScanAssessmentVector $vector
	 * @param $color
	 */
	public function drawSquareFromVector($temp_img, $vector, $color)
	{
		$draw = new ImagickDraw();
		$draw->setStrokeColor(new ImagickPixel('#ff0000'));
		$draw->setFillColor('none');
		$draw->rectangle(	
			$vector->getPosition()->getX() - $vector->getLength() / 2,
			$vector->getPosition()->getY() - $vector->getLength() / 2,
			$vector->getPosition()->getX() + $vector->getLength() / 2,
			$vector->getPosition()->getY() + $vector->getLength() / 2
		);
		$temp_img->drawImage($draw);
	}

	/**
	 * @param Imagick $temp_img
	 * @param ilScanAssessmentPoint $first
	 * @param ilScanAssessmentPoint $second
	 * @param $color
	 */
	public function drawSquareFromTwoPoints($temp_img, $first, $second, $color)
	{
		$draw = new ImagickDraw();
		$draw->setStrokeColor(new ImagickPixel('#0000ffff'));
		$draw->setFillColor('none');
		$draw->rectangle($first->getX(), $first->getY(), $second->getX(), $second->getY());
		$temp_img->drawImage($draw);

	}

	public function getImageSizeY()
	{
		$size = $this->getImage()->getImageGeometry();
		return $size['height'];
	}

	public function getImageSizeX()
	{
		$size = $this->getImage()->getImageGeometry();
		return $size['width'];
	}
}