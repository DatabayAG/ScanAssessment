<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentUserPackagesGUI.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfUtils.php');
ilScanAssessmentPlugin::getInstance()->includeClass('class.ilScanAssessmentFileHelper.php');
ilScanAssessmentPlugin::getInstance()->includeClass('class.ilScanAssessmentGlobalSettings.php');

/**
 * Class ilScanAssessmentUserPackagesPdfGUI
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentUserPackagesPdfGUI extends ilScanAssessmentUserPackagesGUI
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

		/** @var ilTemplate $tpl */
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

		if($this->file_helper->doFilesExistsInDirectory($this->file_helper->getPdfPath()))
		{
			$form->addCommandButton(__CLASS__ . '.downloadMultiplePdfs', $pluginObject->txt('scas_download_pdf'));
			$number = $this->configuration->getCountDocuments();
			$actual = $this->file_helper->countFilesInDirectory($this->file_helper->getPdfPath());
			if($actual < $number)
			{
				$form->addCommandButton(__CLASS__ . '.createMissingPdfs', $pluginObject->txt('scas_create_missing'));
			}
			$form->addCommandButton(__CLASS__ . '.deleteQuestion', $pluginObject->txt('scas_remove_all'));
		}
		else
		{
			if(! $this->getCoreController()->getPluginObject()->checkIfScanAssessmentCronExists() || ! ilScanAssessmentGlobalSettings::getInstance()->isDisableManualPdf())
			{
				$form->addCommandButton(__CLASS__ . '.createPdfDocuments', $pluginObject->txt('scas_create'));
			}
			else
			{
				ilUtil::sendInfo($this->getCoreController()->getPluginObject()->txt('scas_manual_pdf_disabled'));
			}
			$form->addCommandButton(__CLASS__ . '.createDemoPdf', $pluginObject->txt('scas_create_demo_pdf'));
		}
		#$form->addCommandButton(__CLASS__ . '.createDemoPdfAndCutToImages', 'Create Example Scans');

		return $form;
	}

	/**
	 * @return string
	 */
	public function deleteQuestionCmd()
	{
		require_once 'Services/Utilities/classes/class.ilConfirmationGUI.php';
		$pluginObject = $this->getCoreController()->getPluginObject();
		$this->tabs->setTabActive('user_packages');
		$this->tabs->addSubTab('user_packages_settings', $pluginObject->txt('scas_settings'), '');
		$this->tabs->addSubTab('user_packages_pdf', $pluginObject->txt('scas_pdf'), '');

		$this->tabs->setSubTabActive('user_packages_pdf');
		$confirm = new ilConfirmationGUI();
		$confirm->setFormAction($pluginObject->getFormAction(__CLASS__ . '.deleteFiles'));
		$confirm->setHeaderText($pluginObject->txt('scas_sure_delete_file'));
		$confirm->setConfirm($pluginObject->txt('scas_confirm'), __CLASS__ . '.removingTheExistingPdfs');
		$confirm->setCancel($pluginObject->txt('scas_cancel'), __CLASS__ . '.cancel');
		return $confirm->getHTML();
	}

	/**
	 * @return string
	 */
	public function cancelCmd()
	{
		return $this->defaultCmd();
	}

	/**
	 * @param string $active_sub
	 */
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
		ilScanAssessmentPlugin::getInstance()->includeClass('ui/tables/class.ilScanAssessmentScanTablePdfGUI.php');
		$tbl = new ilScanAssessmentScanTablePdfGUI(new ilScanAssessmentUIHookGUI(), 'editComments');
		$tbl->setData($this->getFolderFiles( $this->file_helper->getPdfPath()));
		return $tbl;
	}

	/**
	 * @param $path
	 * @return array
	 */
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

	public function createDemoPdfCmd()
	{
		$demo = new ilScanAssessmentPdfAssessmentBuilder($this->test);
		$demo->createDemoPdf();
		$this->log->debug(sprintf('Demo pdf for test %s by user with id %s.', $this->test->getId(), $this->user->getId()));
	}

	public function createPdfDocumentsCmd()
	{
		$pdf_builder = new ilScanAssessmentPdfAssessmentBuilder($this->test);
		if($this->test->getFixedParticipants() === 1)
		{
			$participants = $this->test->getInvitedUsers();
			if(sizeof($participants) > 0)
			{
				$pdf_builder->createFixedParticipantsPdf($participants);
				$this->redirectAndInfo($this->getCoreController()->getPluginObject()->txt('scas_pdfs_created'));
			}
			else
			{
				$this->redirectAndInfo($this->getCoreController()->getPluginObject()->txt('scas_least_one_participant'));
			}
		}
		else
		{
		    $number = $this->configuration->getCountDocuments();
		    if ($number > 0) {
                $pdf_builder->createNonPersonalisedPdf($number);
                $this->redirectAndInfo($this->getCoreController()->getPluginObject()->txt('scas_pdfs_created'));
            } else {
                $this->redirectAndInfo($this->getCoreController()->getPluginObject()->txt('scas_pdfs_zero_count'));
            }
		}
	}
	
	public function createMissingPdfsCmd()
	{
		$number = $this->configuration->getCountDocuments();
		$actual = $this->file_helper->countFilesInDirectory($this->file_helper->getPdfPath());
		if($actual < $number)
		{
			$pdf_builder = new ilScanAssessmentPdfAssessmentBuilder($this->test);
			$pdf_builder->createNonPersonalisedPdf($number);
			$this->redirectAndInfo($this->getCoreController()->getPluginObject()->txt('scas_pdfs_created'));
		}
	}

	public function createDemoPdfAndCutToImagesCmd()
	{
		$pdf = new ilScanAssessmentPdfAssessmentBuilder($this->test);
		if($this->test->getFixedParticipants() === 1)
		{
			$pdf->createFixedParticipantsPdf($this->test->getInvitedUsers());
		}
		else
		{
			$pdf->createNonPersonalisedPdf($this->configuration->getCountDocuments());
		}
		exec('convert -density 300 ' . $this->file_helper->getPdfPath() . '*.pdf -alpha remove -quality 100 ' . $this->file_helper->getScanPath() . 'scans.jpg');
		$this->redirectAndInfo($this->getCoreController()->getPluginObject()->txt('scas_pdfs_created'));
	}

	public function removingTheExistingPdfsCmd()
	{
		$path    = $this->file_helper->getPdfPath();
		$this->removePdfDataFromDatabase();
		ilUtil::delDir($path, true);
		$this->log->debug(sprintf('Removed pdfs for test %s by user with id %s.', $this->test->getId(), $this->user->getId()));
		$this->redirectAndInfo($this->getCoreController()->getPluginObject()->txt('scas_files_deleted'));
	}

	protected function removePdfDataFromDatabase()
	{
		global $ilDB;

		$pdf_ids = array();
		$test_id = $this->test->getId();

		$res = $ilDB->queryF(
			'SELECT pdf_id FROM pl_scas_pdf_data
			WHERE obj_id = %s ',
			array('integer'),
			array($test_id)
		);

		while($row = $ilDB->fetchAssoc($res))
		{
			$pdf_ids[$row['pdf_id']] = $row['pdf_id'];
		}

		$ilDB->manipulate('DELETE FROM pl_scas_pdf_data_qpl WHERE ' . $ilDB->in('pdf_id', $pdf_ids, false, 'integer'));
		$ilDB->manipulate('DELETE FROM pl_scas_pdf_data WHERE ' . $ilDB->in('obj_id', array($test_id), false, 'integer'));
	}

	public function downloadPdfCmd()
	{
		$file_name = ilUtil::stripSlashes($_GET['file_name']);
		$file_path = $this->file_helper->getPdfPath() . $file_name;
		$this->download($file_path, $file_name);
	}

	public function downloadMultiplePdfsCmd()
	{
		$download_option = (int)$_POST['complete_download'];
		$preview         = new ilScanAssessmentPdfAssessmentBuilder($this->test);
		$files           = $this->getFolderFiles($this->file_helper->getPdfPath());
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
	 * @param $files
	 */
	protected function createZipAndDeliver($preview, $files)
	{
		$this->file_helper->createZipAndDeliverFromFiles($preview->getPathForZip(), $files, 'complete.zip');
	}

	/**
	 * @return string
	 */
	public function getDefaultClassAndCommand()
	{
		return 'ilScanAssessmentUserPackagesPdfGUI.default';
	}
}