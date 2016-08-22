<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentController.php');

/**
 * Class ilScanAssessmentDefaultController
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentDefaultController extends ilScanAssessmentController
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
		$ilTabs->setTabActive('settings');

		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->getCoreController()->getPluginObject()->getFormAction(__CLASS__ . '.saveForm'));
		$form->setTitle($this->getCoreController()->getPluginObject()->txt('scas_app_settings'));

		$active = new ilCheckboxInputGUI($this->getCoreController()->getPluginObject()->txt('scas_active'), 'active');
		$active->setInfo($this->getCoreController()->getPluginObject()->txt('scas_active_info'));
		$active->setValue(1);
		$form->addItem($active);

		$select_mode = new ilSelectInputGUI($this->getCoreController()->getPluginObject()->txt('scas_mode'), 'mode');
		$select_mode->setInfo($this->getCoreController()->getPluginObject()->txt('scas_mode_info'));
		$modes = array('inline' => $this->getCoreController()->getPluginObject()->txt('scas_mode_inline'),
					   'sheet' => $this->getCoreController()->getPluginObject()->txt('scas_answer_sheet')
		);
		$select_mode->setOptions($modes);
		$form->addItem($select_mode);

		$shuffle = new ilSelectInputGUI($this->getCoreController()->getPluginObject()->txt('scas_shuffling'), 'shuffling');
		$shuffle->setInfo($this->getCoreController()->getPluginObject()->txt('scas_shuffling_info'));
		$shuffle_modes = array('inline' => $this->getCoreController()->getPluginObject()->txt('scas_per_assessment'),
					   'sheet' => $this->getCoreController()->getPluginObject()->txt('scas_per_user')
		);
		$shuffle->setOptions($shuffle_modes);
		$form->addItem($shuffle);

		$matriculation_number = new ilCheckboxInputGUI($this->getCoreController()->getPluginObject()->txt('scas_matriculation'), 'matriculation');

		$mat_sub_form = new ilSelectInputGUI($this->getCoreController()->getPluginObject()->txt('scas_matriculation'), 'coding');
		$mat_sub_item = array('matrix' => $this->getCoreController()->getPluginObject()->txt('scas_matrix'),
							   'textfield' => $this->getCoreController()->getPluginObject()->txt('scas_textfield')
		);
		$mat_sub_form->setOptions($mat_sub_item);
		
		$matriculation_number->addSubItem($mat_sub_form);
		$form->addItem($matriculation_number);
	
		$form->addCommandButton(__CLASS__ . '.saveForm', $this->lng->txt('save'));

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
				$this->configuration->bindForm($form);
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

		$tpl = $this->getCoreController()->getPluginObject()->getTemplate('tpl.test_configuration.html', true, true);
		$tpl->setVariable('FORM', $form->getHTML());

		$sidebar = $this->renderPreconditions($tpl);
		$tpl->setVariable('STATUS', $sidebar);

		return $tpl->get();
	}

	/**
	 * @return string
	 */
	protected function renderPreconditions()
	{
		$this->getCoreController()->getPluginObject()->includeClass('ui/statusbar/class.ilScanAssessmentStatusBarGUI.php');
		$status_bar = new ilScanAssessmentStatusBarGUI();
		foreach($this->configuration->getPreconditions() as $precondition)
		{
			$status_bar->addItem($precondition);
		}
		 return $status_bar->getHtml();
	}
}