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
 * QR Code symbol class
 *
 * A QR Code symbol, which is a type of two-dimension barcode.
 * Instances of this class represent an immutable square grid of black and white cells.
 * The class provides static factory functions to create a QR Code from text or binary data.
 * The class covers the QR Code Model 2 specification, supporting all versions (sizes)
 * from 1 to 40, all 4 error correction levels, and 4 character encoding modes.
 */
class QrCode
{
    /**
     * The minimum version number supported in the QR Code Model 2 standard.
     *
     * @var int
     */
    public const MIN_VERSION              = 1;

    /**
     * The maximum version number supported in the QR Code Model 2 standard.
     *
     * @var int
     */
    public const MAX_VERSION              = 40;

    /**
     * #@+
     * For use in getPenaltyScore(), when evaluating which mask is best.
     *
     * @var int
     */
    private const PENALTY_N1              = 3;
    private const PENALTY_N2              = 3;
    private const PENALTY_N3              = 40;
    private const PENALTY_N4              = 10;
    /**
     * #@-
     */
    
    private const ECC_CODEWORDS_PER_BLOCK = [
        // Version: (note that index 0 is for padding, and is set to an illegal value)
        //0,  1,  2,  3,  4,  5,  6,  7,  8,  9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40    Error correction level
        [-1,  7, 10, 15, 20, 26, 18, 20, 24, 30, 18, 20, 24, 26, 30, 22, 24, 28, 30, 28, 28, 28, 28, 30, 30, 26, 28, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30],  // Low
        [-1, 10, 16, 26, 18, 24, 16, 18, 22, 22, 26, 30, 22, 22, 24, 24, 28, 28, 26, 26, 26, 26, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28, 28],  // Medium
        [-1, 13, 22, 18, 26, 18, 24, 18, 22, 20, 24, 28, 26, 24, 20, 30, 24, 28, 28, 26, 30, 28, 30, 30, 30, 30, 28, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30],  // Quartile
        [-1, 17, 28, 22, 16, 22, 28, 26, 26, 24, 28, 24, 28, 22, 24, 24, 30, 28, 28, 26, 28, 30, 24, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30],  // High
    ];

    private const NUM_ERROR_CORRECTION_BLOCKS = [
        // Version: (note that index 0 is for padding, and is set to an illegal value)
        //0, 1, 2, 3, 4, 5, 6, 7, 8, 9,10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40    Error correction level
        [-1, 1, 1, 1, 1, 1, 2, 2, 2, 2, 4,  4,  4,  4,  4,  6,  6,  6,  6,  7,  8,  8,  9,  9, 10, 12, 12, 12, 13, 14, 15, 16, 17, 18, 19, 19, 20, 21, 22, 24, 25],  // Low
        [-1, 1, 1, 1, 2, 2, 4, 4, 4, 5, 5,  5,  8,  9,  9, 10, 10, 11, 13, 14, 16, 17, 17, 18, 20, 21, 23, 25, 26, 28, 29, 31, 33, 35, 37, 38, 40, 43, 45, 47, 49],  // Medium
        [-1, 1, 1, 2, 2, 4, 4, 6, 6, 8, 8,  8, 10, 12, 16, 12, 17, 16, 18, 21, 20, 23, 23, 25, 27, 29, 34, 34, 35, 38, 40, 43, 45, 48, 51, 53, 56, 59, 62, 65, 68],  // Quartile
        [-1, 1, 1, 2, 4, 4, 4, 5, 6, 8, 8, 11, 11, 16, 16, 18, 16, 19, 21, 25, 25, 25, 34, 30, 32, 35, 37, 40, 42, 45, 48, 51, 54, 57, 60, 63, 66, 70, 74, 77, 81],  // High
    ];
    /**
     * The modules of this QR Code (false = white, true = black).
     *
     * @var array
     */
    public $modules = [];

    /**
     * Indicates function modules that are not subjected to masking. Discarded when constructor finishes.
     *
     * @var array
     */
    public $isFunction = [];

    /**
     * The size of the qrcode depending on the version
     *
     * @var int
     */
    public $size = 0;

    /**
     * The version number of this QR Code, which is between 1 and 40 (inclusive).
     * This determines the size of this barcode.
     *
     * @var int
     */
    public $version;

    /**
     * The error correction level used in this QR Code.
     *
     * @var Ecl
     */
    public $errorCorrectionLevel;

