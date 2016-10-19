<?php

require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/model/class.ilScanAssessmentTestConfiguration.php';

class ilScanAssessmentUserPackagesConfiguration extends ilScanAssessmentTestConfiguration
{
	/**
	 * @var int
	 */
	protected $tst_id;

	/**
	 * @var int
	 */
	protected $count_documents;

	/**
	 * @var boolean
	 */
	protected $matriculation_code;

	/**
	 * @var int
	 */
	protected $matriculation_style;

	/**
	 * @var int
	 */
	protected $download_style;

	/**
	 * @var boolean
	 */
	protected $personalised;

	/**
	 * @var int
	 */
	protected $documents_generated;

	/**
	 * @param ilPropertyFormGUI $form
	 */
	public function bindForm(ilPropertyFormGUI $form)
	{
		
	}

	/**
	 * @throws ilException
	 */
	private function validate()
	{
		
	}
}