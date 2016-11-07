<?php
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/geometry/class.ilScanAssessmentPoint.php');

/**
 * Class ilScanAssessmentRectangle
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentRectangle
{

	/**
	 * @var ilScanAssessmentPoint
	 */
	private $top_left;

	/**
	 * @var ilScanAssessmentPoint
	 */
	private $bottom_right;

	/**
	 * ilScanAssessmentRectangle constructor.
	 * @param $top_left
	 * @param $bottom_right
	 */
	public function __construct($top_left, $bottom_right)
	{
		$this->top_left = $top_left;
		$this->bottom_right = $bottom_right;
	}

	/**
	 * @return ilScanAssessmentPoint
	 */
	public function getTopLeft()
	{
		return $this->top_left;
	}

	/**
	 * @param ilScanAssessmentPoint $top_left
	 */
	public function setTopLeft($top_left)
	{
		$this->top_left = $top_left;
	}

	/**
	 * @return ilScanAssessmentPoint
	 */
	public function getBottomRight()
	{
		return $this->bottom_right;
	}

	/**
	 * @param ilScanAssessmentPoint $bottom_right
	 */
	public function setBottomRight($bottom_right)
	{
		$this->bottom_right = $bottom_right;
	}
}