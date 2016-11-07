<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('steps/class.ilScanAssessmentStepsBase.php');

/**
 * Class ilScanAssessmentReturnResultsStep
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentReturnResultsStep extends ilScanAssessmentStepsBase
{
	/**
	 *  {@inheritdoc}
	 */
	public function getLabel()
	{
		return $this->plugin->txt('scas_results_returned');
	}

	/**
	 *  {@inheritdoc}
	 */
	public function getTooltip()
	{
		return $this->plugin->txt('scas_results_returned_info');
	}

	/**
	 *  {@inheritdoc}
	 */
	public function isFulfilled()
	{
		return false;
	}
}