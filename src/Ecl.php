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
 * Error Correction Level class
 */
class Ecl
{
    /**
     * The QR Code can tolerate about  7% erroneous codewords
     *
     * @var array
     */
    public const LOW      = [0, 1];

    /**
     * The QR Code can tolerate about 15% erroneous codewords
     *
     * @var array
     */
    public const MEDIUM   = [1, 0];

    /**
     * The QR Code can tolerate about 25% erroneous codewords
     *
     * @var array
     */
    public const QUARTILE = [2, 3];

    /**
     * The QR Code can tolerate about 30% erroneous codewords
     *
     * @var array
     */
    public const HIGH     = [3, 2];

    /**
     * In the range 0 to 3 (unsigned 2-bit integer).
     *
     * @var int
     */
    public $ordinal;

    /**
     * In the range 0 to 3 (unsigned 2-bit integer).
     *
     * @var int
     */
    public $formatBits;

    /**
     * The error correction level in a QR Code symbol. Immutable.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->ordinal    = $data[0];
        $this->formatBits = $data[1];
    }
}
