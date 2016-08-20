<?php

namespace Riesjart\Relaquent\Traits;

use Illuminate\Support\Str;
use Riesjart\Relaquent\Relations\BelongsTo;
use Riesjart\Relaquent\Relations\BelongsToMany;
use Riesjart\Relaquent\Relations\HasMany;
use Riesjart\Relaquent\Relations\HasManyThrough;
use Riesjart\Relaquent\Relations\HasOne;
use Riesjart\Relaquent\Relations\HasOneThrough;
use Riesjart\Relaquent\Relations\MorphMany;
use Riesjart\Relaquent\Relations\MorphTo;
use Riesjart\Relaquent\Relations\MorphToMany;

trait RelationsTrait
{
    // =======================================================================//
    //          Defining relations                                                                        
    // =======================================================================//

    /**
     * Define an inverse one-to-one or many relationship.
     * 
     * @param $related
     * @param string|null $foreignKey
     * @param string|null $otherKey
     * @param string|null $relation
     *
     * @return BelongsTo
     */
    public function belongsTo($related, $foreignKey = null, $otherKey = null, $relation = null)
    {
        // If no relation name was given, we will use this debug backtrace to extract
        // the calling method's name and use that as the relationship name as most
        // of the time this will be what we desire to use for the relationships.
        if (is_null($relation)) {
            
            list($current, $caller) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

            $relation = $caller['function'];
        }

        // If no foreign key was supplied, we can use a backtrace to guess the proper
        // foreign key name by using the name of the relationship function, which
        // when combined with an "_id" should conventionally match the columns.
        if (is_null($foreignKey)) {
            
            $foreignKey = Str::snake($relation).'_id';
        }

        $instance = new $related;

        // Once we have the foreign key names, we'll just create a new Eloquent query
        // for the related models and returns the relationship instance which will
        // actually be responsible for retrieving and hydrating every relations.
        $query = $instance->newQuery();

        $otherKey = $otherKey ?: $instance->getKeyName();

        return new BelongsTo($query, $this, $foreignKey, $otherKey, $relation);
    }



    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
     *
     * @param string $name
     * @param string $type
     * @param string $id
     *
     * @return MorphTo
     */
    public function morphTo($name = null, $type = null, $id = null)
    {
        // If no name is provided, we will use the backtrace to get the function name
        // since that is most likely the name of the polymorphic interface. We can
        // use that to get both the class and foreign key that will be utilized.
        if (is_null($name)) {

            list($current, $caller) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

            $name = Str::snake($caller['function']);
        }

        list($type, $id) = $this->getMorphs($name, $type, $id);

        // If the type value is null it is probably safe to assume we're eager loading
        // the relationship. In this case we'll just pass in a dummy query where we
        // need to remove any eager loads that may already be defined on a model.
        if (empty($class = $this->$type)) {

            return new MorphTo(
                $this->newQuery()->setEagerLoads([]), $this, $id, null, $type, $name
            );
        }

        // If we are not eager loading the relationship we will essentially treat this
        // as a belongs-to style relationship since morph-to extends that class and
        // we will pass in the appropriate values so that it behaves as expected.
        else {

            $class = $this->getActualClassNameForMorph($class);

            $instance = new $class;

            return new MorphTo(
                $instance->newQuery(), $this, $id, $instance->getKeyName(), $type, $name
            );
        }
    }
    
    
    /**
     * Define a many-to-many relationship.
     *
     * @param string $related
     * @param string|null $table
     * @param string|null $foreignKey
     * @param string|null $otherKey
     * @param string|null $relation
     * 
     * @return BelongsToMany
     */
    public function belongsToMany($related, $table = null, $foreignKey = null, $otherKey = null, $relation = null)
    {
        // If no relationship name was passed, we will pull backtraces to get the
        // name of the calling function. We will use that function name as the
        // title of this relation since that is a great convention to apply.
        if (is_null($relation)) {

            $relation = $this->getBelongsToManyCaller();
        }

        // First, we'll need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we'll make the query
        // instances as well as the relationship instances we need for this.
        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $instance = new $related;

        $otherKey = $otherKey ?: $instance->getForeignKey();

        // If no table name was provided, we can guess it by concatenating the two
        // models using underscores in alphabetical order. The two model names
        // are transformed to snake case from their default CamelCase also.
        if (is_null($table)) {

            $table = $this->joiningTable($related);
        }

        // Now we're ready to create a new query builder for the related model and
        // the relationship instances for the relation. The relations will set
        // appropriate query constraint and entirely manages the hydrations.
        $query = $instance->newQuery();

        return new BelongsToMany($query, $this, $table, $foreignKey, $otherKey, $relation);
    }


