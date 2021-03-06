<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('steps/class.ilScanAssessmentStepsBase.php');
ilScanAssessmentPlugin::getInstance()->includeClass('steps/class.ilScanAssessmentStepsBase.php');

/**
 * Class ilScanAssessmentScanStep
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentScanStep extends ilScanAssessmentStepsBase
{
	/**
	 *  {@inheritdoc}
	 */
	public function getLabel()
	{
		return $this->plugin->txt('scas_scan_completed');
	}

	/**
	 *  {@inheritdoc}
	 */
	public function getTooltip()
	{
		return $this->plugin->txt('scas_scan_completed_info');
	}

	/**
	 *  {@inheritdoc}
	 */
	public function isFulfilled()
	{
		$scanner = new ilScanAssessmentScanGUI($this->plugin->getUIClassInstance());
		return $scanner->doProcessedFilesExist();
	}
}