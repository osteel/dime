<?php

declare(strict_types=1);

namespace Domain\Projections;

use Domain\Enums\FiatCurrency;
use Domain\ValueObjects\FiatAmount;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * @property FiatCurrency $currency
 * @property FiatAmount   $fiat_balance
 *
 * @method static self|null first()
 * @method static self      firstOrNew($attributes = [], $values = [])
 */
final class Summary extends Model
{
    /** Indicates if the model should be timestamped. */
    public $timestamps = false;

    /** Indicates if all mass assignment is enabled. */
    protected static $unguarded = true;

    protected function currency(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => FiatCurrency::from($value),
            set: fn (FiatCurrency $value) => $value->value,
        );
    }

    protected function fiatBalance(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => new FiatAmount($value ?? '0', $this->currency),
            set: fn (FiatAmount $value) => (string) $value->quantity,
        );
    }
}
