<?php

/**
 * Created by PhpStorm.
 * User: gvollbach
 * Date: 18.08.16
 * Time: 14:35
 */
class ilScanAssessmentImageHelper
{

	public function getColor(&$im2, $x,$y) {
		$color = imagecolorat($im2,$x,$y);
		$blue = 0x0000ff & $color;
		$green = 0x00ff00 & $color;
		$green = $green >> 8;
		$red =0xff0000 & $color;
		$red = $red >> 16;

		return(array($red, $green, $blue));
	}

	public function getGray(&$im2, $x,$y)
	{
		$rgb = $this->getColor($im2, $x, $y);
		return( ($rgb[0]+$rgb[1]+$rgb[2])/3 );
	}

}