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

		$sidebar = $this->renderSteps();
		$tpl->setVariable('STATUS', $sidebar);

		$images = $this->getAnswerImages();
		require_once 'Services/Accordion/classes/class.ilAccordionGUI.php';
		$accordion = new ilAccordionGUI(); 
		$accordion->setBehaviour('FirstOpen');
		$pdf_id = '';
		foreach($images as $key => $folder)
		{
			/** @var ilTemplate $template */
			$template = $this->getCoreController()->getPluginObject()->getTemplate('default/tpl.revision.html', true, true);
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
			$accordion->addItem('PDF ' . $pdf_id . ', ' . $this->getCoreController()->getPluginObject()->txt('scas_found_elements') . ' (' . $counter . ')', $template->get());
		}
		$test = new ilCustomInputGUI($this->getCoreController()->getPluginObject()->txt('scas_checkbox_revision'), '');
		$test->setHTML( $accordion->getHTML());
		$form->addItem($test);
		$form->addCommandButton(__CLASS__ . '.saveForm', $this->lng->txt('save'));
		$tpl->setVariable('FORM', $form->getHTML());
		$this->addTabs();

		return $tpl->get();
	}

	/**
	 * @param      $img
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

		require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';
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
		$answers = ilScanAssessmentScanProcess::getAnswerDataForTest($this->test->getId());
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
				if(array_key_exists('revision', $_POST))
				{
					$answers = ilUtil::stripSlashesRecursive($_POST['revision']);
					ilScanAssessmentScanProcess::addAnswers($this->test->getId(), $answers);
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