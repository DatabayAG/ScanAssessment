<?php

require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/class.ilScanAssessmentScanner.php';

class ilScanAssessmentAnswerScanner extends ilScanAssessmentScanner
{
	const MIN_VALUE_BLACK	= 180;
	const MIN_MARKED_AREA		= 0.05;
	const MARKED_AREA_CHECKED	= 0.3;

	/**
	 * ilScanAssessmentAnswerScanner constructor.
	 * @param $fn
	 */
	public function __construct($fn)
	{
		parent::__construct($fn);
	}

	public function scanImage($marker_positions, $qr_position)
	{
		$im = $this->getImage();
		$this->findAnswers($im, $marker_positions, $qr_position);
	}

	/**
	 * @param $im
	 * @param ilScanAssessmentVector[] $marker_positions 
	 * @param $qr_position
	 */
	protected function findAnswers(&$im, $marker_positions, $qr_position) 
	{
		$answers = [
			['qid' => 450, 'aid' => -1, 'a_text' => 'Der Würfel ist gefallen.',					'x' =>  15, 'y' => '54.04861'	],
			['qid' => 450, 'aid' => -1, 'a_text' => 'Die Entscheidung ist getroffen.',			'x' =>  15, 'y' => '60.458332'	],
			['qid' => 450, 'aid' => -1, 'a_text' => 'Das ist mein Urteil.',						'x' =>  15, 'y' => '66.868054'	],
			['qid' => 450, 'aid' => -1, 'a_text' => 'So soll es sein.', 						'x' =>  15, 'y' => '73.277776'	],
			['qid' => 452, 'aid' => -1, 'a_text' => 'Picasso',									'x' =>  15, 'y' => '103.916664'	],
			['qid' => 452, 'aid' => -1, 'a_text' => 'van Gogh', 								'x' =>  15, 'y' => '110.326386'	],
			['qid' => 452, 'aid' => -1, 'a_text' => 'Monet', 									'x' =>  15, 'y' => '116.736108'	],
			['qid' => 452, 'aid' => -1, 'a_text' => 'Leonardo da Vinci', 						'x' =>  15, 'y' => '123.14583'	],
			['qid' => 454, 'aid' => -1, 'a_text' => 'Geschäft mit beschränkter Haftung', 		'x' =>  15, 'y' => '153.784718'	],
			['qid' => 454, 'aid' => -1, 'a_text' => 'Gesellschaft mit bekannter Haftung', 		'x' =>  15, 'y' => '160.19444'	],
			['qid' => 454, 'aid' => -1, 'a_text' => 'Gesellschafter mit beschränkter Haftung',	'x' =>  15, 'y' => '166.604162'	],
			['qid' => 454, 'aid' => -1, 'a_text' => 'Gesellschaft mit beschränkter Haftung', 	'x' =>  15, 'y' => '173.013884'	],
			['qid' => 456, 'aid' => -1, 'a_text' => 'Frankfurt / Oder',							'x' =>  15, 'y' => '203.652772'	],
			['qid' => 456, 'aid' => -1, 'a_text' => 'Fridingen am Fluß', 						'x' =>  15, 'y' => '210.062494'	],
			['qid' => 456, 'aid' => -1, 'a_text' => 'Flensburg', 								'x' =>  15, 'y' => '216.472216'	],
			['qid' => 456, 'aid' => -1, 'a_text' => 'Frankenberg', 								'x' =>  15, 'y' => '222.881938'	],
		];
		
		$a = 0;
		
 		$positions = array (
			'TOPLEFT' =>
				array (
					'x' => 20,
					'y' => 20,
					'w' => 5,
				),
			'BOTTOMLEFT' =>
				array (
					'x' => 20,
					'y' => 277.00008333333329,
					'w' => 5,
				),
			'BOTTOMRIGHT' =>
				array (
					'x' => 205.0001444444444,
					'y' => 292.00008333333329,
					'w' => 30,
				),
		);

		$original_position	= new ilScanAssessmentPoint($positions["TOPLEFT"]["x"], $positions["TOPLEFT"]["y"]);
		$scan				= new ilScanAssessmentPoint($marker_positions[0]->getPosition()->getX(),  $marker_positions[0]->getPosition()->getY());
		$correctedX	= ($qr_position["x"] - $scan->getX()) / ($positions["BOTTOMRIGHT"]["x"] - $original_position->getX());
		$correctedY	= ($marker_positions[1]->getPosition()->getY() - $scan->getY()) / ($positions["BOTTOMLEFT"]["y"] - $original_position->getY());
		$corrected	= new ilScanAssessmentPoint($correctedX, $correctedY);

		$mx = (($positions["BOTTOMRIGHT"]["x"] - $positions["TOPLEFT"]["x"]) / 2) * $corrected->getX();

		$im2 = $im;
		$answer_state = array();
		foreach($answers as $key => $value)
		{
			$this->analyseAnswer($im2, $value, $corrected, $scan);
		}
		$this->image_helper->drawTempImage($im2, 'bla.jpg');
		
		
		/*
		 * 	
	protected function findAnswers(&$im, $marker_positions, $qr_position, $positions, $filename) {
		 * 

		

		$fx = ($qrPosition["x"] - $mScanx) / ($positions["BOTTOMRIGHT"]["x"] - $mA4x);
		$fy = ($marker[1]["y"] - $mScany) / ($positions["BOTTOMLEFT"]["y"] - $mA4y);

		$mx = (($positions["BOTTOMRIGHT"]["x"] - $positions["TOPLEFT"]["x"]) / 2) * $fx;

		$this->schwelle = 150;

		$this->black  = imagecolorallocate($im, 0, 0, 0);
		$this->red    = imagecolorallocate($im, 255, 0, 0);
		$this->yellow = imagecolorallocate($im, 255, 255, 100);
		$this->blue   = imagecolorallocate($im, 0, 0, 255);
		$this->gray   = imagecolorallocate($im, 100, 100, 100);

		$answers = array();
		foreach($positions["circlepositions"] as $questionNumber => $positionline)
		{
			$answers[$questionNumber] = array();
			$this->processAnswerLine($answers, $positionline, $mx, $mA4x, $mA4y, $fx, $fy, $mScanx, $mScany, $filename, $questionNumber, $im);
		}
		if(isset($positions["KRightpositions"]))
		{
			foreach($positions["KRightpositions"] as $questionNumber => $positionline)
			{
				$this->processAnswerLine($answers, $positionline, $mx, $mA4x, $mA4y, $fx, $fy, $mScanx, $mScany, $filename, $questionNumber, $im, "R");
				$this->processAnswerLine($answers, $positions["KWrongpositions"][$questionNumber], $mx, $mA4x, $mA4y, $fx, $fy, $mScanx, $mScany, $filename, $questionNumber, $im, "W");
			}
		}
#vd($answers);exit;
		return $answers;
*/
	}

