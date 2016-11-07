<?php
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/geometry/class.ilScanAssessmentPoint.php');

/**
 * Class ilScanAssessmentLine
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentLine
{
	/**
	 * @var ilScanAssessmentPoint
	 */
	private $start;

	/**
	 * @var ilScanAssessmentPoint
	 */
	private $end;

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