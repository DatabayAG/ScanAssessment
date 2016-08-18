<?php

/**
 * Class ilScanAssessmentArea
 * @author Guido Vollbach <gvollbach@databay.de>
 */
class ilScanAssessmentArea
{
	/**
	 * @var int
	 */
	private $pixels;

	/**
	 * @var int
	 */
	private $white_pixels;
	
	/**
	 * @var int
	 */
	private $black_pixels;

	/**
	 * ilScanAssessmentArea constructor.
	 * @param $pixels
	 * @param $white_pixels
	 * @param $black_pixels
	 */
	public function __construct($pixels, $white_pixels, $black_pixels)
	{
		$this->pixels       = $pixels;
		$this->white_pixels = $white_pixels;
		$this->black_pixels = $black_pixels;
	}

	/**
	 * @return float
	 */
	public function percentBlack()
	{
		if($this->pixels > 0)
		{
			return (100 * $this->black_pixels) / $this->pixels;
		}
		return 0;
	}

	/**
	 * @return float
	 */
	public function percentWhite()
	{
		if($this->pixels > 0)
		{
			return (100 * $this->white_pixels) / $this->pixels;
		}
		return 0;
	}
}