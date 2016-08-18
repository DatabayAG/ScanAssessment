<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/Geometry/class.ilScanAssessmentPoint.php';
/**
 * Created by PhpStorm.
 * User: gvollbach
 * Date: 18.08.16
 * Time: 12:57
 */
class ilScanAssessmentLine
{
	/**
	 * @var ilScanAssessmentPoint
	 */
	protected $start;

	/**
	 * @var ilScanAssessmentPoint
	 */
	protected $end;

	/**
	 * @var
	 */
	protected $length;

	/**
	 * ilScanAssessmentLine constructor.
	 * @param ilScanAssessmentPoint $start
	 * @param ilScanAssessmentPoint $end
	 */
	public function __construct(ilScanAssessmentPoint $start, ilScanAssessmentPoint $end)
	{
		$this->start = $start;
		$this->end   = $end;
	}

	/**
	 * @return ilScanAssessmentPoint
	 */
	public function getStart()
	{
		return $this->start;
	}

	/**
	 * @param ilScanAssessmentPoint $start
	 */
	public function setStart($start)
	{
		$this->start = $start;
	}

	/**
	 * @return ilScanAssessmentPoint
	 */
	public function getEnd()
	{
		return $this->end;
	}

	/**
	 * @param ilScanAssessmentPoint $end
	 */
	public function setEnd($end)
	{
		$this->end = $end;
	}

	/**
	 * @return mixed
	 */
	public function getLength()
	{
		$x = $this->getEnd()->getX() - $this->getStart()->getX();
		$y = $this->getEnd()->getY() - $this->getStart()->getY();
		return sqrt(pow($x, 2) + pow($y, 2));
	}
}