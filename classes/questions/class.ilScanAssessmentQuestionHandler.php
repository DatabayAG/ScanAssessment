<?php

require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/interfaces/interface.ilScanAssessmentQuestion.php';

/**
 * Class ilScanAssessmentQuestionHandler
 */
class ilScanAssessmentQuestionHandler implements ilScanAssessmentQuestion 
{
	const TITLE_AND_POINTS		= 0;
	const ONLY_TITLE			= 1;
	const QUESTION_NUMBER_ONLY	= 2;

	/**
	 * @var ilLanguage
	 */
	protected $lng;

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
		/** @var $lng ilLanguage */
		global $lng;

		$this->lng			= $lng;
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
	protected function writeQuestionEndToPdf()
	{
		$this->pdf_helper->pdf->setCellMargins(PDF_CELL_MARGIN);
		$this->pdf_helper->pdf->Ln(2);
		$this->pdf_helper->pdf->Line($this->pdf_helper->pdf->GetX() + 10, $this->pdf_helper->pdf->GetY(), $this->pdf_helper->pdf->GetX() + 160, $this->pdf_helper->pdf->GetY());
	}

	/**
	 * @param assQuestion $question
	 */
	protected function writeQuestionTextToPdf($question)
	{
		$this->pdf_helper->pdf->Ln(1);
		$this->pdf_helper->writeHTML($question->getQuestion());
		$this->pdf_helper->pdf->Ln(2);
	}

	/**
	 * @param assQuestion $question
	 * @param ilObjTest $test
	 * @param $counter
	 */
	public function writeQuestionTitleToPdf($question, $test, $counter)
	{
		$this->pdf_helper->pdf->Ln(2);
		$title = $this->getQuestionTitle($question, $test, $counter);
		$this->pdf_helper->pdf->SetTextColor(0);
		$this->pdf_helper->pdf->SetFillColor(255, 255, 255);
		$this->pdf_helper->pdf->SetFont(PDF_DEFAULT_FONT,'B',PDF_DEFAULT_FONT_SIZE_HEAD);
		$this->pdf_helper->pdf->Cell(80, 5, $title , 0, 0, 'L', 1);
		$this->pdf_helper->pdf->SetFont(PDF_DEFAULT_FONT,'',PDF_DEFAULT_FONT_SIZE);
		$this->pdf_helper->pdf->Ln();
	}

	/**
	 * @param assQuestion $question
	 * @param ilObjTest $test
	 * @param $counter
	 * @return string
	 */
	protected function getQuestionTitle($question, $test, $counter)
	{
		$title			= $this->lng->txt('question') . ' ' . $counter . ': ';
		$title_setting	= $test->getTitleOutput();
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
}