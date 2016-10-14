<?php

require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/interfaces/interface.ilScanAssessmentQuestion.php';

/**
 * Class ilScanAssessmentQuestionHandler
 */
class ilScanAssessmentQuestionHandler implements ilScanAssessmentQuestion 
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
	 * ilScanAssessment_assSingleChoice constructor.
	 * @param ilScanAssessmentPdfHelper $pdf_helper
	 * @param                           $circleStyle
	 */

	public function __construct(ilScanAssessmentPdfHelper $pdf_helper, $circleStyle)
	{
		$this->pdf_helper	= $pdf_helper;
		$this->log			= ilScanAssessmentLog::getInstance();
		$this->circleStyle	= $circleStyle;
	}

	public function writeQuestionToPdf($question)
	{
		$this->writeQuestionTextToPdf($question);
		$this->writeAnswersToPdf($question);
		$this->writeQuestionEndToPdf();
	}

	/**
	 * @param $question
	 */
	public function writeAnswersToPdf($question)
	{}

	/**
	 * 
	 */
	public function writeQuestionEndToPdf()
	{
		$this->pdf_helper->pdf->setCellMargins(PDF_CELL_MARGIN);
		$this->pdf_helper->pdf->Ln(2);
		$this->pdf_helper->pdf->Line($this->pdf_helper->pdf->GetX() + 10, $this->pdf_helper->pdf->GetY(), $this->pdf_helper->pdf->GetX() + 160, $this->pdf_helper->pdf->GetY());
	}

	/**
	 * @param assQuestion $question
	 */
	public function writeQuestionTextToPdf($question)
	{
		$this->pdf_helper->pdf->Ln(1);
		$this->pdf_helper->writeHTML($question->getQuestion());
		$this->pdf_helper->pdf->Ln(2);
	}
}