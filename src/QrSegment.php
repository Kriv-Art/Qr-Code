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

use Exception;

/**
 * QrSegment class
 */
class QrSegment
{
    public const ALPHANUMERIC_CHARSET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:';
    public $mode;
    public $numChars;
    public $bitData;

    public function __construct($mode, int $numChars, array $bitData)
    {
        if ($numChars < 0) {
            throw new Exception('Invalid argument');
        }
        $this->mode     = $mode;
        $this->numChars = $numChars;
        $this->bitData  = $bitData;
    }

    public function getData()
    {
        $bitData = \array_slice($this->bitData, 0);

        return $bitData;
    }

    /**
     * Returns a new mutable list of zero or more segments to represent the given Unicode text string.
     *
     * @param string $text Description
     *
     * @return array
     **/
    public static function makeBytes($data)
    {
        $bb = [];
        foreach ($data as $b => $val) {
            QrCode::appendBits($b, 8, $bb);
        }

        return new self(new Mode(Mode::BYTE), \count($data), $bb);
    }

    /**
     * Returns a new mutable list of zero or more segments to represent the given Unicode text string.
     *
     * @param string $text Description
     *
     * @return array
     **/
    public static function makeNumeric($digits)
    {
        // if (!this.NUMERIC_REGEX.test(digits))
        //     throw "String contains non-numeric characters";
        $bb = [];
        for ($i = 0; $i < \count($digits);) { // Consume up to 3 digits per iteration
            $n = \min(\count($digits) - $i, 3);
            QrCode::appendBits(\intval(\mb_substr($digits, $i, $n), 10), $n * 3 + 1, $bb);
            $i += $n;
        }

        return new self(new Mode(Mode::NUMERIC), \count($digits), bb);
    }

    /**
     * Returns a new mutable list of zero or more segments to represent the given Unicode text string.
     *
     * @param string $text Description
     *
     * @return array
     **/
    public static function makeSegments($text)
    {
        // Select the most efficient segment encoding automatically
        if ($text == '') {
            return [];
        } elseif (\preg_match('/^[0-9]*$/', $text)) {
            return [self::makeNumeric($text)];
        } elseif (\preg_match("/^[A-Z0-9 $%*+.\/:-]*$/", $text)) {
            return [self::makeAlphanumeric($text)];
        } else {
            return [self::makeBytes(self::toUtf8ByteArray($text))];
        }
    }

    /**
     * Undocumented function long description
     *
     * @param string $text Description
     *
     * @return $this
     *
     * @throws \Exception
     **/
    public static function makeAlphanumeric($text)
    {
        $bb    = [];
        $i     = 0;
        $text1 = \str_split($text);
        for ($i; $i + 2 <= \mb_strlen($text); $i += 2) {
            $temp = \mb_strpos(self::ALPHANUMERIC_CHARSET, $text1[$i]) * 45;
            $temp += \mb_strpos(self::ALPHANUMERIC_CHARSET, $text1[$i + 1]);
            QrCode::appendBits($temp, 11, $bb);
        }
        if ($i < \mb_strlen($text)) {
            QrCode::appendBits(\mb_strpos(self::ALPHANUMERIC_CHARSET, $text1[$i]), 6, $bb);
        }

        return new self(new Mode(Mode::ALPHANUMERIC), \mb_strlen($text), $bb);
    }

    /**
     * Undocumented function long description
     *
     * @param string $text Description
     *
     * @return $this
     *
     * @throws \Exception
     **/
    public static function makeEci($assignVal)
    {
        $bb = [];
        if ($assignVal < 0) {
            throw new Exception('ECI assignment value out of range');
        } elseif ($assignVal < (1 << 7)) {
            QrCode::appendBits($assignVal, 8, $bb);
        } elseif ($assignVal < (1 << 14)) {
            QrCode::appendBits(2, 2, $bb);
            QrCode::appendBits($assignVal, 14, $bb);
        } elseif ($assignVal < 1000000) {
            QrCode::appendBits(6, 3, $bb);
            QrCode::appendBits($assignVal, 21, $bb);
        } else {
            throw new Exception('ECI assignment value out of range');
        }

        return new self(new Mode(Mode::ECI), 0, $bb);
    }

    /**
     * Undocumented function long description
     *
     * @param string $text Description
     *
     * @return $this
     *
     * @throws \Exception
     **/
    public static function getTotalBits($segs, $version)
    {
        $result = 0;
        foreach ($segs as $seg) {
            $ccbits = $seg->mode->numCharCountBits($version);
            if ($seg->numChars >= (1 << $ccbits)) {
                return;  // The segment's length doesn't fit the field's bit width
            }
            $result += 4 + $ccbits + \count($seg->bitData);
        }

        return $result;
    }

    /**
     * Undocumented function long description
     *
     * @param string $text Description
     *
     * @return $this
     *
     * @throws \Exception
     **/
    public static function toUtf8ByteArray($str)
    {
        // $str = urlencode($str);
        $str    = \rawurlencode($str);
        $result = [];
        $strs   = \str_split($str);
        for ($i = 0; $i < \mb_strlen($str); $i++) {
            if ($strs[$i] != '%') {
                $result[] = $strs[$i];
            } else {
                $result[] = \intval(\mb_substr($str, $i + 1, 2), 16);
                $i += 2;
            }
        }

        return $result;
    }
}
