<?php

namespace Riesjart\Relaquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne as BaseHasOne;
use Riesjart\Relaquent\Traits\HasOneOrManyTrait;

class HasOne extends BaseHasOne
{
    use HasOneOrManyTrait;

    
    /**
     * @param array $attributes
     *
     * @return Model
     */
    public function newInstance(array $attributes = [])
    {
        $instance = $this->related->newInstance($attributes);

        $instance->setAttribute($this->getPlainForeignKey(), $this->getParentKey());

        return $instance;
    }
}