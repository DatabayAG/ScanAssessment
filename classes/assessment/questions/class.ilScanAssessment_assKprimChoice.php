<?php
ilScanAssessmentPlugin::getInstance()->includeClass('../interfaces/interface.ilScanAssessmentQuestion.php');
ilScanAssessmentPlugin::getInstance()->includeClass('assessment/questions/class.ilScanAssessmentQuestionHandler.php');

/**
 * Class ilScanAssessmentMultipleChoice
 */
class ilScanAssessment_assKprimChoice extends ilScanAssessmentQuestionHandler
{
	/**
	 * @param assQuestion $question
	 * @return array
	 */
	public function writeAnswersToPdf($question)
	{

		$false_label = '-';
		$true_label = '+';
		$answer_positions = array();
		$y_before_labels =  $this->pdf_helper->pdf->GetY();
		$this->pdf_helper->pdf->setCellMargins(20.5, PDF_CHECKBOX_MARGIN);
		$this->pdf_helper->pdf->Cell(2, 0,$true_label , 0, 0, 'R');
		$this->pdf_helper->pdf->setCellMargins(1, PDF_CHECKBOX_MARGIN);
		$this->pdf_helper->pdf->Cell(2, 0,$false_label , 0, 0, 'L');
		$this->pdf_helper->pdf->Ln();
		$y_after_labels =  $this->pdf_helper->pdf->GetY();
		$y_diff = $y_after_labels - $y_before_labels;

		foreach($question->getAnswers() as $key => $answer)
		{
			/** @var ASS_AnswerSimple $answer */
			$this->pdf_helper->pdf->Rect(34, $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN + 0.8, PDF_ANSWERBOX_W, PDF_ANSWERBOX_H, 'D', array('all' => $this->circleStyle));
			$this->pdf_helper->pdf->Rect(39, $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN + 0.8, PDF_ANSWERBOX_W, PDF_ANSWERBOX_H, 'D', array('all' => $this->circleStyle));
			$this->pdf_helper->pdf->setCellMargins(28, PDF_CHECKBOX_MARGIN);
			$this->pdf_helper->writeHTML($answer->getAnswertext());

			$x1 = $this->pdf_helper->pdf->GetX() + 34;
			$x2 = $this->pdf_helper->pdf->GetX() + 39;
			$y	= $this->pdf_helper->pdf->GetY() - $y_diff;

			$this->log->debug(sprintf('Answer checkbox for Question with id %s, answer order %s and text %s was added to correct => [%s, %s], wrong => [%s, %s]', $question->getId(), $answer->getPosition(), $answer->getAnswertext(), $x1 , $y, $x2 , $y));
			$answer_positions[] = array( 'correct' => array('qid' => $question->getId() , 'position' => $answer->getPosition() , 'a_text' => $answer->getAnswerText(), 'x' => $x1 , 'y' => $y, 'correctness' => 1),
										 'wrong' => array('qid' => $question->getId()  , 'position' => $answer->getPosition() , 'a_text' => $answer->getAnswerText(), 'x' => $x2 , 'y' => $y, 'correctness' => 0),
										 'type' => __CLASS__);
		}
		return $answer_positions;
	}
}

/*$this->answer_export[] =	'correct' .' ' .'qid' 		.' '. $question->getId()		.' '.
	'position'		.' '. $answer->getPosition() .' '.
	'a_text'	.' '. $answer->getAnswerText()	.' '.
	'x'			.' '. $x1						.' '.
	'y'			.' '. $y .' '.
	'wrong' .' ' .'qid' 		.' '. $question->getId()		.' '.
	'position'		.' '. $answer->getPosition() .' '.
	'a_text'	.' '. $answer->getAnswerText()	.' '.
	'x'			.' '. $x2						.' '.
	'y'			.' '. $y;
$this->answer_positions[] = array( 'correct' => array('qid' => $question->getId() , 'position' => $answer->getPosition() , 'a_text' => $answer->getAnswerText(), 'x' => $x1 , 'y' => $y),
								   'wrong' => array('qid' => $question->getId() , 'position' => $answer->getPosition() , 'a_text' => $answer->getAnswerText(), 'x' => $x2 , 'y' => $y));
*/