<?php
/*
 * This file is part of KrivArt QrCode.
 *
 * (c) Noah Too aka Krivah <krivahtoo@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace KrivArt\QrCode;

/**
 * Mode class
 *
 * Describes how a segment's data bits are interpreted. Immutable.
 */
class Mode
{
    public const NUMERIC      = [0x1, [10, 12, 14]];
    public const ALPHANUMERIC = [0x2, [9, 11, 13]];
    public const BYTE         = [0x4, [8, 16, 16]];
    public const KANJI        = [0x8, [8, 10, 12]];
    public const ECI          = [0x7, [0,  0,  0]];

    /**
     * The mode indicator bits, which is a uint4 value (range 0 to 15).
     *
     * @var int
     */
    public $modeBits;

    /**
     * Number of character count bits for three different version ranges.
     *
     * @var array
     */
    public $numBitsCharCount;

    /**
     * Constructor function
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->modeBits         = $data[0];
        $this->numBitsCharCount = $data[1];
    }

    /**
     * Returns the bit width of the character count field for a segment in
     * this mode in a QR Code at the given version number. The result is in the range [0, 16].
     */
    public function numCharCountBits($ver)
    {
        return $this->numBitsCharCount[\floor(($ver + 7) / 17)];
    }
}
