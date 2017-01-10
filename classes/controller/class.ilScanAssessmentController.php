<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */
ilScanAssessmentPlugin::getInstance()->includeClass('log/class.ilScanAssessmentLog.php');
ilScanAssessmentPlugin::getInstance()->includeClass('class.ilScanAssessmentFileHelper.php');

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

	/**
	 * @var ilScanAssessmentLog
	 */
	protected $log;
	/**
	 * @var array
	 */
	protected $parameters = array();

	/**
	 * 
	 */
	protected $file_helper;

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
		$this->test 			= ilObjectFactory::getInstanceByRefId((int) $_GET['ref_id']);
		$this->file_helper		= new ilScanAssessmentFileHelper($this->test->getId());
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

	/**
	 * @param $txt
	 */
	protected function redirectAndInfo($txt)
	{
		ilUtil::sendInfo($txt, true);
		$this->redirect($this->getDefaultClassAndCommand());
	}

	/**
	 * @param $file_path
	 * @param $file_name
	 */
	protected function download($file_path, $file_name)
	{
		if(file_exists($file_path))
		{
			ilUtil::deliverFile($file_path, $file_name, '', 'I');
		}
		$this->redirect($this->getDefaultClassAndCommand());
	}

	/**
	 * @param      $txt
	 * @param null $class_and_command
	 */
	protected function redirectAndFailure($txt, $class_and_command = null)
	{
		if($class_and_command == null)
		{
			$class_and_command = $this->getDefaultClassAndCommand();
		}
		ilUtil::sendInfo($txt, true);
		#$this->redirect($class_and_command);
	}

	/**
	 * @param $class_and_command
	 */
	protected function redirect($class_and_command)
	{
		ilUtil::redirect($this->getCoreController()->getPluginObject()->getLinkTarget(
			$class_and_command,
			array(
				'ref_id' => (int)$_GET['ref_id']
			)
		));
	}

	/**
	 * @return string
	 */
	public function getDefaultClassAndCommand()
	{
		return 'ilScanAssessmentController.default';
	}
}