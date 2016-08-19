<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/geometry/class.ilScanAssessmentPoint.php';
/**
 * Class ilScanAssessmentImageHelper
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentImageHelper
{

	/**
	 * @param                       $im2
	 * @param ilScanAssessmentPoint $point
	 * @return array
	 */
	public function getColor(&$im2, ilScanAssessmentPoint $point) {
		$color = imagecolorat($im2, $point->getX(), $point->getY());
		$blue = 0x0000ff & $color;
		$green = 0x00ff00 & $color;
		$green = $green >> 8;
		$red =0xff0000 & $color;
		$red = $red >> 16;

		return(array($red, $green, $blue));
	}

	/**
	 * @param                       $im2
	 * @param ilScanAssessmentPoint $point
	 * @return float
	 */
	public function getGray(&$im2, ilScanAssessmentPoint $point)
	{
		$rgb = $this->getColor($im2, $point);
		return( ($rgb[0]+$rgb[1]+$rgb[2])/3 );
	}

	public function removeBlackBorder($img) 
	{
		$img2 = $img;

		for($y = imagesy($img) - 1; $y > imagesy($img) - 100; $y--) 
		{
			if($this->getGray($img, new ilScanAssessmentPoint(round(imagesx($img)) / 2 , $y)) > 50) 
			{
				$img2 = imagecreatetruecolor(imagesx($img), $y);
				imagecopy($img2, $img, 0,0,0,0,imagesx($img), $y);
				break;
			}
		}

		for($x = imagesx($img) - 1;  $x > imagesx($img) - 100; $x--) 
		{
			if($this->getGray($img, new ilScanAssessmentPoint($x, round( imagesy ( $img) ) / 2 ) ) > 50 ) 
			{
				$img2 = imagecreatetruecolor($x, imagesy($img));
				imagecopy($img2, $img, 0,0,0,0,$x, imagesy($img));
				break;
			}
		}

		return $img2;
	}

}