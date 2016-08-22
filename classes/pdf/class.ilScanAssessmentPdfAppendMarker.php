<?php
require_once('Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/libs/fpdi/fpdi.php');
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/pdf/class.ilScanAssessmentPdfConstants.php';

/**
 * Class ilPDFAppendMarker
 */
class ilPDFAppendMarker extends FPDI{

	protected $pageNr = 0;
	protected $qrImg = NULL;
	protected $backgroundPDF = '';
	protected $processPDFPage = 0;
	protected $pageHeaderHTML = '';
	protected $counterPerParticipant = 0;
	protected $QRState = array();
	protected $docArea = '';
	protected $docType = '';
	protected $lastDocType = NULL;

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
			$this->SetFont(PDF_DEFAULT_FONT, '', PDF_DEFAULT_FONT_SIZE_HEAD, '', TRUE);

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
		$this->SetY(-20);
		$this->SetX(0);
		$html = "<table style='width:100%;'><tr>";
		$html .= '<td style="font-size:0.8em;text-align:center;">';

		$html .= "Universität Bla und Blubb - Einführung in die BWL 6/2016<br>";
		$html .= "</td>";
		$html .= "</tr></table>";

		$this->writeHTML($html, true, false, true, false, 'L');
	}

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

	/**
	 * @param boolean $state
	 */
	public function setQRCodeOnThisPage($state)
	{
		$this->QRState[] = $state;
	}
}

?>