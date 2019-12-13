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

/**
 * Scalable vector graphic class
 */
class Svg extends Formatter
{
    /**
     * {@inheritdoc}
     */
    public function format()
    {
        $border = $this->border;
        if ($border === 20 || $border === null) {
            $border = 4;
        }
        if ($border < 0) {
            throw new Exception('Border must be non-negative');
        }
        $parts     = [];
        $positions = [];
        for ($y = 0; $y < $this->qr->size; $y++) {
            for ($x = 0; $x < $this->qr->size; $x++) {
                if ($this->qr->getModule($x, $y)) {
                    if ($x <= 7 && $y <= 7) {
                        $path = 'M' . ($x + $border) . ',' . ($y + $border) . 'h1v1h-1z';
                        \array_push($positions, $path);
                    } else {
                        $path = 'M' . ($x + $border) . ',' . ($y + $border) . 'h1v1h-1z';
                        \array_push($parts, $path);
                    }
                }
            }
        }
        $w      = $this->qr->size + ($border * 2);
        $result = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $result .= '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">' . PHP_EOL;
        $result .= "<svg xmlns=\"http://www.w3.org/2000/svg\" version=\"1.1\" viewBox=\"0 0 {$w} {$w}\" stroke=\"none\">\n";
        $result .= "\t<rect width=\"100%\" height=\"100%\" fill=\"{$this->backgroundColor}\" />\n";
        $result .= "\t<path d=\"" . \implode(' ', $positions) . "\" fill=\"{$this->positionColor}\" />\n";
        $result .= "\t<path d=\"" . \implode(' ', $parts) . "\" fill=\"{$this->foregroundColor}\" />\n";
        $result .= '</svg>';

        $this->data = $result;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function output($target = null)
    {
        if ($target !== null) {
            return \file_put_contents($target, $this->data);
        }

        return $this->data;
    }
}