    /**
     * Define a one-to-many relationship.
     *
     * @param $related
     * @param string|null $foreignKey
     * @param string|null $localKey
     *
     * @return HasMany
     */
    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $instance = new $related;

        $localKey = $localKey ?: $this->getKeyName();

        return new HasMany($instance->newQuery(), $this, $instance->getTable() . '.' . $foreignKey, $localKey);
    }
    

    /**
     * Define a has-many-through relationship.
     * 
     * @param $related
     * @param $through
     * @param string|null $firstKey
     * @param string|null $secondKey
     * @param string|null $localKey
     * 
     * @return HasManyThrough
     */
    public function hasManyThrough($related, $through, $firstKey = null, $secondKey = null, $localKey = null)
    {
        $through = new $through;

        $firstKey = $firstKey ?: $this->getForeignKey();

        $secondKey = $secondKey ?: $through->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return new HasManyThrough((new $related)->newQuery(), $this, $through, $firstKey, $secondKey, $localKey);
    }


    /**
     * Define a one-to-one relationship.
     *
     * @param $related
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @return HasOne
     */
    public function hasOne($related, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $instance = new $related;

        $localKey = $localKey ?: $this->getKeyName();

        return new HasOne($instance->newQuery(), $this, $instance->getTable() . '.' . $foreignKey, $localKey);
    }
    

    /**
     * Define a has-one-through relationship.
     *
     * @param string $related
     * @param string $through
     * @param string|null $firstKey
     * @param string|null $secondKey
     * @param string|null $localKey
     *
     * @return HasOneThrough
     */
    public function hasOneThrough($related, $through, $firstKey = null, $secondKey = null, $localKey = null)
    {
        $related = new $related;
        $through = new $through;

        $firstKey = $firstKey ?: $this->getForeignKey();
        $secondKey = $secondKey ?: $related->getForeignKey();
        $localKey = $localKey ?: $this->getKeyName();

        return new HasOneThrough($related->newQuery(), $this, $through, $firstKey, $secondKey, $localKey);
    }


    /**
     * Define a polymorphic one-to-many relationship.
     *
     * @param string $related
     * @param string $name
     * @param string|null $type
     * @param string|null $id
     * @param string|null $localKey
     *
     * @return MorphMany
     */
    public function morphMany($related, $name, $type = null, $id = null, $localKey = null)
    {
        $instance = new $related;

        // Here we will gather up the morph type and ID for the relationship so that we
        // can properly query the intermediate table of a relation. Finally, we will
        // get the table and create the relationship instances for the developers.
        list($type, $id) = $this->getMorphs($name, $type, $id);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        return new MorphMany($instance->newQuery(), $this, $table . '.' . $type, $table . '.' . $id, $localKey);
    }


    /**
     * Define a polymorphic many-to-many relationship.
     *
     * @param string $related
     * @param string $name
     * @param string|null $table
     * @param string|null $foreignKey
     * @param string|null $otherKey
     * @param bool $inverse
     *
     * @return MorphToMany
     */
    public function morphToMany($related, $name, $table = null, $foreignKey = null, $otherKey = null, $inverse = false)
    {
        $caller = $this->getBelongsToManyCaller();

        // First, we will need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we will make the query
        // instances, as well as the relationship instances we need for these.
        $foreignKey = $foreignKey ?: $name.'_id';

        $instance = new $related;

        $otherKey = $otherKey ?: $instance->getForeignKey();

        // Now we're ready to create a new query builder for this related model and
        // the relationship instances for this relation. This relations will set
        // appropriate query constraints then entirely manages the hydrations.
        $query = $instance->newQuery();

        $table = $table ?: Str::plural($name);

        return new MorphToMany(
            $query, $this, $name, $table, $foreignKey,
            $otherKey, $caller, $inverse
        );
    }
}