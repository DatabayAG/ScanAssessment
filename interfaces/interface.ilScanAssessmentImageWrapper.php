<?php

ilScanAssessmentPlugin::getInstance()->includeClass('scanner/geometry/class.ilScanAssessmentPoint.php');
/**
 * Interface ilScanAssessmentImageWrapper
 */
interface ilScanAssessmentImageWrapper
{
	/**
	 * ilScanAssessmentImageHelper constructor.
	 * @param string $fn
	 */
	public function __construct($fn);

	/**
	 * @param ilScanAssessmentPoint $point
	 * @return mixed
	 */
	public function getColor(ilScanAssessmentPoint $point);

	/**
	 * @param ilScanAssessmentPoint $point
	 * @return mixed
	 */
	public function getGrey(ilScanAssessmentPoint $point);

	public function removeBlackBorder();

	/**
	 * @param $rad
	 * @return mixed
	 */
	public function rotate($rad);

	/**
	 * @param $temp_img
	 * @param $start_x
	 * @param $start_y
	 * @param $end_x
	 * @param $end_y
	 * @param $color
	 */
	public function drawLine($temp_img, $start_x, $start_y, $end_x, $end_y, $color);

	/**
	 * @param $temp_img
	 * @param ilScanAssessmentPoint $point
	 * @param $color
	 */
	public function drawPixel($temp_img, ilScanAssessmentPoint $point, $color);

	/**
	 * @param                        $temp_img
	 * @param ilScanAssessmentVector $vector
	 * @param                        $color
	 */
	public function drawSquareFromVector($temp_img, ilScanAssessmentVector $vector, $color);

	/**
	 * @param                       $temp_img
	 * @param ilScanAssessmentPoint $first
	 * @param ilScanAssessmentPoint $second
	 * @param                       $color
	 */
	public function drawSquareFromTwoPoints($temp_img, ilScanAssessmentPoint $first, ilScanAssessmentPoint $second, $color);

	/**
	 * @return int
	 */
	public function getImageSizeY();

	/**
	 * @return int
	 */
	public function getImageSizeX();

	/**
	 * @param $img
	 * @param $fn
	 */
	public function drawTempImage($img, $fn);

	/**
	 * @return string
	 */
	public function getWhite();

	/**
	 * @return string
	 */
	public function getBlack();

	/**
	 * @return string
	 */
	public function getRed();

	/**
	 * @return string
	 */
	public function getGreen();

	/**
	 * @return string
	 */
	public function getPink();

	/**
	 * @return string
	 */
	public function getYellow();

	/**
	 * @return string
	 */
	public function getBlue();

}