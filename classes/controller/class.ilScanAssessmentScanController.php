<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentController.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfPreviewBuilder.php');

/**
 * Class ilScanAssessmentScanController
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentScanController extends ilScanAssessmentController
{
	/**
	 * @var ilObjTest
	 */
	protected $test;

	/**
	 * @var ilScanAssessmentTestConfiguration
	 */
	protected $configuration;

	protected $path_to_scans;
	
	/**
	 * 
	 */
	protected function init()
	{
		$this->test = ilObjectFactory::getInstanceByRefId((int) $_GET['ref_id']);

		$this->path_to_scans = $path = ilUtil::getDataDir() . '/scanAssessment/tst_' . $this->test->getId() . '/scans';
		$this->ensureSavePathExists();
		$this->getCoreController()->getPluginObject()->includeClass('model/class.ilScanAssessmentScanConfiguration.php');
		$this->configuration = new ilScanAssessmentScanConfiguration($this->test->getId());
		$this->isPreconditionFulfilled();
	}

	protected function isPreconditionFulfilled()
	{
		$this->getCoreController()->getPluginObject()->includeClass('steps/class.ilScanAssessmentIsActivatedStep.php');
		$activated		= new ilScanAssessmentIsActivatedStep($this->getCoreController()->getPluginObject(), $this->test);
		$layout			= new ilScanAssessmentLayoutStep($this->getCoreController()->getPluginObject(), $this->test);
		$user_packages	= new ilScanAssessmentUserPackagesExportedStep($this->getCoreController()->getPluginObject(), $this->test);
		
		if(! $activated->isFulfilled() || !$layout->isFulfilled() || !$user_packages->isFulfilled())
		{
			ilUtil::sendFailure($this->getCoreController()->getPluginObject()->txt('scas_previous_step_unfulfilled'), true);
			ilUtil::redirect($this->getCoreController()->getPluginObject()->getLinkTarget(
				'ilScanAssessmentUserPackagesController.default',
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

		$form->addCommandButton(__CLASS__ . '.analyse', 'Analyse');
		$form->addCommandButton(__CLASS__ . '.saveForm', $this->lng->txt('save'));

		return $form;
	}

	public function analyseCmd()
	{
		$file = $this->path_to_scans . '/pruefung_r.jpg';
		require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/class.ilScanAssessmentMarkerDetection.php';
		require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/class.ilScanAssessmentQrCode.php';
		require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/class.ilScanAssessmentAnswerScanner.php';

		$runs = 0;
		for($i = 0; $i <= 1; $i++)
		{
			echo '<br>Run '.$i .'<br>';
			$demo = new ilScanAssessmentMarkerDetection($file);
			$time_start = microtime(true);
			$marker = $demo->getMarkerPosition();
			print_r($marker);
			$demo->drawTempImage($demo->getTempImage(), 'test_marker.jpg');
			$time_end = microtime(true);
			$time = $time_end - $time_start;
			$runs += $time;
			echo '<br>' . $time;
			$qr = new ilScanAssessmentQrCode($file);
			$qr_pos = $qr->getQRPosition();
			echo print_r($qr_pos);
			$qr->drawTempImage($qr->getTempImage(), 'test_qr.jpg');
			#$qr = new ilScanAssessmentAnswerScanner($file);
			#echo print_r($qr->scanImage($marker, $qr_pos ));
		}
		echo '<br><br>' . $runs;
		$runs = $runs / $i;
		echo '<br><br>' . $runs;
		exit();
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

	/**
	 *
	 */
	protected function ensureSavePathExists()
	{
		$path = ilUtil::getDataDir() . '/' . $this->parent_folder_name . '/tst_' . $this->obj_id;
		if( ! is_dir($path))
		{
			ilUtil::makeDirParents($path);
		}
	}

}