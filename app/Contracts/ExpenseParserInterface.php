<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DataTransferObjects\Actions\ParsedExpenseDto;

interface ExpenseParserInterface
{
    public function parse(string $input, int $userId): ParsedExpenseDto;
}
