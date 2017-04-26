<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentController.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentScanProcess.php');
ilScanAssessmentPlugin::getInstance()->includeClass('class.ilScanAssessmentGlobalSettings.php');

/**
 * Class ilScanAssessmentScanGUI
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentScanGUI extends ilScanAssessmentController
{
	/**
	 * @var ilObjTest
	 */
	protected $test;

	/**
	 * @var ilScanAssessmentTestConfiguration
	 */
	protected $configuration;

	/**
	 * 
	 */
	public function init()
	{
		$this->test = ilObjectFactory::getInstanceByRefId((int) $_GET['ref_id']);
		$this->getCoreController()->getPluginObject()->includeClass('model/class.ilScanAssessmentScanConfiguration.php');
		$this->configuration = new ilScanAssessmentScanConfiguration($this->test->getId());
		$this->isPreconditionFulfilled();
	}

	/**
	 * 
	 */
	protected function isPreconditionFulfilled()
	{
		$this->getCoreController()->getPluginObject()->includeClass('steps/class.ilScanAssessmentIsActivatedStep.php');
		$this->getCoreController()->getPluginObject()->includeClass('steps/class.ilScanAssessmentLayoutStep.php');
		$this->getCoreController()->getPluginObject()->includeClass('steps/class.ilScanAssessmentUserPackagesExportedStep.php');
		$activated		= new ilScanAssessmentIsActivatedStep($this->getCoreController()->getPluginObject(), $this->test);
		$layout			= new ilScanAssessmentLayoutStep($this->getCoreController()->getPluginObject(), $this->test);
		$user_packages	= new ilScanAssessmentUserPackagesExportedStep($this->getCoreController()->getPluginObject(), $this->test);
		
		if( !$activated->isFulfilled() || !$layout->isFulfilled() || !$user_packages->isFulfilled())
		{
			$this->redirectAndFailure($this->getCoreController()->getPluginObject()->txt('scas_previous_step_unfulfilled'), 'ilScanAssessmentUserPackagesPdfGUI.default');
		}
	}

	/**
	 * @param string $active_sub
	 */
	protected function addTabs($active_sub = 'scan_scanner')
	{
		$pluginObject = $this->getCoreController()->getPluginObject();
		$this->tabs->setTabActive('scan');
		$this->tabs->addSubTab('scan_scanner', $pluginObject->txt('scas_scan'), $this->getLink());
		$this->tabs->addSubTab('scan_revision_by_answer_row', $pluginObject->txt('scas_revision_answer'), $this->getLink('ilScanAssessmentScanRevisionByAnswerRowGUI'));
		$this->tabs->addSubTab('scan_revision', $pluginObject->txt('scas_scan_revision'), $this->getLink('ilScanAssessmentScanRevisionGUI'));
		$this->tabs->addSubTab('scan_user_mapping', $pluginObject->txt('scan_user_mapping'), $this->getLink('ilScanAssessmentScanUserMappingGUI'));
		$this->tabs->setSubTabActive($active_sub);
	}

	/**
	 * @param string $ctrl
	 * @return string
	 */
	protected function getLink($ctrl = 'ilScanAssessmentScanGUI')
	{
		$link = $this->getCoreController()->getPluginObject()->getLinkTarget($ctrl . '.default',	array('ref_id' => (int)$_GET['ref_id']));
		return $link;
	}

	/**
	 * @param bool $show_analyse
	 * @return ilPropertyFormGUI
	 */
	protected function getForm($show_analyse = false)
	{
		require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';

		/**
		 * @var $ilTabs ilTabsGUI
		 */
		global $ilTabs;
		$ilTabs->setTabActive('scan');
		$form = new ilPropertyFormGUI();

		if(! $this->getCoreController()->getPluginObject()->checkIfScanAssessmentCronExists() || ! ilScanAssessmentGlobalSettings::getInstance()->isDisableManualScan())
		{
			if($show_analyse || $this->file_helper->doFilesExistsInDirectory($this->file_helper->getScanPath()))
			{
				$form->addCommandButton(__CLASS__ . '.analyse', 'Analyse');
			}
		}
		else
		{
			ilUtil::sendInfo($this->getCoreController()->getPluginObject()->txt('scas_manual_scan_disabled'));
		}

		$form->setFormAction($this->getCoreController()->getPluginObject()->getFormAction(__CLASS__ . '.saveForm'));
		$form->setTitle($this->getCoreController()->getPluginObject()->txt('scas_scan'));

		$upload = new ilFileInputGUI($this->getCoreController()->getPluginObject()->txt('scas_upload'), 'upload');
		$upload->setInfo($this->getCoreController()->getPluginObject()->txt('scas_upload_info'));

        $suffixes = array('zip', 'jpg', 'jpeg', 'png', 'gif');

        if(ilScanAssessmentGlobalSettings::getInstance()->isTiffEnabled())
        {
            array_push($suffixes, 'tif', 'tiff');
        }

		$upload->setSuffixes($suffixes);
		$form->addItem($upload);

		$form->addCommandButton(__CLASS__ . '.saveForm', $this->lng->txt('save'));

		return $form;
	}

	/**
	 * 
	 */
	public function analyseCmd()
	{
		$scan_process = new ilScanAssessmentScanProcess($this->file_helper, $this->test->getId());

		try
        {
            $value = $scan_process->analyse();
        }
        catch (Exception $e)
        {
            $this->log->err(sprintf('An exception occured: %s', $e));
            $this->redirectAndInfo($this->getCoreController()->getPluginObject()->txt('scas_internal_error'));
        }

		if($value == $scan_process::FOUND)
		{
			$txt = $this->getCoreController()->getPluginObject()->txt('scas_files_found');

			if(sizeof($scan_process->getNonConformFiles()) > 0)
			{
				$txt .= '<br>' . $this->getCoreController()->getPluginObject()->txt('scas_non_conform_files_found');
				foreach($scan_process->getNonConformFiles() as $key => $filename)
				{
					$txt .= '<br>' . $filename;
				}
			}
			if(sizeof($scan_process->getFilesNotForThisTest()) > 0)
			{
				$txt .= '<br>' . $this->getCoreController()->getPluginObject()->txt('scas_not_for_this_test');
				foreach($scan_process->getFilesNotForThisTest() as $key => $filename)
				{
					$txt .= '<br>' . $filename;
				}
			}
			$this->redirectAndInfo($txt);
		}
		else if ($value == $scan_process::LOCKED)
		{
			$this->redirectAndFailure($this->getCoreController()->getPluginObject()->txt('scas_lock_file_found'));
		}
		else
		{
			$this->redirectAndFailure($this->getCoreController()->getPluginObject()->txt('scas_no_files_found'));
		}
	}

	/**
	 * @return string
	 */
	public function saveFormCmd()
	{
		$disable = false;

		$form = $this->getForm(true);
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
				$disable = true;
				ilUtil::sendFailure($this->getCoreController()->getPluginObject()->txt($e->getMessage()));
			}
		}

		$form->setValuesByPost();
		if($disable)
		{
			$form->getItemByPostVar('active')->setChecked(false);
		}

		return $this->defaultCmd($form);
	}

	/**
	 * @param ilPropertyFormGUI $form
	 */
	protected function bindModelToForm(ilPropertyFormGUI $form)
	{
		$form->setValuesByArray($this->configuration->toArray());
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
			$this->bindModelToForm($form);
		}

		$this->addTabs();
		
		/** @var ilTemplate $tpl */
		$tpl = $this->getCoreController()->getPluginObject()->getTemplate('tpl.test_configuration.html', true, true);
		$tpl->setVariable('FORM', $form->getHTML());
		$tpl->setCurrentBlock('detail_table');

		$tbl = $this->displayUnprocessedFiles();
		$tpl->setVariable('CONTENT', $tbl->getHTML());
		$tpl->parseCurrentBlock();
		$tpl->setCurrentBlock('detail_table');

		$tbl = $this->displayProcessedFiles();
		$tpl->setVariable('CONTENT', $tbl->getHTML());
		$tpl->parseCurrentBlock();

		$sidebar = $this->renderSteps();
		$tpl->setVariable('STATUS', $sidebar);

		return $tpl->get();
	}

	/**
	 * @return ilScanAssessmentScanTableUnprocessedGUI
	 */
	protected function displayUnprocessedFiles()
	{
		ilScanAssessmentPlugin::getInstance()->includeClass('ui/tables/class.ilScanAssessmentScanTableUnprocessedGUI.php');
		$tbl = new ilScanAssessmentScanTableUnprocessedGUI(new ilScanAssessmentUIHookGUI(), 'default');
		$tbl->setData($this->getUnprocessedFilesData());
		return $tbl;
	}

	/**
	 * @return ilScanAssessmentScanTableProcessedGUI
	 */
	protected function displayProcessedFiles()
	{
		ilScanAssessmentPlugin::getInstance()->includeClass('ui/tables/class.ilScanAssessmentScanTableProcessedGUI.php');
		$tbl = new ilScanAssessmentScanTableProcessedGUI(new ilScanAssessmentUIHookGUI(), 'default');
		$tbl->setData($this->getProcessedFilesData());
		return $tbl;
	}

	/**
	 * @return array
	 */
	protected function getUnprocessedFilesData()
	{
		$path	= $this->file_helper->getScanPath();
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
	 * @return array
	 */
	protected function getProcessedFilesData()
	{
		$path	= $this->file_helper->getAnalysedPath();
		$files	= $this->file_helper->getFilesFromFolderRecursive($path);
		return $files;
	}

	/**
	 * @return bool
	 */
	public function doProcessedFilesExist()
	{
		$files = $this->getProcessedFilesData();
		if(sizeof($files) > 0)
		{
			return true;
		}
		return false;
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

	/**
	 * 
	 */
	public function downloadScanImageCmd()
	{
		$file_name = ilUtil::stripSlashes($_GET['file_name']);
		$file_path = $this->file_helper->getScanPath() . $file_name;
		$this->download($file_path, $file_name);
	}

	/**
	 *
	 */
	public function removeFileCmd()
	{
		$file_name = ilUtil::stripSlashes($_GET['file_name']);
		$file_path = $this->file_helper->getScanPath() . $file_name;
		if(file_exists($file_path))
		{
			unlink($file_path);
		}
		return $this->defaultCmd();
	}

	/**
	 * 
	 */
	public function downloadProcessedImageCmd()
	{
		$file_name = ilUtil::stripSlashes($_GET['file_name']);
		$file_path = $this->file_helper->getAnalysedPath() . $file_name;
		$this->download($file_path, $file_name);
	}

	/**
	 * @return string
	 */
	public function getDefaultClassAndCommand()
	{
		return 'ilScanAssessmentScanGUI.default';
	}

}