<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentUserPackagesController.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfUtils.php');

/**
 * Class ilScanAssessmentUserPackagesControllerPdf
 */
class ilScanAssessmentUserPackagesControllerPdf extends ilScanAssessmentUserPackagesController
{
	const FLAG = 0;
	const ZIP = 1;

	/**
	 * @param ilPropertyFormGUI|null $form
	 * @return string
	 */
	public function defaultCmd(ilPropertyFormGUI $form = null)
	{

		if(!($form instanceof ilPropertyFormGUI))
		{
			$form = $this->getForm();
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
		$options = array(
			self::FLAG => $pluginObject->txt('scas_download_as_flag'),
			self::ZIP  => $pluginObject->txt('scas_download_as_zip')
		);
		$complete_download->setValue($this->configuration->getDownloadStyle());
		$complete_download->setOptions($options);
		$form->addItem($complete_download);

		$this->showPdfFilesIfExisting();

		if($this->doPdfFilesExistsInDirectory())
		{
			$form->addCommandButton(__CLASS__ . '.downloadMultiplePdfs', $pluginObject->txt('scas_download_pdf'));
			$form->addCommandButton(__CLASS__ . '.removingTheExistingPdfs', $pluginObject->txt('scas_remove_all'));
		}
		else
		{
			$form->addCommandButton(__CLASS__ . '.createPdfDocuments', $pluginObject->txt('scas_create'));
		}
		$form->addCommandButton(__CLASS__ . '.createDemoPdf', $pluginObject->txt('scas_create_demo_pdf'));
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
		$files = array();
		foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filename)
		{
			if($filename->getFilename() != '.' && $filename->getFilename() != '..')
			{
				$size    = (int)($filename->getSize() / 1024);
				$date    = date('d. F Y H:i:s', $filename->getMtime());
				$files[] = array('file_id' => $filename, 'file_name' => $filename->getBaseName(), 'file_size' => $size . 'K', 'file_date' => $date);
			}
		}
		return $files;
	}

	/**
	 * @return bool
	 */
	protected function doPdfFilesExistsInDirectory()
	{
		$preview = new ilScanAssessmentPdfAssessmentBuilder($this->test);
		$path    = $preview->getPathForPdfs();
		if($handle = opendir($path))
		{
			while(false !== ($entry = readdir($handle)))
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
		if($this->test->getFixedParticipants() === self::ZIP)
		{
			$demo->createFixedParticipantsPdf();
		}
		else
		{
			$demo->createNonPersonalisedPdf($this->configuration->getCountDocuments());
		}
		$this->redirectAndInfo($this->getCoreController()->getPluginObject()->txt('scas_pdfs_created'));
	}

	public function createDemoPdfAndCutToImagesCmd()
	{
		$pdf = new ilScanAssessmentPdfAssessmentBuilder($this->test);
		if($this->test->getFixedParticipants() === self::ZIP)
		{
			$pdf->createFixedParticipantsPdf();
		}
		else
		{
			$pdf->createNonPersonalisedPdf($this->configuration->getCountDocuments());
		}
		$path = ilUtil::getDataDir() . '/scanAssessment/tst_' . $this->test->getId();
		exec('convert -density 300 ' . $path . '/pdf/*.pdf -quality 100 ' . $path . '/scans/scans.jpg');
		$this->redirectAndInfo($this->getCoreController()->getPluginObject()->txt('scas_pdfs_created'));
	}

	public function removingTheExistingPdfsCmd()
	{
		$preview = new ilScanAssessmentPdfAssessmentBuilder($this->test);
		$path    = $preview->getPathForPdfs();
		ilUtil::delDir($path, true);
		$this->log->debug(sprintf('Removed pdfs for test %s by user with id %s.', $this->test->getId(), $this->user->getId()));
		$this->redirectAndInfo($this->getCoreController()->getPluginObject()->txt('scas_files_deleted'));
	}

	public function downloadPdfCmd()
	{
		$file_name = ilUtil::stripSlashes($_GET['file_name']);
		$file_path = ilUtil::getDataDir() . '/scanAssessment/tst_' . $this->test->getId() . '/pdf/' . $file_name;
		$this->download($file_path, $file_name);
	}

	public function downloadMultiplePdfsCmd()
	{
		$download_option = (int)$_POST['complete_download'];
		$preview         = new ilScanAssessmentPdfAssessmentBuilder($this->test);
		$files           = $this->getFolderFiles($preview->getPathForPdfs());
		$only_names      = array();
		foreach($files as $value)
		{
			$only_names[] = $value['file_id']->getPathName();
		}

		if($download_option === self::FLAG)
		{
			$utils = new ilScanAssessmentPdfUtils();
			$utils->concat($only_names);
			$utils->getPdfInline('complete.pdf');
		}
		else if($download_option == self::ZIP)
		{
			$this->createZipAndDeliver($preview, $only_names);
		}
		$this->redirect($this->getDefaultClassAndCommand());
	}

	/**
	 * @param $preview
	 * @param $only_names
	 */
	protected function createZipAndDeliver($preview, $only_names)
	{
		$zip      = new ZipArchive;
		$zip_file = $preview->getPathForZip() . '/complete.zip';
		if(file_exists($zip_file))
		{
			unlink($zip_file);
		}
		$zip->open($zip_file, ZipArchive::CREATE);
		foreach($only_names as $file)
		{
			$zip->addFile($file, basename($file));
		}
		$zip->close();
		if(file_exists($zip_file))
		{
			ilUtil::deliverFile($zip_file, 'complete.zip', 'I');
		}
	}

	public function getDefaultClassAndCommand()
	{
		return 'ilScanAssessmentUserPackagesControllerPdf.default';
	}
}