<?php

require_once 'Modules/TestQuestionPool/classes/class.assQuestion.php';

/**
 * Class ilScanAssessmentPdfQuestionBuilder
 */
class ilScanAssessmentPdfQuestionBuilder
{

	/**
	 * @var ilLanguage
	 */
	protected $lng;

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
	protected $answer_positions = array();

	/**
	 * @var array
	 */
	protected $answer_export = array();

	/**
	 * @var ilScanAssessmentPdfHelper
	 */
	protected $pdf_helper;

	/**
	 * @var array
	 */
	protected $circleStyle;

	/**
	 * ilScanAssessmentPdfQuestionBuilder constructor.
	 * @param ilObjTest $test
	 * @param ilScanAssessmentPdfHelper $pdf
	 */
	public function __construct(ilObjTest $test, $pdf)
	{
		global $lng;

		$this->lng			= $lng;
		$this->test			= $test;
		$this->pdf_helper	= $pdf;
		$this->questions	= array();
		$this->circleStyle	= array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(10,10,10));
	}

	/**
	 * @param assQuestion $question
	 */
	public function writeQuestionToPdf($question)
	{
		$this->pdf_helper->pdf->Ln(1);
		$this->pdf_helper->writeHTML($question->getQuestion());
		$this->pdf_helper->pdf->Ln(2);
		foreach($question->getAnswers() as $key => $answer)
		{
			$this->pdf_helper->pdf->setCellMargins(26, PDF_CHECKBOX_MARGIN);
			$this->pdf_helper->pdf->Rect(34, $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN, PDF_ANSWERBOX_W, PDF_ANSWERBOX_H, 'D', array('all' => $this->circleStyle));
			$this->pdf_helper->writeHTML($answer->getAnswerText());

			$this->answer_positions[] = $question->getId() .' '. $answer->getId() .' '. $answer->getAnswerText() .' '. $this->pdf_helper->pdf->GetX() .' '. $this->pdf_helper->pdf->GetY();
			$x = $this->pdf_helper->pdf->GetX() + 34;
			$y = $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN;
			$this->answer_export[] =		'qid' .' '. $question->getId().' '.
				'aid'.' '. $answer->getId() .' '.
				'a_text' .' '. $answer->getAnswerText().' '.
				'x' .' '. $x .' '.
				'y' .' '.  $y;
		}

		$this->pdf_helper->pdf->setCellMargins(PDF_CELL_MARGIN);
		$this->pdf_helper->pdf->Ln(2);
		$this->pdf_helper->writeHTML('<hr/>');

		$pageData = array(
			'TOPLEFT'         => array(
				"x" => PDF_TOPLEFT_SYMBOL_X,
				"y" => PDF_TOPLEFT_SYMBOL_Y,
				"w" => PDF_TOPLEFT_SYMBOL_W
			),
			'BOTTOMLEFT'      => array(
				"x" => PDF_TOPLEFT_SYMBOL_X,
				"y" => $this->pdf_helper->pdf->getPageHeight() + PDF_BOTTOMLEFT_SYMBOL_Y,
				"w" => PDF_TOPLEFT_SYMBOL_W
			),
			'BOTTOMRIGHT'     => array(
				"x" => $this->pdf_helper->pdf->getPageWidth() - PDF_BOTTOMRIGHT_QR_MARGIN_X,
				"y" => $this->pdf_helper->pdf->getPageHeight() - PDF_BOTTOMRIGHT_QR_MARGIN_Y,
				"w" => PDF_BOTTOMRIGHT_QR_W
			),
			'PAGESIZE'     => array(
				"width" => $this->pdf_helper->pdf->getPageWidth(),
				"height" => $this->pdf_helper->pdf->getPageHeight(),
			)
		);
	}
	public function instantiateQuestions()
	{
		foreach($this->test->getQuestions() as $key => $value)
		{
			$this->questions[] = assQuestion::_instantiateQuestion($value);
		}
		return $this->questions;
	}

	public function writeQuestionTitleToPdf($question, $counter)
	{
		$title = $this->getQuestionTitle($question, $counter);
		$this->pdf_helper->pdf->SetTextColor(0);
		$this->pdf_helper->pdf->SetFillColor(255, 255, 255);
		$this->pdf_helper->pdf->SetFont(PDF_DEFAULT_FONT,'B',PDF_DEFAULT_FONT_SIZE_HEAD);
		$this->pdf_helper->pdf->Cell(80, 5, $title , 0, 0, 'L', 1);
		$this->pdf_helper->pdf->SetFont(PDF_DEFAULT_FONT,'',PDF_DEFAULT_FONT_SIZE_HEAD);
		$this->pdf_helper->pdf->Ln();
	}

	protected function getQuestionTitle($question, $counter)
	{
		$title = $this->lng->txt('question') . ' ' . $counter;
		if(true)
		{
			$title .= ': ' .$question->getTitle();
		}

		return $title;
	}

	public function printDebug($pdf_h)
	{
		$pdf_h->addPage();
		$pdf_h->writeHTML(implode($this->answer_positions, '<pre>'));
		$pdf_h->writeHTML(implode($this->answer_export, '<pre>'));
		#$matriculation = $pdf_h->pdf->getMatriculationInformation();
		#$pdf_h->writeHTML(print_r($matriculation['head_row'], '<pre>'));
		#$pdf_h->writeHTML(print_r($matriculation['value_rows'], '<pre>'));
	}
}