<?php

/**
 * Class ilScanAssessmentPdfMetaData
 */
class ilScanAssessmentPdfMetaData
{
	protected $test_title;
	
	protected $test_date;
	
	protected $author;
	
	protected $institution;
	
	protected $student_name;
	
	protected $student_matriculation;

	protected $personalised;

	/**
	 * ilScanAssessmentPdfMetaData constructor.
	 * @param $test_title
	 * @param $test_date
	 * @param $test_author
	 * @param $institution
	 * @param $personalised
	 */
	public function __construct($test_title, $test_date, $test_author, $institution, $personalised)
	{
		$this->test_title	= $test_title;
		$this->test_date	= $test_date;
		$this->author		= $test_author;
		$this->institution	= $institution;
		$this->personalised	= $personalised;
	}

	/**
	 * @return mixed
	 */
	public function getPersonalised()
	{
		return $this->personalised;
	}

	/**
	 * @return mixed
	 */
	public function getTestTitle()
	{
		return $this->test_title;
	}

	/**
	 * @return mixed
	 */
	public function getTestDate()
	{
		return $this->test_date;
	}

	/**
	 * @return mixed
	 */
	public function getAuthor()
	{
		return $this->author;
	}

	/**
	 * @return mixed
	 */
	public function getInstitution()
	{
		return $this->institution;
	}

	/**
	 * @return mixed
	 */
	public function getStudentName()
	{
		return $this->student_name;
	}

	/**
	 * @param mixed $student_name
	 */
	public function setStudentName($student_name)
	{
		$this->student_name = $student_name;
	}

	/**
	 * @return mixed
	 */
	public function getStudentMatriculation()
	{
		return $this->student_matriculation;
	}

	/**
	 * @param mixed $student_matriculation
	 */
	public function setStudentMatriculation($student_matriculation)
	{
		$this->student_matriculation = $student_matriculation;
	}
	
	
}