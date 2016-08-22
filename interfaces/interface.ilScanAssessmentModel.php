<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilScanAssessmentModel
 * @author Guido Vollbach <gvollbach@databay.de>
 */
interface ilScanAssessmentModel
{
	CONST PARENT_FOLDER_NAME = 'scanAssessment';
	CONST SCAN_UPLOAD_FOLDER = 'scans';

	/**
	 * @param ilPropertyFormGUI $form
	 */
	public function bindForm(ilPropertyFormGUI $form);

	/**
	 * @return array
	 */
	public function toArray();
}