<?php

/**
 * Class ilScanAssessmentCheckBoxAnalyser
 */
class ilScanAssessmentCheckBoxAnalyser
{
    /**
     * @var
     */
    private $image;

    /**
     * @var array
     */
    private $pixels;

    /**
     * @var array
     */
    private $bounding_box;

    /**
     * @var
     */
    private $threshold;

    /**
     * @var
     */
    private $coverage;

    /**
     * @var ilScanAssessmentImageWrapper
     */
    protected static $img_helper;

    public function __construct($image, $x, $y, $threshold, $image_helper)
    {
        $this->threshold = $threshold;
        $this->coverage = 0.75;

        $pixels = array();
        self::$img_helper = $image_helper;
        $this->gatherPixels($image, $x, $y, $threshold, $pixels);
        $this->pixels = $pixels;

        $this->bounding_box = $this->calculateBoundingBox();

        $this->image = $image;
    }

    /**
     * @return array
     */
    public function rightmost()
    {
        $x = PHP_INT_MIN;
        $y = 0;

        foreach($this->coordinates() as $pixel)
        {
            if($pixel[0] > $x)
            {
                list($x, $y) = $pixel;
            }
        }

        return array($x, $y);
    }

    /**
     * @param $image
     * @param $x
     * @param $y
     * @param $threshold
     * @param $pixels
     */
    private static function gatherPixels($image, $x, $y, $threshold, &$pixels)
    {
        // essentially a flood fill that detects all black marker pixels.

        $stack = array(array($x, $y));

        array_push($stack, array($x - 1, $y - 1));
        array_push($stack, array($x - 1, $y + 1));
        array_push($stack, array($x + 1, $y - 1));
        array_push($stack, array($x + 1, $y + 1));

        $w = imagesx($image);
        $h = imagesy($image);

        while(count($stack) > 0)
        {
            list($x, $y) = array_pop($stack);

            if($x < 0 || $y < 0 || $x >= $w || $y >= $h)
            {
                continue;
            }

            $coordinates = $x . '/' . $y;

            if(isset($pixels[$coordinates]))
            {
                continue;
            }

            if(self::$img_helper->getGrey(new ilScanAssessmentPoint($x, $y)) < $threshold) // black?
            {
                $pixels[$coordinates] = true;
                array_push($stack, array($x + 1, $y));
                array_push($stack, array($x - 1, $y));
                array_push($stack, array($x, $y + 1));
                array_push($stack, array($x, $y - 1));
            }
        }
    }

    /**
     * @return array
     */
    private function coordinates()
    {
        $coordinates = array();
        foreach(array_keys($this->pixels) as $xy)
        {
            list($x, $y) = explode('/', $xy);
            array_push($coordinates, array(intval($x), intval($y)));
        }
        return $coordinates;
    }

    /**
     * @return array
     */
    private function calculateBoundingBox()
    {
        $x = array();
        $y = array();

        foreach($this->coordinates() as $pixel)
        {
            array_push($x, $pixel[0]);
            array_push($y, $pixel[1]);
        }

        if(count($x) > 0 && count($y) > 0)
        {
            return array(min($x), min($y), max($x), max($y));
        }
        else
        {
            return false;
        }
    }

    private function testLine($pixel, $k0, $k1)
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

    private function testHorizontalLine($x0, $x1, $y)
    {
        $pixel = function($k) use ($y)
        {
            return self::$img_helper->getGrey(new ilScanAssessmentPoint($k, $y));
        };

        return $this->testLine($pixel, $x0, $x1);
    }

    private function testVerticalLine($x, $y0, $y1)
    {
        $pixel = function($k) use ($x)
        {
            return self::$img_helper->getGrey(new ilScanAssessmentPoint($x, $k));
        };

        return $this->testLine($pixel, $y0, $y1);
    }

    /**
     * Detect which side of the rectangle is faulty (i.e. does not have a continuous
     * line) and return the side's name. If the rectangle is good, return false (i.e.
     * no faulty line).
     *
     * @return string|bool
     */

    private function detectFaultySide($x0, $y0, $x1, $y1)
    {
        if(!$this->testHorizontalLine($x0, $x1, $y0))
        {
            return 'top';
        }
        else if(!$this->testHorizontalLine($x0, $x1, $y1))
        {
            return 'bottom';
        }
        else if(!$this->testVerticalLine($x0, $y0, $y1))
        {
            return 'left';
        }
        else if(!$this->testVerticalLine($x1, $y0, $y1))
        {
            return 'right';
        }
        else
        {
            return false;
        }
    }

