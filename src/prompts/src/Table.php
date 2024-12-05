<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Prompts;

use Hyperf\Collection\Collection;

class Table extends Prompt
{
    /**
     * The table headers.
     *
     * @var array<int, array<int, string>|string>
     */
    public array $headers;

    /**
     * The table rows.
     *
     * @var array<int, array<int, string>>
     */
    public array $rows;

    /**
     * Create a new Table instance.
     *
     * @param array<int, array<int, string>|string>|Collection<int, array<int, string>|string> $headers
     * @param array<int, array<int, string>>|Collection<int, array<int, string>> $rows
     *
     * @phpstan-param ($rows is null ? list<list<string>>|Collection<int, list<string>> : list<string|list<string>>|Collection<int, string|list<string>>) $headers
     */
    public function __construct(array|Collection $headers = [], null|array|Collection $rows = null)
    {
        if ($rows === null) {
            $rows = $headers;
            $headers = [];
        }

        $this->headers = $headers instanceof Collection ? $headers->all() : $headers;
        $this->rows = $rows instanceof Collection ? $rows->all() : $rows;
    }

    /**
     * Display the table.
     */
    public function display(): void
    {
        $this->prompt();
    }

    /**
     * Display the table.
     */
    public function prompt(): bool
    {
        $this->capturePreviousNewLines();

        $this->state = 'submit';

        static::output()->write($this->renderTheme());

        return true;
    }

    /**
     * Get the value of the prompt.
     */
    public function value(): bool
    {
        return true;
    }
}
