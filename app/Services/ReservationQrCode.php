<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Generates a local QR Code Model 2, version 4-L, for reservation tokens.
 * Version 4-L has enough byte capacity for the 64-character reservation token.
 */
class ReservationQrCode
{
    private const VERSION = 4;
    private const SIZE = 17 + (4 * self::VERSION);
    private const DATA_CODEWORDS = 80;
    private const ECC_CODEWORDS = 20;

    public function svg(string $value): string
    {
        $bytes = array_values(unpack('C*', $value) ?: []);
        if ($bytes === [] || count($bytes) > 78) {
            throw new InvalidArgumentException('Reservation QR data must contain between 1 and 78 bytes.');
        }

        $dataCodewords = $this->encodeData($bytes);
        $codewords = array_merge($dataCodewords, $this->reedSolomonRemainder($dataCodewords, self::ECC_CODEWORDS));
        $dataBits = [];
        foreach ($codewords as $codeword) {
            $this->appendBits($dataBits, $codeword, 8);
        }

        $matrix = array_fill(0, self::SIZE, array_fill(0, self::SIZE, null));
        $function = array_fill(0, self::SIZE, array_fill(0, self::SIZE, false));
        $this->drawFunctionPatterns($matrix, $function, 0);
        $this->placeData($matrix, $function, $dataBits, 0);
        $this->drawFormatBits($matrix, $function, 0);

        $quietZone = 4;
        $viewSize = self::SIZE + ($quietZone * 2);
        $path = '';
        for ($y = 0; $y < self::SIZE; $y++) {
            for ($x = 0; $x < self::SIZE; $x++) {
                if ($matrix[$y][$x]) {
                    $path .= 'M' . ($x + $quietZone) . ' ' . ($y + $quietZone) . 'h1v1h-1z';
                }
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $viewSize . ' ' . $viewSize . '" width="246" height="246" shape-rendering="crispEdges" role="img" aria-label="QR reservasi"><rect width="100%" height="100%" fill="#fff"/><path d="' . $path . '" fill="#111827"/></svg>';
    }

    public function dataUri(string $value): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode($this->svg($value));
    }

    /** @param list<int> $bytes @return list<int> */
    private function encodeData(array $bytes): array
    {
        $bits = [];
        $this->appendBits($bits, 0b0100, 4); // Byte mode
        $this->appendBits($bits, count($bytes), 8);
        foreach ($bytes as $byte) {
            $this->appendBits($bits, $byte, 8);
        }

        $capacity = self::DATA_CODEWORDS * 8;
        $this->appendBits($bits, 0, min(4, $capacity - count($bits)));
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        $codewords = [];
        for ($i = 0; $i < count($bits); $i += 8) {
            $value = 0;
            for ($j = 0; $j < 8; $j++) {
                $value = ($value << 1) | $bits[$i + $j];
            }
            $codewords[] = $value;
        }

        foreach ([0xEC, 0x11] as $pad) {
            while (count($codewords) < self::DATA_CODEWORDS) {
                $codewords[] = $pad;
                $pad = $pad === 0xEC ? 0x11 : 0xEC;
            }
        }

        return $codewords;
    }

    /** @param list<int> $bits */
    private function appendBits(array &$bits, int $value, int $length): void
    {
        for ($i = $length - 1; $i >= 0; $i--) {
            $bits[] = ($value >> $i) & 1;
        }
    }

    /** @param list<int> $data @return list<int> */
    private function reedSolomonRemainder(array $data, int $degree): array
    {
        $divisor = array_fill(0, $degree, 0);
        $divisor[$degree - 1] = 1;
        $root = 1;
        for ($i = 0; $i < $degree; $i++) {
            for ($j = 0; $j < $degree; $j++) {
                $divisor[$j] = $this->gfMultiply($divisor[$j], $root);
                if ($j + 1 < $degree) {
                    $divisor[$j] ^= $divisor[$j + 1];
                }
            }
            $root = $this->gfMultiply($root, 2);
        }

        $remainder = array_fill(0, $degree, 0);
        foreach ($data as $byte) {
            $factor = $byte ^ $remainder[0];
            array_shift($remainder);
            $remainder[] = 0;
            for ($i = 0; $i < $degree; $i++) {
                $remainder[$i] ^= $this->gfMultiply($divisor[$i], $factor);
            }
        }

        return $remainder;
    }

    private function gfMultiply(int $x, int $y): int
    {
        $result = 0;
        for ($i = 7; $i >= 0; $i--) {
            $result = (($result << 1) ^ (($result >> 7) * 0x11D)) & 0xFF;
            $result ^= (($y >> $i) & 1) * $x;
        }

        return $result;
    }

    /** @param array<int, array<int, int|null>> $matrix @param array<int, array<int, bool>> $function */
    private function drawFunctionPatterns(array &$matrix, array &$function, int $mask): void
    {
        $this->drawFinderPattern($matrix, $function, 0, 0);
        $this->drawFinderPattern($matrix, $function, self::SIZE - 7, 0);
        $this->drawFinderPattern($matrix, $function, 0, self::SIZE - 7);

        for ($i = 8; $i < self::SIZE - 8; $i++) {
            $this->setFunctionModule($matrix, $function, $i, 6, $i % 2 === 0);
            $this->setFunctionModule($matrix, $function, 6, $i, $i % 2 === 0);
        }

        // Version 4 has one non-overlapping alignment pattern at (26, 26).
        $this->drawAlignmentPattern($matrix, $function, 26, 26);
        $this->drawFormatBits($matrix, $function, $mask);
    }

    /** @param array<int, array<int, int|null>> $matrix @param array<int, array<int, bool>> $function */
    private function drawFinderPattern(array &$matrix, array &$function, int $left, int $top): void
    {
        for ($dy = -1; $dy <= 7; $dy++) {
            for ($dx = -1; $dx <= 7; $dx++) {
                $x = $left + $dx;
                $y = $top + $dy;
                if ($x < 0 || $x >= self::SIZE || $y < 0 || $y >= self::SIZE) {
                    continue;
                }
                $distance = max(abs($dx - 3), abs($dy - 3));
                $dark = $distance !== 2 && $distance !== 4;
                if ($dx === -1 || $dx === 7 || $dy === -1 || $dy === 7) {
                    $dark = false;
                }
                $this->setFunctionModule($matrix, $function, $x, $y, $dark);
            }
        }
    }

    /** @param array<int, array<int, int|null>> $matrix @param array<int, array<int, bool>> $function */
    private function drawAlignmentPattern(array &$matrix, array &$function, int $centerX, int $centerY): void
    {
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $dark = max(abs($dx), abs($dy)) !== 1;
                $this->setFunctionModule($matrix, $function, $centerX + $dx, $centerY + $dy, $dark);
            }
        }
    }

    /** @param array<int, array<int, int|null>> $matrix @param array<int, array<int, bool>> $function */
    private function drawFormatBits(array &$matrix, array &$function, int $mask): void
    {
        // Error correction level L is encoded as 01 in the format information.
        $data = (1 << 3) | $mask;
        $remainder = $data;
        for ($i = 0; $i < 10; $i++) {
            $remainder = ($remainder << 1) ^ ((($remainder >> 9) & 1) * 0x537);
        }
        $format = (($data << 10) | $remainder) ^ 0x5412;
        $bit = fn (int $i): bool => (($format >> $i) & 1) !== 0;

        for ($i = 0; $i <= 5; $i++) {
            $this->setFunctionModule($matrix, $function, 8, $i, $bit($i));
        }
        $this->setFunctionModule($matrix, $function, 8, 7, $bit(6));
        $this->setFunctionModule($matrix, $function, 8, 8, $bit(7));
        $this->setFunctionModule($matrix, $function, 7, 8, $bit(8));
        for ($i = 9; $i < 15; $i++) {
            $this->setFunctionModule($matrix, $function, 14 - $i, 8, $bit($i));
        }

        for ($i = 0; $i < 8; $i++) {
            $this->setFunctionModule($matrix, $function, self::SIZE - 1 - $i, 8, $bit($i));
        }
        for ($i = 8; $i < 15; $i++) {
            $this->setFunctionModule($matrix, $function, 14 - $i, 8, $bit($i));
        }
        $this->setFunctionModule($matrix, $function, 8, self::SIZE - 8, true);
    }

    /** @param list<int> $bits @param array<int, array<int, int|null>> $matrix @param array<int, array<int, bool>> $function */
    private function placeData(array &$matrix, array $function, array $bits, int $mask): void
    {
        $bitIndex = 0;
        for ($right = self::SIZE - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }
            for ($vertical = 0; $vertical < self::SIZE; $vertical++) {
                $upward = (($right + 1) & 2) === 0;
                $y = $upward ? self::SIZE - 1 - $vertical : $vertical;
                for ($offset = 0; $offset < 2; $offset++) {
                    $x = $right - $offset;
                    if ($function[$y][$x]) {
                        continue;
                    }
                    $value = $bitIndex < count($bits) ? $bits[$bitIndex] : 0;
                    $bitIndex++;
                    if ($mask === 0 && (($x + $y) % 2 === 0)) {
                        $value ^= 1;
                    }
                    $matrix[$y][$x] = $value;
                }
            }
        }
    }

    /** @param array<int, array<int, int|null>> $matrix @param array<int, array<int, bool>> $function */
    private function setFunctionModule(array &$matrix, array &$function, int $x, int $y, bool $dark): void
    {
        $matrix[$y][$x] = $dark ? 1 : 0;
        $function[$y][$x] = true;
    }
}
