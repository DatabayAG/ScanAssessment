<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/libs/fpdi/fpdi.php';

/**
 * Class ilScanAssessmentPdfUtils
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentPdfUtils extends FPDI
{
	/**
	 * @param $pdf_files
	 */
	public function concat($pdf_files)
	{
		if(is_array($pdf_files) && sizeof($pdf_files) > 0)
		{
			$this->setPrintHeader(false);
			$this->setPrintFooter(false);
			foreach($pdf_files as $file)
			{
				$pageCount = $this->setSourceFile($file);
				for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++)
				{
					$tplIdx = $this->importPage($pageNo);
					$s = $this->getTemplateSize($tplIdx);
					$this->AddPage($s['w'] > $s['h'] ? 'L' : 'P', array($s['w'], $s['h']));
					$this->useTemplate($tplIdx);
				}
			}
		}
	}

	/**
	 * @param $name
	 */
	public function getPdfInline($name)
	{
		$this->Output($name, 'I');
		unlink($name);
	}

	/**
	 * @param $name
	 */
	public function writePdfFile($name)
	{
		$this->Output($name, 'F');
	}
}