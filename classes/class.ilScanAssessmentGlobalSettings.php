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
	 *
	 */
	private function __construct()
	{
		$this->settings = new ilSetting('scan_assessment_global');
		$this->read();
	}

	/**
	 * Get singleton instance
	 *
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
		$institution = $this->settings->get('institution');
		$matriculation_format = $this->settings->get('matriculation_format');
		$disable_manual_scan = $this->settings->get('disable_manual_scan');

		if(strlen($institution))
		{
			$this->setInstitution($institution);
		}
		if(strlen($matriculation_format))
		{
			$this->setMatriculationStyle($matriculation_format);
		}
		$this->setDisableManualScan($disable_manual_scan);
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
		$this->settings->set('institution', $this->getInstitution());
		$this->settings->set('matriculation_format', $this->getMatriculationStyle());
		$this->settings->set('disable_manual_scan', $this->isDisableManualScan());
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
	 * @return mixed
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


}