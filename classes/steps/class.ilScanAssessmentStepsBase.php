<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once dirname(__FILE__) . '/../../interfaces/interface.ilScanAssessmentStatusBarItem.php';

/**
 * Class ilScanAssessmentStepsBase
 * @author Guido Vollbach <gvollbach@databay.de>
 */
abstract class ilScanAssessmentStepsBase implements ilScanAssessmentStatusBarItem
{
	/**
	 * @var ilScanAssessmentPlugin
	 */
	protected $plugin;
	
	/**
	 * @var ilObjTest
	 */
	protected $test;

	/**
	 * ilScanAssessmentStepsBase constructor.
	 * @param ilScanAssessmentPlugin $plugin
	 * @param ilObjTest                     $test
	 */
	public function __construct(ilScanAssessmentPlugin $plugin, ilObjTest $test)
	{
		$this->plugin = $plugin;
		$this->test   = $test;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isRequired()
	{
		return true;
	}
}