<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentUserPackagesController.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfUtils.php');

/**
 * Class ilScanAssessmentUserPackagesControllerPdf
 */
class ilScanAssessmentUserPackagesControllerPdf extends ilScanAssessmentUserPackagesController
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
		$form->setFormAction($pluginObject->getFormAction(__CLASS__ . '.saveForm', array('ref_id' => (int)$_GET['ref_id'])));
		$form->setTitle($pluginObject->txt('scas_user_packages'));

		$complete_download = new ilSelectInputGUI($pluginObject->txt('scas_complete_download'), 'complete_download');
		$complete_download->setInfo($pluginObject->txt('scas_complete_download_info'));
		$options = array(	0 => $pluginObject->txt('scas_download_as_flag'),
							1 => $pluginObject->txt('scas_download_as_zip')
		);
		$complete_download->setValue($this->configuration->getDownloadStyle());
		$complete_download->setDisabled(true);
		$complete_download->setOptions($options);
		$form->addItem($complete_download);

		$this->showPdfFilesIfExisting($form);
		$form->addCommandButton(__CLASS__ . '.createDemoPdf', $pluginObject->txt('scas_create_demo_pdf'));
		if($this->doPdfFilesExistsInDirectory())
		{
			$form->addCommandButton(__CLASS__ . '.removingTheExistingPdfs', $pluginObject->txt('scas_remove_all'));
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
		$this->tabs->setSubTabActive('user_packages_pdf');
	}

	/**
	 * 
	 */
	public function showPdfFilesIfExisting()
	{
		$preview = new ilScanAssessmentPdfAssessmentBuilder($this->test);
		ilScanAssessmentPlugin::getInstance()->includeClass('ui/tables/class.ilScanAssessmentScanTablePdfGUI.php');
		$tbl = new ilScanAssessmentScanTablePdfGUI(new ilScanAssessmentUIHookGUI(), 'editComments');
		$tbl->setData($this->getFolderFiles($preview->getPathForPdfs()));
		return $tbl;
	}

	protected function getFolderFiles($path)
	{
		$files	= array();
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filename)
		{
			if($filename->getFilename() != '.' && $filename->getFilename() != '..')
			{
				$size = (int) ($filename->getSize() / 1024);
				$date = date('d. F Y H:i:s', $filename->getMtime());
				$files[] = array('file_id' => $filename, 'file_name' => $filename->getBaseName(), 'file_size' => $size . 'K', 'file_date' => $date);
			}
		}
		return $files;
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

		$tpl = $this->getCoreController()->getPluginObject()->getTemplate('tpl.test_configuration.html', true, true);
		$tpl->setVariable('FORM', $form->getHTML());

		$sidebar = $this->renderSteps();
		$tpl->setVariable('STATUS', $sidebar);
		
		$tbl = $this->showPdfFilesIfExisting();
		$tpl->setVariable('CONTENT', $tbl->getHTML());
		
		return $tpl->get();
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
		$this->log->debug(sprintf('Demo pdf for test %s by user with id %s.', $this->test->getId(), $this->user->getId()));
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
			$demo->createNonPersonalisedPdf($this->configuration->getCountDocuments());
		}
		ilUtil::sendInfo($this->getCoreController()->getPluginObject()->txt('scas_pdfs_created'), true);
		ilUtil::redirect($this->getLink('ilScanAssessmentUserPackagesControllerPdf'));
	}


	public function createDemoPdfAndCutToImagesCmd()
	{
		$pdfs = new ilScanAssessmentPdfAssessmentBuilder($this->test);
		if($this->test->getFixedParticipants() === 1)
		{
			$pdfs->createFixedParticipantsPdf();
		}
		else
		{
			$pdfs->createNonPersonalisedPdf($this->configuration->getCountDocuments());
		}
		$path = ilUtil::getDataDir() . '/scanAssessment/tst_' . $this->test->getId() ;
		exec('convert -density 300 '. $path .'/pdf/*.pdf -quality 100 ' . $path . '/scans/scans.jpg');
		ilUtil::sendInfo($this->getCoreController()->getPluginObject()->txt('scas_pdfs_created'), true);
		ilUtil::redirect($this->getCoreController()->getPluginObject()->getLinkTarget(
			'ilScanAssessmentUserPackagesControllerPdf.default',
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
		$this->log->debug(sprintf('Removed pdfs for test %s by user with id %s.', $this->test->getId(), $this->user->getId()));
		#ilUtil::sendInfo($this->getCoreController()->getPluginObject()->txt('scas_removed'), true);
		ilUtil::redirect($this->getCoreController()->getPluginObject()->getLinkTarget(
			'ilScanAssessmentUserPackagesControllerPdf.default',
			array(
				'ref_id' => (int)$_GET['ref_id']
			)
		));
	}

	/**
	 */
	public function downloadPdfCmd()
	{
		$file_name = ilUtil::stripSlashes($_GET['file_name']);
		$file_path = ilUtil::getDataDir() . '/scanAssessment/tst_' . $this->test->getId() . '/pdf/' . $file_name;
		if(file_exists($file_path))
		{
			ilUtil::deliverFile($file_path, $file_name, '', 'I');
		}
		ilUtil::redirect($this->getCoreController()->getPluginObject()->getLinkTarget(
			'ilScanAssessmentUserPackagesControllerPdf.default',
			array(
				'ref_id' => (int)$_GET['ref_id']
			)
		));
	}
}