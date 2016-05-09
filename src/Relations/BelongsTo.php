<?php

namespace Riesjart\Relaquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo as BaseBelongsTo;

class BelongsTo extends BaseBelongsTo
{
    // =======================================================================//
    //          Join                                                                        
    // =======================================================================//

    /**
     * @param Builder $q
     * @param string|null $alias
     * @param string $type
     * @param bool $where
     *
     * @return int
     */
    public function addAsJoin(Builder $q, $alias = null, $type = 'inner', $where = false)
    {
        $relatedTable = $this->getRelated()->getTable();

        $alias = $alias ?: $relatedTable;

        $table = $relatedTable . ' as ' . $alias;
        $one = $this->getTable() . '.' . $this->getForeignKey();
        $two = $alias . '.' . $this->getOtherKey();

        $q->join($table, $one, '=', $two, $type, $where);

        return 1;
    }
}