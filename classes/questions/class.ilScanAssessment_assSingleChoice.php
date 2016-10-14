<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/interfaces/interface.ilScanAssessmentQuestion.php';
/**
 * Class ilScanAssessmentMultipleChoice
 */
class ilScanAssessment_assSingleChoice implements ilScanAssessmentQuestion 
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
	 * @var array
	 */
	protected $circleStyle;

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
	 * @param assSingleChoice | assMultipleChoice $question[]
	 */
	public function writeQuestionToPdf($question)
	{
		$this->pdf_helper->pdf->Ln(1);
		$this->pdf_helper->writeHTML($question->getQuestion());
		$this->pdf_helper->pdf->Ln(2);

		foreach($question->getAnswers() as $key => $answer)
		{
			$this->pdf_helper->pdf->setCellMargins(26, PDF_CHECKBOX_MARGIN);
			$this->pdf_helper->pdf->Rect(34, $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN + 0.8, PDF_ANSWERBOX_W, PDF_ANSWERBOX_H, 'D', array('all' => $this->circleStyle));
			$this->pdf_helper->writeHTML($answer->getAnswerText());

			$x = $this->pdf_helper->pdf->GetX() + 34;
			$y = $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN;
			/*$this->answer_export[] =		'qid' 		.' '. $question->getId()		.' '.
				'aid'		.' '. $answer->getId()			.' '.
				'a_text'	.' '. $answer->getAnswerText()	.' '.
				'x'			.' '. $x						.' '.
				'y'			.' '. $y;
			$this->answer_positions[] = array('qid' => $question->getId() , 'aid' => $answer->getId() , 'a_text' => $answer->getAnswerText(), 'x' => $x , 'y' => $y);
			*/
			$this->log->debug(sprintf('Answer checkbox for Question with id %s and text %s was added to [%s, %s]', $question->getId(), $answer->getAnswerText(), $x , $y));

		}

		$this->pdf_helper->pdf->setCellMargins(PDF_CELL_MARGIN);
		$this->pdf_helper->pdf->Ln(2);
		$this->pdf_helper->pdf->Line($this->pdf_helper->pdf->GetX() + 10, $this->pdf_helper->pdf->GetY(), $this->pdf_helper->pdf->GetX() + 160, $this->pdf_helper->pdf->GetY());
	}
}