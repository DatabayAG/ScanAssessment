<?php
ilScanAssessmentPlugin::getInstance()->includeClass('assessment/class.ilScanAssessmentIdentification.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentMarkerDetection.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentQrCode.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentAnswerScanner.php');
ilScanAssessmentPlugin::getInstance()->includeClass('../libs/php-qrcode-detector-decoder/lib/QrReader.php');

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
	 * @param $scanner
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
	 * @return array
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
	 * @return ilScanAssessmentAnswerScanner
	 */
	protected function detectAnswers($marker, $qr_pos, $log)
	{
		$time_start = microtime(true);
		$ans = new ilScanAssessmentAnswerScanner($this->file_helper->getScanTempPath() . 'new_file.jpg', $this->path_to_done);
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

		/** @var ilScanAssessmentVector $qr_pos */
		$qr_pos = $this->detectQrCode($log);
		$im2 = $scanner->image_helper->imageCrop($scanner->image_helper->getImage(), $qr_pos);
		if ($im2 !== FALSE)
		{
			$path = $this->file_helper->getScanTempPath() . '/qr.jpg';
			$scanner->image_helper->drawTempImage($im2, $path);
			if(! $this->processQrCode($path, $org))
			{
				return false;
			}
		}
		else
		{
			$this->log->warn('No QR Code found!');
			$this->getAnalysingFolder();
		}
		$done = $this->path_to_done . '/' . $entry;

		$scanner->drawTempImage($scanner->getTempImage(), $this->path_to_done . '/test_marker.jpg');

		$this->detectAnswers($marker, $qr_pos, $log);

		$log->debug('Coping file: ' . $org . ' to ' .$done );
		$this->file_helper->moveFile($org, $done);
		return true;
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

		$this->getAnalysingFolder($code);
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
		}
		else
		{
			$return_value = self::LOCKED;
		}

		if($this->releaseScanLock())
		{
			$this->log->info(sprintf('Removed lock file: %s' , $this->getScanLockFilePath()));
		}
		else
		{
			$this->log->debug(sprintf('No lock to remove: %s', $this->getScanLockFilePath()));
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
	protected function isScanLocked()
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