<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/interfaces/interface.ilScanAssessmentLogger.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/log/class.ilScanAssessmentEchoWriter.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/log/class.ilScanAssessmentLogFileWriter.php';
require_once 'Services/Calendar/classes/class.ilDateTime.php';

/**
 * Class ilScanAssessmentLog
 */
class ilScanAssessmentLog implements ilScanAssessmentLogger
{
	/**
	 * @var self
	 */
	protected static $instance;

	/**
	 * @var ilScanAssessmentLogWriter[]
	 */
	protected $writer = array();

	/**
	 *
	 */
	private function __construct()
	{
		$this->addWriter(new ilScanAssessmentEchoWriter());
		$this->addWriter(new ilScanAssessmentLogFileWriter());
	}

	/**
	 * Get singleton instance
	 * @return self
	 */
	public static function getInstance()
	{
		if(null !== self::$instance)
		{
			return self::$instance;
		}

		return (self::$instance = new self());
	}

	public function __destruct()
	{
		foreach($this->writer as $writer)
		{
			$writer->shutdown();
		}
	}
	/**
	 * @return array
	 */
	public static function getPriorities()
	{
		return array(
			self::EMERG  => 'EMERG',
			self::ALERT  => 'ALERT',
			self::CRIT   => 'CRIT',
			self::ERR    => 'ERR',
			self::WARN   => 'WARN',
			self::NOTICE => 'NOTICE',
			self::INFO   => 'INFO',
			self::DEBUG  => 'DEBUG',
		);
	}

	/**
	 * @param ilScanAssessmentBaseLogWriter $writer
	 * @param int $priority
	 */
	public function addWriter(ilScanAssessmentBaseLogWriter $writer, $priority = 1)
	{
		$this->writer[] = $writer;
	}

	/**
	 * @param ilScanAssessmentBaseLogWriter $writer
	 */
	public function removeWriter(ilScanAssessmentBaseLogWriter $writer)
	{
		$key = array_search($writer, $this->writer);
		if($key !== false)
		{
			unset($this->writer[$key]);
		}
	}

	/**
	 * @param int $priority
	 * @param mixed $message
	 * @param array $extra
	 * @throws ilException
	 */
	public function log($priority, $message, $extra = array())
	{
		if(!is_int($priority) || ($priority < 0) || ($priority >= count(self::getPriorities())))
		{
			throw new ilException(
				sprintf('$priority must be an integer > 0 and < %d; received %s',
					count(self::getPriorities()),
					var_export($priority, 1)
				)
			);
		}

		if(is_object($message) && !method_exists($message, '__toString'))
		{
			throw new ilException('$message must implement magic __toString() method');
		}

		if(is_array($message))
		{
			$message = var_export($message, true);
		}

		$timestamp = new ilDateTime(time(), IL_CAL_UNIX);

		$priorities = self::getPriorities();
		foreach($this->writer as $writer)
		{
			$writer->write(array(
				'timestamp'    => $timestamp,
				'priority'     => (int)$priority,
				'priorityName' => $priorities[$priority],
				'message'      => (string)$message,
				'extra'        => $extra
			));
		}
	}

	/**
	 * @param string $message
	 * @param array  $extra
	 * @return void
	 */
	public function emerg($message, $extra = array())
	{
		$this->log(self::EMERG, $message, $extra);
	}

	/**
	 * @param string $message
	 * @param array  $extra
	 * @return void
	 */
	public function alert($message, $extra = array())
	{
		$this->log(self::ALERT, $message, $extra);
	}

	/**
	 * @param string $message
	 * @param array  $extra
	 * @return void
	 */
	public function crit($message, $extra = array())
	{
		$this->log(self::CRIT, $message, $extra);
	}

	/**
	 * @param string $message
	 * @param array  $extra
	 * @return void
	 */
	public function err($message, $extra = array())
	{
		$this->log(self::ERR, $message, $extra);
	}

	/**
	 * @param string $message
	 * @param        array
	 * @return void
	 */
	public function info($message, $extra = array())
	{
		$this->log(self::INFO, $message, $extra);
	}

	/**
	 * @param string $message
	 * @param array  $extra
	 * @return void
	 */
	public function warn($message, $extra = array())
	{
		$this->log(self::WARN, $message, $extra);
	}

	/**
	 * @param string $message
	 * @param array  $extra
	 * @return void
	 */
	public function notice($message, $extra = array())
	{
		$this->log(self::NOTICE, $message, $extra);
	}

	/**
	 * @param string $message
	 * @param array  $extra
	 * @return void
	 */
	public function debug($message, $extra = array())
	{
		$this->log(self::DEBUG, $message, $extra);
	}
}