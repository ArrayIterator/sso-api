<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Utils\Helper;

use RuntimeException;
use ValueError;
use function explode;
use function function_exists;
use function intdiv;
use function is_int;
use function is_numeric;
use function ltrim;
use function max;
use function min;
use function preg_replace;
use function sprintf;
use function str_contains;
use function str_pad;
use function str_repeat;
use function str_replace;
use function strlen;
use function strrev;
use function strval;
use function substr;
use const PHP_INT_MAX;
use const PHP_INT_SIZE;
use const STR_PAD_LEFT;

/**
 * Basic Math like bcmath
 *
 * @todo implement : bcdiv, bcpow, bcsqrt, bcmod, bcpowmod
 */
class BasicMath
{
    /**
     * The largest integer supported in 32-bit systems
     */
    final public const INTEGER_32 = 2147483647;

    /**
     * @var numeric-string $number
     * The number string
     */
    private string $number;

    /**
     * @var int $scale
     * The optional scale parameter is used to set the number of digits after the decimal place in the result.
     */
    protected int $scale = 0;

    /**
     * Number constructor.
     *
     * @param $number
     * @param int|null $scale
     */
    public function __construct($number, ?int $scale = null)
    {
        $number = $this->normalizeNumber($number);
        $this->number = $number;
        $scale ??= function_exists('bcscale')
            ? bcscale()
            : 0;
        $this->scale($scale);
    }

    /**
     * @param $number
     * @param ?int $scale
     * @return self
     */
    public static function create($number, ?int $scale = 0) : self
    {
        return new self($number, $scale);
    }

    /* UTIL */

    /**
     * Assertion
     *
     * @param $scale
     * @return void
     */
    private function assertScale($scale): void
    {
        if ($scale === null) {
            return;
        }
        if (!is_int($scale)
            || $scale < 0
            || $scale > self::INTEGER_32
        ) {
            throw new ValueError(
                sprintf(
                    'Argument #1 ($scale) must be a between 0 and %d',
                    self::INTEGER_32
                )
            );
        }
    }

    /**
     * Pads the left of one of the given numbers with zeros if necessary to make both numbers the same length.
     *
     * The numbers must only consist of digits, without leading minus sign.
     *
     * @return array{numeric-string, numeric-string, int}
     */
    private function padNumber(string $leftOperand, string $rightOperand) : array
    {
        $x = strlen($leftOperand);
        $y = strlen($rightOperand);
        if ($x > $y) {
            $rightOperand = str_repeat('0', $x - $y) . $rightOperand;
            return [$leftOperand, $rightOperand, $x];
        }
        if ($x < $y) {
            $leftOperand = str_repeat('0', $y - $x) . $leftOperand;
            return [$leftOperand, $rightOperand, $y];
        }

        return [$leftOperand, $rightOperand, $x];
    }

    /**
     * Convert a number if contain scientific notation to standard notation
     * e.g.: 1.2e+3 to 1200
     *
     * @param $number
     * @return ?string
     */
    public function normalizeNumber($number) : ?string
    {
        // if number is instance of Number
        if ($number instanceof BasicMath) {
            $number = $number->getNumber();
        }

        if (!is_numeric($number)) {
            throw new ValueError(
                'Argument #1 ($number) must be a number'
            );
        }

        // replace E to e
        $number = str_replace('E', 'e', strval($number));
        // Convert a number in scientific notation to standard notation
        if (str_contains($number, 'e')) {
            [$mantissa, $exponent] = explode('e', $number);
            if (($minus = $mantissa[0] === '-') || $mantissa[0] === '+') {
                $mantissa = substr($mantissa, 1);
            }
            if (($isDecimalPoint = $exponent[0] === '.') || $exponent[0] === '+') {
                $exponent = substr($exponent, 1);
            }
            $exponent = (int)$exponent;
            if ($exponent >= PHP_INT_MAX) {
                throw new RuntimeException(
                    'Exponent is too large'
                );
            }
            // check additional exponent
            $additionalExponent = 0;
            if (!$isDecimalPoint && str_contains($mantissa, '.')) {
                $additionalExponent = strlen(explode('.', $mantissa)[0]);
            }
            $exponent = $exponent + $additionalExponent;
            $mantissa = str_replace('.', '', $mantissa);
            if ($isDecimalPoint) {
                // - is decimal point, convert mantissa
                $mantissa = substr(str_repeat('0', $exponent - 1) . $mantissa, 0, $exponent + 1);
                $mantissa = '0.' . $mantissa;
            } else {
                $mantissa = str_pad($mantissa, $exponent, '0');
                if (strlen($mantissa) > $exponent) {
                    $mantissa = substr($mantissa, 0, $exponent+1)
                        . '.'
                        . substr($mantissa, $exponent + 1, strlen($mantissa) - $exponent);
                }
                if (str_contains($mantissa, '.0')) {
                    // trim right padding
                    $mantissa = preg_replace('/\.0+$/', '', $mantissa);
                }
            }

            $number = $minus ? '-' . $mantissa : $mantissa;
        }

        return $number;
    }

