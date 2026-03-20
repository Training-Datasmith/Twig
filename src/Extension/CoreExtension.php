<?php

declare (strict_types=1);
/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Twig\Extension;

use Twig\Deprecated_Callable_Info;
use Twig\Environment;
use Twig\Error\Loader_Error;
use Twig\Error\Runtime_Error;
use Twig\Error\Syntax_Error;
use Twig\Expression_Parser\Infix\Arrow_Expression_Parser;
use Twig\Expression_Parser\Infix\Assignment_Expression_Parser;
use Twig\Expression_Parser\Infix\Binary_Operator_Expression_Parser;
use Twig\Expression_Parser\Infix\Conditional_Ternary_Expression_Parser;
use Twig\Expression_Parser\Infix\Dot_Expression_Parser;
use Twig\Expression_Parser\Infix\Filter_Expression_Parser;
use Twig\Expression_Parser\Infix\Function_Expression_Parser;
use Twig\Expression_Parser\Infix\Is_Expression_Parser;
use Twig\Expression_Parser\Infix\Is_Not_Expression_Parser;
use Twig\Expression_Parser\Infix\Square_Bracket_Expression_Parser;
use Twig\Expression_Parser\Infix_Associativity;
use Twig\Expression_Parser\Precedence_Change;
use Twig\Expression_Parser\Prefix\Grouping_Expression_Parser;
use Twig\Expression_Parser\Prefix\Literal_Expression_Parser;
use Twig\Expression_Parser\Prefix\Unary_Operator_Expression_Parser;
use Twig\Markup;
use Twig\Node\Expression\Binary\Add_Binary;
use Twig\Node\Expression\Binary\And_Binary;
use Twig\Node\Expression\Binary\Bitwise_And_Binary;
use Twig\Node\Expression\Binary\Bitwise_Or_Binary;
use Twig\Node\Expression\Binary\Bitwise_Xor_Binary;
use Twig\Node\Expression\Binary\Concat_Binary;
use Twig\Node\Expression\Binary\Div_Binary;
use Twig\Node\Expression\Binary\Elvis_Binary;
use Twig\Node\Expression\Binary\Ends_With_Binary;
use Twig\Node\Expression\Binary\Equal_Binary;
use Twig\Node\Expression\Binary\Floor_Div_Binary;
use Twig\Node\Expression\Binary\Greater_Binary;
use Twig\Node\Expression\Binary\Greater_Equal_Binary;
use Twig\Node\Expression\Binary\Has_Every_Binary;
use Twig\Node\Expression\Binary\Has_Some_Binary;
use Twig\Node\Expression\Binary\In_Binary;
use Twig\Node\Expression\Binary\Less_Binary;
use Twig\Node\Expression\Binary\Less_Equal_Binary;
use Twig\Node\Expression\Binary\Matches_Binary;
use Twig\Node\Expression\Binary\Mod_Binary;
use Twig\Node\Expression\Binary\Mul_Binary;
use Twig\Node\Expression\Binary\Not_Equal_Binary;
use Twig\Node\Expression\Binary\Not_In_Binary;
use Twig\Node\Expression\Binary\Not_Same_As_Binary;
use Twig\Node\Expression\Binary\Null_Coalesce_Binary;
use Twig\Node\Expression\Binary\Or_Binary;
use Twig\Node\Expression\Binary\Power_Binary;
use Twig\Node\Expression\Binary\Range_Binary;
use Twig\Node\Expression\Binary\Same_As_Binary;
use Twig\Node\Expression\Binary\Spaceship_Binary;
use Twig\Node\Expression\Binary\Starts_With_Binary;
use Twig\Node\Expression\Binary\Sub_Binary;
use Twig\Node\Expression\Binary\Xor_Binary;
use Twig\Node\Expression\Block_Reference_Expression;
use Twig\Node\Expression\Filter\Default_Filter;
use Twig\Node\Expression\Function_Node\Enum_Cases_Function;
use Twig\Node\Expression\Function_Node\Enum_Function;
use Twig\Node\Expression\Get_Attr_Expression;
use Twig\Node\Expression\Parent_Expression;
use Twig\Node\Expression\Test\Constant_Test;
use Twig\Node\Expression\Test\Defined_Test;
use Twig\Node\Expression\Test\Divisibleby_Test;
use Twig\Node\Expression\Test\Even_Test;
use Twig\Node\Expression\Test\Null_Test;
use Twig\Node\Expression\Test\Odd_Test;
use Twig\Node\Expression\Test\Sameas_Test;
use Twig\Node\Expression\Test\True_Test;
use Twig\Node\Expression\Unary\Neg_Unary;
use Twig\Node\Expression\Unary\Not_Unary;
use Twig\Node\Expression\Unary\Pos_Unary;
use Twig\Node\Expression\Unary\Spread_Unary;
use Twig\Node\Node;
use Twig\Parser;
use Twig\Sandbox\Security_Not_Allowed_Method_Error;
use Twig\Sandbox\Security_Not_Allowed_Property_Error;
use Twig\Source;
use Twig\Template;
use Twig\Template_Wrapper;
use Twig\Token_Parser\Apply_Token_Parser;
use Twig\Token_Parser\Block_Token_Parser;
use Twig\Token_Parser\Deprecated_Token_Parser;
use Twig\Token_Parser\Do_Token_Parser;
use Twig\Token_Parser\Embed_Token_Parser;
use Twig\Token_Parser\Extends_Token_Parser;
use Twig\Token_Parser\Flush_Token_Parser;
use Twig\Token_Parser\For_Token_Parser;
use Twig\Token_Parser\From_Token_Parser;
use Twig\Token_Parser\Guard_Token_Parser;
use Twig\Token_Parser\If_Token_Parser;
use Twig\Token_Parser\Import_Token_Parser;
use Twig\Token_Parser\Include_Token_Parser;
use Twig\Token_Parser\Macro_Token_Parser;
use Twig\Token_Parser\Set_Token_Parser;
use Twig\Token_Parser\Types_Token_Parser;
use Twig\Token_Parser\Use_Token_Parser;
use Twig\Token_Parser\With_Token_Parser;
use Twig\Twig_Filter;
use Twig\Twig_Function;
use Twig\Twig_Test;
use Twig\Util\Callable_Arguments_Extractor;
final class Core_Extension extends Abstract_Extension
{
    public const ARRAY_LIKE_CLASSES = ['ArrayIterator', 'ArrayObject', 'CachingIterator', 'RecursiveArrayIterator', 'RecursiveCachingIterator', 'SplDoublyLinkedList', 'SplFixedArray', 'SplObjectStorage', 'SplQueue', 'SplStack', 'WeakMap'];
    private const DEFAULT_TRIM_CHARS = " \t\n\r\x00\v";
    private $date_formats = ['F j, Y H:i', '%d days'];
    private array $number_format = [0, '.', ','];
    private ?\DateTimeZone $timezone = null;
    /**
     * Sets the default format to be used by the date filter.
     *
     * @param string|null $format             The default date format string
     * @param string|null $dateIntervalFormat The default date interval format string
     */
    public function set_date_format($format = null, $date_interval_format = null): void
    {
        if (null !== $format) {
            $this->date_formats[0] = $format;
        }
        if (null !== $date_interval_format) {
            $this->date_formats[1] = $date_interval_format;
        }
    }
    /**
     * Gets the default format to be used by the date filter.
     *
     * @return array The default date format string and the default date interval format string
     */
    public function get_date_format()
    {
        return $this->date_formats;
    }
    /**
     * Sets the default timezone to be used by the date filter.
     *
     * @param \DateTimeZone|string $timezone The default timezone string or a \DateTimeZone object
     */
    public function set_timezone($timezone): void
    {
        $this->timezone = $timezone instanceof \DateTimeZone ? $timezone : new \DateTimeZone($timezone);
    }
    /**
     * Gets the default timezone to be used by the date filter.
     *
     * @return \DateTimeZone The default timezone currently in use
     */
    public function get_timezone()
    {
        if (null === $this->timezone) {
            $this->timezone = new \DateTimeZone(date_default_timezone_get());
        }
        return $this->timezone;
    }
    /**
     * Sets the default format to be used by the number_format filter.
     *
     * @param int    $decimal      the number of decimal places to use
     * @param string $decimalPoint the character(s) to use for the decimal point
     * @param string $thousandSep  the character(s) to use for the thousands separator
     */
    public function set_number_format($decimal, $decimal_point, $thousand_sep): void
    {
        $this->number_format = [$decimal, $decimal_point, $thousand_sep];
    }
    /**
     * Get the default format used by the number_format filter.
     *
     * @return array The arguments for number_format()
     */
    public function get_number_format()
    {
        return $this->number_format;
    }
    public function get_token_parsers(): array
    {
        return [new Apply_Token_Parser(), new For_Token_Parser(), new If_Token_Parser(), new Extends_Token_Parser(), new Include_Token_Parser(), new Block_Token_Parser(), new Use_Token_Parser(), new Macro_Token_Parser(), new Import_Token_Parser(), new From_Token_Parser(), new Set_Token_Parser(), new Types_Token_Parser(), new Flush_Token_Parser(), new Do_Token_Parser(), new Embed_Token_Parser(), new With_Token_Parser(), new Deprecated_Token_Parser(), new Guard_Token_Parser()];
    }
    public function get_filters(): array
    {
        return [
            // formatting filters
            new Twig_Filter('date', $this->format_date(...)),
            new Twig_Filter('date_modify', $this->modify_date(...)),
            new Twig_Filter('format', self::sprintf(...)),
            new Twig_Filter('replace', self::replace(...)),
            new Twig_Filter('number_format', $this->format_number(...)),
            new Twig_Filter('abs', 'abs'),
            new Twig_Filter('round', self::round(...)),
            // encoding
            new Twig_Filter('url_encode', self::urlencode(...)),
            new Twig_Filter('json_encode', 'json_encode'),
            new Twig_Filter('convert_encoding', self::convert_encoding(...)),
            // string filters
            new Twig_Filter('title', self::title_case(...), ['needs_charset' => true]),
            new Twig_Filter('capitalize', self::capitalize(...), ['needs_charset' => true]),
            new Twig_Filter('upper', self::upper(...), ['needs_charset' => true]),
            new Twig_Filter('lower', self::lower(...), ['needs_charset' => true]),
            new Twig_Filter('striptags', self::striptags(...)),
            new Twig_Filter('trim', self::trim(...)),
            new Twig_Filter('nl2br', self::nl2br(...), ['pre_escape' => 'html', 'is_safe' => ['html']]),
            new Twig_Filter('spaceless', self::spaceless(...), ['is_safe' => ['html'], 'deprecation_info' => new Deprecated_Callable_Info('twig/twig', '3.12')]),
            // array helpers
            new Twig_Filter('join', self::join(...)),
            new Twig_Filter('split', self::split(...), ['needs_charset' => true]),
            new Twig_Filter('sort', self::sort(...), ['needs_environment' => true]),
            new Twig_Filter('merge', self::merge(...)),
            new Twig_Filter('batch', self::batch(...)),
            new Twig_Filter('column', self::column(...)),
            new Twig_Filter('filter', self::filter(...), ['needs_environment' => true]),
            new Twig_Filter('map', self::map(...), ['needs_environment' => true]),
            new Twig_Filter('reduce', self::reduce(...), ['needs_environment' => true]),
            new Twig_Filter('find', self::find(...), ['needs_environment' => true]),
            // string/array filters
            new Twig_Filter('reverse', self::reverse(...), ['needs_charset' => true]),
            new Twig_Filter('shuffle', self::shuffle(...), ['needs_charset' => true]),
            new Twig_Filter('length', self::length(...), ['needs_charset' => true]),
            new Twig_Filter('slice', self::slice(...), ['needs_charset' => true]),
            new Twig_Filter('first', self::first(...), ['needs_charset' => true]),
            new Twig_Filter('last', self::last(...), ['needs_charset' => true]),
            // iteration and runtime
            new Twig_Filter('default', self::default(...), ['node_class' => Default_Filter::class]),
            new Twig_Filter('keys', self::keys(...)),
            new Twig_Filter('invoke', self::invoke(...)),
        ];
    }
    public function get_functions(): array
    {
        return [new Twig_Function('parent', null, ['parser_callable' => self::parse_parent_function(...)]), new Twig_Function('block', null, ['parser_callable' => self::parse_block_function(...)]), new Twig_Function('attribute', null, ['parser_callable' => self::parse_attribute_function(...)]), new Twig_Function('max', 'max'), new Twig_Function('min', 'min'), new Twig_Function('range', 'range'), new Twig_Function('constant', self::constant(...), ['needs_environment' => true]), new Twig_Function('cycle', self::cycle(...)), new Twig_Function('random', self::random(...), ['needs_charset' => true]), new Twig_Function('date', $this->convert_date(...)), new Twig_Function('include', self::include(...), ['needs_environment' => true, 'needs_context' => true, 'is_safe' => ['all']]), new Twig_Function('source', self::source(...), ['needs_environment' => true, 'is_safe' => ['all']]), new Twig_Function('enum_cases', self::enum_cases(...), ['node_class' => Enum_Cases_Function::class]), new Twig_Function('enum', self::enum(...), ['node_class' => Enum_Function::class])];
    }
    public function get_tests(): array
    {
        return [new Twig_Test('even', null, ['node_class' => Even_Test::class]), new Twig_Test('odd', null, ['node_class' => Odd_Test::class]), new Twig_Test('defined', null, ['node_class' => Defined_Test::class]), new Twig_Test('same as', null, ['node_class' => Sameas_Test::class, 'one_mandatory_argument' => true]), new Twig_Test('none', null, ['node_class' => Null_Test::class]), new Twig_Test('null', null, ['node_class' => Null_Test::class]), new Twig_Test('divisible by', null, ['node_class' => Divisibleby_Test::class, 'one_mandatory_argument' => true]), new Twig_Test('constant', null, ['node_class' => Constant_Test::class]), new Twig_Test('empty', self::test_empty(...)), new Twig_Test('iterable', 'is_iterable'), new Twig_Test('sequence', self::test_sequence(...)), new Twig_Test('mapping', self::test_mapping(...)), new Twig_Test('true', null, ['node_class' => True_Test::class])];
    }
    public function get_node_visitors(): array
    {
        return [];
    }
    public function get_expression_parsers(): array
    {
        return [
            // unary operators
            new Unary_Operator_Expression_Parser(Not_Unary::class, 'not', 50, new Precedence_Change('twig/twig', '3.15', 70)),
            new Unary_Operator_Expression_Parser(Spread_Unary::class, '...', 512, description: 'Spread operator', operandPrecedence: 0),
            new Unary_Operator_Expression_Parser(Neg_Unary::class, '-', 500),
            new Unary_Operator_Expression_Parser(Pos_Unary::class, '+', 500),
            // binary operators
            new Binary_Operator_Expression_Parser(Elvis_Binary::class, '?:', 5, Infix_Associativity::Right, description: 'Elvis operator (a ?: b)', aliases: ['? :']),
            new Binary_Operator_Expression_Parser(Null_Coalesce_Binary::class, '??', 300, Infix_Associativity::Right, new Precedence_Change('twig/twig', '3.15', 5), description: 'Null coalescing operator (a ?? b)'),
            new Binary_Operator_Expression_Parser(Or_Binary::class, 'or', 10),
            new Binary_Operator_Expression_Parser(Xor_Binary::class, 'xor', 12),
            new Binary_Operator_Expression_Parser(And_Binary::class, 'and', 15),
            new Binary_Operator_Expression_Parser(Bitwise_Or_Binary::class, 'b-or', 16),
            new Binary_Operator_Expression_Parser(Bitwise_Xor_Binary::class, 'b-xor', 17),
            new Binary_Operator_Expression_Parser(Bitwise_And_Binary::class, 'b-and', 18),
            new Binary_Operator_Expression_Parser(Equal_Binary::class, '==', 20),
            new Binary_Operator_Expression_Parser(Not_Equal_Binary::class, '!=', 20),
            new Binary_Operator_Expression_Parser(Spaceship_Binary::class, '<=>', 20),
            new Binary_Operator_Expression_Parser(Less_Binary::class, '<', 20),
            new Binary_Operator_Expression_Parser(Greater_Binary::class, '>', 20),
            new Binary_Operator_Expression_Parser(Greater_Equal_Binary::class, '>=', 20),
            new Binary_Operator_Expression_Parser(Less_Equal_Binary::class, '<=', 20),
            new Binary_Operator_Expression_Parser(Not_In_Binary::class, 'not in', 20),
            new Binary_Operator_Expression_Parser(In_Binary::class, 'in', 20),
            new Binary_Operator_Expression_Parser(Matches_Binary::class, 'matches', 20),
            new Binary_Operator_Expression_Parser(Starts_With_Binary::class, 'starts with', 20),
            new Binary_Operator_Expression_Parser(Ends_With_Binary::class, 'ends with', 20),
            new Binary_Operator_Expression_Parser(Has_Some_Binary::class, 'has some', 20),
            new Binary_Operator_Expression_Parser(Has_Every_Binary::class, 'has every', 20),
            new Binary_Operator_Expression_Parser(Same_As_Binary::class, '===', 20),
            new Binary_Operator_Expression_Parser(Not_Same_As_Binary::class, '!==', 20),
            new Binary_Operator_Expression_Parser(Range_Binary::class, '..', 25),
            new Binary_Operator_Expression_Parser(Add_Binary::class, '+', 30),
            new Binary_Operator_Expression_Parser(Sub_Binary::class, '-', 30),
            new Binary_Operator_Expression_Parser(Concat_Binary::class, '~', 40, precedenceChange: new Precedence_Change('twig/twig', '3.15', 27)),
            new Binary_Operator_Expression_Parser(Mul_Binary::class, '*', 60),
            new Binary_Operator_Expression_Parser(Div_Binary::class, '/', 60),
            new Binary_Operator_Expression_Parser(Floor_Div_Binary::class, '//', 60, description: 'Floor division'),
            new Binary_Operator_Expression_Parser(Mod_Binary::class, '%', 60),
            new Binary_Operator_Expression_Parser(Power_Binary::class, '**', 200, Infix_Associativity::Right, description: 'Exponentiation operator'),
            // ternary operator
            new Conditional_Ternary_Expression_Parser(),
            // assignment operator
            new Assignment_Expression_Parser('='),
            // Twig callables
            new Is_Expression_Parser(),
            new Is_Not_Expression_Parser(),
            new Filter_Expression_Parser(),
            new Function_Expression_Parser(),
            // get attribute operators
            new Dot_Expression_Parser(),
            new Square_Bracket_Expression_Parser(),
            // group expression
            new Grouping_Expression_Parser(),
            // arrow function
            new Arrow_Expression_Parser(),
            // all literals
            new Literal_Expression_Parser(),
        ];
    }
    /**
     * Cycles over a sequence.
     *
     * @param array|\ArrayAccess $values   A non-empty sequence of values
     * @param int<0, max>        $position The position of the value to return in the cycle
     *
     * @return mixed The value at the given position in the sequence, wrapping around as needed
     *
     * @internal
     */
    public static function cycle($values, $position): mixed
    {
        if (!\is_array($values)) {
            if (!$values instanceof \ArrayAccess) {
                throw new Runtime_Error('The "cycle" function expects an array or "ArrayAccess" as first argument.');
            }
            if (!is_countable($values)) {
                // To be uncommented in 4.0
                // throw new RuntimeError('The "cycle" function expects a countable sequence as first argument.');
                trigger_deprecation('twig/twig', '3.12', 'Passing a non-countable sequence of values to "%s()" is deprecated.', __METHOD__);
                $values = self::to_array($values, false);
            }
        }
        if (!$count = \count($values)) {
            throw new Runtime_Error('The "cycle" function expects a non-empty sequence.');
        }
        return $values[$position % $count];
    }
    /**
     * Returns a random value depending on the supplied parameter type:
     * - a random item from a \Traversable or array
     * - a random character from a string
     * - a random integer between 0 and the integer parameter.
     *
     * @param \Traversable|array|int|float|string $values The values to pick a random item from
     * @param int|null                            $max    Maximum value used when $values is an int
     *
     * @return mixed A random value from the given sequence
     *
     * @throws RuntimeError when $values is an empty array (does not apply to an empty string which is returned as is)
     *
     * @internal
     */
    public static function random(string $charset, $values = null, $max = null)
    {
        if (null === $values) {
            return null === $max ? mt_rand() : mt_rand(0, (int) $max);
        }
        if (\is_int($values) || \is_float($values)) {
            if (null === $max) {
                if ($values < 0) {
                    $max = 0;
                    $min = $values;
                } else {
                    $max = $values;
                    $min = 0;
                }
            } else {
                $min = $values;
            }
            return mt_rand((int) $min, (int) $max);
        }
        if (\is_string($values)) {
            if ('' === $values) {
                return '';
            }
            if ('UTF-8' !== $charset) {
                $values = self::convert_encoding($values, 'UTF-8', $charset);
            }
            // unicode version of str_split()
            // split at all positions, but not after the start and not before the end
            $values = preg_split('/(?<!^)(?!$)/u', $values);
            if ('UTF-8' !== $charset) {
                foreach ($values as $i => $value) {
                    $values[$i] = self::convert_encoding($value, $charset, 'UTF-8');
                }
            }
        }
        if (!is_iterable($values)) {
            return $values;
        }
        $values = self::to_array($values);
        if (0 === \count($values)) {
            throw new Runtime_Error('The "random" function cannot pick from an empty sequence or mapping.');
        }
        return $values[array_rand($values, 1)];
    }
    /**
     * Formats a date.
     *
     *   {{ post.published_at|date("m/d/Y") }}
     *
     * @param \DateTimeInterface|\DateInterval|string|int|null $date     A date, a timestamp or null to use the current time
     * @param string|null                                      $format   The target format, null to use the default
     * @param \DateTimeZone|string|false|null                  $timezone The target timezone, null to use the default, false to leave unchanged
     */
    public function format_date($date, $format = null, $timezone = null): string
    {
        if (null === $format) {
            $formats = $this->get_date_format();
            $format = $date instanceof \DateInterval ? $formats[1] : $formats[0];
        }
        if ($date instanceof \DateInterval) {
            return $date->format($format);
        }
        return $this->convert_date($date, $timezone)->format($format);
    }
    /**
     * Returns a new date object modified.
     *
     *   {{ post.published_at|date_modify("-1day")|date("m/d/Y") }}
     *
     * @param \DateTimeInterface|string|int|null $date     A date, a timestamp or null to use the current time
     * @param string                             $modifier A modifier string
     *
     * @return \DateTime|\DateTimeImmutable
     *
     * @internal
     */
    public function modify_date($date, $modifier)
    {
        $date = $this->convert_date($date, false);
        $modified = $date->modify($modifier);
        if (false === $modified) {
            throw new Runtime_Error(\sprintf('Invalid modifier "%s" for the "date_modify" filter.', $modifier));
        }
        return $modified;
    }
    /**
     * Returns a formatted string.
     *
     * @param string|null $format
     *
     * @internal
     */
    public static function sprintf($format, ...$values): string
    {
        return \sprintf($format ?? '', ...$values);
    }
    /**
     * @internal
     */
    public static function date_converter(Environment $env, $date, $format = null, $timezone = null): string
    {
        return $env->get_extension(self::class)->format_date($date, $format, $timezone);
    }
    /**
     * Converts an input to a \DateTime instance.
     *
     *    {% if date(user.created_at) < date('+2days') %}
     *      {# do something #}
     *    {% endif %}
     *
     * @param \DateTimeInterface|string|int|null $date     A date, a timestamp or null to use the current time
     * @param \DateTimeZone|string|false|null    $timezone The target timezone, null to use the default, false to leave unchanged
     *
     * @return \DateTime|\DateTimeImmutable
     */
    public function convert_date($date = null, $timezone = null)
    {
        // determine the timezone
        if (false !== $timezone) {
            if (null === $timezone) {
                $timezone = $this->get_timezone();
            } elseif (!$timezone instanceof \DateTimeZone) {
                $timezone = new \DateTimeZone($timezone);
            }
        }
        // immutable dates
        if ($date instanceof \DateTimeImmutable) {
            return false !== $timezone ? $date->set_timezone($timezone) : $date;
        }
        if ($date instanceof \DateTime) {
            $date = clone $date;
            if (false !== $timezone) {
                $date->set_timezone($timezone);
            }
            return $date;
        }
        if (null === $date || 'now' === $date) {
            if (null === $date) {
                $date = 'now';
            }
            return new \DateTime($date, false !== $timezone ? $timezone : $this->get_timezone());
        }
        $as_string = (string) $date;
        if (ctype_digit($as_string) || '' !== $as_string && '-' === $as_string[0] && ctype_digit(substr($as_string, 1))) {
            $date = new \DateTime('@' . $date);
        } else {
            $date = new \DateTime($date);
        }
        if (false !== $timezone) {
            $date->set_timezone($timezone);
        }
        return $date;
    }
    /**
     * Replaces strings within a string.
     *
     * @param string|null        $str  String to replace in
     * @param array|\Traversable $from Replace values
     *
     * @internal
     */
    public static function replace($str, $from): string
    {
        if (!is_iterable($from)) {
            throw new Runtime_Error(\sprintf('The "replace" filter expects a sequence or a mapping, got "%s".', get_debug_type($from)));
        }
        return strtr($str ?? '', self::to_array($from));
    }
    /**
     * Rounds a number.
     *
     * @param int|float|string|null   $value     The value to round
     * @param int|float               $precision The rounding precision
     * @param 'common'|'ceil'|'floor' $method    The method to use for rounding
     *
     * @return float The rounded number
     *
     * @internal
     */
    public static function round($value, $precision = 0, $method = 'common'): float
    {
        $value = (float) $value;
        if ('common' === $method) {
            return round($value, $precision);
        }
        if ('ceil' !== $method && 'floor' !== $method) {
            throw new Runtime_Error('The "round" filter only supports the "common", "ceil", and "floor" methods.');
        }
        return match ($method) {
            'ceil' => ceil($value * 10 ** $precision) / 10 ** $precision,
            'floor' => floor($value * 10 ** $precision) / 10 ** $precision,
        };
    }
    /**
     * Formats a number.
     *
     * All of the formatting options can be left null, in that case the defaults will
     * be used. Supplying any of the parameters will override the defaults set in the
     * environment object.
     *
     * @param mixed       $number       A float/int/string of the number to format
     * @param int|null    $decimal      the number of decimal points to display
     * @param string|null $decimalPoint the character(s) to use for the decimal point
     * @param string|null $thousandSep  the character(s) to use for the thousands separator
     */
    public function format_number($number, $decimal = null, $decimal_point = null, $thousand_sep = null): string
    {
        $defaults = $this->get_number_format();
        if (null === $decimal) {
            $decimal = $defaults[0];
        }
        if (null === $decimal_point) {
            $decimal_point = $defaults[1];
        }
        if (null === $thousand_sep) {
            $thousand_sep = $defaults[2];
        }
        return number_format((float) $number, $decimal, $decimal_point, $thousand_sep);
    }
    /**
     * URL encodes (RFC 3986) a string as a path segment or an array as a query string.
     *
     * @param string|array|null $url A URL or an array of query parameters
     *
     * @internal
     */
    public static function urlencode($url): string
    {
        if (\is_array($url)) {
            return http_build_query($url, '', '&', \PHP_QUERY_RFC3986);
        }
        return rawurlencode($url ?? '');
    }
    /**
     * Merges any number of arrays or Traversable objects.
     *
     *  {% set items = { 'apple': 'fruit', 'orange': 'fruit' } %}
     *
     *  {% set items = items|merge({ 'peugeot': 'car' }, { 'banana': 'fruit' }) %}
     *
     *  {# items now contains { 'apple': 'fruit', 'orange': 'fruit', 'peugeot': 'car', 'banana': 'fruit' } #}
     *
     * @param array|\Traversable ...$arrays Any number of arrays or Traversable objects to merge
     *
     * @internal
     */
    public static function merge(...$arrays): array
    {
        $result = [];
        foreach ($arrays as $arg_number => $array) {
            if (!is_iterable($array)) {
                throw new Runtime_Error(\sprintf('The "merge" filter expects a sequence or a mapping, got "%s" for argument %d.', get_debug_type($array), $arg_number + 1));
            }
            $result = array_merge($result, self::to_array($array));
        }
        return $result;
    }
    /**
     * Slices a variable.
     *
     * @param mixed $item         A variable
     * @param int   $start        Start of the slice
     * @param int   $length       Size of the slice
     * @param bool  $preserveKeys Whether to preserve key or not (when the input is an array)
     *
     * @return mixed The sliced variable
     *
     * @internal
     */
    public static function slice(string $charset, $item, $start, $length = null, $preserve_keys = false): array|string
    {
        if ($item instanceof \Traversable) {
            while ($item instanceof \IteratorAggregate) {
                $item = $item->getIterator();
            }
            if ($start >= 0 && $length >= 0 && $item instanceof \Iterator) {
                try {
                    return iterator_to_array(new \Limit_Iterator($item, $start, $length ?? -1), $preserve_keys);
                } catch (\OutOfBoundsException) {
                    return [];
                }
            }
            $item = iterator_to_array($item, $preserve_keys);
        }
        if (\is_array($item)) {
            return \array_slice($item, $start, $length, $preserve_keys);
        }
        return mb_substr((string) $item, $start, $length, $charset);
    }
    /**
     * Returns the first element of the item.
     *
     * @param mixed $item A variable
     *
     * @return mixed The first element of the item
     *
     * @internal
     */
    public static function first(string $charset, $item)
    {
        $elements = self::slice($charset, $item, 0, 1, false);
        return \is_string($elements) ? $elements : current($elements);
    }
    /**
     * Returns the last element of the item.
     *
     * @param mixed $item A variable
     *
     * @return mixed The last element of the item
     *
     * @internal
     */
    public static function last(string $charset, $item)
    {
        $elements = self::slice($charset, $item, -1, 1, false);
        return \is_string($elements) ? $elements : current($elements);
    }
    /**
     * Joins the values to a string.
     *
     * The separators between elements are empty strings per default, you can define them with the optional parameters.
     *
     *  {{ [1, 2, 3]|join(', ', ' and ') }}
     *  {# returns 1, 2 and 3 #}
     *
     *  {{ [1, 2, 3]|join('|') }}
     *  {# returns 1|2|3 #}
     *
     *  {{ [1, 2, 3]|join }}
     *  {# returns 123 #}
     *
     * @param iterable|array|string|float|int|bool|null $value An array
     * @param string                                    $glue  The separator
     * @param string|null                               $and   The separator for the last pair
     *
     * @internal
     */
    public static function join($value, $glue = '', $and = null): string
    {
        if (!is_iterable($value)) {
            $value = (array) $value;
        }
        $value = self::to_array($value, false);
        if (0 === \count($value)) {
            return '';
        }
        if (null === $and || $and === $glue) {
            return implode($glue, $value);
        }
        if (1 === \count($value)) {
            return $value[0];
        }
        return implode($glue, \array_slice($value, 0, -1)) . $and . $value[\count($value) - 1];
    }
    /**
     * Splits the string into an array.
     *
     *  {{ "one,two,three"|split(',') }}
     *  {# returns [one, two, three] #}
     *
     *  {{ "one,two,three,four,five"|split(',', 3) }}
     *  {# returns [one, two, "three,four,five"] #}
     *
     *  {{ "123"|split('') }}
     *  {# returns [1, 2, 3] #}
     *
     *  {{ "aabbcc"|split('', 2) }}
     *  {# returns [aa, bb, cc] #}
     *
     * @param string|null $value     A string
     * @param string      $delimiter The delimiter
     * @param int|null    $limit     The limit
     *
     * @internal
     */
    public static function split(string $charset, $value, $delimiter, $limit = null): array
    {
        $value ??= '';
        if ('' !== $delimiter) {
            return null === $limit ? explode($delimiter, $value) : explode($delimiter, $value, $limit);
        }
        if ($limit <= 1) {
            return preg_split('/(?<!^)(?!$)/u', $value);
        }
        $length = mb_strlen($value, $charset);
        if ($length < $limit) {
            return [$value];
        }
        $r = [];
        for ($i = 0; $i < $length; $i += $limit) {
            $r[] = mb_substr($value, $i, $limit, $charset);
        }
        return $r;
    }
    /**
     * @internal
     */
    public static function default($value, $default = '')
    {
        if (self::test_empty($value)) {
            return $default;
        }
        return $value;
    }
    /**
     * Returns the keys for the given array.
     *
     * It is useful when you want to iterate over the keys of an array:
     *
     *  {% for key in array|keys %}
     *      {# ... #}
     *  {% endfor %}
     *
     * @internal
     */
    public static function keys($array): array
    {
        if ($array instanceof \Traversable) {
            while ($array instanceof \IteratorAggregate) {
                $array = $array->getIterator();
            }
            $keys = [];
            if ($array instanceof \Iterator) {
                $array->rewind();
                while ($array->valid()) {
                    $keys[] = $array->key();
                    $array->next();
                }
                return $keys;
            }
            foreach ($array as $key => $item) {
                $keys[] = $key;
            }
            return $keys;
        }
        if (!\is_array($array)) {
            return [];
        }
        return array_keys($array);
    }
    /**
     * Invokes a callable.
     *
     * @internal
     */
    public static function invoke(\Closure $arrow, ...$arguments): mixed
    {
        return $arrow(...$arguments);
    }
    /**
     * Reverses a variable.
     *
     * @param array|\Traversable|string|null $item         An array, a \Traversable instance, or a string
     * @param bool                           $preserveKeys Whether to preserve key or not
     *
     * @return mixed The reversed input
     *
     * @internal
     */
    public static function reverse(string $charset, $item, $preserve_keys = false): array|string
    {
        if ($item instanceof \Traversable) {
            return array_reverse(iterator_to_array($item), $preserve_keys);
        }
        if (\is_array($item)) {
            return array_reverse($item, $preserve_keys);
        }
        $string = (string) $item;
        if ('UTF-8' !== $charset) {
            $string = self::convert_encoding($string, 'UTF-8', $charset);
        }
        preg_match_all('/./us', $string, $matches);
        $string = implode('', array_reverse($matches[0]));
        if ('UTF-8' !== $charset) {
            return self::convert_encoding($string, $charset, 'UTF-8');
        }
        return $string;
    }
    /**
     * Shuffles an array, a \Traversable instance, or a string.
     * The function does not preserve keys.
     *
     * @param array|\Traversable|string|null $item
     *
     * @internal
     */
    public static function shuffle(string $charset, $item)
    {
        if (\is_string($item)) {
            if ('UTF-8' !== $charset) {
                $item = self::convert_encoding($item, 'UTF-8', $charset);
            }
            $item = preg_split('/(?<!^)(?!$)/u', $item, -1);
            shuffle($item);
            $item = implode('', $item);
            if ('UTF-8' !== $charset) {
                return self::convert_encoding($item, $charset, 'UTF-8');
            }
            return $item;
        }
        if (is_iterable($item)) {
            $item = self::to_array($item, false);
            shuffle($item);
        }
        return $item;
    }
    /**
     * Sorts an array.
     *
     * @param array|\Traversable $array
     * @param ?\Closure          $arrow
     *
     * @internal
     */
    public static function sort(Environment $env, $array, $arrow = null): array
    {
        if ($array instanceof \Traversable) {
            $array = iterator_to_array($array);
        } elseif (!\is_array($array)) {
            throw new Runtime_Error(\sprintf('The "sort" filter expects a sequence or a mapping, got "%s".', get_debug_type($array)));
        }
        if (null !== $arrow) {
            self::check_arrow($env, $arrow, 'sort', 'filter');
            uasort($array, $arrow);
        } else {
            asort($array);
        }
        return $array;
    }
    /**
     * @internal
     */
    public static function in_filter($value, $compare)
    {
        if ($value instanceof Markup) {
            $value = (string) $value;
        }
        if ($compare instanceof Markup) {
            $compare = (string) $compare;
        }
        if (\is_string($compare)) {
            if (\is_string($value) || \is_int($value) || \is_float($value)) {
                return str_contains($compare, (string) $value);
            }
            return false;
        }
        if (!is_iterable($compare)) {
            return false;
        }
        if (\is_object($value) || \is_resource($value)) {
            if (!\is_array($compare)) {
                foreach ($compare as $item) {
                    if ($item === $value) {
                        return true;
                    }
                }
                return false;
            }
            return \in_array($value, $compare, true);
        }
        foreach ($compare as $item) {
            if (0 === self::compare($value, $item)) {
                return true;
            }
        }
        return false;
    }
    /**
     * Compares two values using a more strict version of the PHP non-strict comparison operator.
     *
     * @see https://wiki.php.net/rfc/string_to_number_comparison
     * @see https://wiki.php.net/rfc/trailing_whitespace_numerics
     *
     * @internal
     */
    public static function compare($a, $b): int
    {
        // int <=> string
        if (\is_int($a) && \is_string($b)) {
            $b_trim = trim($b, " \t\n\r\v\f");
            if (!is_numeric($b_trim)) {
                return (string) $a <=> $b;
            }
            if ((int) $b_trim == $b_trim) {
                return $a <=> (int) $b_trim;
            }
            return (float) $a <=> (float) $b_trim;
        }
        if (\is_string($a) && \is_int($b)) {
            $a_trim = trim($a, " \t\n\r\v\f");
            if (!is_numeric($a_trim)) {
                return $a <=> (string) $b;
            }
            if ((int) $a_trim == $a_trim) {
                return (int) $a_trim <=> $b;
            }
            return (float) $a_trim <=> (float) $b;
        }
        // float <=> string
        if (\is_float($a) && \is_string($b)) {
            if (is_nan($a)) {
                return 1;
            }
            $b_trim = trim($b, " \t\n\r\v\f");
            if (!is_numeric($b_trim)) {
                return (string) $a <=> $b;
            }
            return $a <=> (float) $b_trim;
        }
        if (\is_string($a) && \is_float($b)) {
            if (is_nan($b)) {
                return -1;
            }
            $a_trim = trim($a, " \t\n\r\v\f");
            if (!is_numeric($a_trim)) {
                return $a <=> (string) $b;
            }
            return (float) $a_trim <=> $b;
        }
        // fallback to <=>
        return $a <=> $b;
    }
    /**
     * @throws RuntimeError When an invalid pattern is used
     *
     * @internal
     */
    public static function matches(string $regexp, ?string $str): int
    {
        set_error_handler(static function ($t, $m) use ($regexp): never {
            throw new Runtime_Error(\sprintf('Regexp "%s" passed to "matches" is not valid', $regexp) . substr($m, 12));
        });
        try {
            return preg_match($regexp, $str ?? '');
        } finally {
            restore_error_handler();
        }
    }
    /**
     * Returns a trimmed string.
     *
     * @param string|\Stringable|null $string
     * @param string|null             $characterMask
     * @param string                  $side          left, right, or both
     *
     * @throws RuntimeError When an invalid trimming side is used
     *
     * @internal
     */
    public static function trim($string, $character_mask = null, $side = 'both'): string|\Stringable
    {
        if (null === $character_mask) {
            $character_mask = self::DEFAULT_TRIM_CHARS;
        }
        $trimmed = match ($side) {
            'both' => trim((string) ($string ?? ''), $character_mask),
            'left' => ltrim((string) ($string ?? ''), $character_mask),
            'right' => rtrim((string) ($string ?? ''), $character_mask),
            default => throw new Runtime_Error('Trimming side must be "left", "right" or "both".'),
        };
        // trimming a safe string with the default character mask always returns a safe string (independently of the context)
        return $string instanceof Markup && self::DEFAULT_TRIM_CHARS === $character_mask ? new Markup($trimmed, $string->get_charset()) : $trimmed;
    }
    /**
     * Inserts HTML line breaks before all newlines in a string.
     *
     * @param string|null $string
     *
     * @internal
     */
    public static function nl2br($string): string
    {
        return nl2br($string ?? '');
    }
    /**
     * Removes whitespaces between HTML tags.
     *
     * @param string|null $content
     *
     * @internal
     */
    public static function spaceless($content): string
    {
        return trim((string) preg_replace('/>\s+</', '><', $content ?? ''));
    }
    /**
     * @param string|null $string
     * @param string      $to
     * @param string      $from
     *
     * @internal
     */
    public static function convert_encoding($string, $to, $from): string
    {
        if (!\function_exists('iconv')) {
            throw new Runtime_Error('Unable to convert encoding: required function iconv() does not exist. You should install ext-iconv or symfony/polyfill-iconv.');
        }
        return iconv($from, $to, $string ?? '');
    }
    /**
     * Returns the length of a variable.
     *
     * @param mixed $thing A variable
     *
     * @internal
     */
    public static function length(string $charset, $thing): int
    {
        if (null === $thing) {
            return 0;
        }
        if (\is_scalar($thing)) {
            return mb_strlen((string) $thing, $charset);
        }
        if (is_countable($thing) || $thing instanceof \Simple_Xml_Element) {
            return \count($thing);
        }
        if ($thing instanceof \Traversable) {
            return iterator_count($thing);
        }
        if ($thing instanceof \Stringable) {
            return mb_strlen((string) $thing, $charset);
        }
        return 1;
    }
    /**
     * Converts a string to uppercase.
     *
     * @param string|null $string A string
     *
     * @internal
     */
    public static function upper(string $charset, $string): string
    {
        return mb_strtoupper((string) ($string ?? ''), $charset);
    }
    /**
     * Converts a string to lowercase.
     *
     * @param string|null $string A string
     *
     * @internal
     */
    public static function lower(string $charset, $string): string
    {
        return mb_strtolower((string) ($string ?? ''), $charset);
    }
    /**
     * Strips HTML and PHP tags from a string.
     *
     * @param string|null          $string
     * @param string[]|string|null $allowable_tags
     *
     * @internal
     */
    public static function striptags($string, $allowable_tags = null): string
    {
        return strip_tags((string) ($string ?? ''), $allowable_tags);
    }
    /**
     * Returns a titlecased string.
     *
     * @param string|null $string A string
     *
     * @internal
     */
    public static function title_case(string $charset, $string): string
    {
        return mb_convert_case((string) ($string ?? ''), \MB_CASE_TITLE, $charset);
    }
    /**
     * Returns a capitalized string.
     *
     * @param string|null $string A string
     *
     * @internal
     */
    public static function capitalize(string $charset, $string): string
    {
        return mb_strtoupper(mb_substr((string) ($string ?? ''), 0, 1, $charset), $charset) . mb_strtolower(mb_substr((string) ($string ?? ''), 1, null, $charset), $charset);
    }
    /**
     * @internal
     *
     * to be removed in 4.0
     */
    public static function call_macro(Template $template, string $method, array $args, int $lineno, array $context, Source $source)
    {
        if (!method_exists($template, $method)) {
            $parent = $template;
            while ($parent = $parent->get_parent($context)) {
                if (method_exists($parent, $method)) {
                    return $parent->{$method}(...$args);
                }
            }
            throw new Runtime_Error(\sprintf('Macro "%s" is not defined in template "%s".', substr($method, \strlen('macro_')), $template->get_template_name()), $lineno, $source);
        }
        return $template->{$method}(...$args);
    }
    /**
     * @template TSequence
     *
     * @param TSequence $seq
     *
     * @return ($seq is iterable ? TSequence : array{})
     *
     * @internal
     */
    public static function ensure_traversable($seq): iterable
    {
        if (is_iterable($seq)) {
            return $seq;
        }
        return [];
    }
    /**
     * @internal
     */
    public static function to_array($seq, $preserve_keys = true)
    {
        if ($seq instanceof \Traversable) {
            return iterator_to_array($seq, $preserve_keys);
        }
        if (!\is_array($seq)) {
            return $seq;
        }
        return $preserve_keys ? $seq : array_values($seq);
    }
    /**
     * Checks if a variable is empty.
     *
     *    {# evaluates to true if the foo variable is null, false, or the empty string #}
     *    {% if foo is empty %}
     *        {# ... #}
     *    {% endif %}
     *
     * @param mixed $value A variable
     *
     * @internal
     */
    public static function test_empty($value): bool
    {
        if ($value instanceof \Countable) {
            return 0 === \count($value);
        }
        if ($value instanceof \Traversable) {
            foreach ($value as $_) {
                return false;
            }
            return true;
        }
        if ($value instanceof \Stringable) {
            return '' === (string) $value;
        }
        return '' === $value || false === $value || null === $value || [] === $value;
    }
    /**
     * Checks if a variable is a sequence.
     *
     *    {# evaluates to true if the foo variable is a sequence #}
     *    {% if foo is sequence %}
     *        {# ... #}
     *    {% endif %}
     *
     * @internal
     */
    public static function test_sequence($value): bool
    {
        if ($value instanceof \ArrayObject) {
            $value = $value->get_array_copy();
        }
        if ($value instanceof \Traversable) {
            $value = iterator_to_array($value);
        }
        return \is_array($value) && array_is_list($value);
    }
    /**
     * Checks if a variable is a mapping.
     *
     *    {# evaluates to true if the foo variable is a mapping #}
     *    {% if foo is mapping %}
     *        {# ... #}
     *    {% endif %}
     *
     * @internal
     */
    public static function test_mapping($value): bool
    {
        if ($value instanceof \ArrayObject) {
            $value = $value->get_array_copy();
        }
        if ($value instanceof \Traversable) {
            $value = iterator_to_array($value);
        }
        return \is_array($value) && !array_is_list($value) || \is_object($value);
    }
    /**
     * Renders a template.
     *
     * @param array                        $context
     * @param string|array|TemplateWrapper $template      The template to render or an array of templates to try consecutively
     * @param array                        $variables     The variables to pass to the template
     * @param bool                         $withContext
     * @param bool                         $ignoreMissing Whether to ignore missing templates or not
     * @param bool                         $sandboxed     Whether to sandbox the template or not
     *
     * @internal
     */
    public static function include(Environment $env, $context, $template, $variables = [], $with_context = true, $ignore_missing = false, $sandboxed = false): string
    {
        $already_sandboxed = false;
        $sandbox = null;
        if ($with_context) {
            $variables = array_merge($context, $variables);
        }
        if ($is_sandboxed = $sandboxed && $env->has_extension(Sandbox_Extension::class)) {
            $sandbox = $env->get_extension(Sandbox_Extension::class);
            if (!$already_sandboxed = $sandbox->is_sandboxed()) {
                $sandbox->enable_sandbox();
            }
        }
        try {
            $loaded = null;
            try {
                $loaded = $env->resolve_template($template);
            } catch (Loader_Error $e) {
                if (!$ignore_missing) {
                    throw $e;
                }
                return '';
            }
            if ($is_sandboxed) {
                $loaded->unwrap()->check_security();
            }
            return $loaded->render($variables);
        } finally {
            if ($is_sandboxed && !$already_sandboxed) {
                $sandbox->disable_sandbox();
            }
        }
    }
    /**
     * Returns a template content without rendering it.
     *
     * @param string $name          The template name
     * @param bool   $ignoreMissing Whether to ignore missing templates or not
     *
     * @internal
     */
    public static function source(Environment $env, string $name, $ignore_missing = false): string
    {
        $loader = $env->get_loader();
        try {
            return $loader->get_source_context($name)->get_code();
        } catch (Loader_Error $e) {
            if (!$ignore_missing) {
                throw $e;
            }
            return '';
        }
    }
    /**
     * Returns the list of cases of the enum.
     *
     * @template T of \UnitEnum
     *
     * @param class-string<T> $enum
     *
     * @return list<T>
     *
     * @internal
     */
    public static function enum_cases(string $enum): array
    {
        if (!enum_exists($enum)) {
            throw new Runtime_Error(\sprintf('Enum "%s" does not exist.', $enum));
        }
        return $enum::cases();
    }
    /**
     * Provides the ability to access enums by their class names.
     *
     * @template T of \UnitEnum
     *
     * @param class-string<T> $enum
     *
     * @return T
     *
     * @internal
     */
    public static function enum(string $enum): \Unit_Enum
    {
        if (!enum_exists($enum)) {
            throw new Runtime_Error(\sprintf('"%s" is not an enum.', $enum));
        }
        if (!$cases = $enum::cases()) {
            throw new Runtime_Error(\sprintf('"%s" is an empty enum.', $enum));
        }
        return $cases[0];
    }
    /**
     * Provides the ability to get constants from instances as well as class/global constants.
     *
     * @param string      $constant     The name of the constant
     * @param object|null $object       The object to get the constant from
     * @param bool        $checkDefined Whether to check if the constant is defined or not
     *
     * @return mixed Class constants can return many types like scalars, arrays, and
     *               objects depending on the PHP version (\BackedEnum, \UnitEnum, etc.)
     *               When $checkDefined is true, returns true when the constant is defined, false otherwise
     *
     * @internal
     */
    public static function constant(Environment $env, $constant, $object = null, bool $check_defined = false)
    {
        if (null !== $object) {
            if ('class' === $constant) {
                return $check_defined ? true : $object::class;
            }
            $constant = $object::class . '::' . $constant;
        }
        if ($env->has_extension(Sandbox_Extension::class)) {
            $env->get_extension(Sandbox_Extension::class)->check_constant_allowed($constant);
        }
        if (!\defined($constant)) {
            if ($check_defined) {
                return false;
            }
            if ('::class' === strtolower(substr($constant, -7))) {
                throw new Runtime_Error(\sprintf('You cannot use the Twig function "constant" to access "%s". You could provide an object and call constant("class", $object) or use the class name directly as a string.', $constant));
            }
            throw new Runtime_Error(\sprintf('Constant "%s" is undefined.', $constant));
        }
        return $check_defined ? true : \constant($constant);
    }
    /**
     * Batches item.
     *
     * @param array $items An array of items
     * @param int   $size  The size of the batch
     * @param mixed $fill  A value used to fill missing items
     *
     * @internal
     */
    public static function batch($items, $size, $fill = null, $preserve_keys = true): array
    {
        if (!is_iterable($items)) {
            throw new Runtime_Error(\sprintf('The "batch" filter expects a sequence or a mapping, got "%s".', get_debug_type($items)));
        }
        $size = (int) ceil($size);
        $result = array_chunk(self::to_array($items, $preserve_keys), $size, $preserve_keys);
        if (null !== $fill && $result) {
            $last = \count($result) - 1;
            if ($fill_count = $size - \count($result[$last])) {
                for ($i = 0; $i < $fill_count; ++$i) {
                    $result[$last][] = $fill;
                }
            }
        }
        return $result;
    }
    /**
     * Returns the attribute value for a given array/object.
     *
     * @param mixed  $object            The object or array from where to get the item
     * @param mixed  $item              The item to get from the array or object
     * @param array  $arguments         An array of arguments to pass if the item is an object method
     * @param string $type              The type of attribute (@see \Twig\Template constants)
     * @param bool   $isDefinedTest     Whether this is only a defined check
     * @param bool   $ignoreStrictCheck Whether to ignore the strict attribute check or not
     * @param int    $lineno            The template line where the attribute was called
     *
     * @return mixed The attribute value, or a Boolean when $isDefinedTest is true, or null when the attribute is not set and $ignoreStrictCheck is true
     *
     * @throws RuntimeError if the attribute does not exist and Twig is running in strict mode and $isDefinedTest is false
     *
     * @internal
     */
    public static function get_attribute(Environment $env, Source $source, $object, $item, array $arguments = [], $type = Template::ANY_CALL, $is_defined_test = false, $ignore_strict_check = false, $sandboxed = false, int $lineno = -1)
    {
        $property_not_allowed_error = null;
        // array
        if (Template::METHOD_CALL !== $type) {
            $array_item = \is_bool($item) || \is_float($item) ? (int) $item : $item;
            if ($sandboxed && $object instanceof \ArrayAccess && !\in_array($object::class, self::ARRAY_LIKE_CLASSES, true)) {
                try {
                    $env->get_extension(Sandbox_Extension::class)->check_property_allowed($object, $array_item, $lineno, $source);
                } catch (Security_Not_Allowed_Property_Error $e) {
                    $property_not_allowed_error = $e;
                    goto methodCheck;
                }
            }
            if (match (true) {
                \is_array($object) => \array_key_exists($array_item = (string) $array_item, $object),
                $object instanceof \ArrayAccess => $object->offsetExists($array_item),
                default => false,
            }) {
                if ($is_defined_test) {
                    return true;
                }
                return $object[$array_item];
            }
            if (Template::ARRAY_CALL === $type || !\is_object($object)) {
                if ($is_defined_test) {
                    return false;
                }
                if ($ignore_strict_check || !$env->is_strict_variables()) {
                    return;
                }
                if ($object instanceof \ArrayAccess) {
                    if (\is_object($array_item) || \is_array($array_item)) {
                        $message = \sprintf('Key of type "%s" does not exist in ArrayAccess-able object of class "%s".', get_debug_type($array_item), get_debug_type($object));
                    } else {
                        $message = \sprintf('Key "%s" does not exist in ArrayAccess-able object of class "%s".', $array_item, get_debug_type($object));
                    }
                } elseif (\is_object($object)) {
                    $message = \sprintf('Impossible to access a key "%s" on an object of class "%s" that does not implement ArrayAccess interface.', $item, get_debug_type($object));
                } elseif (\is_array($object)) {
                    if (!$object) {
                        $message = \sprintf('Key "%s" does not exist as the sequence/mapping is empty.', $array_item);
                    } else {
                        $message = \sprintf('Key "%s" for sequence/mapping with keys "%s" does not exist.', $array_item, implode(', ', array_keys($object)));
                    }
                } elseif (Template::ARRAY_CALL === $type) {
                    if (null === $object) {
                        $message = \sprintf('Impossible to access a key ("%s") on a null variable.', $item);
                    } else {
                        $message = \sprintf('Impossible to access a key ("%s") on a %s variable ("%s").', $item, get_debug_type($object), $object);
                    }
                } elseif (null === $object) {
                    $message = \sprintf('Impossible to access an attribute ("%s") on a null variable.', $item);
                } else {
                    $message = \sprintf('Impossible to access an attribute ("%s") on a %s variable ("%s").', $item, get_debug_type($object), $object);
                }
                throw new Runtime_Error($message, $lineno, $source);
            }
        }
        $item = (string) $item;
        if (!\is_object($object)) {
            if ($is_defined_test) {
                return false;
            }
            if ($ignore_strict_check || !$env->is_strict_variables()) {
                return;
            }
            if (null === $object) {
                $message = \sprintf('Impossible to invoke a method ("%s") on a null variable.', $item);
            } elseif (\is_array($object)) {
                $message = \sprintf('Impossible to invoke a method ("%s") on a sequence/mapping.', $item);
            } else {
                $message = \sprintf('Impossible to invoke a method ("%s") on a %s variable ("%s").', $item, get_debug_type($object), $object);
            }
            throw new Runtime_Error($message, $lineno, $source);
        }
        if ($object instanceof Template) {
            throw new Runtime_Error('Accessing \Twig\Template attributes is forbidden.', $lineno, $source);
        }
        // object property
        if (Template::METHOD_CALL !== $type) {
            if ($sandboxed) {
                try {
                    $env->get_extension(Sandbox_Extension::class)->check_property_allowed($object, $item, $lineno, $source);
                } catch (Security_Not_Allowed_Property_Error $e) {
                    $property_not_allowed_error = $e;
                    goto methodCheck;
                }
            }
            static $property_checkers = [];
            if ($object instanceof \Closure && '__invoke' === $item) {
                if ($sandboxed) {
                    $env->get_extension(Sandbox_Extension::class)->check_method_allowed($object, '__invoke', $lineno, $source);
                }
                return $is_defined_test ? true : $object();
            }
            if (isset($object->{$item}) || ($property_checkers[$object::class][$item] ??= self::get_property_checker($object::class, $item))($object, $item)) {
                if ($is_defined_test) {
                    return true;
                }
                return $object->{$item};
            }
            if ($object instanceof \DateTimeInterface && \in_array($item, ['date', 'timezone', 'timezone_type'], true)) {
                if ($is_defined_test) {
                    return true;
                }
                return ((array) $object)[$item];
            }
            if (\defined($object::class . '::' . $item)) {
                if ($is_defined_test) {
                    return true;
                }
                return \constant($object::class . '::' . $item);
            }
        }
        methodCheck:
        static $cache = [];
        $class = $object::class;
        // object method
        // precedence: getXxx() > isXxx() > hasXxx()
        if (!isset($cache[$class])) {
            $methods = get_class_methods($object);
            if ($object instanceof \Closure) {
                $methods[] = '__invoke';
            }
            sort($methods);
            $lc_methods = array_map(strtolower(...), $methods);
            $class_cache = [];
            foreach ($methods as $i => $method) {
                $class_cache[$method] = $method;
                $class_cache[$lc_name = $lc_methods[$i]] = $method;
                if ('g' === $lc_name[0] && str_starts_with($lc_name, 'get')) {
                    $name = substr($method, 3);
                    $lc_name = substr($lc_name, 3);
                } elseif ('i' === $lc_name[0] && str_starts_with($lc_name, 'is')) {
                    $name = substr($method, 2);
                    $lc_name = substr($lc_name, 2);
                } elseif ('h' === $lc_name[0] && str_starts_with($lc_name, 'has')) {
                    $name = substr($method, 3);
                    $lc_name = substr($lc_name, 3);
                    if (\in_array('is' . $lc_name, $lc_methods, true)) {
                        continue;
                    }
                } else {
                    continue;
                }
                // skip get() and is() methods (in which case, $name is empty)
                if ($name) {
                    if (!isset($class_cache[$name])) {
                        $class_cache[$name] = $method;
                    }
                    if (!isset($class_cache[$lc_name])) {
                        $class_cache[$lc_name] = $method;
                    }
                }
            }
            $cache[$class] = $class_cache;
        }
        $call = false;
        if (isset($cache[$class][$item])) {
            $method = $cache[$class][$item];
        } elseif (isset($cache[$class][$lc_item = strtolower($item)])) {
            $method = $cache[$class][$lc_item];
        } elseif (isset($cache[$class]['__call'])) {
            $method = $item;
            $call = true;
        } else {
            if ($is_defined_test) {
                return false;
            }
            if ($property_not_allowed_error) {
                throw $property_not_allowed_error;
            }
            if ($ignore_strict_check || !$env->is_strict_variables()) {
                return;
            }
            throw new Runtime_Error(\sprintf('Neither the property "%1$s" nor one of the methods "%1$s()", "get%1$s()", "is%1$s()", "has%1$s()" or "__call()" exist and have public access in class "%2$s".', $item, $class), $lineno, $source);
        }
        if ($sandboxed) {
            try {
                $env->get_extension(Sandbox_Extension::class)->check_method_allowed($object, $method, $lineno, $source);
            } catch (Security_Not_Allowed_Method_Error $e) {
                if ($is_defined_test) {
                    return false;
                }
                if ($property_not_allowed_error) {
                    throw $property_not_allowed_error;
                }
                throw $e;
            }
        }
        if ($is_defined_test) {
            return true;
        }
        // Some objects throw exceptions when they have __call, and the method we try
        // to call is not supported. If ignoreStrictCheck is true, we should return null.
        try {
            $ret = $object->{$method}(...$arguments);
        } catch (\BadMethodCallException $e) {
            if ($call && ($ignore_strict_check || !$env->is_strict_variables())) {
                return;
            }
            throw $e;
        }
        return $ret;
    }
    /**
     * Returns the values from a single column in the input array.
     *
     * <pre>
     *  {% set items = [{ 'fruit' : 'apple'}, {'fruit' : 'orange' }] %}
     *
     *  {% set fruits = items|column('fruit') %}
     *
     *  {# fruits now contains ['apple', 'orange'] #}
     * </pre>
     *
     * @param array|\Traversable $array An array
     * @param int|string         $name  The column name
     * @param int|string|null    $index The column to use as the index/keys for the returned array
     *
     * @return array The array of values
     *
     * @internal
     */
    public static function column($array, $name, $index = null): array
    {
        if (!is_iterable($array)) {
            throw new Runtime_Error(\sprintf('The "column" filter expects a sequence or a mapping, got "%s".', get_debug_type($array)));
        }
        if ($array instanceof \Traversable) {
            $array = iterator_to_array($array);
        }
        return array_column($array, $name, $index);
    }
    /**
     * @param \Closure $arrow
     *
     * @internal
     */
    public static function filter(Environment $env, $array, $arrow): array|\Callback_Filter_Iterator
    {
        if (!is_iterable($array)) {
            throw new Runtime_Error(\sprintf('The "filter" filter expects a sequence/mapping or "Traversable", got "%s".', get_debug_type($array)));
        }
        self::check_arrow($env, $arrow, 'filter', 'filter');
        if (\is_array($array)) {
            return array_filter($array, $arrow, \ARRAY_FILTER_USE_BOTH);
        }
        // the IteratorIterator wrapping is needed as some internal PHP classes are \Traversable but do not implement \Iterator
        return new \Callback_Filter_Iterator(new \Iterator_Iterator($array), $arrow);
    }
    /**
     * @param \Closure $arrow
     *
     * @internal
     */
    public static function find(Environment $env, $array, $arrow)
    {
        if (!is_iterable($array)) {
            throw new Runtime_Error(\sprintf('The "find" filter expects a sequence or a mapping, got "%s".', get_debug_type($array)));
        }
        self::check_arrow($env, $arrow, 'find', 'filter');
        foreach ($array as $k => $v) {
            if ($arrow($v, $k)) {
                return $v;
            }
        }
        return null;
    }
    /**
     * @param \Closure $arrow
     *
     * @internal
     * @return mixed[]
     */
    public static function map(Environment $env, $array, $arrow): array
    {
        if (!is_iterable($array)) {
            throw new Runtime_Error(\sprintf('The "map" filter expects a sequence or a mapping, got "%s".', get_debug_type($array)));
        }
        self::check_arrow($env, $arrow, 'map', 'filter');
        $r = [];
        foreach ($array as $k => $v) {
            $r[$k] = $arrow($v, $k);
        }
        return $r;
    }
    /**
     * @param \Closure $arrow
     *
     * @internal
     */
    public static function reduce(Environment $env, $array, $arrow, $initial = null)
    {
        if (!is_iterable($array)) {
            throw new Runtime_Error(\sprintf('The "reduce" filter expects a sequence or a mapping, got "%s".', get_debug_type($array)));
        }
        self::check_arrow($env, $arrow, 'reduce', 'filter');
        $accumulator = $initial;
        foreach ($array as $key => $value) {
            $accumulator = $arrow($accumulator, $value, $key);
        }
        return $accumulator;
    }
    /**
     * @param \Closure $arrow
     *
     * @internal
     */
    public static function array_some(Environment $env, $array, $arrow): bool
    {
        if (!is_iterable($array)) {
            throw new Runtime_Error(\sprintf('The "has some" test expects a sequence or a mapping, got "%s".', get_debug_type($array)));
        }
        self::check_arrow($env, $arrow, 'has some', 'operator');
        foreach ($array as $k => $v) {
            if ($arrow($v, $k)) {
                return true;
            }
        }
        return false;
    }
    /**
     * @param \Closure $arrow
     *
     * @internal
     */
    public static function array_every(Environment $env, $array, $arrow): bool
    {
        if (!is_iterable($array)) {
            throw new Runtime_Error(\sprintf('The "has every" test expects a sequence or a mapping, got "%s".', get_debug_type($array)));
        }
        self::check_arrow($env, $arrow, 'has every', 'operator');
        foreach ($array as $k => $v) {
            if (!$arrow($v, $k)) {
                return false;
            }
        }
        return true;
    }
    /**
     * @internal
     */
    public static function check_arrow(Environment $env, $arrow, string $thing, $type): void
    {
        if ($arrow instanceof \Closure) {
            return;
        }
        if ($env->has_extension(Sandbox_Extension::class) && $env->get_extension(Sandbox_Extension::class)->is_sandboxed()) {
            throw new Runtime_Error(\sprintf('The callable passed to the "%s" %s must be a Closure in sandbox mode.', $thing, $type));
        }
        trigger_deprecation('twig/twig', '3.15', 'Passing a callable that is not a PHP \Closure as an argument to the "%s" %s is deprecated.', $thing, $type);
    }
    /**
     * @internal to be removed in Twig 4
     */
    public static function capture_output(iterable $body): string
    {
        $level = ob_get_level();
        ob_start();
        try {
            foreach ($body as $data) {
                echo $data;
            }
        } catch (\Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        }
        return ob_get_clean();
    }
    /**
     * @internal
     */
    public static function parse_parent_function(Parser $parser, Node $fake_node, $args, int $line): \Twig\Node\Expression\Parent_Expression
    {
        if (!$block_name = $parser->peek_block_stack()) {
            throw new Syntax_Error('Calling the "parent" function outside of a block is forbidden.', $line, $parser->get_stream()->get_source_context());
        }
        if (!$parser->has_inheritance()) {
            throw new Syntax_Error('Calling the "parent" function on a template that does not call "extends" or "use" is forbidden.', $line, $parser->get_stream()->get_source_context());
        }
        return new Parent_Expression($block_name, $line);
    }
    /**
     * @internal
     */
    public static function parse_block_function(Parser $parser, Node $fake_node, $args, int $line): \Twig\Node\Expression\Block_Reference_Expression
    {
        $fake_function = new Twig_Function('block', static fn($name, $template = null) => null);
        $args = (new Callable_Arguments_Extractor($fake_node, $fake_function))->extract_arguments($args);
        return new Block_Reference_Expression($args[0], $args[1] ?? null, $line);
    }
    /**
     * @internal
     */
    public static function parse_attribute_function(Parser $parser, Node $fake_node, $args, int $line): \Twig\Node\Expression\Get_Attr_Expression
    {
        $fake_function = new Twig_Function('attribute', static fn($variable, $attribute, $arguments = null) => null);
        $args = (new Callable_Arguments_Extractor($fake_node, $fake_function))->extract_arguments($args);
        /*
        Deprecation to uncomment sometimes during the lifetime of the 4.x branch
        $src = $parser->getStream()->getSourceContext();
        $dep = new DeprecatedCallableInfo('twig/twig', '3.15', 'The "attribute" function is deprecated, use the "." notation instead.');
        $dep->setName('attribute');
        $dep->setType('function');
        $dep->triggerDeprecation($src->getPath() ?: $src->getName(), $line);
        */
        return new Get_Attr_Expression($args[0], $args[1], $args[2] ?? null, Template::ANY_CALL, $line);
    }
    private static function get_property_checker(string $class, string $property): \Closure
    {
        static $class_reflectors = [];
        $class = $class_reflectors[$class] ??= new \ReflectionClass($class);
        if (!$class->has_property($property)) {
            static $property_exists;
            return $property_exists ??= \property_exists(...);
        }
        $property = $class->get_property($property);
        if (!$property->is_public() || $property->is_static()) {
            static $false;
            return $false ??= static fn(): bool => false;
        }
        return static fn($object) => $property->is_initialized($object);
    }
}