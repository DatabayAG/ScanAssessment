<?php

require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/class.ilScanAssessmentScanner.php';


/**
 * Class ilScanAssessmentMarkerDetection
 * @author Guido Vollbach <gvollbach@databay.de>
 */

class ilScanAssessmentMarkerDetection extends ilScanAssessmentScanner
{

	public function __construct($fn)
	{
		parent::__construct($fn);
	}

	/**
	 * @return array|bool
	 */
	public function getMarkerPosition()
	{

		$im = $this->getImage();

		$this->setThreshold(self::LOWER_THRESHOLD);
		$marker = $this->findMarker($im, false, $this->getThreshold());

		if($marker === false)
		{
			$this->setThreshold(self::HIGHER_THRESHOLD);
			$marker = $this->findMarker($im, false, $this->getThreshold());
		}

		return $marker;
	}

	/**
	 * @param      $im
	 * @param bool $rotated
	 * @param int  $threshold
	 * @var ilScanAssessmentVector $locate_top_left
	 * @var ilScanAssessmentVector $locate_bottom_left
	 * @return array|bool
	 */
	public function findMarker(&$im, $rotated = false, $threshold = 150) {

		$locate_top_left = null;
		$locate_bottom_left = null;

		$scan_top_left = $this->probeMarkerPosition($im, 'top', $threshold);

		if($scan_top_left === false)
		{
			if($rotated) 
			{
				return false;
			}
			
			#$im = $this->image_helper->rotate($im,180);
			$scan_top_left = $this->probeMarkerPosition($im, 'top', $threshold);
		}

		if($scan_top_left !== false) {

			$locate_top_left = $this->detectExactMarkerPosition($im, $scan_top_left, $threshold);
			$scan_bottom_left = $this->probeMarkerPosition($im, 'bottom', $threshold);

			if($scan_bottom_left !== false) 
			{
				$locate_bottom_left = $this->detectExactMarkerPosition($im, $scan_bottom_left, $threshold);
				$dx = $locate_bottom_left->getPosition()->getX() - $locate_top_left->getPosition()->getX();
				$dy = $locate_bottom_left->getPosition()->getY() - $locate_top_left->getPosition()->getY();

				$rotation = 180 / 3.141592 * atan($dx / $dy);

				if($rotated==false && abs($rotation) > 0.05) 
				{
					$im = $this->tryToDetectMarkerByRotatingTheImage($im, $rotation, $locate_bottom_left, $locate_top_left);
					return $this->findMarker($im, true, $threshold);
				} 
				else 
				{
					$this->drawDebugSquareFromVector($locate_top_left);
					$this->drawDebugSquareFromVector($locate_bottom_left);
					return array($locate_top_left, $locate_bottom_left);
				}
			}
		} 
		else 
		{
			return false;
		}
		return false;
	}

	/**
	 * @param $im
	 * @param ilScanAssessmentLine $find
	 * @param $threshold
	 * @return ilScanAssessmentVector
	 */
	public function detectExactMarkerPosition(&$im, $find, $threshold) 
	{
		$i1 = 0;
		$i2 = 0;

		$dx = $find->getEnd()->getX() - $find->getStart()->getX();
		$dy = $find->getEnd()->getY() - $find->getStart()->getY();

		$mx = $find->getStart()->getX() + $dx / 2;
		$my = $find->getStart()->getY() + $dy / 2;

		$dx2 = $dy; // Rotation um 90 Grad in 2D
		$dy2 = -$dx;

		for($i = 0.1; $i < 2; $i += 0.1)
		{
			$gray = $this->image_helper->getGrey(new ilScanAssessmentPoint($mx + $dx2 * $i, $my + $dy2 * $i));
			if($gray > $threshold)
			{
				$i1 = $i;
				break;
			}
		}

		for($i = 0.1; $i < 2; $i += 0.1) 
		{
			$gray = $this->image_helper->getGrey(new ilScanAssessmentPoint($mx - $dx2 * $i, $my - $dy2 * $i));
			if($gray > $threshold)
			{
				$i2 = $i;
				break;
			}
		}

		$x2 = $mx + $dx2 * $i1;
		$y2 = $my + $dy2 * $i1;

		$dx2 = -$x2 + ($mx - $dx2 * $i2);
		$dy2 = -$y2 + ($my - $dy2 * $i2);

		$len2 = sqrt($dx2 * $dx2 + $dy2 * $dy2);

		$this->drawDebugLine(new ilScanAssessmentLine(new ilScanAssessmentPoint($x2, $y2), new ilScanAssessmentPoint($x2+$dx2, $y2+$dy2)), 0x000055);

		return new ilScanAssessmentVector(new ilScanAssessmentPoint($x2+$dx2/2, $y2+$dy2/2), $len2);
	}

