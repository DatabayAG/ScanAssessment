<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/geometry/class.ilScanAssessmentPoint.php';

/**
 * Interface ilScanAssessmentImageHelper
 */
interface ilScanAssessmentImageHelper
{

	public function __construct($fn);

	public function getColor(ilScanAssessmentPoint $point);

	public function getGrey(ilScanAssessmentPoint $point);

	public function removeBlackBorder();

	public function rotate($rad);

	public function drawLine($temp_img, $start_x, $start_y, $end_x, $end_y, $color);

	public function drawPixel($temp_img, $point, $color);

	public function drawSquareFromVector($temp_img, $vector, $color);

	public function drawSquareFromTwoPoints($temp_img, $first, $second, $color);

	public function getImageSizeY();

	public function getImageSizeX();

	public function drawTempImage($img, $fn);

}