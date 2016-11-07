<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('class.ilScanAssessmentStepsBase.php');
ilScanAssessmentPlugin::getInstance()->includeClass('model/class.ilScanAssessmentTestConfiguration.php');

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