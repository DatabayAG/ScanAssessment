<?php

/**
 * Class ilScanAssessmentPdfMap
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentPdfMap
{
	/**
	 * @var array
	 */
	protected $question_positions;

	/**
	 * @var array
	 */
	protected $matriculation_position;

	/**
	 * @var string
	 */
	protected $identification;

	/**
	 * ilScanAssessmentPdfMap constructor.
	 */
	public function __construct()
	{
		$this->question_positions = array();
	}

	/**
	 * @return array
	 */
	public function getQuestionPositions()
	{
		return $this->question_positions;
	}

	/**
	 * @param $page
	 * @param $question_positions
	 */
	public function setQuestionPositions($page, $question_positions)
	{
		$this->question_positions[$page][] = $question_positions;
	}

	/**
	 * @return array
	 */
	public function getMatriculationPosition()
	{
		return $this->matriculation_position;
	}

	/**
	 * @param $matriculation_position
	 */
	public function setMatriculationPosition($matriculation_position)
	{
		$this->matriculation_position = $matriculation_position;
	}

	/**
	 * @return string
	 */
	public function getIdentification()
	{
		return $this->identification;
	}

	/**
	 * @param $identification
	 */
	public function setIdentification($identification)
	{
		$this->identification = $identification;
	}

}