    /**
     * Scale number of decimal point
     *
     * @param $number
     * @param int|null $scale
     * @return string
     */
    public function scaleNumber($number, ?int $scale = null): string
    {
        // scale is null fallback as 0
        $scale ??= $this->scale();
        $number = $this->normalizeNumber($number);
        $explode = explode('.', $number);
        $int = $explode[0];
        if ($scale === 0) {
            return $int;
        }
        $decimal = $explode[1] ?? '';
        if (strlen($decimal) > $scale) {
            $decimal = substr($decimal, 0, $scale);
        } else {
            $decimal = str_pad($decimal, $scale, '0');
        }
        return $int . '.' . $decimal;
    }

    /* END UTIL */

    /**
     * Get stored number
     *
     * @return numeric-string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @return numeric-string
     */
    public function __toString(): string
    {
        return $this->getNumber();
    }

    /**
     * Scaling factor for all bc math functions
     *
     * @param ?int $scale
     * @return int
     * @see bcscale()
     */
    public function scale(?int $scale = null): int
    {
        // if null get the scale
        if ($scale === null) {
            return $this->scale;
        }

        $this->assertScale($scale);
        $this->scale = $scale;
        return $scale;
    }

    /**
     * Multiply two arbitrary precision numbers
     *
     * @param $number
     * @param int|null $scale
     * @return numeric-string
     * @see \bcmul()
     */
    public function multiply($number, ?int $scale = null): string
    {
        // normalize number
        $number = $this->normalizeNumber($number);
        // current number
        $leftOperand = $this->getNumber();

        // asserting scale
        $this->assertScale($scale);

        $x = strlen($leftOperand);
        $y = 2;
        $maxDigits = PHP_INT_SIZE === 4 ? 9 : 18;
        $maxDigits = intdiv($maxDigits, 2);
        $complement = 10 ** $maxDigits;

        $result = '0';
        for ($i = $x - $maxDigits;; $i -= $maxDigits) {
            $blockALength = $maxDigits;
            if ($i < 0) {
                $blockALength += $i;
                /** @psalm-suppress LoopInvalidation */
                $i = 0;
            }

            $blockA = (int) substr($leftOperand, $i, $blockALength);

            $line = '';
            $carry = 0;

            for ($j = $y - $maxDigits;; $j -= $maxDigits) {
                $blockBLength = $maxDigits;

                if ($j < 0) {
                    $blockBLength += $j;
                    /** @psalm-suppress LoopInvalidation */
                    $j = 0;
                }

                $blockB = (int) substr($number, $j, $blockBLength);

                $mul = $blockA * $blockB + $carry;
                $value = $mul % $complement;
                $carry = ($mul - $value) / $complement;

                $value = (string) $value;
                $value = str_pad($value, $maxDigits, '0', STR_PAD_LEFT);

                $line = $value . $line;

                if ($j === 0) {
                    break;
                }
            }

            if ($carry !== 0) {
                $line = $carry . $line;
            }

            $line = ltrim($line, '0');

            if ($line !== '') {
                $line .= str_repeat('0', $x - $blockALength - $i);
                $result = self::create($result, $scale)->add($line, $scale);
            }

            if ($i === 0) {
                break;
            }
        }

        return $this->scaleNumber($result, $scale);
    }

