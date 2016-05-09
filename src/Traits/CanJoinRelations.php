<?php

namespace Riesjart\Relaquent\Traits;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

trait CanJoinRelations
{
    use RelationsTrait;


    /**
     * TODO: keep track of joined relations
     *
     * @param Builder $q
     * @param string $relationName
     * @param Closure|null $closure
     * @param string $type
     * @param bool $where
     *
     * @throws Exception
     */
    public function scopeJoinRelation(Builder $q, $relationName, Closure $closure = null, $type = 'inner', $where = false)
    {
        $relationNames = explode('.', $relationName);

        if(count($relationNames) > 1) {

            $relationName = array_shift($relationNames);

            $closure = $this->getClosureForNestedRelationJoins($relationNames, $closure, $type, $where);
        }
        
        if(($aliasPosition = strpos(strtolower($relationName), ' as ')) !== false) {

            $alias = trim(substr($relationName, $aliasPosition + 4));
            $relationName = trim(substr($relationName, 0, $aliasPosition));

        } else {

            $alias = $relationName;
        }

        $relation = $this->$relationName();

        $related = $relation->getRelated();

        $joinCount = $this->createJoinOfRelation($q, $relation, $alias, $type, $where);

        if($closure) {

            $related->setTable($alias);
            $q->setModel($related);

            $joins = array_slice($q->getQuery()->joins, -1 * $joinCount, $joinCount);
            $paramArr = array_prepend($joins, $q);

            call_user_func_array($closure, $paramArr);

            $related->setTable($related->getTable());
            $q->setModel($this);
        }
    }


    /**
     * @param Builder $q
     * @param string $relation
     * @param Closure|null $closure
     * @param string $type
     */
    public function scopeJoinRelationWhere(Builder $q, $relation, Closure $closure = null, $type = 'inner')
    {
        $this->scopeJoinRelation($q, $relation, $closure, $type, true);
    }


    /**
     * @param Builder $q
     * @param string $relation
     * @param Closure|null $closure
     */
    public function scopeLeftJoinRelation(Builder $q, $relation, Closure $closure = null)
    {
        $this->scopeJoinRelation($q, $relation, $closure, 'left');
    }


    /**
     * @param Builder $q
     * @param string $relation
     * @param Closure|null $closure
     */
    public function scopeLeftJoinRelationWhere(Builder $q, $relation, Closure $closure = null)
    {
        $this->scopeJoinRelationWhere($q, $relation, $closure, 'left');
    }


    /**
     * @param Builder $q
     * @param string $relation
     * @param Closure|null $closure
     */
    public function scopeRightJoinRelation(Builder $q, $relation, Closure $closure = null)
    {
        $this->scopeJoinRelation($q, $relation, $closure, 'right');
    }


    /**
     * @param Builder $q
     * @param string $relation
     * @param Closure|null $closure
     */
    public function scopeRightJoinRelationWhere(Builder $q, $relation, Closure $closure = null)
    {
        $this->scopeJoinRelationWhere($q, $relation, $closure, 'right');
    }


    /**
     * @param array $relationNames
     * @param Closure|null $closure
     * @param string $type
     * @param bool $where
     *
     * @return Closure
     */
    protected function getClosureForNestedRelationJoins(array $relationNames, Closure $closure = null, $type = 'inner', $where = false)
    {
        return function(Builder $q) use ($relationNames, $closure, $type, $where) {

            $relationName = implode('.', $relationNames);

            $q->joinRelation($relationName, $closure, $type, $where);
        };
    }


    /**
     * @param Builder $q
     * @param Relation $relation
     * @param string $alias
     * @param string $type
     * @param bool $where
     *
     * @return int
     *
     * @throws Exception
     */
    protected function createJoinOfRelation(Builder $q, Relation $relation, $alias, $type = 'inner', $where = false)
    {
        if(method_exists($relation, 'addAsJoin')) {

            $joinCount = $relation->addAsJoin($q, $alias, $type, $where);
            
        } else {

            throw new Exception('Unsupported relation type given.');
        }

        return $joinCount;
    }
}