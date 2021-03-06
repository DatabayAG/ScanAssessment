<?php
ilScanAssessmentPlugin::getInstance()->includeClass('assessment/questions/class.ilScanAssessmentQuestionHandler.php');

/**
 * Class ilScanAssessment_assSingleChoice
 */
class ilScanAssessment_assSingleChoice extends ilScanAssessmentQuestionHandler
{

	/**
	 * @param assQuestion $question
	 * @param      $answer_position
	 * @param      $answer_text
	 * @param      $x
	 * @param      $y
	 * @param int  $end_x
	 * @param null $ident_string
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

	/**
	 * @param assSingleChoice | assMultipleChoice $question
	 * @param             $counter
	 * @return array
	 */
	public function writeAnswersWithCheckboxToPdf($question, $counter)
	{
		$answer_positions = array();
		$answers = $question->getAnswers();
		if($question->getShuffle() == 1)
		{
			shuffle($answers);
		}
		foreach($answers as $key => $answer)
		{
			/** @var ASS_AnswerSimple $answer */
			$this->pdf_helper->pdf->setCellMargins(23, PDF_CHECKBOX_MARGIN);
			$x = 34;
			$y = $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN + 0.8;

			$this->pdf_helper->pdf->Rect($x, $y, PDF_ANSWERBOX_W, PDF_ANSWERBOX_H, 'D', array('all' => $this->circleStyle));

			$x_relative = $x - PDF_TOPLEFT_SYMBOL_X;
			$y_relative = $y - PDF_TOPLEFT_SYMBOL_Y;

			$this->pdf_helper->writeHTML($answer->getAnswertext());
			$answer_positions[] = $this->appendAnswer($question, $answer->getOrder(), $answer->getAnswertext(), $x_relative, $y_relative);
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
		$answers	= array();
		$answer_counter	= 0;
		$answers_org = $question->getAnswers();
		if($question->getShuffle() == 1)
		{
			shuffle($answers_org);
		}
		foreach($answers_org as $key => $answer)
		{
			/** @var ASS_AnswerSimple $answer */
			$this->pdf_helper->pdf->setCellMargins(23, PDF_CHECKBOX_MARGIN);
			$ident_string = $counter . $this->getLetterFromNumber($answer_counter);
			$this->pdf_helper->writeHTML('(' . $ident_string . ') ' . $this->removeUnwantedTag($answer->getAnswertext()));
			$answers[] = array('identifier' => $ident_string, 'question' => $question, 'answer' => $answer);
			$answer_counter++;
		}
		return $answers;
	}

	/**
	 * @param assQuestion $question
	 * @param             $answers
	 * @param             $columns
	 * @return array
	 */
	public function writeAnswersCheckboxForIdentifierToPdf($question, $answers, $columns)
	{
		$answer_positions	= array();
		foreach($answers as $key => $answer)
		{
			/** @var ASS_AnswerSimple $answer */
			$this->pdf_helper->pdf->setCellMargins(23, PDF_CHECKBOX_MARGIN);
			$this->pdf_helper->pdf->Rect($columns * 25, $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN + 0.8, PDF_ANSWERBOX_W, PDF_ANSWERBOX_H, 'D', array('all' => $this->circleStyle));
			$x = ($columns * 25);
			$y = $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN - 0.2;
			$this->pdf_helper->writeHTMLCell(0, 0, ($columns * 25) - 20, $y, $answer['identifier'], 0, 0, 0, TRUE, '', TRUE);
			//$this->pdf_helper->pdf->Cell($x, $y, $answer['identifier']);
			$x_relative = $x - PDF_TOPLEFT_SYMBOL_X;
			$y_relative = $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN + 0.8 - PDF_TOPLEFT_SYMBOL_Y;

			$answer_positions[] = $this->appendAnswer($question, $answer['answer']->getOrder(), $answer['answer']->getAnswerText(), $x_relative, $y_relative, $x + 25, $answer['identifier']);
			$this->pdf_helper->pdf->Ln();
		}
		return $answer_positions;
	}


}