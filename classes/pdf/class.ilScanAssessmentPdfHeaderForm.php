<?php

/**
 * Class ilScanAssessmentPdfMatriculationForm
 */
class ilScanAssessmentPdfHeaderForm
{
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
	 * ilScanAssessmentPdfHeaderForm constructor.
	 * @param tcpdf $pdf
	 */
	public function __construct($pdf)
	{
		global $lng;
		
		$this->lng	= $lng;
		$this->pdf	= $pdf;
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

	protected function insertFirstAndSurnameBoxes($columns, $width)
	{
		$this->pdf->Ln(1);
		$this->pdf->MultiCell($width, 26, ' ' . $this->lng->txt('firstname') . ': ', 1, 'L', 1, 0, '', '', true);
		if($columns > 0)
		{
			$this->pdf->Ln();
		}
		$this->pdf->MultiCell($width, 26, ' ' . $this->lng->txt('lastname') . ': ', 1, 'L', 0, 1, '', '', true);
	}

	/**
	 * @param $columns
	 */
	protected function appendFirstAndSurnameBoxes($columns)
	{
		list($first_column, $second_column, $width) = $this->calculateCellWidth($columns);

		if($columns > 0)
		{
			$this->insertFirstAndSurnameBoxes($columns, $first_column);
			$this->pdf->MultiCell($second_column, 52, ' ' . $this->lng->txt('matriculation') . ': ', 1, 'C', 0, 1, $first_column + 15, 35, true);
		}
		else
		{
			$this->insertFirstAndSurnameBoxes($columns, $width / 2);
		}
	}

	/**
	 * @param string $format
	 */
	public function addMatriculationForm($format = 'XX-X-XXXXX')
	{

		$columns	= strlen($format);
		$positions	= array('head_row' => array(), 'value_rows' => array());
		$this->appendFirstAndSurnameBoxes($columns);
		$this->pdf->SetFont(PDF_DEFAULT_FONT,'', PDF_DEFAULT_FONT_SIZE_MATRICULATION);
		if($columns > 0)
		{
			for($i=0; $i<=9; $i++)
			{
				$y = 45 + ($i * 4);
				$x = 186 - ($columns * 4);
				$this->pdf->MultiCell(5, 4, $i, 0, 'C', 0, 1, $x + 1.5, $y + 0.3 , true);

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
					$x2 = ($x + 2) + (4 * ($j + 1)) + $spacer;
					$y2 = $y + PDF_CHECKBOX_MARGIN;

					if($format[$j] === 'X')
					{
						if($i === 0)
						{
							$this->pdf->Rect($x2 - 0.5, $y2 - 5, PDF_ANSWERBOX_W + 1, PDF_ANSWERBOX_H + 1, 'D');
							$positions['head_row'][] = new ilScanAssessmentVector(new ilScanAssessmentPoint($x2 - 0.5, $y2 - 5), PDF_ANSWERBOX_W +1);
						}
						$this->pdf->Rect($x2, $y2, PDF_ANSWERBOX_W, PDF_ANSWERBOX_H, 'D');
						$positions['value_rows'][$j][$i] = new ilScanAssessmentVector(new ilScanAssessmentPoint($x2, $y2), PDF_ANSWERBOX_W);
					}
					if($format[$j] === '-' && $i === 0)
					{
						$this->pdf->Line($x2 - 2, $y2 - 3.2, $x2, $y2 - 3.2);
					}
				}
			}
		}
		$this->matriculation_positions = $positions;
		$this->pdf->SetFont(PDF_DEFAULT_FONT,'', PDF_DEFAULT_FONT_SIZE);
	}
}