<?php
require_once 'Services/Table/classes/class.ilTable2GUI.php';
require_once 'Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php';
require_once 'Services/User/classes/class.ilUserUtil.php';

/**
 * Class ilScanAssessmentScanTableLayoutGUI
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentScanTableLayoutGUI extends ilTable2GUI
{

	/**
	 * @var
	 */
	protected $parent_obj;

	/**
	 * @var
	 */
	protected $parent_cmd;

	/**
	 * @param $a_parent_obj
	 * @param string      $a_parent_cmd
	 */
	public function __construct($a_parent_obj, $a_parent_cmd)
	{

		$this->parent_obj = $a_parent_obj;
		$this->parent_cmd = $a_parent_cmd;

		$this->setId('scas_layout_table' );
		parent::__construct($a_parent_obj, $a_parent_cmd);

		#$this->setFormAction(ilScanAssessmentPlugin::getInstance()->getFormAction(__CLASS__ . '.saveForm'));
		$this->setDefaultOrderDirection('ASC');
		$this->setDefaultOrderField('filename');
		$this->setShowRowsSelector(false);
		$this->setTitle(ilScanAssessmentPlugin::getInstance()->txt('scas_layout'));
		$this->setRowTemplate('tpl.row_scans.html', ilScanAssessmentPlugin::getInstance()->getDirectory());

		$this->addColumn('', 'file_id',  '1px', true);
		$this->setSelectAllCheckbox('file_id');

		$this->addColumn(ilScanAssessmentPlugin::getInstance()->txt('scas_file_name'), 'file_name', '', false, 'ilScanAssessmentLayoutController' );
		$this->addColumn(ilScanAssessmentPlugin::getInstance()->txt('scas_file_date'), 'file_date');
		$this->addColumn(ilScanAssessmentPlugin::getInstance()->txt('scas_file_size'), 'file_size');
		
		$this->addColumn(ilScanAssessmentPlugin::getInstance()->txt('scas_actions'), 'actions', '10%');

		$link = 'ilScanAssessmentLayoutController.areYouSureDeleteEntries';
		$this->addMultiCommand($link, ilScanAssessmentPlugin::getInstance()->txt('scas_delete'));

	}

	/**
	 * @param array $a_set
	 */
	protected function fillRow($a_set)
	{
		foreach ($a_set as $key => $value)
		{
			if($key == 'file_id')
			{
				$value = ilUtil::formCheckbox(0, 'file_id[]', $value);
			}
			else if($key == 'file_name')
			{
				$link = ilScanAssessmentPlugin::getInstance()->getLinkTarget('ilScanAssessmentLayoutController' . '.downloadPdf',	array('ref_id' => (int)$_GET['ref_id'], 'file_name' => $value));
				$this->tpl->setVariable('VAL_LINK', $link);
			}
			$this->tpl->setVariable('VAL_'.strtoupper($key), $value);
		}

		$current_selection_list = new ilAdvancedSelectionListGUI();
		$current_selection_list->setListTitle(ilScanAssessmentPlugin::getInstance()->txt('scas_actions'));
		$current_selection_list->setId('act_' . $a_set['file_id']);

		#$this->ctrl->setParameter($this->parent_obj, 'comment_id', $a_set['comment_id']);
		
		$current_selection_list->addItem(ilScanAssessmentPlugin::getInstance()->txt('scas_edit'), '', '$link_target');
		#$this->tpl->setVariable('VAL_ACTIONS', $current_selection_list->getHTML());
	}
}