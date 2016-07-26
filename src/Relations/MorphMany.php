<?php

namespace Riesjart\Relaquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany as BaseMorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class MorphMany extends BaseMorphMany
{
    /**
     * @param array $attributes
     *
     * @return Model
     */
    public function newInstance(array $attributes = [])
    {
        $instance = $this->related->newInstance($attributes);

        // When saving a polymorphic relationship, we need to set not only the foreign
        // key, but also the foreign key type, which is typically the class name of
        // the parent model. This makes the polymorphic item unique in the table.
        $this->setForeignAttributesForCreate($instance);

        return $instance;
    }


    // =======================================================================//
    //          Converters                                                                        
    // =======================================================================//
    
    /**
     * @return MorphOne
     */
    public function toMorphOne()
    {
        return new MorphOne($this->related->newQuery(), $this->parent, $this->morphType, $this->foreignKey, $this->localKey);
    }
}