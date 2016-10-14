<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/interfaces/interface.ilScanAssessmentQuestion.php';

/**
 * Class ilScanAssessmentKprim
 */
class ilScanAssessment_assKprimChoice implements ilScanAssessmentQuestion 
{
	/**
	 * @var ilScanAssessmentPdfHelper
	 */
	protected $pdf_helper;

	/**
	 * @var ilScanAssessmentLog
	 */
	protected $log;

	/**
	 * ilScanAssessmentMultipleChoice constructor.
	 * @param ilScanAssessmentPdfHelper $pdf_helper
	 */
	public function __construct(ilScanAssessmentPdfHelper $pdf_helper, $circleStyle)
	{
		$this->pdf_helper	= $pdf_helper;
		$this->log			= ilScanAssessmentLog::getInstance();
		$this->circleStyle	= $circleStyle;
	}

	/**
	 * @param assKprimChoice $question
	 */
	public function writeQuestionToPdf($question)
	{
		$this->pdf_helper->pdf->Ln(1);
		$this->pdf_helper->writeHTML($question->getQuestion());
		$this->pdf_helper->pdf->Ln(2);

		$false_label = '-';
		$true_label = '+';

		$this->pdf_helper->pdf->setCellMargins(20.8, PDF_CHECKBOX_MARGIN);
		$this->pdf_helper->pdf->Cell(2, 0,$true_label , 0, 0, 'R');
		$this->pdf_helper->pdf->setCellMargins(1, PDF_CHECKBOX_MARGIN);
		$this->pdf_helper->pdf->Cell(2, 0,$false_label , 0, 0, 'L');
		$this->pdf_helper->pdf->Ln();

		foreach($question->getAnswers() as $key => $answer)
		{
			$this->pdf_helper->pdf->Rect(34, $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN + 0.8, PDF_ANSWERBOX_W, PDF_ANSWERBOX_H, 'D', array('all' => $this->circleStyle));
			$this->pdf_helper->pdf->Rect(39, $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN + 0.8, PDF_ANSWERBOX_W, PDF_ANSWERBOX_H, 'D', array('all' => $this->circleStyle));
			$this->pdf_helper->pdf->setCellMargins(28, PDF_CHECKBOX_MARGIN);
			$this->pdf_helper->writeHTML($answer->getAnswerText());

			$x1 = $this->pdf_helper->pdf->GetX() + 34;
			$x2 = $this->pdf_helper->pdf->GetX() + 39;
			$y = $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN;
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
			$this->log->debug(sprintf('Answer checkbox for Question with id %s and text %s was added to correct => [%s, %s], wrong => [%s, %s]', $question->getId(), $answer->getAnswerText(), $x1 , $y, $x2 , $y));

		}

		$this->pdf_helper->pdf->setCellMargins(PDF_CELL_MARGIN);
		$this->pdf_helper->pdf->Ln(2);
		$this->pdf_helper->pdf->Line($this->pdf_helper->pdf->GetX() + 10, $this->pdf_helper->pdf->GetY(), $this->pdf_helper->pdf->GetX() + 160, $this->pdf_helper->pdf->GetY());
	}
}