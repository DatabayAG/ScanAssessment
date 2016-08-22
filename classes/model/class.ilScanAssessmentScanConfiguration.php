<?php

require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/model/class.ilScanAssessmentTestConfiguration.php';

class ilScanAssessmentScanConfiguration extends ilScanAssessmentTestConfiguration
{
	/**
	 * @param ilPropertyFormGUI $form
	 */
	public function bindForm(ilPropertyFormGUI $form)
	{
		$this->ensureSavePathExists();
	}

	/**
	 * @throws ilException
	 */
	private function validate()
	{
		
	}

	/**
	 *
	 */
	protected function ensureSavePathExists()
	{
		$path = ilUtil::getDataDir() . '/' .  self::PARENT_FOLDER_NAME . '/tst_' . $this->obj_id . '/' . self::SCAN_UPLOAD_FOLDER;
		if( ! is_dir($path))
		{
			ilUtil::makeDirParents($path);
		}
	}
}