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
		$form->setShowTopButtons(false);
		$pluginObject = $this->getCoreController()->getPluginObject();

		$form->setFormAction($pluginObject->getFormAction(__CLASS__ . '.saveForm'));
		$form->setTitle($pluginObject->txt('scas_app_settings'));

		$active = new ilCheckboxInputGUI($pluginObject->txt('scas_active'), 'active');
		$active->setInfo($pluginObject->txt('scas_active_info'));
		$active->setValue(1);
		$form->addItem($active);

		$select_mode = new ilSelectInputGUI($pluginObject->txt('scas_mode'), 'mode');
		$select_mode->setInfo($pluginObject->txt('scas_mode_info'));
		$modes = array(	'inline' => $pluginObject->txt('scas_mode_inline'),
						'sheet' => $pluginObject->txt('scas_answer_sheet')
		);
		$select_mode->setOptions($modes);
		$form->addItem($select_mode);

		$shuffle = new ilSelectInputGUI($pluginObject->txt('scas_shuffling'), 'shuffling');
		$shuffle->setInfo($pluginObject->txt('scas_shuffling_info'));
		$shuffle_modes = array(	'inline' =>$pluginObject->txt('scas_per_assessment'),
					  			 'sheet' => $pluginObject->txt('scas_per_user')
		);
		$shuffle->setOptions($shuffle_modes);
		$form->addItem($shuffle);

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

		$sidebar = $this->renderPreconditions();
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