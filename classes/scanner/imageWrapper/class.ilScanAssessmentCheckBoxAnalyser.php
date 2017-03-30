<?php

/**
 * Class ilScanAssessmentCheckBoxAnalyser
 */
class ilScanAssessmentCheckBoxAnalyser
{
	private $pixels;
	private $bounding_box;

	public function rightmost() 
	{
		$x = PHP_INT_MIN;
		$y = 0;

		foreach ($this->coordinates() as $pixel) {
			if ($pixel[0] > $x) {
				list($x, $y) = $pixel;
			}
		}

		return array($x, $y);
	}

	public function __construct($image, $x, $y, $threshold)
	{
		$pixels = array();
		self::gatherPixels($image, $x, $y, $threshold, $pixels);
		$this->pixels = $pixels;

		$this->bounding_box = $this->calculateBoundingBox();
	}
	
	static function  grey($image, $x, $y) {
		$color = imagecolorat($image, $x, $y);

		$blue	= 0x0000ff & $color;
		$green	= 0x00ff00 & $color;
		$green	= $green >> 8;
		$red	= 0xff0000 & $color;
		$red	= $red >> 16;

		return ($red + $green + $blue) / 3;
	}

	private static function gatherPixels($image, $x, $y, $threshold, &$pixels)
	{
		// essentially a flood fill that detects all black marker pixels.

		$stack = array(array($x, $y));

		array_push($stack, array($x - 1, $y - 1));
		array_push($stack, array($x - 1, $y + 1));
		array_push($stack, array($x + 1, $y - 1));
		array_push($stack, array($x + 1, $y + 1));

		$w = imagesx($image);
		$h = imagesy($image);

		while (count($stack) > 0)
		{
			list($x, $y) = array_pop($stack);

			if ($x < 0 || $y < 0 || $x >= $w || $y >= $h)
			{
				continue;
			}

			$coordinates = $x . '/' . $y;

			if (isset($pixels[$coordinates]))
			{
				continue;
			}

			if (self::grey($image, $x, $y) < $threshold) // black?
			{
				$pixels[$coordinates] = true;
				array_push($stack, array($x + 1, $y));
				array_push($stack, array($x - 1, $y));
				array_push($stack, array($x, $y + 1));
				array_push($stack, array($x, $y - 1));
			}
		}
	}

	private function coordinates() 
	{
		$coordinates = array();
		foreach (array_keys($this->pixels) as $xy) {
			list($x, $y) = explode('/', $xy);
			array_push($coordinates, array(intval($x), intval($y)));
		}
		return $coordinates;
	}

	private function calculateBoundingBox() {
		$x = array();
		$y = array();

		foreach ($this->coordinates() as $pixel) {
			array_push($x, $pixel[0]);
			array_push($y, $pixel[1]);
		}

		if(count($x) > 0 && count($y) >0)
		{
			return array(array(min($x), min($y)), array(max($x), max($y)));
		}

	}

	public function getBoundingBox() {
		return $this->bounding_box;
	}

	public function detectRectangle() {
		list($min, $max) = $this->bounding_box;

		list($x0, $y0) = $min;
		list($x1, $y1) = $max;

		$w = 1 + $x1 - $x0;
		$h = 1 + $y1 - $y0;

		while (true) {
			$d = 0;
			for ($y = $y0; $y <= $y1; $y++) {
				$d += $this->distanceTo($x0, $y);
			}
			if ($d / $h > 2) {
				$x0 += 1;
				if ($x0 >= $x1) {
					return false;
				}
			} else {
				break;
			}
		}

		while (true) {
			$d = 0;
			for ($y = $y0; $y <= $y1; $y++) {
				$d += $this->distanceTo($x1, $y);
			}
			if ($d / $h > 2) {
				$x1 -= 1;
				if ($x0 >= $x1) {
					return false;
				}
			} else {
				break;
			}
		}

		while (true) {
			$d = 0;
			for ($x = $x0; $x <= $x1; $x++) {
				$d += $this->distanceTo($x, $y0);
			}
			if ($d / $w > 2) {
				$y0 += 1;
				if ($y0 >= $y1) {
					return false;
				}
			} else {
				break;
			}
		}

		while (true) {
			$d = 0;
			for ($x = $x0; $x <= $x1; $x++) {
				$d += $this->distanceTo($x, $y1);
			}
			if ($d / $w > 2) {
				$y1 -= 1;
				if ($y0 >= $y1) {
					return false;
				}
			} else {
				break;
			}
		}

		return array(array($x0, $y0), array($x1, $y1));
	}

	public function distanceTo($x, $y) {
		$coordinates = $x . '/' . $y;
		if (isset($this->pixels[$coordinates])) {
			return 0;
		}

		foreach (array(-1, 1) as $dx) {
			foreach (array(-1, 1) as $dy) {
				$coordinates = ($x + $dx) . '/' . ($y + $dy);
				if (isset($this->pixels[$coordinates])) {
					return 1;
				}
			}
		}

		return 1000; // FIXME
	}








}