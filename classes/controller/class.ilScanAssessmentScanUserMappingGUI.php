<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentScanGUI.php');
ilScanAssessmentPlugin::getInstance()->includeClass('model/class.ilScanAssessmentUserPackagesConfiguration.php');
require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';
require_once 'Services/User/classes/class.ilUserAutoComplete.php';
require_once 'Services/Accordion/classes/class.ilAccordionGUI.php';

/**
 * Class ilScanAssessmentScanUserMappingGUI
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentScanUserMappingGUI extends ilScanAssessmentScanGUI
{

	/**
	 * @param ilPropertyFormGUI|null $form
	 * @return string
	 */
	public function defaultCmd(ilPropertyFormGUI $form = null)
	{
		$pluginObject = $this->getCoreController()->getPluginObject();
		/** @var ilTemplate $tpl */
		$tpl = $pluginObject->getTemplate('tpl.test_configuration.html', true, true);
		if(!($form instanceof ilPropertyFormGUI))
		{
			$form  = $this->getForm();
		}
		$sidebar = $this->renderSteps();
		$tpl->setVariable('STATUS', $sidebar);
		$tpl->setVariable('FORM', $form->getHTML());
		
		$this->addTabs();

		return $tpl->get();
	}

	/**
	 * 
	 */
	public function saveFormCmd()
	{
		$form = $this->getForm();
		if($form->checkInput())
		{
			try
			{
				$this->saveMappingsFromPost();
				ilUtil::sendSuccess($this->lng->txt('saved_successfully'));
			}
			catch(ilException $e)
			{
				ilUtil::sendFailure($this->getCoreController()->getPluginObject()->txt($e->getMessage()));
			}
		}
		$form->setValuesByPost();
		return $this->defaultCmd();
	}

	/**
	 * @param bool $show_analyse
	 * @return ilPropertyFormGUI
	 */
	protected function getForm($show_analyse = false)
	{
		$pluginObject = $this->getCoreController()->getPluginObject();
		
		$package_config = new ilScanAssessmentUserPackagesConfiguration($this->test->getId());

		$matriculation_activated = $package_config->isMatriculationCode();
		
		$form = new ilPropertyFormGUI();
		$form->setFormAction($pluginObject->getFormAction(__CLASS__ . '.saveForm', array('ref_id' => (int)$_GET['ref_id'])));
		$mapping = $this->getMappings($this->test->getId());

		foreach($mapping as $pdf_id => $values)
		{
			$dsDataLink = $this->getCoreController()->getPluginObject()->getLinkTarget('ilScanAssessmentScanUserMappingGUI.doUserAutoComplete',	array('ref_id' => (int)$_GET['ref_id'], true));
			$user = new ilTextInputGUI($pluginObject->txt('scas_user') . ' ' . $pdf_id, 'user['.$pdf_id.']');
			$user->setDataSource($dsDataLink);
			$user->setMaxLength(null);
			$user->setMulti(false);
			$user->setInfo($pluginObject->txt('scas_user_info') );
			$user->setValue(ilObjUser::_lookupLogin($values['usr_id']));
			if((int) $values['revision'] == 0)
			{
				$user->setDisabled(true);
				$user->setInfo($pluginObject->txt('scas_user_revision_info'));
			}
			if($values['double'])
			{
				$user->setAlert($pluginObject->txt('scas_user_already_assigned'));
			}
			$form->addItem($user);
			$this->addAccordionWithDetails($form, $pdf_id,  $values['matriculation'], $matriculation_activated);

		}
		$form->addCommandButton(__CLASS__ . '.saveForm', $this->lng->txt('save'));

		return $form;
	}

	/**
	 * @param string $active_sub
	 */
	protected function addTabs($active_sub = 'scan_scanner')
	{
		parent::addTabs();
		$this->tabs->setSubTabActive('scan_user_mapping');
	}

	/**
	 * @return string
	 */
	public function getDefaultClassAndCommand()
	{
		return 'ilScanAssessmentScanUserMappingGUI.default';
	}

	protected function addAccordionWithDetails($form, $pdf_id, $detected_matriculation, $matriculation_activated)
	{
		$pluginObject = $this->getCoreController()->getPluginObject();

		$accordion_identification = new ilAccordionGUI();
		if($matriculation_activated == 1)
		{
			if($detected_matriculation != null && $detected_matriculation != 0)
			{
				$acor_title = $pluginObject->txt('scas_first_page') . ' (' . $pluginObject->txt('scas_matriculation_detected') . ': ' . $detected_matriculation . ')';
			}
			else
			{
				$acor_title = $pluginObject->txt('scas_first_page');
			}
		}
		else
		{
			$acor_title = $pluginObject->txt('scas_first_page');
		}

		$file = $this->file_helper->getRevisionPath() . '/qpl/' . $pdf_id . '/head/header' . ilScanAssessmentGlobalSettings::getInstance()->getInternFileType();
		/** @var ilTemplate $template */
		$template = $pluginObject->getTemplate('default/tpl.revision_header.html', true, true);
		
		if(file_exists($file))
		{
			$template->setVariable('IMAGE', $file);
		}
		else
		{
			$template->setVariable('NOT_FOUND', $pluginObject->txt('scas_not_found'));
		}

		$accordion_identification->addItem( $acor_title , $template->get());
		
		$custom_todo = new ilCustomInputGUI('', '');
		$custom_todo->setHTML($accordion_identification->getHTML());
		$form->addItem($custom_todo);
	}

	const pdf_data_table = 'pl_scas_pdf_data';
	/**
	 * @param $test_id
	 * @return array
	 */
	public static function getMappings($test_id)
	{
		$already_assigned = array();
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		$res = $ilDB->queryF(
			'SELECT pdf_id, revision_done, usr_id, matriculation_number
			FROM '.self::pdf_data_table.'
			WHERE obj_id = %s',
			array('integer'),
			array((int) $test_id)
		);

		$mappings = array();
		while($row = $ilDB->fetchAssoc($res))
		{
			$usr_id	= (int) $row['usr_id'];
			$state	= true;
			if(! array_key_exists($usr_id, $already_assigned))
			{
				if($usr_id != 0)
				{
					$already_assigned[$usr_id] = true;
				}
				$state	= false;
			}
			$mappings[$row['pdf_id']] = array('revision' => $row['revision_done'], 'usr_id' => $usr_id, 'double' => $state, 'matriculation' => $row['matriculation_number']);
		}
		ksort($mappings);
		return $mappings;
	}

	public function saveMappingsFromPost()
	{
		/**
		 * @var $ilDB ilDB
		 * @var $ilUser ilObjUser
		 */
		global $ilDB, $ilUser;
		$user_mapping = ilUtil::stripSlashesRecursive($_POST['user']);
		foreach($user_mapping as $pdf_id => $username)
		{
			$usr_id = ilObjUser::_loginExists($username);
			$pdf_id = (int) $pdf_id;
			if($usr_id)
			{
				$ilDB->update(self::pdf_data_table,
					array(
						'usr_id' 	=> array('integer', $usr_id),
					),
					array(
						'pdf_id' => array('integer', $pdf_id)
					)
				);
				ilScanAssessmentLog::getInstance()->debug(sprintf('User with the id (%s) set mapping for pdf (%s) to user %s', $ilUser->getId(), $pdf_id, $usr_id));
			}
		}
	}

	/**
	 * Do auto completion
	 * @return void
	 */
	public function doUserAutoCompleteCmd()
	{

		if(!isset($_GET['autoCompleteField']))
		{
			$a_fields = array('login','firstname','lastname','email', 'recipients', 'matriculation');
			$result_field = 'login';
		}
		else
		{
			$a_fields = array((string) $_GET['autoCompleteField']);
			$result_field = (string) $_GET['autoCompleteField'];
		}

		$auto = new ilUserAutoComplete();
		$auto->setSearchFields($a_fields);
		$auto->setResultField($result_field);
		$auto->enableFieldSearchableCheck(true);
		echo $auto->getList($_REQUEST['term']);
		exit();
	}
}