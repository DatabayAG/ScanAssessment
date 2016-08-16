<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/libs/qr_img0.50i/php/class.qr_img.php';
class ScanService  {

	/**
	 * @param $im
	 * @return resource
	 */
	public function removeBlackBorder($im) {

		for($y=imageSy($im)-1;$y>imageSy($im)-100;$y--) 
		{
			if($this->getGray($im, round(imageSx($im))/2,$y)>50) 
			{
				$im2 = imageCreateTrueColor(imageSx($im), $y);
				imageCopy($im2, $im, 0,0,0,0,imageSx($im), $y);
				break;
			}
		}

		for($x=imageSx($im)-1;$x>imageSx($im)-100;$x--) 
		{
			if($this->getGray($im, $x, round(imageSy($im))/2)>50) 
			{
				$im2 = imageCreateTrueColor($x, imageSy($im));
				imageCopy($im2, $im, 0,0,0,0,$x, imageSy($im));
				break;
			}
		}
		return $im2;
	}

	public function getMarkerPosition($fn, $withDebug=false) 
	{
		$im = imagecreatefromjpeg($fn);
		$im = $this->removeBlackBorder($im);

		$threshold = 150;
		$marker = $this->findMarker($im, FALSE, $withDebug, $threshold);
		if($marker===FALSE) 
		{
			$threshold = 200;
			$im = imagecreatefromjpeg($fn);
			$im = $this->removeBlackBorder($im);
			$marker = $this->findMarker($im, FALSE, $withDebug, $threshold);
		}

		if($withDebug)
		{
			imagejpeg($im, '/tmp/debug_image2.jpg');
			chmod( '/tmp/debug_image2.jpg', 0664);
			$im = imagecreatefromjpeg($fn);
			$im = $this->removeBlackBorder($im);
		}

		imageJpeg($im, $fn."-rotated.jpg", 90);
		chmod($fn."-rotated.jpg", 0664);
		return $marker;
	}

	public function getQRPosition($fn, $withDebug=false) {
		$im = imagecreatefromjpeg($fn);
		$im = $this->removeBlackBorder($im);
		$this->red = imageColorAllocate($im, 255,0,0);
		$this->green= imageColorAllocate($im, 0,255,0);
		$this->yellow = imagecolorallocate($im, 255,255,100);

		if($withDebug) {
			$qr1 = new qrDecode();
			$res = $qr1->findqr($im, FALSE, TRUE);
			$im = imagecreatefromjpeg($fn);
			$im = $this->removeBlackBorder($im);
		}
		$qr1 = new qrDecode();
		$res = $qr1->findqr($im, false, false);

		$this->QRImage = $res;
		if($res===FALSE) return FALSE;
		$foundAt = $qr1->foundQRat;
		return $foundAt;
	}

	public function decodeFromImage($im) {
		/*$qr2 = new \classes\qrDecode();
		$qr_data = $qr2->decode($im, TRUE);
		return $qr_data;*/
	}

	public function decodeQRCode($fn) {
		if(file_exists($fn."-rotated.jpg")) {
			$fn .= '-rotated.jpg';
		}
		$im = imagecreatefromjpeg($fn);
		$im = $this->removeBlackBorder($im);

		$this->red = imageColorAllocate($im, 255,0,0);
		$this->green= imageColorAllocate($im, 0,255,0);

		$schwelle = 150;
		$marker = $this->findMarker($im, false, false, $schwelle);
		if($marker==FALSE) {
			$schwelle = 200;
			$marker = $this->findMarker($im, false, false, $schwelle);
		}
		if(!is_array($marker)) {
			$this->cause = "Marker auf dem Blatt nicht gefunden.";
			return FALSE;
		}


		$qr1 = new qrDecode();
		$res = $qr1->findqr($im);

		if($res===FALSE) {
			$this->cause = "QR-Code auf dem Blatt nicht gefunden.";
			return FALSE;
		}
		$foundAt = $qr1->foundQRat;

		$qr2 = new qrDecode();
		$qr_data = $qr2->decode($res, true);

		$foundAt["x"] -= $qr2->cutX;
		$foundAt["x"] += $qr2->qrW/21*4;

		$fn2 = str_replace("-rotated.jpg", "", $fn);
		//$fn2 = \classes\FileUtils::addBeforeExt($fn2, "_processed");

		//file_get_contents("http://develop1/pc/index.php?action=newmsg&from=sys&msg=".urlencode($fn2));
		imageJpeg($im, $fn2, 90);
		imageRectangle($im, $marker[0]["x"], $marker[0]["y"], $foundAt["x"], $foundAt["y"], $this->red);


		if($qr_data===FALSE) {
			$this->cause = "QR-Code konnte nicht dekodiert werden.";
			return FALSE;
		}

		$this->processedImage = $im;
		$this->marker = $marker;
		$this->foundQRCodeAt = $foundAt;

		$data = array(
			"marker" => $marker,
			"foundQRCodeAt" => $foundAt,
		);
#vd($data);
#vd($fn.'.json');
		writeJson(str_replace("-rotated.jpg", "", $fn).'.json', $data);

		return $qr_data;

	}

