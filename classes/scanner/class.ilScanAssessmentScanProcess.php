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
	 * @var array
	 */
	protected $non_conform_files = array();

	/**
	 * @var array
	 */
	protected $files_not_for_this_test = array();

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
		$this->rescale		= 0;
	}

	/**
	 * @param ilScanAssessmentMarkerDetection $scanner
	 * @param ilScanAssessmentLog $log
	 * @return array
	 */
	protected function detectMarker($scanner, $log)
	{
		$time_start	= microtime(true);
		$marker		= $scanner->getMarkerPosition($this->file_helper->getScanTempPath());
		if($marker === false)
		{
			$marker = $this->detectMarkerAfterCrop($scanner);
		}
		$time_end	= microtime(true);
		$time		= $time_end - $time_start;
		$log->debug('Marker Position detection duration: ' . $time);
		$log->debug($marker);

		return $marker;
	}

	/**
	 * @param ilScanAssessmentMarkerDetection $scanner
	 * @return array | bool
	 */
	protected function detectMarkerAfterCrop($scanner)
	{
		$this->log->debug(sprintf('Marker not found retrying after cropping...'));
		$image  = new ilScanAssessmentGDWrapper($scanner->getFn());
		$scaled = $image->imageCropWithSource($image, $image->getImageSizeX() / 10, $image->getImageSizeY() / 100, 0, 0, $this->file_helper->getScanTempPath() . '/new_file.jpg');
		$scanner->setTempImage($scaled);
		$scanner->image_helper->setImage($scaled);
		$scanner->setImage($scaled);
		$marker = $scanner->getMarkerPosition($this->file_helper->getScanTempPath());
		return $marker;
	}

	/**
	 * @param $scanner
	 * @param ilScanAssessmentLog $log
	 * @param $marker
	 * @return bool
	 */
	protected function checkIfMustBeCropped($scanner, $log, $marker)
	{
		$corrected = $scanner->getCorrectedPosition();
		$x1        = $marker[0]->getPosition()->getX();
		$x2        = $marker[1]->getPosition()->getX();
		$y1        = $marker[0]->getPosition()->getY();
		$y2        = $marker[1]->getPosition()->getY();
		$marker_should_be_at_x = 10 * $corrected->getX();
		$marker_should_be_at_y = 10 * $corrected->getY();

		$log->debug('Corrected Position values [' . $corrected->getX() .' ,' . $corrected->getY() . ']');
		$log->debug('Top Marker should be at ' . $marker_should_be_at_x .' ' . $marker_should_be_at_y);
		$log->debug('Top Marker is at ' . $x1 .' ' . $y1);

		$a =  $scanner->image_helper->getImageSizeY() - $marker_should_be_at_y;
		$log->debug('Bottom Marker should be at ' . $marker_should_be_at_x . ' ' . $a);
		$log->debug('Bottom Marker is at ' . $x2 .' ' . $y2);

		$x3 = $x1 - $marker_should_be_at_x;
		$y3 = $y1 - $marker_should_be_at_y;

		$x4 = 0;#$x2 - $marker_should_be_at_x;
		$y4 = 0;#$a - $y2;
		$log->debug('Crop would start at ' . $x3 .' ' . $y3. ' ' . $x4 .' ' . $y4);

		if( $this->rescale < 2 && ($x3 > 10 || $y3 > 10))
		{

			$image = new ilScanAssessmentGDWrapper($this->file_helper->getScanTempPath() . 'new_file.jpg');
			$image->imageCropWithSource($image, $x3, $y3, $x4, $y4, $this->file_helper->getScanTempPath() . 'rescaled.jpg');
			return true;
		}

		return false;
	}

	/**
	 * @param ilScanAssessmentLog $log
	 * @return bool|ilScanAssessmentVector
	 */
	protected function detectQrCode($log)
	{
		$time_start = microtime(true);
		$qr			= new ilScanAssessmentQrCode($this->file_helper->getScanTempPath() . 'new_file.jpg');
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
    protected function prepareTIFF($path, $entry)
    {
        $log = ilScanAssessmentLog::getInstance();
        $org = $path . '/' . $entry;
        $pathinfo = pathinfo($org);

        $t0 = microtime(true);

        $dpi_limits = ilScanAssessmentGlobalSettings::getInstance()->getTiffDpiLimits();
        $lower_dpi_limit = 0;
        $upper_dpi_limit = PHP_INT_MAX;

        if(!empty($dpi_limits))
        {
            if(!empty($dpi_limits[0]))
            {
                $lower_dpi_limit = $dpi_limits[0];
            }
            if(!empty($dpi_limits[1]))
            {
                $upper_dpi_limit = $dpi_limits[1];
            }
        }

        $img = new Imagick(realpath($org));

        $n_images = $img->getNumberImages();
        $log->debug('Preparing TIFF ' . $org . ' with ' . $n_images . ' images');

        $target_file_type = 'png'; // we want something lossless here

        for($i = 0; $i < $n_images; $i++) {
            if(!$img->setImageIndex($i)) {
                return false;
            }

            $resolution = $img->getImageResolution();
            $dpi_x = $resolution['x'];
            $dpi_y = $resolution['y'];

            if($dpi_x < $lower_dpi_limit || $dpi_y < $lower_dpi_limit)
            {
                return false;
            }

            if($dpi_x > $upper_dpi_limit || $dpi_y > $upper_dpi_limit)
            {
                $dpi_x = min($dpi_x, $upper_dpi_limit);
                $dpi_y = min($dpi_y, $upper_dpi_limit);

                if(!$img->setResolution($dpi_x, $dpi_y))
                {
                    return false;
                }

                if(!$img->resampleImage($dpi_x, $dpi_y, Imagick::FILTER_BOX, 0))
                {
                    return false;
                }
            }

            $img_path = $path . '/' . $pathinfo['filename'] . '-' . $i . '.' . $target_file_type;
            $log->debug('Writing file: ' . $img_path . ' with dpi (' . $dpi_x . ', ' . $dpi_y . ')');
            if(!$img->writeImage($img_path))
            {
                return false;
            }
        }

        $img->destroy();

        $t1 = microtime(true);
        $log->debug('Converting TIFF took ' . ($t1 - $t0) . 's');

        $log->debug('Deleting TIFF: ' . $org);
        unlink($org);

        return true;
    }

	/**
	 * @param $path
	 * @param $entry
	 * @return bool
	 */
	protected function analyseImage($path, $entry)
	{
		$qr_code = false;
		$log = ilScanAssessmentLog::getInstance();
		$org = $path . '/' . $entry;
		$not_cropped = true;
		copy($org, $this->file_helper->getScanTempPath() . 'new_file.jpg');
		$log->debug('Start with file: ' . $org);

		$scanner = new ilScanAssessmentMarkerDetection($org);
		$marker = $this->detectMarker($scanner, $log);
		if($marker != false)
		{
			$rotate_file = $this->file_helper->getScanTempPath() . '/rotate_file.jpg';
			if(file_exists($rotate_file))
			{
				$log->debug('Rotated file found, using that for further processing.');
				copy($rotate_file, $this->file_helper->getScanTempPath() . 'new_file.jpg');
				unlink($rotate_file);
				$this->rescale = 0;
			}

			if($this->checkIfMustBeCropped($scanner, $log, $marker))
			{
				$log->debug('Image was scaled re-detecting marker positions.');
				if( $this->rescale < 2 )
				{
					$this->rescale++;
					$not_cropped = false;
					$this->analyseImage($this->file_helper->getScanTempPath() , 'rescaled.jpg');
				}
			}

			if($not_cropped)
			{
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
						return false;
					}
				}
				$scanner->drawTempImage($scanner->getTempImage(), $this->path_to_done . '/test_marker.jpg');
				$scan_answer_object = $this->detectAnswers($marker, $qr_pos, $log, $qr_code);
				$this->processAnswers($scan_answer_object, $qr_code, $scanner);
			}
			if($qr_code != false || !$not_cropped)
			{
				$done = $this->path_to_done . '/' . $entry;
				$log->debug('Moving file: ' . $org . ' to ' .$done );
				$this->file_helper->moveFile($org, $done);
				return true;
			}
			else
			{
				if($entry != 'rescaled.jpg')
				{
					$this->files_not_for_this_test[] = $entry;
				}
			}
		}
		else
		{
			$this->non_conform_files[] = $entry;
			return false;
		}
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
		$whole_path = $path . '/whole/';
		$this->file_helper->ensurePathExists($path);
		$this->file_helper->ensurePathExists($whole_path);
		$qid = 0;
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
			$checkbox = $scanner->image_helper->imageCrop($scanner->image_helper->getImage(), $value['vector']);

			if($qid != $value['qid'])
			{
				$answer_image = new ilScanAssessmentGDWrapper($this->path_to_done . '/answer_detection.jpg');
				$whole_answer = $scanner->image_helper->imageCropByPoints($answer_image->getImage(), $value['start'], $value['end']);
				$file_whole_path = $whole_path . $qr_code->getPageNumber() . '_' . $value['qid'] . '.jpg';
				$scanner->image_helper->drawTempImage($whole_answer, $file_whole_path);
				$qid = $value['qid'];
			}

			$file_path = $path . $qr_code->getPageNumber() . '_' . $value['qid'] . '_' . $value['aid'] . '_' . $pos . '_' . $value['marked']  .'.jpg';

			$scanner->image_helper->drawTempImage($checkbox, $file_path);
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
				$file = basename($org);
				if($file != 'rescaled.jpg')
				{
					$this->files_not_for_this_test[] = $file;
				}
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

    private function traverse($path, $callback)
    {
        $return_value = self::NOT_FOUND;

        if($handle = opendir($path))
        {
            while(false !== ($entry = readdir($handle)))
            {
                if(is_dir($path . '/' . $entry) === false)
                {
                    if($entry !== 'scan_assessment.lock')
                    {
                        if(call_user_func(array($this, $callback), $path, $entry)) {
                            $return_value = self::FOUND;
                        }
                    }
                }
            }
            closedir($handle);
        }

        return $return_value;
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

            try
            {
                if(ilScanAssessmentGlobalSettings::getInstance()->isTiffEnabled())
                {
                    $this->traverse($path, 'prepareTIFF');
                }

                $return_value = $this->traverse($path, 'analyseImage');
            }
            catch(Exception $e)
            {
                $this->releaseScanLock();
                throw $e;
            }

            $this->releaseScanLock();
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
                $this->log->info(sprintf('Removed lock file: %s' , $this->getScanLockFilePath()));
			}
			else
			{
                $this->log->debug(sprintf('Failed to remove lock: %s', $this->getScanLockFilePath()));
			}
		}
		else
        {
            $this->log->debug(sprintf('No lock to remove: %s', $this->getScanLockFilePath()));
        }
	}

	/**
	 * @return array
	 */
	public function getNonConformFiles()
	{
		return $this->non_conform_files;
	}

	/**
	 * @return array
	 */
	public function getFilesNotForThisTest()
	{
		return $this->files_not_for_this_test;
	}

}