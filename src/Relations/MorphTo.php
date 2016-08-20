<?php

namespace Riesjart\Relaquent\Relations;

use Illuminate\Database\Eloquent\Relations\MorphTo as BaseMorphTo;

class MorphTo extends BaseMorphTo
{
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