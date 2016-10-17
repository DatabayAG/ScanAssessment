<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentController.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfPreviewBuilder.php');

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
		$this->test = ilObjectFactory::getInstanceByRefId((int) $_GET['ref_id']);

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

		if($this->test->getFixedParticipants() === 1)
		{
			$creation = new ilSelectInputGUI($this->getCoreController()->getPluginObject()->txt('scas_creation'), 'creation');
			$creation->setInfo($this->getCoreController()->getPluginObject()->txt('scas_creation_info'));
			$personalised = array(
				'personalised' => $this->getCoreController()->getPluginObject()->txt('scas_creation_personalised'),
				'non_personalised' => $this->getCoreController()->getPluginObject()->txt('scas_creation_non_personalised')
			);
			$creation->setOptions($personalised);
			$form->addItem($creation);
		}
		else
		{
			$count_pdfs = new ilNumberInputGUI($this->getCoreController()->getPluginObject()->txt('scas_count_pdfs'), 'count_pdfs');
			$count_pdfs->setInfo($this->getCoreController()->getPluginObject()->txt('scas_count_pdfs_info'));
			$count_pdfs->setMinValue(1);
			$form->addItem($count_pdfs);
		}

		$complete_download = new ilSelectInputGUI($this->getCoreController()->getPluginObject()->txt('scas_complete_download'), 'complete_download');
		$complete_download->setInfo($this->getCoreController()->getPluginObject()->txt('scas_complete_download_info'));
		$options = array(	'complete_flag' => $this->getCoreController()->getPluginObject()->txt('scas_download_as_flag'), 
							'complete_zip' => $this->getCoreController()->getPluginObject()->txt('scas_download_as_zip')
		);
		$complete_download->setOptions($options);
		$form->addItem($complete_download);

		$this->showPdfFilesIfExisting($form);
		$form->addCommandButton(__CLASS__ . '.createDemoPdf', $this->getCoreController()->getPluginObject()->txt('scas_create_demo_pdf'));
		if($this->doPdfFilesExistsInDirectory())
		{
			$form->addCommandButton(__CLASS__ . '.createPdfDocumentsAfterRemovingTheExisting', $this->getCoreController()->getPluginObject()->txt('scas_recreate'));
		}
		else
		{
			$form->addCommandButton(__CLASS__ . '.createPdfDocuments', $this->getCoreController()->getPluginObject()->txt('scas_create'));
		}

		$form->addCommandButton(__CLASS__ . '.saveForm', $this->lng->txt('save'));

		return $form;
	}

	/**
	 * @param $form
	 */
	public function showPdfFilesIfExisting($form)
	{
		require_once 'Services/Form/classes/class.ilNestedListInputGUI.php';
		$preview = new ilPdfPreviewBuilder($this->test);
		$path = $preview->getPathForPdfs();
		$list = new ilNestedListInputGUI();
		if ($handle = opendir($path)) 
		{
			while (false !== ($entry = readdir($handle))) 
			{
				if($entry != '.' && $entry != '..')
				{
					$list->addListNode($entry, $entry);
				}
			}
			$form->addItem($list);
			closedir($handle);
		}
	}

	/**
	 * @return bool
	 */
	protected function doPdfFilesExistsInDirectory()
	{
		$preview = new ilPdfPreviewBuilder($this->test);
		$path = $preview->getPathForPdfs();
		if ($handle = opendir($path))
		{
			while (false !== ($entry = readdir($handle)))
			{
				if($entry != '.' && $entry != '..')
				{
					return true;
				}
			}
			closedir($handle);
		}
		return false;
	}

	public function createDemoPdfCmd()
	{
		$demo = new ilPdfPreviewBuilder($this->test);
		$demo->createDemoPdf();
	}

	public function createPdfDocumentsCmd()
	{
		$demo = new ilPdfPreviewBuilder($this->test);
		if($this->test->getFixedParticipants() === 1)
		{
			$demo->createFixedParticipantsPdf();
		}
		else
		{
			$todo_get_value_from_number_input = 2;
			$demo->createNonPersonalisedPdf($todo_get_value_from_number_input);
		}
		ilUtil::sendInfo($this->getCoreController()->getPluginObject()->txt('scas_pdfs_created'), true);
		ilUtil::redirect($this->getCoreController()->getPluginObject()->getLinkTarget(
			'ilScanAssessmentUserPackagesController.default',
			array(
				'ref_id' => (int)$_GET['ref_id']
			)
		));
	}
	
	public function createPdfDocumentsAfterRemovingTheExistingCmd()
	{
		$preview = new ilPdfPreviewBuilder($this->test);
		$path = $preview->getPathForPdfs();
		ilUtil::delDir($path, true);
		$this->createPdfDocumentsCmd();
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