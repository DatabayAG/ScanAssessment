<?php

/**
 * Class ilScanAssessmentGlobalSettings
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentGlobalSettings
{
	/**
	 * @var self
	 */
	private static $instance;

	/**
	 * @var ilSetting
	 */
	protected $settings;

	/**
	 * @var string
	 */
	protected $institution;

	/**
	 * @var string
	 */
	protected $matriculation_style;

	/**
	 * @var boolean
	 */
	protected $disable_manual_scan;

	/**
	 * @var boolean
	 */
	protected $disable_manual_pdf;

	/**
	 * @var boolean
	 */
	protected $tiff_enabled;

	/**
	 * @var array
	 */
	protected $tiff_dpi_limits;

	/**
	 * @var boolean
	 */
	protected $enable_debug_export_tab;

	/**
	 * @var int
	 */
	protected $min_value_black = 150;

	/**
	 * @var float
	 */
	protected $min_marked_area = 0.35;

	/**
	 * @var float
	 */
	protected $marked_area_checked = 0.45;

	/**
	 * @var float
	 */
	protected $marked_area_unchecked = 0.90;

	/**
	 *
	 */
	private function __construct()
	{
		$this->settings = new ilSetting('scan_assessment_global');
		$this->read();
	}

	/**
	 * Get singleton instance
	 * @return self
	 */
	public static function getInstance()
	{
		if(null !== self::$instance)
		{
			return self::$instance;
		}

		return (self::$instance = new self());
	}

	/**
	 *
	 */
	protected function read()
	{
		$institution				= $this->settings->get('institution');
		$matriculation_format		= $this->settings->get('matriculation_format');
		$disable_manual_scan		= $this->settings->get('disable_manual_scan');
		$disable_manual_pdf			= $this->settings->get('disable_manual_pdf');
		$tiff_enabled				= $this->settings->get('tiff_enabled');
		$tiff_dpi_minimum			= $this->settings->get('tiff_dpi_minimum');
		$tiff_dpi_maximum			= $this->settings->get('tiff_dpi_maximum');
		$enable_debug_export_tab	= $this->settings->get('enable_debug_export_tab');
		$min_value_black			= $this->settings->get('min_value_black') ? $this->settings->get('min_value_black') : $this->min_value_black;
		$min_marked_area			= $this->settings->get('min_marked_area') ? $this->settings->get('min_marked_area') : $this->min_marked_area;
		$marked_area_checked		= $this->settings->get('marked_area_checked') ? $this->settings->get('marked_area_checked') : $this->marked_area_checked;
		$marked_area_unchecked		=$this->settings->get('marked_area_unchecked') ? $this->settings->get('marked_area_unchecked') : $this->marked_area_unchecked;

		if(strlen($institution))
		{
			$this->setInstitution($institution);
		}
		if(strlen($matriculation_format))
		{
			$this->setMatriculationStyle($matriculation_format);
		}
		$this->setDisableManualScan($disable_manual_scan);
		$this->setDisableManualPdf($disable_manual_pdf);
		$this->setTiffEnabled($tiff_enabled);
		$this->setTiffDpiLimits(array($tiff_dpi_minimum, $tiff_dpi_maximum));
		$this->setEnableDebugExportTab($enable_debug_export_tab);
		$this->setMinValueBlack($min_value_black);
		$this->setMinMarkedArea($min_marked_area);
		$this->setMarkedAreaChecked($marked_area_checked);
		$this->setMarkedAreaUnchecked($marked_area_unchecked);
	}

	/**
	 * @return ilSetting
	 */
	public function getSettings()
	{
		return $this->settings;
	}

	/**
	 * @param ilSetting $settings
	 */
	public function setSettings($settings)
	{
		$this->settings = $settings;
	}

	/**
	 *
	 */
	public function save()
	{
		$this->settings->set('institution',				$this->getInstitution());
		$this->settings->set('matriculation_format',	$this->getMatriculationStyle());
		$this->settings->set('disable_manual_scan',		$this->isDisableManualScan());
		$this->settings->set('disable_manual_pdf',		$this->isDisableManualPdf());
		$this->settings->set('tiff_enabled',			$this->isTiffEnabled());
		$dpi_limits = $this->getTiffDpiLimits();
		$this->settings->set('tiff_dpi_minimum', $dpi_limits[0]);
		$this->settings->set('tiff_dpi_maximum', $dpi_limits[1]);
		$this->settings->set('enable_debug_export_tab',	$this->isEnableDebugExportTab());
		$this->settings->set('min_value_black',			$this->getMinValueBlack());
		$this->settings->set('min_marked_area',			$this->getMinMarkedArea());
		$this->settings->set('marked_area_checked',		$this->getMarkedAreaChecked());
		$this->settings->set('marked_area_unchecked',	$this->getMarkedAreaUnchecked());
	}

	/**
	 * @return ilDB
	 */
	public function getDatabaseAdapter()
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		return $ilDB;
	}

	/**
	 * @return bool
	 */
	public function isConfigurationComplete()
	{
		return true;
	}

	/**
	 * @return string
	 */
	public function getInstitution()
	{
		return $this->institution;
	}

	/**
	 * @param string $institution
	 */
	public function setInstitution($institution)
	{
		$this->institution = $institution;
	}

	/**
	 * @return string
	 */
	public function getMatriculationStyle()
	{
		return $this->matriculation_style;
	}

	/**
	 * @param string $matriculation_style
	 */
	public function setMatriculationStyle($matriculation_style)
	{
		$this->matriculation_style = $matriculation_style;
	}

	/**
	 * @return boolean
	 */
	public function isDisableManualScan()
	{
		return $this->disable_manual_scan;
	}

	/**
	 * @param boolean $disable_manual_scan
	 */
	public function setDisableManualScan($disable_manual_scan)
	{
		$this->disable_manual_scan = $disable_manual_scan;
	}

	/**
	 * @return bool
	 */
	public function isDisableManualPdf()
	{
		return $this->disable_manual_pdf;
	}

	/**
	 * @param bool $disable_manual_pdf
	 */
	public function setDisableManualPdf($disable_manual_pdf)
	{
		$this->disable_manual_pdf = $disable_manual_pdf;
	}

	/**
	 * @return boolean
	 */
	public function isTiffEnabled()
	{
		return $this->tiff_enabled;
	}

	/**
	 * @param boolean $tiff_enabled
	 */
	public function setTiffEnabled($tiff_enabled)
	{
		$this->tiff_enabled = $tiff_enabled;
	}

	/**
	 * @return array
	 */
	public function getTiffDpiLimits()
	{
		return $this->tiff_dpi_limits;
	}

	/**
	 * @param array $tiff_dpi_limits
	 */
	public function setTiffDPILimits($tiff_dpi_limits)
	{
		$this->tiff_dpi_limits = $tiff_dpi_limits;
	}

	/**
	 * @return bool
	 */
	public function isEnableDebugExportTab()
	{
		return $this->enable_debug_export_tab;
	}

	/**
	 * @param bool $enable_debug_export_tab
	 */
	public function setEnableDebugExportTab($enable_debug_export_tab)
	{
		$this->enable_debug_export_tab = $enable_debug_export_tab;
	}

	/**
	 * @return int
	 */
	public function getMinValueBlack()
	{
		return $this->min_value_black;
	}

	/**
	 * @param int $min_value_black
	 */
	public function setMinValueBlack($min_value_black)
	{
		$this->min_value_black = $min_value_black;
	}

	/**
	 * @return float
	 */
	public function getMinMarkedArea()
	{
		return $this->min_marked_area;
	}

	/**
	 * @param float $min_marked_area
	 */
	public function setMinMarkedArea($min_marked_area)
	{
		$this->min_marked_area = $min_marked_area;
	}

	/**
	 * @return float
	 */
	public function getMarkedAreaChecked()
	{
		return $this->marked_area_checked;
	}

	/**
	 * @param float $marked_area_checked
	 */
	public function setMarkedAreaChecked($marked_area_checked)
	{
		$this->marked_area_checked = $marked_area_checked;
	}

	/**
	 * @return float
	 */
	public function getMarkedAreaUnchecked()
	{
		return $this->marked_area_unchecked;
	}

	/**
	 * @param float $marked_area_unchecked
	 */
	public function setMarkedAreaUnchecked($marked_area_unchecked)
	{
		$this->marked_area_unchecked = $marked_area_unchecked;
	}
}