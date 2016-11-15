<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentController.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfAssessmentBuilder.php');
ilScanAssessmentPlugin::getInstance()->includeClass('class.ilScanAssessmentGlobalSettings.php');

/**
 * Class ilScanAssessmentUserPackagesGUI
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentUserPackagesGUI extends ilScanAssessmentController
{
	/**
	 * @var ilObjTest
	 */
	protected $test;

	/**
	 * @var ilScanAssessmentUserPackagesConfiguration
	 */
	protected $configuration;

	/**
	 * @var ilScanAssessmentGlobalSettings
	 */
	protected $global_settings;

	/**
	 * 
	 */
	protected function init()
	{
		$this->test = ilObjectFactory::getInstanceByRefId((int) $_GET['ref_id']);

		$this->getCoreController()->getPluginObject()->includeClass('model/class.ilScanAssessmentUserPackagesConfiguration.php');
		$this->configuration = new ilScanAssessmentUserPackagesConfiguration($this->test->getId());
		$this->global_settings = ilScanAssessmentGlobalSettings::getInstance();
		$this->isPreconditionFulfilled();
	}

	/**
	 * 
	 */
	protected function isPreconditionFulfilled()
	{
		$this->getCoreController()->getPluginObject()->includeClass('steps/class.ilScanAssessmentLayoutStep.php');
		$this->getCoreController()->getPluginObject()->includeClass('steps/class.ilScanAssessmentIsActivatedStep.php');
		$activated		= new ilScanAssessmentIsActivatedStep($this->getCoreController()->getPluginObject(), $this->test);
		$layout			= new ilScanAssessmentLayoutStep($this->getCoreController()->getPluginObject(), $this->test);

		if(!$activated->isFulfilled() || !$layout->isFulfilled())
		{
			$this->redirectAndFailure($this->getCoreController()->getPluginObject()->txt('scas_previous_step_unfulfilled'), 'ilScanAssessmentLayoutGUI.default');
		}
	}

	protected function addTabs($active_sub = 'user_packages_settings')
	{
		$pluginObject = $this->getCoreController()->getPluginObject();
		$this->tabs->setTabActive('user_packages');
		$this->tabs->addSubTab('user_packages_settings', $pluginObject->txt('scas_settings'), $this->getLink());
		$this->tabs->addSubTab('user_packages_pdf', $pluginObject->txt('scas_pdf'), $this->getLink('ilScanAssessmentUserPackagesPdfGUI'));
		$this->tabs->setSubTabActive($active_sub);
	}
	
	/**
	 * @return ilPropertyFormGUI
	 */
	protected function getForm()
	{
		$pluginObject = $this->getCoreController()->getPluginObject();

		require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';
		$form = new ilPropertyFormGUI();
		$form->setShowTopButtons(false);

		$this->addTabs();

		$form->setFormAction($pluginObject->getFormAction(__CLASS__ . '.saveForm', array('ref_id' => (int)$_GET['ref_id'])));
		$form->setTitle($pluginObject->txt('scas_user_packages'));

		if($this->test->getFixedParticipants() === 1)
		{
			$info = new ilNonEditableValueGUI();
			$info->setValue($pluginObject->txt('scas_fixed_participants'));
			$info->setInfo($pluginObject->txt('scas_tst_settings_info'));
			$info->setTitle($pluginObject->txt('scas_tst_settings'));
			$form->addItem($info);
			$personalised = new ilSelectInputGUI($pluginObject->txt('scas_creation'), 'personalised');
			$personalised->setInfo($pluginObject->txt('scas_creation_info'));
			$personalised_options = array(
				0 => $pluginObject->txt('scas_creation_personalised'),
				1 => $pluginObject->txt('scas_creation_non_personalised')
			);
			$personalised->setOptions($personalised_options);
			$personalised->setValue($this->configuration->isNotPersonalised());
			$form->addItem($personalised);
		}
		else
		{
			$info = new ilNonEditableValueGUI();
			$info->setValue($pluginObject->txt('scas_non_fixed_participants'));
			$info->setTitle($pluginObject->txt('scas_tst_settings'));
			$info->setInfo($pluginObject->txt('scas_tst_settings_info'));
			$form->addItem($info);
			$count_pdfs = new ilNumberInputGUI($pluginObject->txt('scas_count_pdfs'), 'count_pdfs');
			$count_pdfs->setValue($this->configuration->getCountDocuments());
			$count_pdfs->setInfo($pluginObject->txt('scas_count_pdfs_info'));
			$count_pdfs->setMinValue(1);
			$form->addItem($count_pdfs);
		}

		$matriculation_number = new ilCheckboxInputGUI($pluginObject->txt('scas_matriculation'), 'matriculation');
		$matriculation_number->setInfo($pluginObject->txt('scas_matriculation_style') . ' ' . $this->global_settings->getMatriculationStyle());
		$matriculation_number->setValue(1);
		if($this->configuration->isMatriculationCode() == 1)
		{
			$matriculation_number->setChecked(true);
		}
		$mat_sub_form = new ilSelectInputGUI($pluginObject->txt('scas_matriculation'), 'coding');
		$mat_sub_item = array(0 => $pluginObject->txt('scas_matrix'),
							  1 => $pluginObject->txt('scas_textfield')
		);
		$mat_sub_form->setOptions($mat_sub_item);
		$mat_sub_form->setValue($this->configuration->getMatriculationStyle());
		$matriculation_number->addSubItem($mat_sub_form);
		$form->addItem($matriculation_number);

		$no_names = new ilCheckboxInputGUI($pluginObject->txt('scas_no_name_field'), 'no_name_field');
		$no_names->setInfo($pluginObject->txt('scas_no_name_field_info'));
		$no_names->setValue(1);
		if($this->configuration->isNoNameField() == 1)
		{
			$no_names->setChecked(true);
		}
		$form->addItem($no_names);

		$date_box = new ilDateTimeInputGUI($pluginObject->txt('scas_assessment_date'), 'assessment_date');
		$date_box->setInfo($pluginObject->txt('scas_assessment_date_info'));
		$date_box->setDate(new ilDateTime($this->configuration->getAssessmentDate(), IL_CAL_UNIX));
		$form->addItem($date_box);

		$form->addCommandButton(__CLASS__ . '.saveForm', $this->lng->txt('save'));

		return $form;
	}
	public function createDemoPdfCmd()
	{
		$demo = new ilScanAssessmentPdfAssessmentBuilder($this->test);
		$demo->createDemoPdf();
	}

	protected function getLink($ctrl = 'ilScanAssessmentUserPackagesGUI')
	{
		return $this->getCoreController()->getPluginObject()->getLinkTarget($ctrl . '.default',	array('ref_id' => (int)$_GET['ref_id']));
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
		/** @var ilTemplate $tpl */
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
		$steps = new ilScanAssessmentTestConfiguration($this->test->getId());
		foreach($steps->getSteps() as $step)
		{
			$status_bar->addItem($step);
		}
		return $status_bar->getHtml();
	}
}