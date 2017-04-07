<?php

/**
 * Class ilScanAssessmentLineDetector
 */
abstract class ilScanAssessmentLineDetector
{
    /**
     * @var
     */
    protected $threshold;

    /**
     * the relative of black pixels on a line so that it
     * is still detected as a line.
     * @var
     */
    protected $coverage;

    /**
     * @var ilScanAssessmentImageWrapper
     */
    protected $img_helper;

    public function __construct($img_helper, $threshold)
    {
        $this->img_helper = $img_helper;
        $this->threshold = $threshold;
        $this->coverage = 0.95;
    }

    /**
     * @param $pixel
     * @param $k0
     * @param $k1
     * @return bool
     */
    abstract protected function test($pixel, $k0, $k1);

    /**
     * @param $x0
     * @param $x1
     * @param $y
     * @return bool
     */
    public function horizontal($x0, $x1, $y)
    {
        $pixel = function($x) use ($y)
        {
            return $this->img_helper->getGrey(new ilScanAssessmentPoint($x, $y));
        };

        return $this->test($pixel, $x0, $x1);
    }

    /**
     * @param $x
     * @param $y0
     * @param $y1
     * @return bool
     */
    public function vertical($x, $y0, $y1)
    {
        $pixel = function($y) use ($x)
        {
            return $this->img_helper->getGrey(new ilScanAssessmentPoint($x, $y));
        };

        return $this->test($pixel, $y0, $y1);
    }
}
