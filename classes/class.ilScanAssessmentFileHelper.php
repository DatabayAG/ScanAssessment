<?php

/**
 * Class ilScanAssessmentFileHelper
 */
class ilScanAssessmentFileHelper
{

	/**
	 * @var int
	 */
	protected $test_id;

	/**
	 * ilScanAssessmentFileHelper constructor.
	 * @param $test_id
	 */
	public function __construct($test_id)
	{
		$this->test_id = $test_id;
	}

	/**
	 * @return string
	 */
	public function getLayoutPath()
	{
		$path = $this->getBasePath() . '/layout/';
		$this->ensurePathExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	public function getPdfPath()
	{
		$path = $this->getBasePath() . '/pdf/';
		$this->ensurePathExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	public function getScanPath()
	{
		$path = $this->getBasePath() . '/scans/';
		$this->ensurePathExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	public function getAnalysedPath()
	{
		$path = $this->getScanPath() . '/analysed/';
		$this->ensurePathExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	public function getZipPath()
	{
		$path = $this->getScanPath() . 'zip/';
		$this->ensurePathExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	protected function getBasePath()
	{
		$path = ilUtil::getDataDir() . '/scanAssessment/tst_' . $this->test_id;
		$this->ensurePathExists($path);
		return $path;
	}

	/**
	 * @param $path
	 */
	protected function ensurePathExists($path)
	{
		if( ! is_dir($path))
		{
			ilUtil::makeDirParents($path);
		}
	}
}