	/**
	 * @param        $im
	 * @param string $top_bottom
	 * @param int    $threshold
	 * @var ilScanAssessmentPoint $found_start
	 * @var ilScanAssessmentPoint $found_end
	 * @return bool|ilScanAssessmentLine
	 */
	public function probeMarkerPosition(&$im, $top_bottom='top', $threshold = 150) 
	{
		$width			= $this->image_helper->getImageSizeX();
		$step_size		= 3;
		$found			= false;
		$subDX			= 0;
		$beginD			= -1;
		$found_start	= null;
		$found_end		= null;
		$max_width		= $width / 4 * 3;

		for($d=55; $d < $max_width; $d += $step_size) 
		{
			$len = $this->getLengthFromScanLine($d, $im, $top_bottom, $threshold);

			if( ($beginD == -1 && $len < $d/3*2 ) ||
				($beginD != -1 && $len - $subDX/2 <= $beginD)
			)
			{
				if($found == false)
				{
					$found			= true;
					$subDX			= 0;
					$beginD			= $len;
					$found_start	= new ilScanAssessmentPoint($len, $d-$len);
					$found_end		= new ilScanAssessmentPoint($len, $d-$len);
				}
				else
				{
					$subDX += $step_size;
					$found_end =  new ilScanAssessmentPoint($len, $d-$len);
				}
				if($top_bottom=='top') 
				{
					$this->drawDebugLine(new ilScanAssessmentLine(new ilScanAssessmentPoint(0 + $subDX / 2, $d - $subDX / 2), new ilScanAssessmentPoint( $len, $d - $len)), 0xffff00);
				} 
				else 
				{
					$this->drawDebugLine(new ilScanAssessmentLine(new ilScanAssessmentPoint(0 + $subDX / 2, $this->image_helper->getImageSizeY($im)- ($d - $subDX / 2)), new ilScanAssessmentPoint($len, $this->image_helper->getImageSizeY($im)- ($d - $len))), 0xffff00);
				}
			}
			else
			{
				if($found == true) 
				{
					$found_line = new ilScanAssessmentLine($found_start, $found_end);
					$length = sqrt( 
								($found_start->getX() - $found_end->getX()) *
								($found_start->getX() - $found_end->getX()) + 
								($found_start->getY() - $found_end->getY()) *
								($found_start->getY() - $found_end->getY()) 
							);

					if($length > 0) 
					{
						if (($width / $length) > 35 && ($width / $length) < 100)
						{
							$this->drawDebugLine($found_line, 0xff0033);

							if($top_bottom=="bottom")
							{
								$found_line->getStart()->setY( $this->image_helper->getImageSizeY() - $found_line->getStart()->getY() );
								$found_line->getEnd()->setY( $this->image_helper->getImageSizeY() - $found_line->getEnd()->getY() );
							}
							return $found_line;
							break;
						}
					}
				}
				$found = false;
			}
		}
		return false;
	}

	/**
	 * @param        $d
	 * @param        $im
	 * @param string $top_bottom
	 * @param        $threshold
	 * @return int
	 */
	public function getLengthFromScanLine($d, &$im, $top_bottom = 'top', $threshold)
	{
		for($x = 0; $x < $d; $x++)
		{
			if($top_bottom == 'top')
			{
				$y = $d - $x;
			}
			else
			{
				$y = $this->image_helper->getImageSizeY() - $d + $x;
			}

			$gray = $this->image_helper->getGrey(new ilScanAssessmentPoint($x, $y));

			if($gray < $threshold) {

				$len = 15;
				$mean = $gray;
				for($i = 1; $i < $len; $i += 3)
				{
					if($top_bottom == 'top')
					{
						$y2 = $d - ($x + $i);
					}
					else
					{
						$y2 =  $this->image_helper->getImageSizeY() - $d + ($x + $i);
					}
					$mean += $this->image_helper->getGrey(new ilScanAssessmentPoint($x, $y2));
				}
				if($mean / $len < $threshold)
				{
					return $x;
				}
			}
			$this->drawDebugPixel(new ilScanAssessmentPoint($x, $y), 0x000fff);
		}
		return $x;
	}
	
	/**
	 * @param $im
	 * @param $rotation
	 * @param ilScanAssessmentVector $locate_bottom_left
	 * @param ilScanAssessmentVector $locate_top_left
	 * @return resource
	 */
	protected function tryToDetectMarkerByRotatingTheImage(&$im, $rotation, $locate_bottom_left, $locate_top_left)
	{
		/*$white = imagecolorallocate($im, 255,255,255);

		$imSIK = imagecreatetruecolor($this->image_helper->getImageSizeX($im),  $this->image_helper->getImageSizeY($im));
		imagecopy($imSIK, $im, 0, 0, 0, 0, $this->image_helper->getImageSizeX($im),  $this->image_helper->getImageSizeY($im));
		$im = imagerotate($imSIK, -$rotation, $white);

		$randX = min($locate_bottom_left->getPosition()->getX(), $locate_top_left->getPosition()->getX());
		$randY = $locate_top_left->getPosition()->getY();

		$im2   = imagecreatetruecolor(imagesx($im) - $randX / 2, imagesy($im) - $randY / 2);
		$white = imagecolorallocate($im2, 255, 255, 255);

		imagefilledrectangle($im2, 0, 0, $this->image_helper->getImageSizeX($im2),  $this->image_helper->getImageSizeY($im2), $white);
		imagecopy($im2, $im, 0, 0, $randX / 2, $randY / 2, $this->image_helper->getImageSizeX($im2),  $this->image_helper->getImageSizeY($im2));
		$im = $im2;
		return $im;*/
	}
	
}