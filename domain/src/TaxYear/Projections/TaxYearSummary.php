<?php

declare(strict_types=1);

namespace Domain\TaxYear\Projections;

use Domain\Enums\FiatCurrency;
use Domain\TaxYear\TaxYearId;
use Domain\ValueObjects\Exceptions\FiatAmountException;
use Domain\ValueObjects\FiatAmount;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * @property TaxYearId $tax_year_id
 * @property string $tax_year
 * @property FiatCurrency $currency
 * @property FiatAmount $capital_gain
 * @property FiatAmount $income
 * @property FiatAmount $non_attributable_allowable_costs
 * @method static self firstOrNew($attributes = [], $values = [])
 */
final class TaxYearSummary extends Model
{
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

    /** @throws FiatAmountException */
    public function increaseCapitalGain(FiatAmount $amount): self
    {
        $this->capital_gain = $this->capital_gain->plus($amount);

        return $this;
    }

    /** @throws FiatAmountException */
    public function decreaseCapitalGain(FiatAmount $amount): self
    {
        $this->capital_gain = $this->capital_gain->minus($amount);

        return $this;
    }

    /** @throws FiatAmountException */
    public function increaseIncome(FiatAmount $amount): self
    {
        $this->income = $this->income->plus($amount);

        return $this;
    }

    /** @throws FiatAmountException */
    public function increaseNonAttributableAllowableCosts(FiatAmount $amount): self
    {
        $this->non_attributable_allowable_costs = $this->non_attributable_allowable_costs->plus($amount);

        return $this;
    }

    protected function taxYearId(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => TaxYearId::fromString($value),
            set: fn ($value) => $value instanceof TaxYearId ? $value->toString() : $value,
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
            get: fn ($value) => new FiatAmount($value ?? '0', $this->currency),
            set: fn (FiatAmount $value) => $value->amount,
        );
    }

    protected function income(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => new FiatAmount($value ?? '0', $this->currency),
            set: fn (FiatAmount $value) => $value->amount,
        );
    }

    protected function nonAttributableAllowableCosts(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => new FiatAmount($value ?? '0', $this->currency),
            set: fn (FiatAmount $value) => $value->amount,
        );
    }
}
