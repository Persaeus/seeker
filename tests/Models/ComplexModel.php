<?php

namespace Nihilsen\Seeker\Tests\Models;

use Nihilsen\Seeker\Data;

/**
 * @property string $description
 * @property string $value
 */
class ComplexModel extends TestModel
{
    public static function data(
        ?string $value = null,
        ?string $description = null,
        ?SimpleModel $simpleModel = null
    ): static {
        return Data::trace(firstOrNew: ['value']);
    }

    public function simpleModel()
    {
        return $this->belongsTo(SimpleModel::class);
    }
}
