<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/libs/qr_img0.50i/php/class.qr_img.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/Geometry/class.ilScanAssessmentPoint.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/Geometry/class.ilScanAssessmentLine.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/Geometry/class.ilScanAssessmentVector.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/class.ilScanAssessmentImageHelper.php';

/**
 * Class ilScanAssessmentMarkerDetection
 * @author Guido Vollbach <gvollbach@databay.de>
 */

class ilScanAssessmentMarkerDetection
{

	/**
	 * @var bool
	 */
	protected $debug = false;
	
	/**
	 * @var
	 */
	protected $temp_image;

	/**
	 * @var
	 */
	protected $image;

	/**
	 * @var
	 */
	protected $threshold;

	
	protected $image_helper;
	
	/**
	 * ilScanAssessmentMarkerDetection constructor.
	 */
	public function __construct()
	{
		$this->image_helper = new ilScanAssessmentImageHelper();
	}

	/**
	 * @param $x
	 * @param $y
	 * @param $color
	 */
	protected function setDebugPixelFromPoint($x, $y , $color)
	{
		if($this->isDebug())
		{
			imagesetpixel($this->getTempImage(), $x, $y, $color);
		}
	}

	/**
	 * @param ilScanAssessmentVector $vector
	 */
	protected function createDebugImageFromVector(ilScanAssessmentVector $vector)
	{
		if($this->isDebug())
		{
			imagerectangle($this->getTempImage(),
				$vector->getPosition()->getX() - $vector->getLength() / 2,
				$vector->getPosition()->getY() - $vector->getLength() / 2,
				$vector->getPosition()->getX() + $vector->getLength() / 2,
				$vector->getPosition()->getY() + $vector->getLength() / 2, 0x0000dd);
		}

	}

