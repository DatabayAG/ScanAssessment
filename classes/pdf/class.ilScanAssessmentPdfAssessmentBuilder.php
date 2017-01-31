<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentController.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfHelper.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfMetaData.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfMap.php');
ilScanAssessmentPlugin::getInstance()->includeClass('assessment/class.ilScanAssessmentPdfAssessmentQuestionBuilder.php');
ilScanAssessmentPlugin::getInstance()->includeClass('log/class.ilScanAssessmentLog.php');
ilScanAssessmentPlugin::getInstance()->includeClass('class.ilScanAssessmentFileHelper.php');
ilScanAssessmentPlugin::getInstance()->includeClass('assessment/class.ilScanAssessmentIdentification.php');


/**
 * Class ilScanAssessmentLayoutGUI
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentPdfAssessmentBuilder
{
	
	const PAGE_SIZE_LEFT = 30;

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
	 * @var ilScanAssessmentPdfMap
	 */
	protected $map;

	/**
	 * @var int
	 */
	protected $shuffle = false;

	/**
	 * @var ilScanAssessmentTestConfiguration
	 */
	protected $config;
	
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
		$this->map				= new ilScanAssessmentPdfMap();
		$config					=  new ilScanAssessmentTestConfiguration($this->test->getId());
		$this->config			= $config;
		if($config->getShuffle() == 1)
		{
			$this->shuffle			= true;
		}

	}

	/**
	 * @param ilScanAssessmentPdfHelper $pdf_h
	 * @param ilScanAssessmentPdfAssessmentQuestionBuilder $question_builder
	 * @param assQuestion $question
	 * @param int $counter
	 */
	protected function addQuestionUsingTransaction($pdf_h, $question_builder, $question, $counter)
	{
		/** @var tcpdf $pdf */
		$pdf = $pdf_h->pdf;

		$pdf->setCellMargins(PDF_CELL_MARGIN);
		$pdf->startTransaction();
		$this->log->debug(sprintf('Starting transaction for page %s ...', $pdf->getPage()));
		$question_start = new ilScanAssessmentPoint($pdf->getX(), $pdf->getY());
		$start_page = $pdf->getPage();
		$answers = $question_builder->addQuestionToPdf($question, $counter);
		$height = $pdf->getPageHeight();
		$y = $pdf->getY();
		if($pdf->getPage() != $start_page || $height - $y < self::PAGE_SIZE_LEFT)
		{
			$this->log->debug(sprintf('Transaction failed for page %s rollback ended up on page %s.', $start_page, $pdf->getPage()));
			$pdf->rollbackTransaction(true);
			$this->addPageWithQrCode($pdf_h);
			$question_start = new ilScanAssessmentPoint($pdf->getX(), $pdf->getY());
			$answers = $question_builder->addQuestionToPdf($question, $counter);
		}
		else
		{
			$pdf->commitTransaction();
			$this->log->debug(sprintf('Transaction worked for page %s commit.', $pdf->getPage()));
		}
		$question_end = new ilScanAssessmentPoint($pdf->getX(), $pdf->getY());
		$this->map->setQuestionPositions($pdf->getPage(), array('question' => $question->getId() ,'answers' => $answers, 'start_x' => $question_start->getX(), 'start_y' => $question_start->getY(), 'end_x' => $question_end->getX(), 'end_y' => $question_end->getY()));
	}

	/**
	 * @param ilScanAssessmentPdfHelper $pdf_h
	 * @param ilScanAssessmentPdfAssessmentQuestionBuilder $question_builder
	 * @param assQuestion $question
	 * @param int $counter
	 */
	protected function addQuestionWithoutCheckboxUsingTransaction($pdf_h, $question_builder, $question, $counter)
	{
		/** @var tcpdf $pdf */
		$pdf = $pdf_h->pdf;

		$pdf->setCellMargins(PDF_CELL_MARGIN);
		$pdf->startTransaction();
		$this->log->debug(sprintf('Starting transaction for page %s ...', $pdf->getPage()));
		$question_start = new ilScanAssessmentPoint($pdf->getX(), $pdf->getY());
		$start_page = $pdf->getPage();
		$answers = $question_builder->addQuestionToPdfWithoutCheckbox($question, $counter);
		$height = $pdf->getPageHeight();
		$y = $pdf->getY();
		if($pdf->getPage() != $start_page || $height - $y < self::PAGE_SIZE_LEFT)
		{
			$this->log->debug(sprintf('Transaction failed for page %s rollback ended up on page %s.', $start_page, $pdf->getPage()));
			$pdf->rollbackTransaction(true);
			$this->addPage($pdf_h);
			$question_start = new ilScanAssessmentPoint($pdf->getX(), $pdf->getY());
			$answers = $question_builder->addQuestionToPdfWithoutCheckbox($question, $counter);
		}
		else
		{
			$pdf->commitTransaction();
			$this->log->debug(sprintf('Transaction worked for page %s commit.', $pdf->getPage()));
		}
		$question_end = new ilScanAssessmentPoint($pdf->getX(), $pdf->getY());
		$this->map->setQuestionPositions($pdf->getPage(), array('question' => $question->getId() ,'answers' => $answers, 'start_x' => $question_start->getX(), 'start_y' => $question_start->getY(), 'end_x' => $question_end->getX(), 'end_y' => $question_end->getY()));
	}

	/**
	 * 
	 */
	public function createDemoPdf()
	{
		$start_time = microtime(TRUE);
		$this->log->info(sprintf('Starting to create demo pdf for test %s ...', $this->test->getId()));

		$identification	= new ilScanAssessmentIdentification();
		$identification->init($this->test->getId(), 0);
		$data = new ilScanAssessmentPdfMetaData($this->test, $identification);
		$pdf_h	= $this->createPdf($data);
		$filename = $this->path_for_pdfs . $this->test->getId() . '_demo' . self::FILE_TYPE;
		$pdf_h->writeFile($filename);

		$utils = new ilScanAssessmentPdfUtils();
		$utils->concat($this->getConfiguredFilesToPrepend($filename));
		$utils->writePdfFile($filename);
		$utils->getPdfInline($filename);

		$this->saveQuestionData($data);
		$end_time = microtime(TRUE);
		$this->log->info(sprintf('Creating demo pdf finished for test %s which took %s seconds.', $this->test->getId(), $end_time - $start_time));
	}

	/**
	 * @param $org_file
	 * @return array
	 */
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
		$this->map = new ilScanAssessmentPdfMap();
		$question_builder = new ilScanAssessmentPdfAssessmentQuestionBuilder($this->test, $pdf_h);
		$questions = $question_builder->instantiateQuestions($this->shuffle);

		if($this->config->getPdfMode() == 0)
		{
			$this->addQuestionWithCheckbox($pdf_h, $questions, $question_builder);
		}
		else
		{
			$this->addQuestionWithoutCheckbox($pdf_h, $questions, $question_builder);
			$this->addAnswerData($pdf_h, $question_builder);
		}

		$this->log->info('Document Information:' . json_encode($data->getIdentification()) . json_encode($this->document_information));
		return $pdf_h;
	}

	/**
	 * @param $pdf_h
	 * @param $questions
	 * @param $question_builder
	 */
	private function addQuestionWithCheckbox($pdf_h, $questions, $question_builder)
	{
		$this->addPageWithQrCode($pdf_h);
		$counter = 1;
		foreach($questions as $question)
		{
			$this->addQuestionUsingTransaction($pdf_h, $question_builder, $question, $counter);
			$counter++;
		}
	}

	/**
	 * @param $pdf_h
	 * @param $questions
	 * @param $question_builder
	 */
	private function addQuestionWithoutCheckbox($pdf_h, $questions, $question_builder)
	{
		$this->addPage($pdf_h);
		$counter = 1;
		foreach($questions as $question)
		{
			$this->addQuestionWithoutCheckboxUsingTransaction($pdf_h, $question_builder, $question, $counter);
			$counter++;
		}
	}	

	/**
	 * @param $pdf_h
	 */
	private function addAnswerData($pdf_h, $question_builder)
	{
		$this->addPageWithQrCode($pdf_h);
		foreach($this->map->getQuestionPositions() as $key => $page)
		{
			$this->addAnswerCheckboxesUsingTransaction($pdf_h, $question_builder);
		}

		$a = 0;
	}


	/**
	 * @param ilScanAssessmentPdfHelper $pdf_h
	 * @param ilScanAssessmentPdfAssessmentQuestionBuilder $question_builder
	 * @param assQuestion $question
	 * @param int $counter
	 */
	protected function addAnswerCheckboxesUsingTransaction($pdf_h, $question_builder)
	{
		/** @var tcpdf $pdf */
		$pdf = $pdf_h->pdf;

		$pdf->setCellMargins(PDF_CELL_MARGIN);
		$pdf->startTransaction();
		$this->log->debug(sprintf('Starting transaction for page %s ...', $pdf->getPage()));
		$question_start = new ilScanAssessmentPoint($pdf->getX(), $pdf->getY());
		$start_page = $pdf->getPage();
		$answers = $question_builder->addCheckboxToPdf($question);
		$height = $pdf->getPageHeight();
		$y = $pdf->getY();
		if($pdf->getPage() != $start_page || $height - $y < self::PAGE_SIZE_LEFT)
		{
			$this->log->debug(sprintf('Transaction failed for page %s rollback ended up on page %s.', $start_page, $pdf->getPage()));
			$pdf->rollbackTransaction(true);
			$this->addPageWithQrCode($pdf_h);
			$question_start = new ilScanAssessmentPoint($pdf->getX(), $pdf->getY());
			$answers = $question_builder->addCheckboxToPdf($question);
		}
		else
		{
			$pdf->commitTransaction();
			$this->log->debug(sprintf('Transaction worked for page %s commit.', $pdf->getPage()));
		}
		$question_end = new ilScanAssessmentPoint($pdf->getX(), $pdf->getY());
		$this->map->setQuestionPositions($pdf->getPage(), array('question' => $question->getId() ,'answers' => $answers, 'start_x' => $question_start->getX(), 'start_y' => $question_start->getY(), 'end_x' => $question_end->getX(), 'end_y' => $question_end->getY()));
	}
	
	/**
	 * @param $participants
	 */
	public function createFixedParticipantsPdf($participants)
	{
		$start_time = microtime(TRUE);
		$this->log->info(sprintf('Starting to create pdfs for test %s ...', $this->test->getId()));
		$counter = 0;
		foreach($participants as $usr_id => $user)
		{
			$identification	= new ilScanAssessmentIdentification();
			$identification->init($this->test->getId(), 0);
			$data 			= new ilScanAssessmentPdfMetaData($this->test, $identification);
			$usr_obj		= new ilObjUser($usr_id);

			$filename = $this->path_for_pdfs . $this->test->getId() . '_' . $counter . self::FILE_TYPE;

			if(! $data->isNotPersonalised())
			{
				$data->setStudentMatriculation($usr_obj->getMatriculation());
				$data->setStudentName($usr_obj->getFullname());
				$filename = $this->path_for_pdfs . $this->test->getId() . '_' . $usr_id . '_' . $usr_obj->getLastname() . '_' . $usr_obj->getFirstname() . self::FILE_TYPE;
				$this->saveUserIdForPdf($identification->getPdfId(), $usr_obj->getId());
			}
			$pdf_h	= $this->createPdf($data);
			$this->writePdfFile($pdf_h, $filename);
			$this->saveQuestionData($data);
			$counter++;
		}
		$end_time = microtime(TRUE);
		$this->log->info(sprintf('Creating pdfs finished for test %s which took %s seconds for %s tests.', $this->test->getId(), $end_time - $start_time, count($participants)));
	}

	/**
	 * @param $pdf_id
	 * @param $usr_id
	 */
	protected function saveUserIdForPdf($pdf_id, $usr_id)
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		$ilDB->update('pl_scas_pdf_data',
			array(
				'usr_id' => array('integer', $usr_id),
			),
			array(
				'pdf_id' => array('integer', $pdf_id)
			)
		);
		$this->log->debug(sprintf('Since this is a fixed test mapping for pdf (%s) to user %s was set.', $pdf_id, $usr_id));
	}
	
	/**
	 * @param int $number
	 */
	public function createNonPersonalisedPdf($number)
	{
		if($number > 0)
		{
			$start_time = microtime(TRUE);
			$this->log->info(sprintf('Starting to create pdfs for test %s ...', $this->test->getId()));
			for($i = 1; $i <= $number; $i++)
			{
				$identification	= new ilScanAssessmentIdentification();
				$identification->init($this->test->getId(), 0);
				$data 			= new ilScanAssessmentPdfMetaData($this->test, $identification);

				$pdf_h	= $this->createPdf($data);
				$filename = $this->path_for_pdfs . $this->test->getId() . '_' . $i . self::FILE_TYPE;
				$this->writePdfFile($pdf_h, $filename);
				$this->saveQuestionData($data);
			}
			$end_time = microtime(TRUE);
			$this->log->info(sprintf('Creating pdfs finished for test %s which took %s seconds for %s tests.', $this->test->getId(), $end_time - $start_time, $number));
		}
	}

	/**
	 * @param ilScanAssessmentPdfHelper $pdf_h
	 * @param string $filename
	 */
	protected function writePdfFile($pdf_h, $filename)
	{
		$pdf_h->writeFile($filename);
		$utils = new ilScanAssessmentPdfUtils();
		$utils->concat($this->getConfiguredFilesToPrepend($filename));
		$utils->writePdfFile($filename);
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
	 * @param ilScanAssessmentPdfHelper $pdf_h
	 */
	protected function addPage($pdf_h)
	{
		$pdf_h->addPage();
	}

	/**
	 * @return string
	 */
	public function getPathForZip()
	{
		return $this->path_for_zip;
	}

	/**
	 * @param ilScanAssessmentPdfMetaData $data
	 */
	protected function saveQuestionData($data)
	{
		$ident = new ilScanAssessmentIdentification();
		$ident->parseIdentificationString($data->getIdentification());

		global $ilDB;
		foreach($this->map->getQuestionPositions() as $key => $value)
		{
			$ilDB->insert('pl_scas_pdf_data_qpl',
				array(
					'pdf_id'	=> array('integer', $ident->getPdfId()),
					'page'		=> array('integer', $key),
					'qpl_data'	=> array('text', json_encode($value)),
				));
		}
	}

}