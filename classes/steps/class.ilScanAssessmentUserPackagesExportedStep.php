<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once dirname(__FILE__) . '/class.ilScanAssessmentStepsBase.php';

/**
 * Class class.ilScanAssessmentUserPackagesExportedStep.php
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
		return false;
	}
}