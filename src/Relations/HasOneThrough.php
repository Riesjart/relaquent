<?php

namespace Riesjart\Relaquent\Relations;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Expression;

/**
 * TODO: load pivot in results
 */
class HasOneThrough extends Relation
{
    /**
     * The distance parent model instance.
     *
     * @var Model
     */
    protected $farParent;
    
    /**
     * The near key on the relationship.
     *
     * @var string
     */
    protected $firstKey;

    /**
     * Alias for the near key on the relationship.
     * 
     * @var string
     */
    protected $firstKeyAlias = '__related_through_key';

    /**
     * The local key on the relationship.
     *
     * @var string
     */
    protected $localKey;
    
    /**
     * The far key on the relationship.
     *
     * @var string
     */
    protected $secondKey;


    /**
     * Build model dictionary keyed by the relation's foreign key.
     *
     * @param Collection $results
     *
     * @return array
     */
    protected function buildDictionary(Collection $results)
    {
        $dictionary = [];

        // First we will create a dictionary of models keyed by the foreign key of the
        // relationship as this will allow us to quickly access all of the related
        // models without having to do nested looping which will be quite slow.
        foreach ($results as $result) {

            $dictionary[$result->{$this->firstKeyAlias}][] = $result;
            $result->offsetUnset($this->firstKeyAlias);
        }

        return $dictionary;
    }


    /**
     * @inheritdoc
     */
    public function getRelationCountQuery(Builder $query, Builder $parent)
    {
        $parentTable = $this->parent->getTable();

        $this->setJoin($query);

        $query->select(new Expression('count(*)'));

        $key = $this->wrap($parentTable . '.' . $this->firstKey);

        return $query->where($this->getHasCompareKey(), '=', new Expression($key));
    }


    /**
     * Set the join clause on the query.
     *
     * @param Builder|null $query
     */
    protected function setJoin(Builder $query = null)
    {
        $query = $query ?: $this->query;

        $foreignKey = $this->related->getTable() . '.' . $this->related->getKeyName();
        $localKey = $this->parent->getTable() . '.' . $this->secondKey;

        $query->join($this->parent->getTable(), $foreignKey, '=', $localKey);

        if ($this->parentSoftDeletes()) {

            $query->whereNull($this->parent->getQualifiedDeletedAtColumn());
        }
    }


