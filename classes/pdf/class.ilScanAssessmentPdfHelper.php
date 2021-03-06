<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/libs/qr_img0.50i/php/class.qr_img.php';
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfAppendMarker.php');
ilScanAssessmentPlugin::getInstance()->includeClass('pdf/class.ilScanAssessmentPdfConstants.php');
if(file_exists('Services/PDFGeneration/classes/tcpdf/tcpdf.php'))
{
	require_once 'Services/PDFGeneration/classes/tcpdf/tcpdf.php';
}
else
{
	require_once 'libs/composer/vendor/tecnickcom/tcpdf/tcpdf.php';
}
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/libs/fpdi/fpdi.php';

/**
 * Class ilScanAssessmentPdfHelper
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentPdfHelper
{
	/**
	 * @var ilScanAssessmentPdfAppendMarker
	 */
	public $pdf;

	/**
	 * @var string
	 */
	protected $qr_images_path = '';

	/**
	 * @var
	 */
	protected $add_head;

	/**
	 * ilScanAssessmentPdfHelper constructor.
	 * @param ilScanAssessmentPdfMetaData $data
	 * @param ilScanAssessmentTestConfiguration $config
	 */
	public function __construct(ilScanAssessmentPdfMetaData $data, $config) 
	{
		$this->qr_images_path = ilUtil::getDataDir() . '/temp_qr_images';
		$this->initializePDFStructure($data, $config);
		$this->setAddHead(false);
	}

	/**
	 * @param ilScanAssessmentPdfMetaData $data
	 * @param ilScanAssessmentTestConfiguration $config
	 */
	protected function initializePDFStructure($data, $config)
	{
		$this->pdf = new ilScanAssessmentPdfAppendMarker(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, TRUE, 'UTF-8', FALSE);
		$this->pdf->setMetadata($data, $config);
		$this->pdf->SetCreator($data->getAuthor());
		$this->pdf->SetAuthor($data->getAuthor());

		$this->pdf->setFooterData(array(0, 64, 0), array(0, 64, 128));

		$this->pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$this->pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		$this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		$this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$this->pdf->setHeaderMargin(PDF_MARGIN_HEADER);
		$this->pdf->setFooterMargin(PDF_MARGIN_FOOTER);

		$this->pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		$this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		$this->pdf->setFontSubsetting(TRUE);

		$this->pdf->SetFont(PDF_DEFAULT_FONT, '', PDF_DEFAULT_FONT_SIZE, '', TRUE);
	}

	protected function removeOldQRFiles()
	{
		$this->checkIfTempDirExistsOrCreateIt();

		$files = glob($this->qr_images_path . '/qr_*.jpg');

		if(!is_array($files))
		{
			$files = array();
		}

		for($i = 0; $i < count($files); $i++)
		{
			if(filemtime($files[$i]) < time() - 60 * 10)
			{
				unlink($files[$i]);
			}
		}
	}
	
	protected function checkIfTempDirExistsOrCreateIt()
	{
		if(!is_dir($this->qr_images_path))
		{
			ilUtil::makeDirParents($this->qr_images_path);
		}
	}

	/**
	 * @param $code
	 */
	public function createQRCode($code) {

		if ($code == "") 
		{
			$code = "NoCodeGiven";
		}
		else if(stristr($code,"DemoCode")) 
		{
			$code = "DemoCode";
		}

		$this->removeOldQRFiles();

		$QR = new myQR();
		$output_image = $QR->create($code, 'M', 10, 1, 'jpeg');
		$filename = $this->qr_images_path . '/qr_' . $code . "_".microtime(true).'.jpg';
		/**
		 * Da der Footer immer erst im PDF angelegt wird, wenn die nächste Seite erzeugt wird oder das Dokument
		 * zuende ist, muss ich hier beim erzeugen des QR-Codes, was vor addPage() erfolgt, die neue Grafik
		 * Zwischenspeichern und erst nach der Verwendung im Footer als Aktives Bild einsetzen.
		 * Siehe PDFStyler $this->qrImg = $this->qrImgNext;
		 */
		$this->pdf->setQrImg($filename);

		$white = imagecolorallocate($output_image, 255,255,255);
		$output_image = imagerotate($output_image, 180, $white);

		$light = imagecolorallocate($output_image, 10,10,10);
		imagestring($output_image, 5, imagesx($output_image)/3, 0, $code, $light);
		imagejpeg($output_image, $filename, 100);
	}

	/**
	 * @param $pdfFile
	 */
	public function addPdfPage($pdfFile) 
	{
		$backgroundPDF = $this->pdf->getBackgroundPDF();
		$pageHeaderHTML = $this->pdf->getPageHeaderHTML();
		$this->pdf->setPageHeaderHTML("");
		$this->pdf->setBackgroundPDF($pdfFile);
		$this->pdf->AddPage();
		$this->pdf->setBackgroundPDF($backgroundPDF);
		$this->pdf->setPageHeaderHTML($pageHeaderHTML);
	}

	/**
	 * @param string $pdfTemplate
	 */
	public function addPage($pdfTemplate="") 
	{
		if($pdfTemplate!='') 
		{
			$this->pdf->setBackgroundPDF($pdfTemplate);
		}
		$this->pdf->setAddHead($this->getAddHead());
		$this->pdf->AddPage();
	}

	/**
	 * @param $html
	 */
	public function writeHTML($html) 
	{
		$this->pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, TRUE, '', TRUE);
	}

	/**
	 * @param        $w
	 * @param        $h
	 * @param        $x
	 * @param        $y
	 * @param string $html
	 * @param int    $border
	 * @param int    $ln
	 * @param bool   $fill
	 * @param bool   $reseth
	 * @param string $align
	 * @param bool   $autopadding
	 */
	public function writeHTMLCell($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=false, $reseth=true, $align='', $autopadding=true)
	{
		$this->pdf->writeHTMLCell($w, $h, $x, $y, $html, $border, $ln, $fill, $reseth, $align, $autopadding);
	}

	/**
	 * @param string $filename
	 */
	public function inline($filename='pruefung.pdf') 
	{
		$qr_image = $this->pdf->getQrImg();

		$this->pdf->Output($filename, 'I');

		if (file_exists($qr_image)) {
			unlink($qr_image);
		}
	}

	/**
	 * @param string $filename
	 */
	public function writeFile($filename)
	{
		$qr_image = $this->pdf->getQrImg();
		$this->pdf->Output($filename, 'F');
		if (file_exists($qr_image)) {
			unlink($qr_image);
		}
	}

	/**
	 * @return mixed
	 */
	public function getAddHead()
	{
		return $this->add_head;
	}

	/**
	 * @param mixed $add_head
	 */
	public function setAddHead($add_head)
	{
		$this->add_head = $add_head;
	}

}