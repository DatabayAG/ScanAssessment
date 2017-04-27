<?php

/**
 * Class ilScanAssessmentRegion
 *
 * Describes a contiguous region (i.e. a set of pixels connected by the
 * definition of blackness with respect to a given threshold).
 */
class ilScanAssessmentRegion
{
    /**
     * @var array
     */
    private $pixels;

    /**
     * ilScanAssessmentRegion constructor.
     *
     * @param ilScanAssessmentImageWrapper $image
     * @param ilScanAssessmentPoint $p
     * @param int $threshold
     */

    public function __construct($image, $p, $threshold)
    {
        // essentially a flood fill that detects all black marker pixels.

        $x = intval($p->getX());
        $y = intval($p->getY());
        $pixels = array();

        $stack = array(array($x, $y));

        array_push($stack, array($x - 1, $y - 1));
        array_push($stack, array($x - 1, $y + 1));
        array_push($stack, array($x + 1, $y - 1));
        array_push($stack, array($x + 1, $y + 1));

        $w = $image->getImageSizeX();
        $h = $image->getImageSizeY();

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

            if($image->getGrey(new ilScanAssessmentPoint($x, $y)) < $threshold) // black?
            {
                $pixels[$coordinates] = true;
                array_push($stack, array($x + 1, $y));
                array_push($stack, array($x - 1, $y));
                array_push($stack, array($x, $y + 1));
                array_push($stack, array($x, $y - 1));
            }
        }

        $this->pixels = $pixels;
    }

    /**
     * @return generator a generator of all coordinate pairs in this region.
     */
    public function coordinates()
    {
        foreach(array_keys($this->pixels) as $xy)
        {
            list($x, $y) = explode('/', $xy);
            yield array(intval($x), intval($y));
        }
    }

    /**
     * @return ilScanAssessmentPoint|bool centre of this region or false, if there are no pixels.
     */
    public function centre()
    {
        $n = 0;

        $x = 0;
        $y = 0;

        foreach($this->coordinates() as $xy)
        {
            $x += $xy[0];
            $y += $xy[1];
            $n++;
        }

        if($n > 0)
        {
            return new ilScanAssessmentPoint($x / $n, $y / $n);
        }
        else
        {
            return false;
        }
    }

    /**
     * Compute the bounding box of $this->pixels.
     *
     * @return array|bool bounding box of this region or false, if there are no pixels.
     */
    public function bbox()
    {
        $minX = PHP_INT_MAX;
        $minY = PHP_INT_MAX;
        $maxX = PHP_INT_MIN;
        $maxY = PHP_INT_MIN;
        $n = 0;

        foreach($this->coordinates() as $xy)
        {
            list($x, $y) = $xy;
            $minX = min($minX, $x);
            $minY = min($minY, $y);
            $maxX = max($maxX, $x);
            $maxY = max($maxY, $y);
            $n++;
        }

        if($n > 0)
        {
            return array($minX, $minY, $maxX, $maxY);
        }
        else
        {
            return false;
        }
    }

    /**
     * @return array the coordinate of the rightmost pixel in this region.
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

    public function size()
    {
        return count($this->pixels);
    }

    public function dump($clip = 7)
    {
        $centre = $this->centre();
        $s = sprintf("region: size: %s, centre: [%s, %s ], pixels: ",
            $this->size(), $centre->getX(), $centre->getY());
        $s .= var_export(array_slice($this->pixels, 0, $clip), true);
        if($this->size() > $clip)
        {
            $s .= " [too many pixels]";
        }
        return $s;
    }
}
