<?php

/**
 * Class ilScanAssessmentFileHelper
 */
class ilScanAssessmentFileHelper
{

	const LAYOUT			= '/layout/';
	const PDF				= '/pdf/';
	const SCANS				= '/scans/';
	const SCAN_ASSESSMENT	= '/scanAssessment/';
	const ANALYSED			= 'analysed/';
	const ZIP				= 'zip/';

	/**
	 * @var int
	 */
	protected $test_id;

	/**
	 * ilScanAssessmentFileHelper constructor.
	 * @param $test_id
	 */
	public function __construct($test_id)
	{
		$this->test_id = $test_id;
	}

	/**
	 * @return string
	 */
	public function getLayoutPath()
	{
		$path = $this->getBasePath() . self::LAYOUT;
		$this->ensurePathExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	public function getPdfPath()
	{
		$path = $this->getBasePath() . self::PDF;
		$this->ensurePathExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	public function getScanPath()
	{
		$path = $this->getBasePath() . self::SCANS;
		$this->ensurePathExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	public function getAnalysedPath()
	{
		$path = $this->getScanPath() . self::ANALYSED;
		$this->ensurePathExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	public function getPdfZipPath()
	{
		$path = $this->getPdfPath() . self::ZIP;
		$this->ensurePathExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	protected function getBasePath()
	{
		$path = ilUtil::getDataDir() . self::SCAN_ASSESSMENT . 'tst_' . $this->test_id;
		$this->ensurePathExists($path);
		return $path;
	}

	/**
	 * @param $path
	 */
	public function ensurePathExists($path)
	{
		if( ! is_dir($path))
		{
			ilUtil::makeDirParents($path);
		}
	}

	/**
	 * @param $path
	 * @param array $files
	 * @param $name
	 */
	public function createZipAndDeliverFromFiles($path, $files, $name)
	{
		$zip      = new ZipArchive;
		$zip_file = $path . $name;

		if(is_array($files))
		{
			$zip->open($zip_file, ZipArchive::CREATE);
			foreach($files as $file)
			{
				if(file_exists($file))
				{
					$zip->addFile($file, basename($file));
				}
			}
			$zip->close();
		}

		if(file_exists($zip_file))
		{
			ilUtil::deliverFile($zip_file, $name, 'zip', true, true);
		}
	}

	/**
	 * @param $path
	 * @return bool
	 */
	public function doFilesExistsInDirectory($path)
	{
		if($handle = opendir($path))
		{
			while(false !== ($entry = readdir($handle)))
			{
				if(is_file($path . $entry))
				{
					return true;
				}
			}
			closedir($handle);
		}
		return false;
	}

	/**
	 * @param $path
	 * @return array
	 */
	public function getFilesFromFolderRecursive($path)
	{
		$files	= array();
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filename)
		{
			if(! is_dir($filename->getFilename()))
			{
				$size = (int) ($filename->getSize() / 1024);
				$date = date('d. F Y H:i:s', $filename->getMtime());
				$files[] = array('file_id' => $filename->getPathName(), 
								 'splfileinfo' => $filename, 
								 'file_name' => basename($filename->getPath()) . '/' . $filename->getBaseName(), 
								 'file_size' => $size . 'K', 
								 'file_date' => $date);
			}
		}
		return $files;
	}
}