<?php

ilScanAssessmentPlugin::getInstance()->includeClass('scanner/geometry/class.ilScanAssessmentPoint.php');
/**
 * Interface ilScanAssessmentImageWrapper
 * @author Guido Vollbach <gvollbach@databay.de>
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

	/**
	 * @return mixed
	 */
	public function removeBlackBorder();

	/**
	 * @param $filename
	 * @return mixed
	 */
	public function createNewImageInstanceFromFileName($filename);
	
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

	/**
	 * @param $image
	 * @param $rect
	 * @return mixed
	 */
	function imageCrop($image, $rect);


	/**
	 * @param $image
	 * @param ilScanAssessmentPoint $point1
	 * @param ilScanAssessmentPoint $point2
	 * @return mixed
	 */
	function imageCropByPoints($image, $point1, $point2);

	/**
	 * @param      $image
	 * @param      $src_x
	 * @param      $src_y
	 * @param      $dest_x
	 * @param      $dest_y
	 * @param null $filename
	 * @return mixed
	 */
	function imageCropWithSource($image, $src_x, $src_y, $dest_x, $dest_y, $filename = null);

}