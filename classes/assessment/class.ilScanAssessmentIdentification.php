<?php
/**
 * Class ilScanAssessmentIdentification
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentIdentification
{
	/** @var  int */
	protected $test_id;

	/** @var boolean */
	protected $personalised;

	/** @var int */
	protected $page_number;

	/** @var int */
	protected $session_id;

	/**
	 * ilScanAssessmentIdentification constructor.
	 */
	public function __construct(){}

	/**
	 * @param int  $test_id
	 * @param int  $page_number
	 * @param int  $session_id
	 * @param bool $personalised
	 */
	public function init($test_id, $page_number, $session_id, $personalised = false)
	{
		global $ilDB;
		$this->test_id      = $test_id;
		$this->page_number  = $page_number;
		$this->session_id   = $ilDB->nextId('pl_scas_pdf_data');
		$this->personalised = $personalised;
		$this->save();
	}

	protected function save()
	{
		global $ilDB;
		$ilDB->insert('pl_scas_pdf_data',
			array(
				'pdf_id'		=> array('integer', $this->getSessionId()),
				'obj_id'		=> array('integer', $this->getTestId()),
				'personalised'	=> array('integer', $this->isPersonalised()),
			));
	}

	/**
	 * @return int
	 */
	public function getTestId()
	{
		return $this->test_id;
	}

	/**
	 * @return boolean
	 */
	public function isPersonalised()
	{
		return $this->personalised;
	}

	/**
	 * @return int
	 */
	public function getPageNumber()
	{
		return $this->page_number + 1;
	}

	/**
	 * @param $page_number
	 */
	public function setPageNumber($page_number)
	{
		$this->page_number = $page_number;
	}

	/**
	 * @return int
	 */
	public function getSessionId()
	{
		return $this->session_id;
	}

	/**
	 * @return string
	 */
	public function getIdentificationString()
	{
		//max size for qr '0000000000000000000000000000000000';
		return $this->getSessionId() . '_' . $this->getPageNumber();
	}

	/**
	 * @return string
	 */
	public function getSavePathName()
	{
		return $this->getSessionId() . '/' . $this->getPageNumber();
	}

	/**
	 * @param $string
	 */
	public function parseIdentificationString($string)
	{
		$string = preg_split('/_/', $string);
		if(is_array($string) && sizeof($string) == 2)
		{
			$this->session_id	= (int) $string[0];
			$this->page_number	= (int) $string[1] - 1;
			
			global $ilDB;
			$res = $ilDB->queryF('SELECT obj_id FROM pl_scas_pdf_data WHERE pdf_id = %s',
				array('integer'), array($this->session_id));
			while($row = $ilDB->fetchAssoc($res))
			{
				$this->test_id = $row['obj_id'];
			}
		}
	}
}