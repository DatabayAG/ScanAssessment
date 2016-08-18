<?php

/**
 * Created by PhpStorm.
 * User: gvollbach
 * Date: 18.08.16
 * Time: 14:34
 */
class ilScanAssessmentQrCode
{
	public function getQRPosition($fn) {
		$im = imagecreatefromjpeg($fn);

		$res = $this->findqr($im, false);

		if($res===FALSE) return FALSE;
		$foundAt = $res[0];
		return $foundAt;
	}

	public function findqr($im, $rotate=false) {

		$threshold = $this->getThreshold();
		$red = imageColorAllocate($im, 255,0,0);
		if($rotate) {

			$im = imageRotate($im, 180, $red);
		}

		$im2 = $im;

		$w = imageSx($im);
		$h = imageSy($im);

		$w2 = $w-5;
		$h2 = $h-5;
		$x = $w-5;
		$y = $h-5;
		$nachLinks = 0;
		$found = false;
		$step = 2;

		while($found == false && $x-$nachLinks>$w2/2) {
			$nachLinks += 10;
			$i=0;
			for($xi=$x-$nachLinks;$xi<$w2-20;$xi+=$step) {
				if($i>=20) {
					$c = $this->getGray($im2, $xi, $y - $i);
					$c2 = $this->getGray($im2, $xi + 1, $y - $i - 1);
					if ($c < $threshold && $c2 < $threshold ) {
						$found = TRUE;
						break;
					}
				}
				$i+=$step;
			}
		}

		if($found==true) {
			$foundX = $xi;
			$foundY = $y-$i;

			$x2 = $xi + ($w2-$xi)/2;
			$y2 = ($y-$i) + ($h2-($y-$i))/2;

			$found = false;
			for($xi=$x2;$xi>=$foundX;$xi--) {
				for($yi=$y2;$yi>=$foundY-($h2-$y2)*5;$yi-=1) {
					$c = $this->getGray($im2, $xi,$yi);
					if($c< $threshold) {
						$found = true;
						break;
					}
				}
				if($found) break;
			}
			$fixedMaxX = $xi;

			$found = false;
			for($yi=$y2;$yi>=$foundY-($h2-$y2)*5;$yi--) {
				for($xi=$x2;$xi>=$foundX;$xi--) {
					$c = $this->getGray($im2, $xi,$yi);
					if($c< $threshold) {
						$found = true;
						break;
					}
				}
				if($found) break;
			}
			$fixedMaxY = $yi;
			$fixedMaxX = $fixedMaxX + 15;
			$fixedMaxY = $fixedMaxY + 15;

			$found = false;
			for($sq=$w2/10;$sq<$w2/2;$sq += 3) {
				if($this->isSquareFree($im2, $fixedMaxX, $fixedMaxY, $sq)) {
					$found = true;
					break;
				}
			}

			$cord = array("x" => $fixedMaxX, "y" => $fixedMaxY, "w" => $sq);

			if($found)
			{
				$sq += 10;
				$im3 = imageCreateTrueColor($sq, $sq);
				imageCopy($im3, $im2, 0,0, $fixedMaxX-$sq, $fixedMaxY-$sq, $sq, $sq);
				imageRectangle($this->getTempImage(), $fixedMaxX, $fixedMaxY, $fixedMaxX-$sq, $fixedMaxY-$sq, $red);

			} else return false;


		} else return false;

		return array($cord, $im3);
	}



	public function isSquareFree($im, $sx, $sy, $sq) {
		$threshold = $this->getThreshold();
		$s = 1;
		for($x=$sx;$x>$sx-$sq;$x-=$s) {
			$c = $this->getGray($im, $x,$sy-$sq);
			if($c<$threshold) return false;
		}
		for($y=$sy;$y>$sy-$sq;$y-=$s) {
			$c = $this->getGray($im, $sx-$sq,$y);
			if($c<$threshold) return false;
		}
		return true;
	}
}