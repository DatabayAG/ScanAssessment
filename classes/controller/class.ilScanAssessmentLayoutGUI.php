<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentController.php');

/**
 * Class ilScanAssessmentLayoutGUI
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentLayoutGUI extends ilScanAssessmentController
{
	const TITLE_AND_POINTS		= 0;
	const ONLY_TITLE			= 1;
	const QUESTION_NUMBER_ONLY	= 2;

	/**
	 * @var ilObjTest
	 */
	protected $test;

	/**
	 * @var ilScanAssessmentLayoutConfiguration
	 */
	protected $configuration;

	/**
	 * 
	 */
	protected function init()
	{
		$this->test = ilObjectFactory::getInstanceByRefId((int) $_GET['ref_id']);
		$this->getCoreController()->getPluginObject()->includeClass('model/class.ilScanAssessmentLayoutConfiguration.php');
		$this->configuration = new ilScanAssessmentLayoutConfiguration($this->test->getId());
		$this->isPreconditionFulfilled();
	}

	protected function isPreconditionFulfilled()
	{
		$this->getCoreController()->getPluginObject()->includeClass('steps/class.ilScanAssessmentIsActivatedStep.php');
		$activated = new ilScanAssessmentIsActivatedStep($this->getCoreController()->getPluginObject(), $this->test);
		if(! $activated->isFulfilled())
		{
			$this->redirectAndFailure($this->getCoreController()->getPluginObject()->txt('scas_previous_step_unfulfilled'), 'ilScanAssessmentDefaultGUI.default');
		}
	}
	
	/**
	 * @return ilPropertyFormGUI
	 */
	protected function getForm()
	{
		$pluginObject = $this->getCoreController()->getPluginObject();
		require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';

		$this->setActiveLayoutTab();

		$form = new ilPropertyFormGUI();
		$form->setFormAction($pluginObject->getFormAction(__CLASS__ . '.saveForm'));
		$form->setTitle($pluginObject->txt('scas_layout'));
		
		$info = new ilNonEditableValueGUI();
		$title_setting	= $this->test->getTitleOutput();
		if($title_setting < self::QUESTION_NUMBER_ONLY)
		{
			$info->setValue($pluginObject->txt('scas_question_title'));
			if($title_setting < self::ONLY_TITLE)
			{
				$info->setValue($pluginObject->txt('scas_question_title_and_points'));
			}
		}
		else
		{
			$info->setValue($pluginObject->txt('scas_question_non_title'));
		}

		$info->setInfo($pluginObject->txt('scas_tst_settings_title_info'));
		$info->setTitle($pluginObject->txt('scas_tst_settings_title'));
		$form->addItem($info);
		
		$file = new ilFileInputGUI($pluginObject->txt('scas_upload'), 'layout_upload');
		$file->setSuffixes(array('pdf'));
		$file->setInfo($pluginObject->txt('scas_upload_info'));
		
		$form->addItem($file);

		$form->addCommandButton(__CLASS__ . '.saveForm', $this->lng->txt('save'));

		return $form;
	}
	
	/**
	 * @return string
	 */
	public function saveFormCmd()
	{
		$form = $this->getForm();
		if($form->checkInput())
		{
			try
			{
				$this->configuration->setValuesFromPost();
				$this->configuration->save();
				ilUtil::sendSuccess($this->lng->txt('saved_successfully'));
			}
			catch(ilException $e)
			{
				ilUtil::sendFailure($this->getCoreController()->getPluginObject()->txt($e->getMessage()));
			}
		}

		$form->setValuesByPost();

		return $this->defaultCmd($form);
	}

	/**
	 * @param ilPropertyFormGUI|null $form
	 * @return string
	 */
	public function defaultCmd(ilPropertyFormGUI $form = null)
	{

		if(!($form instanceof ilPropertyFormGUI))
		{
			$form  = $this->getForm();
		}

		$tpl = $this->getCoreController()->getPluginObject()->getTemplate('tpl.test_configuration.html', true, true);
		$tpl->setVariable('FORM', $form->getHTML());

		$sidebar = $this->renderSteps();
		$tpl->setVariable('STATUS', $sidebar);

		$tbl = $this->showLayoutFilesIfExisting();
		$tpl->setVariable('CONTENT', $tbl->getHTML());

		return $tpl->get();
	}
	
	protected function setActiveLayoutTab()
	{
		/**
		 * @var $ilTabs ilTabsGUI
		 */
		global $ilTabs;
		$ilTabs->setTabActive('layout');
	}
	
	public function areYouSureDeleteEntriesCmd()
	{

		$this->setActiveLayoutTab();

		if(!isset($_POST['file_id']) || !is_array($_POST['file_id']) || !count($_POST['file_id']))
		{
			ilUtil::sendFailure($this->lng->txt('select_one'));
			return $this->defaultCmd();
		}

		require_once 'Services/Utilities/classes/class.ilConfirmationGUI.php';
		$pluginObject = $this->getCoreController()->getPluginObject();
		$confirm = new ilConfirmationGUI();
		$confirm->setFormAction($pluginObject->getFormAction(__CLASS__ . '.deleteFiles'));
		$post_ids = $_POST['file_id'];
		if(is_array($post_ids))
		{
			foreach($post_ids as $file)
			{
				$confirm->addItem('files[]', basename($file), basename($file));
			}
		}
		$confirm->setHeaderText($pluginObject->txt('scas_sure_delete_file'));
		$confirm->setConfirm($pluginObject->txt('scas_confirm'), __CLASS__ . '.deleteFiles');
		$confirm->setCancel($pluginObject->txt('scas_cancel'), __CLASS__ . '.cancel');
		return $confirm->getHTML();

	}

	public function cancelCmd()
	{
		return $this->defaultCmd();
	}

	public function deleteFilesCmd()
	{
		if(array_key_exists('files', $_POST))
		{
			$files = ilUtil::stripSlashesRecursive($_POST['files']);
			if(is_array($files))
			{
				foreach($files as $file)
				{
					$full_path = $this->file_helper->getLayoutPath()  . '/' . $file;
					if(file_exists($full_path))
					{
						unlink($full_path);
						$this->log->info(sprintf('File: %s was removed from test with id %s by user with the id: %s', $full_path, $this->test->getId(),  $this->user->getId()));
						ilUtil::sendInfo($this->getCoreController()->getPluginObject()->txt('scas_files_deleted'));
					}
				}
			}
		}
		return $this->defaultCmd();
	}

	public function downloadPdfCmd()
	{
		$file_name = ilUtil::stripSlashes($_GET['file_name']);
		$file_path = $this->file_helper->getLayoutPath() . $file_name;
		$this->download($file_path, $file_name);
	}

	/**
	 *
	 */
	public function showLayoutFilesIfExisting()
	{
		ilScanAssessmentPlugin::getInstance()->includeClass('ui/tables/class.ilScanAssessmentScanTableLayoutGUI.php');
		$tbl = new ilScanAssessmentScanTableLayoutGUI(new ilScanAssessmentUIHookGUI(), '');
		$tbl->setData($this->getFolderFiles($this->file_helper->getLayoutPath()));
		return $tbl;
	}

	/**
	 * @param $path
	 * @return array
	 */
	public function getFolderFiles($path)
	{
		$files	= array();
		if ($handle = opendir($path))
		{
			while (false !== ($entry = readdir($handle)))
			{
				if(is_dir($path .'/'. $entry) === false)
				{
					$size = (int) (filesize($path . '/' .$entry) / 1024);
					$date = date('d. F Y H:i:s', filemtime($path . '/' .$entry));
					$files[] = array('file_id' => $entry, 'file_name' => $entry, 'file_size' => $size . 'K', 'file_date' => $date);
				}
			}
			closedir($handle);
		}
		return $files;
	}
	/**
	 * @return string
	 */
	protected function renderSteps()
	{
		$this->getCoreController()->getPluginObject()->includeClass('ui/statusbar/class.ilScanAssessmentStepsGUI.php');
		$status_bar = new ilScanAssessmentStepsGUI();
		$steps = new ilScanAssessmentTestConfiguration($this->test->getId());
		foreach($steps->getSteps() as $step)
		{
			$status_bar->addItem($step);
		}
		return $status_bar->getHtml();
	}

}