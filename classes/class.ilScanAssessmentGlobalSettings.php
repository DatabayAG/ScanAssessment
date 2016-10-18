<?php

/**
 * Class ilScanAssessmentGlobalSettings
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
	 * @var
	 */
	protected $institution;

	/**
	 * @var
	 */
	protected $matriculation_style;

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

		if(strlen($institution))
		{
			$this->setInstitution($institution);
		}
		if(strlen($matriculation_format))
		{
			$this->setMatriculationStyle($matriculation_format);
		}
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
	 * @param mixed $institution
	 */
	public function setInstitution($institution)
	{
		$this->institution = $institution;
	}

	/**
	 * @return mixed
	 */
	public function getMatriculationStyle()
	{
		return $this->matriculation_style;
	}

	/**
	 * @param mixed $matriculation_style
	 */
	public function setMatriculationStyle($matriculation_style)
	{
		$this->matriculation_style = $matriculation_style;
	}


}