    /**
     * Add two arbitrary precision numbers
     *
     * @param $number
     * @param int|null $scale
     * @return numeric-string
     * @see \bcadd()
     */
    public function add($number, ?int $scale = null): string
    {
        // normalize number
        $number = $this->normalizeNumber($number);
        // current number
        $leftOperand = $this->getNumber();

        // asserting scale
        $this->assertScale($scale);

        $maxDigits = PHP_INT_SIZE === 4 ? 9 : 18;
        [$leftOperand, $number, $length] = $this->padNumber($leftOperand, $number);

        $carry = 0;
        $result = '';
        for (($i = $length - $maxDigits);; $i -= $maxDigits) {
            $blockLength = $maxDigits;

            if ($i < 0) {
                $blockLength += $i;
                /** @psalm-suppress LoopInvalidation */
                $i = 0;
            }

            /** @var numeric $blockA */
            $blockA = substr($leftOperand, $i, $blockLength);

            /** @var numeric $blockB */
            $blockB = substr($number, $i, $blockLength);

            $sum = (string) ($blockA + $blockB + $carry);
            $sumLength = strlen($sum);

            if ($sumLength > $blockLength) {
                $sum = substr($sum, 1);
                $carry = 1;
            } else {
                if ($sumLength < $blockLength) {
                    $sum = str_repeat('0', $blockLength - $sumLength) . $sum;
                }
                $carry = 0;
            }

            $result = $sum . $result;

            if ($i === 0) {
                break;
            }
        }

        if ($carry === 1) {
            $result = '1' . $result;
        }

        return $this->scaleNumber($result, $scale);
    }

    /**
     * Subtract one arbitrary precision number from another
     *
     * @param $number
     * @param int|null $scale
     * @return numeric-string
     * @see \bcsub()
     */
    public function subtract($number, ?int $scale = null): string
    {
        // normalize number
        $number     = $this->normalizeNumber($number);
        // current number
        $leftOperand = $this->getNumber();

        // assert scale
        $this->assertScale($scale);

        if (trim($number, '.0') === '0') {
            return $leftOperand;
        }

        // check contains decimal
        if (str_contains($number, '.')
            || str_contains($leftOperand, '.')
        ) {
            $leftOperandArray = explode('.', $leftOperand);
            $rightOperandArray = explode('.', $number);
            $intResult   = self::create($leftOperand[0])->subtract($rightOperandArray[0]);
            $leftDecimal  = $leftOperandArray[1]??'0';
            $rightDecimal = $rightOperandArray[1]??'0';
            if ($leftDecimal === '0' && $rightDecimal === '0') {
                return $this->scaleNumber($intResult, $scale);
            }

            $decimalResult = '0';
            if (self::create($leftDecimal)->compare($rightDecimal) === -1) {
                [$leftDecimal, $rightDecimal, $length] = $this->padNumber($leftDecimal, $rightDecimal);
                $upOne = str_pad('1', $length+1, '0', STR_PAD_RIGHT);
                $leftDecimal = self::create($leftDecimal, 0)->add($upOne);
                $intResult = self::create($intResult)->subtract('-1');
                $decimalResult = self::create($rightDecimal)->subtract($leftDecimal);
            }

            // scale
            $result = $decimalResult === '0'
                ? $intResult
                : $intResult . '.' . ltrim($decimalResult, '-');
            return $this->scaleNumber($result, $scale);
        }

        $negate = self::create($leftOperand, $scale)->compare($number) === -1;
        // Ensure $leftOperand greater than $rightOperand
        if ($negate) {
            [$leftOperand, $number] = [$number, $leftOperand];
        }

        // Reverse the strings to start subtraction from the rightmost digit
        $leftOperand  = strrev($leftOperand);
        $number = strrev($number);

        $result = '';
        $borrow = 0;

        $maxLength = max(strlen($leftOperand), strlen($number));
        // Start subtracting from the rightmost digit
        for ($i = 0; $i < $maxLength; $i++) {
            $digit1 = (int)($leftOperand[$i]??0);
            $digit2 = (int)($number[$i]??0);

            // Subtract digits and handle borrowing
            $digit = $digit1 - $digit2 - $borrow;
            if ($digit < 0) {
                $digit += 10;
                $borrow = 1;
            } else {
                $borrow = 0;
            }

            // Append result
            $result .= $digit;
        }

        // Remove leading zeros from the result
        $result = ltrim(strrev($result), '0');

        if ($result === '') {
            return $this->scaleNumber('0', $scale);
        }

        // Add negative sign if necessary
        if ($negate) {
            $result = '-' . $result;
        }

        return $this->scaleNumber($result, $scale);
    }

