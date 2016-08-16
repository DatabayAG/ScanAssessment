<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentController.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilPdfGenerationHelper.php');


/**
 * Class ilScanAssessmentLayoutController
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilPdfPreviewBuilder
{
	protected $test;

	protected $questions;
	
	/**
	 * ilPdfPreviewBuilder constructor.
	 * @param ilObjTest $test
	 */
	public function __construct(ilObjTest $test)
	{
		$this->test			= $test;
		$this->questions	= array();
	}

	public function createDemoPdf()
	{
		$this->instantiateQuestions();
		$a = new ilPdfGenerationHelper();

		$this->addQrCodeToPage($a);

		$a->pdf->setCellMargins(15);
		$a->addPage();
		$circleStyle = array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(10,10,10));

		foreach($this->questions as $question)
		{
			$a->pdf->startTransaction();
			$start_page = $a->pdf->getPage();

			$this->writeQuestionToPdf($a, $question, $circleStyle);

			$end_page = $a->pdf->getPage();
			if  ($end_page != $start_page) 
			{
				$a->pdf->rollbackTransaction(true);
				$a->addPage();
				$this->addQrCodeToPage($a);
				$this->writeQuestionToPdf($a, $question, $circleStyle);
			} 
			else 
			{
				$a->pdf->commitTransaction();
			}
		}

		$a->output();
	}

	protected function addQrCodeToPage($a)
	{
		$page = $a->pdf->getPage();
		$a->pdf->setQRCodeOnThisPage(true);
		$a->pdf->setQRCodeOnThisPage(true);
		$a->createQRCode('DemoCode');
	}

	/**
	 * @param $a
	 * @param $question
	 * @param $circleStyle
	 */
	protected function writeQuestionToPdf($a, $question, $circleStyle)
	{
		$a->writeHTML($question->getTitle());
		$a->pdf->Ln(1);
		$a->writeHTML($question->getQuestion());
		$a->pdf->Ln(6);
		foreach($question->getAnswers() as $key => $answer)
		{
			$a->pdf->Rect(35, $a->pdf->GetY(), PDF_ANSWERBOX_W, PDF_ANSWERBOX_H, 'D', array('all' => $circleStyle));
			$a->pdf->setCellMargins(28, 2);
			//$a->writeHTML('<div style="border:1px solid red;">' . $answer->getAnswerText() .'</div>');
			$a->writeHTML($answer->getAnswerText());

		}
		$a->pdf->setCellMargins(15);
		$a->pdf->Ln(2);
		$a->writeHTML('<hr/>');
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