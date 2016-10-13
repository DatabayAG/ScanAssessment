<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentController.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfHelper.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfQuestionBuilder.php');
ilScanAssessmentPlugin::getInstance()->includeClass('log/class.ilScanAssessmentLog.php');


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
	 * @var
	 */
	protected $log;

	/**
	 * ilPdfPreviewBuilder constructor.
	 * @param ilObjTest $test
	 */
	public function __construct(ilObjTest $test)
	{
		$this->test	= $test;
		$this->log	= ilScanAssessmentLog::getInstance();
	}

	/**
	 * 
	 */
	public function createDemoPdf()
	{
		$this->log->info('Starting to create demo pdf...');
		$pdf_h	= new ilScanAssessmentPdfHelper();
		/** @var tcpdf $pdf */
		$pdf	= $pdf_h->pdf; 
		$question_builder = new ilScanAssessmentPdfQuestionBuilder($this->test, $pdf_h);
		$questions = $question_builder->instantiateQuestions();

		$this->addQrCodeToPage($pdf_h);

		$pdf->setCellMargins(PDF_CELL_MARGIN);
		$pdf_h->addPage();
		$counter = 1;
		foreach($questions as $question)
		{
			$pdf->startTransaction();
			$start_page = $pdf->getPage();

			$this->addQrCodeToPage($pdf_h);
			$question_builder->writeQuestionTitleToPdf($question, $counter);
			$question_builder->writeQuestionToPdf($question);

			$end_page = $pdf->getPage();
			if($end_page != $start_page)
			{
				$pdf->rollbackTransaction(true);
				$pdf_h->addPage();
				$question_builder->writeQuestionTitleToPdf($question, $counter);
				$question_builder->writeQuestionToPdf($question);
			}
			else
			{
				$pdf->commitTransaction();
			}
			$counter++;
		}
		$question_builder->printDebug($pdf_h);
		$pdf_h->output();
		$this->log->info('Creating demo pdf finished.');
	}

	/**
	 * @param $pdf_h
	 */
	protected function addQrCodeToPage($pdf_h)
	{
		$pdf_h->pdf->getPage();
		$pdf_h->pdf->setQRCodeOnThisPage(true);
		$pdf_h->createQRCode('DemoCode');
	}

	
}