    /**
     * @var array
     */
    public $dataCodewords;

    /**
     * The index of the mask pattern used in this QR Code, which is between 0 and 7 (inclusive).
     *
     * @var int
     */
    public $mask;

    /**
     * Construct function
     *
     * Creates a new QR Code with the given version number, error correction level, data codeword bytes, and mask number.
     * This is a low-level API that most users should not use directly.
     * A mid-level API is the encodeSegments() function.
     *
     * @param int   $version              The version number of this QR Code, which is between 1 and 40 (inclusive)
     * @param Ecl   $errorCorrectionLevel The error correction level used in this QR Code.
     * @param array $dataCodewords
     * @param int   $mask                 The index of the mask pattern used in this QR Code, which is between 0 and 7 (inclusive).
     *
     * @throws \Exception
     **/
    public function __construct(int $version, Ecl $errorCorrectionLevel, array $dataCodewords, int $mask)
    {
        $this->version              = $version;
        $this->errorCorrectionLevel = $errorCorrectionLevel;
        $this->dataCodewords        = $dataCodewords;
        $this->mask                 = $mask;

        // Check scalar arguments
        if ($version < self::MIN_VERSION || $version > self::MAX_VERSION) {
            throw new Exception('Version value out of range');
        }
        if ($mask < -1 || $mask > 7) {
            throw new Exception('Mask value out of range');
        }
        $this->size = $version * 4 + 17;

        // Initialize both grids to be size*size arrays of Boolean false
        $row = [];
        for ($i = 0; $i < $this->size; $i++) {
            \array_push($row, false);
        }
        for ($i = 0; $i < $this->size; $i++) {
            \array_push($this->modules, \array_slice($row, 0));  // Initially all white
            \array_push($this->isFunction, \array_slice($row, 0));
        }

        // Compute ECC, draw modules
        $this->drawFunctionPatterns();
        $allCodewords = $this->addEccAndInterleave($dataCodewords);
        $this->drawCodewords($allCodewords);

        // Do masking
        if ($mask == -1) {  // Automatically choose best mask
            $minPenalty = 1000000000;
            for ($i = 0; $i < 8; $i++) {
                $this->applyMask($i);
                $this->drawFormatBits($i);
                $penalty = $this->getPenaltyScore();
                if ($penalty < $minPenalty) {
                    $mask       = $i;
                    $minPenalty = $penalty;
                }
                $this->applyMask($i);  // Undoes the mask due to XOR
            }
        }
        if ($mask < 0 || $mask > 7) {
            throw new Exception('Assertion error');
        }
        $this->mask = $mask;
        $this->applyMask($mask);  // Apply the final choice of mask
        $this->drawFormatBits($mask);  // Overwrite old format bits

        $this->isFunction = [];
    }

    /**
     * Returns a QR Code representing the given Unicode text string at the given error correction level.
     *
     * @param string $text Text to encode
     * @param Ecl    $ecl  Error correction level
     *
     * @throws \Exception
     **/
    public static function encodeText(string $text, Ecl $ecl)
    {
        $segs = QrSegment::makeSegments($text);

        return self::encodeSegments($segs, $ecl);
    }