    /**
     * Compare two numbers,
     * Returns 0 if the two operands are equal,
     * 1 if the left_operand is larger than the right_operand,
     * -1 otherwise.
     *
     * @param $number
     * @param int|null $scale
     * @return int
     * @see \bccomp()
     */
    public function compare($number, ?int $scale = null): int
    {
        // normalize number
        $number = $this->normalizeNumber($number);
        // current number
        $leftOperand  = $this->getNumber();

        // asserting scale
        $this->assertScale($scale);

        // scale number for comparison
        $number = $this->scaleNumber($number, $scale);
        $leftOperand = $this->scaleNumber($leftOperand, $scale);

        // negative numbers are less than positive numbers
        $leftNegate  = $leftOperand[0] === '-';
        $rightNegate = $number[0] === '-';

        // length of the integer part
        $leftLength  = strlen($leftOperand);
        $rightLength = strlen($number);

        // scaling
        $leftScale   = strlen(explode('.', $leftOperand)[1]??'');
        $rightScale  = strlen(explode('.', $number)[1]??'');

        /* First, compare signs. */
        if ($leftNegate !== $rightNegate) {
            return $leftNegate ? -1 : 1;
        }

        /* compare the magnitude. */
        if ($leftLength !== $rightLength) {
            if ($leftLength > $rightLength) {
                return !$leftNegate ? 1 : -1;
            } else {
                return !$leftNegate ? -1 : 1;
            }
        }


        /* If we get here, they have the same number of integer digits.
           check the integer part and the equal length part of the fraction. */
        $count = $leftLength + min($leftScale, $rightScale);
        $n1ptr = $leftOperand;
        $n2ptr = $number;

        while (($count > 0) && ($n1ptr === $n2ptr)) {
            $n1ptr++;
            $n2ptr++;
            $count--;
        }

        if ($count !== 0) {
            if ($n1ptr > $n2ptr) {
                /* Magnitude of n1 > n2. */
                return !$leftNegate ? 1 : -1;
            } else {
                /* Magnitude of n1 < n2. */
                return !$leftNegate ? -1 : 1;
            }
        }

        /* They are equal up to the last part of the equal part of the fraction. */
        if ($leftScale === $rightScale) {
            return 0;
        }
        if ($leftScale > $rightScale) {
            for (($count = $leftScale - $rightScale); $count > 0; $count--) {
                if ($n1ptr++ !== 0) {
                    /* Magnitude of n1 > n2. */
                    return !$leftNegate ? 1 : -1;
                }
            }
            return 0;
        }

        for (($count = $rightScale - $leftScale); $count > 0; $count--) {
            if ($n2ptr++ !== 0) {
                /* Magnitude of n1 < n2. */
                return !$leftNegate ? -1 : 1;
            }
        }

        return 0;
    }

    /*
    public function divide($number, ?int $scale = null): string
    {
        // TODO: Implement divide() method.
    }

    public function raise(int $exponent, ?int $scale = null): string
    {
        // TODO: Implement raise() method.
    }

    public function modulo($number, ?int $scale = null): string
    {
        // TODO: Implement modulo() method.
    }

    public function sqrt(?int $scale = null): string
    {
        // TODO: Implement sqrt() method.
    }

    public function raiseModule($exponent, $modulus, ?int $scale = null): string
    {
        // TODO: Implement raiseModule() method.
    }
    */
}
