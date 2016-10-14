<?php

require_once 'Modules/TestQuestionPool/classes/class.assQuestion.php';
ilScanAssessmentPlugin::getInstance()->includeClass('questions/class.ilScanAssessment_assMultipleChoice.php');
ilScanAssessmentPlugin::getInstance()->includeClass('questions/class.ilScanAssessment_assKprimChoice.php');
/**
 * Class ilScanAssessmentPdfQuestionBuilder
 */
class ilScanAssessmentPdfQuestionBuilder
{

	protected $supported_question_types = array(
		'assSingleChoice',
		'assMultipleChoice',
		'assKprimChoice'
	);

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

		$this->test			= $test;
		$this->pdf_helper	= $pdf;
		$this->log			= ilScanAssessmentLog::getInstance();
		$this->questions	= array();
		$this->circleStyle	= array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(10,10,10));
	}

	/**
	 * @param assQuestion $question
	 * @param $counter
	 */
	public function addQuestionToPdf($question, $counter)
	{
		$class = 'ilScanAssessment_' . $question->getQuestionType();
		/** @var ilScanAssessmentQuestion $instance */
		$instance = new $class($this->pdf_helper, $this->circleStyle);
		$instance->writeQuestionTitleToPdf($question, $this->test , $counter);
		$instance->writeQuestionToPdf($question);
	}

	/**
	 * @return array|assQuestion[]
	 */
	public function instantiateQuestions()
	{
		foreach($this->test->getQuestions() as $key => $value)
		{
			$question = assQuestion::_instantiateQuestion($value);
			if(in_array($question->getQuestionType(), $this->supported_question_types))
			{
				$this->questions[]	= $question;
				$this->log->debug(sprintf('Question with id %s of type %s instantiated.', $question->getId(), $question->getQuestionType()));
			}
			else
			{
				$this->log->warn(sprintf('Question with id %s of type %s is not supported.', $question->getId(), $question->getQuestionType()));
			}
		}
		return $this->questions;
	}

	/**
	 * @param ilScanAssessmentPdfHelper $pdf_h
	 */
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