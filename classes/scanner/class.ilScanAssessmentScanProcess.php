<?php
ilScanAssessmentPlugin::getInstance()->includeClass('assessment/class.ilScanAssessmentIdentification.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentMarkerDetection.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentQrCode.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentAnswerScanner.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentRevision.php');
ilScanAssessmentPlugin::getInstance()->includeClass('class.ilScanAssessmentGlobalSettings.php');
ilScanAssessmentPlugin::getInstance()->includeClass('../libs/php-qrcode-detector-decoder/lib/QrReader.php');
ilScanAssessmentPlugin::getInstance()->includeClass('class.ilScanAssessmentGlobalSettings.php');
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
	 * @var string
	 */
	protected $internal_file_type = '.png';

	/**
	 * @var int
	 */
	protected $rescale;

	/**
	 * ilScanAssessmentScanProcess constructor.
	 * @param ilScanAssessmentFileHelper $file_helper
	 * @param                            $test_obj_id
	 */
	public function __construct(ilScanAssessmentFileHelper $file_helper, $test_obj_id)
	{
		$this->file_helper		= $file_helper;
		$this->log				= ilScanAssessmentLog::getInstance();
		$this->test 			= ilObjectFactory::getInstanceByObjId((int) $test_obj_id);
		$this->rescale			= 0;
		$this->internal_file_type = ilScanAssessmentGlobalSettings::getInstance()->getInternFileType();
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
		$scaled = $image->imageCropWithSource($image, $image->getImageSizeX() / 100, $image->getImageSizeY() / 100, 0, 0, $this->file_helper->getScanTempPath() . '/new_file'  . $this->internal_file_type);
		$scanner->image_helper->setImage($scaled);
		$scanner->setImage($scaled);
		$marker = $scanner->getMarkerPosition($this->file_helper->getScanTempPath());
		return $marker;
	}

	/**
	 * @param ilScanAssessmentScanner $scanner
	 * @param ilScanAssessmentLog $log
	 * @param ilScanAssessmentVector[] $marker
	 * @param $qr_pos
	 * @return bool
	 */
	protected function checkIfMustBeCropped($scanner, $log, $marker, $qr_pos)
	{
		$corrected = $scanner->getCorrectedPosition();
		$x1        = $marker[0]->getPosition()->getX();
		$x2        = $marker[1]->getPosition()->getX();
		$y1        = $marker[0]->getPosition()->getY();
		$y2        = $marker[1]->getPosition()->getY();
		$marker_should_be_at_x = 10 * $corrected->getX();
		$marker_should_be_at_y = 10 * $corrected->getY();
		$image_x = $scanner->image_helper->getImageSizeX();
		$image_y = $scanner->image_helper->getImageSizeY();

		$log->debug('Corrected Position values [' . $corrected->getX() .' ,' . $corrected->getY() . ']');
		$log->debug('Top Marker should be at ' . $marker_should_be_at_x .' ' . $marker_should_be_at_y);
		$log->debug('Top Marker is at ' . $x1 .' ' . $y1);

		$marker_should_be_at_y_2 =  $scanner->image_helper->getImageSizeY() - $marker_should_be_at_y;
		$log->debug('Bottom Marker should be at ' . $marker_should_be_at_x . ' ' . $marker_should_be_at_y_2);
		$log->debug('Bottom Marker is at ' . $x2 .' ' . $y2);

		$x3 = $x1 - $marker_should_be_at_x;
		$y3 = $y1 - $marker_should_be_at_y;

		$x4 = $x2 - $marker_should_be_at_x;
		$y4 = $marker_should_be_at_y_2 - $y2;
		$log->debug('Crop would start at ' . $x3 .' ' . $y3. ' ' . $x4 .' ' . $y4);
		$max_cut_x = $qr_pos['crop']->getPosition()->getX() + $qr_pos['crop']->getLength();
		if($max_cut_x > $image_x - $x4)
		{
			$log->debug('Crop would destroy qr_code on the left reducing width.');
			$x4 = 0;
		}
		$max_cut_y = $qr_pos['crop']->getPosition()->getY() + $qr_pos['crop']->getLength();
		if($max_cut_y > $image_y - $y4)
		{
			$log->debug('Crop would destroy qr_code on the left reducing height.');
			$y4 = 0;
		}
		if( $this->rescale < 2 && ($x3 > 2 || $y3 > 2))
		{
			$image = new ilScanAssessmentGDWrapper($this->file_helper->getScanTempPath() . 'new_file' . $this->internal_file_type);
			$image->imageCropWithSource($image, $x3, $y3, $x4, $y4, $this->file_helper->getScanTempPath() . 'rescaled'  . $this->internal_file_type);
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
		$qr			= new ilScanAssessmentQrCode($this->file_helper->getScanTempPath() . 'new_file'  . $this->internal_file_type);
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
		$ans = new ilScanAssessmentAnswerScanner($this->file_helper->getScanTempPath() . 'new_file'  . $this->internal_file_type, $this->path_to_done, $qr_ident);
		$ans->scanImage($marker, $qr_pos);
		$time_end = microtime(true);
		$time     = $time_end - $time_start;
		$log->info('Answer Calculation duration:  ' . $time);

		return $ans;
	}

    /**
     * @param $path
     * @param $entry
     * @return bool
     */
    protected function prepareTIFF($path, $entry)
    {
        $org = $path . '/' . $entry;
        $pathinfo = pathinfo($org);

        if (!in_array(strtolower($pathinfo['extension']), array('tif', 'tiff'))) {
            return false;
        }

        $log = ilScanAssessmentLog::getInstance();

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

        $target_file_type = 'png';

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
	 * @throws Exception
	 */
	protected function cleanupFolder($path)
	{
		if(file_exists($path))
		{
			$log   = ilScanAssessmentLog::getInstance();
			$files = glob($path . '/*', GLOB_MARK);
			if(is_array($files))
			{
				foreach($files as $file)
				{
					if(!is_dir($file))
					{
						$log->info("deleting file " . $file);
						if(!unlink($file))
						{
							throw new \Exception("could not delete scan tmp dir file " . $file);
						}
					}
				}
			}
		}
	}

    /**
     * Ensure that we start in a defined state for each new image (i.e. nothing from the analysis state
     * of the old image is left over and affects the new analysis).
     *
     * @param $path
     * @param $entry
     * @return bool
     */
    protected function cleanupAndAnalyseImage($path, $entry)
    {
        $this->rescale = 0;

        $this->cleanupFolder($this->file_helper->getScanTempPath());
        $this->getAnalysingFolder('');
        $this->cleanupFolder($this->path_to_done);

        return $this->analyseImage($path, $entry);
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
		$this->getAnalysingFolder();

		$log->info('Starting with file: ' . $org);

		$scanner = new ilScanAssessmentMarkerDetection($org);
		$scanner->image_helper->drawTempImage($scanner->getImage(), $this->file_helper->getScanTempPath() . 'new_file'  . $this->internal_file_type);
		$marker = $this->detectMarker($scanner, $log);
		if($marker != false)
		{
			$rotate_file = $this->file_helper->getScanTempPath() . '/rotate_file'  . $this->internal_file_type;
			if(file_exists($rotate_file))
			{
				$log->debug('Rotated file found, using that for further processing.');
				$img = $scanner->image_helper->createNewImageInstanceFromFileName($rotate_file);
				$scanner->image_helper->drawTempImage($img, $this->file_helper->getScanTempPath() . 'new_file'  . $this->internal_file_type);

				unlink($rotate_file);
				$this->rescale = 0;
			}
			$qr_pos = $this->detectQrCode($log);
            if ($qr_pos === false) {
                $this->log->warn('No QR Code found!');
                return false;
            }
			if($this->checkIfMustBeCropped($scanner, $log, $marker, $qr_pos))
			{
				$log->debug('Image was scaled re-detecting marker positions.');
				if( $this->rescale < 2 )
				{
					$this->rescale++;
					$not_cropped = false;
					$this->analyseImage($this->file_helper->getScanTempPath() , 'rescaled'  . $this->internal_file_type);
				}
			}

			if($not_cropped)
			{
				if($qr_pos !== false)
				{
					$im2 = $scanner->image_helper->imageCrop($scanner->image_helper->getImage(), $qr_pos['crop']);
					if($im2 !== false)
					{
						$path = $this->file_helper->getScanTempPath() . 'qr' . $this->internal_file_type;
						$scanner->image_helper->drawTempImage($im2, $path);
						$qr_code = $this->processQrCode($path, $org);
						if(! $qr_code)
						{
							return false;
						}
					}
					else
					{
						$this->log->err('No QR Code found!');
						return false;
					}
				}
				$scanner->drawTempImage($scanner->getTempImage(), $this->path_to_done . '/test_marker' . $this->internal_file_type);
				$scan_answer_object = $this->detectAnswers($marker, $qr_pos, $log, $qr_code);
				$this->processAnswers($scan_answer_object, $qr_code, $scanner);
			}
			if($qr_code != false || !$not_cropped)
			{
				$done = $this->path_to_done . '/' . $entry;
				$log->info('Moving file: ' . $org . ' to ' .$done );
				$this->file_helper->moveFile($org, $done);
				$this->convertFilesAfterScanning($this->path_to_done);
				return true;
			}
			else
			{
				if($entry != 'rescaled' . $this->internal_file_type)
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
		return false;
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
		ilScanAssessmentRevision::removeOldQuestionData($qr_code, $path);
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
			
			if(array_key_exists('vector', $value))
			{
				$checkbox = $scanner->image_helper->imageCrop($scanner->image_helper->getImage(), $value['vector']);
			}
			else if(array_key_exists('start_point', $value) && array_key_exists('end_point', $value))
			{
				$pos = 'i';
				$checkbox = $scanner->image_helper->imageCropByPoints($scanner->image_helper->getImage(), $value['start_point'], $value['end_point']);
			}

			if($qid != $value['qid'])
			{
				$answer_image = new ilScanAssessmentGDWrapper($this->path_to_done . '/answer_detection' . $this->internal_file_type);
				$whole_answer = $scanner->image_helper->imageCropByPoints($answer_image->getImage(), $value['start'], $value['end']);
				$file_whole_path = $whole_path . $qr_code->getPageNumber() . '_' . $value['qid'] . $this->internal_file_type;
				$scanner->image_helper->drawTempImage($whole_answer, $file_whole_path);
				$qid = $value['qid'];
			}

			$file_path = $path . $qr_code->getPageNumber() . '_' . $value['qid'] . '_' . $value['aid'] . '_' . $pos . '_' . $value['marked']   . $this->internal_file_type;

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
				$file = basename($org);
				$this->log->warn(sprintf('This img %s does not belong to this test id %s', $file, $this->test->getId()));
				if($file != 'rescaled'  . $this->internal_file_type)
				{
					$this->validateImageReallyBelongsToThisTest($org, $identification);
				}
				return false;
			}
		}
		else
		{
			$this->log->err('QR Code could not be read!');
			return false;
		}

		$this->file_helper->moveFile($path, $this->path_to_done . '/qr' . $this->internal_file_type);
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
	 * @param $path_to_file
	 * @param ilScanAssessmentIdentification $identification
	 */
	protected function validateImageReallyBelongsToThisTest($path_to_file, $identification)
	{
		if(ilScanAssessmentGlobalSettings::getInstance()->getAutoMoveFiles() == 1)
		{
			$file = basename($path_to_file);
			if($identification->getTestId() != null)
			{
				$exists = ilObject2::_exists($identification->getTestId());
				$type = ilObject::_lookupType($identification->getTestId(), false);

				if($exists && $type == 'tst')
				{
					try
					{
						$test = new ilObjTest($identification->getTestId(), false);
						$this->log->warn(sprintf('Found test with this id (%s) with title (%s) on this platform, trying to move file to correct folder.', $identification->getTestId(), $test->getTitle()));
						$new_path = $this->file_helper->getScanPathByTestId($identification->getTestId());
						$this->file_helper->moveFile($path_to_file, $new_path . $file);
					}
					catch(ilObjectNotFoundException $e)
					{
						$this->log->warn(sprintf('No test with this id (%s) found on this platform.', $identification->getTestId()));
						$this->files_not_for_this_test[] = $file;
					}
				}
				else
				{
					if($exists)
					{
						$this->log->warn(sprintf('Found id (%s) is a object from type %s and not a test, skipping.', $identification->getTestId(), $type));
					}
					else
					{
						$this->log->warn(sprintf('No object with id (%s) found on this platform, skipping.', $identification->getTestId()));
					}
				}
			}
			else
			{
				$this->log->warn(sprintf('PDF with id (%s) is no longer valid.', $identification->getPdfId()));
				$this->files_not_for_this_test[] = $file;
			}
		}
		else
		{
			$this->log->info('Auto move is not activated, skipping check.');
		}
	}

	/**
	 * @param $path
	 * @param $callback
	 * @return int
	 */
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
						if(call_user_func(array($this, $callback), $path, $entry))
						{
							$return_value = self::FOUND;
						}
						else if(count($this->getFilesNotForThisTest()) > 0 || count($this->getNonConformFiles()) > 0)
						{
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
	 * @throws Exception
	 */
	public function analyse()
	{
		$path         = $this->file_helper->getScanPath();
		$return_value = self::NOT_FOUND;

		if($this->acquireScanLock())
		{
			$this->log->info(sprintf('Created lock file: %s', $this->getScanLockFilePath()));

			try
			{
				if(ilScanAssessmentGlobalSettings::getInstance()->isTiffEnabled())
				{
					$this->traverse($path, 'prepareTIFF');
				}

				$return_value = $this->traverse($path, 'cleanupAndAnalyseImage');
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
		$qr_code = new QrReader($path, 'file', false);
		$txt = $qr_code->text();
		if($txt != '')
		{
			$this->log->info(sprintf('Found id %s in qr code.', $txt));
			return $txt;
		}
		return false;
	}

	/**
	 * @param $path
	 */
	protected function convertFilesAfterScanning($path)
	{
		/** @var splfileinfo $filename */
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filename)
		{
			if(! is_dir($filename->getFilename()))
			{
				if(file_exists($filename) && $filename->getExtension() != ilScanAssessmentGlobalSettings::getInstance()->getSaveFileType() )
				{
					$img = new ilScanAssessmentGDWrapper($filename);
					$name = $filename->getPath() . '/' . $filename->getBasename('.' . $filename->getExtension());
					$img->drawTempImage($img->getImage(), $name . '.' . ilScanAssessmentGlobalSettings::getInstance()->getSaveFileType());
					unlink($filename);
				}
			}
		}
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
                $this->log->err(sprintf('Failed to remove lock: %s', $this->getScanLockFilePath()));
			}
		}
		else
        {
            $this->log->warn(sprintf('No lock to remove: %s', $this->getScanLockFilePath()));
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