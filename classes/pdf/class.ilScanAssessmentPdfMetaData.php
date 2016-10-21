<?php
ilScanAssessmentPlugin::getInstance()->includeClass('model/class.ilScanAssessmentUserPackagesConfiguration.php');
/**
 * Class ilScanAssessmentPdfMetaData
 */
class ilScanAssessmentPdfMetaData extends ilScanAssessmentUserPackagesConfiguration
{
	/**
	 * @var string
	 */
	protected $test_title;

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
	 * @var string
	 */
	protected $identification;

	/**
	 * ilScanAssessmentPdfMetaData constructor.
	 * @param ilObjTest	$test
	 * @param boolean	$personalised
	 * @param string	$identification
	 */
	public function __construct($test, $personalised, $identification)
	{
		parent::__construct($test->getId());

		$this->test_title		= $test->getTitle();
		$this->author			= $test->getAuthor();
		$this->identification	= $identification;
		$this->personalised		= $personalised;
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

	/**
	 * @return string
	 */
	public function getIdentification()
	{
		return $this->identification;
	}
}