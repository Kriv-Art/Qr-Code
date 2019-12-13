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
 * Jpeg image class
 */
class Jpeg extends Image
{
    /**
     * {@inheritdoc}
     */
    public function output($target = null)
    {
        $this->target = $target;
        if ($target) {
            \imagejpeg($this->data, $target);
        } else {
            \imagejpeg($this->data);
        }
        \imagedestroy($this->data);
    }
}