    /**
     * Returns a string of SVG code for an image depicting this QR Code, with the given number
     * of border modules. The string always uses Unix newlines (\n), regardless of the platform.
     *
     * @param int $border Border
     *
     * @throws \Exception
     **/
    public function toSvgString($border)
    {
        if ($border < 0) {
            throw new Exception('Border must be non-negative');
        }
        $parts = [];
        for ($y = 0; $y < $this->size; $y++) {
            for ($x = 0; $x < $this->size; $x++) {
                if ($this->getModule($x, $y)) {
                    $path = 'M' . ($x + $border) . ',' . ($y + $border) . 'h1v1h-1z';
                    \array_push($parts, $path);
                }
            }
        }

        return '<?xml version="1.0" encoding="UTF-8"?>
        <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
        <svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 ' . ($this->size + $border * 2) . ' ' . ($this->size + $border * 2) . '" stroke="none">
            <rect width="100%" height="100%" fill="#FFFFFF"/>
            <path d="' . \implode(' ', $parts) . '" fill="#000000"/>
        </svg>';
    }
    /**
     * Returns a QR Code representing the given segments with the given encoding parameters.
     *
     * @param array $segs       Description
     * @param array $minVersion Description
     * @param array $maxVersion Description
     * @param array $mask       Description
     *
     * @throws \Exception
     **/
    public static function encodeSegments($segs, $ecl, $minVersion = 1, $maxVersion = 40, $mask = -1, $boostEcl = true)
    {
        if (
            !(self::MIN_VERSION <= $minVersion
            && $minVersion <= $maxVersion
            && $maxVersion <= self::MAX_VERSION)
            || $mask < -1 || $mask > 7
        ) {
            throw new \Exception('Invalid values');
        }

        // Find the minimal version number to use
        $version      = 0;
        $dataUsedBits = 0;
        for ($version = $minVersion;; $version++) {
            $dataCapacityBits = self::getNumDataCodewords($version, $ecl) * 8;
            $usedBits         = QrSegment::getTotalBits($segs, $version);
            if ($usedBits <= $dataCapacityBits) {
                $dataUsedBits = $usedBits;
                break;  // This version number is found to be suitable
            }
            if ($version >= $maxVersion) {  // All versions in the range could not fit the given data
                throw new Exception('Data too long');
            }
        }

        // Increase the error correction level while the data still fits in the current version number
        foreach ([new Ecl(Ecl::MEDIUM), new Ecl(Ecl::QUARTILE), new Ecl(Ecl::HIGH)] as $newEcl) {  // From low to high
            if ($boostEcl && $dataUsedBits <= self::getNumDataCodewords($version, $newEcl) * 8) {
                $ecl = $newEcl;
            }
        }
        // Concatenate all segments to create the data bit string
        $bb = [];
        foreach ($segs as $seg) {
            self::appendBits($seg->mode->modeBits, 4, $bb);
            self::appendBits($seg->numChars, $seg->mode->numCharCountBits($version), $bb);
            foreach ($seg->getData() as $b) {
                $bb[] = $b;
            }
        }
        if (\count($bb) != $dataUsedBits) {
            throw new Exception('Assertion error');
        }

        // Add terminator and pad up to a byte if applicable
        $dataCapacityBits = self::getNumDataCodewords($version, $ecl) * 8;
        if (\count($bb) > $dataCapacityBits) {
            throw new Exception('Assertion error');
        }
        self::appendBits(0, \min([4, $dataCapacityBits - \count($bb)]), $bb);
        self::appendBits(0, (8 - \count($bb) % 8) % 8, $bb);
        if (\count($bb) % 8 != 0) {
            throw new Exception('Assertion error');
        }

        // Pad with alternating bytes until data capacity is reached
        for ($padByte = 0xEC; \count($bb) < $dataCapacityBits; $padByte ^= 0xEC ^ 0x11) {
            self::appendBits($padByte, 8, $bb);
        }

        $dataCodewords = [];
        while (\count($dataCodewords) * 8 < \count($bb)) {
            $dataCodewords[] = 0;
        }
        foreach ($bb as $i => $b) {
            $dataCodewords[$i >> 3] |= $b << (7 - ($i & 7));
        }

        // Create the QR Code object
        return new self($version, $ecl, $dataCodewords, $mask);
    }

    /**
     * Returns the color of the module (pixel) at the given coordinates, which is false
     * for white or true for black. The top left corner has the coordinates (x=0, y=0).
     * If the given coordinates are out of bounds, then false (white) is returned.
     *
     * @param int $x Description
     * @param int $y Description
     *
     * @throws \Exception
     **/
    public function getModule($x, $y)
    {
        return 0 <= $x && $x < $this->size && 0 <= $y && $y < $this->size && $this->modules[$y][$x];
    }

    /**
     * Returns the number of data bits that can be stored in a QR Code of the given version number, after
     * all function modules are excluded. This includes remainder bits, so it might not be a multiple of 8.
     * The result is in the range [208, 29648]. This could be implemented as a 40-entry lookup table.
     *
     * @param int $ver Version number
     *
     * @throws \Exception
     **/
    public static function getNumRawDataModules(int $ver)
    {
        if ($ver < self::MIN_VERSION || $ver > self::MAX_VERSION) {
            throw new Exception('Version number out of range');
        }
        $result = (16 * $ver + 128) * $ver + 64;
        if ($ver >= 2) {
            $numAlign = \floor($ver / 7) + 2;
            $result -= (25 * $numAlign - 10) * $numAlign - 55;
            if ($ver >= 7) {
                $result -= 36;
            }
        }
        if (!(208 <= $result && $result <= 29648)) {
            throw new Exception('Assertion error');
        }

        return $result;
    }

