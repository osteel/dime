<?php

declare(strict_types=1);

namespace Domain\Aggregates\TaxYear\Projections;

use Domain\Enums\FiatCurrency;
use Domain\Aggregates\TaxYear\ValueObjects\TaxYearId;
use Domain\Aggregates\TaxYear\ValueObjects\CapitalGain;
use Domain\Tests\Aggregates\TaxYear\Factories\Projections\TaxYearSummaryFactory;
use Domain\ValueObjects\FiatAmount;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CapitalGain  $capital_gain
 * @property FiatCurrency $currency
 * @property FiatAmount   $income
 * @property FiatAmount   $non_attributable_allowable_cost
 * @property TaxYearId    $tax_year_id
 *
 * @method static self firstOrNew($attributes = [], $values = [])
 */
final class TaxYearSummary extends Model
{
    use HasFactory;

    /** The primary key for the model. */
    protected $primaryKey = 'tax_year_id';

    /** The "type" of the primary key ID. */
    protected $keyType = 'string';

    /** Indicates if the IDs are auto-incrementing. */
    public $incrementing = false;

    /** Indicates if the model should be timestamped. */
    public $timestamps = false;

    /** Indicates if all mass assignment is enabled. */
    protected static $unguarded = true;

    protected static function newFactory(): TaxYearSummaryFactory
    {
        return TaxYearSummaryFactory::new();
    }

    protected function taxYearId(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => TaxYearId::fromString($value),
            set: fn (TaxYearId|string $value) => $value instanceof TaxYearId ? $value->toString() : $value,
        );
    }

    protected function currency(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => FiatCurrency::from($value),
            set: fn (FiatCurrency $value) => $value->value,
        );
    }

    protected function capitalGain(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                $values = is_null($value) ? [] : json_decode($value, true);

                assert(is_array($values));

                return new CapitalGain(
                    costBasis: new FiatAmount($values['cost_basis'] ?? '0', $this->currency),
                    proceeds: new FiatAmount($values['proceeds'] ?? '0', $this->currency),
                );
            },
            set: fn (CapitalGain $value) => json_encode($value),
        );
    }

    protected function income(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => new FiatAmount($value ?? '0', $this->currency),
            set: fn (FiatAmount $value) => (string) $value->quantity,
        );
    }

    protected function nonAttributableAllowableCost(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => new FiatAmount($value ?? '0', $this->currency),
            set: fn (FiatAmount $value) => (string) $value->quantity,
        );
    }
}
