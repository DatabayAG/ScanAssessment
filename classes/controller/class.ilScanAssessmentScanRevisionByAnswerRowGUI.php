<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentScanGUI.php');
ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentScanRevisionGUI.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentRevision.php');
require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';
require_once 'Services/Accordion/classes/class.ilAccordionGUI.php';
/**
 * Class ilScanAssessmentUserPackagesPdfGUI
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentScanRevisionByAnswerRowGUI  extends ilScanAssessmentScanRevisionGUI
{
	const NEUTRAL	= 2;
	const DONE		= 1;
	const UNDONE	= 0;

	/**
	 * @var array
	 */
	protected static $question_cache = array();

	/**
	 * @param $pdf_id
	 * @param $page
	 * @param $qid
	 * @return bool
	 */
	protected function doesQuestionFileExist($pdf_id, $page, $qid)
	{
		$file = $this->file_helper->getRevisionPath() . '/qpl/' . $pdf_id . '/whole/' . $page . '_' . $qid . ilScanAssessmentGlobalSettings::getInstance()->getInternFileType();
		return file_exists($file);
	}
	/**
	 * @param ilPropertyFormGUI|null $form
	 * @return string
	 */
	public function defaultCmd(ilPropertyFormGUI $form = null)
	{
		$pluginObject = $this->getCoreController()->getPluginObject();
		if(!($form instanceof ilPropertyFormGUI))
		{
			$form = $this->getForm();
		}

		/** @var ilTemplate $tpl */
		$tpl = $pluginObject->getTemplate('tpl.test_configuration.html', true, true);

		$sidebar = $this->renderSteps();
		$tpl->setVariable('STATUS', $sidebar);

		$pdf_objects = $this->queryPdfData();
		$pdf_accordion = new ilAccordionGUI();
		$pdf_accordion->setBehaviour('FirstOpen');
		$checked_answers = ilScanAssessmentRevision::getAnswerDataForTest($this->test->getId());
		foreach($pdf_objects as $pdf_id => $pdf_data)
		{
			$page_accordion = new ilAccordionGUI();
			$page_accordion->setBehaviour('FirstOpen');
			foreach($pdf_data as $page => $question_data)
			{
				/** @var ilTemplate $template */
				$template = $pluginObject->getTemplate('default/tpl.revision_whole_answer.html', true, true);
				if($question_data['checkboxes'] == 1)
				{
					$not_found = false;
					foreach($question_data as $position => $data)
					{
						if(is_array($data))
						{
							$qid = $data['question'];

							if($this->doesQuestionFileExist($pdf_id, $page, $qid))
							{
								$this->addAnswersToTemplate($pdf_id, $page, $data['answers'], $checked_answers, $template);
								$template->setCurrentBlock('checkbox');
								$template->setVariable('IMAGE', $this->file_helper->getRevisionPath() . '/qpl/' . $pdf_id . '/whole/' . $page . '_' . $qid . ilScanAssessmentGlobalSettings::getInstance()->getInternFileType());
								$template->parseCurrentBlock();
							}
							else
							{
								$not_found = true;
							}
						}

					}
					$info = '';
					if($not_found)
					{
						$template->setCurrentBlock('not_found');
						$template->setVariable('NOT_FOUND', $pluginObject->txt('scas_not_found'));
						$template->parseCurrentBlock();
						$info = ' (' . $pluginObject->txt('scas_not_found') . ')';
					}
					$page_accordion->addItem($pluginObject->txt('scas_page') . ' ' . $page . $info, $template->get());
				}
			}
			$header_string = 'PDF ' . $pdf_id;
			$pdf_accordion->addItem($header_string, $page_accordion->getHTML());
		}

		$custom = new ilCustomInputGUI($pluginObject->txt('scas_checkbox_revision'), '');
		$custom->setHTML($pdf_accordion->getHTML());
		$form->addItem($custom);
		
		$form->addCommandButton(__CLASS__ . '.saveForm', $this->lng->txt('save'));

		$tpl->setVariable('FORM', $form->getHTML());
		$this->addTabs();

		return $tpl->get();
	}

	/**
	 * @param $pdf_id
	 * @param $page
	 * @param $answers
	 * @param $checked_answers
	 * @param $template
	 */
	protected function addAnswersToTemplate($pdf_id, $page, $answers, $checked_answers, $template)
	{
		foreach($answers as $key => $data)
		{
			if($data['type'] == 'ilScanAssessment_assSingleChoice' || $data['type'] == 'ilScanAssessment_assMultipleChoice')
			{
				$name = $pdf_id . '_' .$page . '_' . $data['qid'] . '_' .$data['aid'] . '_l';
				$this->answerIsChecked($checked_answers, $name, $template, $data['type']);
				$template->setCurrentBlock($data['type']);
				$answer = $this->getSingleOrMultipleAnswerTexts($data['qid']);
				$template->setVariable('VALUE', $answer[$data['aid']]);
				$template->setVariable('ANSWER_ID', 'revision[' . $name . ']');
				$template->parseCurrentBlock();
			}
			else if($data['type'] == 'ilScanAssessment_assKprimChoice')
			{
				$name_l = $pdf_id . '_' .$page . '_' . $data['correct']['qid'] . '_' .$data['correct']['position'] . '_l';
				$name_r = $pdf_id . '_' .$page . '_' . $data['wrong']['qid'] . '_' .$data['correct']['position'] . '_r';
				$answer = $this->getKprimAnswerTexts($data['correct']['qid']);
				if(array_key_exists($name_l, $checked_answers))
				{
					$template->touchBlock('checked_1');
				}
				if(array_key_exists($name_r, $checked_answers))
				{
					$template->touchBlock('checked_2');
				}
				$template->setCurrentBlock($data['type']);
				$template->setVariable('ANSWER_ID', 'revision[' . $name_l .']');
				$template->setVariable('ANSWER_ID_2', 'revision[' . $name_r .']');
				$template->setVariable('VALUE', $answer[$data['correct']['position']]);
				$template->parseCurrentBlock();
			}
		}
	}

	/**
	 * @param $qid
	 * @return array
	 */
	protected function getSingleOrMultipleAnswerTexts($qid)
	{
		if(!array_key_exists($qid, self::$question_cache))
		{
			$question = assQuestion::_instantiateQuestion($qid);
			$answer_texts = array();
			if($question != null)
			{
				foreach($question->getAnswers() as $key => $answer)
				{
					$answer_texts[$answer->getOrder()] = $answer->getAnswerText();
				}
				self::$question_cache[$qid] = $answer_texts;
			}
		}
		return self::$question_cache[$qid];
	}

	/**
	 * @param $qid
	 * @return array
	 */
	protected function getKprimAnswerTexts($qid)
	{
		if(!array_key_exists($qid, self::$question_cache))
		{
			$question = assQuestion::_instantiateQuestion($qid);
			$answer_texts = array();
			if($question != null)
			{
				foreach($question->getAnswers() as $key => $answer)
				{
					$answer_texts[$answer->getPosition()] = $answer->getAnswerText();
				}
				self::$question_cache[$qid] = $answer_texts;
			}
		}
		return self::$question_cache[$qid];
	}

	/**
	 * @param $checked_answers
	 * @param $answer_id
	 * @param $template
	 */
	protected function answerIsChecked($checked_answers, $answer_id, $template, $type)
	{
		if(array_key_exists($answer_id, $checked_answers))
		{
			if($type == 'ilScanAssessment_assSingleChoice')
			{
				$template->touchBlock('checked_single');
			}
			else
			{
				$template->touchBlock('checked_multiple');
			}
		}
	}

	/**
	 * @return ilPropertyFormGUI
	 */
	protected function getForm()
	{

		$pluginObject = $this->getCoreController()->getPluginObject();

		$form = new ilPropertyFormGUI();
		$form->setFormAction($pluginObject->getFormAction(__CLASS__ . '.saveForm', array('ref_id' => (int)$_GET['ref_id'])));

		return $form;
	}

	/**
	 * @param string $active_sub
	 */
	protected function addTabs($active_sub = 'scan_scanner')
	{
		parent::addTabs();
		$this->tabs->setSubTabActive('scan_revision_by_answer_row');
	}

	/**
	 * @return string
	 */
	public function getDefaultClassAndCommand()
	{
		return 'ilScanAssessmentScanRevisionByAnswerRowGUI.default';
	}

	/**
	 * @return string
	 */
	public function saveFormCmd()
	{
		$form = $this->getForm();
		if($form->checkInput())
		{
			try
			{
				if(array_key_exists('pdf_id', $_POST))
				{
					foreach($_POST['pdf_id'] as $pdf_id)
					{
						$pdf_id = (int) $pdf_id;
						$state = self::UNDONE;
						if(array_key_exists('revision_done', $_POST) && array_key_exists($pdf_id, $_POST['revision_done']))
						{
							$state = self::DONE;
						}
						if(array_key_exists('all_revision_state', $_POST) && strlen($_POST['all_revision_state']) != 0 && $_POST['all_revision_state'] != self::NEUTRAL)
						{
							$state = (int) $_POST['all_revision_state'];
						}
						ilScanAssessmentRevision::saveRevisionDoneState($pdf_id, $state);
						ilScanAssessmentRevision::removeRevisionData($pdf_id, $this->test->getId());
					}
				}

				if(array_key_exists('revision', $_POST))
				{
					$answers = ilUtil::stripSlashesRecursive($_POST['revision']);
					ilScanAssessmentRevision::addAnswers($this->test->getId(), $answers);
				}
				ilUtil::sendSuccess($this->lng->txt('saved_successfully'));
				$form = $this->getForm();
			}
			catch(ilException $e)
			{
				ilUtil::sendFailure($this->getCoreController()->getPluginObject()->txt($e->getMessage()));
			}
		}
		return $this->defaultCmd($form);
	}

	/**
	 * @return array
	 */
	protected function queryPdfData()
	{
		$answers = array();
		global $ilDB;
		$res = $ilDB->queryF(
			'SELECT qpl_data, page, dq.pdf_id, has_checkboxes FROM pl_scas_pdf_data_qpl dq INNER JOIN pl_scas_pdf_data pd ON pd.pdf_id = dq.pdf_id
					WHERE obj_id = %s ',
			array('integer'),
			array($this->test->getId())
		);

		while($row = $ilDB->fetchAssoc($res))
		{
			$answers[$row['pdf_id']][$row['page']] = json_decode($row['qpl_data'], true);
			$answers[$row['pdf_id']][$row['page']]['checkboxes'] = $row['has_checkboxes'];
		}
		return $answers;
	}
}