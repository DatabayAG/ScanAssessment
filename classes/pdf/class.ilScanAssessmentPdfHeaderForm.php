<?php
ilScanAssessmentPlugin::getInstance()->includeClass('class.ilScanAssessmentGlobalSettings.php');
/**
 * Class ilScanAssessmentPdfHeaderForm
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentPdfHeaderForm
{
	const NUMBER = 'X';
	const SPACER = '-';

	/**
	 * @var array
	 */
	protected $matriculation_positions = array();

	/**
	 * @var tcpdf
	 */
	protected $pdf;

	/**
	 * @var ilLanguage
	 */
	protected $lng;

	/**
	 * @var ilScanAssessmentPdfMetaData
	 */
	protected $metadata;

	/**
	 * @var ilScanAssessmentGlobalSettings
	 */
	protected $global_settings;

	/**
	 * @var
	 */
	protected $pdf_mode;

	/**
	 * ilScanAssessmentPdfHeaderForm constructor.
	 * @param tcpdf $pdf
	 * @param ilScanAssessmentPdfMetaData $metadata
	 * @param $pdf_mode
	 */
	public function __construct($pdf, $metadata, $pdf_mode)
	{
		global $lng;
		
		$this->lng				= $lng;
		$this->pdf				= $pdf;
		$this->metadata			= $metadata;
		$this->pdf_mode			= $pdf_mode;
		$this->global_settings	= ilScanAssessmentGlobalSettings::getInstance(); 
	}

	/**
	 * @return array
	 */
	public function getMatriculationPositions()
	{
		return $this->matriculation_positions;
	}

	/**
	 * @param $columns
	 * @return array
	 */
	protected function calculateCellWidth($columns)
	{
		$width = 180;
		if($columns < 7)
		{
			$columns = 7;
		}
		$second_column = $columns * PDF_CELL_MARGIN;
		$first_column  = $width - $second_column;
		return array($first_column, $second_column, $width);
	}

	/**
	 * 
	 */
	protected function insertIdentificationHead()
	{
		$this->pdf->Ln(1);
		if(strlen($this->metadata->getStudentMatriculation()) > 0)
		{
			$matriculation = '(' . $this->metadata->getStudentMatriculation() . ')';
		}
		else
		{
			$matriculation = '';
		}
		$this->pdf->SetFont(PDF_DEFAULT_FONT, '', PDF_DEFAULT_FONT_SIZE_HEAD_NAME);
		$this->pdf->Cell(0, 8, ' ' .  $this->metadata->getStudentName(). ' ' . $matriculation, 1, 0, 'C', 1);
		$this->pdf->SetFont(PDF_DEFAULT_FONT, '', PDF_DEFAULT_FONT_SIZE);
		$this->pdf->Ln();
	}


	/**
	 * @param $columns
	 */
	protected function appendFirstAndSurnameBoxes($columns)
	{
		list($first_column, $second_column, $width) = $this->calculateCellWidth($columns);
		if($this->shouldMatriculationMatrixBePrinted() && $columns > 0)
		{
			$this->insertFirstAndSurnameBoxes($columns, $first_column);
			if($this->pdf_mode == 1)
			{
				$y = 41;
			}
			else
			{
				$y = 37;
			}
			$this->pdf->MultiCell($second_column, 52, ' ' . $this->lng->txt('matriculation') . ': ', 1, 'C', 0, 1, $first_column + 15, $y, true);
		}
		else
		{
			$this->insertFirstAndSurnameBoxes($columns, $width / 2);
		}
	}

	/**
	 * @param $columns
	 * @param $width
	 */
	protected function insertFirstAndSurnameBoxes($columns, $width)
	{
		$this->pdf->Ln(1);
		if( ! $this->metadata->isNoNameField() )
		{
			$this->pdf->MultiCell($width, 26, ' ' . $this->lng->txt('firstname') . ': ', 1, 'L', 1, 0, '', '', true);
			if($this->shouldMatriculationMatrixBePrinted() && $columns > 0)
			{
				$this->pdf->Ln();
			}
			$this->pdf->MultiCell($width, 26, ' ' . $this->lng->txt('lastname') . ': ', 1, 'L', 0, 1, '', '', true);
		}
	}

	/**
	 * 
	 */
	protected function insertMatriculationTextField()
	{
		$this->pdf->MultiCell(180, 26, ' ' . $this->lng->txt('matriculation') . ': ', 1, 'L', 1, 0, '', '', true);
		$this->pdf->Ln();
	}

	/**
	 * 
	 */
	public function insertIdentification()
	{
		if($this->metadata->isNotPersonalised())
		{
			$format		= $this->global_settings->getMatriculationStyle();
			$columns	= strlen($format);
			$this->appendFirstAndSurnameBoxes($columns);

			if($this->shouldMatriculationMatrixBePrinted())
			{
				$this->addMatriculationMatrixForm($columns, $format);
			}
			else if($this->shouldMatriculationTextFieldBePrinted())
			{
				$this->insertMatriculationTextField();
			}
		}
		else
		{
			$this->insertIdentificationHead();
		}
		$this->saveHeaderHeightAndPage($this->pdf->GetY(), $this->pdf->getPage());
	}

	/**
	 * @param $columns
	 * @param $format
	 */
	protected function addMatriculationMatrixForm($columns, $format)
	{
			$positions	= array('head_row' => array(), 'value_rows' => array(), 'page' => $this->pdf->getPage());
			if($this->pdf_mode == 1)
			{
				$org_y = 51;
			}
			else
			{
				$org_y = 47;
			}
			$this->pdf->SetFont(PDF_DEFAULT_FONT, '', PDF_DEFAULT_FONT_SIZE_MATRICULATION);
			if($columns > 0)
			{
				for($i = 0; $i <= 9; $i++)
				{
					$y = $org_y + ($i * 4);
					$x = 186 - ($columns * 4);
					$this->pdf->MultiCell(5, 4, $i, 0, 'C', 0, 1, $x + 1.5, $y + 0.3, true);

					for($j = 0; $j <= $columns; $j++)
					{
						if($format[$j] === self::SPACER)
						{
							$spacing = 2;
						}
						else
						{
							$spacing = 0;
						}
						$x2 = ($x + 2) + (4 * ($j + 1)) + $spacing;
						$y2 = $y + PDF_CHECKBOX_MARGIN;

						if($format[$j] === self::NUMBER)
						{
							if($i === 0)
							{
								$this->pdf->Rect($x2 - 0.5, $y2 - 5, PDF_ANSWERBOX_W + 1, PDF_ANSWERBOX_H + 1, 'D');
								#$positions['head_row'][] = new ilScanAssessmentVector(new ilScanAssessmentPoint($x2 - 0.5, $y2 - 5), PDF_ANSWERBOX_W + 1);
								$positions['head_row'][] = array( 'x' => $x2 - 0.5, 'y' => $y2 - 5, 'w' => PDF_ANSWERBOX_W);
							}
							$this->pdf->Rect($x2, $y2, PDF_ANSWERBOX_W, PDF_ANSWERBOX_H, 'D');
							#$positions['value_rows'][$j][$i] = new ilScanAssessmentVector(new ilScanAssessmentPoint($x2, $y2), PDF_ANSWERBOX_W);
							$x_relative = $x2 - PDF_TOPLEFT_SYMBOL_X;
							$y_relative = $y2 - PDF_TOPLEFT_SYMBOL_Y;
							$positions['value_rows'][$j][$i] = array( 'x' => $x_relative, 'y' => $y_relative, 'w' => PDF_ANSWERBOX_W);
						}
						if($format[$j] === self::SPACER && $i === 0)
						{
							$this->pdf->Line($x2 - 2, $y2 - 3.2, $x2, $y2 - 3.2);
						}
					}
				}
			}
			$this->matriculation_positions = $positions;
			$this->saveMatriculationMatrixPositions($positions);
			$log = ilScanAssessmentLog::getInstance();
			$log->debug(print_r($positions));
			$this->pdf->SetFont(PDF_DEFAULT_FONT, '', PDF_DEFAULT_FONT_SIZE);
	}

	/**
	 * @param $positions
	 */
	protected function saveMatriculationMatrixPositions($positions)
	{
		global $ilDB;

		$ilDB->update('pl_scas_pdf_data',
			array(
				'matriculation_matrix'	=> array('text', json_encode($positions)),
			),
			array(
				'pdf_id' => array('integer', $this->metadata->getIdentificationObject()->getPdfId())
			));
		
	}

	/**
	 * @param $height
	 * @param $page
	 */
	protected function saveHeaderHeightAndPage($height, $page)
	{
		global $ilDB;

		$ilDB->update('pl_scas_pdf_data',
			array(
				'header_height'	=> array('integer', (int)$height),
				'header_page'	=> array('integer', (int)$page),
			),
			array(
				'pdf_id' => array('integer', $this->metadata->getIdentificationObject()->getPdfId())
			));

	}

	/**
	 * @return bool
	 */
	protected function shouldMatriculationMatrixBePrinted()
	{
		return $this->metadata->isMatriculationCode() == PRINT_MATRICULATION_FORM && $this->metadata->getMatriculationStyle() == MATRICULATION_MATRIX;
	}

	/**
	 * @return bool
	 */
	protected function shouldMatriculationTextFieldBePrinted()
	{
		return $this->metadata->isMatriculationCode() == PRINT_MATRICULATION_FORM && $this->metadata->getMatriculationStyle() == MATRICULATION_TEXT;
	}
}