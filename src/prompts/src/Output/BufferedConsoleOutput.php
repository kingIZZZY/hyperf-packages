<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Prompts\Output;

use const PHP_EOL;

class BufferedConsoleOutput extends ConsoleOutput
{
    /**
     * The output buffer.
     */
    protected string $buffer = '';

    /**
     * Empties the buffer and returns its content.
     */
    public function fetch(): string
    {
        $content = $this->buffer;
        $this->buffer = '';

        return $content;
    }

    /**
     * Return the content of the buffer.
     */
    public function content(): string
    {
        return $this->buffer;
    }

    /**
     * Write to the output buffer.
     */
    protected function doWrite(string $message, bool $newline): void
    {
        $this->buffer .= $message;

        if ($newline) {
            $this->buffer .= PHP_EOL;
        }
    }

    /**
     * Write output directly, bypassing newline capture.
     */
    public function writeDirectly(string $message): void
    {
        $this->doWrite($message, false);
    }
}