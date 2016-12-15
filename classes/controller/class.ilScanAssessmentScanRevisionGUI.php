<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

ilScanAssessmentPlugin::getInstance()->includeClass('controller/class.ilScanAssessmentScanGUI.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentRevision.php');
require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';

/**
 * Class ilScanAssessmentUserPackagesPdfGUI
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentScanRevisionGUI extends ilScanAssessmentScanGUI
{
	const NEUTRAL	= 2;
	const DONE		= 1;
	const UNDONE	= 0;

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

		$revision_state = ilScanAssessmentRevision::getRevisionState($this->test->getId());

		$images = $this->getAnswerImages();
		require_once 'Services/Accordion/classes/class.ilAccordionGUI.php';
		$accordion = new ilAccordionGUI(); 
		$accordion->setBehaviour('FirstOpen');
		$pdf_id = '';
		$files = $this->getProcessedFilesData();
		foreach($images as $key => $folder)
		{
			/** @var ilTemplate $template */
			$template = $pluginObject->getTemplate('default/tpl.revision.html', true, true);
			$counter = 0;
			if(array_key_exists('checked', $folder))
			{
				foreach($folder['checked'] as $img)
				{
					$pdf_id = $this->addImageToTemplate($img, $template, true);
					$counter++;
				}
			}
			if(array_key_exists('unchecked', $folder))
			{
				foreach($folder['unchecked'] as $img)
				{
					$pdf_id = $this->addImageToTemplate($img, $template);
					$counter++;
				}
			}
			if(array_key_exists($pdf_id, $files))
			{
				ksort($files[$pdf_id]);
				foreach($files[$pdf_id] as $page => $element)
				{
					foreach($element as $file)
					{
						$link = ilScanAssessmentPlugin::getInstance()->getLinkTarget('ilScanAssessmentScanRevisionGUI' . '.downloadProcessedImage', array('ref_id' => (int)$_GET['ref_id'], 'file_name' => $file['file_name']));
						$template->setCurrentBlock('link');
						$template->setVariable('VAL_LINK', $link);
						$template->setVariable('VAL_FILE_NAME', $file['file_name']);
						$template->parseCurrentBlock();
					}
					$template->setCurrentBlock('link_list');
					$template->setVariable('PAGE',  $pluginObject->txt('scas_page') . ' ' . $page);
					$template->parseCurrentBlock();
				}
			}

			$header_string = 'PDF ' . $pdf_id . ', ' . $pluginObject->txt('scas_found_elements') . ' (' . $counter . ')';
			if($revision_state[$pdf_id] == self::DONE)
			{
				$template->touchBlock('checked');
				$header_string .= ', ' . $pluginObject->txt('scas_revision_done');
			}

			$template->setCurrentBlock('form');
			$template->setVariable('REVISION_DONE', sprintf($pluginObject->txt('scas_revision_done_spf'), $pdf_id));
			$template->setVariable('REVISION_CHECK', 'revision_done['.$pdf_id.']');
			$template->parseCurrentBlock();
			$template->setCurrentBlock('hidden');
			$template->setVariable('PDF', $pdf_id);
			$template->parseCurrentBlock();
			$hidden = new ilHiddenInputGUI('pdf_id');
			$hidden->setValue($pdf_id);
			$accordion->addItem($header_string, $template->get());
		}

		$custom = new ilCustomInputGUI($pluginObject->txt('scas_checkbox_revision'), '');
		$custom->setHTML($accordion->getHTML());
		$form->addItem($custom);
		$form->addCommandButton(__CLASS__ . '.saveForm', $this->lng->txt('save'));
		$select = new ilSelectInputGUI($pluginObject->txt('scas_revision_state'), 'all_revision_state');
		$options = $shuffle_modes = array(self::NEUTRAL => '',
										  self::DONE    => $pluginObject->txt('scas_revsion_all_done'),
										  self::UNDONE  => $pluginObject->txt('scas_revsion_all_undone')
		);

		$select->setOptions($options);
		$form->addItem($select);
		$tpl->setVariable('FORM', $form->getHTML());
		$this->addTabs();

		return $tpl->get();
	}

	public function downloadProcessedImageCmd()
	{
		$file_name = ilUtil::stripSlashes($_GET['file_name']);
		$file_path = $this->file_helper->getAnalysedPath() . $file_name;
		$this->download($file_path, $file_name);
	}

	/**
	 * @return array
	 */
	protected function getProcessedFilesData()
	{
		$files = parent::getProcessedFilesData();
		$resort_files = array();
		foreach($files as $file)
		{
			$resort_files[$file['pdf_id']][$file['page']][] = $file;
		}
		return $resort_files;
	}

	/**
	 * @param $img
	 * @param ilTemplate $template
	 * @param bool $checked
	 * @return int
	 */
	protected function addImageToTemplate($img, $template, $checked = false)
	{
		$pdf_id = $img['pdf_id'];
		if($checked == true)
		{
			$template->touchBlock('checked');
		}
		$template->setCurrentBlock('checkbox');
		$template->setVariable('IMAGE', $img['relative_path']);
		$template->setVariable('CHECKBOX', 'revision['.$img['id'].']');
		$template->parseCurrentBlock();
		return $pdf_id;
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
		$this->tabs->setSubTabActive('scan_revision');
	}

	/**
	 * @return string
	 */
	public function getDefaultClassAndCommand()
	{
		return 'ilScanAssessmentScanRevisionGUI.default';
	}

	/**
	 * @return array
	 */
	protected function getAnswerImages()
	{
		$dirs = $this->scanAnalysedDir();
		$files	= array();
		$answers = ilScanAssessmentRevision::getAnswerDataForTest($this->test->getId());
		foreach($dirs as $path)
		{
			$dir = basename($path);
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filename)
			{
				if(! is_dir($filename->getFilename()))
				{
					if(basename(dirname(dirname($filename->getPathName()))) == 'qpl')
					{
						$pdf_id		= basename(dirname($filename->getPathName()));
						$parts		= preg_split('/_/', $filename->getFilename());
						$answer_id	= $pdf_id . '_' . $parts[0] . '_' . $parts[1] . '_' . $parts[2];

						$element = array(	'file_name'			=> $filename->getFilename(),
											'relative_path'		=> $this->file_helper->getRevisionPath() . '/' . $dir . '/' .$pdf_id. '/'. $filename->getFilename(),
											'pdf_id'			=> $pdf_id,
											'id'				=> $answer_id
										);

						if(array_key_exists($answer_id, $answers))
						{
							$files[$pdf_id]['checked'][] = $element;
						}
						else
						{
							$files[$pdf_id]['unchecked'][] = $element;
						}
						
					}

				}
			}
		}
		ksort($files);
		return $files;
	}

	/**
	 * @return array
	 */
	protected function scanAnalysedDir()
	{
		$path = $this->file_helper->getRevisionPath();
		$dirs = array();
		if ($handle = opendir($path))
		{
			while (false !== ($entry = readdir($handle)))
			{
				if(is_dir($path .'/'. $entry) && $entry != '.' && $entry != '..')
				{
					$dirs[] = $path .'/'. $entry;
				}
			}
			closedir($handle);
		}
		return $dirs;
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
}