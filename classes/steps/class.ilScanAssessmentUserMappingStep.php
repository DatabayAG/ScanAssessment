<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('steps/class.ilScanAssessmentStepsBase.php');

/**
 * Class ilScanAssessmentUserMappingStep
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentUserMappingStep extends ilScanAssessmentStepsBase
{

	
	/**
	 *  {@inheritdoc}
	 */
	public function getLabel()
	{
		return $this->plugin->txt('scas_user_mapping_completed');
	}

	/**
	 *  {@inheritdoc}
	 */
	public function getTooltip()
	{
		return $this->plugin->txt('scas_user_mapping_completed_info');
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
				WHERE obj_id = %s AND usr_id IS NULL',
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