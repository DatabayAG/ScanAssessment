<?php

/**
 * Class ilScanAssessmentCheckBoxAnalyser
 */
class ilScanAssessmentCheckBoxAnalyser
{
    private $image;
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

		$this->image = $image;
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

	public function detectRectangle($threshold) {
		list($min, $max) = $this->bounding_box;

		list($x0, $y0) = $min;
		list($x1, $y1) = $max;

        // return array(array($x0, $y0), array($x1, $y1));

		$w = 1 + $x1 - $x0;
		$h = 1 + $y1 - $y0;

        $err = 0.75;
        $image = $this->image;

		while (true) {
			$n = 0;
			for ($y = $y0; $y <= $y1; $y++) {
			    if (self::grey($image, $x0, $y) < $threshold) {
			        $n++;
                }
			}
			if ($n / (float)$h < $err) {
				$x0 += 1;
				if ($x0 >= $x1) {
					return false;
				}
			} else {
				break;
			}
		}

		while (true) {
            $n = 0;
			for ($y = $y0; $y <= $y1; $y++) {
                if (self::grey($image, $x1, $y) < $threshold) {
                    $n++;
                }
			}
            if ($n / (float)$h < $err) {
				$x1 -= 1;
				if ($x0 >= $x1) {
					return false;
				}
			} else {
				break;
			}
		}

		while (true) {
            $n = 0;
			for ($x = $x0; $x <= $x1; $x++) {
                if (self::grey($image, $x, $y0) < $threshold) {
                    $n++;
                }
			}
            if ($n / (float)$w < $err) {
				$y0 += 1;
				if ($y0 >= $y1) {
					return false;
				}
			} else {
				break;
			}
		}

		while (true) {
            $n = 0;
			for ($x = $x0; $x <= $x1; $x++) {
                if (self::grey($image, $x, $y1) < $threshold) {
                    $n++;
                }
			}
            if ($n / (float)$w < $err) {
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
}