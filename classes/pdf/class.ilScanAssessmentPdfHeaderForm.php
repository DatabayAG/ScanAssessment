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
	 * @param string $format
	 */
	public function addMatriculationForm($format = 'XXX-XXX-XXX-XX')
	{
		$columns	= strlen($format);
		$positions	= array('head_row' => array(), 'value_rows' => array());
		if($columns > 0)
		{
			$this->pdf->Ln(1);
			$this->pdf->MultiCell(55, 25, ' ' . $this->lng->txt('firstname') . ': ', 1, 'L', 1, 0, '', '', true);
			$this->pdf->Ln();
			$this->pdf->MultiCell(55, 25, ' ' . $this->lng->txt('lastname') . ': ', 1, 'L', 0, 1, '', '', true);
			$this->pdf->MultiCell(125, 50,' ' . $this->lng->txt('exam_student_id') . ': ', 1, 'C', 0, 1, 70, 34, true);

			for($i=0; $i<=9; $i++)
			{
				$y = 44 + ($i * 4);
				$x = 188 - ($columns * 4);
				$this->pdf->MultiCell(5, 4, $i, 0, 'C', 0, 1, $x, $y , true);

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
							$this->pdf->Rect($x2 - 0.5, $y2 - 5, PDF_ANSWERBOX_W + 1, PDF_ANSWERBOX_H + 1, 'D');
							$positions['head_row'][] = new ilScanAssessmentVector(new ilScanAssessmentPoint($x2 - 0.5, $y2 - 5), PDF_ANSWERBOX_W +1);
						}
						$this->pdf->Rect($x2, $y2, PDF_ANSWERBOX_W, PDF_ANSWERBOX_H, 'D');
						$positions['value_rows'][$j][$i] = new ilScanAssessmentVector(new ilScanAssessmentPoint($x2, $y2), PDF_ANSWERBOX_W);
					}
					if($format[$j] === '-' && $i === 0)
					{
						$this->pdf->Rect(($x + 2) + (4 * ($j + 1)), $y - 3, 2, 0, 'D');
					}
				}
			}
		}
		$this->matriculation_positions = $positions;
	}

}