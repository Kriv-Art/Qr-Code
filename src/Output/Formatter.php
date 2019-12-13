<?php
/*
 * This file is part of KrivArt QrCode.
 *
 * (c) Noah Too aka Krivah <krivahtoo@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KrivArt\QrCode\Output;

use KrivArt\QrCode\QrCode;
use KrivArt\QrCode\Exceptions\FormatterException;

/**
 * Formater Base class
 */
abstract class Formatter
{
    /**
     * Background color
     *
     * @var string
     */
    protected $backgroundColor = '#ffffff';

    /**
     * Foreground color
     *
     * @var string
     */
    protected $foregroundColor = '#000000';

    /**
     * Alignment color
     *
     * @var string
     */
    protected $alignmentColor = '#000000';

    /**
     * Position color
     *
     * @var string
     */
    protected $positionColor = '#000000';

    /**
     * Target output
     *
     * @var mixed
     */
    protected $target = null;

    /**
     * Resource data
     *
     * @var mixed
     */
    protected $data = null;

    /**
     * QrCode to format
     *
     * @var QrCode
     */
    protected $qr = null;

    /**
     * Height
     *
     * @var int
     */
    protected $height;

    /**
     * Width
     *
     * @var int
     */
    protected $width;

    /**
     * Output border
     *
     * @var int
     */
    protected $border;

    /**
     * Constructor
     *
     * @param QrCode $qr     QrCode to format
     * @param int    $height Height of the output
     * @param int    $width  Width of the output
     * @param int    $border Output border
     * @param array  $colors Output colors
     */
    public function __construct(QrCode $qr, $height = 512, $width = 512, $border = 20, $colors = [])
    {
        $this->qr     = $qr;
        $this->height = $height;
        $this->width  = $width;
        $this->border = $border;
        foreach ($colors as $color => $value) {
            $this->$color = $value;
        }
        $this->format();
    }

    /**
     * Format qrcode to a specific format
     *
     * @return $this
     */
    abstract public function format();

    /**
     * Output qrcode to a specific target
     *
     * @return mixed
     */
    abstract public function output($target);

    /**
     * Hex to RGB Color converter
     *
     * @param string $color Color fo convert
     *
     * @return array
     */
    protected function colorToRgb($color)
    {
        if (\mb_strpos($color, '#') !== 0 && \mb_strlen($color) !== 7) {
            throw new FormatterException('Invalid hexadecimal color');
        }
        $result                   = [];
        $color                    = \ltrim($color, '#');
        list($red, $green, $blue) = \str_split($color, 2);

        $result['red']   = \intval($red, 16);
        $result['green'] = \intval($green, 16);
        $result['blue']  = \intval($blue, 16);

        return $result;
    }
}
