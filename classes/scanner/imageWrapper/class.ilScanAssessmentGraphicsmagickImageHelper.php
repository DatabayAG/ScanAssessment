<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/imageWrapper/interface.ilScanAssessmentImageHelper.php';
/**
 * Class ilScanAssessmentGraphicsmagickImageHelper
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentGraphicsmagickImageHelper 
{


	protected $image;

	public function __construct($fn)
	{
		$img = new Gmagick(realpath($fn));
		$this->setImage($img);
	}

	/**
	 * @return Gmagick
	 */
	public function getImage()
	{
		return $this->image;
	}

	/**
	 * @param Gmagick $image
	 */
	public function setImage($image)
	{
		$this->image = $image;
	}

	public function getColor(ilScanAssessmentPoint $point) 
	{
		$img_clone = clone $this->getImage();
		$img_clone->cropimage(1, 1, $point->getX(), $point->getY());
		$color = $img_clone->getimagehistogram()[0]->getcolor();
		$re = '/rgb\((\d+),(\d+),(\d+)\)/';
		preg_match($re, $color, $matches);
		return(array($matches[0],$matches[1],$matches[2]));
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
	 * @param Gmagick $im
	 * @param $rad
	 * @return mixed
	 */
	public function rotate($rad)
	{
		return $this->getImage()->rotateImage(new GmagickPixel('#ffffff'), $rad);
	}

	/**
	 * @param Gmagick $img
	 * @param $fn
	 */
	public function drawTempImage($img, $fn)
	{
		$img->write('/tmp/' . $fn);
	}

	/**
	 * @param Gmagick $temp_img
	 * @param $start_x
	 * @param $start_y
	 * @param $end_x
	 * @param $end_y
	 * @param $color
	 */
	public function drawLine($temp_img, $start_x, $start_y, $end_x, $end_y, $color)
	{
		$draw = new GmagickDraw();
		$draw->setstrokecolor(new GmagickPixel('#00ff00ff'));
		$draw->setfillcolor(1);
		$draw->line($start_x ,$start_y ,$end_x , $end_y );
		$temp_img->drawimage($draw);
	}

	/**
	 * @param Gmagick $temp_img
	 * @param ilScanAssessmentPoint $point
	 * @param $color
	 */
	public function drawPixel($temp_img, $point, $color)
	{

		$draw = new GmagickDraw();
		$draw->setstrokecolor(new GmagickPixel('#ff0000ff'));
		$draw->point($point->getX(), $point->getY());
		$temp_img->drawimage($draw);
	}

	/**
	 * @param Gmagick $temp_img
	 * @param ilScanAssessmentVector $vector
	 * @param $color
	 */
	public function drawSquareFromVector($temp_img, $vector, $color)
	{
		$draw = new GmagickDraw();
		$draw->setstrokecolor(new GmagickPixel('#ff0000'));
		$draw->setfillcolor('none');
		$draw->rectangle(	
			$vector->getPosition()->getX() - $vector->getLength() / 2,
			$vector->getPosition()->getY() - $vector->getLength() / 2,
			$vector->getPosition()->getX() + $vector->getLength() / 2,
			$vector->getPosition()->getY() + $vector->getLength() / 2
		);
		$temp_img->drawimage($draw);
	}

	/**
	 * @param Gmagick $temp_img
	 * @param ilScanAssessmentPoint $first
	 * @param ilScanAssessmentPoint $second
	 * @param $color
	 */
	public function drawSquareFromTwoPoints($temp_img, $first, $second, $color)
	{
		$draw = new GmagickDraw();
		$draw->setstrokecolor(new GmagickPixel('#0000ffff'));
		$draw->setfillcolor('none');
		$draw->rectangle($first->getX(), $first->getY(), $second->getX(), $second->getY());
		$temp_img->drawimage($draw);

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