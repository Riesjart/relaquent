<?php

namespace Riesjart\Relaquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough as BaseHasManyThrough;

class HasManyThrough extends BaseHasManyThrough
{
    // =======================================================================//
    //          Getters                                                                        
    // =======================================================================//
    
    /**
     * @return Model
     */
    public function getFarParent()
    {
        return $this->farParent;
    }
    
    
    /**
     * @return string
     */
    public function getPlainForeignKey()
    {
        return $this->secondKey;
    }


    /**
     * @return string
     */
    public function getPlainLocalKey()
    {
        return $this->localKey;
    }


    /**
     * @return string
     */
    public function getPlainThroughKey()
    {
        return $this->firstKey;
    }


    // =======================================================================//
    //          Converters                                                                  
    // =======================================================================//

    /**
     * TODO: 4th param needed
     */
//    public function toHasOneThrough()
//    {
//        return new HasOneThrough($this->related->newQuery(), $this->farParent, $this->parent, $this->firstKey, $this->secondKey, $this->localKey);
//    }


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
        $related = $this->getRelated();
        $relatedTable = $related->getTable();

        $alias = $alias ?: $relatedTable;
        $pivotAlias = $alias . '_pivot';

        $table = $this->getParent()->getTable() . ' as ' . $pivotAlias;

        $one = $this->getTable() . '.' . $this->getPlainLocalKey();
        $two = $pivotAlias . '.' . $this->getPlainThroughKey();

        $q->join($table, $one, '=', $two, $type, $where);

        $table = $relatedTable . ' as ' . $alias;
        $one = $pivotAlias . '.' . $this->getParent()->getKeyName();
        $two = $alias . '.' . $this->getPlainForeignKey();

        $q->join($table, $one, '=', $two, $type, $where);
        
        return 2;
    }
}