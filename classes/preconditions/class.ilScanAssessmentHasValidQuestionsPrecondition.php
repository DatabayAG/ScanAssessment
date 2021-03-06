<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('preconditions/class.ilScanAssessmentPreconditionBase.php');
/**
 * Class ilScanAssessmentHasValidQuestionsPrecondition
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentHasValidQuestionsPrecondition extends ilScanAssessmentPreconditionBase
{
	/**
	 * @var array
	 */
	protected static $supported_type_tags = array(
		'assSingleChoice',
		'assMultipleChoice',
		'assKprimChoice',
		'assFreestyleScanQuestion'
	);

	/**
	 *  {@inheritdoc}
	 */
	public function getLabel()
	{
		return $this->plugin->txt('scas_pc_has_valid_questions');
	}

	/**
	 *  {@inheritdoc}
	 */
	public function getTooltip()
	{
		return $this->plugin->txt('scas_pc_has_valid_questions_info');
	}

	/**
	 * {@inheritdoc}
	 */
	public function isRequired()
	{
		return false;
	}

	/**
	 *  {@inheritdoc}
	 */
	public function isFulfilled()
	{
		$status = false;

		if($this->test->isRandomTest())
		{
			$questions = $this->test->getPotentialRandomTestQuestions();
		}
		else
		{
			$questions = $this->test->getTestQuestions();
		}

		foreach($questions as $question)
		{
			$status = true;
			if(!in_array($question['type_tag'], self::$supported_type_tags))
			{
				return false;
			}
		}

		return $status;
	}
}