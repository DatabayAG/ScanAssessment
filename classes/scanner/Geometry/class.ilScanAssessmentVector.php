<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/Geometry/class.ilScanAssessmentPoint.php';
class ilScanAssessmentVector
{
	/**
	 * @var ilScanAssessmentPoint
	 */
	private $position;

	/**
	 * @var float
	 */
	private $length;

	/**
	 * ilScanAssessmentVector constructor.
	 * @param ilScanAssessmentPoint $position
	 * @param float                 $length
	 */
	public function __construct(ilScanAssessmentPoint $position, $length)
	{
		$this->position	= $position;
		$this->length	= $length;
	}

	/**
	 * @return ilScanAssessmentPoint
	 */
	public function getPosition()
	{
		return $this->position;
	}

	/**
	 * @param ilScanAssessmentPoint $position
	 */
	public function setPosition($position)
	{
		$this->position = $position;
	}

	/**
	 * @return float
	 */
	public function getLength()
	{
		return $this->length;
	}

	/**
	 * @param float $length
	 */
	public function setLength($length)
	{
		$this->length = $length;
	}
	
	
}