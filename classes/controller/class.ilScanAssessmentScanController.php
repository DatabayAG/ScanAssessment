<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentController.php');

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
	protected $path_to_done;
	
	/**
	 * 
	 */
	protected function init()
	{
		$this->test = ilObjectFactory::getInstanceByRefId((int) $_GET['ref_id']);

		$this->path_to_scans	= ilUtil::getDataDir() . '/scanAssessment/tst_' . $this->test->getId() . '/scans';
		$this->ensureSavePathExists($this->path_to_scans);
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
	 * @param $scanner
	 * @param $log
	 * @return array
	 */
	protected function detectMarker($scanner, $log)
	{
		$time_start = microtime(true);
		$marker = $scanner->getMarkerPosition();
		$time_end     = microtime(true);
		$time         = $time_end - $time_start;
		$log->debug('Marker Position detection duration: ' . $time);
		$log->debug($marker);

		return $marker;
	}
	/**
	 * @param $log
	 * @return array
	 */
	protected function detectQrCode($log)
	{
		$time_start = microtime(true);
		$qr			= new ilScanAssessmentQrCode('/tmp/new_file.jpg');
		$qr_pos		= $qr->getQRPosition();
		$log->debug($qr_pos);
		$time_end     = microtime(true);
		$time         = $time_end - $time_start;
		$log->debug('QR Position detection duration: ' . $time);
		$qr->drawTempImage($qr->getTempImage(),  $this->path_to_done . '/test_qr.jpg');

		return $qr_pos;
	}

	/**
	 * @param $marker
	 * @param $qr_pos
	 * @param $log
	 * @return ilScanAssessmentAnswerScanner
	 */
	protected function detectAnswers($marker, $qr_pos, $log)
	{
		$time_start = microtime(true);
		$ans = new ilScanAssessmentAnswerScanner('/tmp/new_file.jpg', $this->path_to_done);
		$val = $ans->scanImage($marker, $qr_pos);
		#$log->debug($val);
		$time_end = microtime(true);
		$time     = $time_end - $time_start;
		$log->debug('Answer Calculation duration:  ' . $time);

		return $ans;
	}

	/**
	 * @param $path
	 * @param $entry
	 * @return bool
	 */
	protected function analyseImage($path, $entry)
	{
		$log = ilScanAssessmentLog::getInstance();
		$org = $path . '/' . $entry;
		$done = $this->path_to_done . '/' . $entry;

		$log->debug('Start with file: ' . $org);

		$scanner = new ilScanAssessmentMarkerDetection($org);

		$marker = $this->detectMarker($scanner, $log);

		$scanner->drawTempImage($scanner->getTempImage(), $this->path_to_done . '/test_marker.jpg');
		$scanner->drawTempImage($scanner->getImage(), '/tmp/new_file.jpg');

		$qr_pos = $this->detectQrCode($log);

		$this->detectAnswers($marker, $qr_pos, $log);

		$log->debug('Coping file: ' . $org . ' to ' .$done );
		#copy($org, $done);

		if(file_exists($done))
		{
			unlink($org);
			$log->debug('Coping exist removing original: ' . $org);
		}
		else
		{
			$log->debug('Coping does not exist leaving original: ' . $org);
		}
	}

	protected function getNextFreeAnalysingFolder()
	{
		$path	= ilUtil::getDataDir() . '/scanAssessment/tst_' . $this->test->getId() . '/scans/analysed/';
		$counter = (count(glob("$path/*",GLOB_ONLYDIR)));
		$this->path_to_done	= $path . $counter;
		$this->ensureSavePathExists($this->path_to_done);
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
		$path = $this->path_to_scans;
		require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/class.ilScanAssessmentMarkerDetection.php';
		require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/class.ilScanAssessmentQrCode.php';
		require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/class.ilScanAssessmentAnswerScanner.php';
		$files_found = false;
		if ($handle = opendir($path)) 
		{
			while (false !== ($entry = readdir($handle))) 
			{
				if(is_dir($path .'/'. $entry) === false)
				{
					$this->getNextFreeAnalysingFolder();
					$files_found = true;
					$this->analyseImage($path, $entry);
				}
			}
			closedir($handle);
		}
		if($files_found)
		{
			ilUtil::sendInfo($this->getCoreController()->getPluginObject()->txt('scas_files_found'), true);
		}
		else
		{
			ilUtil::sendFailure($this->getCoreController()->getPluginObject()->txt('scas_no_files_found'), true);
		}
		ilUtil::redirect($this->getCoreController()->getPluginObject()->getLinkTarget(
			'ilScanAssessmentScanController.default',
			array(
				'ref_id' => (int)$_GET['ref_id']
			)
		));
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
	 * @param $path
	 */
	protected function ensureSavePathExists($path)
	{
		if( ! is_dir($path))
		{
			ilUtil::makeDirParents($path);
		}
	}

}