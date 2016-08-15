<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Component/classes/class.ilPluginConfigGUI.php';

/**
 * Class ilScanAssessmentConfigGUI
 * @author Michael Jansen <mjansen@databay.de>
 */
class ilScanAssessmentConfigGUI extends ilPluginConfigGUI
{
	/**
	 * @var ilTemplate
	 */
	protected $tpl;

	/**
	 * @var ilLanguage
	 */
	protected $lng;

	/**
	 * @var ilCtrl
	 */
	protected $ctrl;

	/**
	 * @var ilToolbarGUI
	 */
	protected $toolbar;

	/**
	 * @var ilDB
	 */
	protected $db;

	/**
	 * @var ilObjUser
	 */
	protected $user;

	/**
	 *
	 */
	public function __construct()
	{
		/**
		 * @var ilTemplate   $tpl
		 * @var ilLanguage   $lng
		 * @var ilCtrl       $ilCtrl
		 * @var ilToolbarGUI $ilToolbar
		 * @var ilDB         $ilDB
		 * @var ilObjUser    $ilUser
		 */
		global $lng, $tpl, $ilCtrl, $ilToolbar, $ilDB, $ilUser;

		$this->lng     = $lng;
		$this->tpl     = $tpl;
		$this->ctrl    = $ilCtrl;
		$this->toolbar = $ilToolbar;
		$this->db      = $ilDB;
		$this->user    = $ilUser;
	}

	/**
	 * {@inheritdoc}
	 */
	public function performCommand($cmd)
	{
		switch($cmd)
		{
			case 'cancel':
				$this->listCategories();
				break;

			default:
				$this->$cmd();
				break;
		}
	}

	/**
	 *
	 */
	protected function configure()
	{
		$this->listCategories();
	}
	
	/**
	 *
	 */
	protected function cancel()
	{
		$this->listCategories();
	}

	/**
	 *
	 */
	protected function listCategories()
	{
		$button = ilLinkButton::getInstance();
		$button->setCaption(ilScanAssessmentPlugin::getInstance()->txt('scas_cat_add_new'), false);
		$button->setUrl($this->ctrl->getLinkTarget($this, 'add'));
		$this->toolbar->addButtonInstance($button);
		
	}

	/**
	 * 
	 */
	protected function add()
	{

	}

	/**
	 *
	 */
	protected function edit()
	{

	}

	/**
	 * 
	 */
	protected function create()
	{
		
			ilUtil::sendSuccess($this->lng->txt('saved_successfully'), true);
			$this->ctrl->redirect($this);
		
	}

	/**
	 * 
	 */
	protected function update()
	{
		
			ilUtil::sendSuccess($this->lng->txt('saved_successfully'), true);
			$this->ctrl->redirect($this);
		
	}

	/**
	 * 
	 */
	protected function delete()
	{
		
	}

	/**
	 * 
	 */
	protected function confirmDelete()
	{
		
	}
}