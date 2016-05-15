<?php

namespace Riesjart\Relaquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as BaseBelongsToMany;

class BelongsToMany extends BaseBelongsToMany
{
    /**
     * @param array $attributes
     *
     * @return Model
     */
    public function newInstance(array $attributes = [])
    {
        $instance = $this->related->newInstance($attributes);

        return $instance;
    }
    
    
    // =======================================================================//
    //          Getters                                                                        
    // =======================================================================//
    
    /**
     * @return string
     */
    public function getPlainForeignKey()
    {
        return $this->foreignKey;
    }


    /**
     * @return string
     */
    public function getPlainOtherKey()
    {
        return $this->otherKey;
    }


    /**
     * @return string
     */
    public function getPivotClass()
    {
        return '\App\Models\\' . studly_case($this->table);
    }


    // =======================================================================//
    //          Converters                                                                  
    // =======================================================================//

    /**
     * @param string $pivotClass
     *
     * @return HasMany
     */
    public function toHasMany($pivotClass = null)
    {
        $pivotClass = $pivotClass ?: $this->getPivotClass();
        $pivot = new $pivotClass;

        return new HasMany($pivot->newQuery(), $this->parent, $pivot->getTable() . '.' . $this->foreignKey, $this->parent->getKeyName());
    }

    
    /**
     * @param string $pivotClass
     *
     * @return HasOneThrough
     */
    public function toHasOneThrough($pivotClass = null)
    {
        $throughClass = $pivotClass ?: $this->getPivotClass();
        $through = new $throughClass;

        return new HasOneThrough($this->related->newQuery(), $this->parent, $through, $this->foreignKey, $this->otherKey, $this->parent->getKeyName());
    }
    
    
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

        $table = $this->getTable() . ' as ' . $pivotAlias;
        $one = $this->getQualifiedParentKeyName();
        $two = $pivotAlias . '.' . $this->getPlainForeignKey();

        $q->join($table, $one, '=', $two, $type, $where);

        $table = $relatedTable . ' as ' . $alias;
        $one = $pivotAlias . '.' . $this->getPlainOtherKey();
        $two = $alias . '.' . $related->getKeyName();

        $q->join($table, $one, '=', $two, $type, $where);
        
        return 2;
    }
}