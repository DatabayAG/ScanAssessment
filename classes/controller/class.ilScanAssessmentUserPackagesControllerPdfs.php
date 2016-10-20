<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentUserPackagesController.php');

/**
 * Class ilScanAssessmentUserPackagesControllerPdfs
 */
class ilScanAssessmentUserPackagesControllerPdfs extends ilScanAssessmentUserPackagesController
{
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
		$form->setFormAction($pluginObject->getFormAction(__CLASS__ . '.saveForm'));
		$form->setTitle($pluginObject->txt('scas_user_packages'));

		$this->showPdfFilesIfExisting($form);
		$form->addCommandButton(__CLASS__ . '.createDemoPdf', $pluginObject->txt('scas_create_demo_pdf'));
		if($this->doPdfFilesExistsInDirectory())
		{
			$form->addCommandButton(__CLASS__ . '.removingTheExistingPdfs', $pluginObject->txt('scas_remove'));
		}
		else
		{
			$form->addCommandButton(__CLASS__ . '.createPdfDocuments', $pluginObject->txt('scas_create'));
		}
		$form->addCommandButton(__CLASS__ . '.createDemoPdfAndCutToImages', 'Create Example Scans');

		return $form;
	}

	protected function addTabs($active_sub = 'user_packages_settings')
	{
		parent::addTabs();
		/**
		 * @var $ilTabs ilTabsGUI
		 */
		global $ilTabs;
		$ilTabs->setSubTabActive('user_packages_pdf');
	}

	/**
	 * @param $form
	 */
	public function showPdfFilesIfExisting($form)
	{
		require_once 'Services/Form/classes/class.ilNestedListInputGUI.php';
		$preview = new ilScanAssessmentPdfAssessmentBuilder($this->test);
		$path = $preview->getPathForPdfs();
		$list = new ilNestedListInputGUI();
		$list->setTitle($this->getCoreController()->getPluginObject()->txt('scas_created_pdfs'));
		$add_item = false;
		if ($handle = opendir($path))
		{
			while (false !== ($entry = readdir($handle)))
			{
				if($entry != '.' && $entry != '..')
				{
					$list->addListNode($entry, $entry);
					$add_item = true;
				}
			}
			closedir($handle);
			if($add_item)
			{
				$form->addItem($list);
			}
		}
	}

	/**
	 * @return bool
	 */
	protected function doPdfFilesExistsInDirectory()
	{
		$preview = new ilScanAssessmentPdfAssessmentBuilder($this->test);
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
		$demo = new ilScanAssessmentPdfAssessmentBuilder($this->test);
		$demo->createDemoPdf();
	}

	public function createPdfDocumentsCmd()
	{
		$demo = new ilScanAssessmentPdfAssessmentBuilder($this->test);
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
		ilUtil::redirect($this->getLink('ilScanAssessmentUserPackagesControllerPdf'));
	}


	public function createDemoPdfAndCutToImagesCmd()
	{
		$demo = new ilScanAssessmentPdfAssessmentBuilder($this->test);
		if($this->test->getFixedParticipants() === 1)
		{
			$demo->createFixedParticipantsPdf();
		}
		else
		{
			$todo_get_value_from_number_input = 2;
			$demo->createNonPersonalisedPdf($todo_get_value_from_number_input);
		}
		$path = ilUtil::getDataDir() . '/scanAssessment/tst_' . $this->test->getId() ;
		exec('convert -density 300 '. $path .'/pdf/*.pdf -quality 100 ' . $path . '/scans/scans.jpg');
		ilUtil::sendInfo($this->getCoreController()->getPluginObject()->txt('scas_pdfs_created'), true);
		ilUtil::redirect($this->getCoreController()->getPluginObject()->getLinkTarget(
			'ilScanAssessmentUserPackagesController.default',
			array(
				'ref_id' => (int)$_GET['ref_id']
			)
		));
	}

	public function removingTheExistingPdfsCmd()
	{
		$preview = new ilScanAssessmentPdfAssessmentBuilder($this->test);
		$path = $preview->getPathForPdfs();
		ilUtil::delDir($path, true);
		#ilUtil::sendInfo($this->getCoreController()->getPluginObject()->txt('scas_removed'), true);
		ilUtil::redirect($this->getCoreController()->getPluginObject()->getLinkTarget(
			'ilScanAssessmentUserPackagesController.default',
			array(
				'ref_id' => (int)$_GET['ref_id']
			)
		));
	}
}