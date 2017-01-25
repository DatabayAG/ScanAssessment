<?php
ilScanAssessmentPlugin::getInstance()->includeClass('assessment/class.ilScanAssessmentIdentification.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentMarkerDetection.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentQrCode.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentAnswerScanner.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentRevision.php');
ilScanAssessmentPlugin::getInstance()->includeClass('../libs/php-qrcode-detector-decoder/lib/QrReader.php');

/**
 * Class ilScanAssessmentScanProcess
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentScanProcess
{
	const FOUND		= 0;
	const LOCKED	= 1;
	const NOT_FOUND	= 2;

	/**
	 * @var string
	 */
	protected $path_to_done;

	/**
	 * @var ilScanAssessmentFileHelper
	 */
	protected $file_helper;

	/**
	 * @var ilScanAssessmentLog
	 */
	protected $log;

	/**
	 * @var ilObjTest
	 */
	protected $test;

	/**
	 * ilScanAssessmentScanProcess constructor.
	 * @param ilScanAssessmentFileHelper $file_helper
	 * @param                            $test_obj_id
	 */
	public function __construct(ilScanAssessmentFileHelper $file_helper, $test_obj_id)
	{
		$this->file_helper	= $file_helper;
		$this->log			= ilScanAssessmentLog::getInstance();
		$this->test 		= ilObjectFactory::getInstanceByObjId((int) $test_obj_id);
	}

	/**
	 * @param ilScanAssessmentMarkerDetection $scanner
	 * @param ilScanAssessmentLog $log
	 * @return array
	 */
	protected function detectMarker($scanner, $log)
	{
		$time_start	= microtime(true);
		$marker		= $scanner->getMarkerPosition();
		$time_end	= microtime(true);
		$time		= $time_end - $time_start;
		$log->debug('Marker Position detection duration: ' . $time);
		$log->debug($marker);

		return $marker;
	}

	/**
	 * @param ilScanAssessmentLog $log
	 * @return bool|ilScanAssessmentVector
	 */
	protected function detectQrCode($log)
	{
		$time_start = microtime(true);
		$qr			= new ilScanAssessmentQrCode( $this->file_helper->getScanTempPath() . 'new_file.jpg');
		$qr_pos		= $qr->getQRPosition();
		$time_end   = microtime(true);
		$time       = $time_end - $time_start;
		$log->debug('QR Position detection duration: ' . $time);

		return $qr_pos;
	}

	/**
	 * @param $marker
	 * @param $qr_pos
	 * @param ilScanAssessmentLog $log
	 * @param $qr_ident
	 * @return ilScanAssessmentAnswerScanner
	 */
	protected function detectAnswers($marker, $qr_pos, $log, $qr_ident)
	{
		$time_start = microtime(true);
		$ans = new ilScanAssessmentAnswerScanner($this->file_helper->getScanTempPath() . 'new_file.jpg', $this->path_to_done, $qr_ident);
		$val = $ans->scanImage($marker, $qr_pos);
		#$log->debug($val);
		$time_end = microtime(true);
		$time     = $time_end - $time_start;
		$log->debug('Answer Calculation duration:  ' . $time);

		return $ans;
	}

	/**
	 * @param $path
	 * @param $entry
	 * @return bool
	 */
	protected function analyseImage($path, $entry)
	{
		$log = ilScanAssessmentLog::getInstance();
		$org = $path . '/' . $entry;
		copy($org, $this->file_helper->getScanTempPath() . 'new_file.jpg');
		$log->debug('Start with file: ' . $org);

		$scanner = new ilScanAssessmentMarkerDetection($org);
		$marker = $this->detectMarker($scanner, $log);

		$qr_pos = $this->detectQrCode($log);
		if($qr_pos !== false)
		{
			$im2 = $scanner->image_helper->imageCrop($scanner->image_helper->getImage(), $qr_pos);
			if($im2 !== false)
			{
				$path = $this->file_helper->getScanTempPath() . 'qr.jpg';
				$scanner->image_helper->drawTempImage($im2, $path);
				$qr_code = $this->processQrCode($path, $org);
				if(! $qr_code)
				{
					return false;
				}
			}
			else
			{
				$this->log->warn('No QR Code found!');
				$this->getAnalysingFolder();
			}
		}

		$done = $this->path_to_done . '/' . $entry;

		$scanner->drawTempImage($scanner->getTempImage(), $this->path_to_done . '/test_marker.jpg');

		$scan_answer_object = $this->detectAnswers($marker, $qr_pos, $log, $qr_code);
		$this->processAnswers($scan_answer_object, $qr_code, $scanner);
		$log->debug('Coping file: ' . $org . ' to ' .$done );
		//TODO: uncomment this again
		#$this->file_helper->moveFile($org, $done);
		return true;
	}

	/**
	 * @param ilScanAssessmentAnswerScanner $answers
	 * @param ilScanAssessmentIdentification $qr_code
	 * @param $scanner
	 */
	protected function processAnswers($answers, $qr_code, $scanner)
	{
		global $ilDB;

		ilScanAssessmentRevision::removeOldPdfData($qr_code);
		$path = $this->file_helper->getRevisionPath() . '/qpl/' . $qr_code->getPdfId() . '/';
		$this->file_helper->ensurePathExists($path);
		foreach($answers->getCheckBoxContainer() as $key => $value)
		{
			$pos = 'l';
			if(array_key_exists('correctness', $value) && $value['correctness'] == 0)
			{
				$pos = 'r';
			}

			if($value['marked'] == 2)
			{
				$id	= $ilDB->nextId('pl_scas_scan_data');
				$ilDB->insert('pl_scas_scan_data',
					array(
						'answer_id'		=> array('integer', $id),
						'pdf_id'		=> array('integer', $qr_code->getPdfId()),
						'test_id'		=> array('integer', $qr_code->getTestId()),
						'page'			=> array('integer', $qr_code->getPageNumber()),
						'qid'			=> array('integer', $value['qid']),
						'value1'		=> array('text', ilUtil::stripSlashes($value['aid'])),
						'value2'		=> array('text', ilUtil::stripSlashes($value['value2'])),
						'correctness'	=> array('text', ilUtil::stripSlashes($pos)),
					));
			}
			$temp = $scanner->image_helper->imageCrop($scanner->image_helper->getImage(), $value['vector']);

			$file_path = $path . $qr_code->getPageNumber() . '_' . $value['qid'] . '_' . $value['aid'] . '_' . $pos . '_' . $value['marked']  .'.jpg';
			$scanner->image_helper->drawTempImage($temp, $file_path);
		}
	}

	/**
	 * @param $path
	 * @param $org
	 * @return false|ilScanAssessmentIdentification
	 */
	protected function processQrCode($path, $org)
	{
		$code = $this->readQrCode($path);
		if($code != false)
		{
			$identification = new ilScanAssessmentIdentification();
			$identification->parseIdentificationString($code);
			$this->getAnalysingFolder($identification->getSavePathName());
			if($identification->getTestId() != $this->test->getId())
			{
				$this->log->warn(sprintf('This img %s does not belong to this test id %s', $org, $this->test->getId()));
				return false;
			}
		}
		else
		{
			$this->log->warn('QR Code could not be read!');
			return false;
		}

		$this->file_helper->moveFile($path, $this->path_to_done . '/qr.jpg');
		return $identification;
	}

	/**
	 * @param string $identifier
	 */
	protected function getAnalysingFolder($identifier = '')
	{
		$path	= $this->file_helper->getAnalysedPath();
		if($identifier === '')
		{
			$counter = (count(glob("$path/*",GLOB_ONLYDIR)));
			$this->path_to_done	= $path . $counter;
		}
		else
		{
			$this->path_to_done	= $path . $identifier;
		}
		$this->file_helper->ensurePathExists($this->path_to_done);
	}

	/**
	 * @return int
	 */
	public function analyse()
	{
		$path = $this->file_helper->getScanPath();
		$return_value	= self::NOT_FOUND;

		if($this->acquireScanLock())
		{
			$this->log->info(sprintf('Created lock file: %s', $this->getScanLockFilePath()));

			if ($handle = opendir($path))
			{
				while (false !== ($entry = readdir($handle)))
				{
					if(is_dir($path . '/' . $entry) === false)
					{
						if($entry !== 'scan_assessment.lock')
						{
							$return_value = self::FOUND;
							$this->analyseImage($path, $entry);
						}
					}
				}
				closedir($handle);
			}
			if($this->releaseScanLock())
			{
				$this->log->info(sprintf('Removed lock file: %s' , $this->getScanLockFilePath()));
			}
			else
			{
				$this->log->debug(sprintf('No lock to remove: %s', $this->getScanLockFilePath()));
			}
		}
		else
		{
			$return_value = self::LOCKED;
		}

		return $return_value;
	}

	/**
	 * @param $path
	 * @return bool
	 */
	protected function readQrCode($path)
	{
		$qr_code = new QrReader($path);
		$txt = $qr_code->text();
		if($txt != '')
		{
			$this->log->debug(sprintf('Found id %s in qr code.', $txt));
			return $txt;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	protected function acquireScanLock()
	{
		if(! $this->isScanLocked())
		{
			if(!@file_put_contents($this->getScanLockFilePath(), getmypid(), LOCK_EX))
			{
				return false;
			}
			return true;
		}
		return false;
	}

	/**
	 * @return string
	 */
	protected function getScanLockFilePath()
	{
		return $this->file_helper->getScanPath() . 'scan_assessment.lock';
	}

	/**
	 * @return boolean
	 */
	public function isScanLocked()
	{
		if(file_exists($this->getScanLockFilePath()))
		{
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	protected function releaseScanLock()
	{
		if(file_exists($this->getScanLockFilePath()))
		{
			if(@unlink($this->getScanLockFilePath()))
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		return true;
	}

}