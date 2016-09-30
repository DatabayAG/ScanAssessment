<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/class.ilScanAssessmentScanner.php';

/**
 * Created by PhpStorm.
 * User: gvollbach
 * Date: 30.09.16
 * Time: 09:06
 */
class ilScanAssessmentCheckBoxElement
{
	const MIN_VALUE_BLACK		= 180;
	const MIN_MARKED_AREA		= 0.05;
	const MARKED_AREA_CHECKED	= 0.3;
	const BOX_SIZE				= 5;
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
	 * @return int
	 */
	public function isMarked($im, $mark = false)
	{
		$total_count	= 0;
		$marked_count	= 0;
		for($x = $this->getLeftTop()->getX(); $x < $this->getRightBottom()->getX(); $x++)
		{
			for($y = $this->getLeftTop()->getY(); $y < $this->getRightBottom()->getY(); $y++)
			{
				$total_count++;
				$gray = $this->image_helper->getGrey(new ilScanAssessmentPoint($x, $y));
				if($gray < self::MIN_VALUE_BLACK)
				{
					$marked_count++;
					if($mark)
					{
						$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x,$y), $this->image_helper->getBlack());
					}
				}
			}
		}
		if($total_count > 0)
		{
			$r = 1 / $total_count * $marked_count;
			if($r >= self::MIN_MARKED_AREA)
			{
				if($r >= self::MARKED_AREA_CHECKED)
				{
					if($mark)
					{
						$this->image_helper->drawSquareFromTwoPoints($im, $this->getLeftTop(), $this->getRightBottom(), $this->image_helper->getGreen());
						//$this->image_helper->drawSquareFromTwoPoints($im, new ilScanAssessmentPoint($first_point->getX() -1 ,$first_point->getY() -1), new ilScanAssessmentPoint($second_point->getX() +1 ,$second_point->getY() +1),  $this->image_helper->getGreen());
					}

					return 2;
				}
				if($mark)
				{
					$this->image_helper->drawSquareFromTwoPoints($im,  $this->getLeftTop(), $this->getRightBottom(),  $this->image_helper->getBlue());
					//$this->image_helper->drawSquareFromTwoPoints($im, new ilScanAssessmentPoint($first_point->getX() -1 ,$first_point->getY() -1), new ilScanAssessmentPoint($second_point->getX() +1 ,$second_point->getY() +1), $this->image_helper->getBlue());
				}

				return 1;
			}
		}

		if($mark)
		{
			$this->image_helper->drawSquareFromTwoPoints($im,  $this->getLeftTop(), $this->getRightBottom(), $this->image_helper->getYellow());
		}

		return 0;
	}

}