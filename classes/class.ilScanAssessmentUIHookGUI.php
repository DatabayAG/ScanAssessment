<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/UIComponent/classes/class.ilUIHookPluginGUI.php';
require_once 'Services/Link/classes/class.ilLink.php';

/**
 * Class ilScanAssessmentUIHookGUI
 * @author Guido Vollbach <gvollbach@databay.de>
 * @ilCtrl_isCalledBy ilScanAssessmentUIHookGUI: ilUIPluginRouterGUI
 */
class ilScanAssessmentUIHookGUI extends ilUIHookPluginGUI
{
	/**
	 * @param       $a_comp
	 * @param       $a_part
	 * @param array $a_par
	 * @throws ilException
	 */
	public function modifyGUI($a_comp, $a_part, $a_par = array())
	{
		/**
		 * @var $lng    ilLanguage
		 * @var $ilCtrl ilCtrl
		 * @var $ilTabs ilTabsGUI 
		 */
		global $lng, $ilCtrl, $ilTabs;

		if($a_part == 'tabs')
		{
			if($this->plugin_object->hasAccess())
			{
				if(in_array(strtolower($ilCtrl->getCmdClass()), array(
					'iltestplayerrandomquestionsetgui',
					'iltestplayerfixedquestionsetgui',
					'iltestplayerdynamicquestionsetgui'
				)))
				{
					return;
				}
				
				/** @var $tabs ilTabsGUI */
				$tabs = $a_par['tabs'];
				if(!$this->plugin_object->isPluginRequest())
				{
					$tabs->addNonTabbedLink(
						'scan_assessment',
						$this->plugin_object->txt('scan_assessment'),
						$this->plugin_object->getLinkTarget(
							'ilScanAssessmentDefaultController.default',
							array(
								'ref_id' => (int)$_GET['ref_id']
							)
						)
					);
				}
				else
				{
					$ilTabs->setBackTarget(
						$lng->txt('back'),
						ilLink::_getLink((int)$_GET['ref_id'], 'tst')
					);



					$ilTabs->addTab('settings', $this->plugin_object->txt('scas_settings'), $this->plugin_object->getLinkTarget(
						'ilScanAssessmentDefaultController.default',
						array(
							'ref_id' => (int)$_GET['ref_id']
						)
					));

					$ilTabs->addTab('layout', $this->plugin_object->txt('scas_layout'), $this->plugin_object->getLinkTarget(
						'ilScanAssessmentLayoutController.default',
						array(
							'ref_id' => (int)$_GET['ref_id']
						)
					));

					$ilTabs->addTab('user_packages', $this->plugin_object->txt('scas_user_packages'), $this->plugin_object->getLinkTarget(
						'ilScanAssessmentUserPackagesController.default',
						array(
							'ref_id' => (int)$_GET['ref_id']
						)
					));

					$ilTabs->addTab('scan', $this->plugin_object->txt('scas_scan'), '');

					$ilTabs->addTab('return', $this->plugin_object->txt('scas_return'), '');
				}
			}
		}
	}

	/**
	 *
	 */
	public function executeCommand()
	{
		/**
		 * @var $tpl    ilTemplate
		 * @var $ilCtrl ilCtrl
		 * @var $ilias  ILIAS
		 * @var $lng    ilLanguage
		 * @var $ilLocator      ilLocatorGUI
		 */
		global $tpl, $ilCtrl, $lng, $ilias, $ilLocator;

		if(!ilScanAssessmentPlugin::getInstance()->isPluginRequest())
		{
			return;
		}

		if(!ilScanAssessmentPlugin::getInstance()->hasAccess())
		{
			$ilias->raiseError($lng->txt('permission_denied'), $ilias->error_obj->MESSAGE);
		}

		$tpl->getStandardTemplate();

		$ilLocator->addRepositoryItems((int)$_REQUEST['ref_id']);
		$tpl->setLocator();

		$this->setPluginObject(ilScanAssessmentPlugin::getInstance());

		require_once 'Modules/Test/classes/class.ilObjTestGUI.php';
		$test_gui = new ilObjTestGUI();

		$reflectionMethod = new ReflectionMethod('ilObjTestGUI', 'setTitleAndDescription');
		$reflectionMethod->setAccessible(true);
		$reflectionMethod->invoke($test_gui);

		$ilCtrl->saveParameter($this, 'ref_id');
		$next_class = $ilCtrl->getNextClass();
		switch(strtolower($next_class))
		{
			default:
				ilScanAssessmentPlugin::getInstance()->includeClass('dispatcher/class.ilScanAssessmentCommandDispatcher.php');
				$dispatcher = ilScanAssessmentCommandDispatcher::getInstance($this);
				$response = $dispatcher->dispatch($ilCtrl->getCmd());
				break;
		}

		if(ilScanAssessmentPlugin::getInstance()->isAjaxRequest())
		{
			echo $response;
			exit();
		}

		$tpl->setContent($response);
		$tpl->show();
	}
}