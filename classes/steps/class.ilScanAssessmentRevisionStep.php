<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('steps/class.ilScanAssessmentStepsBase.php');

/**
 * Class ilScanAssessmentRevisionStep
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentRevisionStep extends ilScanAssessmentStepsBase
{
	/**
	 *  {@inheritdoc}
	 */
	public function getLabel()
	{
		return $this->plugin->txt('scas_revision_completed');
	}

	/**
	 *  {@inheritdoc}
	 */
	public function getTooltip()
	{
		return $this->plugin->txt('scas_revision_completed_info');
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
				WHERE obj_id = %s AND revision_done = %s',
			array('integer', 'integer'),
			array((int) $this->test->getId(), 1)
		);

		$value = false;
		while($row = $ilDB->fetchAssoc($res))
		{
			$value = true;
			continue;
		}
		return $value;
	}
}