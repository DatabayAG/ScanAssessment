<?php
require_once 'Services/Table/classes/class.ilTable2GUI.php';
require_once 'Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php';
require_once 'Services/User/classes/class.ilUserUtil.php';

/**
 * Class ilScanAssessmentScanTableReturnResultsGUI
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentScanTableReturnResultsGUI extends ilTable2GUI
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

		$this->setId('scas_return_results_table' );
		parent::__construct($a_parent_obj, $a_parent_cmd);

		$this->setDefaultOrderDirection('ASC');
		$this->setDefaultOrderField('usr_id');
		$this->setShowRowsSelector(false);
		$this->setTitle(ilScanAssessmentPlugin::getInstance()->txt('scas_return_results'));
		$this->setRowTemplate('tpl.row_return.html', ilScanAssessmentPlugin::getInstance()->getDirectory());

		$this->addColumn('', 'usr_id',  '1px', true);
		#$this->setSelectAllCheckbox('usr_id');
		$this->addColumn(ilScanAssessmentPlugin::getInstance()->txt('scas_usr_name'), 'user_name');
		$this->addColumn(ilScanAssessmentPlugin::getInstance()->txt('scas_pdf_id'), 'pdf_id');
		$this->addColumn(ilScanAssessmentPlugin::getInstance()->txt('scas_revision_done'), 'revision_done');
		$this->addColumn(ilScanAssessmentPlugin::getInstance()->txt('scas_results_returned'), 'results_return');
		
		//$this->addColumn(ilScanAssessmentPlugin::getInstance()->txt('scas_actions'), 'actions', '10%');
	}

	/**
	 * @param array $a_set
	 */
	protected function fillRow($a_set)
	{
		foreach ($a_set as $key => $value)
		{
			if($key == 'usr_id')
			{
				$value = ilUtil::formCheckbox(0, 'usr_id[]', $value);
			}
			$this->tpl->setVariable('VAL_'.strtoupper($key), $value);
		}

		#$current_selection_list = new ilAdvancedSelectionListGUI();
		#$current_selection_list->setListTitle(ilScanAssessmentPlugin::getInstance()->txt('scas_actions'));
		#$current_selection_list->setId('act_' . $a_set['file_id']);

		#$this->ctrl->setParameter($this->parent_obj, 'comment_id', $a_set['comment_id']);
		
		#$current_selection_list->addItem(ilScanAssessmentPlugin::getInstance()->txt('scas_edit'), '', '$link_target');
		#$this->tpl->setVariable('VAL_ACTIONS', $current_selection_list->getHTML());
	}
}