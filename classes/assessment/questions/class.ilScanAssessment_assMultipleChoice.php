<?php
ilScanAssessmentPlugin::getInstance()->includeClass('assessment/questions/class.ilScanAssessment_assSingleChoice.php');

/**
 * Class ilScanAssessment_assMultipleChoice
 */
class ilScanAssessment_assMultipleChoice extends ilScanAssessment_assSingleChoice 
{
	/**
	 * @param $question
	 * @param $answer
	 * @param $x
	 * @param $y
	 * @return array
	 */
	protected function appendAnswer($question, $answer, $x, $y)
	{
		$this->log->debug(sprintf('Answer checkbox for Question with id %s, answer order %s and text %s was added to [%s, %s] question type %s', $question->getId(), $answer->getOrder(), $answer->getAnswertext(), $x, $y, __CLASS__));

		return array(
			'qid'  => $question->getId(),
			'aid'  => $answer->getOrder(),
			//'a_text' => $answer->getAnswertext(), 
			'x'    => $x,
			'y'    => $y,
			'type' => __CLASS__
		);
	}

}