<?php

namespace Riesjart\Relaquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany as BaseHasMany;
use Riesjart\Relaquent\Traits\HasOneOrManyTrait;

class HasMany extends BaseHasMany
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


    // =======================================================================//
    //          Getters                                                                        
    // =======================================================================//
    
    /**
     * @return string
     */
    public function getPlainParentKey()
    {
        return $this->localKey;
    }


    // =======================================================================//
    //          Converters                                                                        
    // =======================================================================//
    
    /**
     * @return HasOne
     */
    public function toHasOne()
    {
        return new HasOne($this->related->newQuery(), $this->parent, $this->related->getTable() . '.' . $this->foreignKey, $this->localKey);
    }
}