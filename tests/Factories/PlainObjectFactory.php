<?php

namespace Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of object
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory
 */
abstract class PlainObjectFactory extends Factory
{
    /**
     * {@inheritDoc}
     *
     * @param  array<string, mixed>  $attributes
     * @return object|TModel
     */
    public function newModel(array $attributes = [])
    {
        $model = $this->modelName();

        return new $model(...$attributes);
    }

    protected function getLatest(string $attribute): mixed
    {
        $state = $this->states->last(function (callable $state) use ($attribute) {
            return isset($state()[$attribute]);
        });

        return $state ? $state()[$attribute] : null;
    }
}
