<?php

namespace Nihilsen\Seeker\Assimilation\Tasks\Attribute;

use Nihilsen\Seeker\Schema;

class SetAttribute extends AttributeTask
{
    protected static $columnListing;

    public function modelTableColumnExists(): bool
    {
        $table = $this->model->getTable();

        $columnListing = static::$columnListing[$table] ??= Schema::getColumnListing($table);

        return in_array($this->key, $columnListing);
    }

    public function get(): ?\Closure
    {
        if (
            $this->model->hasSetMutator($this->key) ||
            $this->model->hasAttributeSetMutator($this->key) ||
            $this->modelTableColumnExists()
        ) {
            return fn () => $this->model->{$this->key} = $this->value;
        }

        return null;
    }
}
