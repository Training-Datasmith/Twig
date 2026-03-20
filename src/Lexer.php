<?php

declare (strict_types=1);
/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Twig;

use Twig\Error\Syntax_Error;
use Twig\Expression_Parser\Expression_Parsers;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Lexer
{
    private bool $is_initialized = false;
    private ?array $tokens = null;
    private ?string $code = null;
    private $cursor;
    private ?int $lineno = null;
    private ?int $end = null;
    private $state;
    private ?array $states = null;
    private ?array $brackets = null;
    private ?\Twig\Source $source = null;
    private array $options;
    private ?array $regexes = null;
    private ?int $position = null;
    private ?array $positions = null;
    private $current_var_block_line;
    private array $opening_brackets = ['{', '(', '['];
    private array $closing_brackets = ['}', ')', ']'];
    public const STATE_DATA = 0;
    public const STATE_BLOCK = 1;
    public const STATE_VAR = 2;
    public const STATE_STRING = 3;
    public const STATE_INTERPOLATION = 4;
    public const REGEX_NAME = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/A';
    public const REGEX_STRING = '/"([^#"\\\\]*(?:\\\\.[^#"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'/As';
    public const REGEX_NUMBER = '/(?(DEFINE)
        (?<LNUM>[0-9]+(_[0-9]+)*)               # Integers (with underscores)   123_456
        (?<FRAC>\.(?&LNUM))                     # Fractional part               .456
        (?<EXPONENT>[eE][+-]?(?&LNUM))          # Exponent part                 E+10
        (?<DNUM>(?&LNUM)(?:(?&FRAC))?)          # Decimal number                123_456.456
    )(?:(?&DNUM)(?:(?&EXPONENT))?)              #                               123_456.456E+10
    /Ax';
    public const REGEX_DQ_STRING_DELIM = '/"/A';
    public const REGEX_DQ_STRING_PART = '/[^#"\\\\]*(?:(?:\\\\.|#(?!\{))[^#"\\\\]*)*/As';
    public const REGEX_INLINE_COMMENT = '/#[^\n]*/A';
    public const PUNCTUATION = '()[]{}?:.,|';
    private const SPECIAL_CHARS = ['f' => "\f", 'n' => "\n", 'r' => "\r", 't' => "\t", 'v' => "\v"];
    public function __construct(private readonly Environment $env, array $options = [])
    {
        $this->options = array_merge(['tag_comment' => ['{#', '#}'], 'tag_block' => ['{%', '%}'], 'tag_variable' => ['{{', '}}'], 'whitespace_trim' => '-', 'whitespace_line_trim' => '~', 'whitespace_line_chars' => ' \t\0\x0B', 'interpolation' => ['#{', '}']], $options);
    }
    private function initialize(): void
    {
        if ($this->is_initialized) {
            return;
        }
        $this->regexes = [
            // }}
            'lex_var' => '{
                \s*
                (?:' . preg_quote($this->options['whitespace_trim'] . $this->options['tag_variable'][1], '#') . '\s*' . '|' . preg_quote($this->options['whitespace_line_trim'] . $this->options['tag_variable'][1], '#') . '[' . $this->options['whitespace_line_chars'] . ']*' . '|' . preg_quote((string) $this->options['tag_variable'][1], '#') . ')
            }Ax',
            // %}
            'lex_block' => '{
                \s*
                (?:' . preg_quote($this->options['whitespace_trim'] . $this->options['tag_block'][1], '#') . '\s*\n?' . '|' . preg_quote($this->options['whitespace_line_trim'] . $this->options['tag_block'][1], '#') . '[' . $this->options['whitespace_line_chars'] . ']*' . '|' . preg_quote((string) $this->options['tag_block'][1], '#') . '\n?' . ')
            }Ax',
            // {% endverbatim %}
            'lex_raw_data' => '{' . preg_quote((string) $this->options['tag_block'][0], '#') . '(' . preg_quote($this->options['whitespace_trim'], '#') . '|' . preg_quote($this->options['whitespace_line_trim'], '#') . ')?\s*endverbatim\s*' . '(?:' . preg_quote($this->options['whitespace_trim'] . $this->options['tag_block'][1], '#') . '\s*' . '|' . preg_quote($this->options['whitespace_line_trim'] . $this->options['tag_block'][1], '#') . '[' . $this->options['whitespace_line_chars'] . ']*' . '|' . preg_quote((string) $this->options['tag_block'][1], '#') . ')
            }sx',
            'operator' => $this->get_operator_regex(),
            // #}
            'lex_comment' => '{
                (?:' . preg_quote($this->options['whitespace_trim'] . $this->options['tag_comment'][1], '#') . '\s*\n?' . '|' . preg_quote($this->options['whitespace_line_trim'] . $this->options['tag_comment'][1], '#') . '[' . $this->options['whitespace_line_chars'] . ']*' . '|' . preg_quote((string) $this->options['tag_comment'][1], '#') . '\n?' . ')
            }sx',
            // verbatim %}
            'lex_block_raw' => '{
                \s*verbatim\s*
                (?:' . preg_quote($this->options['whitespace_trim'] . $this->options['tag_block'][1], '#') . '\s*' . '|' . preg_quote($this->options['whitespace_line_trim'] . $this->options['tag_block'][1], '#') . '[' . $this->options['whitespace_line_chars'] . ']*' . '|' . preg_quote((string) $this->options['tag_block'][1], '#') . ')
            }Asx',
            'lex_block_line' => '{\s*line\s+(\d+)\s*' . preg_quote((string) $this->options['tag_block'][1], '#') . '}As',
            // {{ or {% or {#
            'lex_tokens_start' => '{
                (' . preg_quote((string) $this->options['tag_variable'][0], '#') . '|' . preg_quote((string) $this->options['tag_block'][0], '#') . '|' . preg_quote((string) $this->options['tag_comment'][0], '#') . ')(' . preg_quote((string) $this->options['whitespace_trim'], '#') . '|' . preg_quote((string) $this->options['whitespace_line_trim'], '#') . ')?
            }sx',
            'interpolation_start' => '{' . preg_quote((string) $this->options['interpolation'][0], '#') . '\s*}A',
            'interpolation_end' => '{\s*' . preg_quote((string) $this->options['interpolation'][1], '#') . '}A',
        ];
        $this->is_initialized = true;
    }
    public function tokenize(Source $source): Token_Stream
    {
        $this->initialize();
        $this->source = $source;
        $this->code = str_replace(["\r\n", "\r"], "\n", $source->get_code());
        $this->cursor = 0;
        $this->lineno = 1;
        $this->end = \strlen($this->code);
        $this->tokens = [];
        $this->state = self::STATE_DATA;
        $this->states = [];
        $this->brackets = [];
        $this->position = -1;
        $this->current_var_block_line = 0;
        // find all token starts in one go
        preg_match_all($this->regexes['lex_tokens_start'], $this->code, $matches, \PREG_OFFSET_CAPTURE);
        $this->positions = $matches;
        while ($this->cursor < $this->end) {
            // dispatch to the lexing functions depending
            // on the current state
            switch ($this->state) {
                case self::STATE_DATA:
                    $this->lex_data();
                    break;
                case self::STATE_BLOCK:
                    $this->lex_block();
                    break;
                case self::STATE_VAR:
                    $this->lex_var();
                    break;
                case self::STATE_STRING:
                    $this->lex_string();
                    break;
                case self::STATE_INTERPOLATION:
                    $this->lex_interpolation();
                    break;
            }
        }
        $this->push_token(Token::EOF_TYPE);
        if ($this->brackets) {
            [$expect, $lineno] = array_pop($this->brackets);
            throw new Syntax_Error(\sprintf('Unclosed "%s".', $expect), $lineno, $this->source);
        }
        return new Token_Stream($this->tokens, $this->source);
    }
    private function lex_data(): void
    {
        // if no matches are left we return the rest of the template as simple text token
        if ($this->position == \count($this->positions[0]) - 1) {
            $this->push_token(Token::TEXT_TYPE, substr((string) $this->code, $this->cursor));
            $this->cursor = $this->end;
            return;
        }
        // Find the first token after the current cursor
        $position = $this->positions[0][++$this->position];
        while ($position[1] < $this->cursor) {
            if ($this->position == \count($this->positions[0]) - 1) {
                return;
            }
            $position = $this->positions[0][++$this->position];
        }
        // push the template text first
        $text = $text_content = substr((string) $this->code, $this->cursor, $position[1] - $this->cursor);
        // trim?
        if (isset($this->positions[2][$this->position][0])) {
            if ($this->options['whitespace_trim'] === $this->positions[2][$this->position][0]) {
                // whitespace_trim detected ({%-, {{- or {#-)
                $text = rtrim($text);
            } elseif ($this->options['whitespace_line_trim'] === $this->positions[2][$this->position][0]) {
                // whitespace_line_trim detected ({%~, {{~ or {#~)
                // don't trim \r and \n
                $text = rtrim($text, " \t\x00\v");
            }
        }
        $this->push_token(Token::TEXT_TYPE, $text);
        $this->move_cursor($text_content . $position[0]);
        switch ($this->positions[1][$this->position][0]) {
            case $this->options['tag_comment'][0]:
                $this->lex_comment();
                break;
            case $this->options['tag_block'][0]:
                // raw data?
                if (preg_match($this->regexes['lex_block_raw'], (string) $this->code, $match, 0, $this->cursor)) {
                    $this->move_cursor($match[0]);
                    $this->lex_raw_data();
                    // {% line \d+ %}
                } elseif (preg_match($this->regexes['lex_block_line'], (string) $this->code, $match, 0, $this->cursor)) {
                    $this->move_cursor($match[0]);
                    $this->lineno = (int) $match[1];
                } else {
                    $this->push_token(Token::BLOCK_START_TYPE);
                    $this->push_state(self::STATE_BLOCK);
                    $this->current_var_block_line = $this->lineno;
                }
                break;
            case $this->options['tag_variable'][0]:
                $this->push_token(Token::VAR_START_TYPE);
                $this->push_state(self::STATE_VAR);
                $this->current_var_block_line = $this->lineno;
                break;
        }
    }
    private function lex_block(): void
    {
        if (!$this->brackets && preg_match($this->regexes['lex_block'], (string) $this->code, $match, 0, $this->cursor)) {
            $this->push_token(Token::BLOCK_END_TYPE);
            $this->move_cursor($match[0]);
            $this->pop_state();
        } else {
            $this->lex_expression();
        }
    }
    private function lex_var(): void
    {
        if (!$this->brackets && preg_match($this->regexes['lex_var'], (string) $this->code, $match, 0, $this->cursor)) {
            $this->push_token(Token::VAR_END_TYPE);
            $this->move_cursor($match[0]);
            $this->pop_state();
        } else {
            $this->lex_expression();
        }
    }
    private function lex_expression(): void
    {
        // whitespace
        if (preg_match('/\s+/A', (string) $this->code, $match, 0, $this->cursor)) {
            $this->move_cursor($match[0]);
            if ($this->cursor >= $this->end) {
                throw new Syntax_Error(\sprintf('Unclosed "%s".', self::STATE_BLOCK === $this->state ? 'block' : 'variable'), $this->current_var_block_line, $this->source);
            }
        }
        // operators
        if (preg_match($this->regexes['operator'], (string) $this->code, $match, 0, $this->cursor)) {
            $operator = preg_replace('/\s+/', ' ', $match[0]);
            if (\in_array($operator, $this->opening_brackets, true)) {
                $this->check_brackets($operator);
            }
            $this->push_token(Token::OPERATOR_TYPE, $operator);
            $this->move_cursor($match[0]);
        } elseif (preg_match(self::REGEX_NAME, (string) $this->code, $match, 0, $this->cursor)) {
            $this->push_token(Token::NAME_TYPE, $match[0]);
            $this->move_cursor($match[0]);
        } elseif (preg_match(self::REGEX_NUMBER, (string) $this->code, $match, 0, $this->cursor)) {
            $this->push_token(Token::NUMBER_TYPE, 0 + str_replace('_', '', $match[0]));
            $this->move_cursor($match[0]);
        } elseif (str_contains(self::PUNCTUATION, (string) $this->code[$this->cursor])) {
            $this->check_brackets($this->code[$this->cursor]);
            $this->push_token(Token::PUNCTUATION_TYPE, $this->code[$this->cursor]);
            ++$this->cursor;
        } elseif (preg_match(self::REGEX_STRING, (string) $this->code, $match, 0, $this->cursor)) {
            $this->push_token(Token::STRING_TYPE, $this->stripcslashes(substr($match[0], 1, -1), substr($match[0], 0, 1)));
            $this->move_cursor($match[0]);
        } elseif (preg_match(self::REGEX_DQ_STRING_DELIM, (string) $this->code, $match, 0, $this->cursor)) {
            $this->brackets[] = ['"', $this->lineno];
            $this->push_state(self::STATE_STRING);
            $this->move_cursor($match[0]);
        } elseif (preg_match(self::REGEX_INLINE_COMMENT, (string) $this->code, $match, 0, $this->cursor)) {
            $this->move_cursor($match[0]);
        } else {
            throw new Syntax_Error(\sprintf('Unexpected character "%s".', $this->code[$this->cursor]), $this->lineno, $this->source);
        }
    }
    private function stripcslashes(string $str, string $quote_type): string
    {
        $result = '';
        $length = \strlen($str);
        $i = 0;
        while ($i < $length) {
            if (false === $pos = strpos($str, '\\', $i)) {
                $result .= substr($str, $i);
                break;
            }
            $result .= substr($str, $i, $pos - $i);
            $i = $pos + 1;
            if ($i >= $length) {
                $result .= '\\';
                break;
            }
            $next_char = $str[$i];
            if (isset(self::SPECIAL_CHARS[$next_char])) {
                $result .= self::SPECIAL_CHARS[$next_char];
            } elseif ('\\' === $next_char) {
                $result .= $next_char;
            } elseif ("'" === $next_char || '"' === $next_char) {
                if ($next_char !== $quote_type) {
                    trigger_deprecation('twig/twig', '3.12', 'Character "%s" should not be escaped; the "\" character is ignored in Twig 3 but will not be in Twig 4. Please remove the extra "\" character at position %d in "%s" at line %d.', $next_char, $i + 1, $this->source->get_name(), $this->lineno);
                }
                $result .= $next_char;
            } elseif ('#' === $next_char && $i + 1 < $length && '{' === $str[$i + 1]) {
                $result .= '#{';
                ++$i;
            } elseif ('x' === $next_char && $i + 1 < $length && ctype_xdigit($str[$i + 1])) {
                $hex = $str[++$i];
                if ($i + 1 < $length && ctype_xdigit($str[$i + 1])) {
                    $hex .= $str[++$i];
                }
                $result .= \chr(hexdec($hex));
            } elseif (ctype_digit($next_char) && $next_char < '8') {
                $octal = $next_char;
                while ($i + 1 < $length && ctype_digit($str[$i + 1]) && $str[$i + 1] < '8' && \strlen($octal) < 3) {
                    $octal .= $str[++$i];
                }
                $result .= \chr(octdec($octal));
            } else {
                trigger_deprecation('twig/twig', '3.12', 'Character "%s" should not be escaped; the "\" character is ignored in Twig 3 but will not be in Twig 4. Please remove the extra "\" character at position %d in "%s" at line %d.', $next_char, $i + 1, $this->source->get_name(), $this->lineno);
                $result .= $next_char;
            }
            ++$i;
        }
        return $result;
    }
    private function lex_raw_data(): void
    {
        if (!preg_match($this->regexes['lex_raw_data'], (string) $this->code, $match, \PREG_OFFSET_CAPTURE, $this->cursor)) {
            throw new Syntax_Error('Unexpected end of file: Unclosed "verbatim" block.', $this->lineno, $this->source);
        }
        $text = substr((string) $this->code, $this->cursor, $match[0][1] - $this->cursor);
        $this->move_cursor($text . $match[0][0]);
        // trim?
        if (isset($match[1][0])) {
            if ($this->options['whitespace_trim'] === $match[1][0]) {
                // whitespace_trim detected ({%-, {{- or {#-)
                $text = rtrim($text);
            } else {
                // whitespace_line_trim detected ({%~, {{~ or {#~)
                // don't trim \r and \n
                $text = rtrim($text, " \t\x00\v");
            }
        }
        $this->push_token(Token::TEXT_TYPE, $text);
    }
    private function lex_comment(): void
    {
        if (!preg_match($this->regexes['lex_comment'], (string) $this->code, $match, \PREG_OFFSET_CAPTURE, $this->cursor)) {
            throw new Syntax_Error('Unclosed comment.', $this->lineno, $this->source);
        }
        $this->move_cursor(substr((string) $this->code, $this->cursor, $match[0][1] - $this->cursor) . $match[0][0]);
    }
    private function lex_string(): void
    {
        if (preg_match($this->regexes['interpolation_start'], (string) $this->code, $match, 0, $this->cursor)) {
            $this->brackets[] = [$this->options['interpolation'][0], $this->lineno];
            $this->push_token(Token::INTERPOLATION_START_TYPE);
            $this->move_cursor($match[0]);
            $this->push_state(self::STATE_INTERPOLATION);
        } elseif (preg_match(self::REGEX_DQ_STRING_PART, (string) $this->code, $match, 0, $this->cursor) && '' !== $match[0]) {
            $this->push_token(Token::STRING_TYPE, $this->stripcslashes($match[0], '"'));
            $this->move_cursor($match[0]);
        } elseif (preg_match(self::REGEX_DQ_STRING_DELIM, (string) $this->code, $match, 0, $this->cursor)) {
            [$expect, $lineno] = array_pop($this->brackets);
            if ('"' != $this->code[$this->cursor]) {
                throw new Syntax_Error(\sprintf('Unclosed "%s".', $expect), $lineno, $this->source);
            }
            $this->pop_state();
            ++$this->cursor;
        } else {
            // unlexable
            throw new Syntax_Error(\sprintf('Unexpected character "%s".', $this->code[$this->cursor]), $this->lineno, $this->source);
        }
    }
    private function lex_interpolation(): void
    {
        $bracket = end($this->brackets);
        if ($this->options['interpolation'][0] === $bracket[0] && preg_match($this->regexes['interpolation_end'], (string) $this->code, $match, 0, $this->cursor)) {
            array_pop($this->brackets);
            $this->push_token(Token::INTERPOLATION_END_TYPE);
            $this->move_cursor($match[0]);
            $this->pop_state();
        } else {
            $this->lex_expression();
        }
    }
    private function push_token(int $type, $value = ''): void
    {
        // do not push empty text tokens
        if (Token::TEXT_TYPE === $type && '' === $value) {
            return;
        }
        $this->tokens[] = new Token($type, $value, $this->lineno);
    }
    private function move_cursor(string $text): void
    {
        $this->cursor += \strlen($text);
        $this->lineno += substr_count($text, "\n");
    }
    private function get_operator_regex(): string
    {
        $expression_parsers = [];
        foreach ($this->env->get_expression_parsers() as $expression_parser) {
            $expression_parsers = array_merge($expression_parsers, Expression_Parsers::get_operator_tokens_for($expression_parser));
        }
        $expression_parsers = array_combine($expression_parsers, array_map(strlen(...), $expression_parsers));
        arsort($expression_parsers);
        $regex = [];
        foreach ($expression_parsers as $expression_parser => $length) {
            // an operator that ends with a character must be followed by
            // a whitespace, a parenthesis, an opening map [ or sequence {
            $r = preg_quote($expression_parser, '/');
            if (ctype_alpha($expression_parser[$length - 1])) {
                $r .= '(?=[\s()\[{])';
            }
            // an operator that begins with a character must not have a dot or pipe before
            if (ctype_alpha($expression_parser[0])) {
                $r = '(?<![\.\|]\s|.[\.\|])' . $r;
            }
            // an operator with a space can be any amount of whitespaces
            $r = preg_replace('/\s+/', '\s+', $r);
            $regex[] = $r;
        }
        return '/' . implode('|', $regex) . '/A';
    }
    private function push_state(int $state): void
    {
        $this->states[] = $this->state;
        $this->state = $state;
    }
    private function pop_state(): void
    {
        if (0 === \count($this->states)) {
            throw new \LogicException('Cannot pop state without a previous state.');
        }
        $this->state = array_pop($this->states);
    }
    private function check_brackets(string $code): void
    {
        // opening bracket
        if (\in_array($code, $this->opening_brackets, true)) {
            $this->brackets[] = [$code, $this->lineno];
        } elseif (\in_array($code, $this->closing_brackets, true)) {
            // closing bracket
            if (!$this->brackets) {
                throw new Syntax_Error(\sprintf('Unexpected "%s".', $code), $this->lineno, $this->source);
            }
            [$expect, $lineno] = array_pop($this->brackets);
            if ($code !== str_replace($this->opening_brackets, $this->closing_brackets, $expect)) {
                throw new Syntax_Error(\sprintf('Unclosed "%s".', $expect), $lineno, $this->source);
            }
        }
    }
}