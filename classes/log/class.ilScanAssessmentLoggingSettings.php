<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Logging/classes/public/class.ilLogLevel.php';
include_once './Services/Logging/interfaces/interface.ilLoggingSettings.php';

/**
 * Class ilScanAssessmentLoggingSettings
 */
class ilScanAssessmentLoggingSettings implements ilLoggingSettings
{
	protected static $instance = null;

	private $level			= null;
	private $cache			= FALSE;
	private $cache_level	= null;
	private $now			= null;
	
	protected $logfile;

	/**
	 * @inheritdoc
	 */
	private function __construct()
	{
		$now				= new ilDateTime(time(), IL_CAL_UNIX);
		$this->now			= $now->get(IL_CAL_FKT_DATE, 'Y_m_d-H_i_');
		$this->level		= ilLogLevel::DEBUG;
		$this->cache_level	= ilLogLevel::DEBUG;
	}

	/**
	 * @inheritdoc
	 */
	public static function getInstance()
	{
		if(self::$instance)
		{
			return self::$instance;
		}
		return self::$instance = new self();
	}

	/**
	 * @inheritdoc
	 */
	public function getLevelByComponent($a_component_id)
	{
		return $this->getLevel();
	}

	/**
	 * @inheritdoc
	 */
	public function isEnabled()
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function getLogDir()
	{
		return ilUtil::getDataDir().'/'.ilScanAssessmentLoggingSettings::getInstance()->getLogDirectory();
	}

	/**
	 * @inheritdoc
	 */
	public function getLogFile()
	{
		if($this->logfile != '')
		{
			return $this->logfile;
		}
		return $this->now . 'ilScanAssessment.log';
	}

	/**
	 * @inheritdoc
	 */
	public function getLogFilePath()
	{
		return $this->getLogDir() . '/' .$this->getLogFile();
	}

	public function setLogFile($logfile)
	{
		$this->logfile = $logfile;
	}

	/**
	 * @return string
	 */
	public function getLogDirectory()
	{
		return 'scanAssessment/logs/';
	}

	/**
	 * @inheritdoc
	 */
	public function getLevel()
	{
		return $this->level;
	}

	/**
	 * @inheritdoc
	 */
	public function getCacheLevel()
	{
		return $this->cache_level;
	}

	/**
	 * @inheritdoc
	 */
	public function isCacheEnabled()
	{
		return $this->cache;
	}

	/**
	 * @inheritdoc
	 */
	public function isMemoryUsageEnabled()
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function isBrowserLogEnabled()
	{
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function isBrowserLogEnabledForUser($a_login)
	{
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function getBrowserLogUsers()
	{
		return array();
	}


}