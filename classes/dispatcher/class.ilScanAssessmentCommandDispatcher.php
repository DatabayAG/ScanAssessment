<?php
/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilScanAssessmentCommandDispatcher
 * @author Michael Jansen <mjansen@databay.de>
 */
class ilScanAssessmentCommandDispatcher
{
	/**
	 * @var self
	 */
	private static $instance = null;

	/**
	 * @var ilUIHookPluginGUI
	 */
	protected $core_controller;

	/**
	 *
	 */
	private function __clone(){}

	/**
	 * @param ilScanAssessmentUIHookGUI $baseController
	 */
	private function __construct(ilScanAssessmentUIHookGUI $baseController)
	{
		$this->core_controller = $baseController;
	}

	/**
	 * @param  ilScanAssessmentUIHookGUI $base_controller
	 * @return self
	 */
	public static function getInstance(ilScanAssessmentUIHookGUI $base_controller)
	{
		if(self::$instance === null)
		{
			self::$instance = new self($base_controller);
		}
		return self::$instance;
	}

	/**
	 * @param string $cmd
	 * @return string
	 */
	public function dispatch($cmd)
	{
		$controller = $this->getController($cmd);
		$command    = $this->getCommand($cmd);
		$controller = $this->instantiateController($controller);
		return $controller->$command();
	}

	/**
	 * @param string $cmd
	 * @return string
	 */
	protected function getController($cmd)
	{
		$parts = explode('.', $cmd);

		$controller = $parts[0];
		return $controller;
	}

	/**
	 * @param string $cmd
	 * @return string
	 */
	protected function getCommand($cmd)
	{
		$parts = explode('.', $cmd);

		$cmd = $parts[1];

		return $cmd . 'Cmd';
	}

	/**
	 * @param string $controller
	 * @return mixed
	 */
	protected function instantiateController($controller)
	{
		$this->requireController($controller);
		return new $controller($this->getCoreController());
	}

	/**
	 * @return string
	 */
	protected function getControllerPath()
	{
		$path = $this->getCoreController()->getPluginObject()->getDirectory() .
			DIRECTORY_SEPARATOR .
			'classes' .
			DIRECTORY_SEPARATOR .
			'controller' .
			DIRECTORY_SEPARATOR;

		return $path;
	}

	/**
	 * @param string $controller
	 */
	protected function requireController($controller)
	{
		require_once $this->getControllerPath() . "class.$controller.php";
	}

	/**
	 * @return ilScanAssessmentUIHookGUI
	 */
	public function getCoreController()
	{
		return $this->core_controller;
	}

	/**
	 * @param ilScanAssessmentUIHookGUI $core_controller
	 */
	public function setCoreController(ilScanAssessmentUIHookGUI $core_controller)
	{
		$this->core_controller = $core_controller;
	}
}