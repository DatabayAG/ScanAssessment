<?php

ilScanAssessmentPlugin::getInstance()->includeClass('model/class.ilScanAssessmentTestConfiguration.php');
ilScanAssessmentPlugin::getInstance()->includeClass('class.ilScanAssessmentFileHelper.php');

class ilScanAssessmentLayoutConfiguration extends ilScanAssessmentTestConfiguration
{
	protected $uploaded_file;

	protected $path_to_layout;

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
			$this->path_to_layout = $file_helper->getLayoutPath();
		}
	}

	public function read()
	{

	}

	public function setValuesFromPost()
	{
		$this->uploaded_file = ilUtil::stripSlashesRecursive($_POST['layout_upload']);
	}

	public function save()
	{
		if(file_exists($this->uploaded_file['tmp_name']))
		{
			ilUtil::moveUploadedFile($this->uploaded_file['tmp_name'], $this->uploaded_file['name'], $this->path_to_layout .'/'. $this->uploaded_file['name']);
			global $ilUser;
			ilScanAssessmentLog::getInstance()->info(sprintf('File: %s was added to test with id %s by user with the id: %s', $this->uploaded_file['name'], $this->test_id,  $ilUser->getId()));
		}
	}

}