<?php

declare(strict_types=1);

namespace Othyn\FilamentApiResources\Livewire\Synthesizers;

use Livewire\Mechanisms\HandleComponents\Synthesizers\Synth;
use Othyn\FilamentApiResources\Models\BaseApiModel;

class ApiModelSynthesizer extends Synth
{
    public static $key = 'apimodel';

    public static function match($target)
    {
        return $target instanceof BaseApiModel;
    }

    public function dehydrate($target)
    {
        return [
            [
                'class' => get_class($target),
                'id' => $target->getKey(),
                'data' => $target->getAttributes(),
            ],
            [],
        ];
    }

    public function hydrate($value)
    {
        if (! is_array($value) || ! isset($value['class'])) {
            throw new \InvalidArgumentException('Invalid synthesizer data format');
        }

        $class = $value['class'];
        $model = new $class();
        $model->fill($value['data'] ?? []);
        $model->exists = true;

        return $model;
    }

    public function get(&$target, $key)
    {
        return $target->{$key};
    }

    public function set(&$target, $key, $value)
    {
        $target->{$key} = $value;
    }
}
