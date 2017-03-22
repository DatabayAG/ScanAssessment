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
            $dpi_limits = ilScanAssessmentGlobalSettings::getInstance()->getTiffDpiLimits();
            $form->setValuesByArray(array(
				'institution'				=> ilScanAssessmentGlobalSettings::getInstance()->getInstitution(),
				'matriculation_style'		=> ilScanAssessmentGlobalSettings::getInstance()->getMatriculationStyle(),
				'disable_manual_scan'		=> ilScanAssessmentGlobalSettings::getInstance()->isDisableManualScan(),
				'disable_manual_pdf'		=> ilScanAssessmentGlobalSettings::getInstance()->isDisableManualPdf(),
                'tiff_enabled'				=> ilScanAssessmentGlobalSettings::getInstance()->isTiffEnabled(),
                'tiff_dpi_minimum'			=> $dpi_limits[0],
                'tiff_dpi_maximum'			=> $dpi_limits[1],
				'scas_enable_debug_export'	=> ilScanAssessmentGlobalSettings::getInstance()->isEnableDebugExportTab(),
				'min_value_black'			=> ilScanAssessmentGlobalSettings::getInstance()->getMinValueBlack(),
				'min_marked_area'			=> ilScanAssessmentGlobalSettings::getInstance()->getMinMarkedArea(),
				'marked_area_checked'		=> ilScanAssessmentGlobalSettings::getInstance()->getMarkedAreaChecked(),
				'marked_area_unchecked'		=> ilScanAssessmentGlobalSettings::getInstance()->getMarkedAreaUnchecked(),
				'save_file_type'			=> ilScanAssessmentGlobalSettings::getInstance()->getSaveFileType()
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
		$disable_manual_pdf = new ilCheckboxInputGUI($this->getPluginObject()->txt('scas_disable_manual_pdf'), 'disable_manual_pdf');
		if(!$this->getPluginObject()->checkIfScanAssessmentCronExists())
		{
			$disable_manual_pdf->setDisabled(true);
			$disable_manual_pdf->setInfo($this->getPluginObject()->txt('scas_disable_manual_scan_no_cron_info'));
		}
		else
		{
			$disable_manual_pdf->setInfo($this->getPluginObject()->txt('scas_disable_manual_pdf_info'));
		}
		$form->addItem($disable_manual_pdf);

        $tiff_support = new ilCheckboxInputGUI($this->getPluginObject()->txt('scas_tiff_enabled'), 'tiff_enabled');
        $tiff_support->setInfo($this->getPluginObject()->txt('scas_tiff_enabled_info'));
        $form->addItem($tiff_support);

        $tiff_dpi_minimum = new ilTextInputGUI($this->getPluginObject()->txt('scas_tiff_dpi_minimum'), 'tiff_dpi_minimum');
        $tiff_dpi_minimum->setValidationRegexp('/^[0-9]*$/');
        $tiff_dpi_minimum->setInfo($this->getPluginObject()->txt('scas_tiff_dpi_minimum_info'));
        $form->addItem($tiff_dpi_minimum);

        $tiff_dpi_maximum = new ilTextInputGUI($this->getPluginObject()->txt('scas_tiff_dpi_maximum'), 'tiff_dpi_maximum');
        $tiff_dpi_maximum->setValidationRegexp('/^[0-9]*$/');
        $tiff_dpi_maximum->setInfo($this->getPluginObject()->txt('scas_tiff_dpi_maximum_info'));
        $form->addItem($tiff_dpi_maximum);

        if(!class_exists(Imagick))
        {
            $tiff_support->setInfo($this->getPluginObject()->txt('scas_tiff_no_imagemagick_info'));
            $tiff_support->setDisabled(true);
            $tiff_dpi_minimum->setInfo($this->getPluginObject()->txt('scas_tiff_no_imagemagick_info'));
            $tiff_dpi_minimum->setDisabled(true);
            $tiff_dpi_maximum->setInfo($this->getPluginObject()->txt('scas_tiff_no_imagemagick_info'));
            $tiff_dpi_maximum->setDisabled(true);
        }

		$min_value_black = new ilTextInputGUI($this->getPluginObject()->txt('scas_min_value_black'), 'min_value_black');
		$min_value_black->setInfo($this->getPluginObject()->txt('scas_min_value_black_info'));
		$form->addItem($min_value_black);
		$min_marked_area = new ilTextInputGUI($this->getPluginObject()->txt('scas_min_marked_area'), 'min_marked_area');
		$min_marked_area->setInfo($this->getPluginObject()->txt('scas_min_marked_area_info'));
		$form->addItem($min_marked_area);
		$marked_area_checked = new ilTextInputGUI($this->getPluginObject()->txt('scas_marked_area_checked'), 'marked_area_checked');
		$marked_area_checked->setInfo($this->getPluginObject()->txt('scas_marked_area_checked_info'));
		$form->addItem($marked_area_checked);
		$marked_area_unchecked = new ilTextInputGUI($this->getPluginObject()->txt('scas_marked_area_unchecked'), 'marked_area_unchecked');
		$marked_area_unchecked->setInfo($this->getPluginObject()->txt('scas_marked_area_unchecked_info'));
		$form->addItem($marked_area_unchecked);

		$save_file_type = new ilSelectInputGUI($this->getPluginObject()->txt('scas_save_file_type'), 'save_file_type');
		$save_file_type->setInfo($this->getPluginObject()->txt('scas_save_file_type_info'));
		$options = array(
			'jpg' => 'jpg',
			'gif' => 'gif',
			'png' => 'png'
		);
		$save_file_type->setOptions($options);
		$form->addItem($save_file_type);
		
		$enable_debug_export = new ilCheckboxInputGUI($this->getPluginObject()->txt('scas_enable_debug_export'), 'scas_enable_debug_export');
		$enable_debug_export->setInfo($this->getPluginObject()->txt('scas_enable_debug_export_info'));
		$form->addItem($enable_debug_export);
        
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
				ilScanAssessmentGlobalSettings::getInstance()->setDisableManualPdf($form->getInput('disable_manual_pdf'));
                ilScanAssessmentGlobalSettings::getInstance()->setTiffEnabled($form->getInput('tiff_enabled'));
                ilScanAssessmentGlobalSettings::getInstance()->setTiffDpiLimits(array(
                    $form->getInput('tiff_dpi_minimum'), $form->getInput('tiff_dpi_maximum')));
				ilScanAssessmentGlobalSettings::getInstance()->setMinValueBlack($form->getInput('min_value_black'));
				ilScanAssessmentGlobalSettings::getInstance()->setMinMarkedArea($form->getInput('min_marked_area'));
				ilScanAssessmentGlobalSettings::getInstance()->setMarkedAreaChecked($form->getInput('marked_area_checked'));
				ilScanAssessmentGlobalSettings::getInstance()->setMarkedAreaUnchecked($form->getInput('marked_area_unchecked'));
				ilScanAssessmentGlobalSettings::getInstance()->setEnableDebugExportTab($form->getInput('scas_enable_debug_export'));
				ilScanAssessmentGlobalSettings::getInstance()->setSaveFileType($form->getInput('save_file_type'));
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