	public function processParticipantName($filename, $positions) {
		if($this->processedImage=="") {
		//	$this->processedImage = $this->removeBlackBorder(\classes\Image::imageCreateFromFile($filename));
		}

		$data = readJson($filename.'.json');
		#vd($data);exit;
		$marker = $data["marker"];
		$qrPosition = $data["foundQRCodeAt"];

		$mA4x = $positions["TOPLEFT"]["x"];
		$mA4y = $positions["TOPLEFT"]["y"];

		$mScanx = $marker[0]["x"];
		$mScany = $marker[0]["y"];

		$fx = ($qrPosition["x"]-$mScanx) / ($positions["BOTTOMRIGHT"]["x"]-$mA4x);
		$fy = ($marker[1]["y"]-$mScany) / ($positions["BOTTOMLEFT"]["y"]-$mA4y);

		if(!isset($positions["nameposition"])) return;

		$np = $positions["nameposition"];
		#vd($positions);exit;
		#vd($data);
		#vd($np);

		#vd($fx);
		#vd(array($mA4x, $mA4y));
		$cx = ($np[0]["x"]-$mA4x)*$fx + $mScanx;
		$cy = ($np[0]["y"]-$mA4y)*$fy + $mScany;
		$cw = ($np[1]["x"]-$np[0]["x"])*$fx;
		$ch = ($np[1]["y"]-$np[0]["y"])*$fy;

		#vd(array($cx,$cy, $cw, $ch));

		$im2 = imageCreateTrueColor($cw, $ch);
		imageCopy($im2, $this->processedImage, 0, 0, $cx, $cy, $cw, $ch);

		$fn = $filename . '.name.jpg';
		imageJpeg($im2, $fn, 90);
		chmod($fn, 0664);
		return array($cx, $cy, $cw, $ch);
	}

	public function cutTextAnswer($filename, $positions, $questionId) {
		$data = readJson($filename.'.json');
		$this->findTextAnswers($this->processedImage, $data["marker"], $data["foundQRCodeAt"], $positions, $filename, $questionId);
	}

	public function findTextAnswers(&$im, $marker, $qrPosition, $positions, $filename, $questionId) {
		$mA4x = $positions["TOPLEFT"]["x"];
		$mA4y = $positions["TOPLEFT"]["y"];

		$mScanx = $marker[0]["x"];
		$mScany = $marker[0]["y"];

		$fx = ($qrPosition["x"]-$mScanx) / ($positions["BOTTOMRIGHT"]["x"]-$mA4x);
		$fy = ($marker[1]["y"]-$mScany) / ($positions["BOTTOMLEFT"]["y"]-$mA4y);

		$position = $positions["textpositions"];
		$cx = ($position["x1"] - $mA4x) * $fx + $mScanx;
		$cy = ($position["y1"] - $mA4y) * $fy + $mScany;
		$position["w"] = $position["x2"]-$position["x1"];
		$position["h"] = $position["y2"]-$position["y1"];
		$cwx = $position["w"] * $fx;
		$cwy = $position["h"] * $fy;
		$start = -5;
		$start2 = -5;

		$minX = $cx;
		$minY = $cy;
		$w = $cwx;
		$h = $cwy;

		$im2 = imageCreateTrueColor($w, $h);
		imageCopy($im2, $im, 0, 0, $minX, $minY, $w, $h);
		$fn = $filename . '.answer_' . $questionId . ".jpg";
		imageJpeg($im2, $fn, 90);
		chmod($fn, 0664);
	}

