<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/libs/fpdi/fpdi.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/pdf/class.ilScanAssessmentPdfConstants.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/geometry/class.ilScanAssessmentVector.php';

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


	protected $matriculation_information = array();

	/**
	 * @return array
	 */
	public function getMatriculationInformation()
	{
		return $this->matriculation_information;
	}

	/**
	 * @param array $matriculation_information
	 */
	public function setMatriculationInformation($matriculation_information)
	{
		$this->matriculation_information = $matriculation_information;
	}

	protected function addMarkerAndQrCode()
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

				// Schnittmarken
				$circleStyle = array(
					'width' => 0.25,
					'cap'   => 'butt',
					'join'  => 'miter',
					'dash'  => 0,
					'color' => array(0, 0, 0)
				);

				$innerColor = array(0, 0, 0);
				$this->Circle(PDF_TOPLEFT_SYMBOL_X, PDF_TOPLEFT_SYMBOL_Y, PDF_TOPLEFT_SYMBOL_W, 0, 360, 'DF', $circleStyle, $innerColor);
				$this->Circle(PDF_TOPLEFT_SYMBOL_X, $this->getPageHeight() + PDF_BOTTOMLEFT_SYMBOL_Y, PDF_TOPLEFT_SYMBOL_W, 0, 360, 'DF', $circleStyle, $innerColor);
				#$this->addMarker();
			}
		}
	}

	/**
	 * @param string $format
	 */
	protected function addMatriculationForm($format = 'X-XXX-X-XX')
	{
		$columns	= strlen($format);
		$positions	= array('head_row' => array(), 'value_rows' => array());
		if($columns > 0)
		{
			$this->Ln(1);
			$this->MultiCell(55, 25, ' Vorname: ', 1, 'L', 1, 0, '', '', true);
			$this->Ln();
			$this->MultiCell(55, 25, ' Nachname: ', 1, 'L', 0, 1, '', '', true);
			$this->MultiCell(125, 50, 'Prüfungsteilnehmer-ID für den Prüfungsbogen: ', 1, 'C', 0, 1, 70, 34, true);

			for($i=0; $i<=9; $i++)
			{
				$y = 44 + ($i * 4);
				$x = 188 - ($columns * 4);
				$this->MultiCell(5, 4, $i, 0, 'C', 0, 1, $x, $y , true);

				for($j=0; $j <= $columns; $j++)
				{
					if($format[$j] === '-')
					{
						$spacer = 2;
					}
					else
					{
						$spacer = 0;
					}
					if($format[$j] === 'X')
					{
						$x2 = ($x + 2) + (4 * ($j + 1)) + $spacer;
						$y2 = $y + PDF_CHECKBOX_MARGIN;
						if($i === 0)
						{
							$this->Rect($x2 - 0.5, $y2 - 5, PDF_ANSWERBOX_W + 1, PDF_ANSWERBOX_H +1, 'D');
							$positions['head_row'][] = new ilScanAssessmentVector(new ilScanAssessmentPoint($x2 - 0.5, $y2 - 5), PDF_ANSWERBOX_W +1);
						}
						$this->Rect($x2, $y2, PDF_ANSWERBOX_W, PDF_ANSWERBOX_H, 'D');
						$positions['value_rows'][$j][$i] = new ilScanAssessmentVector(new ilScanAssessmentPoint($x2, $y2), PDF_ANSWERBOX_W);
					}
					if($format[$j] === '-' && $i === 0)
					{
						$this->Rect(($x + 2) + (4 * ($j + 1)), $y - 3, 2, 0, 'D');
					}
				}
			}
		}
		$this->setMatriculationInformation($positions);
	}
	/**
	 * Overwrites TCPDFS Header function
	 */
	public function Header() 
	{
		$this->pageNr++;
		$this->SetY(20);
		$this->SetFont(PDF_DEFAULT_FONT, '', PDF_DEFAULT_FONT_SIZE_HEAD, '', TRUE);
		$this->SetTextColor(0);
		$this->SetDrawColor(0, 0, 0);
		$this->SetFillColor(255, 255, 255);
		$this->SetLineWidth(0.3);
		$this->Ln();
		$this->Cell(30, 5, 'TITLE', 1, 0, 'C', 1);
		$this->Cell(120, 5, 'University of BLAAAA', 1, 0, 'C', 1);
		$this->Cell(30, 5, 'TITLE', 1, 0, 'C', 1);
		$this->Ln();

		if($this->pageNr === 1)
		{
			$this->Cell(40, 8, 'Prof. Dr. Kautschuk', 'LTB', 0, 'C', 1);
			$this->Cell(120, 8, 'Einführung in die Naturheilkunde 2016', 'TB', 0, 'C', 1);
			$this->Cell(20, 8, 'FB47 1/3', 'RTB', 0, 'C', 1);
			$this->Ln();
			$this->addMatriculationForm();
			$this->Ln(10);
			$this->SetMargins(PDF_MARGIN_LEFT, $this->GetY(), PDF_MARGIN_RIGHT);
		}

		$this->addMarkerAndQrCode();
		return;
	}

	/**
	 * Overwrites TCPDFS Footer function
	 */
	public function Footer()
	{
		$this->MultiCell(0, 00, 'University of Bla', 0, 'C', 0, 1, 0, $this->getPageHeight() - 10, true);
	}

	protected function addMarker()
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