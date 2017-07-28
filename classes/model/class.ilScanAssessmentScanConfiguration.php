<?php

ilScanAssessmentPlugin::getInstance()->includeClass('model/class.ilScanAssessmentTestConfiguration.php');

/**
 * Class ilScanAssessmentScanConfiguration
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentScanConfiguration extends ilScanAssessmentTestConfiguration
{
	protected $uploaded_file;

	protected $path_to_scan;

	/**
	 * @param int $test_obj_id
	 */
	public function __construct($test_obj_id)
	{
		if($test_obj_id > 0)
		{
			$this->setTestId($test_obj_id);
			$this->read();
			$file_helper = new ilScanAssessmentFileHelper($test_obj_id);
			$this->path_to_scan = $file_helper->getScanPath();
		}
		parent::__construct($test_obj_id);
	}

	public function read()
	{

	}

	public function setValuesFromPost()
	{
		$this->uploaded_file = ilUtil::stripSlashesRecursive($_POST['upload']);
	}

	public function save()
	{
		if(file_exists($this->uploaded_file['tmp_name']))
		{
			ilUtil::moveUploadedFile($this->uploaded_file['tmp_name'], $this->uploaded_file['name'], $this->path_to_scan .'/'. $this->uploaded_file['name']);
			$extension = pathinfo($this->uploaded_file['name'], PATHINFO_EXTENSION);
			if($extension == 'zip')
			{
				ilUtil::unzip($this->path_to_scan .'/'. $this->uploaded_file['name'], false, true);
			}
		}
	}

}