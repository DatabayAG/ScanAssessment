<?php
ilScanAssessmentPlugin::getInstance()->includeClass('assessment/questions/class.ilScanAssessmentQuestionHandler.php');

/**
 * Class ilScanAssessment_assFreestyleScanQuestion
 */
class ilScanAssessment_assFreestyleScanQuestion extends ilScanAssessmentQuestionHandler
{

	/**
	 * @param assFreestyleScanQuestion $question
	 * @param     $answer_position
	 * @param     $answer_text
	 * @param     $x
	 * @param     $y
	 * @param int $end_x
	 * @param int $end_y
	 * @return array
	 */
	protected function appendAnswer($question, $answer_position, $answer_text, $x, $y, $end_x = 0, $end_y = 0)
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
			'end_y'=> $end_y
		);
	}

	/**
	 * @param assFreestyleScanQuestion $question
	 * @param             $counter
	 * @return array
	 */
	public function writeAnswersWithCheckboxToPdf($question, $counter)
	{
		$answer_positions = array();
		$x1 = $this->pdf_helper->pdf->GetX();
		$y1 = $this->pdf_helper->pdf->GetY();
		$image = $question->getImagePath() . $question->getImageFilename();
		$this->pdf_helper->pdf->Image($image, 23, '', 0, 90, '', '', 'B', true, 300);
		$x2 = $this->pdf_helper->pdf->GetX();
		$y2 = $this->pdf_helper->pdf->GetY();
		$answer_positions[] = $this->appendAnswer($question, 0, '', $x1, $y1, $x2, $y2);
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
		$this->pdf_helper->pdf->setCellMargins(23, PDF_CHECKBOX_MARGIN);
		$ident_string = $counter . $this->getLetterFromNumber(0);
		$this->pdf_helper->writeHTML('(' . $ident_string . ') ');
		$answers[] = array('identifier' => $ident_string, 'question' => $question, 'answer' => '');
		return $answers;
	}

	/**
	 * @param $question
	 * @param $answers
	 * @param $columns
	 * @return array
	 */
	public function writeAnswersCheckboxForIdentifierToPdf($question, $answers, $columns)
	{
		$answer_positions	= array();

		$y = $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN;
		$this->pdf_helper->writeHTMLCell(0, 0, 0, $y, $answers[0]['identifier'], 0, 0, 0, TRUE, '', TRUE);
		$image = $question->getImagePath() . $question->getImageFilename();
		$this->pdf_helper->pdf->Image($image, 35, $y, 0, 90, '', '', 'B', true, 300);
		$this->pdf_helper->pdf->Ln(2);

		return $answer_positions;
	}


}