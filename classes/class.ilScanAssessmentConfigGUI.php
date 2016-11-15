<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Component/classes/class.ilPluginConfigGUI.php';

ilScanAssessmentPlugin::getInstance()->includeClass('class.ilScanAssessmentGlobalSettings.php');

/**
 * Class ilScanAssessmentConfigGUI
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentConfigGUI extends ilPluginConfigGUI
{
	/**
	 * @var ilTemplate
	 */
	protected $tpl;

	/**
	 * @var ilLanguage
	 */
	protected $lng;

	/**
	 * @var ilCtrl
	 */
	protected $ctrl;

	/**
	 * @var ilToolbarGUI
	 */
	protected $toolbar;

	/**
	 * @var ilDB
	 */
	protected $db;

	/**
	 * @var ilObjUser
	 */
	protected $user;

	/**
	 *
	 */
	public function __construct()
	{
		/**
		 * @var ilTemplate   $tpl
		 * @var ilLanguage   $lng
		 * @var ilCtrl       $ilCtrl
		 * @var ilToolbarGUI $ilToolbar
		 * @var ilDB         $ilDB
		 * @var ilObjUser    $ilUser
		 */
		global $lng, $tpl, $ilCtrl, $ilToolbar, $ilDB, $ilUser;

		$this->lng     = $lng;
		$this->tpl     = $tpl;
		$this->ctrl    = $ilCtrl;
		$this->toolbar = $ilToolbar;
		$this->db      = $ilDB;
		$this->user    = $ilUser;
	}

	/**
	 * {@inheritdoc}
	 */
	public function performCommand($cmd)
	{
		switch($cmd)
		{
			case 'saveConfigurationForm':
				$this->saveConfigurationForm();
				break;

			case 'showConfigurationForm':
			default:
				$this->showConfigurationForm();
				break;
		}
	}

	/**
	 * @param ilPropertyFormGUI $form
	 */
	protected function showConfigurationForm(ilPropertyFormGUI $form = null)
	{

		if(!$form instanceof ilPropertyFormGUI)
		{
			$form = $this->getConfigurationForm();
			$form->setValuesByArray(array(
				'institution' => ilScanAssessmentGlobalSettings::getInstance()->getInstitution(),
				'matriculation_style' => ilScanAssessmentGlobalSettings::getInstance()->getMatriculationStyle(),
				'disable_manual_scan' => ilScanAssessmentGlobalSettings::getInstance()->isDisableManualScan()
			));
		}
		$this->tpl->setContent($form->getHTML());
	}

	/**
	 * @return ilPropertyFormGUI
	 */
	protected function getConfigurationForm()
	{
		require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';

		$form = new ilPropertyFormGUI();
		$form->setTitle($this->lng->txt('settings'));
		$form->setFormAction($this->ctrl->getFormAction($this, 'showConfigurationForm'));
		$form->setShowTopButtons(true);

		$institution = new ilTextInputGUI($this->getPluginObject()->txt('scas_institution'), 'institution');
		$form->addItem($institution);

		$matriculation = new ilTextInputGUI($this->getPluginObject()->txt('scas_matriculation_style'), 'matriculation_style');
		$matriculation->setValidationRegexp('/^[X-]+$/');
		$matriculation->setInfo($this->getPluginObject()->txt('scas_matriculation_style_info'));
		$form->addItem($matriculation);

		$disable_manual_scan = new ilCheckboxInputGUI($this->getPluginObject()->txt('scas_disable_manual_scan'), 'disable_manual_scan');
		if(!$this->getPluginObject()->checkIfScanAssessmentCronExists())
		{
			$disable_manual_scan->setDisabled(true);
			$disable_manual_scan->setInfo($this->getPluginObject()->txt('scas_disable_manual_scan_no_cron_info'));
		}
		else
		{
			$disable_manual_scan->setInfo($this->getPluginObject()->txt('scas_disable_manual_scan_info'));
		}
		$form->addItem($disable_manual_scan);
		
		$form->addCommandButton('saveConfigurationForm', $this->lng->txt('save'));

		return $form;
	}
	/**
	 *
	 */
	protected function saveConfigurationForm()
	{
		$form = $this->getConfigurationForm();
		if($form->checkInput())
		{
			try
			{
				ilScanAssessmentGlobalSettings::getInstance()->setInstitution($form->getInput('institution'));
				ilScanAssessmentGlobalSettings::getInstance()->setMatriculationStyle($form->getInput('matriculation_style'));
				ilScanAssessmentGlobalSettings::getInstance()->setDisableManualScan($form->getInput('disable_manual_scan'));
				ilScanAssessmentGlobalSettings::getInstance()->save();
				$this->ctrl->redirect($this, 'configure');
			}
			catch(ilException $e)
			{
				ilUtil::sendFailure($this->lng->txt('form_input_not_valid'));
			}
		}

		$form->setValuesByPost();
		$this->showConfigurationForm($form);
	}

	/**
	 * 
	 */
	protected function confirmDelete()
	{
		
	}
}