<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentController.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilPdfPreviewBuilder.php');

/**
 * Class ilScanAssessmentLayoutController
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentLayoutController extends ilScanAssessmentController
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
		$this->test = ilObjectFactory::getInstanceByRefId($_GET['ref_id']);
		
		$this->getCoreController()->getPluginObject()->includeClass('model/class.ilScanAssessmentTestConfiguration.php');
		$this->configuration = new ilScanAssessmentTestConfiguration($this->test->getId());
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
		require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';

		/**
		 * @var $ilTabs ilTabsGUI
		 */
		global $ilTabs;
		$ilTabs->setTabActive('layout');

		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->getCoreController()->getPluginObject()->getFormAction(__CLASS__ . '.saveForm'));
		$form->setTitle($this->getCoreController()->getPluginObject()->txt('scas_layout'));

		$active = new ilFileInputGUI($this->getCoreController()->getPluginObject()->txt('scas_upload'), 'upload');
		$active->setInfo($this->getCoreController()->getPluginObject()->txt('scas_upload_info'));
		$form->addItem($active);

		$form->addCommandButton(__CLASS__ . '.createDemoPdf', $this->getCoreController()->getPluginObject()->txt('scas_create_demo_pdf'));
		$form->addCommandButton(__CLASS__ . '.saveForm', $this->lng->txt('save'));

		return $form;
	}
	
	public function createDemoPdfCmd()
	{
		$demo = new ilPdfPreviewBuilder($this->test);
		$demo->createDemoPdf();
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