<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Interface ilScanAssessmentLogWriter
 * @author Guido Vollbach <gvollbach@databay.de>
 */
interface ilScanAssessmentLogWriter
{
	/**
	 * @param array $message
	 * @return void
	 */
	public function write(array $message);

	/**
	 * @return void
	 */
	public function shutdown();
}