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
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		$res = $ilDB->queryF('SELECT *
				FROM '.self::pdf_data_table.'
				WHERE obj_id = %s AND results_exported = 0',
			array('integer'),
			array((int) $this->test->getId())
		);

		$value = true;
		while($row = $ilDB->fetchAssoc($res))
		{
			$value = false;
			continue;
		}
		return $value;
	}
}