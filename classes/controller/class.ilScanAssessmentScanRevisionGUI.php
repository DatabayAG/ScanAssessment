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

		$images = $this->getAnswerImages();
		sort($images);
		require_once 'Services/Accordion/classes/class.ilAccordionGUI.php';
		$accordion = new ilAccordionGUI(); 
		$accordion->setBehaviour('FirstOpen');
		$pdf_id = '';
		$answers = ilScanAssessmentScanProcess::getAnswerDataForTest($this->test->getId());
		foreach($images as $key => $folder)
		{
			/** @var ilTemplate $template */
			$template = $this->getCoreController()->getPluginObject()->getTemplate('default/tpl.revision.html', true, true);
			foreach($folder as $img)
			{
				$pdf_id = $img['pdf_id'];
				if(array_key_exists($img['id'], $answers))
				{
					$template->touchBlock('checked');
				}
				$template->setCurrentBlock('checkbox');
				$template->setVariable('IMAGE', $img['relative_path']);
				$template->setVariable('CHECKBOX', $img['file_name']);
				$template->parseCurrentBlock();
			}
			$accordion->addItem('PDF ' . $pdf_id, $template->get());
		}
		
		$tpl->setVariable('FORM', $accordion->getHTML());
		$this->addTabs();
		return $tpl->get();
	}

	/**
	 * @return ilPropertyFormGUI
	 */
	protected function getForm()
	{

		$pluginObject = $this->getCoreController()->getPluginObject();

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

	/**
	 * @return array
	 */
	protected function getAnswerImages()
	{
		$dirs = $this->scanAnalysedDir();
		$files	= array();
		foreach($dirs as $path)
		{
			$dir = basename($path);
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filename)
			{
				if(! is_dir($filename->getFilename()))
				{
					if(basename(dirname(dirname($filename->getPathName()))) == 'qpl')
					{
						$pdf_id = basename(dirname($filename->getPathName()));
						$parts = preg_split('/_/', $filename->getFilename());
						$files[$pdf_id][] = array(	'file_name'		=> $filename->getFilename(),
												'relative_path'	=> $this->file_helper->getRevisionPath() . '/' . $dir . '/' .$pdf_id. '/'. $filename->getFilename(),
												'pdf_id'		=> $pdf_id,
												'id'			=> $pdf_id . '_' . $parts[0] . '_' . $parts[1]   
						);
					}

				}
			}
		}
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
}