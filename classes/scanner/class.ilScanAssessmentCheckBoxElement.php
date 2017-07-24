<?php
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentScanner.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/geometry/class.ilScanAssessmentArea.php');
ilScanAssessmentPlugin::getInstance()->includeClass('class.ilScanAssessmentGlobalSettings.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/imageWrapper/class.ilScanAssessmentCheckBoxAnalyser.php');


/**
 * Class ilScanAssessmentCheckBoxElement
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentCheckBoxElement
{
	const BOX_SIZE = 5;
	const CHECKED = 2;
	const UNCHECKED = 1;
	const UNTOUCHED = 0;
	const RECURSIVE_CALL = 50;
	const SEARCH_ROUNDS = 10;
	const SEARCH_INCREMENT = 3;

	protected $color_mapping;
	/**
	 * @var ilScanAssessmentPoint
	 */
	protected $first_point;

	/**
	 * @var ilScanAssessmentPoint
	 */
	protected $second_point;

	/**
	 * @var ilScanAssessmentImageWrapper
	 */
	protected $image_helper;

	/**
	 * @var int
	 */
	protected $min_value_black;

	/**
	 * @var float
	 */
	protected $min_marked_area;

	/**
	 * @var float
	 */
	protected $marked_area_checked;

	/**
	 * @var float
	 */
	protected $marked_area_unchecked;

	/**
	 * @var int
	 */
	protected $correction_length;

	/**
	 * @var float|int
	 */
	protected $search_rounds;

    private $border_line;

    /**
	 * ilScanAssessmentCheckBoxElement constructor.
	 * @param ilScanAssessmentPoint        $first_point
	 * @param ilScanAssessmentPoint        $second_point
	 * @param ilScanAssessmentImageWrapper $image_helper
	 */
	public function __construct($first_point, $second_point, $image_helper)
	{
		$this->first_point       = $first_point;
		$this->second_point      = $second_point;
		$this->image_helper      = $image_helper;
		$this->color_mapping     = array(
			self::UNTOUCHED => $this->image_helper->getYellow(),
			self::UNCHECKED => $this->image_helper->getPink(),
			self::CHECKED   => $this->image_helper->getGreen()
		);
		$this->correction_length = ($this->image_helper->getImageSizeY() / 297) * 1.43846153846;
		$this->search_rounds     = ($this->image_helper->getImageSizeY() / 297);
		$this->min_value_black   = ilScanAssessmentGlobalSettings::getInstance()->getMinValueBlack();
		$this->min_marked_area       = ilScanAssessmentGlobalSettings::getInstance()->getMinMarkedArea();
		$this->marked_area_checked   = ilScanAssessmentGlobalSettings::getInstance()->getMarkedAreaChecked();
		$this->marked_area_unchecked = ilScanAssessmentGlobalSettings::getInstance()->getMarkedAreaUnchecked();

        $this->border_line = new ilScanAssessmentReliableLineDetector($image_helper, $this->min_value_black, 0.4);
	}

	/**
	 * @return ilScanAssessmentPoint
	 */
	public function getLeftBottom()
	{
		return new ilScanAssessmentPoint($this->getFirstPoint()->getX(), $this->getSecondPoint()->getY());
	}

	/**
	 * @return ilScanAssessmentPoint
	 */
	public function getRightTop()
	{
		return new ilScanAssessmentPoint($this->getSecondPoint()->getX(), $this->getFirstPoint()->getY());
	}

	/**
	 * @return ilScanAssessmentPoint
	 */
	public function getFirstPoint()
	{
		return $this->first_point;
	}

	/**
	 * @param ilScanAssessmentPoint $first_point
	 */
	public function setFirstPoint($first_point)
	{
		$this->first_point = $first_point;
	}

	/**
	 * @return ilScanAssessmentPoint
	 */
	public function getSecondPoint()
	{
		return $this->second_point;
	}

	/**
	 * @param ilScanAssessmentPoint $second_point
	 */
	public function setSecondPoint($second_point)
	{
		$this->second_point = $second_point;
	}

	/**
	 * @param      $im
	 * @param bool $mark
	 * @return ilScanAssessmentArea
	 */
	protected function analyseCheckBox($im, $mark = false)
	{
		$black = 0;
		$white = 0;
		$total = 0;
		$this->detectBorder($im);
		for($x = $this->getFirstPoint()->getX(); $x < $this->getSecondPoint()->getX(); $x++)
		{
			for($y = $this->getFirstPoint()->getY(); $y < $this->getSecondPoint()->getY(); $y++)
			{
				$total++;
				$gray = $this->image_helper->getGrey(new ilScanAssessmentPoint($x, $y));
				if($gray < $this->min_value_black)
				{
					$black++;
					if($mark)
					{
						$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x, $y), $this->image_helper->getBlack());
					}
				}
				else
				{
					$white++;
				}
			}
		}
		ilScanAssessmentLog::getInstance()->debug(sprintf('Checkbox pixels total %s, black %s, white %s.', $total, $black, $white));
		return new ilScanAssessmentArea($total, $white, $black);
	}

	protected function detectBox($im, $x, $y, $size, $threshold)
	{
	    $x0 = $x;

		while($x - $x0 < $size[0] / 2)
		{
			// echo "@" . $x . ", " . $y . "<br>";

			$x = $this->scanline($im, $x, $y, $threshold);
			if($x === false)
			{
				break;
			}

			$pixels = new ilScanAssessmentCheckBoxAnalyser(
			    $im, $x, $y, $size, $threshold, $this->image_helper);

			$r = $pixels->detectRectangle();
			if($r)
			{
				return $r;
			}

			list($x, $y) = $pixels->rightmost();
			$x += 1;
		}

		return false;
	}

	function scanline($image, $x0, $y, $threshold)
	{
		$w = imagesx($image);
		for($x = $x0; $x < $w; $x++)
		{
			if($this->image_helper->getGrey(new ilScanAssessmentPoint($x, $y)) < $threshold)
			{
				return $x;
			}
		}
		return false;
	}

	protected function trimBorder($what)
    {
        // removing borders seems to make detection thresholds more resilient to noise.
        // ignoring the black borders of a checkbox and using only inner pixels for
        // calculating the blackness ratio of a checkbox, is a good idea as the border
        // pixels might be jagged or contain varying noise; thus, one empty checkbox's
        // blackness ratio might be higher than another empty one's due to slightly
        // different border pixels and thus skew the detected blackness levels.

        $x0 = $this->getFirstPoint()->getX();
        $y0 = $this->getFirstPoint()->getY();
        $x1 = $this->getSecondPoint()->getX();
        $y1 = $this->getSecondPoint()->getY();

        $s = 0.2; // maximum factor to remove
        $max_dx = intval($s * ($x1 - $x0));
        $max_dy = intval($s * ($y1 - $y0));
        $max_x0 = $x0 + $max_dx;
        $min_x1 = $x1 - $max_dx;
        $max_y0 = $y0 + $max_dy;
        $min_y1 = $y1 - $max_dy;

        while($y0 < $max_y0 && $this->border_line->horizontal($x0, $x1, $y0) === $what)
        {
            $y0++;
        }
        while($y1 > $min_y1 && $this->border_line->horizontal($x0, $x1, $y1) === $what)
        {
            $y1--;
        }
        while($x0 < $max_x0 && $this->border_line->vertical($x0, $y0, $y1) === $what)
        {
            $x0++;
        }
        while($x1 > $min_x1 && $this->border_line->vertical($x1, $y0, $y1) === $what)
        {
            $x1--;
        }

        $this->setFirstPoint(new ilScanAssessmentPoint($x0, $y0));
        $this->setSecondPoint(new ilScanAssessmentPoint($x1, $y1));
    }

    protected function trimBorderBlack()
    {
        $this->trimBorder(true);
    }

    protected function trimBorderWhite()
    {
        $this->trimBorder(false);
    }

	/**
	 * @param $im
	 */
	protected function detectBorder($im)
	{
		//TODO: remove this
		return;
		//TODO: remove this
		$center_x = $this->getFirstPoint()->getX();
		$center_y = $this->getFirstPoint()->getY();
		ilScanAssessmentLog::getInstance()->debug(sprintf('New Center is [%s, %s].',$center_x, $center_y));
		$size     = array(
			$this->getSecondPoint()->getX() - $this->getFirstPoint()->getX(),
			$this->getSecondPoint()->getY() - $this->getFirstPoint()->getY());
		$box      = $this->detectBox($im, $center_x, $center_y, $size, 100);
		if($box)
		{
			list($x0, $y0, $x1, $y1) = $box;
			$this->setFirstPoint(new ilScanAssessmentPoint($x0, $y0));
			$this->setSecondPoint(new ilScanAssessmentPoint($x1, $y1));
		}
		else
		{
			$length = ($center_x - $this->getFirstPoint()->getX());

			$left_border   = $this->getLeftBorderPosition($im, $center_x, $center_y, $length);
			$right_border  = $this->getRightBorderPosition($im, $center_x, $center_y, $length);
			$top_border    = $this->getTopBorderPosition($im, $center_x, $center_y, $length);
			$bottom_border = $this->getBottomBorderPosition($im, $center_x, $center_y, $length);

			ilScanAssessmentLog::getInstance()->debug(sprintf('Found Borders [%s, %s], [%s, %s], [%s, %s], [%s, %s].',
				$left_border->getPosition()->getX(), $left_border->getPosition()->getY(),
				$right_border->getPosition()->getX(), $right_border->getPosition()->getY(),
				$top_border->getPosition()->getX(), $top_border->getPosition()->getY(),
				$bottom_border->getPosition()->getX(), $bottom_border->getPosition()->getY()));

			$new_center_x = ($left_border->getPosition()->getX() + $right_border->getPosition()->getX()) / 2;
			$new_center_y = ($top_border->getPosition()->getY() + $bottom_border->getPosition()->getY()) / 2;

			if(!$this->checkIfCenterIsCentered($im, $new_center_x, $new_center_y, $left_border->getPosition()->getX(), $right_border->getPosition()->getX(), $top_border->getPosition()->getY(), $bottom_border->getPosition()->getY()))
			{
				ilScanAssessmentLog::getInstance()->warn(sprintf('Non center point found. Make more detailed scan starting %s %s %s.', $center_x, $center_y, $length));
				$value = false;
				for($k = 0; $k < $length; $k++)
				{
					if(!$value)
					{
						$value = $this->probeCrossSection($im, $center_x + $k, $center_y + $k, $length);
					}
					if(!$value)
					{
						$value = $this->probeCrossSection($im, $center_x - $k, $center_y + $k, $length);
					}
					if(!$value)
					{
						$value = $this->probeCrossSection($im, $center_x + $k, $center_y - $k, $length);
					}
					if(!$value)
					{
						$value = $this->probeCrossSection($im, $center_x - $k, $center_y - $k, $length);
					}
				}
				if($value)
				{

					if($length < $this->correction_length)
					{
						$length = $this->correction_length;
					}

					$this->setFirstPoint(new ilScanAssessmentPoint($value->getX() - $length, $value->getY() - $length));
					$this->setSecondPoint(new ilScanAssessmentPoint($value->getX() + $length, $value->getY() + $length));
				}
			}

			// the current check estimation is usually not directly on the border lines, i.e. there is some white
            // that needs to be trimmed first until the rectangle corresponds to a tight bounding box for the rectangle.

			$this->trimBorderWhite();
		}

		$this->trimBorderBlack();

        $new_center_x = ($this->getFirstPoint()->getX() + $this->getSecondPoint()->getX()) / 2;
        $new_center_y = ($this->getFirstPoint()->getY() + $this->getSecondPoint()->getY()) / 2;

        $this->image_helper->drawPixel($im, new ilScanAssessmentPoint($new_center_x, $new_center_y), $this->image_helper->getPink());
		$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($center_x, $center_y), $this->image_helper->getGreen());

		ilScanAssessmentLog::getInstance()->debug(sprintf('Old center was [%s, %s] new center is [%s, %s]', $center_x, $center_y, $new_center_x, $new_center_y));

	}

	protected function probeCrossSection($im, $center_x, $center_y, $length)
	{
		$cross_length = $length;
		$point        = null;
		$point_store  = array();

		for($j = 0; $j < $cross_length; $j++)
		{
			for($i = 0; $i < $cross_length; $i++)
			{
				$found = $this->scanCross($im, $center_x, $center_y, $i, $j, $cross_length);
				if($found)
				{
					$point         = $found;
					$point_store[] = $point;
					continue 2;
				}
			}
		}
		for($j = $cross_length; $j > 0; $j--)
		{
			for($i = $cross_length; $i > 0; $i--)
			{
				$found = $this->scanCross($im, $center_x, $center_y, $i, $j, $cross_length);
				if($found)
				{
					$point         = $found;
					$point_store[] = $point;
					continue 2;
				}
			}
		}
		for($j = 0; $j < $cross_length; $j++)
		{
			for($i = $cross_length; $i > 0; $i--)
			{
				$found = $this->scanCross($im, $center_x, $center_y, $i, $j, $cross_length);
				if($found)
				{
					$point         = $found;
					$point_store[] = $point;
					continue 2;
				}
			}
		}
		for($j = $cross_length; $j > 0; $j--)
		{
			for($i = 0; $i < $cross_length; $i++)
			{
				$found = $this->scanCross($im, $center_x, $center_y, $i, $j, $cross_length);
				if($found)
				{
					$point         = $found;
					$point_store[] = $point;
					continue 2;
				}
			}
		}

		$found = 0;
		$x     = 0;
		$y     = 0;
		foreach($point_store as $point)
		{
			ilScanAssessmentLog::getInstance()->debug(sprintf('Found point [%s, %s]', $point->getX(), $point->getY()));
			$found++;
			$x += $point->getX();
			$y += $point->gety();
		}
		if($found > 0)
		{
			$point = new ilScanAssessmentPoint($x / $found, $y / $found);
			$this->image_helper->drawPixel($im, $point, $this->image_helper->getRed());
			return $point;
		}
		return false;
	}

	/**
	 * @param      $im
	 * @param      $center_x
	 * @param      $center_y
	 * @param      $i
	 * @param      $j
	 * @param      $cross_length
	 * @param bool $black
	 * @return bool|ilScanAssessmentPoint
	 */
	protected function scanCross($im, $center_x, $center_y, $i, $j, $cross_length, $black = true)
	{
		$x                 = $center_x + $i;
		$y                 = $center_y + $j;
		$gray_left         = $this->image_helper->getGrey(new ilScanAssessmentPoint($x - $cross_length, $y));
		$gray_right        = $this->image_helper->getGrey(new ilScanAssessmentPoint($x + $cross_length, $y));
		$gray_top          = $this->image_helper->getGrey(new ilScanAssessmentPoint($x, $y - $cross_length));
		$gray_bottom       = $this->image_helper->getGrey(new ilScanAssessmentPoint($x, $y + $cross_length));
		$gray_top_left     = $this->image_helper->getGrey(new ilScanAssessmentPoint($x - $cross_length, $y - $cross_length));
		$gray_top_right    = $this->image_helper->getGrey(new ilScanAssessmentPoint($x + $cross_length, $y - $cross_length));
		$gray_bottom_left  = $this->image_helper->getGrey(new ilScanAssessmentPoint($x - $cross_length, $y + $cross_length));
		$gray_bottom_right = $this->image_helper->getGrey(new ilScanAssessmentPoint($x + $cross_length, $y + $cross_length));

		$gray = ($gray_left + $gray_right + $gray_top + $gray_bottom + $gray_top_left + $gray_top_right + $gray_bottom_left + $gray_bottom_right) / 8;
		if($black && $gray < 50)
		{
			/*ilScanAssessmentLog::getInstance()->debug(sprintf('Found Colors %s, %s, %s, %s, %s, %s, %s, %s, %s.',
				$gray_left,
				$gray_right,
				$gray_top,
				$gray_bottom,
				$gray_top_left,
				$gray_top_right,
				$gray_bottom_left,
				$gray_bottom_right,
				$gray
			));
			$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x - $cross_length, $y), $this->image_helper->getPink());
			$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x + $cross_length, $y), $this->image_helper->getRed());
			$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x, $y - $cross_length), $this->image_helper->getBlue());
			$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x, $y + $cross_length), $this->image_helper->getGreen());
			$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x - $cross_length, $y - $cross_length), $this->image_helper->getPink());
			$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x + $cross_length, $y - $cross_length), $this->image_helper->getRed());
			$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x - $cross_length, $y + $cross_length), $this->image_helper->getBlue());
			$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x + $cross_length, $y + $cross_length), $this->image_helper->getGreen());
			*/
			return new ilScanAssessmentPoint($x, $y);
		}
		else if(!$black)
		{
			if($gray > 200)
			{
				/*ilScanAssessmentLog::getInstance()->debug(sprintf('Found Colors > 250 %s, %s, %s, %s, %s, %s, %s, %s, %s.',
					$gray_left,
					$gray_right,
					$gray_top,
					$gray_bottom,
					$gray_top_left,
					$gray_top_right,
					$gray_bottom_left,
					$gray_bottom_right,
					$gray
				));*/
				return new ilScanAssessmentPoint($x, $y);
			}
		}
		return false;
	}

	protected function checkIfCenterIsCentered($im, $x, $y, $left_x, $right_x, $top_y, $bottom_y)
	{
		$to_the_left   = $x - $left_x;
		$to_the_right  = $right_x - $x;
		$to_the_top    = $y - $top_y;
		$to_the_bottom = $bottom_y - $y;
		if(abs($to_the_left - $to_the_right) > 0.5 ||
			abs($to_the_left - $to_the_bottom) > 0.5 ||
			abs($to_the_left - $to_the_top) > 0.5 ||
			abs($to_the_right - $to_the_left) > 0.5 ||
			abs($to_the_right - $to_the_bottom) > 0.5 ||
			abs($to_the_right - $to_the_top) > 0.5
		)
		{
			return false;
		}
		if($this->scanCross($im, $x, $y, 0, 0, $right_x - $left_x, false))
		{
			$new_top_left     = new ilScanAssessmentPoint($left_x, $top_y);
			$new_bottom_right = new ilScanAssessmentPoint($right_x, $bottom_y);
			$this->setFirstPoint($new_top_left);
			$this->setSecondPoint($new_bottom_right);
			return true;
		}
		return false;
	}

	/**
	 * @param     $im
	 * @param     $center_x
	 * @param     $center_y
	 * @param int $length_multiplier
	 * @return ilScanAssessmentVector
	 */
	protected function getBottomBorderPosition($im, $center_x, $center_y, $length, $length_multiplier = 1)
	{
		$border_temp   = array();
		$bottom_border = false;
		for($i = 1; $i < $this->search_rounds; $i += self::SEARCH_INCREMENT)
		{
			$bottom      = false;
			$black_pixel = 0;
			for($y = $center_y; $y < $this->getSecondPoint()->getY() + ($length * $length_multiplier); $y++)
			{
				$x = $center_x - $i;
				#$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x,$y), $this->image_helper->getRed());
				$gray = $this->image_helper->getGrey(new ilScanAssessmentPoint($x, $y));

				if($gray < $this->min_value_black)
				{
					$bottom        = true;
					$bottom_border = false;
					$black_pixel++;
					if($bottom_border == true)
					{
						array_pop($border_temp);
						$bottom_border = false;
					}
				}
				else if($bottom && !$bottom_border)
				{
					$bottom        = false;
					$border_temp[] = new ilScanAssessmentVector(new ilScanAssessmentPoint($x, $y), $black_pixel);
					$bottom_border = true;
				}
			}

		}
		if(!$bottom_border && $length_multiplier < self::RECURSIVE_CALL)
		{
			$border_temp   = array();
			$border_temp[] = $this->getBottomBorderPosition($im, $center_x, $center_y, $length, $length_multiplier + 1);
		}

		$x           = 0;
		$y           = 0;
		$found_twice = false;
		foreach($border_temp as $vector)
		{
			if($vector->getPosition()->getY() >= $y)
			{
				if($vector->getPosition()->getY() == $y)
				{
					$found_twice = true;
				}
				if(!$found_twice)
				{
					$x = $vector->getPosition()->getX();
					$y = $vector->getPosition()->getY();
				}
			}
		}
		$bottom_border = new ilScanAssessmentVector(new ilScanAssessmentPoint($x, $y), 0);
		return $bottom_border;
	}

	/**
	 * @param     $im
	 * @param     $center_x
	 * @param     $center_y
	 * @param int $length_multiplier
	 * @return ilScanAssessmentVector
	 */
	protected function getTopBorderPosition($im, $center_x, $center_y, $length, $length_multiplier = 1)
	{
		$border_temp = array();
		$top_border  = false;
		for($i = 1; $i < $this->search_rounds; $i += self::SEARCH_INCREMENT)
		{
			$top         = false;
			$black_pixel = 0;
			for($y = $center_y; $y > $this->getFirstPoint()->getY() - ($length * $length_multiplier); $y--)
			{
				$x = $center_x - $i;
				#$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x,$y), $this->image_helper->getBlue());
				$gray = $this->image_helper->getGrey(new ilScanAssessmentPoint($x, $y));
				if($gray < $this->min_value_black)
				{
					$top = true;
					if($top_border == true)
					{
						array_pop($border_temp);
						$top_border = false;
					}
					$black_pixel++;
				}
				else if($top && !$top_border)
				{
					$top           = false;
					$border_temp[] = new ilScanAssessmentVector(new ilScanAssessmentPoint($x, $y), $black_pixel);
					$top_border    = true;
				}
			}
		}
		if(!$top_border && $length_multiplier < self::RECURSIVE_CALL)
		{
			$border_temp   = array();
			$border_temp[] = $this->getTopBorderPosition($im, $center_x, $center_y, $length, $length_multiplier + 1);
		}
		$x           = $this->image_helper->getImageSizeX();
		$y           = $this->image_helper->getImageSizeY();
		$found_twice = false;
		foreach($border_temp as $vector)
		{
			if($vector->getPosition()->getY() <= $y)
			{
				if($vector->getPosition()->getY() == $y)
				{
					$found_twice = true;
				}
				if(!$found_twice)
				{
					$x = $vector->getPosition()->getX();
					$y = $vector->getPosition()->getY();
				}
			}
		}
		$top_border = new ilScanAssessmentVector(new ilScanAssessmentPoint($x, $y), 0);
		return $top_border;
	}

	/**
	 * @param     $im
	 * @param     $center_x
	 * @param     $center_y
	 * @param int $length_multiplier
	 * @return ilScanAssessmentVector
	 */
	protected function getLeftBorderPosition($im, $center_x, $center_y, $length, $length_multiplier = 1)
	{
		$border_temp = array();
		$border      = false;
		for($i = 1; $i < $this->search_rounds; $i += self::SEARCH_INCREMENT)
		{
			$black       = false;
			$black_pixel = 0;
			$border      = false;
			for($x = $center_x; $x > $this->getFirstPoint()->getX() - ($length * $length_multiplier); $x--)
			{
				$y = $center_y - $i;
				#$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x,$y), $this->image_helper->getGreen());
				$gray = $this->image_helper->getGrey(new ilScanAssessmentPoint($x, $y));

				if($gray < $this->min_value_black)
				{
					$black = true;
					$black_pixel++;
					if($border == true)
					{
						array_pop($border_temp);
						$border = false;
					}
				}
				else if($black && !$border)
				{
					$black         = false;
					$border_temp[] = new ilScanAssessmentVector(new ilScanAssessmentPoint($x, $y), $black_pixel);
					$border        = true;
				}
			}
		}

		if(!$border && $length_multiplier < self::RECURSIVE_CALL)
		{
			$border_temp   = array();
			$border_temp[] = $this->getLeftBorderPosition($im, $center_x, $center_y, $length, $length_multiplier + 1);
		}

		$x           = $this->image_helper->getImageSizeX();
		$y           = $this->image_helper->getImageSizeY();
		$found_twice = false;
		foreach($border_temp as $vector)
		{
			if($vector->getPosition()->getX() <= $x)
			{
				if($vector->getPosition()->getX() == $x && !$found_twice)
				{
					$found_twice = true;
				}
				if(!$found_twice)
				{
					$x = $vector->getPosition()->getX();
					$y = $vector->getPosition()->getY();
				}
			}
		}
		$border = new ilScanAssessmentVector(new ilScanAssessmentPoint($x, $y), 0);
		return $border;
	}

	/**
	 * @param     $im
	 * @param     $center_x
	 * @param     $center_y
	 * @param int $length_multiplier
	 * @return ilScanAssessmentVector
	 */
	protected function getRightBorderPosition($im, $center_x, $center_y, $length, $length_multiplier = 1)
	{
		$border_temp = array();
		$border      = false;
		for($i = 1; $i < $this->search_rounds; $i += self::SEARCH_INCREMENT)
		{
			$black       = false;
			$black_pixel = 0;
			for($x = $center_x; $x < $this->getSecondPoint()->getX() + ($length * $length_multiplier); $x++)
			{
				$y = $center_y - $i;
				#$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x,$y), $this->image_helper->getPink());
				$gray = $this->image_helper->getGrey(new ilScanAssessmentPoint($x, $y));
				if($gray < $this->min_value_black)
				{
					$black = true;
					if($border == true)
					{
						array_pop($border_temp);
						$border = false;
					}
					$black_pixel++;
				}
				else if($black && !$border)
				{
					$border_temp[] = new ilScanAssessmentVector(new ilScanAssessmentPoint($x, $y), $black_pixel);
					$border        = true;
				}
			}
		}
		if(!$border && $length_multiplier < self::RECURSIVE_CALL)
		{
			$border_temp   = array();
			$border_temp[] = $this->getRightBorderPosition($im, $center_x, $center_y, $length, $length_multiplier + 1);
		}
		$x = 0;
		$y = 0;

		$found_twice = false;
		foreach($border_temp as $vector)
		{
			if($vector->getPosition()->getX() >= $x)
			{
				if($vector->getPosition()->getX() == $x)
				{
					$found_twice = true;
				}
				if(!$found_twice)
				{
					$x = $vector->getPosition()->getX();
					$y = $vector->getPosition()->getY();
				}
			}
		}
		$border = new ilScanAssessmentVector(new ilScanAssessmentPoint($x, $y), 0);
		return $border;
	}

	/**
	 * @param      $im
	 * @param bool $mark
	 * @return int
	 */
	public function isMarked($im, $mark = false)
	{
		$area  = $this->analyseCheckBox($im, $mark);
		$value = self::UNTOUCHED;

		if($area->percentBlack() >= $this->min_marked_area)
		{
			if($area->percentBlack() >= $this->marked_area_checked && $area->percentBlack() <= $this->marked_area_unchecked)
			{
				$value = self::CHECKED;
				ilScanAssessmentLog::getInstance()->debug(sprintf('Checkbox is checked %s.', $area->percentBlack()));
			}
			else
			{
				$value = self::UNCHECKED;
				ilScanAssessmentLog::getInstance()->debug(sprintf('Checkbox is unchecked %s.', $area->percentBlack()));
			}
		}

		if($mark)
		{
			$this->image_helper->drawSquareFromTwoPoints($im, $this->getFirstPoint(), $this->getSecondPoint(), $this->color_mapping[$value]);
		}
		ilScanAssessmentLog::getInstance()->debug(sprintf('Checkbox black %s, white %s.', $area->percentBlack(), $area->percentWhite()));
		return $value;
	}

}