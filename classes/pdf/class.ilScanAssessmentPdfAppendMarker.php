<?php
#require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/libs/fpdi/fpdi.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/pdf/class.ilScanAssessmentPdfConstants.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/pdf/class.ilScanAssessmentPdfHeaderForm.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/geometry/class.ilScanAssessmentVector.php';

/**
 * Class ilPDFAppendMarker
 */
class ilPDFAppendMarker extends TCPDF{

	/**
	 * @var int
	 */
	protected $pageNr = 0;

	/**
	 * @var null
	 */
	protected $qrImg = NULL;

	/**
	 * @var string
	 */
	protected $backgroundPDF = '';

	/**
	 * @var int
	 */
	protected $counterPerParticipant = 0;

	/**
	 * @var array
	 */
	protected $QRState = array();

	/**
	 * 
	 */
	public function addMarkerAndQrCode()
	{
		if(count($this->QRState) > 0)
		{
			$state = array_shift($this->QRState);
			if($this->qrImg != NULL && $state == true)
			{
				$maxWidth  = $this->getPageWidth();
				$maxHeight = $this->getPageHeight();

				$ext = strtolower(substr($this->qrImg, strrpos($this->qrImg, ".") + 1));
				$S   = PDF_BOTTOMRIGHT_QR_W;
				$this->Image($this->qrImg, $maxWidth - ($S + PDF_BOTTOMRIGHT_QR_MARGIN_X), $maxHeight - ($S + PDF_BOTTOMRIGHT_QR_MARGIN_Y), $S, $S, ($ext == 'png' ? 'PNG' : 'JPG'), '', 'T', FALSE, 150, '', FALSE, FALSE, 0, FALSE, FALSE, FALSE);

				$this->counterPerParticipant++;

				$circleStyle = array(
					'width' => 0.25,
					'cap'   => 'butt',
					'join'  => 'miter',
					'dash'  => 0,
					'color' => array(0, 0, 0)
				);

				$innerColor = array(0, 0, 0);
				$this->addTopLeftMarker($circleStyle, $innerColor);
				$this->addBottomLeftMarker($circleStyle, $innerColor);
				$log = ilScanAssessmentLog::getInstance();
				$log->debug(sprintf('Marker where added to 1:X:%s Y:%s, 2:X:%s Y:%s', PDF_TOPLEFT_SYMBOL_X, PDF_TOPLEFT_SYMBOL_Y, PDF_TOPLEFT_SYMBOL_X, $this->getPageHeight() + PDF_BOTTOMLEFT_SYMBOL_Y));
				#$this->addMarker();
			}
		}
	}

	/**
	 * @param $circleStyle
	 * @param $innerColor
	 */
	protected function addTopLeftMarker($circleStyle, $innerColor)
	{
		$this->Circle(PDF_TOPLEFT_SYMBOL_X, PDF_TOPLEFT_SYMBOL_Y, PDF_TOPLEFT_SYMBOL_W, 0, 360, 'DF', $circleStyle, $innerColor);
	}

	/**
	 * @param $circleStyle
	 * @param $innerColor
	 */
	protected function addBottomLeftMarker($circleStyle, $innerColor)
	{
		$this->Circle(PDF_BOTTOMLEFT_SYMBOL_X, $this->getPageHeight() + PDF_BOTTOMLEFT_SYMBOL_Y, PDF_BOTTOMLEFT_SYMBOL_W, 0, 360, 'DF', $circleStyle, $innerColor);
	}

	/**
	 * Overwrites TCPDF Header function
	 */
	public function Header() 
	{
		$this->pageNr++;
		$this->SetY(20);
		$this->SetFont(PDF_DEFAULT_FONT, '', PDF_DEFAULT_FONT_SIZE_HEAD, '', TRUE);
		$this->SetTextColor(0);
		$this->SetDrawColor(0, 0, 0);
		$this->SetFillColor(255, 255, 255);
		$this->SetLineWidth(0.6);
		$this->Ln();
		$this->Cell(40, 5, 'TITLE', 1, 0, 'C', 1);
		$this->Cell(120, 5, 'University of BLAAAA', 1, 0, 'C', 1);
		$this->Cell(20, 5, '30.12.2016', 1, 0, 'C', 1);
		$this->SetLineWidth(0.3);
		if($this->pageNr === 1)
		{
			$this->Ln();
			$this->Ln(1);
			$this->Cell(40, 8, ' Prof. Dr. Kautschuk', 'LTB', 0, 'L', 1);
			$this->Cell(120, 8, 'Einführung in die Naturheilkunde SoSe 2016', 'TB', 0, 'C', 1);
			$this->Cell(20, 8, 'FB0', 'RTB', 0, 'C', 1);
			$this->Ln();
			$header_form = new ilScanAssessmentPdfHeaderForm($this);
			$header_form->addMatriculationForm();
		}
		$this->Ln(5);
		$this->SetMargins(PDF_MARGIN_LEFT, $this->GetY(), PDF_MARGIN_RIGHT);
		$this->SetFont(PDF_DEFAULT_FONT, '', PDF_DEFAULT_FONT_SIZE, '', TRUE);
		$this->addMarkerAndQrCode();
	}

	/**
	 * Overwrites TCPDF Footer function
	 */
	public function Footer()
	{
		global $lng;
		$page = $lng->txt('page') . ' ' . $this->getAliasNumPage().'/'.$this->getAliasNbPages();
		$this->MultiCell(0, 00, 'University of Bla' . ' - ' . $page, 0, 'C', 0, 1, 0, $this->getPageHeight() - 10, true);
	}

	/**
	 * @deprecated  
	 */
	protected function disabled_addMarker()
	{
		$this->Line(PDF_TOPLEFT_SYMBOL_X, PDF_TOPLEFT_SYMBOL_Y, PDF_TOPLEFT_SYMBOL_X + PDF_TOPLEFT_SYMBOL_W , PDF_TOPLEFT_SYMBOL_Y , array('width' => 0.6));
		$this->Line(PDF_TOPLEFT_SYMBOL_X, PDF_TOPLEFT_SYMBOL_Y, PDF_TOPLEFT_SYMBOL_X, PDF_TOPLEFT_SYMBOL_Y + PDF_TOPLEFT_SYMBOL_W);
		
		$bottom_y = $this->getPageHeight() + PDF_BOTTOMLEFT_SYMBOL_Y;
		$this->Line(PDF_TOPLEFT_SYMBOL_X, $bottom_y, PDF_TOPLEFT_SYMBOL_X + PDF_TOPLEFT_SYMBOL_W, $bottom_y);
		$this->Line(PDF_TOPLEFT_SYMBOL_X, $bottom_y, PDF_TOPLEFT_SYMBOL_X, $bottom_y - PDF_TOPLEFT_SYMBOL_W);

		$top_x = $this->getPageWidth() + PDF_BOTTOMLEFT_SYMBOL_Y;
		$top_y = PDF_TOPLEFT_SYMBOL_Y;
		$this->Line($top_x, $top_y, $top_x - PDF_TOPLEFT_SYMBOL_W, $top_y);
		$this->Line($top_x, $top_y, $top_x, $top_y + PDF_TOPLEFT_SYMBOL_W);
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