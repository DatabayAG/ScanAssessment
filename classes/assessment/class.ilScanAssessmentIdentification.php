<?php

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


	public function __construct(){}

	/**
	 * @param int  $test_id
	 * @param int  $page_number
	 * @param int  $session_id
	 * @param bool $personalised
	 */
	public function init($test_id, $page_number, $session_id, $personalised = false)
	{
		$this->test_id      = $test_id;
		$this->page_number  = $page_number;
		$this->session_id   = $session_id;
		$this->personalised = $personalised;
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
		return $this->getTestId() . '_' . $this->getSessionId() . '_' . $this->getPageNumber() . '_' . (int) $this->isPersonalised();
	}

	/**
	 * @param $string
	 */
	public function parseIdentificationString($string)
	{
		$string = preg_split('/_/', $string);
		if(is_array($string) && sizeof($string) == 4)
		{
			$this->test_id		= (int) $string[0];
			$this->session_id	= (int) $string[1];
			$this->page_number	= (int) $string[2];
			$this->personalised	= (boolean) $string[4];
		}
	}
}