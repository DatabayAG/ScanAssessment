<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentScanGUI.php');

/**
 * Class ilScanAssessmentUserPackagesPdfGUI
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentScanRevisionGUI extends ilScanAssessmentScanGUI
{

	/**
	 * @param ilPropertyFormGUI|null $form
	 * @return string
	 */
	public function defaultCmd(ilPropertyFormGUI $form = null)
	{

		if(!($form instanceof ilPropertyFormGUI))
		{
			$form = $this->getForm();
		}

		/** @var ilTemplate $tpl */
		$tpl = $this->getCoreController()->getPluginObject()->getTemplate('tpl.test_configuration.html', true, true);
		$tpl->setVariable('FORM', $form->getHTML());

		$sidebar = $this->renderSteps();
		$tpl->setVariable('STATUS', $sidebar);

		$tpl->setVariable('CONTENT', '');//$tbl->getHTML());

		return $tpl->get();
	}

	/**
	 * @return ilPropertyFormGUI
	 */
	protected function getForm()
	{
		$pluginObject = $this->getCoreController()->getPluginObject();
		$this->addTabs();

		require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';
		$form = new ilPropertyFormGUI();

		return $form;
	}

	/**
	 * @param string $active_sub
	 */
	protected function addTabs($active_sub = 'scan_scanner')
	{
		parent::addTabs();
		$this->tabs->setSubTabActive('scan_revision');
	}

	/**
	 * @return string
	 */
	public function getDefaultClassAndCommand()
	{
		return 'ilScanAssessmentScanRevisionGUI.default';
	}
}