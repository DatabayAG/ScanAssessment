<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilScanAssessmentModel
 * @author Michael Jansen <mjansen@databay.de>
 */
interface ilScanAssessmentModel
{
	/**
	 * @param ilPropertyFormGUI $form
	 */
	public function bindForm(ilPropertyFormGUI $form);

	/**
	 * @return array
	 */
	public function toArray();
}