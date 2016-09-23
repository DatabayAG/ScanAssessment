<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/imageWrapper/class.ilScanAssessmentGDImageHelper.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/imageWrapper/class.ilScanAssessmentImagemagickImageHelper.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/imageWrapper/class.ilScanAssessmentGraphicsmagickImageHelper.php';
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
	 * @var ilScanAssessmentGDImageHelper
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
			/**
			 * @var ilScanAssessmentGDImageHelper
			 */
			$this->image_helper = new ilScanAssessmentGDImageHelper($fn);
			/**
			 * @var ilScanAssessmentImagemagickImageHelper
			 */
			#$this->image_helper = new ilScanAssessmentImagemagickImageHelper($fn);
			/**
			 * @var ilScanAssessmentGraphicsmagickImageHelper
			 */
			#$this->image_helper = new ilScanAssessmentGraphicsmagickImageHelper($fn);
			$im = $this->image_helper->removeBlackBorder();
			$this->setImage($im);
			$this->setTempImage($im);
			$this->setThreshold(self::LOWER_THRESHOLD);
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
			$this->image_helper->drawLine($this->getTempImage(), $line->getStart()->getX(), $line->getStart()->getY(),
				$line->getEnd()->getX(), $line->getEnd()->getY(),
				$color);
		}
	}

	/**
	 * @param ilScanAssessmentPoint $point
	 * @param $color
	 */
	protected function drawDebugPixel($point, $color)
	{
		if($this->isDebug())
		{
			$this->image_helper->drawPixel($this->getTempImage(), $point, $color);
		}
	}

	/**
	 * @param ilScanAssessmentVector $vector
	 */
	protected function drawDebugSquareFromVector(ilScanAssessmentVector $vector)
	{
		if($this->isDebug())
		{
			$this->image_helper->drawSquareFromVector($this->getTempImage(), $vector, 0x0000dd);
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
			$this->image_helper->drawSquareFromTwoPoints($this->getTempImage(), $first, $second, 0x0000dd);
		}
	}

	/**
	 * @param $img
	 * @param $fn
	 */
	public function drawTempImage($img, $fn)
	{
		$this->image_helper->drawTempImage($img, $fn);
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