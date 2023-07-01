<?php

namespace Bitrix\Disk\Internals;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Error\IErrorable;
use Bitrix\Disk\SystemUser;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\AddResult;
use Bitrix\Main\Entity\Result;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Dictionary;

abstract class Model implements \ArrayAccess, IErrorable
{
	const ERROR_INTERNAL_ADD    = 'DISK_MO_28000';
	const ERROR_INTERNAL_UPDATE = 'DISK_MO_28001';
	const ERROR_INTERNAL_DELETE = 'DISK_MO_28002';

	/** @var int */
	protected $id;
	/** @var  ErrorCollection */
	protected $errorCollection;
	/** @var array */
	protected static $lastAliases = array();
	/** @var array */
	protected static $lastExtra = array();
	/** @var array */
	protected $loadedAttributes = array();
	/** @var Dictionary */
	private $extra;

	protected function __construct(array $attributes = array())
	{
		$this->errorCollection = new ErrorCollection;
		$this->extra = new Dictionary;

		$this->setAttributes($attributes);

		$this->init();
	}

	protected function init()
	{}

	protected function setAttributes(array $attributes, array &$aliases = null)
	{
		if(empty($attributes))
		{
			return $this;
		}

		$modelMapAttributes = static::getMapAttributes();

		foreach(array_intersect_key($attributes, $modelMapAttributes) as $name => $value)
		{
			if(isset($modelMapAttributes[$name]))
			{
				$this->{$modelMapAttributes[$name]} = $value;
			}
			$this->setAsLoadedAttribute($modelMapAttributes[$name]);
			unset($attributes[$name]);
		}
		unset($name, $value);

		if(empty($attributes))
		{
			return $this;
		}

		if($aliases === null)
		{
			$aliases =& $this::$lastAliases;
		}

		if(!$aliases)
		{
			return $this;
		}
		ksort($attributes);


		foreach($aliases as $prefix => &$aliasData)
		{
			$subEntity = array();
			$pos = mb_strlen($prefix);
			$entityName = $aliasData['alias'][$prefix];
			if(!empty($aliasData['already_calc']))
			{
				list($start, $end) = $aliasData['already_calc'];
				foreach(array_splice($attributes, $start, $end) as $name => $value)
				{
					$subEntity[mb_substr($name, $pos)] = $value;
				}
				unset($name, $value);
			}
			else
			{
				$start = null;
				$end = null;
				$i = 0;
				$currentEntity = null;
				foreach($attributes as $name => $value)
				{
					if(mb_substr($name, 0, $pos) === $prefix)
					{
						$subEntity[mb_substr($name, $pos)] = $value;
						unset($attributes[$name]);

						if($start === null)
						{
							$start = $i;
						}
						$end = $i;
					}
					$i++;
				}
				unset($name, $value);
				$aliasData['already_calc'] = array($start, $end - $start + 1);
			}
			if(!empty($subEntity))
			{
				$alias = isset($aliasData['sub']) ? $aliasData['sub'] : array();
				/** @var Model $className */
				$className = $aliasData['class'];
				$aliasesData =& $alias;

				if(!array_filter($subEntity))
				{
					if(count($subEntity) > 1 && isset($modelMapAttributes[$entityName]))
					{
						$this->setAsLoadedAttribute($modelMapAttributes[$entityName]);
					}
					continue;
				}

				$this->setSubEntityToModel($entityName, $modelMapAttributes, $className, $subEntity, $aliasesData);

				if(isset($modelMapAttributes[$entityName]))
				{
					$this->{$modelMapAttributes[$entityName]} = $className::buildFromArray($subEntity, $aliasesData);
					$this->setAsLoadedAttribute($modelMapAttributes[$entityName]);
				}

			}
		}
		unset($prefix, $aliasData, $alias);

		$this->setExtraAttributes($attributes);

		return $this;
	}

	protected function setSubEntityToModel($entityName, array $modelMapAttributes, $className, $subEntity, $aliasesData)
	{
		if(isset($modelMapAttributes[$entityName]))
		{
			$this->{$modelMapAttributes[$entityName]} = $className::buildFromArray($subEntity, $aliasesData);
			$this->setAsLoadedAttribute($modelMapAttributes[$entityName]);
		}
	}

	public function getId()
	{
		return $this->id;
	}

	public function getPrimary()
	{
		return $this->getId();
	}

	/**
	 * Returns the list of pair for mapping data and object properties.
	 * Key is field in DataManager, value is object property.
	 * @return array
	 */
	public static function getMapAttributes()
	{
		return array(
			'ID' => 'id',
		);
	}

	/**
	 * Returns the list attributes which is connected with another models.
	 * @return array
	 */
	public static function getMapReferenceAttributes()
	{
		return array();
	}