    private function clipLeft($x0, $y0, $x1, $y1)
    {
        while(++$x0 < $x1)
        {
            if($this->testVerticalLine($x0, $y0, $y1))
            {
                return $x0;
            }
        }

        return false;
    }

    private function clipRight($x0, $y0, $x1, $y1)
    {
        while(--$x1 > $x0)
        {
            if($this->testVerticalLine($x1, $y0, $y1))
            {
                return $x1;
            }
        }

        return false;
    }

    private function clipTop($x0, $y0, $x1, $y1)
    {
        while(++$y0 < $y1)
        {
            if($this->testHorizontalLine($x0, $x1, $y0))
            {
                return $y0;
            }
        }

        return false;
    }

    private function clipBottom($x0, $y0, $x1, $y1)
    {
        while(--$y1 > $y0)
        {
            if($this->testHorizontalLine($x0, $x1, $y1))
            {
                return $y1;
            }
        }

        return false;
    }

    /**
     * @return array|bool
     */

    public function detectRectangle()
    {
        $nodes = array();

        if($this->bounding_box)
        {
            array_push($nodes, $this->bounding_box);
        }

        while(!empty($nodes))
        {
            list($x0, $y0, $x1, $y1) = array_pop($nodes);

            $faulty = $this->detectFaultySide($x0, $y0, $x1, $y1);

            if($faulty === false)
            {
                return array($x0, $y0, $x1, $y1);
            }

            // note that we add the nodes in inverse order of intended traversal, as
            // they are fetched via array_pop() for reasons of efficiency.

            switch($faulty)
            {
                case 'left':
                case 'right':
                    $y0_clipped = $this->clipTop($x0, $y0, $x1, $y1);

                    $y1_clipped = $this->clipBottom($x0, $y0, $x1, $y1);

                    if($y0_clipped !== false && $y1_clipped !== false)
                    {
                        array_push($nodes, array($x0, $y0_clipped, $x1, $y1_clipped));
                    }

                    if($y1_clipped !== false)
                    {
                        array_push($nodes, array($x0, $y0, $x1, $y1_clipped));
                    }

                    if($y0_clipped !== false)
                    {
                        array_push($nodes, array($x0, $y0_clipped, $x1, $y1));
                    }
                    break;

                case 'top':
                case 'bottom':
                    $x0_clipped = $this->clipLeft($x0, $y0, $x1, $y1);

                    $x1_clipped = $this->clipRight($x0, $y0, $x1, $y1);

                    if($x0_clipped !== false && $x1_clipped !== false)
                    {
                        array_push($nodes, array($x0_clipped, $y0, $x1_clipped, $y1));
                    }

                    if($x1_clipped !== false)
                    {
                        array_push($nodes, array($x0, $y0, $x1_clipped, $y1));
                    }

                    if($x0_clipped !== false)
                    {
                        array_push($nodes, array($x0_clipped, $y0, $x1, $y1));
                    }
                    break;

                default:
                    throw new \Exception('illegal faulty side code '. $faulty);
            }

            switch ($faulty)
            {
                case 'left':
                    $x0_clipped = $this->clipLeft($x0, $y0, $x1, $y1);
                    if($x0_clipped !== false)
                    {
                        array_push($nodes, array($x0_clipped, $y0, $x1, $y1));
                    }
                    break;

                case 'right':
                    $x1_clipped = $this->clipRight($x0, $y0, $x1, $y1);
                    if($x1_clipped !== false)
                    {
                        array_push($nodes, array($x0, $y0, $x1_clipped, $y1));
                    }
                    break;

                case 'top':
                    $y0_clipped = $this->clipTop($x0, $y0, $x1, $y1);
                    if($y0_clipped !== false)
                    {
                        array_push($nodes, array($x0, $y0_clipped, $x1, $y1));
                    }
                    break;

                case 'bottom':
                    $y1_clipped = $this->clipBottom($x0, $y0, $x1, $y1);
                    if($y1_clipped !== false)
                    {
                        array_push($nodes, array($x0, $y0, $x1, $y1_clipped));
                    }
                    break;

                default:
                    throw new \Exception('illegal faulty side code '. $faulty);
            }
        }

        return false;
    }
}
