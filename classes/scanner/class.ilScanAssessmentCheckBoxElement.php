<?php
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentScanner.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/geometry/class.ilScanAssessmentArea.php');
ilScanAssessmentPlugin::getInstance()->includeClass('class.ilScanAssessmentGlobalSettings.php');


/**
 * Class ilScanAssessmentCheckBoxElement
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentCheckBoxElement
{
	const BOX_SIZE = 5;
	const CHECKED = 2;
	const UNCHECKED = 1;
	const UNTOUCHED = 0;
	const RECURSIVE_CALL = 50;
	const SEARCH_ROUNDS = 10;
	const SEARCH_INCREMENT = 3;

	/**
	 * @var array
	 */

	protected $color_mapping;
	/**
	 * @var ilScanAssessmentPoint
	 */
	protected $first_point;

	/**
	 * @var ilScanAssessmentPoint
	 */
	protected $second_point;

	/**
	 * @var ilScanAssessmentImageWrapper
	 */
	protected $image_helper;

	/**
	 * @var int
	 */
	protected $min_value_black;

	/**
	 * @var float
	 */
	protected $min_marked_area;

	/**
	 * @var float
	 */
	protected $marked_area_checked;

	/**
	 * @var float
	 */
	protected $marked_area_unchecked;

	/**
	 * @var int
	 */
	protected $correction_length;

	/**
	 * @var float|int
	 */
	protected $search_rounds;

    /**
	 * ilScanAssessmentCheckBoxElement constructor.
	 * @param ilScanAssessmentPoint        $first_point
	 * @param ilScanAssessmentPoint        $second_point
	 * @param ilScanAssessmentImageWrapper $image_helper
	 */
	public function __construct($first_point, $second_point, $image_helper)
	{
		$this->first_point       = $first_point;
		$this->second_point      = $second_point;
		$this->image_helper      = $image_helper;
		$this->color_mapping     = array(
			self::UNTOUCHED => $this->image_helper->getYellow(),
			self::UNCHECKED => $this->image_helper->getPink(),
			self::CHECKED   => $this->image_helper->getGreen()
		);
		$this->correction_length     = ($this->image_helper->getImageSizeY() / 297) * 1.43846153846;
		$this->search_rounds         = ($this->image_helper->getImageSizeY() / 297);
		$this->min_value_black       = ilScanAssessmentGlobalSettings::getInstance()->getMinValueBlack();
		$this->min_marked_area       = ilScanAssessmentGlobalSettings::getInstance()->getMinMarkedArea();
		$this->marked_area_checked   = ilScanAssessmentGlobalSettings::getInstance()->getMarkedAreaChecked();
		$this->marked_area_unchecked = ilScanAssessmentGlobalSettings::getInstance()->getMarkedAreaUnchecked();
	}

	/**
	 * @return ilScanAssessmentPoint
	 */
	public function getLeftBottom()
	{
		return new ilScanAssessmentPoint($this->getFirstPoint()->getX(), $this->getSecondPoint()->getY());
	}

	/**
	 * @return ilScanAssessmentPoint
	 */
	public function getRightTop()
	{
		return new ilScanAssessmentPoint($this->getSecondPoint()->getX(), $this->getFirstPoint()->getY());
	}

	/**
	 * @return ilScanAssessmentPoint
	 */
	public function getFirstPoint()
	{
		return $this->first_point;
	}

	/**
	 * @param ilScanAssessmentPoint $first_point
	 */
	public function setFirstPoint($first_point)
	{
		$this->first_point = $first_point;
	}

	/**
	 * @return ilScanAssessmentPoint
	 */
	public function getSecondPoint()
	{
		return $this->second_point;
	}

	/**
	 * @param ilScanAssessmentPoint $second_point
	 */
	public function setSecondPoint($second_point)
	{
		$this->second_point = $second_point;
	}

	/**
	 * @param      $im
	 * @param bool $mark
	 * @return ilScanAssessmentArea
	 */
	protected function analyseCheckBox($im, $mark = false)
	{
		$black = 0;
		$white = 0;
		$total = 0;
		for($x = $this->getFirstPoint()->getX(); $x < $this->getSecondPoint()->getX(); $x++)
		{
			for($y = $this->getFirstPoint()->getY(); $y < $this->getSecondPoint()->getY(); $y++)
			{
				$total++;
				$gray = $this->image_helper->getGrey(new ilScanAssessmentPoint($x, $y));
				if($gray < $this->min_value_black)
				{
					$black++;
					if($mark)
					{
						$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x, $y), $this->image_helper->getBlack());
					}
				}
				else
				{
					$white++;
				}
			}
		}
		ilScanAssessmentLog::getInstance()->debug(sprintf('Checkbox pixels total %s, black %s, white %s.', $total, $black, $white));
		return new ilScanAssessmentArea($total, $white, $black);
	}

	/**
	 * @param      $im
	 * @param bool $mark
	 * @return int
	 */
	public function isMarked($im, $mark = false)
	{
		$area  = $this->analyseCheckBox($im, $mark);
		$value = self::UNTOUCHED;

		if($area->percentBlack() >= $this->min_marked_area)
		{
			if($area->percentBlack() >= $this->marked_area_checked && $area->percentBlack() <= $this->marked_area_unchecked)
			{
				$value = self::CHECKED;
				ilScanAssessmentLog::getInstance()->debug(sprintf('Checkbox is checked %s.', $area->percentBlack()));
			}
			else
			{
				$value = self::UNCHECKED;
				ilScanAssessmentLog::getInstance()->debug(sprintf('Checkbox is unchecked %s.', $area->percentBlack()));
			}
		}

		if($mark)
		{
			$this->image_helper->drawSquareFromTwoPoints($im, $this->getFirstPoint(), $this->getSecondPoint(), $this->color_mapping[$value]);
		}
		ilScanAssessmentLog::getInstance()->debug(sprintf('Checkbox black %s, white %s.', $area->percentBlack(), $area->percentWhite()));
		return $value;
	}

}