<?php
/*
 * This file is part of KrivArt QrCode.
 *
 * (c) Noah Too aka Krivah <krivahtoo@gmail.com>
 */
namespace KrivArt\QrCode;

/**
 * Ecc class
 */
class Ecc
{
    public const LOW      = [0, 1];  // The QR Code can tolerate about  7% erroneous codewords
    public const MEDIUM   = [1, 0];  // The QR Code can tolerate about 15% erroneous codewords
    public const QUARTILE = [2, 3];  // The QR Code can tolerate about 25% erroneous codewords
    public const HIGH     = [3, 2];  // The QR Code can tolerate about 30% erroneous codewords

    public $ordinal;
    public $formatBits;

    public function __construct(array $data)
    {
        $this->ordinal = $data[0];
        $this->formatBits = $data[1];
    }
}