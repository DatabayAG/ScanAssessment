<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentController.php');

/**
 * Class ilScanAssessmentLayoutController
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentLayoutController extends ilScanAssessmentController
{
	const TITLE_AND_POINTS		= 0;
	const ONLY_TITLE			= 1;
	const QUESTION_NUMBER_ONLY	= 2;

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
			ilUtil::sendFailure($this->getCoreController()->getPluginObject()->txt('scas_previous_step_unfulfilled'), true);
			ilUtil::redirect($this->getCoreController()->getPluginObject()->getLinkTarget(
				'ilScanAssessmentDefaultController.default',
				array(
					'ref_id' => (int)$_GET['ref_id']
				)
			));
		}
	}
	
	/**
	 * @return ilPropertyFormGUI
	 */
	protected function getForm()
	{
		$pluginObject = $this->getCoreController()->getPluginObject();
		require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';
		/**
		 * @var $ilTabs ilTabsGUI
		 */
		global $ilTabs;
		$ilTabs->setTabActive('layout');

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
		
		$file = new ilFileInputGUI($pluginObject->txt('scas_upload'), 'upload');
		$file->setInfo($pluginObject->txt('scas_upload_info'));
		$file->setDisabled(true);
		$form->addItem($file);

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