	/**
	 * Builds model from row which received from database.
	 * @param array $row Row.
	 * @param array|null $with Description of connected entities.
	 *
	 * @return Model
	 * @throws SystemException
	 */
	public static function buildFromRow(array $row, array $with = [])
	{
		$lastAliases = [];
		foreach ($with as $ref)
		{
			list(, $aliasData) = static::getAliasForRef($ref);
			if($aliasData)
			{
				$lastAliases[key($aliasData)] = current($aliasData);
			}
		}

		return static::buildFromArray($row, $lastAliases);
	}

	/**
	 * Builds model from array.
	 * @param array $attributes Model attributes.
	 * @param array &$aliases Aliases.
	 * @internal
	 * @return static
	 */
	public static function buildFromArray(array $attributes, array &$aliases = null)
	{
		/** @var Model $model */
		$model = new static;

		return $model->setAttributes($attributes, $aliases);
	}

	/**
	 * Returns the fully qualified name of this class.
	 * @return string
	 */
	public static function className()
	{
		return get_called_class();
	}

	/**
	 * Adds row to entity table, fills error collection and builds model.
	 * @param array           $data Data.
	 * @param ErrorCollection $errorCollection Error collection.
	 * @return \Bitrix\Disk\Internals\Model|static|null
	 * @throws \Bitrix\Main\NotImplementedException
	 * @internal
	 */
	public static function add(array $data, ErrorCollection $errorCollection)
	{
		/** @var DataManager $tableClassName */
		$tableClassName = static::getTableClassName();
		$resultData = $tableClassName::add($data);
		if (!$resultData->isSuccess())
		{
			$errorCollection->addFromResult($resultData);
			$errorCollection->add(array(new Error('', self::ERROR_INTERNAL_ADD)));
			return null;
		}

		return static::buildFromResult($resultData);
	}

	/**
	 * Gets the fully qualified name of table class which belongs to current model.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getTableClassName()
	{
		throw new NotImplementedException;
	}

	/**
	 * Builds model from \Bitrix\Main\Entity\Result.
	 * @param Result $result Query result.
	 * @return static
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function buildFromResult(Result $result)
	{
		/** @var Model $model */
		$model = new static;

