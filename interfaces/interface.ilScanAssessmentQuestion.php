<?php

/**
 * Interface ilScanAssessmentQuestion
 */
interface ilScanAssessmentQuestion
{
	/**
	 * @param assQuestion $question
	 */
	public function writeQuestionToPdf($question);

	/**
	 * @param assQuestion $question
	 * @param ilObjTest $test
	 * @param $counter
	 */
	public function writeQuestionTitleToPdf($question, $test , $counter);

	/**
	 * @param assQuestion $question
	 */
	public function writeAnswersToPdf($question);
}