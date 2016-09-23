<?php

require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/class.ilScanAssessmentScanner.php';

class ilScanAssessmentAnswerScanner extends ilScanAssessmentScanner
{

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
			["qid"=>"450","aid"=>-1,"a_text"=>"Der Würfel ist gefallen.","x"=>15,"y"=>54.04861],
			["qid"=>"450","aid"=>-1,"a_text"=>"Die Entscheidung ist getroffen.","x"=>15,"y"=>60.458332],
			["qid"=>"450","aid"=>-1,"a_text"=>"Das ist mein Urteil.","x"=>15,"y"=>66.868054],
			["qid"=>"450","aid"=>-1,"a_text"=>"So soll es sein.","x"=>15,"y"=>73.277776],
			["qid"=>"452","aid"=>-1,"a_text"=>"Picasso","x"=>15,"y"=>103.916664],
			["qid"=>"452","aid"=>-1,"a_text"=>"van Gogh","x"=>15,"y"=>110.326386],
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

		$mA4x = $positions["TOPLEFT"]["x"];
		$mA4y = $positions["TOPLEFT"]["y"];

		$mScanx = $marker_positions[0]->getPosition()->getX();
		$mScany = $marker_positions[0]->getPosition()->getY();

		$fx = ($qr_position["x"] - $mScanx) / ($positions["BOTTOMRIGHT"]["x"] - $mA4x);
		$fy = ($marker_positions[1]->getPosition()->getY() - $mScany) / ($positions["BOTTOMLEFT"]["y"] - $mA4y);

		$mx = (($positions["BOTTOMRIGHT"]["x"] - $positions["TOPLEFT"]["x"]) / 2) * $fx;
		

		$im2 = $im;
		
		foreach($answers as $key => $value)
		{
			$answer_x = ($value['x'] * $fx) + $mScanx;
			$answer_y = ($value['y'] * $fy) - 20;	
			#$answer_x = $value['x'];
			#$answer_y = $value['y'];
			$postion = array('x' => $answer_x, 'y' => $answer_y, 'w' => 5 * $fx, 'h' => 5 * $fy);

			#$this->processAnswerLine($answers, $postion , $mx, $mA4x, $mA4y, $fx, $fy, $mScanx, $mScany, '/tmp/', 0, $im);
			$this->testIfChecked2($im2, $answer_x,$answer_y, $answer_x + (5 * $fx), $answer_y + (5 * $fy), true);
		}

		$this->image_helper->drawTempImage($im2, 'bla.jpg');
		#$this->processAnswerLine($answers, $postion , $mx, $mA4x, $mA4y, $fx, $fy, $mScanx, $mScany, '', 0, $im);
		
		
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

	public function testIfChecked2(&$im, $x1,$y1, $x2, $y2, $mark=false) {
		$allCount    = 0;
		$markedCount = 0;

		for($x = $x1; $x < $x2; $x++)
		{
			for($y = $y1; $y < $y2; $y++)
			{
				$allCount++;
				$gray = $this->image_helper->getGrey(new ilScanAssessmentPoint($x, $y));
				if($gray < 180)
				{
					$markedCount++;
					if($mark)
					{
						$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x,$y), '0xff00ff');
					} 
				}
			}
		}

		if($allCount > 0)
		{
			$r = 1 / $allCount * $markedCount;
			if($r >= 0.05)
			{
				if($r >= 0.2) // was 0.4
				{
					if($mark)
					{
						$this->image_helper->drawSquareFromTwoPoints($im, new ilScanAssessmentPoint($x1,$y1), new ilScanAssessmentPoint($x2,$y2), '0xff0000');
						$this->image_helper->drawSquareFromTwoPoints($im, new ilScanAssessmentPoint($x1 -1 ,$y1 -1), new ilScanAssessmentPoint($x2 +1 ,$y2 +1), '0xff0000');
					}

					return 2;
				}
				if($mark)
				{
					$this->image_helper->drawSquareFromTwoPoints($im, new ilScanAssessmentPoint($x1,$y1), new ilScanAssessmentPoint($x2,$y2), '0x00ff00');
					$this->image_helper->drawSquareFromTwoPoints($im, new ilScanAssessmentPoint($x1 -1 ,$y1 -1), new ilScanAssessmentPoint($x2 +1 ,$y2 +1), '0x00ff00');
				}

				return 1;
			}
		}

		if($mark)
		{
			$this->image_helper->drawSquareFromTwoPoints($im, new ilScanAssessmentPoint($x1,$y1), new ilScanAssessmentPoint($x2,$y2), '0xffff00');
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