<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once dirname(__FILE__) . '/class.ilScanAssessmentPreconditionBase.php';

/**
 * Class ilScanAssessmentIsFixedTestPrecondition
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentIsFixedTestPrecondition extends ilScanAssessmentPreconditionBase
{
	/**
	 *  {@inheritdoc}
	 */
	public function getLabel()
	{
		return $this->plugin->txt('scas_pc_is_rnd_or_fixed_test');
	}

	/**
	 *  {@inheritdoc}
	 */
	public function getTooltip()
	{
		return $this->plugin->txt('scas_pc_is_rnd_or_fixed_test_info');
	}

	/**
	 *  {@inheritdoc}
	 */
	public function isFulfilled()
	{
		return $this->test->isFixedTest();
	}
}