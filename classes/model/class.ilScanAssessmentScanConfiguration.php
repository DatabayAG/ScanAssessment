<?php

ilScanAssessmentPlugin::getInstance()->includeClass('model/class.ilScanAssessmentTestConfiguration.php');

class ilScanAssessmentScanConfiguration extends ilScanAssessmentTestConfiguration
{
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