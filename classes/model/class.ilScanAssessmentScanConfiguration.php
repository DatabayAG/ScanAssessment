<?php

ilScanAssessmentPlugin::getInstance()->includeClass('model/class.ilScanAssessmentTestConfiguration.php');

/**
 * Class ilScanAssessmentScanConfiguration
 * @author Guido Vollbach <gvollbach@databay.de>
 */
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