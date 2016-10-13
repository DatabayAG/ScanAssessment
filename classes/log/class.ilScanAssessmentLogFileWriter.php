<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Calendar/classes/class.ilDateTime.php';

require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/log/class.ilScanAssessmentBaseLogWriter.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/log/class.ilScanAssessmentLog.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/log/class.ilScanAssessmentLogTraceProcessor.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/log/class.ilScanAssessmentLoggingSettings.php';

/**
 * Class ilScanAssessmentLogFileWriter
 */
class ilScanAssessmentLogFileWriter extends ilScanAssessmentBaseLogWriter
{
	/**
	 * @var null|ilLog
	 */
	protected $aggregated_logger;

	/**
	 * @var string
	 */
	protected $filename = '';

	/**
	 * @var array
	 */
	protected $logged_priorities = array();

	/**
	 * @var int
	 */
	protected $succeeded_users = 0;

	/**
	 * @var int
	 */
	protected $start_ts      = 0;

	/**
	 * @var ilScanAssessmentLoggingSettings|null
	 */
	protected $settings;

	/**
	 *
	 */
	public function __construct()
	{
		$now = new ilDateTime(time(), IL_CAL_UNIX);
		#$file = $now->get(IL_CAL_FKT_DATE, 'YmdHi_') . 'ilScanAssessment.log';
		$file = $now->get(IL_CAL_FKT_DATE, 'Ymd_') . 'ilScanAssessment.log';
		$tmp_dir = ilUtil::ilTempnam();
		ilUtil::makeDir($tmp_dir);

		$this->setFilename($tmp_dir . DIRECTORY_SEPARATOR . $file);

		$this->settings = ilScanAssessmentLoggingSettings::getInstance();
		$this->settings->setLogFile($file);
		$factory = ilLoggerFactory::newInstance($this->settings);
		$this->aggregated_logger = $factory->getComponentLogger('ilScanAssessmentPlugin');
		$this->aggregated_logger->getLogger()->popProcessor();
		$this->aggregated_logger->getLogger()->pushProcessor(new ilScanAssessmentLogTraceProcessor(ilLogLevel::DEBUG));
	}
	

	/**
	 * @param array $message
	 * @return void
	 */
	protected function doWrite(array $message)
	{
		if($this->start_ts == 0)
		{
			$this->start_ts = time();
		}

		if(isset($message['extra']) && isset($message['extra']['import_success']))
		{
			++$this->succeeded_users;
		}

		if(isset($message['priority']))
		{
			if(!isset($this->logged_priorities[$message['priority']]))
			{
				$this->logged_priorities[$message['priority']] = 1;
			}
			else
			{
				++$this->logged_priorities[$message['priority']];
			}
		}

		switch($message['priority'])
		{
			case ilScanAssessmentLogger::EMERG:
				$method = 'emergency';
				break;

			case ilScanAssessmentLogger::ALERT:
				$method = 'alert';
				break;

			case ilScanAssessmentLogger::CRIT:
				$method = 'critical';
				break;

			case ilScanAssessmentLogger::ERR:
				$method = 'error';
				break;

			case ilScanAssessmentLogger::WARN:
				$method = 'warning';
				break;

			case ilScanAssessmentLogger::INFO:
				$method = 'info';
				break;

			case ilScanAssessmentLogger::NOTICE:
				$method = 'notice';
				break;

			case ilScanAssessmentLogger::DEBUG:
			default:
				$method = 'debug';
				break;
		}

		$line = $message['message'];
		$this->aggregated_logger->{$method}($line);
	}


	/**
	 * @return void
	 */
	public function shutdown()
	{
		if($this->log_overview_plugin !== null)
		{
			$file_saved_successful = $this->log_overview_plugin->getReportingData(
				$this->settings->getLogFilePath(),
				(int)$this->logged_priorities[ilScanAssessmentLogger::ERR] + (int)$this->logged_priorities[ilScanAssessmentLogger::CRIT] +
				(int)$this->logged_priorities[ilScanAssessmentLogger::ALERT] + (int)$this->logged_priorities[ilScanAssessmentLogger::EMERG],
				(int)$this->logged_priorities[ilScanAssessmentLogger::WARN],
				$this->succeeded_users,
				$this->start_ts > 0 ? time() - $this->start_ts : 0,
				$this->getHighestLoggedSeverity()
			);
			if($file_saved_successful)
			{
				unlink($this->settings->getLogFilePath());
			}
		}

		unset($this->log_overview_plugin);
		unset($this->aggregated_logger);
	}

	/**
	 * @return string
	 */
	public function getFilename()
	{
		return $this->filename;
	}

	/**
	 * @param string $filename
	 */
	public function setFilename($filename)
	{
		$this->filename = $filename;
	}

	/**
	 * @return int
	 */
	protected function getHighestLoggedSeverity()
	{
		foreach(array(
					ilScanAssessmentLogger::EMERG,
					ilScanAssessmentLogger::ALERT,
					ilScanAssessmentLogger::CRIT,
					ilScanAssessmentLogger::ERR,
					ilScanAssessmentLogger::WARN,
					ilScanAssessmentLogger::NOTICE,
					ilScanAssessmentLogger::INFO,
					ilScanAssessmentLogger::DEBUG
				) as $severity)
		{
			if(isset($this->logged_priorities[$severity]) && $this->logged_priorities[$severity] > 0)
			{
				return $severity;
			}
		}

		return PHP_INT_MAX;
	}
}