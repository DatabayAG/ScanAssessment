<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentController.php');

/**
 * Class ilScanAssessmentUserPackagesController
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentUserPackagesController extends ilScanAssessmentController
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

		$this->getCoreController()->getPluginObject()->includeClass('model/class.ilScanAssessmentUserPackagesConfiguration.php');
		$this->configuration = new ilScanAssessmentUserPackagesConfiguration($this->test->getId());
		$this->isPreconditionFulfilled();
	}

	/**
	 * 
	 */
	protected function isPreconditionFulfilled()
	{
		$this->getCoreController()->getPluginObject()->includeClass('steps/class.ilScanAssessmentLayoutStep.php');
		$activated		= new ilScanAssessmentIsActivatedStep($this->getCoreController()->getPluginObject(), $this->test);
		$layout			= new ilScanAssessmentLayoutStep($this->getCoreController()->getPluginObject(), $this->test);

		if(!$activated->isFulfilled() || !$layout->isFulfilled())
		{
			ilUtil::sendFailure($this->getCoreController()->getPluginObject()->txt('scas_previous_step_unfulfilled'), true);
			ilUtil::redirect($this->getCoreController()->getPluginObject()->getLinkTarget(
				'ilScanAssessmentLayoutController.default',
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
		$ilTabs->setTabActive('user_packages');

		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->getCoreController()->getPluginObject()->getFormAction(__CLASS__ . '.saveForm'));
		$form->setTitle($this->getCoreController()->getPluginObject()->txt('scas_user_packages'));

		$creation = new ilSelectInputGUI($this->getCoreController()->getPluginObject()->txt('scas_creation'), 'creation');
		$creation->setInfo($this->getCoreController()->getPluginObject()->txt('scas_creation_info'));
		$personalised = array('inline' => $this->getCoreController()->getPluginObject()->txt('scas_creation_personalised'),
					   'sheet' => $this->getCoreController()->getPluginObject()->txt('scas_creation_non_personalised')
		);
		$creation->setOptions($personalised);
		$form->addItem($creation);

		$form->addCommandButton(__CLASS__ . '.saveForm', $this->lng->txt('save'));
		$form->addCommandButton(__CLASS__ . '.analyse', 'Analyse');


		return $form;
	}

	public function analyseCmd()
	{
		$file = '/tmp/pruefung_r.jpg';
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
			imagejpeg($demo->getTempImage(), '/tmp/test.jpg');
			$time_end = microtime(true);
			$time = $time_end - $time_start;
			$runs += $time;
			echo '<br>' . $time;
			$qr = new ilScanAssessmentQrCode($file);
			$qr_pos = $qr->getQRPosition();
			echo print_r($qr_pos);

			$qr = new ilScanAssessmentAnswerScanner($file);
			echo print_r($qr->scanImage($marker, $qr_pos ));
			imagejpeg($qr->getTempImage(), '/tmp/test2.jpg');
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
}