<?php
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/imageWrapper/class.ilScanAssessmentLineDetector.php');

/**
 * Class ilScanAssessmentPotentialLineDetector
 *
 * This is a relaxed check (as compared to ReliableLineDetector). it allows
 * borders on both sides of a segment. given a coverage, detects if a segment
 * contains a sub segment satisfying the coverage, such that the sub segment
 * is at least 1/4 times the full segment's length.
 *
 */
class ilScanAssessmentPotentialLineDetector extends ilScanAssessmentLineDetector
{
    protected function test($pixel, $k0, $k1)
    {
        $threshold = $this->threshold;
        $coverage = $this->coverage;

        $n = 0;
        $p = array();
        $l = 0;
        for($k = $k0; $k <= $k1; $k++)
        {
            $d = $pixel($k) < $threshold ? 1 : 0;
            $n += $d;
            array_push($p, $d);

            while(count($p) > 0 && $n / (float)count($p) < $coverage)
            {
                $d = array_shift($p);
                $n -= $d;
            }

            $l = max($l, count($p));
        }

        $total = $k1 - $k0 + 1;
        return $l > $total / 4.0;
    }
}
