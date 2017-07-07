<?php

namespace Spyrit\FormBundle\Form\ChoiceList;

use BaseObject;
use ModelCriteria;
use Persistent;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\Extension\Core\ChoiceList\ObjectChoiceList;
use Symfony\Component\Form\Exception\StringCastException;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceListInterface;
use Traversable;

/**
 * ModelNewChoiceList choice list used for some autocomplete type
 * with new propel object to create if not found
 *
 * bad hack : duplicated code from Propel\PropelBundle\Form\ChoiceList\ModelChoiceList
 * used for autocomplete (need to access some private properties)
 *
 * @author William Durand <william.durand1@gmail.com>
 * @author Charles SANQUER <charles.sanquer@spyrit.net>
 */
class ModelNewChoiceList extends ArrayChoiceList
{
    /**
     * check if we can add new Propel object to the choices
     *
     * @var bool
     */
    protected $addNew;

    /**
     * PHP Propel ColumnName used to set not found value on new object
     *
     * @var string
     */
    protected $newValueColumn;

    /**
     * The fields of which the identifier of the underlying class consists
     *
     * This property should only be accessed through identifier.
     *
     * @var array
     */
    private $identifier = array();

    /**
     * Query
     */
    private $query = null;

    /**
     * Query
     */
    private $queryCriteria = null;

    /**
     * Whether the model objects have already been loaded.
     *
     * @var Boolean
     */
    //private $loaded = false;

    /**
     * @param string         $class
     * @param string         $labelPath
     * @param array          $choices
     * @param ModelCriteria  $queryCriteria
     * @param string         $groupPath
     * @param bool           $addNew
     * @param string         $newValueColumn
     */
    public function __construct($class, $labelPath = null, $choices = null, $queryCriteria = null, $groupPath = null, $addNew = null, $newValueColumn = null)
    {
        $this->class        = $class;

        $queryClass         = $this->class . 'Query';
        $query              = new $queryClass();

        $this->identifier   = $query->getTableMap()->getPrimaryKeys();
        $this->query        = $query;
        $this->queryCriteria = $queryCriteria;

        $choices = is_array($choices) || $choices instanceof Traversable ? $choices : array();

        $this->addNew = (bool) $addNew;
        $this->newValueColumn = $newValueColumn;

        parent::__construct($choices, null);
        //parent::__construct($choices, $labelPath, array(), $groupPath);
    }

    /**
     * Returns the class name
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Returns the list of model objects
     *
     * @return array
     *
     * @see ChoiceListInterface
     */
    public function getChoices()
    {
        return parent::getChoices();
    }

    /**
     * Returns the values for the model objects
     *
     * @return array
     *
     * @see ChoiceListInterface
     */
    public function getValues()
    {
        return parent::getValues();
    }

    /**
     * Returns the choice views of the preferred choices as nested array with
     * the choice groups as top-level keys.
     *
     * @return array
     *
     * @see ChoiceListInterface
     */
    public function getPreferredViews()
    {
        return parent::getPreferredViews();
    }

    /**
     * Returns the choice views of the choices that are not preferred as nested
     * array with the choice groups as top-level keys.
     *
     * @return array
     *
     * @see ChoiceListInterface
     */
    public function getRemainingViews()
    {
        return parent::getRemainingViews();
    }

    /**
     * Returns the model objects corresponding to the given values.
     *
     * @param array $values
     *
     * @return array
     *
     * @see ChoiceListInterface
     */
    public function getChoicesForValues(array $values)
    {
        if (1 === count($this->identifier)) {
            $identifierPhpName = current($this->identifier)->getPhpName();
            $filterBy = 'filterBy' . $identifierPhpName;
            $results = (array) $this->query->create(null, $this->queryCriteria)
                ->$filterBy($values)
                ->find();

            if ($this->addNew && !empty($this->newValueColumn)) {
                $getMethod = 'get'.$identifierPhpName;

                $resultIds = array();
                foreach ($results as $result) {
                    $resultIds[] = $result->$getMethod();
                }

                $setterMethod = 'set'.$this->newValueColumn;

                foreach ($values as $value) {
                    if (!in_array($value, $resultIds)) {
                        $obj = new $this->class();
                        $obj->$setterMethod($value);
                        $results[] = $obj;
                    }
                }
            }
            return $results;
        }

        return parent::getChoicesForValues($values);
    }

