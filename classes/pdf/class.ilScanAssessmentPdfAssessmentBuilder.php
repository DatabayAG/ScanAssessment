<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentController.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfHelper.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfMetaData.php');
ilScanAssessmentPlugin::getInstance()->includeClass('assessment/class.ilScanAssessmentPdfAssessmentQuestionBuilder.php');
ilScanAssessmentPlugin::getInstance()->includeClass('log/class.ilScanAssessmentLog.php');
ilScanAssessmentPlugin::getInstance()->includeClass('class.ilScanAssessmentFileHelper.php');
ilScanAssessmentPlugin::getInstance()->includeClass('assessment/class.ilScanAssessmentIdentification.php');


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
	 * @var
	 */
	protected $path_for_zip;

	/**
	 * @var
	 */
	protected $document_information = array();

	/**
	 * @var ilObjTest
	 */
	protected $test;

	/**
	 * @var ilScanAssessmentLog
	 */
	protected $log;

	/**
	 * @var ilScanAssessmentFileHelper
	 */
	protected $file_helper;

	/**
	 * ilPdfPreviewBuilder constructor.
	 * @param ilObjTest $test
	 */
	public function __construct(ilObjTest $test)
	{
		$this->test				= $test;
		$this->log				= ilScanAssessmentLog::getInstance();
		$this->file_helper		= new ilScanAssessmentFileHelper($this->test->getId());
		$this->path_for_pdfs	= $this->file_helper->getPdfPath();
		$this->path_for_zip		= $this->file_helper->getPdfZipPath();
	}

	/**
	 * @param ilScanAssessmentPdfHelper $pdf_h
	 * @param ilScanAssessmentPdfAssessmentQuestionBuilder $question_builder
	 * @param $question
	 * @param $counter
	 * @return array
	 */
	protected function addQuestionUsingTransaction($pdf_h, $question_builder, $question, $counter)
	{
		/** @var tcpdf $pdf */
		$pdf = $pdf_h->pdf;

		$pdf->setCellMargins(PDF_CELL_MARGIN);

		$pdf->startTransaction();
		$this->log->debug(sprintf('Starting transaction for page %s ...', $pdf->getPage()));

		$start_page = $pdf->getPage();
		$answers = $question_builder->addQuestionToPdf($question, $counter);

		if($pdf->getPage() != $start_page)
		{
			$this->log->debug(sprintf('Transaction failed for page %s rollback ended up on page %s.', $start_page, $pdf->getPage()));
			$pdf->rollbackTransaction(true);

			$this->addPageWithQrCode($pdf_h);
			$answers = $question_builder->addQuestionToPdf($question, $counter);
		}
		else
		{
			$pdf->commitTransaction();
			$this->log->debug(sprintf('Transaction worked for page %s commit.', $pdf->getPage()));
		}
		return array('page' => $pdf->getPage(), 'question' => $question->getId() ,'answers' => $answers);
	}

	/**
	 * 
	 */
	public function createDemoPdf()
	{
		$start_time = microtime(TRUE);
		$this->log->info(sprintf('Starting to create demo pdf for test %s ...', $this->test->getId()));

		$identification	= new ilScanAssessmentIdentification();
		$identification->init($this->test->getId(), 0, 0);
		$data = new ilScanAssessmentPdfMetaData($this->test, $identification);
		$pdf_h	= $this->createPdf($data);
		$filename = $this->path_for_pdfs . $this->test->getId() . '_demo' . self::FILE_TYPE;
		$pdf_h->writeFile($filename);

		$utils = new ilScanAssessmentPdfUtils();
		$utils->concat($this->getConfiguredFilesToPrepend($filename));
		$utils->writePdfFile($filename);
		$utils->getPdfInline($filename);

		$end_time = microtime(TRUE);
		$this->log->info(sprintf('Creating demo pdf finished for test %s which took %s seconds.', $this->test->getId(), $end_time - $start_time));
	}
	
	protected function getConfiguredFilesToPrepend($org_file)
	{
		$path = $this->file_helper->getLayoutPath();
		$files = array();
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filename)
		{
			if($filename->getFilename() != '.' && $filename->getFilename() != '..')
			{
				$files[] = $filename;
			}
		}
		$files[] = $org_file;
		return $files;
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
		$this->document_information = array();

		$this->addPageWithQrCode($pdf_h);
		$counter = 1;
		foreach($questions as $question)
		{
			$this->document_information[] = $this->addQuestionUsingTransaction($pdf_h, $question_builder, $question, $counter);
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
			$identification	= new ilScanAssessmentIdentification();
			$identification->init($this->test->getId(), 0, $usr_id);
			$data 			= new ilScanAssessmentPdfMetaData($this->test, $identification);
			$usr_obj		= new ilObjUser($usr_id);

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
			$utils->concat($this->getConfiguredFilesToPrepend($filename));
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

				$identification	= new ilScanAssessmentIdentification();
				$identification->init($this->test->getId(), 0, $i);
				$data 			= new ilScanAssessmentPdfMetaData($this->test, $identification);

				$pdf_h	= $this->createPdf($data);
				$filename = $this->path_for_pdfs . $this->test->getId() . '_' . $i . self::FILE_TYPE;
				$file_names[] = $filename;
				$pdf_h->writeFile($filename);
				$utils = new ilScanAssessmentPdfUtils();
				$utils->concat($this->getConfiguredFilesToPrepend($filename));
				$utils->writePdfFile($filename);
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
		$pdf_h->pdf->setQRCodeOnThisPage(true);
		$pdf_h->createQRCode($pdf_h->pdf->getMetadata()->getIdentification());
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
	 * @return mixed
	 */
	public function getPathForZip()
	{
		return $this->path_for_zip;
	}


}