<?php

namespace Riesjart\Relaquent\Relations;

use Illuminate\Database\Eloquent\Relations\MorphTo as BaseMorphTo;

class MorphTo extends BaseMorphTo
{
    // =======================================================================//
    //          Getters
    // =======================================================================//

    /**
     * @return mixed
     */
    public function getForeignValue()
    {
        return $this->parent->{$this->foreignKey};
    }


    /**
     * @return mixed
     */
    public function getMorphTypeValue()
    {
        return $this->parent->{$this->morphType};
    }


    // =======================================================================//
    //          Flags
    // =======================================================================//

    /**
     * @return bool
     */
    public function isNull()
    {
        return is_null($this->getForeignValue()) || is_null($this->getMorphTypeValue());
    }


    /**
     * @return bool
     */
    public function notNull()
    {
        return ! $this->isNull();
    }


    // =======================================================================//
    //          Converters
    // =======================================================================//

    /**
     * @param string|null $morphTypeColumn
     *
     * @return HasMany
     */
    public function toSelfReferring($morphTypeColumn = null)
    {
        $morphTypeColumn = $morphTypeColumn ?: $this->morphType;

        return new HasMany($this->parent->newQuery(), $this->parent, $morphTypeColumn, $this->morphType);
    }
}