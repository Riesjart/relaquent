<?php

namespace Riesjart\Relaquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany as BaseMorphToMany;

class MorphToMany extends BaseMorphToMany
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
    public function getPivotClass()
    {
        return '\App\Models\\' . studly_case(str_singular($this->table));
    }


    // =======================================================================//
    //          Converters                                                                  
    // =======================================================================//

    /**
     * @param string|null $pivotClass
     *
     * @return MorphMany
     */
    public function toMorphMany($pivotClass = null)
    {
        $pivotClass = $pivotClass ?: $this->getPivotClass();
        $pivot = new $pivotClass;

        return new MorphMany($pivot->newQuery(), $this->parent,
            $pivot->getTable() . '.' . $this->morphType, $pivot->getTable() . '.' . $this->foreignKey, $this->parent->getKeyName());
    }
}