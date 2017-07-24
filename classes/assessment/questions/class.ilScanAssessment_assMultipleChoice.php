<?php
ilScanAssessmentPlugin::getInstance()->includeClass('assessment/questions/class.ilScanAssessment_assSingleChoice.php');

/**
 * Class ilScanAssessment_assMultipleChoice
 */
class ilScanAssessment_assMultipleChoice extends ilScanAssessment_assSingleChoice 
{
	/**
	 * @param assQuestion $question
	 * @param     $answer_position
	 * @param     $answer_text
	 * @param     $x
	 * @param     $y
	 * @param int $end_x
	 * @return array
	 */
	protected function appendAnswer($question, $answer_position, $answer_text, $x, $y, $end_x = 0, $ident_string = null)
	{
		$this->log->debug(sprintf('Answer checkbox for Question with id %s, answer order %s and text %s was added to [%s, %s] question type %s', $question->getId(), $answer_position, $answer_text, $x, $y, __CLASS__));
		return array(
			'qid'  => $question->getId(),
			'aid'  => $answer_position,
			//'a_text' => $answer_text, 
			'x'    => $x,
			'y'    => $y,
			'type' => __CLASS__,
			'end_x'=> $end_x,
			'ident' => $ident_string
		);
	}

}