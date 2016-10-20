<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/log/class.ilScanAssessmentLog.php';

/**
 * Class ilScanAssessmentController
 * @author Guido Vollbach <gvollbach@databay.de>
 */
abstract class ilScanAssessmentController
{
	/**
	 * The main controller of the Plugin
	 * @var ilScanAssessmentUIHookGUI
	 */
	public $core_controller;

	/***
	 * @var ilCtrl
	 */
	protected $ctrl;

	/**
	 * @var ilTemplate
	 */
	protected $tpl;

	/**
	 * @var ilLanguage
	 */
	protected $lng;

	/**
	 * @var ilTabsGUI
	 */
	protected $tabs;

	/**
	 * @var ilToolbarGUI
	 */
	protected $toolbar;

	/**
	 * @var ilSetting
	 */
	protected $settings;

	/**
	 * @var ilObjuser
	 */
	protected $user;

	/**
	 * @var ilDB
	 */
	protected $db;

	protected $log;
	/**
	 * @var array
	 */
	protected $parameters = array();

	/**
	 * @param ilScanAssessmentUIHookGUI $controller
	 */
	final public function __construct(ilScanAssessmentUIHookGUI $controller)
	{
		global $ilCtrl, $tpl, $lng, $ilTabs, $ilToolbar, $ilSetting, $ilUser, $ilDB;

		$this->ctrl				= $ilCtrl;
		$this->tpl				= $tpl;
		$this->lng				= $lng;
		$this->tabs				= $ilTabs;
		$this->toolbar			= $ilToolbar;
		$this->settings			= $ilSetting;
		$this->user				= $ilUser;
		$this->db				= $ilDB;
		$this->log				= ilScanAssessmentLog::getInstance();
		$this->core_controller	= $controller;

		$this->init();
	}

	/**
	 * 
	 */
	protected function init()
	{
	}

	/**
	 * @return ilScanAssessmentUIHookGUI
	 */
	public function getCoreController()
	{
		return $this->core_controller;
	}
}