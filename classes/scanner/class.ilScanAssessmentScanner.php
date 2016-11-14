<?php
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/imageWrapper/class.ilScanAssessmentGDWrapper.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/imageWrapper/class.ilScanAssessmentImagemagickWrapper.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/geometry/class.ilScanAssessmentPoint.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/geometry/class.ilScanAssessmentLine.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/geometry/class.ilScanAssessmentVector.php');
ilScanAssessmentPlugin::getInstance()->includeClass('log/class.ilScanAssessmentLog.php');
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
	 * @var ilScanAssessmentImageWrapper
	 */
	public $image_helper;

	/**
	 * @var ilScanAssessmentLog
	 */
	protected $log;

	/**
	 * ilScanAssessmentScanner constructor.
	 * @param $fn
	 */
	public function __construct($fn)
	{
		if($this->getImage() === null)
		{
			/**
			 * @var ilScanAssessmentGDWrapper
			 */
			$this->image_helper = new ilScanAssessmentGDWrapper($fn);
			/**
			 * @var ilScanAssessmentImagemagickWrapper
			 */
			#$this->image_helper = new ilScanAssessmentImagemagickWrapper($fn);
			$im = $this->image_helper->removeBlackBorder();
			$this->setImage($im);
			$this->setTempImage($im);
			$this->setThreshold(self::LOWER_THRESHOLD);
			$this->log = ilScanAssessmentLog::getInstance();
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
			$this->image_helper->drawSquareFromVector($this->getTempImage(), $vector, $this->image_helper->getBlue());
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
			$this->image_helper->drawSquareFromTwoPoints($this->getTempImage(), $first, $second, $this->image_helper->getBlue());
		}
	}

	/**
	 * @param $img
	 * @param $path_to_file
	 */
	public function drawTempImage($img, $path_to_file)
	{
		$this->image_helper->drawTempImage($img, $path_to_file);
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