	/**
	 * @param $value
	 * @param ilScanAssessmentPoint $corrected
	 * @param ilScanAssessmentPoint $scan
	 * @return int
	 */
	protected function analyseAnswer($im2, $value, $corrected, $scan)
	{
		$answer_x     = ($value['x'] * $corrected->getX()) + $scan->getX();
		$answer_y     = ($value['y'] * $corrected->getY()) - 20;
		$first_point  = new ilScanAssessmentPoint($answer_x + 1, $answer_y + 2);
		$second_point = new ilScanAssessmentPoint($answer_x + (5 * $corrected->getX()) + 1, $answer_y + (5 * $corrected->getY()) + 2);
		#$this->processAnswerLine($answers, $postion , $mx, $mA4x, $mA4y, $fx, $fy, $mScanx, $mScany, '/tmp/', 0, $im);
		return $this->isCheckboxMarked($im2, $first_point, $second_point, true);
	}

	/**
	 * @param      $im
	 * @param ilScanAssessmentPoint $first_point
	 * @param ilScanAssessmentPoint $second_point
	 * @param bool $mark
	 * @return int
	 */
	public function isCheckboxMarked(&$im, $first_point, $second_point, $mark=false) {

		$total_count	= 0;
		$marked_count	= 0;
		for($x = $first_point->getX(); $x < $second_point->getX(); $x++)
		{
			for($y = $first_point->getY(); $y < $second_point->getY(); $y++)
			{
				$total_count++;
				$gray = $this->image_helper->getGrey(new ilScanAssessmentPoint($x, $y));
				if($gray < self::MIN_VALUE_BLACK)
				{
					$marked_count++;
					if($mark)
					{
						$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x,$y), $this->image_helper->getPink());
					}
				}
			}
		}

		if($total_count > 0)
		{
			$r = 1 / $total_count * $marked_count;
			if($r >= self::MIN_MARKED_AREA)
			{
				if($r >= self::MARKED_AREA_CHECKED) // was 0.4
				{
					if($mark)
					{
						$this->image_helper->drawSquareFromTwoPoints($im, $first_point, $second_point, $this->image_helper->getGreen());
						$this->image_helper->drawSquareFromTwoPoints($im, new ilScanAssessmentPoint($first_point->getX() -1 ,$first_point->getY() -1), new ilScanAssessmentPoint($second_point->getX() +1 ,$second_point->getY() +1),  $this->image_helper->getGreen());
					}

					return 2;
				}
				if($mark)
				{
					$this->image_helper->drawSquareFromTwoPoints($im,  $first_point, $second_point,  $this->image_helper->getBlue());
					$this->image_helper->drawSquareFromTwoPoints($im, new ilScanAssessmentPoint($first_point->getX() -1 ,$first_point->getY() -1), new ilScanAssessmentPoint($second_point->getX() +1 ,$second_point->getY() +1), $this->image_helper->getBlue());
				}

				return 1;
			}
		}