    /**
     * Determine whether close parent of the relation uses Soft Deletes.
     *
     * @return bool
     */
    public function parentSoftDeletes()
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(get_class($this->parent)));
    }


    /**
     * Execute the query and get the first related model.
     *
     * @param array $columns
     *
     * @return mixed
     */
    public function first($columns = ['*'])
    {
        $results = $this->take(1)->get($columns);

        return count($results) > 0 ? $results->first() : null;
    }


    /**
     * Execute the query and get the first result or throw an exception.
     *
     * @param array $columns
     *
     * @return Model|static
     *
     * @throws ModelNotFoundException
     */
    public function firstOrFail($columns = ['*'])
    {
        if (! is_null($model = $this->first($columns))) {

            return $model;
        }

        throw new ModelNotFoundException;
    }


    /**
     * Find a related model by its primary key.
     *
     * @param mixed $id
     * @param array $columns
     * @return Model|Collection|null
     */
    public function find($id, $columns = ['*'])
    {
        if (is_array($id)) {

            return $this->findMany($id, $columns);
        }

        $this->where($this->getRelated()->getQualifiedKeyName(), '=', $id);

        return $this->first($columns);
    }


    /**
     * Find multiple related models by their primary keys.
     *
     * @param mixed $ids
     * @param array $columns
     * @return Collection
     */
    public function findMany($ids, $columns = ['*'])
    {
        if (empty($ids)) {

            return $this->getRelated()->newCollection();
        }

        $this->whereIn($this->getRelated()->getQualifiedKeyName(), $ids);

        return $this->get($columns);
    }


    /**
     * Find a related model by its primary key or throw an exception.
     *
     * @param $id
     * @param array $columns
     * @return Collection|Model
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail($id, $columns = ['*'])
    {
        $result = $this->find($id, $columns);

        if (is_array($id)) {

            if (count($result) == count(array_unique($id))) {

                return $result;
            }

        } elseif (! is_null($result)) {

            return $result;
        }

        throw (new ModelNotFoundException)->setModel(get_class($this->parent));
    }


    /**
     * Execute the query as a "select" statement.
     *
     * @param array $columns
     *
     * @return Collection
     */
    public function get($columns = ['*'])
    {
        // First we'll add the proper select columns onto the query so it is run with
        // the proper columns. Then, we will get the results and hydrate out pivot
        // models with the result of those columns as a separate model relation.
        $columns = $this->query->getQuery()->columns ? [] : $columns;

        $select = $this->getSelectColumns($columns);

        $models = $this->query->addSelect($select)->getModels();

        // If we actually found models we will also eager load any relationships that
        // have been specified as needing to be eager loaded. This will solve the
        // n + 1 query problem for the developer and also increase performance.
        if (count($models) > 0) {

            $models = $this->query->eagerLoadRelations($models);
        }

        return $this->related->newCollection($models);
    }


    /**
     * Get a paginator for the "select" statement.
     *
     * @param int|null $perPage
     * @param array $columns
     * @param string $pageName
     *
     * @return LengthAwarePaginator
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page')
    {
        $this->query->addSelect($this->getSelectColumns($columns));
        return $this->query->paginate($perPage, $columns, $pageName);
    }


    /**
     * Paginate the given query into a simple paginator.
     *
     * @param int|null $perPage
     * @param array $columns
     * @return Paginator
     */
    public function simplePaginate($perPage = null, $columns = ['*'])
    {
        $this->query->addSelect($this->getSelectColumns($columns));

        return $this->query->simplePaginate($perPage, $columns);
    }


    // =======================================================================//
    //          Magic                                                                        
    // =======================================================================//

    /**
     * Create a new has one through relationship instance.
     *
     * @param Builder $query
     * @param Model $farParent
     * @param Model $parent
     * @param string $firstKey
     * @param string $secondKey
     * @param string $localKey
     */
    public function __construct(Builder $query, Model $farParent, Model $parent, $firstKey, $secondKey, $localKey)
    {
        $this->localKey = $localKey;
        $this->firstKey = $firstKey;
        $this->secondKey = $secondKey;
        $this->farParent = $farParent;

        parent::__construct($query, $parent);
    }
    
    
    // =======================================================================//
    //          Abstract implementation                                                                        
    // =======================================================================//

    /**
     * @inheritdoc
     */
    public function addConstraints()
    {
        $parentTable = $this->parent->getTable();

        $localValue = $this->farParent[$this->localKey];

        $this->setJoin();

        if (static::$constraints) {

            $this->query->where($parentTable . '.' . $this->firstKey, '=', $localValue);
        }
    }


    /**
     * @inheritdoc
     */
    public function addEagerConstraints(array $models)
    {
        $table = $this->parent->getTable();

        $this->query->whereIn($table.'.'.$this->firstKey, $this->getKeys($models));
    }


    /**
     * @inheritdoc
     */
    public function getResults()
    {
        return $this->first();
    }


    /**
     * @inheritdoc
     */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {

            $model->setRelation($relation, null);
        }

        return $models;
    }


    /**
     * @inheritdoc
     */
    public function match(array $models, Collection $results, $relation)
    {
        $dictionary = $this->buildDictionary($results);

        // Once we have the dictionary we can simply spin through the parent models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
        foreach ($models as $model) {

            $key = $model->getKey();

            if (isset($dictionary[$key])) {

                $value = $this->related->newCollection($dictionary[$key])->first();

                $model->setRelation($relation, $value);
            }
        }

        return $models;
    }


    // =======================================================================//
    //          Getters                                                                        
    // =======================================================================//

    /**
     * Set the select clause for the relation query.
     *
     * @param array $columns
     * @return array
     */
    protected function getSelectColumns(array $columns = ['*'])
    {
        if ($columns == ['*']) {

            $columns = [$this->related->getTable().'.*'];
        }

        return array_merge($columns, [$this->parent->getTable() . '.' . $this->firstKey . ' as ' . $this->firstKeyAlias]);
    }



    /**
     * Get the qualified foreign key on the related model.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return $this->related->getTable() . '.' . $this->secondKey;
    }


    /**
     * Get the key for comparing against the parent key in "has" query.
     *
     * @return string
     */
    public function getHasCompareKey()
    {
        return $this->farParent->getQualifiedKeyName();
    }


    /**
     * Get the qualified foreign key on the "through" model.
     *
     * @return string
     */
    public function getThroughKey()
    {
        return $this->parent->getTable() . '.' . $this->firstKey;
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
    //          Join                                                                        
    // =======================================================================//

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

        $table = $related->getTable() . ' as ' . $alias;
        $one = $pivotAlias . '.' . $this->getPlainForeignKey();
        $two = $alias . '.' . $related->getKeyName();

        $q->join($table, $one, '=', $two, $type, $where);

        return 2;
    }
}