    /**
     * Returns the values corresponding to the given model objects.
     *
     * @param array $models
     *
     * @return array
     *
     * @see ChoiceListInterface
     */
    public function getValuesForChoices(array $models)
    {
        // Optimize performance for single-field identifiers. We already
        // know that the IDs are used as values

        // Attention: This optimization does not check choices for existence
        if (1 === count($this->identifier)) {
            $values = array();
            foreach ($models as $model) {
                if ($model instanceof $this->class) {
                    // Make sure to convert to the right format
                    $values[] = (string) current($this->getIdentifierValues($model));
                }
            }

            return $values;
        }

        return parent::getValuesForChoices($models);
    }

    /**
     * Returns the indices corresponding to the given models.
     *
     * @param array $models
     *
     * @return array
     *
     * @see ChoiceListInterface
     */
    public function getIndicesForChoices(array $models)
    {
        // Optimize performance for single-field identifiers. We already
        // know that the IDs are used as indices

        // Attention: This optimization does not check choices for existence
        if (1 === count($this->identifier)) {
            $indices = array();

            foreach ($models as $model) {
                if ($model instanceof $this->class) {
                    // Make sure to convert to the right format
                    $indices[] = $this->fixIndex(current($this->getIdentifierValues($model)));
                }
            }

            return $indices;
        }

        return parent::getIndicesForChoices($models);
    }

    /**
     * Returns the models corresponding to the given values.
     *
     * @param array $values
     *
     * @return array
     *
     * @see ChoiceListInterface
     */
    public function getIndicesForValues(array $values)
    {
        // Optimize performance for single-field identifiers. We already
        // know that the IDs are used as indices and values

        // Attention: This optimization does not check values for existence
        if (1 === count($this->identifier)) {
            return $this->fixIndices($values);
        }

        return parent::getIndicesForValues($values);
    }

    /**
     * Creates a new unique index for this model.
     *
     * If the model has a single-field identifier, this identifier is used.
     *
     * Otherwise a new integer is generated.
     *
     * @param mixed $model The choice to create an index for
     *
     * @return integer|string A unique index containing only ASCII letters,
     *                        digits and underscores.
     */
    protected function createIndex($model)
    {
        if (1 === count($this->identifier)) {
            return current($this->getIdentifierValues($model));
        }

        return parent::createIndex($model);
    }

    /**
     * Creates a new unique value for this model.
     *
     * If the model has a single-field identifier, this identifier is used.
     *
     * Otherwise a new integer is generated.
     *
     * @param mixed $model The choice to create a value for
     *
     * @return integer|string A unique value without character limitations.
     */
    protected function createValue($model)
    {
        if (1 === count($this->identifier)) {
            return (string) current($this->getIdentifierValues($model));
        }

        return parent::createValue($model);
    }

    /**
     * Returns the values of the identifier fields of an model
     *
     * Propel must know about this model, that is, the model must already
     * be persisted or added to the idmodel map before. Otherwise an
     * exception is thrown.
     *
     * @param object $model The model for which to get the identifier
     */
    private function getIdentifierValues($model)
    {
        if ($model instanceof Persistent) {
            return array($model->getPrimaryKey());
        }

        // readonly="true" models do not implement Persistent.
        if ($model instanceof BaseObject and method_exists($model, 'getPrimaryKey')) {
            return array($model->getPrimaryKey());
        }

        return $model->getPrimaryKeys();
    }

    /**
     * Fixes the data type of the given choice index to avoid comparison
     * problems.
     *
     * @param mixed $index The choice index.
     *
     * @return integer|string The index as PHP array key.
     */
    protected function fixIndex($index)
    {
        if (is_bool($index) || (string) (int) $index === (string) $index) {
            return (int) $index;
        }

        return (string) $index;
    }
}
