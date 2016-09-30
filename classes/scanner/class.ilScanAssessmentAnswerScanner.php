<?php

require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/class.ilScanAssessmentScanner.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/class.ilScanAssessmentCheckBoxElement.php';

class ilScanAssessmentAnswerScanner extends ilScanAssessmentScanner
{
	const MIN_VALUE_BLACK		= 180;
	const MIN_MARKED_AREA		= 0.05;
	const MARKED_AREA_CHECKED	= 0.3;
	const BOX_SIZE				= 5;
	
	protected $checkbox_container = array();
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
			['qid' => 450, 'aid' => -1, 'a_text' => 'Der Würfel ist gefallen.',					'x' =>  50, 'y' => '84.166666'	],
			#['qid' => 450, 'aid' => -1, 'a_text' => 'Die Entscheidung ist getroffen.',			'x' =>  50, 'y' => '106.576388'	],
			#['qid' => 450, 'aid' => -1, 'a_text' => 'Das ist mein Urteil.',						'x' =>  50, 'y' => '63.98611'	],
			#['qid' => 450, 'aid' => -1, 'a_text' => 'So soll es sein.', 						'x' =>  50, 'y' => '71.395832'	],
			#['qid' => 452, 'aid' => -1, 'a_text' => 'Picasso',									'x' =>  50, 'y' => '107.444442'	],
			#['qid' => 452, 'aid' => -1, 'a_text' => 'van Gogh', 								'x' =>  50, 'y' => '114.854164'	],
			#['qid' => 452, 'aid' => -1, 'a_text' => 'Monet', 									'x' =>  50, 'y' => '142.08333'	],
			#['qid' => 452, 'aid' => -1, 'a_text' => 'Leonardo da Vinci', 						'x' =>  50, 'y' => '149.493052'	],
			#['qid' => 454, 'aid' => -1, 'a_text' => 'Geschäft mit beschränkter Haftung', 		'x' =>  50, 'y' => '156.902774'	],
			#['qid' => 454, 'aid' => -1, 'a_text' => 'Gesellschaft mit bekannter Haftung', 		'x' =>  50, 'y' => '164.312496'	],
			#['qid' => 454, 'aid' => -1, 'a_text' => 'Gesellschafter mit beschränkter Haftung',	'x' =>  50, 'y' => '191.541662'	],
			#['qid' => 454, 'aid' => -1, 'a_text' => 'Gesellschaft mit beschränkter Haftung', 	'x' =>  50, 'y' => '198.951384'	],
			#['qid' => 456, 'aid' => -1, 'a_text' => 'Frankfurt / Oder',							'x' =>  50, 'y' => '206.361106'	],
			#['qid' => 456, 'aid' => -1, 'a_text' => 'Fridingen am Fluß', 						'x' =>  50, 'y' => '210.062494'	],
			#['qid' => 456, 'aid' => -1, 'a_text' => 'Flensburg', 								'x' =>  50, 'y' => '216.472216'	],
			#['qid' => 456, 'aid' => -1, 'a_text' => 'Frankenberg', 								'x' =>  50, 'y' => '222.881938'	],
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
		$correctedX	= ($scan->getX() / $original_position->getX());
		$correctedY	= ($scan->getY() / $original_position->getY());
		
		$v_org = sqrt(pow(($positions["BOTTOMLEFT"]["x"] * 1.33) - ($positions["TOPLEFT"]["x"] * 1.33), 2) + pow(($positions["BOTTOMLEFT"]["y"] * 1.33) - ($positions["TOPLEFT"]["y"] * 1.33), 2));
		$v_scan = sqrt(pow($marker_positions[1]->getPosition()->getX() - $marker_positions[0]->getPosition()->getX(), 2) + pow($marker_positions[1]->getPosition()->getY() - $marker_positions[0]->getPosition()->getY(), 2));
		$v = $v_scan / $v_org;
		$h_org = sqrt(pow(($positions["BOTTOMRIGHT"]["x"] * 1.33) - ($positions["BOTTOMLEFT"]["x"] * 1.33), 2) + pow(($positions["BOTTOMRIGHT"]["y"] * 1.33) - ($positions["BOTTOMLEFT"]["y"] * 1.33), 2));
		$h_scan = sqrt(pow($qr_position["x"] - $marker_positions[1]->getPosition()->getX(), 2) + pow($qr_position["x"] - $marker_positions[1]->getPosition()->getY(), 2));
		$h = $h_scan / $h_org;
		$corrected	= new ilScanAssessmentPoint($correctedX, $correctedY);

