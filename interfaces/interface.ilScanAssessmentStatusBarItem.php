<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilScanAssessmentStatusBarItem
 * @author Michael Jansen <mjansen@databay.de>
 */
interface ilScanAssessmentStatusBarItem
{
	/**
	 * @return string
	 */
	public function getLabel();

	/**
	 * @return string
	 */
	public function getTooltip();

	/**
	 * @return bool
	 */
	public function isFulfilled();
	
	/**
	 * @return bool
	 */
	public function isRequired();
}