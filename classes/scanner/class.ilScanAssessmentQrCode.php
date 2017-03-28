<?php

ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentScanner.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/geometry/class.ilScanAssessmentVector.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/geometry/class.ilScanAssessmentPoint.php');

/**
 * Class ilScanAssessmentQrCode
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentQrCode extends ilScanAssessmentScanner
{

	/**
	 * ilScanAssessmentQrCode constructor.
	 * @param $fn
	 */
	public function __construct($fn)
	{
		parent::__construct($fn);
	}

	/**
	 * @return bool|ilScanAssessmentVector
	 */
	public function getQRPosition() 
	{
		$im = $this->getImage();
		$res = $this->findQR($im, false);

		if($res === false)
		{
			return false;
		} 

		return $res;
	}

	/**
	 * @param      $im
	 * @param bool $rotate
	 * @return array|bool
	 */
	protected function findQR(&$im, $rotate = false) {

		$threshold = $this->getThreshold();

		if($rotate)
		{
			$im = $this->image_helper->rotate(180);
		}

		$w = $this->image_helper->getImageSizeX();
		$h = $this->image_helper->getImageSizeY();

		$w2		= $w - 5;
		$h2		= $h - 5;
		$x		= $w - 5;
		$y		= $h - 5;
		$toLeft	= 0;
		$found	= false;
		$step	= 2;

		while($found == false && $x - $toLeft > $w2 / 2) 
		{
			$toLeft	+= 10;
			$i		= 0;
			for($xi = $x - $toLeft; $xi < $w2 - 20; $xi += $step) 
			{
				if($i>=20) 
				{
					$c = $this->image_helper->getGrey(new ilScanAssessmentPoint($xi, $y - $i));
					$c2 = $this->image_helper->getGrey(new ilScanAssessmentPoint($xi + 1, $y - $i - 1));
					if ($c < $threshold && $c2 < $threshold ) 
					{
						$found = true;
						break;
					}
				}
				$i += $step;
			}
		}

		if($found == true) 
		{
			$foundX = $xi;
			$foundY = $y - $i;

			$x2 = $xi + ($w2 - $xi) / 2;
			$y2 = ($y - $i) + ($h2 - ($y - $i)) / 2;

			$found = false;
			for($xi = $x2; $xi >= $foundX; $xi--) 
			{
				for($yi = $y2; $yi >= $foundY - ($h2 - $y2) * 5; $yi -= 1)
				{
					$c = $this->image_helper->getGrey(new ilScanAssessmentPoint($xi, $yi));
					if($c < $threshold)
					{
						$found = true;
						break;
					}
				}
				if($found) break;
			}
			$fixedMaxX = $xi;

			$found = false;
			for($yi = $y2; $yi >= $foundY - ($h2 - $y2) * 5; $yi--)
			{
				for($xi = $x2; $xi >= $foundX; $xi--)
				{
					$c = $this->image_helper->getGrey(new ilScanAssessmentPoint($xi, $yi));
					if($c < $threshold)
					{
						$found = true;
						break;
					}
				}
				if($found) break;
			}
			$fixedMaxY = $yi;
			$fixedMaxX = $fixedMaxX + 20;
			$fixedMaxY = $fixedMaxY + 20;

			$found = false;
			for($sq = $w2 / 10; $sq < $w2 / 2; $sq += 3)
			{
				if($this->isSquareFree($im, $fixedMaxX, $fixedMaxY, $sq))
				{
					$found = true;
					break;
				}
			}
			
			$vec = new ilScanAssessmentVector( new ilScanAssessmentPoint($fixedMaxX - $sq, $fixedMaxY - $sq), $sq);

			if($found)
			{
				$sq += 10;
				$this->drawDebugSquareFromTwoPoints(new ilScanAssessmentPoint($fixedMaxX, $fixedMaxY), new ilScanAssessmentPoint($fixedMaxX-$sq, $fixedMaxY-$sq));

			} else return false;


		} else return false;

		return array('crop' => $vec, 'end' => new ilScanAssessmentPoint($foundX, $foundY));
	}



	protected function isSquareFree(&$im, $sx, $sy, $sq) {
		$threshold = 150;
		$s         = 1;
		for($x = $sx; $x > $sx - $sq; $x -= $s)
		{
			$c = $this->image_helper->getGrey(new ilScanAssessmentPoint($x, $sy - $sq));
			if($c < $threshold)
			{
				return false;
			}
		}
		for($y = $sy; $y > $sy - $sq; $y -= $s)
		{
			$c = $this->image_helper->getGrey(new ilScanAssessmentPoint($sx - $sq, $y));
			if($c < $threshold)
			{
				return false;
			}
		}
		return true;
	}
}