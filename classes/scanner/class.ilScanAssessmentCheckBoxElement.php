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

	/**
	 * @var array
	 */

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

	/**
	 * @var ilScanAssessmentReliableLineDetector
	 */
    protected $border_line;

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
		$this->correction_length     = ($this->image_helper->getImageSizeY() / 297) * 1.43846153846;
		$this->search_rounds         = ($this->image_helper->getImageSizeY() / 297);
		$this->min_value_black       = ilScanAssessmentGlobalSettings::getInstance()->getMinValueBlack();
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
		$center_x = ($this->getFirstPoint()->getX() + $this->getSecondPoint()->getX()) / 2;
		$center_y = ($this->getFirstPoint()->getY() + $this->getSecondPoint()->getY()) / 2;
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
			ilScanAssessmentLog::getInstance()->err(sprintf('No real box found!.'));
			$this->trimBorderWhite();
		}

		//Todo: check why this fails so heavy now!
		#$this->trimBorderBlack();
		//Todo: check why this fails so heavy now!
        
		$new_center_x = ($this->getFirstPoint()->getX() + $this->getSecondPoint()->getX()) / 2;
        $new_center_y = ($this->getFirstPoint()->getY() + $this->getSecondPoint()->getY()) / 2;

        $this->image_helper->drawPixel($im, new ilScanAssessmentPoint($new_center_x, $new_center_y), $this->image_helper->getPink());
		$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($center_x, $center_y), $this->image_helper->getGreen());

		ilScanAssessmentLog::getInstance()->debug(sprintf('Old center was [%s, %s] new center is [%s, %s]', $center_x, $center_y, $new_center_x, $new_center_y));

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