    /**
     * Returns the number of 8-bit data (i.e. not error correction) codewords contained in any
     * QR Code of the given version number and error correction level, with remainder bits discarded.
     * This stateless pure function could be implemented as a (40*4)-cell lookup table.
     *
     * @param int $ver Version number
     * @param int $ecl Error correction level
     *
     * @throws \Exception
     **/
    public static function getNumDataCodewords(int $ver, Ecl $ecl)
    {
        return (\floor(self::getNumRawDataModules($ver) / 8) -
            self::ECC_CODEWORDS_PER_BLOCK[$ecl->ordinal][$ver] *
            self::NUM_ERROR_CORRECTION_BLOCKS[$ecl->ordinal][$ver]);
    }

    /**
     * Appends the given number of low-order bits of the given value
     * to the given buffer. Requires 0 <= len <= 31 and 0 <= val < 2^len.
     *
     * @param int   $val Value
     * @param int   $len Length
     * @param array $bb
     *
     * @throws \Exception
     **/
    public static function appendBits(int $val, int $len, array &$bb)
    {
        if ($len < 0 || $len > 31 || $val >> $len != 0) {
            throw new \Exception('Value out of range');
        }
        for ($i = $len - 1; $i >= 0; $i--) {
            $bb[] = (int) (($val >> $i) & 1);
        }
    }

    /**
     * Returns true iff the i'th bit of x is set to 1.
     *
     * @param int $x Value x
     * @param int $i
     *
     * @throws \Exception
     **/
    public static function getBit(int $x, int $i)
    {
        return (($x >> $i) & 1) != 0;
    }

    /**
     * Reads this object's version field, and draws and marks all function modules.
     *
     * @throws \Exception
     **/
    private function drawFunctionPatterns()
    {
        // Draw horizontal and vertical timing patterns
        for ($i = 0; $i < $this->size; $i++) {
            $this->setFunctionModule(6, $i, $i % 2 == 0);
            $this->setFunctionModule($i, 6, $i % 2 == 0);
        }

        // Draw 3 finder patterns (all corners except bottom right; overwrites some timing modules)
        $this->drawFinderPattern(3, 3);
        $this->drawFinderPattern($this->size - 4, 3);
        $this->drawFinderPattern(3, $this->size - 4);

        // Draw numerous alignment patterns
        $alignPatPos = $this->getAlignmentPatternPositions();
        $numAlign    = \count($alignPatPos);
        for ($i = 0; $i < $numAlign; $i++) {
            for ($j = 0; $j < $numAlign; $j++) {
                // Don't draw on the three finder corners
                if (!($i == 0 && $j == 0 || $i == 0 && $j == $numAlign - 1 || $i == $numAlign - 1 && $j == 0)) {
                    $this->drawAlignmentPattern($alignPatPos[$i], $alignPatPos[$j]);
                }
            }
        }

        // Draw configuration data
        $this->drawFormatBits(0);  // Dummy mask value; overwritten later in the constructor
        $this->drawVersion();
    }

    /**
     * Sets the color of a module and marks it as a function module.
     * Only used by the constructor. Coordinates must be in bounds.
     *
     * @param int  $x
     * @param int  $y
     * @param bool $isBlack
     *
     * @throws \Exception
     **/
    private function setFunctionModule(int $x, int $y, bool $isBlack)
    {
        $this->modules[$y][$x]    = $isBlack;
        $this->isFunction[$y][$x] = true;
    }

    /**
     * Draws a 9*9 finder pattern including the border separator,
     * with the center module at (x, y). Modules can be out of bounds.
     *
     * @param int $x
     * @param int $y
     *
     * @throws \Exception
     **/
    private function drawFinderPattern(int $x, int $y)
    {
        for ($dy = -4; $dy <= 4; $dy++) {
            for ($dx = -4; $dx <= 4; $dx++) {
                $dist = \max(\abs($dx), \abs($dy));  // Chebyshev/infinity norm
                $xx   = $x + $dx;
                $yy   = $y + $dy;
                if (0 <= $xx && $xx < $this->size && 0 <= $yy && $yy < $this->size) {
                    $this->setFunctionModule($xx, $yy, $dist != 2 && $dist != 4);
                }
            }
        }
    }

