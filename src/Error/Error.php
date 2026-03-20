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
namespace Twig\Error;

use Twig\Source;
use Twig\Template;
/**
 * Twig base exception.
 *
 * This exception class and its children must only be used when
 * an error occurs during the loading of a template, when a syntax error
 * is detected in a template, or when rendering a template. Other
 * errors must use regular PHP exception classes (like when the template
 * cache directory is not writable for instance).
 *
 * To help debugging template issues, this class tracks the original template
 * name and line where the error occurred.
 *
 * Whenever possible, you must set these information (original template name
 * and line number) yourself by passing them to the constructor. If some or all
 * these information are not available from where you throw the exception, then
 * this class will guess them automatically.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Error extends \Exception
{
    private readonly string $php_file;
    private readonly int $php_line;
    /**
     * Constructor.
     *
     * By default, automatic guessing is enabled.
     *
     * @param string $rawMessage The error message
     * @param int         $lineno  The template line where the error occurred
     * @param Source|null $source  The source context where the error occurred
     */
    public function __construct(private string $raw_message, private int $lineno = -1, private ?Source $source = null, ?\Throwable $previous = null)
    {
        parent::__construct('', 0, $previous);
        $this->php_file = $this->get_file();
        $this->php_line = $this->get_line();
        $this->update_repr();
    }
    public function get_raw_message(): string
    {
        return $this->raw_message;
    }
    public function get_template_line(): int
    {
        return $this->lineno;
    }
    public function set_template_line(int $lineno): void
    {
        $this->lineno = $lineno;
        $this->update_repr();
    }
    public function get_source_context(): ?Source
    {
        return $this->source;
    }
    public function set_source_context(?Source $source = null): void
    {
        $this->source = $source;
        $this->update_repr();
    }
    public function guess(): void
    {
        if ($this->lineno > -1) {
            return;
        }
        $this->guess_template_info();
        $this->update_repr();
    }
    public function append_message(string $raw_message): void
    {
        $this->raw_message .= $raw_message;
        $this->update_repr();
    }
    private function update_repr(): void
    {
        if ($this->source && $this->source->get_path()) {
            // we only update the file and the line together
            $this->file = $this->source->get_path();
            if ($this->lineno > 0) {
                $this->line = $this->lineno;
            } else {
                $this->line = -1;
            }
        }
        $this->message = $this->raw_message;
        $last = substr($this->message, -1);
        if ($punctuation = '.' === $last || '?' === $last ? $last : '') {
            $this->message = substr($this->message, 0, -1);
        }
        if ($this->source && $this->source->get_name()) {
            $this->message .= \sprintf(' in "%s"', $this->source->get_name());
        }
        if ($this->lineno > 0) {
            $this->message .= \sprintf(' at line %d', $this->lineno);
        }
        if ($punctuation) {
            $this->message .= $punctuation;
        }
    }
    private function guess_template_info(): void
    {
        // $this->source is never null here (see guess() usage in Template)
        $this->lineno = 0;
        $template = null;
        $backtrace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS | \DEBUG_BACKTRACE_PROVIDE_OBJECT);
        foreach ($backtrace as $trace) {
            if (isset($trace['object']) && $trace['object'] instanceof Template && $this->source->get_name() === $trace['object']->get_template_name()) {
                $template = $trace['object'];
                break;
            }
        }
        if (null === $template) {
            return;
            // Impossible to guess the info as the template was not found in the backtrace
        }
        $r = new \Reflection_Object($template);
        $file = $r->get_file_name();
        $exceptions = [$e = $this];
        while ($e = $e->get_previous()) {
            $exceptions[] = $e;
        }
        while ($e = array_pop($exceptions)) {
            $traces = $e->get_trace();
            array_unshift($traces, ['file' => $e instanceof self ? $e->php_file : $e->get_file(), 'line' => $e instanceof self ? $e->php_line : $e->get_line()]);
            while ($trace = array_shift($traces)) {
                if (!isset($trace['file'])) {
                    continue;
                }
                if (!isset($trace['line'])) {
                    continue;
                }
                if ($file != $trace['file']) {
                    continue;
                }
                foreach ($template->get_debug_info() as $code_line => $template_line) {
                    if ($code_line <= $trace['line']) {
                        // update template line
                        $this->lineno = $template_line;
                        return;
                    }
                }
            }
        }
    }
}