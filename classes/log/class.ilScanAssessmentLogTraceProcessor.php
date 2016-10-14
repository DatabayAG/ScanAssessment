<?php

require_once 'Services/Logging/classes/extensions/class.ilTraceProcessor.php';

/**
 * Class ilScanAssessmentLogTraceProcessor
 */
class ilScanAssessmentLogTraceProcessor extends ilTraceProcessor
{

	/**
	 * @var int
	 */
	private $level = 0;

	/**
	 * ilScanAssessmentLogTraceProcessor constructor.
	 * @param int @a_level
	 */
	public function __construct($a_level)
	{
		parent::__construct($a_level);
		$this->level = $a_level;
	}

	/**
	 * @param array $record
	 * @return array
	 */
	public function __invoke(array $record)
	{
		if($record['level'] < $this->level)
		{
			return $record;
		}

		$trace = debug_backtrace();

		// shift current method
		array_shift($trace);

		// shift plugin logger
		array_shift($trace);
		array_shift($trace);
		array_shift($trace);
		array_shift($trace);

		// shift internal monolog calls
		array_shift($trace);
		array_shift($trace);
		array_shift($trace);
		array_shift($trace);

		array_shift($trace);

		$trace_info = '';//$trace[0]['class'].'::'.$trace[0]['function'].':'.$trace[0]['line'];

		$record['extra'] = array_merge(
			$record['extra'],
			array('trace' => $trace_info)
		);

		$record['channel'] = '';

		return $record;
	}
}