//WHAT IS THIS CASE FOR?
		if($mark)
		{
			$this->image_helper->drawSquareFromTwoPoints($im,  $first_point, $second_point, $this->image_helper->getYellow());
		}

		return 0;
	}

	protected function processAnswerLine(&$answers, $positionline, $mx, $mA4x, $mA4y, $fx, $fy, $mScanx, $mScany, $filename, $questionNumber, &$im, $kquestion="") {

		$prefix = "";
		if($kquestion!="") {
			$prefix = "_". $kquestion;
		}

		$first = true;
		foreach($positionline as $position) {
			$cx = ($position["x"]-$mA4x)*$fx + $mScanx;
			$cy = ($position["y"]-$mA4y)*$fy + $mScany;
			$cwx = $position["w"]*$fx;
			$cwy = $position["h"]*$fy;
			if($first) {
				$minX = $cx; // - $cwx/3*2;
				$minY = $cy; // - $cwy/3*2;
			}
			$first = false;
		}
		$maxX = $cx  + $cwx ;// /3*2;
		$maxY = $cy  + $cwy ;// /3*2;
		$w = max(0,$maxX - $minX);
		$h = max(0,$maxY - $minY);
		if($w>0 && $h>0) {
			$im2 = imagecreatetruecolor($w, $h);
			imagecopy($im2, $im, 0, 0, $minX, $minY, $w, $h);
			$fn = $filename . '.answer_' . $questionNumber . $prefix. ".jpg";
			imagejpeg($im2, $fn, 90);
			chmod($fn, 0664);
		}

		foreach($positionline as $position) {
			$cx = ($position["x"]-$mA4x)*$fx + $mScanx;
			$cy = ($position["y"]-$mA4y)*$fy + $mScany;
			$cwx = $position["w"]*$fx;
			$cwy = $position["h"]*$fy;

			$start = $cwx*0.15;
			$start2 = $cwy*0.15;
			//$answers[$questionNumber][] = $this->testIfChecked($im, $cx-$cwx/2,$cy-$cwy/2,$cx+$cwx/2,$cy+$cwy/2);
			#$res = $this->testIfChecked2($im, $cx+$start, $cy+$start2, $cx+$cwx-$start, $cy+$cwy-$start2, true);

			if($kquestion=="") {
				$answers[$questionNumber][] = ($res == 1);
			} else {
				$answers[$questionNumber][$kquestion][] = ($res == 1);
			}

		}
		if($w>0 && $h>0) {
			imagecopy($im2, $im, 0, 0, $minX, $minY, $w, $h);
			$fn = $filename . '.answerprocessed_' . $questionNumber . $prefix.".jpg";
			imagejpeg($im2, $fn, 90);
			chmod($fn, 0664);

			$im2 = imagecreatetruecolor($minX-$mScanx,$h);
			imagecopy($im2, $im, 0,0, $mScanx,$minY, $minX-$mScanx,$h);
			$fn = $filename . '.answerprocessed_' . $questionNumber .$prefix. "_before.jpg";
			imagejpeg($im2, $fn, 90);
			chmod($fn, 0664);

			#vd($mx-($minX+$w));
			#vd(array($mx, $minX, $w));
			$im2 = imagecreatetruecolor($mx-($minX+$w),$h);
			imagecopy($im2, $im, 0,0, ($minX+$w),$minY, $mx-($minX+$w),$h);
			$fn = $filename . '.answerprocessed_' . $questionNumber . $prefix."_after.jpg";
			imagejpeg($im2, $fn, 90);
			chmod($fn, 0664);

		}
	}

}