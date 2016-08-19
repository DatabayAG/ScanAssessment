<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/class.ilScanAssessmentImageHelper.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/geometry/class.ilScanAssessmentPoint.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/geometry/class.ilScanAssessmentLine.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/geometry/class.ilScanAssessmentVector.php';

/**
 * Class ilScanAssessmentScanner
 */
class ilScanAssessmentScanner
{
	const LOWER_THRESHOLD	= 150;
	const HIGHER_THRESHOLD	= 200;

	/**
	 * @var bool
	 */
	protected $debug = true;

	/**
	 * @var
	 */
	protected $temp_image;

	/**
	 * @var
	 */
	protected $image;

	/**
	 * @var int
	 */
	protected $threshold;

	/**
	 * @var ilScanAssessmentImageHelper
	 */
	protected $image_helper;

	/**
	 * ilScanAssessmentScanner constructor.
	 * @param $fn
	 */
	public function __construct($fn)
	{
		if($this->getImage() === null)
		{
			$im = imagecreatefromjpeg($fn);
			$this->setImage($im);
			$this->setTempImage($im);
			$this->setThreshold(self::LOWER_THRESHOLD);
			$this->image_helper = new ilScanAssessmentImageHelper();
		}
	}

	/**
	 * @param ilScanAssessmentLine $line
	 * @param $color
	 */
	protected function drawDebugLine($line, $color)
	{
		if($this->isDebug())
		{
			imageline(
				$this->getTempImage(), $line->getStart()->getX(), $line->getStart()->getY(),
				$line->getEnd()->getX(), $line->getEnd()->getY(),
				$color
			);
		}
	}

	/**
	 * @param ilScanAssessmentPoint $point
	 * @param $color
	 */
	protected function drawDebugPixel($point , $color)
	{
		if($this->isDebug())
		{
			imagesetpixel($this->getTempImage(), $point->getX(), $point->getY(), $color);
		}
	}

	/**
	 * @param ilScanAssessmentVector $vector
	 */
	protected function drawDebugSquareFromVector(ilScanAssessmentVector $vector)
	{
		if($this->isDebug())
		{
			imagerectangle($this->getTempImage(),
				$vector->getPosition()->getX() - $vector->getLength() / 2,
				$vector->getPosition()->getY() - $vector->getLength() / 2,
				$vector->getPosition()->getX() + $vector->getLength() / 2,
				$vector->getPosition()->getY() + $vector->getLength() / 2,
				0x0000dd);
		}
	}

	/**
	 * @param ilScanAssessmentPoint $first
	 * @param ilScanAssessmentPoint $second
	 */
	protected function drawDebugSquareFromTwoPoints(ilScanAssessmentPoint $first, ilScanAssessmentPoint $second)
	{
		if($this->isDebug())
		{
			imagerectangle($this->getTempImage(), $first->getX(), $first->getY(), $second->getX(), $second->getY(), 0x0000dd);
		}
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