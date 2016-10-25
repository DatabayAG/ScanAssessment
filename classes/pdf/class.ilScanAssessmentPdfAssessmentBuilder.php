<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentController.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfHelper.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfMetaData.php');
ilScanAssessmentPlugin::getInstance()->includeClass('assessment/class.ilScanAssessmentPdfAssessmentQuestionBuilder.php');
ilScanAssessmentPlugin::getInstance()->includeClass('log/class.ilScanAssessmentLog.php');


/**
 * Class ilScanAssessmentLayoutController
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentPdfAssessmentBuilder
{

	const FILE_TYPE = '.pdf';
	/**
	 * @var 
	 */
	protected $path_for_pdfs;

	/**
	 * @var ilObjTest
	 */
	protected $test;

	/**
	 * @var ilScanAssessmentLog
	 */
	protected $log;

	/**
	 * ilPdfPreviewBuilder constructor.
	 * @param ilObjTest $test
	 */
	public function __construct(ilObjTest $test)
	{
		$this->test	= $test;
		$this->log	= ilScanAssessmentLog::getInstance();
		$this->path_for_pdfs = ilUtil::getDataDir() . '/scanAssessment/tst_' . $this->test->getId() . '/pdf/';
		$this->ensureSavePathExists($this->path_for_pdfs);
	}

	/**
	 * @param ilScanAssessmentPdfHelper $pdf_h
	 * @param ilScanAssessmentPdfAssessmentQuestionBuilder $question_builder
	 * @param $question
	 * @param $counter
	 */
	protected function addQuestionUsingTransaction($pdf_h, $question_builder, $question, $counter)
	{
		/** @var tcpdf $pdf */
		$pdf = $pdf_h->pdf;

		$pdf->setCellMargins(PDF_CELL_MARGIN);

		$pdf->startTransaction();
		$this->log->debug(sprintf('Starting transaction for page %s ...', $pdf->getPage()));

		$start_page = $pdf->getPage();
		$question_builder->addQuestionToPdf($question, $counter);

		if($pdf->getPage() != $start_page)
		{
			$this->log->debug(sprintf('Transaction failed for page %s rollback ended up on page %s.', $start_page, $pdf->getPage()));
			$pdf->rollbackTransaction(true);

			$this->addPageWithQrCode($pdf_h);
			$question_builder->addQuestionToPdf($question, $counter);
		}
		else
		{
			$pdf->commitTransaction();
			$this->log->debug(sprintf('Transaction worked for page %s commit.', $pdf->getPage()));
		}
	}

	/**
	 * 
	 */
	public function createDemoPdf()
	{
		$start_time = microtime(TRUE);
		$this->log->info(sprintf('Starting to create demo pdf for test %s ...', $this->test->getId()));
		$data = new ilScanAssessmentPdfMetaData($this->test, 'DEMO');
		$pdf_h	= $this->createPdf($data);
		$filename = $this->path_for_pdfs . $this->test->getId() . '_demo' . self::FILE_TYPE;
		$pdf_h->writeFile($filename);
		$utils = new ilScanAssessmentPdfUtils();
		$utils->concat(array($this->getConfiguredFilesToPrepend(), $filename));
		$utils->getPdfInline($filename);
		$end_time = microtime(TRUE);
		$this->log->info(sprintf('Creating demo pdf finished for test %s which took %s seconds.', $this->test->getId(), $end_time - $start_time));
	}
	
	protected function getConfiguredFilesToPrepend()
	{
		return '/home/gvollbach/Downloads/1.pdf';
	}

	/**
	 * @param ilScanAssessmentPdfMetaData $data
	 * @return ilScanAssessmentPdfHelper
	 */
	public function createPdf($data)
	{
		$pdf_h	= new ilScanAssessmentPdfHelper($data);
		$question_builder = new ilScanAssessmentPdfAssessmentQuestionBuilder($this->test, $pdf_h);
		$questions = $question_builder->instantiateQuestions();

		$this->addPageWithQrCode($pdf_h);
		$counter = 1;
		foreach($questions as $question)
		{
			$this->addQuestionUsingTransaction($pdf_h, $question_builder, $question, $counter);
			$counter++;
		}
		return $pdf_h;
	}

	/**
	 *
	 */
	public function createFixedParticipantsPdf()
	{
		$participants	= $this->test->getInvitedUsers();
		$file_names		= array();
		$start_time = microtime(TRUE);
		$this->log->info(sprintf('Starting to create pdfs for test %s ...', $this->test->getId()));
		$counter = 0;
		foreach($participants as $usr_id => $user)
		{
			$data		= new ilScanAssessmentPdfMetaData($this->test, $usr_id);
			$usr_obj	= new ilObjUser($usr_id);

			$pdf_h	= $this->createPdf($data);
			$filename = $this->path_for_pdfs . $this->test->getId() . '_' . $counter . self::FILE_TYPE;

			if(! $data->isNotPersonalised())
			{
				$data->setStudentMatriculation($usr_obj->getMatriculation());
				$data->setStudentName($usr_obj->getFullname());
				$filename = $this->path_for_pdfs . $this->test->getId() . '_' . $usr_id . '_' . $user['lastname'] . '_' . $user['firstname'] . self::FILE_TYPE;
			}
			$file_names[] = $filename;
			$pdf_h->writeFile($filename);
			$utils = new ilScanAssessmentPdfUtils();
			$utils->concat(array($this->getConfiguredFilesToPrepend(), $filename));
			$utils->writePdfFile($filename);
			$counter++;
		}
		$end_time = microtime(TRUE);
		$this->log->info(sprintf('Creating pdfs finished for test %s which took %s seconds for %s tests.', $this->test->getId(), $end_time - $start_time, count($participants)));
	}
	
	/**
	 * @param int $number
	 */
	public function createNonPersonalisedPdf($number)
	{
		if($number > 0)
		{
			$file_names		= array();
			$start_time = microtime(TRUE);
			$this->log->info(sprintf('Starting to create pdfs for test %s ...', $this->test->getId()));
			for($i = 1; $i <= $number; $i++)
			{
				$data = new ilScanAssessmentPdfMetaData($this->test, $i);
				$pdf_h	= $this->createPdf($data);
				$filename = $this->path_for_pdfs . $i . self::FILE_TYPE;
				$file_names[] = $filename;
				$pdf_h->writeFile($filename);
			}
			$end_time = microtime(TRUE);
			$this->log->info(sprintf('Creating pdfs finished for test %s which took %s seconds for %s tests.', $this->test->getId(), $end_time - $start_time, $number));
		}
	}


	/**
	 * @param ilScanAssessmentPdfHelper $pdf_h
	 */
	protected function addQrCodeToPage($pdf_h)
	{
		$pdf_h->pdf->getPage();
		$pdf_h->pdf->setQRCodeOnThisPage(true);
		$pdf_h->createQRCode('DemoCode');
	}

	/**
	 * @param ilScanAssessmentPdfHelper $pdf_h
	 */
	protected function addPageWithQrCode($pdf_h)
	{
		$this->addQrCodeToPage($pdf_h);
		$pdf_h->addPage();
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

	/**
	 * @return mixed
	 */
	public function getPathForPdfs()
	{
		return $this->path_for_pdfs;
	}

}