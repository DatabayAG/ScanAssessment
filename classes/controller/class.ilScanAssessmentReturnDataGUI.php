<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentController.php');
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/assessment/class.ilScanAssessmentXMLResultCreator.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/class.ilScanAssessmentFileHelper.php';
require_once 'Modules/Test/classes/class.ilTestResultsImportParser.php';
require_once 'Services/Tracking/classes/class.ilLPStatusWrapper.php';
/**
 * Class ilScanAssessmentReturnDataGUI
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentReturnDataGUI extends ilScanAssessmentController
{
	/**
	 * @var ilObjTest
	 */
	protected $test;

	/**
	 * @var ilScanAssessmentTestConfiguration
	 */
	protected $configuration;

	protected function init()
	{
		$this->test = ilObjectFactory::getInstanceByRefId((int) $_GET['ref_id']);
		$this->getCoreController()->getPluginObject()->includeClass('model/class.ilScanAssessmentReturnDataConfiguration.php');
		$this->configuration = new ilScanAssessmentReturnDataConfiguration($this->test->getId());
		$this->isPreconditionFulfilled();
	}

	protected function isPreconditionFulfilled()
	{
		$this->getCoreController()->getPluginObject()->includeClass('steps/class.ilScanAssessmentIsActivatedStep.php');
		$activated		= new ilScanAssessmentIsActivatedStep($this->getCoreController()->getPluginObject(), $this->test);
		$layout			= new ilScanAssessmentLayoutStep($this->getCoreController()->getPluginObject(), $this->test);
		$user_packages	= new ilScanAssessmentUserPackagesExportedStep($this->getCoreController()->getPluginObject(), $this->test);
		$revision		= new ilScanAssessmentRevisionStep($this->getCoreController()->getPluginObject(), $this->test);
		$user_mapping	= new ilScanAssessmentUserMappingStep($this->getCoreController()->getPluginObject(), $this->test);

		$revision_state	= $revision->isFulfilled();
		$user_mapping_state = $user_mapping->isFulfilled();
		
		if(! $activated->isFulfilled() || !$layout->isFulfilled() || !$user_packages->isFulfilled() || !$user_mapping_state || !$revision_state)
		{
			if(!$revision_state)
			{
				$this->redirectAndFailure($this->getCoreController()->getPluginObject()->txt('scas_previous_step_unfulfilled'), 'ilScanAssessmentScanRevisionGUI.default');
			}
			else
			{
				$this->redirectAndFailure($this->getCoreController()->getPluginObject()->txt('scas_previous_step_unfulfilled'), 'ilScanAssessmentScanUserMappingGUI.default');
			}
		}
	}

	protected function returnResultsToIliasTest()
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		$xml      = new ilScanAssessmentXMLResultCreator($this->test);
		$helper   = new ilScanAssessmentFileHelper($this->test->getId());
		$xml_file = $helper->getResultsXmlPath();
		$results  = $xml->xmlDumpFile($xml_file);
		if(file_exists($xml_file))
		{
			$parser = new ilTestResultsImportParser($xml_file, $this->test);
			$parser->startParsing();
			$this->test->recalculateScores(true);
			unlink($xml_file);
			foreach($results as $usr_id)
			{
				$ilDB->update('pl_scas_pdf_data',
					array(
						'results_exported' => array('integer', 1)
					),
					array(
						'usr_id' => array('integer', $usr_id),
						'obj_id' => array('integer', $this->test->getId())
					));
				ilLPStatusWrapper::_updateStatus($this->test->getId(), $usr_id);
			}
		}
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
		$ilTabs->setTabActive('layout');

		$form = new ilPropertyFormGUI();
	
		$form->setFormAction($this->getCoreController()->getPluginObject()->getFormAction(__CLASS__ . '.saveForm'));
		$form->setTitle($this->getCoreController()->getPluginObject()->txt('scas_return'));
		return $form;
	}

	/**
	 * @return ilScanAssessmentScanTableReturnResultsGUI
	 */
	protected function displayUserTable()
	{
		ilScanAssessmentPlugin::getInstance()->includeClass('ui/tables/class.ilScanAssessmentScanTableReturnResultsGUI.php');
		$tbl = new ilScanAssessmentScanTableReturnResultsGUI(new ilScanAssessmentUIHookGUI(), 'default');
		$tbl->setData($this->getUserData());
		return $tbl;
	}

	/**
	 * @return array
	 */
	protected function getUserData()
	{
		global $ilDB, $ilUser;
		$res = $ilDB->queryF('SELECT * FROM pl_scas_pdf_data WHERE obj_id = %s',
			array('integer'), array($this->test->getId()));

		$user_data = array();

		while ($row = $ilDB->fetchAssoc($res))
		{
			$name = '';
			if($row['usr_id'] != null)
			{
				$name = $ilUser::_lookupFullname($row['usr_id']);
			}
			$user_data[] = array(
							'usr_id'			=> $row['usr_id'],
							'usr_name'			=> $name,
							'pdf_id'			=> $row['pdf_id'], 
							'revision_done'		=> $this->intToYesNo($row['revision_done']), 
							'results_exported'	=> $this->intToYesNo($row['results_exported']), 
			);
		}
		return $user_data;
	}

	/**
	 * @param $value
	 * @return string
	 */
	protected function intToYesNo($value)
	{
		global $lng;
		if($value == 1)
		{
			return $lng->txt('yes');
		}
		return $lng->txt('no');
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
				$this->configuration->bindForm($form);
				$this->configuration->save();
				$this->returnResultsToIliasTest();
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
		/** @var ilTemplate $tpl */
		$tpl = $this->getCoreController()->getPluginObject()->getTemplate('tpl.test_configuration.html', true, true);
		
		$check = $user_mapping	= new ilScanAssessmentReturnResultsStep($this->getCoreController()->getPluginObject(), $this->test);
		if( ! $check->isFulfilled())
		{
			$form->addCommandButton(__CLASS__ . '.saveForm', $this->getCoreController()->getPluginObject()->txt('scas_return_results'));
		}

		$tpl->setVariable('FORM', $form->getHTML());
		$tbl = $this->displayUserTable();
		$tpl->setVariable('CONTENT', $tbl->getHTML());
		
		$sidebar = $this->renderSteps();
		$tpl->setVariable('STATUS', $sidebar);

		return $tpl->get();
	}

	/**
	 * @return string
	 */
	protected function renderSteps()
	{
		$this->getCoreController()->getPluginObject()->includeClass('ui/statusbar/class.ilScanAssessmentStepsGUI.php');
		$status_bar = new ilScanAssessmentStepsGUI();
		foreach($this->configuration->getSteps() as $steps)
		{
			$status_bar->addItem($steps);
		}
		return $status_bar->getHtml();
	}

}