<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once dirname(__FILE__) . '/class.ilScanAssessmentPreconditionBase.php';

/**
 * Class ilScanAssessmentIsActivePrecondition
 * @author Michael Jansen <mjansen@databay.de>
 */
class ilScanAssessmentIsActivePrecondition extends ilScanAssessmentPreconditionBase
{
	/**
	 *  {@inheritdoc}
	 */
	public function getLabel()
	{
		return $this->plugin->txt('tqae_pc_is_active');
	}

	/**
	 *  {@inheritdoc}
	 */
	public function getTooltip()
	{
		return $this->plugin->txt('tqae_pc_is_active_info');
	}

	/**
	 *  {@inheritdoc}
	 */
	public function isFulfilled()
	{
		return $this->test->isOnline();
	}
}