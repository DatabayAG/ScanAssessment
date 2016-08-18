<?php

class ilScanAssessmentPoint
{

	/**
	 * @var float
	 */
	private $x;

	/**
	 * @var float
	 */
	private $y;

	/**
	 * ilScanAssessmentPoint constructor.
	 * @param $x
	 * @param $y
	 */
	public function __construct($x, $y)
	{
		$this->x = $x;
		$this->y = $y;
	}
	
	/**
	 * @return float
	 */
	public function getX()
	{
		return $this->x;
	}

	/**
	 * @param float $x
	 */
	public function setX($x)
	{
		$this->x = $x;
	}

	/**
	 * @return float
	 */
	public function getY()
	{
		return $this->y;
	}

	/**
	 * @param float $y
	 */
	public function setY($y)
	{
		$this->y = $y;
	}

	/**
	 * @param $position
	 * @return $this
	 */
	public function moveX($position)
	{
		$this->setX($this->getX() + $position);
		return $this;
	}

	/**
	 * @param $position
	 * @return $this
	 */
	public function moveY($position)
	{
		$this->setY($this->getY() + $position);
		return $this;
	}
}