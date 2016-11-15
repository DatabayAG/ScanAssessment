<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('steps/class.ilScanAssessmentStepsBase.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfAssessmentBuilder.php');
ilScanAssessmentPlugin::getInstance()->includeClass('class.ilScanAssessmentFileHelper.php');
/**
 * Class ilScanAssessmentUserPackagesExportedStep
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentUserPackagesExportedStep extends ilScanAssessmentStepsBase
{
	/**
	 *  {@inheritdoc}
	 */
	public function getLabel()
	{
		return $this->plugin->txt('scas_user_packages_exported');
	}

	/**
	 *  {@inheritdoc}
	 */
	public function getTooltip()
	{
		return $this->plugin->txt('scas_user_packages_exported_info');
	}

	/**
	 *  {@inheritdoc}
	 */
	public function isFulfilled()
	{
		$helper = new ilScanAssessmentFileHelper($this->test->getId());
		return $helper->doFilesExistsInDirectory($helper->getPdfPath());
	}
}