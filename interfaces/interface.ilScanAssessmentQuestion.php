<?php

/**
 * Interface ilScanAssessmentQuestion
 * @author Guido Vollbach <gvollbach@databay.de>
 */
interface ilScanAssessmentQuestion
{
	/**
	 * @param assQuestion $question
	 * @param $counter
	 * @return array
	 */
	public function writeQuestionToPdf($question, $counter);

	/**
	 * @param assQuestion $question
	 * @param ilObjTest $test
	 * @param $counter
	 */
	public function writeQuestionTitleToPdf($question, $test , $counter);

	/**
	 * @param assQuestion $question
	 * @param $counter
	 * @return array
	 */
	public function writeAnswersWithCheckboxToPdf($question, $counter);

	/**
	 * @param assQuestion $question
	 * @param $counter
	 * @return array
	 */
	public function writeAnswersWithIdentifierToPdf($question, $counter);

	/**
	 * @param $question
	 * @param $answers
	 * @param $columns
	 * @return array
	 */
	public function writeAnswersCheckboxForIdentifierToPdf($question, $answers, $columns);
}