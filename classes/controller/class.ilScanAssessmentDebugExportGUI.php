<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentController.php');
ilScanAssessmentPlugin::getInstance()->includeClass('export/class.ilScanAssessmentDebugExport.php');

/**
 * Class ilScanAssessmentDebugExportGUI
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentDebugExportGUI extends ilScanAssessmentController
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
	protected function init()
	{
		$this->test = ilObjectFactory::getInstanceByRefId((int) $_GET['ref_id']);
		$this->getCoreController()->getPluginObject()->includeClass('model/class.ilScanAssessmentTestConfiguration.php');
		$this->configuration = new ilScanAssessmentTestConfiguration($this->test->getId());
	}

	/**
	 * @return ilPropertyFormGUI
	 */
	protected function getForm()
	{
		require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';

		/**
		 * @var $ilTabs ilTabsGUI
		 */
		global $ilTabs;
		$ilTabs->activateTab('debug');

		$form = new ilPropertyFormGUI();
		$form->setShowTopButtons(false);
		$pluginObject = $this->getCoreController()->getPluginObject();

		$form->setFormAction($pluginObject->getFormAction(__CLASS__ . '.saveForm'));
		$form->addCommandButton(__CLASS__ . '.saveForm', $this->lng->txt('export'));

		return $form;
	}

	/**
	 * @return string
	 */
	public function saveFormCmd()
	{
		$disable = false;

		$form = $this->getForm();
		if($form->checkInput())
		{
			try
			{
				ilUtil::delDir($this->file_helper->getExportPath());
				$this->exportTestObject();
				$this->copyFilesToExportDir();
				$scan_export = new ilScanAssessmentDebugExport($this->test);
				$scan_export->xmlDumpFile($this->file_helper->getExportPath() . 'scasExport.xml');
				$export_file = $this->file_helper->getExportPath() . '../scasExport.zip';
				ilUtil::zip($this->file_helper->getExportPath(), $export_file);
				ilUtil::deliverFile($export_file, 'scasExport.zip', '', false , true);
				ilUtil::sendSuccess($this->lng->txt('saved_successfully'));
			}
			catch(ilException $e)
			{
				$disable = true;
				ilUtil::sendFailure($this->getCoreController()->getPluginObject()->txt($e->getMessage()));
			}
		}

		return $this->defaultCmd($form);
	}

	protected function copyFilesToExportDir()
	{
		$layout = $this->file_helper->getExportPath() . 'layout';
		$pdf = $this->file_helper->getExportPath() . 'pdf';
		$results = $this->file_helper->getExportPath() . 'results';
		$scans = $this->file_helper->getExportPath() . 'scans';
		$this->file_helper->ensurePathExists($layout);
		$this->file_helper->ensurePathExists($pdf);
		$this->file_helper->ensurePathExists($results);
		$this->file_helper->ensurePathExists($scans);
		ilUtil::rCopy($this->file_helper->getLayoutPath(), $layout);
		ilUtil::rCopy($this->file_helper->getPdfPath(), $pdf);
		ilUtil::rCopy($this->file_helper->getResultsXmlPath(), $results);
		ilUtil::rCopy($this->file_helper->getScanPath(), $scans);
	}
	
	protected function exportTestObject()
	{
		if(version_compare(ILIAS_VERSION_NUMERIC, '5.2.0', '>='))
		{
			require_once 'Modules/Test/classes/class.ilTestExportFactory.php';
			$expFactory = new ilTestExportFactory($this->test);
			$test_exp   = $expFactory->getExporter('xml');
			$test_exp->setResultExportingEnabledForTestExport(true);
			$src = $test_exp->buildExportFile();
		}
		else
		{	require_once 'Modules/Test/classes/class.ilTestExport.php';
			$test_exp = new ilTestExport($this->test, 'xml');
			$src = $test_exp->buildExportFile();
		}

		if($src)
		{
			$this->file_helper->moveFile($src, $this->file_helper->getExportPath() . basename($src));
			return true;
		}
		return false;
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
		/**
		 * @var $ilTabs ilTabsGUI
		 */
		global $ilTabs;
		$ilTabs->activateTab('tab_debug');
		if(!($form instanceof ilPropertyFormGUI))
		{
			$form  = $this->getForm();
			$this->bindModelToForm($form);
		}

		/** @var ilTemplate $tpl */
		$tpl = $this->getCoreController()->getPluginObject()->getTemplate('tpl.test_configuration.html', true, true);
		$tpl->setVariable('FORM', $form->getHTML());

		$sidebar = $this->renderPreconditions();
		$tpl->setVariable('STATUS', $sidebar);

		return $tpl->get();

	}

	/**
	 * @return string
	 */
	protected function renderPreconditions()
	{}
}