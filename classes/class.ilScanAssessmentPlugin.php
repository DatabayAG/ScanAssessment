<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php';

/**
 * Class ilScanAssessmentPlugin
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentPlugin extends ilUserInterfaceHookPlugin
{
	/**
	 * @var string
	 */
	const CTYPE = 'Services';

	/**
	 * @var string
	 */
	const CNAME = 'UIComponent';

	/**
	 * @var string
	 */
	const SLOT_ID = 'uihk';

	/**
	 * @var string
	 */
	const PNAME = 'ScanAssessment';

	/**
	 * @var string
	 */
	const PLUGIN_CMD_DETECTION_PARAMETER = 'isSaCmd';

	/**+
	 * @var self
	 */
	protected static $instance = null;

	/**
	 * @return string
	 */
	public function getPluginName()
	{
		return self::PNAME;
	}

	/**
	 * @return self
	 */
	public static function getInstance()
	{
		if(self::$instance instanceof self)
		{
			return self::$instance;
		}

		self::$instance = ilPluginAdmin::getPluginObject(
			self::CTYPE,
			self::CNAME,
			self::SLOT_ID,
			self::PNAME
		);

		return self::$instance;
	}

	/**
	 * @return bool
	 */
	public function hasAccess()
	{
		/** @var $ilAccess ilAccessHandler */
		global $ilAccess;

		if(!isset($_GET['ref_id']) || !is_numeric($_GET['ref_id']))
		{
			return false;
		}

		if('tst' != ilObject::_lookupType(ilObject::_lookupObjId((int)$_GET['ref_id'])))
		{
			return false;
		}

		return $ilAccess->checkAccess('write', '', (int)$_GET['ref_id']);
	}

	/**
	 * @return bool
	 */
	public function checkIfScanAssessmentCronExists()
	{
		$cron_plugin_path = 'Customizing/global/plugins/Services/Cron/CronHook/ScanAssessmentCron/classes/class.ilScanAssessmentCronPlugin.php';
		if(file_exists($cron_plugin_path))
		{
			/** @noinspection PhpIncludeInspection */
			require_once $cron_plugin_path;
			$cron_plugin = new ilScanAssessmentCronPlugin();
			if($cron_plugin->isActive())
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function isPluginRequest()
	{
		return isset($_GET[self::PLUGIN_CMD_DETECTION_PARAMETER]) && strlen($_GET[self::PLUGIN_CMD_DETECTION_PARAMETER]);
	}

	/**
	 * @param string $cmd
	 * @param array  $parameters
	 * @param bool   $prevent_xhtml_style
	 * @return string
	 */
	public function getLinkTarget($cmd, $parameters = array(), $prevent_xhtml_style = false)
	{
		/** @var $ilCtrl ilCtrl */
		global $ilCtrl;
		
		foreach($parameters as $key => $val)
		{
			$ilCtrl->setParameterByClass('ilScanAssessmentUIHookGUI', $key, $val);
		}
		$ilCtrl->setParameterByClass('ilScanAssessmentUIHookGUI', self::PLUGIN_CMD_DETECTION_PARAMETER, 1);

		$url = $ilCtrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilScanAssessmentUIHookGUI'), $cmd, '', false, $prevent_xhtml_style);

		foreach($parameters as $key => $val)
		{
			$ilCtrl->setParameterByClass('ilScanAssessmentUIHookGUI', $key, '');
		}
		$ilCtrl->setParameterByClass('ilScanAssessmentUIHookGUI', self::PLUGIN_CMD_DETECTION_PARAMETER, '');

		return $url;
	}

	/**
	 * @param string $cmd
	 * @param array  $parameters
	 * @return string
	 */
	public function getFormAction($cmd, $parameters = array())
	{
		/** @var $ilCtrl ilCtrl */
		global $ilCtrl;

		return $this->getLinkTarget('post', array_merge($parameters, array('fallbackCmd' => $cmd, ilCtrl::IL_RTOKEN_NAME => $ilCtrl->getRequestToken())));
	}

	/**
	 * @return bool
	 */
	public function isAjaxRequest()
	{
		return (
			isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
			strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
		) || (
			isset($_GET['cmdMode']) &&
			$_GET['cmdMode'] == 'asynch'
		);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function afterUninstall()
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;


		parent::afterUninstall();
	}
}