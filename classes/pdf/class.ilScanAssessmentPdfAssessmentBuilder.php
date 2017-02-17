<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentController.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfHelper.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfMetaData.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfMap.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfUtils.php');
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
	 * @var int
	 */
	protected $resetY = 35;

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
			if($pdf->getPage() == $start_page)
			{
				$pdf_h->pdf->getMetadata()->getIdentificationObject()->setPageNumber($start_page);
			}
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
		$this->map->setQuestionPositions($pdf->getPage(), array('question' => $question->getId() ,'answers' => $answers, 'start_x' => $question_start->getX(), 'start_y' => $question_start->getY(), 'end_x' => $question_end->getX(), 'end_y' => $question_end->getY(), 'has_checkboxes' => 1));
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
		$this->map->setQuestionPositions($pdf->getPage(), array('question' => $question->getId(), 'type' => $question->getQuestionType() ,'answers' => $answers, 'start_x' => $question_start->getX(), 'start_y' => $question_start->getY(), 'end_x' => $question_end->getX(), 'end_y' => $question_end->getY(), 'has_checkboxes' => 0));
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
		$pdf_h = new ilScanAssessmentPdfHelper($data);
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
			$pdf_h->setAddHead(true);
			$this->addAnswerData($pdf_h, $question_builder, $data->getIdentificationObject());
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
		$this->has_checkboxes = 1;
		$freestyle_container = array();
		foreach($questions as $question)
		{
			if($question->getQuestionType() == 'assFreestyleScanQuestion')
			{
				$freestyle_container[] = $question;
			}
			else
			{
				$this->addQuestionUsingTransaction($pdf_h, $question_builder, $question, $counter);
				$counter++;
			}
		}
		foreach($freestyle_container as $question)
		{
			$this->addQuestionUsingTransaction($pdf_h, $question_builder, $question, $counter);
			$counter++;
		}
	}

	/**
	 * @param $pdf_h
	 * @param assQuestion[] $questions
	 * @param $question_builder
	 */
	private function addQuestionWithoutCheckbox($pdf_h, $questions, $question_builder)
	{
		$this->addPage($pdf_h);
		$freestyle_container = array();
		$this->has_checkboxes = 0;
		$counter = 1;
		foreach($questions as $question)
		{
			if($question->getQuestionType() == 'assFreestyleScanQuestion')
			{
				$freestyle_container[] = $question;
			}
			else
			{
				$this->addQuestionWithoutCheckboxUsingTransaction($pdf_h, $question_builder, $question, $counter);
				$counter++;
			}
		}
		foreach($freestyle_container as $question)
		{
			$this->addQuestionWithoutCheckboxUsingTransaction($pdf_h, $question_builder, $question, $counter);
			$counter++;
		}
	}

	/**
	 * @param $pdf_h
	 * @param $question_builder
	 */
	private function addAnswerData($pdf_h, $question_builder, $identification)
	{
		$identification->setPageNumber($pdf_h->pdf->getPage());
		$this->addPageWithQrCode($pdf_h);
		$this->log->debug(sprintf('We are on page %s ...', $pdf_h->pdf->getPage()));
		$this->resetY = $pdf_h->pdf->getY();
		$columns = 1;
		$this->has_checkboxes = 1;
		$freestyle_temp = array();
		foreach($this->map->getQuestionPositions() as $key => $page)
		{
			foreach($page as $position => $question)
			{
				if($question['type'] == 'assFreestyleScanQuestion')
				{
					$freestyle_temp[] = $question;
				}
				else
				{
					$columns = $this->addAnswerCheckboxesUsingTransaction($pdf_h, $question_builder, $question, $columns);
				}
			}
		}
		$this->addPageWithQrCode($pdf_h);
		foreach($freestyle_temp as $question)
		{
			$this->addAnswerImageToAnswerSheetUsingTransaction($pdf_h, $question_builder, $question);
		}
	}

	/**
	 * @param ilScanAssessmentPdfHelper $pdf_h
	 * @param ilScanAssessmentPdfAssessmentQuestionBuilder $question_builder
	 * @param $question_array
	 * @param $columns
	 * @return int
	 */
	protected function addAnswerCheckboxesUsingTransaction($pdf_h, $question_builder, $question_array, $columns)
	{
		/** @var tcpdf $pdf */
		$pdf = $pdf_h->pdf;

		#$pdf->setCellMargins(PDF_CELL_MARGIN);
		$pdf->startTransaction();
		$this->log->debug(sprintf('Starting transaction for page %s ...', $pdf->getPage()));
		$question_start = new ilScanAssessmentPoint($pdf->getX(), $pdf->getY());
		$start_page = $pdf->getPage();
		$question = assQuestion::_instantiateQuestion($question_array['question']);
		$answers = $question_builder->addCheckboxToPdf($question, $question_array['answers'], $columns);
		$height = $pdf->getPageHeight();
		$y = $pdf->getY();

		if($pdf->getPage() != $start_page || $height - $y < self::PAGE_SIZE_LEFT)
		{
			$this->log->debug(sprintf('Transaction failed for page %s rollback ended up on page %s.', $start_page, $pdf->getPage()));
			if($pdf->getPage() == $start_page)
			{
				$pdf_h->pdf->getMetadata()->getIdentificationObject()->setPageNumber($start_page);
			}
			$pdf->rollbackTransaction(true);
			if($columns < 6)
			{
				$columns++;
				$pdf->setX($columns * 70);
				$pdf->setY($this->resetY);
				$question_start = new ilScanAssessmentPoint($pdf->getX(), $pdf->getY());
				$answers = $question_builder->addCheckboxToPdf($question, $question_array['answers'], $columns);
			}
			else
			{
				$columns = 1;
				$this->addPageWithQrCode($pdf_h);
				$this->resetY = 35;
				$question_start = new ilScanAssessmentPoint($pdf->getX(), $this->resetY);
				$answers = $question_builder->addCheckboxToPdf($question, $question_array['answers'], $columns);
			}

		}
		else
		{
			$pdf->commitTransaction();
			$this->log->debug(sprintf('Transaction worked for page %s commit.', $pdf->getPage()));
		}
		$point = $this->getXCoordinateFromAnswers($answers);
		$question_start->setX($point->getX());
		$question_end = new ilScanAssessmentPoint($point->getY(), $pdf->getY());
		$this->map->setQuestionPositions($pdf->getPage(), array('question' => $question->getId() ,'answers' => $answers, 'start_x' => $question_start->getX(), 'start_y' => $question_start->getY(), 'end_x' => $question_end->getX(), 'end_y' => $question_end->getY(), 'has_checkboxes' => 1));
		$pdf->Ln();
		return $columns;
	}

	/**
	 * @param ilScanAssessmentPdfHelper $pdf_h
	 * @param ilScanAssessmentPdfAssessmentQuestionBuilder $question_builder
	 * @param $question_array
	 */
	protected function addAnswerImageToAnswerSheetUsingTransaction($pdf_h, $question_builder, $question_array)
	{
		/** @var tcpdf $pdf */
		$pdf = $pdf_h->pdf;
		#$pdf->setCellMargins(PDF_CELL_MARGIN);
		$pdf->startTransaction();
		$this->log->debug(sprintf('Starting transaction for page %s ...', $pdf->getPage()));
		$question_start = new ilScanAssessmentPoint($pdf->getX(), $pdf->getY());
		$start_page = $pdf->getPage();
		$question = assQuestion::_instantiateQuestion($question_array['question']);
		$answers = $question_builder->addCheckboxToPdf($question, $question_array['answers'], 0);
		$height = $pdf->getPageHeight();
		$y = $pdf->getY();

		if($pdf->getPage() != $start_page || $height - $y < self::PAGE_SIZE_LEFT)
		{
			$this->log->debug(sprintf('Transaction failed for page %s rollback ended up on page %s.', $start_page, $pdf->getPage()));
			$pdf->rollbackTransaction(true);
			$this->addPageWithQrCode($pdf_h);
			$this->resetY = 35;
			$question_start = new ilScanAssessmentPoint($pdf->getX(), $this->resetY);
			$answers = $question_builder->addCheckboxToPdf($question, $question_array['answers'], 0);

		}
		else
		{
			$pdf->commitTransaction();
			$this->log->debug(sprintf('Transaction worked for page %s commit.', $pdf->getPage()));
		}
		$point = $this->getXCoordinateFromAnswers($answers);
		$question_start->setX($point->getX());
		$question_end = new ilScanAssessmentPoint($point->getY(), $pdf->getY());
		$this->map->setQuestionPositions($pdf->getPage(), array('question' => $question->getId() ,'answers' => $answers, 'start_x' => $question_start->getX(), 'start_y' => $question_start->getY(), 'end_x' => $question_end->getX(), 'end_y' => $question_end->getY(), 'has_checkboxes' => 1));
		$pdf->Ln();
	}
	
	private function getXCoordinateFromAnswers($answers)
	{
		$x1 = 0;
		$x2 = 0;
		foreach($answers as $key => $answer)
		{
			if($answer['x'] > $x1)
			{
				$x1 = $answer['x'];
			}
			else if($answer['end_x'] > $x2)
			{
				$x2 = $answer['end_x'];
			}
		}
		return new ilScanAssessmentPoint($x1, $x2);
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
		$number = $this->calculatePdfCountToCreate($number);

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
				$filename = $this->path_for_pdfs . $this->test->getId() . '_' . $identification->getPdfId() . self::FILE_TYPE;
				$this->writePdfFile($pdf_h, $filename);
				$this->saveQuestionData($data);
			}
			$end_time = microtime(TRUE);
			$this->log->info(sprintf('Creating pdfs finished for test %s which took %s seconds for %s tests.', $this->test->getId(), $end_time - $start_time, $number));
		}
	}

	/**
	 * @param int $number
	 * @return int
	 */
	protected function calculatePdfCountToCreate($number)
	{
		$number = $number - $this->file_helper->countFilesInDirectory($this->path_for_pdfs);
		if($this->doesDemoPdfExists())
		{
			$number++;
		}
		return $number;
	}

	/**
	 * @return bool
	 */
	protected function doesDemoPdfExists()
	{
		if(file_exists($this->path_for_pdfs . $this->test->getId() . '_demo'  . self::FILE_TYPE))
		{
			return true;
		}
		return false;
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
		$this->log->info(sprintf('Creating qr code with following identification %s.', $pdf_h->pdf->getMetadata()->getIdentification()));
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
			$checkboxes = 0;
			foreach($value as $question => $data)
			{
				if($data['has_checkboxes'] == 1)
				{
					$checkboxes = 1;
					continue;
				}
			}
			$ilDB->insert('pl_scas_pdf_data_qpl',
				array(
					'pdf_id'	=> array('integer', $ident->getPdfId()),
					'page'		=> array('integer', $key),
					'qpl_data'	=> array('text', json_encode($value)),
					'has_checkboxes' => array('integer', $checkboxes)
				));
		}
	}

}