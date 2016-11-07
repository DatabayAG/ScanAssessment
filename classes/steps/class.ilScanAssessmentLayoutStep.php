<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('class.ilScanAssessmentStepsBase.php');

/**
 * Class ilScanAssessmentIsActivatedStep
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentLayoutStep extends ilScanAssessmentStepsBase
{
	/**
	 *  {@inheritdoc}
	 */
	public function getLabel()
	{
		return $this->plugin->txt('scas_layout_completed');
	}

	/**
	 *  {@inheritdoc}
	 */
	public function getTooltip()
	{
		return $this->plugin->txt('scas_layout_completed_info');
	}

	/**
	 *  {@inheritdoc}
	 */
	public function isFulfilled()
	{
		return true;
	}
}