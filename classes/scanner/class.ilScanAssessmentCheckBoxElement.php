<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/class.ilScanAssessmentScanner.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/geometry/class.ilScanAssessmentArea.php';

/**
 * Class ilScanAssessmentCheckBoxElement
 */
class ilScanAssessmentCheckBoxElement
{
	const MIN_VALUE_BLACK		= 180;
	const MIN_MARKED_AREA		= 0.30;
	const MARKED_AREA_CHECKED	= 0.4;
	const MARKED_AREA_UNCHECKED	= 0.95;
	const BOX_SIZE				= 5;
	const CHECKED				= 2;
	const UNCHECKED				= 1;
	const UNTOUCHED				= 0;

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
	 * @param $image_helper
	 */
	public function __construct($left_top, $right_bottom, $image_helper)
	{
		$this->left_top		= $left_top;
		$this->right_bottom	= $right_bottom;
		$this->image_helper	= $image_helper;
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
		return new ilScanAssessmentArea($total, $white, $black);
	}

	/**
	 * @param      $im
	 * @param bool $mark
	 * @return int
	 */
	public function isMarked($im, $mark = false)
	{
		$area = $this->analyseCheckBox($im, $mark);

		if($area->percentBlack() >= self::MIN_MARKED_AREA)
		{
			if($area->percentBlack() >= self::MARKED_AREA_CHECKED && $area->percentBlack() <= self::MARKED_AREA_UNCHECKED)
			{
				if($mark)
				{
					$this->image_helper->drawSquareFromTwoPoints($im, $this->getLeftTop(), $this->getRightBottom(), $this->image_helper->getGreen());
				}
				return self::CHECKED;
			}
			if($mark)
			{
				$this->image_helper->drawSquareFromTwoPoints($im,  $this->getLeftTop(), $this->getRightBottom(),  $this->image_helper->getBlue());
			}
			return self::UNCHECKED;
		}

		if($mark)
		{
			$this->image_helper->drawSquareFromTwoPoints($im,  $this->getLeftTop(), $this->getRightBottom(), $this->image_helper->getYellow());
		}
		return self::UNTOUCHED;
	}

}