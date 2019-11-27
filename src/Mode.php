<?php
/*
 * This file is part of KrivArt QrCode.
 *
 * (c) Noah Too aka Krivah <krivahtoo@gmail.com>
 */
namespace KrivArt\QrCode;

/**
 * Mode class
 */
class Mode
{
    public const NUMERIC      = [0x1, [10, 12, 14]];
    public const ALPHANUMERIC = [0x2, [ 9, 11, 13]];
    public const BYTE         = [0x4, [ 8, 16, 16]];
    public const KANJI        = [0x8, [ 8, 10, 12]];
    public const ECI          = [0x7, [ 0,  0,  0]];

    public $modeBits;
    public $numBitsCharCount;
    public function __construct(int $modeBits = 0x2,array $numBitsCharCount = [ 9, 11, 13])
    {
        $this->modeBits = $modeBits;
        $this->numBitsCharCount = $numBitsCharCount;
    }
    public function numCharCountBits($ver)
    {
        return $this->numBitsCharCount[floor(($ver + 7) / 17)];
    }
}