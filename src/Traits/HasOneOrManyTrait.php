<?php

namespace Riesjart\Relaquent\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasOneOrManyTrait
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
        $one = $this->getQualifiedParentKeyName();
        $two = $alias . '.' . $this->getPlainForeignKey();

        $q->join($table, $one, '=', $two, $type, $where);
        
        return 1;
    }
}