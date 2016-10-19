<?php

/**
 * Class ilScanAssessmentPdfMetaData
 */
class ilScanAssessmentPdfMetaData
{
	/**
	 * @var string
	 */
	protected $test_title;

	/**
	 * @var string
	 */
	protected $test_date;

	/**
	 * @var string
	 */
	protected $author;

	/**
	 * @var string
	 */
	protected $student_name;

	/**
	 * @var string
	 */
	protected $student_matriculation;

	/**
	 * @var bool
	 */
	protected $personalised;

	/**
	 * ilScanAssessmentPdfMetaData constructor.
	 * @param ilObjTest $test
	 * @param $test_date
	 * @param $personalised
	 */
	public function __construct($test, $test_date, $personalised)
	{
		$this->test_title	= $test->getTitle();
		$this->author		= $test->getAuthor();
		$this->test_date	= $test_date;
		$this->personalised	= $personalised;
	}

	/**
	 * @return bool
	 */
	public function getPersonalised()
	{
		return $this->personalised;
	}

	/**
	 * @return string
	 */
	public function getTestTitle()
	{
		return $this->test_title;
	}

	/**
	 * @return string
	 */
	public function getTestDate()
	{
		return $this->test_date;
	}

	/**
	 * @return string
	 */
	public function getAuthor()
	{
		return $this->author;
	}

	/**
	 * @return string
	 */
	public function getStudentName()
	{
		return $this->student_name;
	}

	/**
	 * @param string $student_name
	 */
	public function setStudentName($student_name)
	{
		$this->student_name = $student_name;
	}

	/**
	 * @return string
	 */
	public function getStudentMatriculation()
	{
		return $this->student_matriculation;
	}

	/**
	 * @param string $student_matriculation
	 */
	public function setStudentMatriculation($student_matriculation)
	{
		$this->student_matriculation = $student_matriculation;
	}
	
	
}