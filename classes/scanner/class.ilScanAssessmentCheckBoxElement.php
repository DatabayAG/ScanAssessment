<?php
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/class.ilScanAssessmentScanner.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/geometry/class.ilScanAssessmentArea.php');

/**
 * Class ilScanAssessmentCheckBoxElement
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentCheckBoxElement
{
	const MIN_VALUE_BLACK		= 150;
	const MIN_MARKED_AREA		= 0.40;
	const MARKED_AREA_CHECKED	= 0.45;
	const MARKED_AREA_UNCHECKED	= 0.90;
	const BOX_SIZE				= 5;
	const CHECKED				= 2;
	const UNCHECKED				= 1;
	const UNTOUCHED				= 0;
	const SEARCH_LENGTH			= 5;
	const SEARCH_ROUNDS			= 10;
	const SEARCH_INCREMENT		= 3;

	protected $color_mapping;
	/**
	 * @var ilScanAssessmentPoint
	 */
	protected $left_top;

	/**
	 * @var ilScanAssessmentPoint
	 */
	protected $right_bottom;

	/**
	 * @var ilScanAssessmentImageWrapper
	 */
	protected $image_helper;
	
	protected $recalculate_position = false;
	
	protected $border_left_temp;

	/**
	 * ilScanAssessmentCheckBoxElement constructor.
	 * @param ilScanAssessmentPoint $left_top
	 * @param ilScanAssessmentPoint $right_bottom
	 * @param ilScanAssessmentImageWrapper $image_helper
	 */
	public function __construct($left_top, $right_bottom, $image_helper)
	{
		$this->left_top			= $left_top;
		$this->right_bottom		= $right_bottom;
		$this->image_helper		= $image_helper;
		$this->color_mapping	= array(
			self::UNTOUCHED	=> $this->image_helper->getYellow(),
			self::UNCHECKED	=> $this->image_helper->getPink(),
			self::CHECKED	=> $this->image_helper->getGreen()
		);
		$this->border_left_temp = array();
	}

	/**
	 * @return ilScanAssessmentPoint
	 */
	public function getLeftBottom()
	{
		return new ilScanAssessmentPoint($this->getLeftTop()->getX(), $this->getRightBottom()->getY());
	}

	/**
	 * @return ilScanAssessmentPoint
	 */
	public function getRightTop()
	{
		return new ilScanAssessmentPoint($this->getRightBottom()->getX(), $this->getLeftTop()->getY());
	}

	/**
	 * @return ilScanAssessmentPoint
	 */
	public function getLeftTop()
	{
		return $this->left_top;
	}

	/**
	 * @param ilScanAssessmentPoint $left_top
	 */
	public function setLeftTop($left_top)
	{
		$this->left_top = $left_top;
	}

	/**
	 * @return ilScanAssessmentPoint
	 */
	public function getRightBottom()
	{
		return $this->right_bottom;
	}

	/**
	 * @param ilScanAssessmentPoint $right_bottom
	 */
	public function setRightBottom($right_bottom)
	{
		$this->right_bottom = $right_bottom;
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
		for($x = $this->getLeftTop()->getX(); $x < $this->getRightBottom()->getX(); $x++)
		{
			for($y = $this->getLeftTop()->getY(); $y < $this->getRightBottom()->getY(); $y++)
			{
				$total++;
				$gray = $this->image_helper->getGrey(new ilScanAssessmentPoint($x, $y));
				if($gray < self::MIN_VALUE_BLACK)
				{
					$black++;
					if($mark)
					{
						$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x,$y), $this->image_helper->getBlack());
					}
				}
				else
				{
					$white++;
				}
			}
		}
		#ilScanAssessmentLog::getInstance()->debug(sprintf('Checkbox pixels total %s, black %s, white %s.', $total, $black, $white));
		return new ilScanAssessmentArea($total, $white, $black);
	}

	/**
	 * @param      $im
	 * @param bool $mark
	 * @return ilScanAssessmentArea
	 */
	protected function detectBorder($im)
	{

		$center_x		= ($this->getLeftTop()->getX() + $this->getRightBottom()->getX()) / 2;
		$center_y		= ($this->getLeftTop()->getY() + $this->getRightBottom()->getY()) / 2;

		$length = ($center_x - $this->getLeftTop()->getX());
		ilScanAssessmentLog::getInstance()->warn(sprintf('LENGTH: %s', $length));

		$left_border = $this->getLeftBorderPosition($im, $center_x, $center_y, $length);
		$right_border = $this->getRightBorderPosition($im, $center_x, $center_y, $length);
		$top_border = $this->getTopBorderPosition($im, $center_x, $center_y, $length);
		$bottom_border = $this->getBottomBorderPosition($im, $center_x, $center_y, $length);

		ilScanAssessmentLog::getInstance()->debug(sprintf('%s %s %s %s',$bottom_border->getLength(), $top_border->getLength(), $right_border->getLength(), $left_border->getLength()));
		ilScanAssessmentLog::getInstance()->debug(sprintf('Found Borders [%s, %s], [%s, %s], [%s, %s], [%s, %s].',
				$left_border->getPosition()->getX(), $left_border->getPosition()->getY(),
				$right_border->getPosition()->getX(), $right_border->getPosition()->getY(),
				$top_border->getPosition()->getX(), $top_border->getPosition()->getY(),
				$bottom_border->getPosition()->getX(), $bottom_border->getPosition()->getY()));
			$new_center_x		= ($left_border->getPosition()->getX() + $right_border->getPosition()->getX()) / 2;
			$new_center_y		= ($top_border->getPosition()->getY() + $bottom_border->getPosition()->getY()) / 2;
			$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($new_center_x, $new_center_y), $this->image_helper->getPink());
			$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($center_x, $center_y), $this->image_helper->getGreen());
			$this->checkIfCenterIsCentered($im, $new_center_x, $new_center_y, $left_border->getPosition()->getX(), $right_border->getPosition()->getX(), $top_border->getPosition()->getY(), $bottom_border->getPosition()->getY());
			ilScanAssessmentLog::getInstance()->warn(sprintf('Old center was [%s, %s] new center is [%s, %s]', $center_x, $center_y, $new_center_x, $new_center_y));


		if(!$left_border && !$right_border && !$top_border && !$bottom_border)
		{
			ilScanAssessmentLog::getInstance()->warn(sprintf('Non orientation point found. We are nowhere near a checkbox.'));
		}
	}
	
	protected function checkIfCenterIsCentered($im, $x, $y, $left_x, $right_x, $top_y, $bottom_y)
	{
		$to_the_left	= $x - $left_x;
		$to_the_right	= $right_x - $x;
		$to_the_top		= $y - $top_y;
		$to_the_bottom	= $bottom_y - $y;
		if(	abs($to_the_left - $to_the_right) > 0   ||
			abs($to_the_left - $to_the_bottom) > 0  ||
			abs($to_the_left - $to_the_top) > 0     ||
			abs($to_the_right - $to_the_left) > 0   ||
			abs($to_the_right - $to_the_bottom) > 0 ||
			abs($to_the_right - $to_the_top) > 0 
			)
		{
			$correct_value = 0;
			if($to_the_left > $correct_value)
			{
				$correct_value = $to_the_left;
			}
			if($to_the_right > $correct_value)
			{
				$correct_value = $to_the_right;
			}
			if($to_the_top > $correct_value)
			{
				$correct_value = $to_the_top;
			}
			if($to_the_bottom > $correct_value)
			{
				$correct_value = $to_the_bottom;
			}
			$new_top_left = new ilScanAssessmentPoint($left_x - ($correct_value - $to_the_left), $top_y - ($correct_value - $to_the_top));
			$new_bottom_right = new ilScanAssessmentPoint($right_x + ($correct_value - $to_the_right), $bottom_y + ($correct_value - $to_the_bottom));
		}
		else
		{
			$new_top_left = new ilScanAssessmentPoint($left_x, $top_y);
			$new_bottom_right = new ilScanAssessmentPoint($right_x, $bottom_y);
		}
		$this->setLeftTop($new_top_left);
		$this->setRightBottom($new_bottom_right);
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
		$border_temp 	= array();
		$bottom_border	= false;
		for($i = 1; $i < self::SEARCH_ROUNDS; $i += self::SEARCH_INCREMENT)
		{
			$bottom			= false;
			$black_pixel 	= 0;
			for($y = $center_y ; $y < $this->getRightBottom()->getY() + ($length * $length_multiplier); $y++)
			{
				$x = $center_x - $i;
				#$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x,$y), $this->image_helper->getRed());
				$gray = $this->image_helper->getGrey(new ilScanAssessmentPoint($x, $y));

				if($gray < self::MIN_VALUE_BLACK)
				{
					$bottom = true;
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
					$bottom = false;
					$border_temp[] = new ilScanAssessmentVector(new ilScanAssessmentPoint($x, $y), $black_pixel);
					$bottom_border = true;
				}
			}

		}
		if(!$bottom_border)
		{
			$border_temp = array();
			$border_temp[] = $this->getBottomBorderPosition($im, $center_x, $center_y, $length, $length_multiplier + 1);
			$this->recalculate_position = true;
		}

		$x = 0;
		$y = 0;
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
		$border_temp 	= array();
		$top_border		= false;
		for($i = 1; $i <  self::SEARCH_ROUNDS; $i += self::SEARCH_INCREMENT)
		{
			$top			= false;
			$black_pixel 	= 0;
			for($y = $center_y ; $y > $this->getLeftTop()->getY() - ($length * $length_multiplier); $y--)
			{
				$x = $center_x - $i;
				#$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x,$y), $this->image_helper->getBlue());
				$gray = $this->image_helper->getGrey(new ilScanAssessmentPoint($x, $y));
				if($gray < self::MIN_VALUE_BLACK)
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
					$top = false;
					$border_temp[] = new ilScanAssessmentVector(new ilScanAssessmentPoint($x, $y), $black_pixel);
					$top_border = true;
				}
			}
		}
		if(!$top_border)
		{
			$border_temp = array();
			$border_temp[] = $this->getTopBorderPosition($im, $center_x, $center_y, $length, $length_multiplier + 1);
			$this->recalculate_position = true;
		}
		$x = $this->image_helper->getImageSizeX();
		$y = $this->image_helper->getImageSizeY();
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
		$border_temp 	= array();
		$border			= false;
		for($i = 1; $i <  self::SEARCH_ROUNDS; $i += self::SEARCH_INCREMENT)
		{
			$black			= false;
			$black_pixel 	= 0;
			$border = false;
			for($x = $center_x; $x > $this->getLeftTop()->getX() - ($length * $length_multiplier); $x--)
			{
				$y = $center_y - $i;
				#$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x,$y), $this->image_helper->getGreen());
				$gray = $this->image_helper->getGrey(new ilScanAssessmentPoint($x, $y));

				if($gray < self::MIN_VALUE_BLACK)
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
					$black = false;
					$border_temp[] = new ilScanAssessmentVector(new ilScanAssessmentPoint($x, $y), $black_pixel);
					$border = true;
				}
			}
		}

		if(!$border)
		{
			$border_temp = array();
			$border_temp[] = $this->getLeftBorderPosition($im, $center_x, $center_y, $length, $length_multiplier + 1);
			$this->recalculate_position = true;
		}

		$x = $this->image_helper->getImageSizeX();
		$y = $this->image_helper->getImageSizeY();
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
		$border_temp 	= array();
		$border			= false;
		for($i = 1; $i <  self::SEARCH_ROUNDS; $i += self::SEARCH_INCREMENT)
		{
			$black			= false;
			$black_pixel 	= 0;
			for($x = $center_x ; $x < $this->getRightBottom()->getX() + ($length * $length_multiplier); $x++)
			{
				$y = $center_y - $i;
				#$this->image_helper->drawPixel($im, new ilScanAssessmentPoint($x,$y), $this->image_helper->getPink());
				$gray = $this->image_helper->getGrey(new ilScanAssessmentPoint($x, $y));
				if($gray < self::MIN_VALUE_BLACK)
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
					$border = true;
				}
			}
		}
		if(!$border)
		{
			$border_temp = array();
			$border_temp[] = $this->getRightBorderPosition($im, $center_x, $center_y, $length, $length_multiplier + 1);
			$this->recalculate_position = true;
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
		$area	= $this->analyseCheckBox($im, $mark);
		$value	= self::UNTOUCHED;

		if($area->percentBlack() >= self::MIN_MARKED_AREA)
		{
			if($area->percentBlack() >= self::MARKED_AREA_CHECKED && $area->percentBlack() <= self::MARKED_AREA_UNCHECKED)
			{
				$value	= self::CHECKED;
				#ilScanAssessmentLog::getInstance()->debug(sprintf('Checkbox is checked %s.', $area->percentBlack()));
			}
			else
			{
				$value	= self::UNCHECKED;
				#ilScanAssessmentLog::getInstance()->debug(sprintf('Checkbox is unchecked %s.', $area->percentBlack()));
			}
		}

		if($mark)
		{
			$this->image_helper->drawSquareFromTwoPoints($im,  $this->getLeftTop(), $this->getRightBottom(), $this->color_mapping[$value]);
		}
		#ilScanAssessmentLog::getInstance()->debug(sprintf('Checkbox black %s, white %s.', $area->percentBlack(), $area->percentWhite()));
		return $value;
	}

}