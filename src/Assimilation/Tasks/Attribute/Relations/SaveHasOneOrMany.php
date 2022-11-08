<?php

namespace Nihilsen\Seeker\Assimilation\Tasks\Attribute\Relations;

use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\QueryException;
use Nihilsen\Seeker\Assimilation\Tasks\Attribute\SetIterableRelation;

class SaveHasOneOrMany extends SetIterableRelation
{
    public function getForRelation(): ?\Closure
    {
        if ($this->relation instanceof HasOneOrMany) {
            return function () {
                /**
                 * @var \Illuminate\Database\Eloquent\Model
                 */
                foreach ($this->models as $model) {
                    try {
                        $this->relation->save($model);
                    } catch (QueryException $exception) {
                        throw_if(
                            // Ignore duplicate entry errors
                            $exception->getCode() != 23000,
                            $exception
                        );
                    }
                }
            };
        }

        return null;
    }
}
