<?php
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/imageWrapper/class.ilScanAssessmentReliableLineDetector.php');
ilScanAssessmentPlugin::getInstance()->includeClass('scanner/imageWrapper/class.ilScanAssessmentPotentialLineDetector.php');

/**
 * Class ilScanAssessmentCheckBoxAnalyser
 */
class ilScanAssessmentCheckBoxAnalyser
{
    /**
     * @var array
     */
    private $origin;

    /**
     * @var array
     */
    private $expected_size;

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
     * @var float
     */
    private $minimal;

    /**
     * @var
     */
    private $threshold;

    /**
     * matches lines if all pixels on the respective line are black.
     *
     * @var ilScanAssessmentReliableLineDetector
     */
    private $reliable_line;

    /**
     * matches lines if a segment of pixels on the respective line are
     * black. this also matches lines, if we have a significant border
     * before and after the actual line segment.
     *
     * @var ilScanAssessmentPotentialLineDetector
     */
    private $potential_line;

    /**
     * @var ilScanAssessmentImageWrapper
     */
    protected static $img_helper;

	/**
	 * ilScanAssessmentCheckBoxAnalyser constructor.
	 * @param $image
	 * @param $x
	 * @param $y
	 * @param $threshold
	 * @param $image_helper
	 */
    public function __construct($image, $x, $y, $size, $threshold, $image_helper)
    {
        $this->origin = array($x, $y);
        $this->expected_size = $size;
        $this->threshold = $threshold;

        $pixels = array();
        self::$img_helper = $image_helper;
        $this->gatherPixels($image, $x, $y, $threshold, $pixels);
        $this->pixels = $pixels;

        $this->bounding_box = $this->calculateBoundingBox();

        $this->image = $image;

        $this->reliable_line = new ilScanAssessmentReliableLineDetector($image_helper, $threshold);
        $this->potential_line = new ilScanAssessmentPotentialLineDetector($image_helper, $threshold);
    }

    /**
     * @return array the coordinate of the rightmost pixel in $this->pixels.
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
     * @return array an array of all coordinates in $this->pixels.
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
     * Compute the bounding box of $this->pixels.
     *
	 * @return array|bool bounding box or false, if there are no pixels.
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

	/**
     * Detect which side of the rectangle is faulty (i.e. does not have a continuous
     * line) and return the side's name. If the rectangle is good, return false (i.e.
     * no faulty line).

	 * @param integer $x0
	 * @param integer $y0
	 * @param integer $x1
	 * @param integer $y1
	 * @return bool|string false, if no side is faulty (i.e. it's a good rectangle), or
     * one of 'top', 'bottom', 'left' or 'right'
	 */

    private function detectFaultySide($x0, $y0, $x1, $y1)
    {
        if(!$this->reliable_line->horizontal($x0, $x1, $y0))
        {
            return 'top';
        }
        else if(!$this->reliable_line->horizontal($x0, $x1, $y1))
        {
            return 'bottom';
        }
        else if(!$this->reliable_line->vertical($x0, $y0, $y1))
        {
            return 'left';
        }
        else if(!$this->reliable_line->vertical($x1, $y0, $y1))
        {
            return 'right';
        }
        else
        {
            return false;
        }
    }

    /**
     * Pick the ilScanAssessmentLineDetector suitable for the given
     * search depth.
     *
     * @param integer $depth
     * @return ilScanAssessmentLineDetector
     */
    private function lineDetectorForDepth($depth)
    {
        if($depth <= 1)
        {
            // at levels 0..1, we allow potential matches. this allows
            // us to deal with checkboxes whose bounding box is not
            // aligned with the borders on 3 or more sides due to excess
            // of the marker lines, e.g. (X are the marker lines):

            //     X       |   match:
            //  X#X##      |   *
            //  #X X#   <- |   *
            //  X###X      |   *
            // X     X     |

            // in this case, neither top, left, bottom or right
            // yield a reliable border. for example, on the right, the
            // line is too high due to the excess at the top and at
            // the bottom (see illustration above). in order to match
            // the right border (say, depth 0), we need to resort to
            // $this->potential_line matcher. the same is true for the
            // left border (i.e. depth 1).

            // only after two sides have been cleaned this way, can we now
            // (depth >= 2) proceed with $this->reliable_line to match the
            // top and bottom lines.

            return $this->potential_line;
        }
        else
        {
            return $this->reliable_line;
        }
    }