    /**
     * Returns an ascending list of positions of alignment patterns for this version number.
     * Each position is in the range [0,177), and are used on both the x and y axes.
     * This could be implemented as lookup table of 40 variable-length lists of integers.
     *
     * @return array
     *
     * @throws \Exception
     **/
    private function getAlignmentPatternPositions()
    {
        if ($this->version == 1) {
            return [];
        } else {
            $numAlign = \floor($this->version / 7) + 2;
            $step     = ($this->version == 32) ? 26 : \ceil(($this->size - 13) / ($numAlign * 2 - 2)) * 2;
            $result   = [6];
            for ($pos = $this->size - 7; \count($result) < $numAlign; $pos -= $step) {
                \array_splice($result, 1, 0, $pos);
            }

            return $result;
        }
    }

    /**
     * Draws a 5*5 alignment pattern, with the center module
     * at (x, y). All modules must be in bounds.
     *
     * @param int $x
     * @param int $y
     *
     * @throws \Exception
     **/
    private function drawAlignmentPattern(int $x, int $y)
    {
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $this->setFunctionModule($x + $dx, $y + $dy, \max(\abs($dx), \abs($dy)) != 1);
            }
        }
    }

    /**
     * Draws two copies of the format bits (with its own error correction code)
     * based on the given mask and this object's error correction level field.
     *
     * @param int $mask
     *
     * @throws \Exception
     **/
    private function drawFormatBits(int $mask)
    {
        // Calculate error correction code and pack bits
        $data = $this->errorCorrectionLevel->formatBits << 3 | $mask;  // errCorrLvl is uint2, mask is uint3
        $rem  = $data;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
        }
        $bits = ($data << 10 | $rem) ^ 0x5412;  // uint15
        if ($bits >> 15 != 0) {
            throw new Exception('Assertion error');
        }

        // Draw first copy
        for ($i = 0; $i <= 5; $i++) {
            $this->setFunctionModule(8, $i, $this->getBit($bits, $i));
        }
        $this->setFunctionModule(8, 7, $this->getBit($bits, 6));
        $this->setFunctionModule(8, 8, $this->getBit($bits, 7));
        $this->setFunctionModule(7, 8, $this->getBit($bits, 8));
        for ($i = 9; $i < 15; $i++) {
            $this->setFunctionModule(14 - $i, 8, $this->getBit($bits, $i));
        }

        // Draw second copy
        for ($i = 0; $i < 8; $i++) {
            $this->setFunctionModule($this->size - 1 - $i, 8, $this->getBit($bits, $i));
        }
        for ($i = 8; $i < 15; $i++) {
            $this->setFunctionModule(8, $this->size - 15 + $i, $this->getBit($bits, $i));
        }
        $this->setFunctionModule(8, $this->size - 8, true);  // Always black
    }

    /**
     * Draws two copies of the version bits (with its own error correction code)
     * based on this object's version field, iff 7 <= version <= 40.
     *
     * @throws \Exception
     **/
    private function drawVersion()
    {
        if ($this->version < 7) {
            return;
        }

        // Calculate error correction code and pack bits
        $rem = $this->version;  // version is uint6, in the range [7, 40]
        for ($i = 0; $i < 12; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 11) * 0x1F25);
        }
        $bits = $this->version << 12 | $rem;  // uint18
        if ($bits >> 18 != 0) {
            throw new Exception('Assertion error');
        }

        // Draw two copies
        for ($i = 0; $i < 18; $i++) {
            $color = $this->getBit($bits, $i);
            $a     = $this->size - 11 + $i % 3;
            $b     = (int) \floor($i / 3);
            $this->setFunctionModule($a, $b, $color);
            $this->setFunctionModule($b, $a, $color);
        }
    }

    /**
     * Returns a new byte string representing the given data with the appropriate error correction
     * codewords appended to it, based on this object's version and error correction level.
     *
     * @param array $data
     *
     * @throws \Exception
     **/
    private function addEccAndInterleave($data)
    {
        $ver = $this->version;
        $ecl = $this->errorCorrectionLevel;
        if (\count($data) != self::getNumDataCodewords($ver, $ecl)) {
            throw new Exception('Invalid argument');
        }

        // Calculate parameter numbers
        $numBlocks      = self::NUM_ERROR_CORRECTION_BLOCKS[$ecl->ordinal][$ver];
        $blockEccLen    = self::ECC_CODEWORDS_PER_BLOCK[$ecl->ordinal][$ver];
        $rawCodewords   = \floor(self::getNumRawDataModules($ver) / 8);
        $numShortBlocks = $numBlocks - $rawCodewords % $numBlocks;
        $shortBlockLen  = \floor($rawCodewords / $numBlocks);

        // Split data into blocks and append ECC to each block
        $blocks = [];
        $rsDiv  = self::reedSolomonComputeDivisor($blockEccLen);
        for ($i = 0, $k = 0; $i < $numBlocks; $i++) {
            $dat = \array_slice($data, $k, $k + $shortBlockLen - $blockEccLen + ($i < $numShortBlocks ? 0 : 1));

            $k += \count($dat);
            $ecc = self::reedSolomonComputeRemainder($dat, $rsDiv);
            if ($i < $numShortBlocks) {
                $dat[] = 0;
            }
            $blocks[] = \array_merge($dat, $ecc);
        }

        // Interleave (not concatenate) the bytes from every block into a single sequence
        $result = [];
        for ($i = 0; $i < \count($blocks[0]); $i++) {
            foreach ($blocks as $j => $block) {
                // Skip the padding byte in short blocks
                if ($i != $shortBlockLen - $blockEccLen || $j >= $numShortBlocks) {
                    $result[] = $block[$i];
                }
            }
        }
        if (\count($result) != $rawCodewords) {
            throw new Exception('Assertion error');
        }

        return $result;
    }

    /**
     * Returns a Reed-Solomon ECC generator polynomial for the given degree. This could be
     * implemented as a lookup table over all possible parameter values, instead of as an algorithm.
     *
     * @param array $data
     *
     * @throws \Exception
     **/
    private static function reedSolomonComputeDivisor($degree)
    {
        if ($degree < 1 || $degree > 255) {
            throw new Exception('Degree out of range');
        }
        // Polynomial coefficients are stored from highest to lowest power, excluding the leading term which is always 1.
        // For example the polynomial x^3 + 255x^2 + 8x + 93 is stored as the uint8 array [255, 8, 93].
        $result = [];
        for ($i = 0; $i < $degree - 1; $i++) {
            $result[] = 0;
        }
        $result[] = 1;  // Start off with the monomial x^0

        // Compute the product polynomial (x - r^0) * (x - r^1) * (x - r^2) * ... * (x - r^{degree-1}),
        // and drop the highest monomial term which is always 1x^degree.
        // Note that r = 0x02, which is a generator element of this field GF(2^8/0x11D).
        $root = 1;
        for ($i = 0; $i < $degree; $i++) {
            // Multiply the current product by (x - r^i)
            for ($j = 0; $j < \count($result); $j++) {
                $result[$j] = self::reedSolomonMultiply($result[$j], $root);
                if ($j + 1 < \count($result)) {
                    $result[$j] ^= $result[$j + 1];
                }
            }
            $root = self::reedSolomonMultiply($root, 0x02);
        }

        return $result;
    }

    /**
     * Returns the Reed-Solomon error correction codeword for the given data and divisor polynomials.
     *
     * @param array $data
     * @param array $divisor
     *
     * @throws \Exception
     **/
    private static function reedSolomonComputeRemainder($data, $divisor)
    {
        $result = \array_map(
            function () {
                return 0;
            },
            $divisor
        );
        foreach ($data as $b) {  // Polynomial division
            $factor   = $b ^ \array_shift($result);
            $result[] = 0;
            foreach ($divisor as $i => $coef) {
                $result[$i] ^= self::reedSolomonMultiply($coef, $factor);
            }
        }

        return $result;
    }

    /**
     * Returns the product of the two given field elements modulo GF(2^8/0x11D). The arguments and result
     * are unsigned 8-bit integers. This could be implemented as a lookup table of 256*256 entries of uint8.
     *
     * @param array $data
     *
     * @throws \Exception
     **/
    private static function reedSolomonMultiply($x, $y)
    {
        if ($x >> 8 != 0 || $y >> 8 != 0) {
            throw new Exception('Byte out of range');
        }
        // Russian peasant multiplication
        $z = 0;
        for ($i = 7; $i >= 0; $i--) {
            $z = ($z << 1) ^ (($z >> 7) * 0x11D);
            $z ^= (($y >> $i) & 1) * $x;
        }
        if ($z >> 8 != 0) {
            throw new Exception('Assertion error');
        }

        return $z;
    }

    /**
     * Draws the given sequence of 8-bit codewords (data and error correction) onto the entire
     * data area of this QR Code. Function modules need to be marked off before this is called.
     *
     * @param array $data
     *
     * @throws \Exception
     **/
    private function drawCodewords($data)
    {
        if (\count($data) != \floor(self::getNumRawDataModules($this->version) / 8)) {
            throw new Exception('Invalid argument');
        }
        $i = 0;  // Bit index into the data
        // Do the funny zigzag scan
        for ($right = $this->size - 1; $right >= 1; $right -= 2) {  // Index of right column in each column pair
            if ($right == 6) {
                $right = 5;
            }
            for ($vert = 0; $vert < $this->size; $vert++) {  // Vertical counter
                for ($j = 0; $j < 2; $j++) {
                    $x      = $right - $j;  // Actual x coordinate
                    $upward = (($right + 1) & 2) == 0;
                    $y      = $upward ? $this->size - 1 - $vert : $vert;  // Actual y coordinate
                    if (!$this->isFunction[$y][$x] && $i < \count($data) * 8) {
                        $this->modules[$y][$x] = $this->getBit($data[$i >> 3], 7 - ($i & 7));
                        $i++;
                    }
                    // If this QR Code has any remainder bits (0 to 7), they were assigned as
                    // 0/false/white by the constructor and are left unchanged by this method
                }
            }
        }
        if ($i != \count($data) * 8) {
            throw new Exception('Assertion error');
        }
    }

    /**
     * XORs the codeword modules in this QR Code with the given mask pattern.
     * The function modules must be marked and the codeword bits must be drawn
     * before masking. Due to the arithmetic of XOR, calling applyMask() with
     * the same mask value a second time will undo the mask. A final well-formed
     * QR Code needs exactly one (not zero, two, etc.) mask applied.
     *
     * @param int $mask
     *
     * @throws \Exception
     **/
    private function applyMask($mask)
    {
        if ($mask < 0 || $mask > 7) {
            throw new Exception('Mask value out of range');
        }
        for ($y = 0; $y < $this->size; $y++) {
            for ($x = 0; $x < $this->size; $x++) {
                $invert = false;
                switch ($mask) {
                    case 0:
                        $invert = ($x + $y) % 2 == 0;
                        break;
                    case 1:
                        $invert = $y % 2 == 0;
                        break;
                    case 2:
                        $invert = $x % 3 == 0;
                        break;
                    case 3:
                        $invert = ($x + $y) % 3 == 0;
                        break;
                    case 4:
                        $invert = (\floor($x / 3) + \floor($y / 2)) % 2 == 0;
                        break;
                    case 5:
                        $invert = $x * $y % 2 + $x * $y % 3 == 0;
                        break;
                    case 6:
                        $invert = ($x * $y % 2 + $x * $y % 3) % 2 == 0;
                        break;
                    case 7:
                        $invert = (($x + $y) % 2 + $x * $y % 3) % 2 == 0;
                        break;
                    default:
                        throw new Exception('Assertion error');
                }
                if (!$this->isFunction[$y][$x] && $invert) {
                    $this->modules[$y][$x] = !$this->modules[$y][$x];
                }
            }
        }
    }

    /**
     * Calculates and returns the penalty score based on state of this QR Code's current modules.
     * This is used by the automatic mask choice algorithm to find the mask pattern that yields the lowest score.
     *
     * @param int $mask
     *
     * @throws \Exception
     **/
    private function getPenaltyScore()
    {
        $result = 0;

        // Adjacent modules in row having same color, and finder-like patterns
        for ($y = 0; $y < $this->size; $y++) {
            $runColor   = false;
            $runX       = 0;
            $runHistory = [0, 0, 0, 0, 0, 0, 0];
            $padRun     = $this->size;
            for ($x = 0; $x < $this->size; $x++) {
                if ($this->modules[$y][$x] == $runColor) {
                    $runX++;
                    if ($runX == 5) {
                        $result += self::PENALTY_N1;
                    } elseif ($runX > 5) {
                        $result++;
                    }
                } else {
                    self::finderPenaltyAddHistory($runX + $padRun, $runHistory);
                    $padRun = 0;
                    if (!$runColor) {
                        $result += $this->finderPenaltyCountPatterns($runHistory) * self::PENALTY_N3;
                    }
                    $runColor = $this->modules[$y][$x];
                    $runX     = 1;
                }
            }
            $result += $this->finderPenaltyTerminateAndCount($runColor, $runX + $padRun, $runHistory) * self::PENALTY_N3;
        }
        // Adjacent modules in column having same color, and finder-like patterns
        for ($x = 0; $x < $this->size; $x++) {
            $runColor   = false;
            $runY       = 0;
            $runHistory = [0, 0, 0, 0, 0, 0, 0];
            $padRun     = $this->size;
            for ($y = 0; $y < $this->size; $y++) {
                if ($this->modules[$y][$x] == $runColor) {
                    $runY++;
                    if ($runY == 5) {
                        $result += self::PENALTY_N1;
                    } elseif ($runY > 5) {
                        $result++;
                    }
                } else {
                    self::finderPenaltyAddHistory($runY + $padRun, $runHistory);
                    $padRun = 0;
                    if (!$runColor) {
                        $result += $this->finderPenaltyCountPatterns($runHistory) * self::PENALTY_N3;
                    }
                    $runColor = $this->modules[$y][$x];
                    $runY     = 1;
                }
            }
            $result += $this->finderPenaltyTerminateAndCount($runColor, $runY + $padRun, $runHistory) * self::PENALTY_N3;
        }

        // 2*2 blocks of modules having same color
        for ($y = 0; $y < $this->size - 1; $y++) {
            for ($x = 0; $x < $this->size - 1; $x++) {
                $color = $this->modules[$y][$x];
                if ($color == $this->modules[$y][$x + 1]
                    && $color == $this->modules[$y + 1][$x]
                    && $color == $this->modules[$y + 1][$x + 1]
                ) {
                    $result += self::PENALTY_N2;
                }
            }
        }

        // Balance of black and white modules
        $black = 0;
        foreach ($this->modules as $row) {
            foreach ($row as $color) {
                if ($color) {
                    $black++;
                }
            }
        }
        $total = $this->size * $this->size;  // Note that size is odd, so black/total != 1/2
        // Compute the smallest integer k >= 0 such that (45-5k)% <= black/total <= (55+5k)%
        $k = \ceil(\abs($black * 20 - $total * 10) / $total) - 1;
        $result += $k * self::PENALTY_N4;

        return $result;
    }

    /**
     * Pushes the given value to the front and drops the last value. A helper function for getPenaltyScore().
     *
     * @param array $currentRunLength
     * @param array $runHistory
     *
     * @throws \Exception
     **/
    private static function finderPenaltyAddHistory($currentRunLength, $runHistory)
    {
        \array_pop($runHistory);
        \array_unshift($runHistory, $currentRunLength);
    }

    /**
     * Can only be called immediately after a white run is added, and
     * returns either 0, 1, or 2. A helper function for getPenaltyScore().
     *
     * @param array $runHistory
     *
     * @throws \Exception
     **/
    private function finderPenaltyCountPatterns($runHistory)
    {
        $n = $runHistory[1];
        if ($n > $this->size * 3) {
            throw new Exception('Assertion error');
        }
        $core = $n > 0 && $runHistory[2] == $n && $runHistory[3] == $$n * 3 && $runHistory[4] == $n && $runHistory[5] == $n;

        return ($core && $runHistory[0] >= $n * 4 && $runHistory[6] >= $n ? 1 : 0)
             + ($core && $runHistory[6] >= $n * 4 && $runHistory[0] >= $n ? 1 : 0);
    }

    /**
     * Must be called at the end of a line (row or column) of modules. A helper function for getPenaltyScore().
     *
     * @param array $currentRunColor
     * @param array $currentRunLength
     * @param array $runHistory
     *
     * @throws \Exception
     **/
    private function finderPenaltyTerminateAndCount($currentRunColor, $currentRunLength, $runHistory)
    {
        if ($currentRunColor) {  // Terminate black run
            self::finderPenaltyAddHistory($currentRunLength, $runHistory);
            $currentRunLength = 0;
        }
        $currentRunLength += $this->size;  // Add white border to final run
        self::finderPenaltyAddHistory($currentRunLength, $runHistory);

        return $this->finderPenaltyCountPatterns($runHistory);
    }
}
