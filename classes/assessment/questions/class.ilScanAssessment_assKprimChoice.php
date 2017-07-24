<?php
ilScanAssessmentPlugin::getInstance()->includeClass('../interfaces/interface.ilScanAssessmentQuestion.php');
ilScanAssessmentPlugin::getInstance()->includeClass('assessment/questions/class.ilScanAssessmentQuestionHandler.php');

/**
 * Class ilScanAssessmentMultipleChoice
 */
class ilScanAssessment_assKprimChoice extends ilScanAssessmentQuestionHandler
{
	/**
	 * @param      $question
	 * @param      $answer_position
	 * @param      $answer_text
	 * @param      $x1
	 * @param      $x2
	 * @param      $y
	 * @param int  $end_x
	 * @param null $ident_string
	 * @return array
	 */
	protected function appendAnswer($question, $answer_position, $answer_text, $x1, $x2, $y, $end_x = 0, $ident_string = null)
	{
		$this->log->debug(sprintf('Answer checkbox for Question with id %s, answer order %s and text %s was added to correct => [%s, %s], wrong => [%s, %s]', $question->getId(), $answer_position, $answer_text, $x1 , $y, $x2 , $y));

		return array( 'correct' => array('qid' => $question->getId() , 'position' => $answer_position , 'a_text' => $answer_text, 'x' => $x1 , 'y' => $y, 'correctness' => 1),
					  'wrong' => array('qid' => $question->getId()  , 'position' => $answer_position , 'a_text' => $answer_text, 'x' => $x2 , 'y' => $y, 'correctness' => 0),
					  'type' => __CLASS__,
					  'end_x'=> $end_x,
					  'x' => $x1,
					  'ident' => $ident_string);
	}

	/**
	 * @param assKprimChoice $question
	 * @return array
	 */
	public function writeAnswersWithCheckboxToPdf($question, $counter)
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
		$answers_org = $question->getAnswers();
		if($question->getShuffle() == 1)
		{
			shuffle($answers_org);
		}
		foreach($answers_org as $key => $answer)
		{
			/** @var ASS_AnswerSimple $answer */
			$this->pdf_helper->pdf->Rect(34, $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN + 0.8, PDF_ANSWERBOX_W, PDF_ANSWERBOX_H, 'D', array('all' => $this->circleStyle));
			$this->pdf_helper->pdf->Rect(39, $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN + 0.8, PDF_ANSWERBOX_W, PDF_ANSWERBOX_H, 'D', array('all' => $this->circleStyle));
			$this->pdf_helper->pdf->setCellMargins(28, PDF_CHECKBOX_MARGIN);
			$this->pdf_helper->writeHTML($answer->getAnswertext());

			$x1 = 34;
			$x2 = 39;
			$y	= $this->pdf_helper->pdf->GetY() - $y_diff  + 0.8;
			$x1_relative = $x1 - PDF_TOPLEFT_SYMBOL_X;
			$x2_relative = $x2 - PDF_TOPLEFT_SYMBOL_X;
			$y_relative = $y - PDF_TOPLEFT_SYMBOL_Y;
			$answer_positions[] = $this->appendAnswer($question, $answer->getPosition(), $answer->getAnswerText(), $x1_relative, $x2_relative, $y_relative);
		}
		return $answer_positions;
	}

	/**
	 * @param assSingleChoice | assMultipleChoice $question
	 * @param             $counter
	 * @return array
	 */
	public function writeAnswersWithIdentifierToPdf($question, $counter)
	{
		$answer_counter	= 0;
		$answers = array();
		$answers_org = $question->getAnswers();
		if($question->getShuffle() == 1)
		{
			shuffle($answers_org);
		}
		foreach($answers_org as $key => $answer)
		{
			$ident_string = $counter . $this->getLetterFromNumber($answer_counter);
			/** @var ASS_AnswerSimple $answer */
			$this->pdf_helper->pdf->setCellMargins(23, PDF_CHECKBOX_MARGIN);
			$this->pdf_helper->writeHTML('(' . $ident_string . ') ' . $answer->getAnswertext());

			$answers[] = array('identifier' => $ident_string, 'question' => $question, 'answer' => $answer);
			$answer_counter++;
		}
		return $answers;
	}

	/**
	 * @param $question
	 * @param $answers
	 * @return array
	 */
	public function writeAnswersCheckboxForIdentifierToPdf($question, $answers, $columns)
	{
		$answer_positions	= array();
		$false_label = '-';
		$true_label = '+';
		$this->pdf_helper->pdf->setCellMargins(($columns * 25) - 14, PDF_CHECKBOX_MARGIN);
		$this->pdf_helper->pdf->Cell(2, 0,$true_label , 0, 0, 'R');
		$this->pdf_helper->pdf->setCellMargins(1, PDF_CHECKBOX_MARGIN);
		$this->pdf_helper->pdf->Cell(2, 0,$false_label , 0, 0, 'L');
		$this->pdf_helper->pdf->Ln();
		foreach($answers as $key => $answer)
		{
			/** @var ASS_AnswerSimple $answer */
			$pos_x = $columns * 25;
			$this->pdf_helper->pdf->setCellMargins(23, PDF_CHECKBOX_MARGIN);
			$this->pdf_helper->pdf->Rect($pos_x, $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN + 0.8, PDF_ANSWERBOX_W, PDF_ANSWERBOX_H, 'D', array('all' => $this->circleStyle));
			$this->pdf_helper->pdf->Rect($pos_x + 5, $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN + 0.8, PDF_ANSWERBOX_W, PDF_ANSWERBOX_H, 'D', array('all' => $this->circleStyle));
			$pos_y = $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN  - 0.2;
			$x1 = $this->pdf_helper->pdf->GetX() + $pos_x;
			$x2 = $x1 + 5;
			$y	= $this->pdf_helper->pdf->GetY();
			$x1_relative = $pos_x - PDF_TOPLEFT_SYMBOL_X;
			$x2_relative = $pos_x + 5 - PDF_TOPLEFT_SYMBOL_X;
			$y_relative = $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN + 0.8 - PDF_TOPLEFT_SYMBOL_Y;
			$this->pdf_helper->writeHTMLCell(0, 0, ($columns * 25) - 15, $pos_y, $answer['identifier'], 0, 0, 0, TRUE, '', TRUE);
			//$this->pdf_helper->pdf->Cell($x, $y, $answer['identifier']);
			$answer_positions[] = $this->appendAnswer($question, $answer['answer']->getPosition(), $answer['answer']->getAnswerText(), $x1_relative, $x2_relative, $y_relative, $x2 + 15, $answer['identifier']);
			$this->pdf_helper->pdf->Ln();
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