	/**
     * Increase $x0 until we hit a vertical line as defined by $test.
     *
     * @param ilScanAssessmentLineDetector $test
	 * @param integer $x0
	 * @param integer $y0
	 * @param integer $x1
	 * @param integer $y1
	 * @return integer|bool new $x0 or false, if no line was found.
	 */
    private function clipLeft($test, $x0, $y0, $x1, $y1)
    {
        while(++$x0 < $x1)
        {
            if($test->vertical($x0, $y0, $y1))
            {
                return $x0;
            }
        }

        return false;
    }

	/**
     * Decrease $x1 until we hit a vertical line as defined by $test.
     *
     * @param ilScanAssessmentLineDetector $test
     * @param integer $x0
     * @param integer $y0
     * @param integer $x1
     * @param integer $y1
	 * @return integer|bool new $x1 or false, if no line was found.
	 */
    private function clipRight($test, $x0, $y0, $x1, $y1)
    {
        while(--$x1 > $x0)
        {
            if($test->vertical($x1, $y0, $y1))
            {
                return $x1;
            }
        }

        return false;
    }

	/**
     * Increase $y0 until we hit a horizontal line as defined by $test.
     *
     * @param ilScanAssessmentLineDetector $test
	 * @param integer $x0
	 * @param integer $y0
	 * @param integer $x1
	 * @param integer $y1
	 * @return integer|bool new $y0 or false, if no line was found.
	 */
    private function clipTop($test, $x0, $y0, $x1, $y1)
    {
        while(++$y0 < $y1)
        {
            if($test->horizontal($x0, $x1, $y0))
            {
                return $y0;
            }
        }

        return false;
    }

	/**
     * Decrease $y1 until we hit a horizontal line as defined by $test.
     *
     * @param ilScanAssessmentLineDetector $test
	 * @param integer $x0
	 * @param integer $y0
	 * @param integer $x1
	 * @param integer $y1
	 * @return integer|bool new $y1 or false, if no line was found.
	 */
    private function clipBottom($test, $x0, $y0, $x1, $y1)
    {
        while(--$y1 > $y0)
        {
            if($test->horizontal($x0, $x1, $y1))
            {
                return $y1;
            }
        }

        return false;
    }

    /**
     * Detect if the current checkbox rectangle is too large or too small for one
     * checkbox. If it's too large, it's probably due to connected checkboxes; this
     * happens if gatherPixels detects two or more checkboxes as one component due
     * to a connecting line making them one component, e.g.:
     *
     * X####  XX#####
     * #X  #XX  #   #
     * #  XX    #   #
     * #XX#X    #####
     *
     * in this case, we make the rectangle smaller along that side which, compared to
     * the center of the checkbox we search for, is most probably excess. if the
     * rectangle is too small, we abort the search.
     *
     * @param $x0
     * @param $y0
     * @param $x1
     * @param $y1
     * @return bool|array false to abort the search, or an array with the rectangle
     * rectangle coordinates, which might be a reduced version of the input or the
     * same as the input
     */
    private function reduceRectangle($x0, $y0, $x1, $y1)
    {
        // detect if we actually ended up outlining two or more connected boxes
        // and restrict our search box accordingly through clipping.

        while($y1 - $y0 > $this->expected_size[1] * 1.75)
        {
            if(abs($this->origin[1] - $y0) < abs($this->origin[1] - $y1))
            {
                $y1 = $this->clipBottom($this->reliable_line, $x0, $y0, $x1, $y1);
                if ($y1 === false)
                {
                    return false;
                }
            }
            else
            {
                $y0 = $this->clipTop($this->reliable_line, $x0, $y0, $x1, $y1);
                if ($y0 === false)
                {
                    return false;
                }
            }
        }

        while($x1 - $x0 > $this->expected_size[0] * 1.75)
        {
            if(abs($this->origin[0] - $x0) < abs($this->origin[0] - $x1))
            {
                $x1 = $this->clipRight($this->reliable_line, $x0, $y0, $x1, $y1);
                if ($x1 === false)
                {
                    return false;
                }
            }
            else
            {
                $x0 = $this->clipLeft($this->reliable_line, $x0, $y0, $x1, $y1);
                if ($x0 === false)
                {
                    return false;
                }
            }
        }

        if($x1 - $x0 < $this->expected_size[0] * 0.75)
        {
            return false;
        }

        if($y1 - $y0 < $this->expected_size[1] * 0.75)
        {
            return false;
        }

        return array($x0, $y0, $x1, $y1);
    }

