<?php
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/imageWrapper/class.ilScanAssessmentLineDetector.php');

/**
 * Class ilScanAssessmentReliableLineDetector
 */
class ilScanAssessmentReliableLineDetector extends ilScanAssessmentLineDetector
{
    protected function test($pixel, $k0, $k1)
    {
        $threshold = $this->threshold;
        $coverage = $this->coverage;

        $total = (float)($k1 - $k0 + 1);

        $n = 0;
        for($k = $k0; $k <= $k1; $k++)
        {
            if($pixel($k) < $threshold)
            {
                $n++;
            }
            else
            {
                // early exit: even if all remaining pixels turn
                // out to be good, can the coverage be reached?
                $r = $k1 - $k;
                if(($n + $r) / $total < $coverage)
                {
                    return false;
                }
            }
        }

        return $n / $total >= $coverage;
    }
}