		return $model->setAttributesFromResult($result);
	}

	protected function setAttributesFromResult(Result $result)
	{
		if(!$result->isSuccess())
		{
			throw new ArgumentException('Result is not success');
		}
		$attributes = $result->getData();
		if($result instanceof AddResult)
		{
			$attributes['ID'] = $result->getId();
		}

		return $this->setAttributes($attributes);
	}

	/**
	 * @param       $id
	 * @param array $with
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return static
	 */
	public static function getById($id, array $with = array())
	{
		return static::loadById($id, $with);
	}

	/**
	 * @param       $id
	 * @param array $with
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return static
	 */
	public static function loadById($id, array $with = array())
	{
		return static::load(array('ID' => (int)$id), $with);
	}

	/**
	 * Returns once model by specific filter.
	 * @param array $filter Filter.
	 * @param array $with List of eager loading.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return static
	 */
	public static function load(array $filter, array $with = array())
	{
		$models = static::getModelList(array(
			'select' => array('*'),
			'filter' => $filter,
			'with' => $with,
			'limit' => 1,
		));

		return array_shift($models);
	}

	/**
	 * Get model list like getList
	 * @param array $parameters
	 * @return static[]
	 */
	public static function getModelList(array $parameters)
	{
		$modelList = array();
		$query = static::getList($parameters);
		while($row = $query->fetch())
		{
			$modelList[] = static::buildFromArray($row);
		}

		return $modelList;
	}

	/**
	 * @param array $parameters
	 * @return \Bitrix\Main\DB\Result
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public static function getList(array $parameters)
	{
		/** @var DataManager $tableClass */
		$tableClass = static::getTableClassName();
		return $tableClass::getList(static::prepareGetListParameters($parameters));
	}

	/**
	 * @param array $parameters
	 * @throws \Bitrix\Main\SystemException
	 * @return array
	 */
	protected static function prepareGetListParameters(array $parameters)
	{
		static::$lastAliases = array();
		static::$lastExtra = array();

		if(!empty($parameters['with']))
		{
			if(!is_array($parameters['with']))
			{
				throw new SystemException('"with" must be array');
			}
			if(!isset($parameters['select']))
			{
				$parameters['select'] = array('*');
			}
			$parameters['select'] = array_merge($parameters['select'], static::buildOrmSelectForReference($parameters['with']));
		}
		unset($parameters['with']);

		if(!empty($parameters['extra']))
		{
			if(!is_array($parameters['extra']))
			{
				throw new SystemException('"extra" must be array');
			}
			if(!isset($parameters['select']))
			{
				$parameters['select'] = array('*');
			}
			$parameters['select'] = array_merge($parameters['select'], $parameters['extra']);
			static::$lastExtra = $parameters['extra'];
		}
		unset($parameters['extra']);

		return $parameters;
	}

	protected static function buildOrmSelectForReference(array $with)
	{
		$select = array();
		foreach ($with as $ref)
		{
			list($aliasSelect, $aliasData) = static::getAliasForRef($ref);
			$select = array_merge($select, $aliasSelect);
			if($aliasData)
			{
				static::$lastAliases[key($aliasData)] = current($aliasData);
			}
		}
		unset($ref);

		return $select;
	}

	protected static function getReferenceConf($refName)
	{
		$referenceMapAttributes = static::getMapReferenceAttributes();
		if (!isset($referenceMapAttributes[$refName]))
		{
			return null;
		}

		$conf = $referenceMapAttributes[$refName];
		if(!is_array($conf))
		{
			$conf = array(
				'orm_alias' => $refName,
				'class' => $conf,
				'select' => '*',
			);
		}
		if(!isset($conf['select']))
		{
			$conf['select'] = '*';
		}
		if(!isset($conf['orm_alias']))
		{
			$conf['orm_alias'] = $refName;
		}

		return $conf;
	}

	protected static function getAliasForRef($ref, $prevConcreteRefModelAlias = '', $prevConcreteRefOrmAlias = '')
	{
		$select = array();
		$firstDot = mb_strpos($ref, '.');
		$concreteRef = $ref;
		if($firstDot !== false)
		{
			$concreteRef = mb_substr($ref, 0, $firstDot);
		}

		$conf = static::getReferenceConf($concreteRef);
		if(!$conf)
		{
			throw new SystemException("{$concreteRef} is not defined in getMapReferenceAttributes()");
		}

		if($conf['select'] === '*' || $conf['select'] === array('*'))
		{
			$select[$prevConcreteRefModelAlias . $concreteRef . 'REF_'] = ($prevConcreteRefOrmAlias? $prevConcreteRefOrmAlias . '.' : '') . $conf['orm_alias'];
		}
		elseif(is_array($conf['select']))
		{
			foreach($conf['select'] as $field)
			{
				if(!$field)
				{
					continue;
				}
				$select[$prevConcreteRefModelAlias . $concreteRef . 'REF_' . $field] = ($prevConcreteRefOrmAlias? $prevConcreteRefOrmAlias . '.' : '') . $conf['orm_alias'] . '.' . $field;
			}
			unset($field);
		}

		$aliasData = array();
		if($firstDot)
		{
			/** @var Model $classNextRef */
			$classNextRef = $conf['class'];
			list($selectFromRef, $aliasData) = $classNextRef::getAliasForRef(ltrim(mb_strstr($ref, '.'), '.'), $prevConcreteRefModelAlias . $concreteRef . 'REF_', ltrim($prevConcreteRefOrmAlias . '.' . $conf['orm_alias'], '.'));

			$select = array_merge($select, $selectFromRef);
		}

		return array($select, array(
			$concreteRef . 'REF_' => array(
				'sub' => $aliasData,
				'alias' => array(
					$concreteRef . 'REF_' => $concreteRef,
				),
				'class' => $conf['class'],
		)));
	}

	/**
	 * Returns dictionary with extra data.
	 *
	 * The dictionary will be filled after using getList, getModelList with key 'extra'.
	 * Extra is fields from foreign tables.
	 * @internal
	 * @return Dictionary
	 */
	public function getExtra()
	{
		return $this->extra;
	}

	private function setExtraAttributes(array $attributes)
	{
		if(!$this::$lastExtra)
		{
			return;
		}

		if(!$attributes)
		{
			return;
		}

		foreach($this::$lastExtra as $fieldName => $ormName)
		{
			if(!isset($attributes[$fieldName]))
			{
				continue;
			}
			$this->extra[$fieldName] = $attributes[$fieldName];
		}
		unset($fieldName, $ormName);
	}

	protected static function checkRequiredInputParams(array $inputParams, array $required)
	{
		foreach ($required as $item)
		{
			if(!isset($inputParams[$item]) || (!$inputParams[$item] && !(is_string($inputParams[$item]) && mb_strlen($inputParams[$item]))))
			{
				//todo create validator! this is trash.
				if($item === 'CREATED_BY' || $item === 'UPDATED_BY' || $item === 'DELETED_BY')
				{
					//0 - valid value for above fields
					if(SystemUser::isSystemUserId($inputParams[$item]))
					{
						return;
					}
				}
				if($item === 'FILE_SIZE')
				{
					//possible 0 for FILE size
					if(is_numeric($inputParams[$item]) && ((int)$inputParams[$item]) === 0)
					{
						return;
					}
				}

				throw new ArgumentException("Required params: { {$item} }");
			}
		}
	}

	/**
	 * Export model to array. Use map attributes and field getter.
	 * @param array $with
	 * @return array
	 */
	public function toArray(array $with = []): array
	{
		$data = [];
		$referenceAttributes = self::getMapReferenceAttributes();
		foreach (static::getMapAttributes() as $name => $attribute)
		{
			if (is_array($attribute))
			{
				$attribute = array_pop($attribute);
			}
			if (isset($referenceAttributes[$name]) && !in_array($name, $with, true))
			{
				continue;
			}

			$getterName = 'get' . $attribute;
			if (method_exists($this, $getterName))
			{
				$data[$name] = $this->$getterName();
			}
		}

		return $data;
	}

	/**
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * @inheritdoc
	 */
	public function getErrorsByCode($code)
	{
		return $this->errorCollection->getErrorsByCode($code);
	}

	/**
	 * @inheritdoc
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * @param array $data
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	protected function update(array $data)
	{
		$this->errorCollection->clear();

		/** @var DataManager $tableClassName */
		$tableClassName = static::getTableClassName();
		$updateResult = $tableClassName::update($this->getPrimary(), $data);
		if(!$updateResult->isSuccess())
		{
			$this->errorCollection->addFromResult($updateResult);
			$this->errorCollection->add(array(new Error('', self::ERROR_INTERNAL_UPDATE)));
			return false;
		}

		$this->setAttributesFromResult($updateResult);

		return true;
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	protected function deleteInternal()
	{
		$this->errorCollection->clear();

		/** @var DataManager $tableClassName */
		$tableClassName = static::getTableClassName();
		$deleteResult = $tableClassName::delete($this->id);
		if(!$deleteResult->isSuccess())
		{
			$this->errorCollection->add(array(new Error('', self::ERROR_INTERNAL_DELETE)));
			return false;
		}

		return true;
	}

	protected function isLoadedAttribute($name)
	{
		return isset($this->loadedAttributes[$name]);
	}

	protected function setAsLoadedAttribute($name)
	{
		if($name)
		{
			$this->loadedAttributes[$name] = true;
		}
	}

	protected function setAsNotLoadedAttribute($name): void
	{
		$this->loadedAttributes[$name] = null;
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Whether a offset exists
	 * @link http://php.net/manual/en/arrayaccess.offsetexists.php
	 * @param mixed $offset <p>
	 * An offset to check for.
	 * </p>
	 * @return boolean true on success or false on failure.
	 * </p>
	 * <p>
	 * The return value will be casted to boolean if non-boolean was returned.
	 */
	public function offsetExists($offset): bool
	{
		$attributes = static::getMapAttributes();

		return isset($attributes[$offset]);
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Offset to retrieve
	 * @link http://php.net/manual/en/arrayaccess.offsetget.php
	 * @param mixed $offset <p>
	 * The offset to retrieve.
	 * </p>
	 * @return mixed Can return all value types.
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{
		$attributes = static::getMapAttributes();
		if (isset($attributes[$offset]))
		{
			$getterName = 'get' . $attributes[$offset];

			return $this->$getterName();
		}

		return null;
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Offset to set
	 * @link http://php.net/manual/en/arrayaccess.offsetset.php
	 * @param mixed $offset <p>
	 * The offset to assign the value to.
	 * </p>
	 * @param mixed $value <p>
	 * The value to set.
	 * </p>
	 * @throws \Bitrix\Main\NotSupportedException
	 * @return void
	 */
	public function offsetSet($offset, $value): void
	{
		throw new NotSupportedException('Model provide ArrayAccess only for reading');
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Offset to unset
	 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	 * @param mixed $offset <p>
	 * The offset to unset.
	 * </p>
	 * @throws \Bitrix\Main\NotSupportedException
	 * @return void
	 */
	public function offsetUnset($offset): void
	{
		throw new NotSupportedException('Model provide ArrayAccess only for reading');
	}

	/**
	 * Validates value.
	 * Uses validating logic by table class (DataManager).
	 * Be careful! This method may be deleted or changed.
	 * @param                 $fieldName
	 * @param                 $value
	 * @param ErrorCollection $errorCollection
	 * @return bool
	 * @throws ArgumentException
	 * @throws NotImplementedException
	 * @internal
	 */
	public static function isValidValueForField($fieldName, $value, ErrorCollection $errorCollection = null)
	{
		$result = new Result();

		/** @var DataManager $tableClassName */
		$tableClassName = static::getTableClassName();
		$tableClassName::getEntity()->getField($fieldName)->validateValue($value, null, array(), $result);

		if(!$result->isSuccess())
		{
			$errorCollection && $errorCollection->addFromResult($result);
			return false;
		}

		return true;
	}
}