	/**
     * Detect the rectangle coordinates of the checkbox. If no checkbox
     * rectangle can be identified, false is returned.
     *
	 * @return array|bool checkbox rectangle or false
	 * @throws Exception
	 */
    public function detectRectangle()
    {
        $nodes = array();

        if($this->bounding_box)
        {
            array_push($nodes, array_merge($this->bounding_box, array(0)));
        }

        while(!empty($nodes))
        {
            list($x0, $y0, $x1, $y1, $depth) = array_pop($nodes);

            $reduced = $this->reduceRectangle($x0, $y0, $x1, $y1);

            if($reduced === false)
            {
                continue;
            }

            list($x0, $y0, $x1, $y1) = $reduced;

            $faulty = $this->detectFaultySide($x0, $y0, $x1, $y1);

            if($faulty === false)
            {
                return array($x0, $y0, $x1, $y1);
            }

            // note that we add the nodes in inverse order of intended traversal, as
            // they are fetched via array_pop() for reasons of efficiency.

            $detector = $this->lineDetectorForDepth($depth);

            switch($faulty)
            {
                case 'left':
                case 'right':
                    $y0_clipped = $this->clipTop($detector, $x0, $y0, $x1, $y1);

                    $y1_clipped = $this->clipBottom($detector, $x0, $y0, $x1, $y1);

                    if($y0_clipped !== false && $y1_clipped !== false)
                    {
                        array_push($nodes, array($x0, $y0_clipped, $x1, $y1_clipped, $depth + 1));
                    }

                    if($y1_clipped !== false)
                    {
                        array_push($nodes, array($x0, $y0, $x1, $y1_clipped, $depth + 1));
                    }

                    if($y0_clipped !== false)
                    {
                        array_push($nodes, array($x0, $y0_clipped, $x1, $y1, $depth + 1));
                    }
                    break;

                case 'top':
                case 'bottom':
                    $x0_clipped = $this->clipLeft($detector, $x0, $y0, $x1, $y1);

                    $x1_clipped = $this->clipRight($detector, $x0, $y0, $x1, $y1);

                    if($x0_clipped !== false && $x1_clipped !== false)
                    {
                        array_push($nodes, array($x0_clipped, $y0, $x1_clipped, $y1, $depth + 1));
                    }

                    if($x1_clipped !== false)
                    {
                        array_push($nodes, array($x0, $y0, $x1_clipped, $y1, $depth + 1));
                    }

                    if($x0_clipped !== false)
                    {
                        array_push($nodes, array($x0_clipped, $y0, $x1, $y1, $depth + 1));
                    }
                    break;

                default:
                    throw new \Exception('illegal faulty side code '. $faulty);
            }

            switch ($faulty)
            {
                case 'left':
                    $x0_clipped = $this->clipLeft($detector, $x0, $y0, $x1, $y1);
                    if($x0_clipped !== false)
                    {
                        array_push($nodes, array($x0_clipped, $y0, $x1, $y1, $depth + 1));
                    }
                    break;

                case 'right':
                    $x1_clipped = $this->clipRight($detector, $x0, $y0, $x1, $y1);
                    if($x1_clipped !== false)
                    {
                        array_push($nodes, array($x0, $y0, $x1_clipped, $y1, $depth + 1));
                    }
                    break;

                case 'top':
                    $y0_clipped = $this->clipTop($detector, $x0, $y0, $x1, $y1);
                    if($y0_clipped !== false)
                    {
                        array_push($nodes, array($x0, $y0_clipped, $x1, $y1, $depth + 1));
                    }
                    break;

                case 'bottom':
                    $y1_clipped = $this->clipBottom($detector, $x0, $y0, $x1, $y1);
                    if($y1_clipped !== false)
                    {
                        array_push($nodes, array($x0, $y0, $x1, $y1_clipped, $depth + 1));
                    }
                    break;

                default:
                    throw new \Exception('illegal faulty side code '. $faulty);
            }
        }

        return false;
    }
}
