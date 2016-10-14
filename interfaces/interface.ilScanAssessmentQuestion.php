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
}