		$im2 = $im;
		$answer_state = array();
		$first = true;
		$drift_y = 0;
		foreach($answers as $key => $value)
		{
			$answer_x     = ($value['x'] * $corrected->getX()) + $scan->getX();
			$answer_y     = ($value['y'] * $corrected->getY()) + $scan->getY();

			$point_found_at = $this->scanCheckbox($im2, $value, $corrected, $scan);
			$drift_y = sqrt(pow($answer_y - $point_found_at->getY(), 2));
			$answer_y = $answer_y - $drift_y;
			$first_point  = new ilScanAssessmentPoint($answer_x, $answer_y);
			$second_point = new ilScanAssessmentPoint($answer_x + (5 * $corrected->getX()), $answer_y + (5 * $corrected->getY()));
			
			$checkbox = new ilScanAssessmentCheckBoxElement($first_point, $second_point, $this->image_helper);
			$this->checkbox_container[] = $checkbox;
			$checkbox->isMarked($im, true);
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
	 * @param $im2
	 * @param $value
	 * @param ilScanAssessmentPoint $corrected
	 * @param ilScanAssessmentPoint $scan
	 * @return ilScanAssessmentPoint
	 */
	protected function scanCheckbox($im2, $value, $corrected, $scan)
	{
		$answer_x = ($value['x'] * $corrected->getX()) + $scan->getX();
		$answer_y = ($value['y'] * $corrected->getY()) - ((self::BOX_SIZE * $corrected->getY()));
		
		for($x = $answer_x; $x < $answer_x + (12 * $corrected->getX()); $x++)
		{
			for($y =  $answer_y; $y < $answer_y + (12 * $corrected->getY()); $y++)
			{
				$left_top = new ilScanAssessmentPoint($x,$y);
				$lt = $this->image_helper->getGrey($left_top);
				$this->image_helper->drawPixel($im2, $left_top, $this->image_helper->getRed());
				if($lt < self::MIN_VALUE_BLACK)
				{
					if($this->detectSquare($im2, $corrected, $x, $y))
					{
						return $left_top;
					}
				}
			}
		}
		return new ilScanAssessmentPoint($answer_x,$answer_y);
	}

	/**
	 * @param $im2
	 * @param ilScanAssessmentPoint $corrected
	 * @param $x
	 * @param $y
	 * @return bool
	 */
	protected function detectSquare($im2, $corrected, $x, $y)
	{
		$right_top    = new ilScanAssessmentPoint($x + (self::BOX_SIZE * $corrected->getX()), $y);
		$left_bottom  = new ilScanAssessmentPoint($x, $y + (self::BOX_SIZE * $corrected->getY()));
		$right_bottom = new ilScanAssessmentPoint($x + (self::BOX_SIZE * $corrected->getX()), $y + (self::BOX_SIZE * $corrected->getY()));
		$rt           = $this->image_helper->getGrey($right_top) < self::MIN_VALUE_BLACK;
		$lb           = $this->image_helper->getGrey($left_bottom) < self::MIN_VALUE_BLACK;
		$rb           = $this->image_helper->getGrey($right_bottom) < self::MIN_VALUE_BLACK;

		if($rt === true && $lb === true && $rb === true)
		{
			$this->image_helper->drawSquareFromVector($im2, new ilScanAssessmentVector(new ilScanAssessmentPoint($x, $y), 10), $this->image_helper->getPink());
			$this->image_helper->drawSquareFromVector($im2, new ilScanAssessmentVector($right_top, 10), $this->image_helper->getPink());
			$this->image_helper->drawSquareFromVector($im2, new ilScanAssessmentVector($left_bottom, 10), $this->image_helper->getPink());
			$this->image_helper->drawSquareFromVector($im2, new ilScanAssessmentVector($right_bottom, 10), $this->image_helper->getPink());
			return true;
		}
		else
		{
			$this->image_helper->drawSquareFromVector($im2, new ilScanAssessmentVector(new ilScanAssessmentPoint($x + 2, $y + 2), 10), $this->image_helper->getBlue());
		}
		return false;
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