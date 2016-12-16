<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('../interfaces/interface.ilScanAssessmentStatusBarItem.php');
ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentScanGUI.php');
/**
 * Class ilScanAssessmentStepsBase
 * @author Guido Vollbach <gvollbach@databay.de>
 */
abstract class ilScanAssessmentStepsBase implements ilScanAssessmentStatusBarItem
{
	const pdf_data_table = 'pl_scas_pdf_data';
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
	 * @param $plugin
	 * @param ilObjTest $test
	 */
	public function __construct($plugin, ilObjTest $test)
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