<?php

declare(strict_types=1);

namespace App\Services\TransactionReader\Adapters;

use App\Services\TransactionReader\TransactionReader;
use Generator;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PhpSpreadsheetAdapter implements TransactionReader
{
    public function read(string $path): Generator
    {
        $spreadsheet = IOFactory::load($path);
        $worksheet = $spreadsheet->getActiveSheet()->getRowIterator();

        $headers = [];

        foreach ($worksheet->current()->getCellIterator() as $cell) {
            assert(is_string($header = $cell->getValue()));
            $headers[] = $header;
        }

        $worksheet->next();

        while ($worksheet->valid()) {
            $values = [];

            foreach ($worksheet->current()->getCellIterator() as $cell) {
                $values[] = $cell->getValue();
            }

            // @phpstan-ignore-next-line
            yield array_combine($headers, array_map(fn (?string $value) => $value ?? '', $values));

            $worksheet->next();
        }
    }
}
