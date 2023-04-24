<?php

declare(strict_types=1);

namespace App\Services\TransactionReader\Adapters;

use App\Services\TransactionReader\Exceptions\TransactionReaderException;
use App\Services\TransactionReader\TransactionReader;
use Generator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PhpSpreadsheetAdapter implements TransactionReader
{
    /**
     * @return Generator<array{date:string,operation:string,market_value:string,sent_asset:string,sent_quantity:string,sent_asset_is_non_fungible:string,received_asset:string,received_quantity:string,received_asset_is_non_fungible:string,fee_currency:string,fee_quantity:string,fee_market_value:string,income:string}>
     *
     * @throws TransactionReaderException
     */
    public function read(string $path): Generator
    {
        $stringValueBinder = (new StringValueBinder())->setNumericConversion(false)
            ->setBooleanConversion(false)
            ->setNullConversion(false)
            ->setFormulaConversion(false);

        Cell::setValueBinder($stringValueBinder);

        $spreadsheet = IOFactory::load($path);
        $worksheet = $spreadsheet->getActiveSheet()->getRowIterator();

        $headers = [];

        foreach ($worksheet->current()->getCellIterator() as $cell) {
            assert(is_string($header = $cell->getValue()));
            $headers[] = str_replace('-', '_', Str::snake($header));
        }

        ($missing = array_diff(self::REQUIRED_HEADERS, $headers)) === [] || throw TransactionReaderException::missingHeaders($missing);

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
