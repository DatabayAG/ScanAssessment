# ILIAS ScanAssessment Plugin 0.1.0
* For ILIAS versions: 5.1.0 - 5.2.999

## Important notice
Please evaluate the functions of this plugin to ensure your hardware (e.g. scanner, printer) are suited for this usage.

## System Prerequisites
* PHP GD library
* PHP BC MATH

## Installation Instructions
1. Clone this repository to <ILIAS_DIRECTORY>/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/ScanAssessment
2. Login to ILIAS with an administrator account (e.g. root)
3. Select **Plugins** from the **Administration** main menu drop down.
4. Search the **ScanAssessment** plugin in the list of plugin and choose **Activate** from the **Actions** drop down.
5. Choose **Configure** from the **Actions** drop down and enter the required data.
	1. Make sure the settings for the detection in your plugin config suites your hardware (will probably needs some testing on your part)  
		1. **Minimum Black Value** => The value which will be recognized as black
		2. **Minimum marked area** => The minimum value which will be recognized as marked checkbox
		3. **Checked** => The value where a checkbox is recognized as checked
		4. **Unchecked** => The maximum value where a checkbox is recognized as checked, if the value s equal or higher it will be assumed the checkbox was fully filled by the student, to mark his answer as not given. 

## Known Problems
1. The scan process is at the moment implemented in PHP which is not the optimal solution, so a scanner in a programming language which is more suited for the job of image analytics should be implemented in the future.
2. There was a problem with higher rotation values of the scanned images, which should be reduced by now, but needs still evaluation on a bigger scope.
3. There are still some usability issues.