	/**
	 * @param      $im
	 * @param bool $rotated
	 * @param int  $threshold
	 * @return array|bool
	 */
	public function findMarker(&$im, $rotated = false, $threshold=150) {

		$white = imagecolorallocate($im, 255,255,255);

		$scan_top_left = $this->findLeft($im, 'top', $threshold);

		if($scan_top_left === false)
		{
			if($rotated) 
			{
				return false;
			}

			$im = imagerotate($im,180, $white);
			$scan_top_left = $this->findLeft($im, 'top', $threshold);
		}

		if($scan_top_left !== false) {
			/** @var ilScanAssessmentVector $locate_top_left */
			$locate_top_left = $this->findExactLeft($im, $scan_top_left, $threshold);
			$scan_bottom_left = $this->findLeft($im, 'bottom', $threshold);

			if($scan_bottom_left !== false) 
			{
				/** @var ilScanAssessmentVector $locate_bottom_left */
				$locate_bottom_left = $this->findExactLeft($im, $scan_bottom_left, $threshold);
				$dx = $locate_bottom_left->getPosition()->getX() - $locate_top_left->getPosition()->getX();
				$dy = $locate_bottom_left->getPosition()->getY() - $locate_top_left->getPosition()->getY();

				$rotation = 180 / 3.141592 * atan($dx / $dy);

				if($rotated==false && abs($rotation)>0.05) 
				{
					
					$imSIK = imagecreatetruecolor(imagesx($im), imagesy($im));
					imagecopy($imSIK, $im, 0,0,0,0,imagesx($im), imagesy($im));
					$im = imagerotate($imSIK, -$rotation, $white);

					$randX = min($locate_bottom_left->getPosition()->getX(), $locate_top_left->getPosition()->getX());
					$randY = $locate_top_left->getPosition()->getY();

					$im2 = imagecreatetruecolor(imagesx($im)-$randX/2, imagesy($im)-$randY/2);
					$white = imagecolorallocate($im2, 255,255,255);

					imagefilledrectangle($im2, 0,0,imagesx($im2), imagesy($im2), $white);
					imagecopy($im2, $im, 0,0,$randX/2, $randY/2, imagesx($im2), imagesy($im2));
					$im = $im2;

					return $this->findMarker($im, true, $threshold);
				} 
				else 
				{
					$this->createDebugImageFromVector($locate_top_left);
					$this->createDebugImageFromVector($locate_bottom_left);
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
	public function findExactLeft(&$im, $find, $threshold) 
	{
		$dx =  $find->getEnd()->getX() - $find->getStart()->getX();
		$dy =  $find->getEnd()->getY() - $find->getStart()->getY();

		$mx = $find->getStart()->getX()+$dx/2;
		$my = $find->getStart()->getY()+$dy/2;

		$dx2 = $dy; // Rotation um 90 Grad in 2D
		$dy2 = -$dx;

		for($i=0.1;$i<2;$i+=0.1)
		{
			$gray = $this->image_helper->getGray($im, $mx+$dx2*$i, $my+$dy2*$i);
			if($gray > $threshold)
			{
				$i1 = $i;
				break;
			}
		}
		for($i=0.1;$i<2;$i+=0.1) 
		{
			$gray = $this->image_helper->getGray($im, $mx-$dx2*$i, $my-$dy2*$i);
			if($gray > $threshold)
			{
				$i2 = $i;
				break;
			}
		}

		$x2 = $mx+$dx2*$i1;
		$y2 = $my+$dy2*$i1;

		$dx2 = -$x2 + ($mx-$dx2*$i2);
		$dy2 = -$y2 + ($my-$dy2*$i2);

		$len2 = sqrt($dx2*$dx2 + $dy2*$dy2);

		#$dx2 =

		imageline($this->getTempImage(), $x2, $y2, $x2+$dx2, $y2+$dy2, 0xffff00);

		return new ilScanAssessmentVector(new ilScanAssessmentPoint($x2+$dx2/2, $y2+$dy2/2), $len2);
	}

	/**
	 * @param        $im
	 * @param string $topbottom
	 * @param int    $threshold
	 * @return bool|ilScanAssessmentLine
	 */
	public function findLeft(&$im, $topbottom='top', $threshold=150) {
		
		$w = imagesx($im);

		$dy = 3;

		$found = false;
		$subDX = 0;
		$beginD = -1;
		/** @var ilScanAssessmentPoint $found_start */
		$found_start = null;
		/** @var ilScanAssessmentPoint $found_end */
		$found_end =  null;
		for($d=55; $d < $w / 4 * 3; $d += $dy) {

			$len = $this->pseudoRayTraceLength($d, $im, $topbottom, $threshold);

			if( ($beginD == -1 && $len < $d/3*2 ) ||
				($beginD != -1 && $len - $subDX/2 <= $beginD)
			)
			{
				if($found == false)
				{
					$found = true;
					$subDX = 0;
					$beginD = $len;
					$found_start = new ilScanAssessmentPoint($len, $d-$len);
					$found_end =  new ilScanAssessmentPoint($len, $d-$len);
				}
				else
				{
					$subDX += $dy;
					$found_end =  new ilScanAssessmentPoint($len, $d-$len);
				}
				if($topbottom=='top') 
				{
					imageline($this->getTempImage(), 0 + $subDX / 2, $d - $subDX / 2, $len, $d - $len, 0xffff00);
				} 
				else 
				{
					imageline($this->getTempImage(), 0 + $subDX / 2, imagesy($im)- ($d - $subDX / 2), $len, imagesy($im)- ($d - $len), 0xffff00);
				}
			}
			else
			{
				if($found == true) 
				{
					$found_line = new ilScanAssessmentLine($found_start, $found_end);
					$l = sqrt( ($found_start->getX() - $found_end->getX())*($found_start->getX() - $found_end->getX()) + ($found_start->getY() - $found_end->getY())*($found_start->getY() - $found_end->getY()) );

					if($l > 0) 
					{
						if (($w / $l) > 35 && ($w / $l) < 100)
						{
							
							imageline($this->getTempImage(), $found_line->getStart()->getX(), $found_line->getStart()->getY(), $found_line->getEnd()->getX(), $found_line->getEnd()->getY(), 0x330033);
							if($topbottom=="bottom")
							{
								$found_line->getStart()->setY(imagesy($im) - $found_line->getStart()->getY());
								$found_line->getEnd()->setY(imagesy($im) - $found_line->getEnd()->getY());
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
	public function pseudoRayTraceLength($d, &$im, $top_bottom='top', $threshold)
	{
		for($x=0;$x<$d;$x++)
		{
			if($top_bottom=="top")
			{
				$y = $d-$x;
			}
			else
			{
				$y = imagesy($im)-$d+$x;
			}

			$gray = $this->image_helper->getGray($im, $x, $y);

			if($gray < $threshold) {

				$len = 15;
				$mean = $gray;
				for($i=1;$i<$len;$i++)
				{
					if($top_bottom=="top")
					{
						$y2 = $d-($x+$i);
					}
					else
					{
						$y2 = imagesy($im)-$d+($x+$i);
					}
					$mean += $this->image_helper->getGray($im, $x, $y2);
				}
				if($mean/$len < $threshold)
				{
					return $x;
				}
			}
			$this->setDebugPixelFromPoint($x, $y, 0x000fff);
		}
		return $x;
	}


	
	public function getMarkerPosition($fn)
	{
		$im = imagecreatefromjpeg($fn);

		$this->setImage($im);
		$this->setTempImage($im);

		$this->setThreshold(150);
		$marker = $this->findMarker($im, FALSE, $this->getThreshold());
		if($marker===FALSE)
		{
			$this->setThreshold(200);
			$im = imagecreatefromjpeg($fn);

			$marker = $this->findMarker($im, FALSE, $this->getThreshold());
		}

		return $marker;
	}


	/**
	 * @return mixed
	 */
	public function getTempImage()
	{
		return $this->temp_image;
	}

	/**
	 * @param mixed $temp_image
	 */
	public function setTempImage($temp_image)
	{
		$this->temp_image = $temp_image;
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
	 * @return mixed
	 */
	public function getThreshold()
	{
		return $this->threshold;
	}

	/**
	 * @param mixed $threshold
	 */
	public function setThreshold($threshold)
	{
		$this->threshold = $threshold;
	}

	/**
	 * @return boolean
	 */
	public function isDebug()
	{
		return $this->debug;
	}

	/**
	 * @param boolean $debug
	 */
	public function setDebug($debug)
	{
		$this->debug = $debug;
	}


}