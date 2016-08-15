<?php
require_once('Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/libs/fpdi/fpdi.php');

define('PDF_TOPLEFT_SYMBOL_X', 20);
define('PDF_TOPLEFT_SYMBOL_Y', 20);
define('PDF_TOPLEFT_SYMBOL_W', 5);
define('PDF_BOTTOMLEFT_SYMBOL_Y', -20);

define('PDF_BOTTOMRIGHT_QR_W', 30);
define('PDF_BOTTOMRIGHT_QR_MARGIN_X', 5);
define('PDF_BOTTOMRIGHT_QR_MARGIN_Y', 5);

define('PDF_ANSWERCIRCLE_W', 5);
define('PDF_ANSWERBOX_W', 8);
define('PDF_ANSWERBOX_H', 5);

define('DRAWRULE_MAXTRIES', 1000);
define('DRAWRULE_MAXTIME', 10);

class PdfStyler extends fpdi {

	protected $pageNr = 0;
	protected $qrImg = NULL;
	protected $backgroundPDF="";
	protected $processPDFPage = 0;
	protected $pageHeaderHTML = "";
	protected $counterPerParticipant = 0;
	protected $QRState = array();
	protected $docArea = "";
	protected $docType = "";
	protected $lastDocType = NULL;
	protected $language = "de";

	/**
	 * @return string
	 */
	public function getBackgroundPDF()
	{
		return $this->backgroundPDF;
	}

	/**
	 * @return null
	 */
	public function getQrImg()
	{
		return $this->qrImg;
	}

	/**
	 * @param null $qrImg
	 */
	public function setQrImg($qrImg)
	{
		$this->qrImg = $qrImg;
	}

	/**
	 * @return string
	 */
	public function getPageHeaderHTML()
	{
		return $this->pageHeaderHTML;
	}

	/**
	 * @param string $pageHeaderHTML
	 */
	public function setPageHeaderHTML($pageHeaderHTML)
	{
		$this->pageHeaderHTML = $pageHeaderHTML;
	}

	/**
	 * @param string $backgroundPDF
	 */
	public function setBackgroundPDF($backgroundPDF)
	{
		$this->backgroundPDF = $backgroundPDF;
	}

	public function setQRCodeOnThisPage($state) 
	{
		$this->QRState[] = $state;
	}

	/**
	 * Overwrites TCPDFS Header function
	 */
	public function Header() 
	{

		$this->pageNr++;
		
		$this->setY(0);

		if($this->pageHeaderHTML!='') 
		{
			$this->setY(20);
			$html = "";
			$this->SetFont('dejavusans', '', 8, '', TRUE);

			$html .= $this->pageHeaderHTML;

			$html .= '<hr style="border: solid 1px silver;"/>';

			$this->writeHTML($html, TRUE, FALSE, TRUE, FALSE, 'L');

			$this->Ln(2);

			$this->SetMargins(PDF_MARGIN_LEFT, $this->getY(), PDF_MARGIN_RIGHT);
		}


		if(count($this->QRState)>0) { 
			$state = array_shift($this->QRState);
			if ($this->qrImg != NULL && $state==true) {
				$maxWidth = $this->getPageWidth();
				$maxHeight = $this->getPageHeight();

				//$img = "@".file_get_contents($img);
				$ext = strtolower(substr($this->qrImg, strrpos($this->qrImg, ".") + 1));
				$S = PDF_BOTTOMRIGHT_QR_W;
				$this->Image($this->qrImg, $maxWidth - ($S + PDF_BOTTOMRIGHT_QR_MARGIN_X), $maxHeight - ($S + PDF_BOTTOMRIGHT_QR_MARGIN_Y), $S, $S, ($ext == 'png' ? 'PNG' : 'JPG'), '', 'T', FALSE, 150, '', FALSE, FALSE, 0, FALSE, FALSE, FALSE);

				$this->counterPerParticipant++;

				// Schnittmarken
				$circleStyle = array(
					'width' => 0.25,
					'cap'   => 'butt',
					'join'  => 'miter',
					'dash'  => 0,
					'color' => array(0, 0, 0)
				);
				/*$innerColor = array(
					13,
					79,
					194
				); */
				$innerColor = array( 0, 0, 0 );
				$this->Circle(PDF_TOPLEFT_SYMBOL_X, PDF_TOPLEFT_SYMBOL_Y, PDF_TOPLEFT_SYMBOL_W / 2, 0, 360, 'DF', $circleStyle, $innerColor);
				$this->Circle(PDF_TOPLEFT_SYMBOL_X, $this->getPageHeight() + PDF_BOTTOMLEFT_SYMBOL_Y, PDF_TOPLEFT_SYMBOL_W / 2, 0, 360, 'DF', $circleStyle, $innerColor);

			}
		}
		return;
	}

	/**
	 * Overwrites TCPDFS Footer function
	 */
	public function Footer() 
	{

	}
}

?>