	/*public function processAnswers($filename, $positions, \Event\Model\EventParticipantModel $participant) {

		$data = readJson($filename.'.json');
		$answers = $this->findAnswers($this->processedImage, $data["marker"], $data["foundQRCodeAt"], $positions, $filename);
		writeJson($filename.'.answers.json', $answers);
		if(!file_Exists($filename.'.answers.json')) {
			\classes\FlashMessage::add("Antworten-Datei konnte nicht geschrieben werden", "danger");
		}
	}*/

	public function findAnswers(&$im, $marker, $qrPosition, $positions, $filename) {

		$mA4x = $positions["TOPLEFT"]["x"];
		$mA4y = $positions["TOPLEFT"]["y"];

		$mScanx = $marker[0]["x"];
		$mScany = $marker[0]["y"];

		$fx = ($qrPosition["x"]-$mScanx) / ($positions["BOTTOMRIGHT"]["x"]-$mA4x);
		$fy = ($marker[1]["y"]-$mScany) / ($positions["BOTTOMLEFT"]["y"]-$mA4y);

		$mx = (($positions["BOTTOMRIGHT"]["x"] - $positions["TOPLEFT"]["x"])/2 )* $fx;

		$this->schwelle = 150;

		$this->black = imagecolorallocate($im, 0,0,0);
		$this->red = imagecolorallocate($im, 255,0,0);
		$this->yellow = imagecolorallocate($im, 255,255,100);
		$this->blue = imagecolorallocate($im, 0,0,255);
		$this->gray = imagecolorallocate($im, 100,100,100);

		$answers = array();
		foreach($positions["circlepositions"] as $questionNumber => $positionline) {
			$answers[$questionNumber] = array();
			$this->processAnswerLine($answers, $positionline, $mx, $mA4x, $mA4y, $fx, $fy, $mScanx, $mScany, $filename, $questionNumber, $im);
		}
		if(isset($positions["KRightpositions"])) {
			foreach ($positions["KRightpositions"] as $questionNumber => $positionline) {
				$this->processAnswerLine($answers, $positionline, $mx, $mA4x, $mA4y, $fx, $fy, $mScanx, $mScany, $filename, $questionNumber, $im, "R");
				$this->processAnswerLine($answers, $positions["KWrongpositions"][$questionNumber], $mx, $mA4x, $mA4y, $fx, $fy, $mScanx, $mScany, $filename, $questionNumber, $im, "W");
			}
		}
#vd($answers);exit;
		return $answers;

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
			$im2 = imageCreateTrueColor($w, $h);
			imageCopy($im2, $im, 0, 0, $minX, $minY, $w, $h);
			$fn = $filename . '.answer_' . $questionNumber . $prefix. ".jpg";
			imageJpeg($im2, $fn, 90);
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
			$res = $this->testIfChecked2($im, $cx+$start, $cy+$start2, $cx+$cwx-$start, $cy+$cwy-$start2, true);

			if($kquestion=="") {
				$answers[$questionNumber][] = ($res == 1);
			} else {
				$answers[$questionNumber][$kquestion][] = ($res == 1);
			}

		}
		if($w>0 && $h>0) {
			imageCopy($im2, $im, 0, 0, $minX, $minY, $w, $h);
			$fn = $filename . '.answerprocessed_' . $questionNumber . $prefix.".jpg";
			imageJpeg($im2, $fn, 90);
			chmod($fn, 0664);

			$im2 = imageCreateTrueColor($minX-$mScanx,$h);
			imageCopy($im2, $im, 0,0, $mScanx,$minY, $minX-$mScanx,$h);
			$fn = $filename . '.answerprocessed_' . $questionNumber .$prefix. "_before.jpg";
			imageJpeg($im2, $fn, 90);
			chmod($fn, 0664);

			#vd($mx-($minX+$w));
			#vd(array($mx, $minX, $w));
			$im2 = imageCreateTrueColor($mx-($minX+$w),$h);
			imageCopy($im2, $im, 0,0, ($minX+$w),$minY, $mx-($minX+$w),$h);
			$fn = $filename . '.answerprocessed_' . $questionNumber . $prefix."_after.jpg";
			imageJpeg($im2, $fn, 90);
			chmod($fn, 0664);

		}
	}

	public function testIfChecked2(&$im, $x1,$y1, $x2, $y2, $mark=false) {
		$allCount = 0;
		$markedCount = 0;

		for($x=$x1;$x<$x2;$x++) {
			for($y=$y1;$y<$y2;$y++) {
				$allCount++;
				$gray = $this->getGray($im, $x, $y);
				if($gray<$this->schwelle) {
					$markedCount++;
					if($mark) imagesetpixel($im, $x,$y,$this->blue);
				}
			}
		}

		if($allCount>0) {
			$r = 1 / $allCount * $markedCount;
			if ($r >= 0.05) {
				if ($r >= 0.4) {
					if($mark) {
						imagerectangle($im, $x1, $y1, $x2, $y2, $this->red);
						imagerectangle($im, $x1 - 1, $y1 - 1, $x2 + 1, $y2 + 1, $this->red);
					}

					return 2;
				}
				if($mark) {
					imagerectangle($im, $x1, $y1, $x2, $y2, $this->green);
					imagerectangle($im, $x1 - 1, $y1 - 1, $x2 + 1, $y2 + 1, $this->green);
				}

				return 1;
			}
		}

		if($mark) {
			imagerectangle($im, $x1, $y1, $x2, $y2, $this->gray);
		}

		return 0;
	}

	public function testIfChecked(&$im, $x1,$y1, $x2, $y2) {
		//imagerectangle($im, $x1, $y1, $x2, $y2, $this->red);
		$w = $x2-$x1;
		$h = $y2-$y1;

		$mx = $x1+$w/2;
		$my = $y1+$h/2;

		$squaredRadius = ($h*0.7/2)*($h*0.7/2);

		$allCount = 0;
		$markedCount = 0;

		for($x=$mx-$w/2*0.7;$x<$mx+$w/2*0.7;$x++) {
			for($y=$my-$h/2*0.7;$y<$my+$h/2*0.7;$y++) {
				$dx = $mx-$x;
				$dy = $my-$y;
				$dist = $dx*$dx+$dy*$dy;
				if($dist<$squaredRadius) {
					$allCount++;
					$gray = $this->getGray($im, $x, $y);
					if($gray<$this->schwelle) {
						$markedCount++;
						imagesetpixel($im, $x,$y,$this->black);
					} else {
						#imagesetpixel($im, $x,$y,$this->green);
					}
				}
			}
		}

		if($allCount>0) {
			$r = 1 / $allCount * $markedCount;
			if ($r >= 0.05) {

				#imagestring($im, 2, $x1 + $w / 2, $y1 + $h / 2, round($r * 100), $this->red);

				if ($r >= 0.4) {
					imagerectangle($im, $x1, $y1, $x2, $y2, $this->red);
					imagerectangle($im, $x1 - 1, $y1 - 1, $x2 + 1, $y2 + 1, $this->red);
					return false;
				} else {

					imagerectangle($im, $x1, $y1, $x2, $y2, $this->green);
					imagerectangle($im, $x1 - 1, $y1 - 1, $x2 + 1, $y2 + 1, $this->green);
				}

				#imagearc($im, $x1 + $w / 2, $y1 + $h / 2, $w * 0.7, $h * 0.7, 0, 360, $this->green);

				return true;
			} else {
				#imagearc($im, $x1 + $w / 2, $y1 + $h / 2, $w * 0.7, $h * 0.7, 0, 360, $this->red);
				#imagestring($im, 2, $x1 + $w / 2, $y1 + $h / 2, round($r * 100), $this->red);
				return false;
			}
		}
		return false;
	}

	public function findMarker(&$im, $rotated=false, $debug=false, $threshold=150) {
		$white = imageColorAllocate($im, 255,255,255);

		$imSIK = imageCreateTrueColor(imageSx($im), imageSy($im));
		imageCopy($imSIK, $im, 0,0,0,0,imageSx($im), imageSy($im));

		$find = $this->findLeft($im, 'top', $debug, $threshold);

		if($find===FALSE) 
		{
			if($rotated) {
				echo "Problem!";
				return;
			}

			$im = imagerotate($im,180, $white);
			$find = $this->findLeft($im, 'top', $debug, $threshold);
		}

		if($find !== FALSE) {

			$find2 = $this->findExactLeft($im, $find, $threshold, $debug);
			if($rotated==true)  
			{
				imagerectangle($im, $find2["x"] - $find2["d"] / 2, $find2["y"] - $find2["d"] / 2, $find2["x"] + $find2["d"] / 2, $find2["y"] + $find2["d"] / 2, $this->yellow);
			}
			
			$find3 = $this->findLeft($im, 'bottom', $debug, $threshold);

			if($find3!==FALSE) {

				$find4 = $this->findExactLeft($im, $find3, $threshold, $debug);
				if($debug) imagerectangle($im, $find4["x"]-$find4["d"]/2, $find4["y"]-$find4["d"]/2,$find4["x"]+$find4["d"]/2, $find4["y"]+$find4["d"]/2, $this->yellow );

				$dx = $find4["x"] - $find2["x"];
				$dy = $find4["y"] - $find2["y"];

				$winkel = 180 / 3.141592 * atan($dx / $dy);

				if($rotated==false && abs($winkel)>0.05) {
					// ggf. ausrichten

					#vd($winkel);exit;
					$im = imagerotate($imSIK, -$winkel, $white);

					$randX = min($find4["x"], $find2["x"]);
					$randY = $find2["y"];
					$im2 = imagecreatetruecolor(imageSx($im)-$randX/2, imageSy($im)-$randY/2);
					$white = imagecolorallocate($im2, 255,255,255);
					imagefilledrectangle($im2, 0,0,imageSx($im2), imageSy($im2), $white);
					imageCopy($im2, $im, 0,0,$randX/2, $randY/2, imageSx($im2), imageSy($im2));
					$im = $im2;

					imageJpeg($im, '/tmp/debug_findmarker.jpg');

					return $this->findMarker($im, TRUE, $debug, $threshold);
				} else {

					imageJpeg($im, '/tmp/debug_findmarker.jpg');

					if($debug) imagerectangle($im, $find2["x"] - $find2["d"] / 2, $find2["y"] - $find2["d"] / 2, $find2["x"] + $find2["d"] / 2, $find2["y"] + $find2["d"] / 2, $this->yellow);
					if($debug) imagerectangle($im, $find4["x"]-$find4["d"]/2, $find4["y"]-$find4["d"]/2,$find4["x"]+$find4["d"]/2, $find4["y"]+$find4["d"]/2, $this->yellow );
					return array($find2, $find4);
				}

			} 
		} else {
			return false;
		}
		return false;
	}

	public function findExactLeft(&$im, $find, $threshold ,$debug=false) {

		$dx =  $find[1][0] - $find[0][0];
		$dy =  $find[1][1] - $find[0][1];

		$len = sqrt($dx*$dx + $dy*$dy);

		$mx = $find[0][0]+$dx/2;
		$my = $find[0][1]+$dy/2;

		$dx2 = $dy; // Rotation um 90 Grad in 2D
		$dy2 = -$dx;

		for($i=0.1;$i<2;$i+=0.1) {
			$gray = $this->getGray($im, $mx+$dx2*$i, $my+$dy2*$i);
			if($gray > $threshold) {
				$i1 = $i;
				break;
			}
		}
		for($i=0.1;$i<2;$i+=0.1) {
			$gray = $this->getGray($im, $mx-$dx2*$i, $my-$dy2*$i);
			if($gray > $threshold) {
				$i2 = $i;
				break;
			}
		}

		$x2 = $mx+$dx2*$i1;
		$y2 = $my+$dy2*$i1;

		$dx2 = -$x2 + ($mx-$dx2*$i2);
		$dy2 = -$y2 + ($my-$dy2*$i2);

		$len2 = sqrt($dx2*$dx2 + $dy2*$dy2);

		#$dx2 =

		if($debug) imageLine($im, $x2, $y2, $x2+$dx2, $y2+$dy2, $this->yellow);

		return array("x" => $x2+$dx2/2, "y" => $y2+$dy2/2, "d" => $len2);

		//vd(array($i1,$i2));

	}

	public function findLeft(&$im, $topbottom='top', $debug = false, $threshold=150) {
		
		$debug = false;
		$w = imageSx($im);
		$h = imageSy($im);

		$dx = 3;
		$dy = 3;

		$X = array();
		$found = false;
		$subDX = 0;
		$beginD = -1;
		for($d=55; $d < $w / 4 * 3; $d += $dy) {

			$len = $this->getLength($d, $im, $dx, $topbottom, $threshold, $debug);

			if($beginD==-1) $minD = $d/3*2;
			else $minD = $beginD;
			if( ($beginD == -1 && $len < $d/3*2 ) || 
				($beginD != -1 && $len - $subDX/2 <= $beginD) 
			) 
			{
				if($found == false) 
				{
					$found = true;
					$subDX = 0;
					$beginD = $len;
					$foundBegin = array($len, $d-$len);
					$foundLast = array($len, $d-$len);
				} 
				else 
				{
					$subDX += $dy;
					$foundLast = array($len, $d-$len);
				}
				if($debug) {
					if($topbottom=='top') {
						imageLine($im, 0 + $subDX / 2, $d - $subDX / 2, $len, $d - $len, $this->red);
					} else {
						imageLine($im, 0 + $subDX / 2, imageSy($im)- ($d - $subDX / 2), $len, imageSy($im)- ($d - $len), $this->red);
					}
				}
				$X[$d] = $len-$subDX/2;
			} 
			else 
			{
				if($found == true) {
					$foundEnd = array($foundBegin, $foundLast);

					$l = sqrt( ($foundBegin[0]-$foundLast[0])*($foundBegin[0]-$foundLast[0]) + ($foundBegin[1]-$foundLast[1])*($foundBegin[1]-$foundLast[1]) );

					if($l>0) {

						if (($w / $l) > 35 && ($w / $l) < 60) 
						{

							if($debug) 
							{
								imageLine($im, $foundBegin[0], $foundBegin[1], $foundLast[0], $foundLast[1], $this->yellow);
							}

							if($topbottom=="bottom") 
							{
								$foundEnd[0][1] = imageSy($im)-$foundEnd[0][1];
								$foundEnd[1][1] = imageSy($im)-$foundEnd[1][1];
							}
							return $foundEnd;
							break;
						}
					}
				}
				$found = false;
			}

		}

		return false;
	}

	/**
	 * @param        $d
	 * @param        $im
	 * @param string $topbottom
	 * @param        $threshold
	 * @param bool   $debug
	 * @return int
	 */
	public function getLength($d, &$im, $dx, $topbottom='top', $threshold,  $debug=false) {

		$debug = true;
		for($x=0;$x<$d;$x+=$dx) 
		{

			if($topbottom=="top")
			{
				$y = $d-$x;
			}
			else
			{
				$y = imageSy($im)-$d+$x;
			}

			$gray = $this->getGray($im, $x, $y);

			if($gray < $threshold) {

				$len = 15;
				$gMittel = $gray;
				for($i=1;$i<$len;$i++) 
				{
					if($topbottom=="top") 
					{
						$y2 = $d-($x+$i);
					} 
					else 
					{
						$y2 = imageSy($im)-$d+($x+$i);
					}
					$gMittel += $this->getGray($im, $x, $y2);
				}
				if($gMittel/$len < $threshold) 
				{
					return $x;
				}
			}

			if($debug)
			{
				imagesetpixel($im, $x, $y, 0x00ffff);
			} 

		}
		return $x;
	}


	public function getColor(&$im2, $x,$y) {
		// {{{
		$color = imagecolorat($im2,$x,$y);
		$blue = 0x0000ff & $color;
		$green = 0x00ff00 & $color;
		$green = $green >> 8;
		$red =0xff0000 & $color;
		$red = $red >> 16;

		return(array($red, $green, $blue));

		// }}}
	}
	public function getGray(&$im2, $x,$y) {
		$rgb = $this->getColor($im2, $x, $y);
		return( ($rgb[0]+$rgb[1]+$rgb[2])/3 );
	}

}

?>