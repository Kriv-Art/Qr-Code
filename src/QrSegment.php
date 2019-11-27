<?php
/*
 * This file is part of KrivArt QrCode.
 *
 * (c) Noah Too aka Krivah <krivahtoo@gmail.com>
 */
namespace KrivArt\QrCode;

use Exception;

/**
 * QrSegment class
 */
class QrSegment
{
    public const ALPHANUMERIC_CHARSET = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:";
    public $mode;
    public $numChars;
    public $bitData;

    public function __construct($mode, int $numChars, array $bitData)
    {
        if ($numChars < 0) {
            throw new Exception("Invalid argument");
        }
        $this->mode = $mode;
        $this->numChars = $numChars;
        $this->bitData = $bitData;
    }

    public function getData()
    {
        $bitData = array_slice($this->bitData, 0);
        return $bitData;
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
        if ($text == "") {
            return [];
        } else {
            return [self::makeAlphanumeric($text)];
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
        $bb = [];
        $i = 0;
        $text1 = str_split($text);
        for ($i; $i + 2 <= strlen($text); $i += 2) { 
            $temp = strpos(self::ALPHANUMERIC_CHARSET, $text1[$i]) * 45;
            $temp += strpos(self::ALPHANUMERIC_CHARSET, $text1[$i + 1]);
            QrCode::appendBits($temp, 11, $bb);
        }
        if ($i < strlen($text)) {
            QrCode::appendBits(strpos(self::ALPHANUMERIC_CHARSET, $text1[$i]), 6, $bb);
        }
        return new QrSegment(new Mode(), strlen($text), $bb);
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
                return null;  // The segment's length doesn't fit the field's bit width
            }
            $result += 4 + $ccbits + count($seg->bitData);
        }
        return $result;
    }
}
