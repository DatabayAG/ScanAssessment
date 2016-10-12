<?php
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/imageWrapper/class.ilScanAssessmentGDWrapper.php';
require_once 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment/classes/scanner/imageWrapper/class.ilScanAssessmentImagemagickWrapper.php';


class dummy
{
	public function run()
	{
		/**
		 * @var ilScanAssessmentGDWrapper
		 */
		#$image_helper = new ilScanAssessmentGDWrapper('/home/gvollbach/Desktop/test2.jpg');
		/**
		 * @var ilScanAssessmentImagemagickWrapper
		 */
		$image_helper = new ilScanAssessmentImagemagickWrapper('/home/gvollbach/Desktop/test2.jpg');
		
		$x = $image_helper->getImageSizeX();
		$y = $image_helper->getImageSizeY();
		echo 'x:' . $x . ' ' . 'y:' . $y . ' ' . PHP_EOL;
		for($x2 = 0; $x2 < $x; $x2++)
		{
			for($y2 = 0; $y2 < $y; $y2++)
			{
				$gray =  $image_helper->getGrey(new ilScanAssessmentPoint($x2, $y2));
				if($gray < 180)
				{
					echo 'MARKED => x:' . $x2 . ' ' . 'y:' . $y2 . ' ' . $gray . PHP_EOL;
				}
				else
				{
					#echo 'x:' . $x2 . ' ' . 'y:' . $y2 . ' ' . $gray . PHP_EOL;
				}
				
			}
		}
	}
}
dummy::run();
