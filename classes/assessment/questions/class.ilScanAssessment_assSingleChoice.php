<?php
ilScanAssessmentPlugin::getInstance()->includeClass('assessment/questions/class.ilScanAssessmentQuestionHandler.php');

/**
 * Class ilScanAssessment_assSingleChoice
 */
class ilScanAssessment_assSingleChoice extends ilScanAssessmentQuestionHandler
{

	/**
	 * @param assSingleChoice | assMultipleChoice $question
	 * @return array
	 */
	public function writeAnswersToPdf($question)
	{
		$answer_positions = array();
		foreach($question->getAnswers() as $key => $answer)
		{
			/** @var ASS_AnswerSimple $answer */
			$this->pdf_helper->pdf->setCellMargins(23, PDF_CHECKBOX_MARGIN);
			$this->pdf_helper->pdf->Rect(34, $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN + 0.8, PDF_ANSWERBOX_W, PDF_ANSWERBOX_H, 'D', array('all' => $this->circleStyle));
			$x = $this->pdf_helper->pdf->GetX() + 34;
			$y = $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN;
			$this->pdf_helper->writeHTML($answer->getAnswertext());
			$this->log->debug(sprintf('Answer checkbox for Question with id %s, answer order %s and text %s was added to [%s, %s]', $question->getId(), $answer->getOrder(),  $answer->getAnswertext(), $x , $y));
			$answer_positions[] = array('qid' => $question->getId() , 
										'aid' => $answer->getOrder() ,
										//'a_text' => $answer->getAnswertext(), 
										'x' => $x ,
										'y' => $y);
		}
		return $answer_positions;
	}
}

/*$this->answer_export[] =		'qid' 		.' '. $question->getId()		.' '.
	'aid'		.' '. $answer->getId()			.' '.
	'a_text'	.' '. $answer->getAnswerText()	.' '.
	'x'			.' '. $x						.' '.
	'y'			.' '. $y;
$this->answer_positions[] = array('qid' => $question->getId() , 'aid' => $answer->getId() , 'a_text' => $answer->getAnswerText(), 'x' => $x , 'y' => $y);
*/