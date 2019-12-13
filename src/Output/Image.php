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

use KrivArt\QrCode\Exceptions\FormatterException;

/**
 * Image class
 */
class Image extends Formatter
{
    /**
     * {@inheritdoc}
     */
    public function format()
    {
        $image = \imagecreatetruecolor($this->width, $this->height);
        if (!$image) {
            throw new FormatterException('Could not create an image');
        }
        if ($this->backgroundColor) {
            $colors     = $this->colorToRgb($this->backgroundColor);
            $background = \imagecolorallocate($image, $colors['red'], $colors['green'], $colors['blue']);
        } else {
            $background = \imagecolorallocatealpha($image, 0, 0, 0, 127);
        }
        \imagefill($image, 0, 0, $background);
        $qr               = $this->qr;
        $border           = $this->border;
        $width            = (int) ($this->width - ($border * 2)) / $qr->size;
        $foregroundColor  = $this->colorToRgb($this->foregroundColor);
        $positionColor    = $this->colorToRgb($this->positionColor);
        $color            = \imagecolorallocate(
            $image,
            $foregroundColor['red'],
            $foregroundColor['green'],
            $foregroundColor['blue']
        );

        for ($y = -$border; $y < $qr->size + $border; $y++) {
            for ($x = -$border; $x < $qr->size + $border; $x++) {
                $w = $border + ($width * $x);
                $h = $border + ($width * $y);
                if ($qr->getModule($x, $y)) {
                    if ($x <= 7 && $y <= 7) {
                        $color = \imagecolorallocate(
                            $image,
                            $positionColor['red'],
                            $positionColor['green'],
                            $positionColor['blue']
                        );
                    }
                    \imagefilledrectangle($image, $w, $h, $w + $width, $h + $width, $color);
                }
            }
        }
        $this->data = $image;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function output($target)
    {
        $this->target = $target;
    }
}
