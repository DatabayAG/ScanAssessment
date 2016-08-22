<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentController.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfGenerationHelper.php');


/**
 * Class ilScanAssessmentLayoutController
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilPdfPreviewBuilder
{
	/**
	 * @var ilObjTest
	 */
	protected $test;

	/**
	 * @var assQuestion[]
	 */
	protected $questions;

	/**
	 * @var array
	 */
	protected $circleStyle;

	/**
	 * @var array
	 */
	protected $answer_positions = array();

	/**
	 * ilPdfPreviewBuilder constructor.
	 * @param ilObjTest $test
	 */
	public function __construct(ilObjTest $test)
	{
		$this->test			= $test;
		$this->questions	= array();
		$this->circleStyle	= array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(10,10,10));
	}

	/**
	 * 
	 */
	public function createDemoPdf()
	{
		$this->instantiateQuestions();
		$pdf_h = new ilPdfGenerationHelper();

		$this->addQrCodeToPage($pdf_h);

		$pdf_h->pdf->setCellMargins(15);
		$pdf_h->addPage();
		
		foreach($this->questions as $question)
		{
			$pdf_h->pdf->startTransaction();
			$start_page = $pdf_h->pdf->getPage();

			$this->writeQuestionToPdf($pdf_h, $question, $this->circleStyle);

			$end_page = $pdf_h->pdf->getPage();
			if  ($end_page != $start_page) 
			{
				$pdf_h->pdf->rollbackTransaction(true);
				$this->addQrCodeToPage($pdf_h);
				$pdf_h->addPage();
				$this->writeQuestionToPdf($pdf_h, $question, $this->circleStyle);
			} 
			else 
			{
				$pdf_h->pdf->commitTransaction();
			}
		}

		$this->printDebug($pdf_h);
		$pdf_h->output();
	}

	protected function printDebug($pdf_h)
	{
		$pdf_h->addPage();
		$pdf_h->writeHTML(implode($this->answer_positions, '<pre>'));
	}

	/**
	 * @param $pdf_h
	 */
	protected function addQrCodeToPage($pdf_h)
	{
		$page = $pdf_h->pdf->getPage();
		$pdf_h->pdf->setQRCodeOnThisPage(true);
		$pdf_h->createQRCode('DemoCode');
	}

	/**
	 * @param $pdf_h
	 * @param assQuestion $question
	 * @param $circleStyle
	 */
	protected function writeQuestionToPdf($pdf_h, $question, $circleStyle)
	{
		$pdf_h->writeHTML($question->getTitle());
		$pdf_h->pdf->Ln(1);
		$pdf_h->writeHTML($question->getQuestion());
		$pdf_h->pdf->Ln(2);
		foreach($question->getAnswers() as $key => $answer)
		{
			$pdf_h->pdf->setCellMargins(26, 2);
			$pdf_h->pdf->Rect(35, $pdf_h->pdf->GetY() + 2, PDF_ANSWERBOX_W, PDF_ANSWERBOX_H, 'D', array('all' => $circleStyle));
			$pdf_h->writeHTML($answer->getAnswerText());

			$this->answer_positions[] = $question->getId() .' '. $answer->getId() .' '. $answer->getAnswerText() .' '. $pdf_h->pdf->GetX() .' '. $pdf_h->pdf->GetY();
		}
		$pdf_h->pdf->setCellMargins(15);
		$pdf_h->pdf->Ln(2);
		$pdf_h->writeHTML('<hr/>');

		$pageData = array(
			'TOPLEFT'         => array(
				"x" => PDF_TOPLEFT_SYMBOL_X,
				"y" => PDF_TOPLEFT_SYMBOL_Y,
				"w" => PDF_TOPLEFT_SYMBOL_W
			),
			'BOTTOMLEFT'      => array(
				"x" => PDF_TOPLEFT_SYMBOL_X,
				"y" => $pdf_h->pdf->getPageHeight() + PDF_BOTTOMLEFT_SYMBOL_Y,
				"w" => PDF_TOPLEFT_SYMBOL_W
			),
			'BOTTOMRIGHT'     => array(
				"x" => $pdf_h->pdf->getPageWidth() - PDF_BOTTOMRIGHT_QR_MARGIN_X,
				"y" => $pdf_h->pdf->getPageHeight() - PDF_BOTTOMRIGHT_QR_MARGIN_Y,
				"w" => PDF_BOTTOMRIGHT_QR_W
			));
		$a = 0;
	}
	protected function instantiateQuestions()
	{
		require_once 'Modules/TestQuestionPool/classes/class.assQuestion.php';
		foreach($this->test->getQuestions() as $key => $value)
		{
			$this->questions[] = assQuestion::_instantiateQuestion($value);
		}
	}
}