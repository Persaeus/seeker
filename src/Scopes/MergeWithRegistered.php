<?php

namespace Nihilsen\Seeker\Scopes;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;
use Nihilsen\LaravelJoinUsing\JoinUsingClause;
use Nihilsen\Seeker\Endpoints;
use Nihilsen\Seeker\Schema;

class MergeWithRegistered implements Scope
{
    protected Endpoints $model;

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $query, Model $model)
    {
        $this->model = $model;

        if ($classes = Endpoints::classes()) {
            $query->rightJoinSub(
                $this->getRightJoinQuery($classes),
                'registered_'.Schema::endpointsTable,
                fn (JoinUsingClause $join) => $join->using('class'),
            );
        }
    }

    protected function getRightJoinQuery(array $classes)
    {
        $query = $this->getRowForUnionExpression(array_shift($classes));
        foreach ($classes as $class) {
            $query->unionAll($this->getRowForUnionExpression($class));
        }

        return $query;
    }

    /**
     * For the given class string, get a query object for the corresponding row
     * in a "select ... union select ...) expression.
     *
     * @param  string  $class
     */
    protected function getRowForUnionExpression(string $class)
    {
        $class = $this->model->classToAlias($class);

        $class = str_replace(
            '\\',
            '\\\\',
            $class,
        );

        return DB::query()->select(['class' => fn ($query) => $query->select(DB::raw("'$class'"))]);
    }
}
