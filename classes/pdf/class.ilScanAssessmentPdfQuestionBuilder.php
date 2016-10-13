<?php

require_once 'Modules/TestQuestionPool/classes/class.assQuestion.php';
/**
 * Class ilScanAssessmentPdfQuestionBuilder
 */
class ilScanAssessmentPdfQuestionBuilder
{

	const TITLE_AND_POINTS		= 0;
	const ONLY_TITLE			= 1;
	const QUESTION_NUMBER_ONLY	= 2;

	protected $supported_question_types = array(
		'assSingleChoice',
		'assMultipleChoice'
	);
	
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
	 * @var ilScanAssessmentLog
	 */
	protected $log;

	/**
	 * ilScanAssessmentPdfQuestionBuilder constructor.
	 * @param ilObjTest                 $test
	 * @param ilScanAssessmentPdfHelper $pdf
	 */
	public function __construct(ilObjTest $test, ilScanAssessmentPdfHelper $pdf)
	{
		global $lng;

		$this->lng			= $lng;
		$this->test			= $test;
		$this->pdf_helper	= $pdf;
		$this->log			= ilScanAssessmentLog::getInstance();
		$this->questions	= array();
		$this->circleStyle	= array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(10,10,10));
	}

	public function addQuestionToPdf($question, $counter)
	{
		$this->writeQuestionTitleToPdf($question, $counter);
		$this->writeQuestionToPdf($question);
	}
	
	/**
	 * @param assQuestion $question
	 * @param $counter
	 */
	protected function writeQuestionTitleToPdf($question, $counter)
	{
		$this->pdf_helper->pdf->Ln(2);
		$title = $this->getQuestionTitle($question, $counter);
		$this->pdf_helper->pdf->SetTextColor(0);
		$this->pdf_helper->pdf->SetFillColor(255, 255, 255);
		$this->pdf_helper->pdf->SetFont(PDF_DEFAULT_FONT,'B',PDF_DEFAULT_FONT_SIZE_HEAD);
		$this->pdf_helper->pdf->Cell(80, 5, $title , 0, 0, 'L', 1);
		$this->pdf_helper->pdf->SetFont(PDF_DEFAULT_FONT,'',PDF_DEFAULT_FONT_SIZE_HEAD);
		$this->pdf_helper->pdf->Ln();
	}

	/**
	 * @param assQuestion $question[]
	 */
	protected function writeQuestionToPdf($question)
	{
		$this->pdf_helper->pdf->Ln(1);
		$this->pdf_helper->writeHTML($question->getQuestion());
		$this->pdf_helper->pdf->Ln(2);

		foreach($question->getAnswers() as $key => $answer)
		{
			$this->pdf_helper->pdf->setCellMargins(26, PDF_CHECKBOX_MARGIN);
			$this->pdf_helper->pdf->Rect(34, $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN + 0.8, PDF_ANSWERBOX_W, PDF_ANSWERBOX_H, 'D', array('all' => $this->circleStyle));
			$this->pdf_helper->writeHTML($answer->getAnswerText());

			$this->answer_positions[] = $question->getId() .' '. $answer->getId() .' '. $answer->getAnswerText() .' '. $this->pdf_helper->pdf->GetX() .' '. $this->pdf_helper->pdf->GetY();
			$x = $this->pdf_helper->pdf->GetX() + 34;
			$y = $this->pdf_helper->pdf->GetY() + PDF_CHECKBOX_MARGIN;
			$this->answer_export[] =		'qid' .' '. $question->getId().' '.
				'aid'.' '. $answer->getId() .' '.
				'a_text' .' '. $answer->getAnswerText().' '.
				'x' .' '. $x .' '.
				'y' .' '.  $y;
			$this->answer_positions[] = array('qid' => $question->getId() , 'aid' => $answer->getId() , 'a_text' => $answer->getAnswerText(), 'x' => $x , 'y' => $y);
			$this->log->debug(sprintf('Answer checkbox for Question with id %s and text %s was added to X:%s Y:%s', $question->getId(), $answer->getAnswerText(), $x , $y));

		}

		$this->pdf_helper->pdf->setCellMargins(PDF_CELL_MARGIN);
		$this->pdf_helper->pdf->Ln(2);
		$this->pdf_helper->pdf->Line($this->pdf_helper->pdf->GetX() + 10, $this->pdf_helper->pdf->GetY(), $this->pdf_helper->pdf->GetX() + 160, $this->pdf_helper->pdf->GetY());
	}

	/**
	 * @return array|assQuestion[]
	 */
	public function instantiateQuestions()
	{
		foreach($this->test->getQuestions() as $key => $value)
		{
			$question			= assQuestion::_instantiateQuestion($value);
			if(in_array($question->getQuestionType(), $this->supported_question_types))
			{
				$this->questions[]	= $question;
				$this->log->debug(sprintf('Question with id %s type %s instantiated.', $question->getId(), $question->getQuestionType()));
			}
			else
			{
				$this->log->warn(sprintf('Question with id %s type %s is not supported.', $question->getId(), $question->getQuestionType()));
			}
		}
		return $this->questions;
	}

	/**
	 * @param assQuestion $question
	 * @param $counter
	 * @return string
	 */
	protected function getQuestionTitle($question, $counter)
	{
		$title			= $this->lng->txt('question') . ' ' . $counter . ': ';
		$title_setting	= $this->test->getTitleOutput();
		if($title_setting < self::QUESTION_NUMBER_ONLY)
		{
			$title .= $question->getTitle();
			if($title_setting < self::ONLY_TITLE)
			{
				$title .= $this->buildPointsText($question->getMaximumPoints());
			}
		}

		return $title;
	}

	/**
	 * @param $points
	 * @return string
	 */
	protected function buildPointsText($points)
	{
		$points_txt = $this->lng->txt('point');
		if($points > 1)
		{
			$points_txt = $this->lng->txt('points');
		}
		return ' (' . $points . ' ' . $points_txt . ')';
	}

	public function printDebug($pdf_h)
	{
		$pdf_h->addPage();
		$pdf_h->writeHTML(implode($this->answer_export, '<pre>'));

		$this->log->debug(sprintf('Question positions: %s', json_encode($this->answer_positions)));
		#$pdf_h->writeHTML(implode($this->answer_positions, '<pre>'));
		#$matriculation = $pdf_h->pdf->getMatriculationInformation();
		#$pdf_h->writeHTML(print_r($matriculation['head_row'], '<pre>'));
		#$pdf_h->writeHTML(print_r($matriculation['value_rows'], '<pre>'));
	}
}