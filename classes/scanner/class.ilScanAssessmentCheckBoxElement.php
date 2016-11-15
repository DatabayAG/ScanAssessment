<?php
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentScanner.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/geometry/class.ilScanAssessmentArea.php');

/**
 * Class ilScanAssessmentCheckBoxElement
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentCheckBoxElement
{
	const MIN_VALUE_BLACK		= 150;
	const MIN_MARKED_AREA		= 0.35;
	const MARKED_AREA_CHECKED	= 0.45;
	const MARKED_AREA_UNCHECKED	= 0.90;
	const BOX_SIZE				= 5;
	const CHECKED				= 2;
	const UNCHECKED				= 1;
	const UNTOUCHED				= 0;

	protected $color_mapping;
	/**
	 * @var ilScanAssessmentPoint
	 */
	protected $left_top;

	/**
	 * @var ilScanAssessmentPoint
	 */
	protected $right_bottom;

	/**
	 * @var ilScanAssessmentImageWrapper
	 */
	protected $image_helper;

	/**
	 * ilScanAssessmentCheckBoxElement constructor.
	 * @param ilScanAssessmentPoint $left_top
	 * @param ilScanAssessmentPoint $right_bottom
	 * @param ilScanAssessmentImageWrapper $image_helper
	 */
	public function __construct($left_top, $right_bottom, $image_helper)
	{
		$this->left_top			= $left_top;
		$this->right_bottom		= $right_bottom;
		$this->image_helper		= $image_helper;
		$this->color_mapping	= array(
			self::UNTOUCHED	=> $this->image_helper->getYellow(),
			self::UNCHECKED	=> $this->image_helper->getPink(),
			self::CHECKED	=> $this->image_helper->getGreen()
		);
	}

	/**
	 * @return ilScanAssessmentPoint
	 */
	public function getLeftBottom()
	{
		return new ilScanAssessmentPoint($this->getLeftTop()->getX(), $this->getRightBottom()->getY());
	}

	/**
	 * @return ilScanAssessmentPoint
	 */
	public function getRightTop()
	{
		return new ilScanAssessmentPoint($this->getRightBottom()->getX(), $this->getLeftTop()->getY());
	}

	/**
	 * @return ilScanAssessmentPoint
	 */
	public function getLeftTop()
	{
		return $this->left_top;
	}

	/**
	 * @param ilScanAssessmentPoint $left_top
	 */
	public function setLeftTop($left_top)
	{
		$this->left_top = $left_top;
	}

	/**
	 * @return ilScanAssessmentPoint
	 */
	public function getRightBottom()
	{
		return $this->right_bottom;
	}

	/**
	 * @param ilScanAssessmentPoint $right_bottom
	 */
	public function setRightBottom($right_bottom)
	{
		$this->right_bottom = $right_bottom;
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
		for($x = $this->getLeftTop()->getX(); $x < $this->getRightBottom()->getX(); $x++)
		{
			for($y = $this->getLeftTop()->getY(); $y < $this->getRightBottom()->getY(); $y++)
			{
				$total++;
				$gray = $this->image_helper->getGrey(new ilScanAssessmentPoint($x, $y));
				if($gray < self::MIN_VALUE_BLACK)
				{
					$black++;
					if($mark)
					{
						$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x,$y), $this->image_helper->getBlack());
					}
				}
				else
				{
					$white++;
				}
			}
		}
		#ilScanAssessmentLog::getInstance()->debug(sprintf('Checkbox pixels total %s, black %s, white %s.', $total, $black, $white));
		return new ilScanAssessmentArea($total, $white, $black);
	}

	/**
	 * @param      $im
	 * @param bool $mark
	 * @return int
	 */
	public function isMarked($im, $mark = false)
	{
		$area	= $this->analyseCheckBox($im, $mark);
		$value	= self::UNTOUCHED;

		if($area->percentBlack() >= self::MIN_MARKED_AREA)
		{
			if($area->percentBlack() >= self::MARKED_AREA_CHECKED && $area->percentBlack() <= self::MARKED_AREA_UNCHECKED)
			{
				$value	= self::CHECKED;
				#ilScanAssessmentLog::getInstance()->debug(sprintf('Checkbox is checked %s.', $area->percentBlack()));
			}
			else
			{
				$value	= self::UNCHECKED;
				#ilScanAssessmentLog::getInstance()->debug(sprintf('Checkbox is unchecked %s.', $area->percentBlack()));
			}
		}

		if($mark)
		{
			$this->image_helper->drawSquareFromTwoPoints($im,  $this->getLeftTop(), $this->getRightBottom(), $this->color_mapping[$value]);
		}
		#ilScanAssessmentLog::getInstance()->debug(sprintf('Checkbox black %s, white %s.', $area->percentBlack(), $area->percentWhite()));
		return $value;
	}

}