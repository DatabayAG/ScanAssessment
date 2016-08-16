<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once dirname(__FILE__) . '/class.ilScanAssessmentStepsBase.php';
require_once dirname(__FILE__) . '/../model/class.ilScanAssessmentTestConfiguration.php';

/**
 * Class ilScanAssessmentIsActivatedStep
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentIsActivatedStep extends ilScanAssessmentStepsBase
{
	/**
	 *  {@inheritdoc}
	 */
	public function getLabel()
	{
		return $this->plugin->txt('scas_is_activated');
	}

	/**
	 *  {@inheritdoc}
	 */
	public function getTooltip()
	{
		return $this->plugin->txt('scas_is_activated_info');
	}

	/**
	 *  {@inheritdoc}
	 */
	public function isFulfilled()
	{
		$configuration = new ilScanAssessmentTestConfiguration($this->test->getId());
		return $configuration->getActive() == 1;
	}
}