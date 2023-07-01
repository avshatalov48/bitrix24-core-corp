<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * This API is in the draft status, it may be modified in the near future, so relying on it is strongly discouraged.
 *
 * @access private
 */

namespace Bitrix\Tasks;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\DataBase\LazyAccess;
use Bitrix\Tasks\Internals\Task\LabelTable;
use Bitrix\Tasks\Item\Access;
use Bitrix\Tasks\Item\Collection;
use Bitrix\Tasks\Item\Context;
use Bitrix\Tasks\Item\Converter;
use Bitrix\Tasks\Item\Exporter\Canonical;
use Bitrix\Tasks\Item\Field;
use Bitrix\Tasks\Item\Result;
use Bitrix\Tasks\Item\State;
use Bitrix\Tasks\Util\Error;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Util\UserField;

Loc::loadMessages(__FILE__);

/**
 * Magic methods, see __call()
 * @method bool canFetchData()
 * @method bool canCreate($result = null)
 * @method bool canRead($result = null)
 * @method bool canUpdate($result = null)
 * @method bool canDelete($result = null)
 */
abstract class Item extends LazyAccess
{
	protected $id = 0;
	protected $userId = 0;
	protected $transitionState = null;
	protected $instanceCached = false; // indicates if this instance use global instance cache

	protected $context = null;
	protected $accessController = null;
	protected $userFieldController = null;

	private $readingFailed = false;
	private $fetchInProgress = false;
	private $modifiedFields = array();

	protected $currentDataContext = null;
	private $dataContexts = array();
	private $dataContextFlags = array();

	protected $immutable = false;

	protected static $cache = array();

	/**
	 * Returns tablet class name, that serves database layer of the current item class
	 *
	 * @return DataManager string
	 * @throws NotImplementedException
	 */
	public static function getDataSourceClass()
	{
		throw new NotImplementedException('No data source class defined');
	}

	/**
	 * Returns user field controller class name, that performs user field management for the current item class
	 *
	 * @return null|UserField
	 */
	public static function getUserFieldControllerClass()
	{
		return null;
	}

	/**
	 * Returns access controller class name, for the current item class
	 *
	 * @return Access string
	 */
	public static function getAccessControllerClass()
	{
		return Access::getClass();
	}

	/**
	 * @return Collection string
	 */
	public static function getCollectionClass()
	{
		return Collection::getClass();
	}

	protected static function getLegacyEventMap()
	{
		return array();
	}

	public function __construct($source = 0, $userId = 0)
	{
		if(is_array($source))
		{
			$this->setData($source);
		}
		else
		{
			$this->setId($source);
		}

		$this->setUserId($userId);

		parent::__construct();
	}

	/**
	 * Get entity data (download it from database if necessary)
	 * Returns null if item not found or no access to it under the current user
	 *
	 * @param mixed[] $select
	 * @param mixed[] $parameters
	 *
	 * @return array|null
	 * @throws \Bitrix\Main\SystemException
	 */
	/*
	 * todo: strongly need to implement smart behaviour of $select argument. cases:
	 * todo:    1) greedy selection: fetch entire data: basic, ufs, sub-entities
	 * todo:    2) un-greedy selection: fetch only several fields, such as e.g. TITLE and UF_CRM_TASK in one query, or from cache
	 * todo:    accessing fields via $this['SOME_FIELD'] is always greedy
	 */
	// todo: implement what to select: all, basic, ufs, subEntity
	// todo: some of fields SHOULD NOT be loaded in greedy mode, like SE_LOG (it may be quite huge)
	// todo: therefore, introduce some flag like "noGreedy" in getMap() that will indicate such behaviour
	// todo: aliases mostly should go with noGreedy == true

	// todo: if item is attached to a not-existing db record, getData() should return null or array of null. $item['FIELD'] also should return null
	public function getData($select = array(), array $parameters = array())
	{
		$fields = $this->decodeSelectExpression($select);

		$data = array();
		foreach($fields as $k)
		{
			$data[$k] = $this[$k];
		}

		if($this->readingFailed)
		{
			// item not exists
			return null;
		}

		return $data;
	}

	/**
	 * @param $select
	 *
	 * Possible combinations:
	 * <li> ~ - cached fields
	 * <li> # - tablet fields
	 * <li> UF_# - user fields
	 * <li> * - check field name against the wildcard (not implemented)
	 * <li> /expression/ - check field name against the regular expression (not implemented)
	 * <li> field_name - exact field name to get
	 *
	 * todo: implement also inversion logic, with ! at the beginning
	 *
	 * @return array|\string[]
	 * @throws NotImplementedException
	 */
	private function decodeSelectExpression($select)
	{
		$map = $this->getMap();

		if($select == '~') // only cached fields
		{
			$fields = $this->getCachedFields();
		}
		elseif($select == '#') // only tablet fields
		{
			$fields = $map->getTabletFieldNames();
		}
		elseif($select == 'UF_#') // only user fields
		{
			$fields = $map->getUserFieldNames();
		}
		elseif(is_array($select) && !empty($select)) // only exactly specified fields
		{
			$fields = array();
			$expressions = array_unique($select);
			foreach($expressions as $expression)
			{
				if(
					$expression == '~' ||
					$expression == '#' ||
					$expression == 'UF_#' ||
					static::isWildCard($expression) ||
					static::isRegularExpression($expression)
				)
				{
					$fields = array_merge($fields, $this->decodeSelectExpression($expression));
				}
				else
				{
					$fields[] = $expression;
				}
			}
		}
		elseif(static::isWildCard($select)) // field names against wildcard
		{
			// todo: implement wildcard here, for example: * (all), UF_* (user fields), SE_* (sub-entities), *_FIELD_NAME_* (custom wildcard), etc...
			throw new NotImplementedException();
		}
		elseif(static::isRegularExpression($select))
		{
			throw new NotImplementedException();
		}
		else // iterate all fields
		{
			$fields = $map->getKeys();
		}

		return $fields;
	}

	private static function isWildCard($expression)
	{
		return ($expression === '*'); // todo: more complicated wildcards, like UF_*, SE_*
	}

	private static function isRegularExpression($expression): bool
	{
		if (is_array($expression))
		{
			return false;
		}

		$expression = trim((string)$expression);

		return ($expression[0] === '/' && $expression[mb_strlen($expression) - 1] === '/');
	}

	/**
	 * Set instance data in a business-level format
	 *
	 * @param array $data
	 * @param mixed[] $parameters
	 * @return $this
	 */
	public function setData(array $data, array $parameters = array())
	{
		if($this->isImmutable())
		{
			return $this; // todo: throw NotAllowedException?
		}

		foreach($data as $k => $v)
		{
			$this->offsetSetConfigurable($k, $v, $parameters);
		}

		// update id from data, if passed
		if(
			!$this->getId()
			&& isset($data['ID'])
			&& intval($data['ID'])
		)
		{
			$this->setId(intval($data['ID']));
		}

		return $this;
	}

	/**
	 * Clear item data (will be re-obtained from database on next closest call)
	 *
	 * @return $this
	 */
	public function clearData()
	{
		$this->readingFailed = false;
		$this->dataContexts = array();
		$this->dataContextFlags = array();
		$this->modifiedFields = array();
		$this->clear();

		return $this;
	}

	/**
	 * Marks field $field as modified, to be able to update its value on next closest save() call
	 *
	 * @param string $field
	 */
	public function setFieldModified($field)
	{
		$this->modifiedFields[$field] = true;
	}

	/**
	 * Marks field $field as NOT modified, to prevent from making an update of its value on next closest save() call
	 *
	 * @param string $field
	 */
	public function setFieldUnModified($field)
	{
		unset($this->modifiedFields[$field]);
	}

	/**
	 * Returns current list of modified fields
	 * @return array
	 */
	public function getModifiedFields()
	{
		// todo: also include virtual fields, if its origins were modified...

		return array_keys($this->modifiedFields);
	}

	/**
	 * Returns keys that are present in the cache
	 * @return array
	 */
	public function getCachedFields()
	{
		return array_keys($this->values);
	}

	/**
	 * An alias for static::getModifiedFields()
	 *
	 * @return array
	 */
	public function getChangedFields()
	{
		return $this->getModifiedFields();
	}

	/**
	 * Returns true if the field $field was modified
	 *
	 * @param $field
	 * @return bool
	 */
	public function isFieldModified($field)
	{
		if (
			!isset($this->modifiedFields[$field])
		)
		{
			return false;
		}
		return !!$this->modifiedFields[$field];
	}

	/**
	 * Marks all fields as NOT modified
	 */
	protected function clearModifiedFields()
	{
		$this->modifiedFields = array();
	}

	/**
	 * Sets the offset
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value): void
	{
		$this->offsetSetConfigurable($offset, $value);
	}

	/**
	 * Returns the offset
	 *
	 * @param $offset
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{
		$map = $this->getMap();
		$value = null;

		$isImmutable = $this->isImmutable();

		/** @var Field\Scalar $field */
		$field = $map[$offset];
		if($field)
		{
			if(!$this->readingFailed)
			{
				// offset is in the cache (values), null-ed or not, but it presents there
				if($this->containsKey($offset))
				{
					return $field->getValue($offset, $this);
				}

				// if the record exists, try to download value from database
				if($this->id)
				{
					$isTabletOrUf = $field->isSourceTablet() || $field->isSourceUserField();
					if($isTabletOrUf)
					{
						if($this->fetchInProgress) // can not go to the endless recursion, sorry...
						{
							return null;
						}
						$this->fetchInProgress = true;
					}

					$tabletLoaded = $this->isTabletLoaded();

					// temporarily disable immutable flag, to allow offsetSet
					if($isImmutable)
					{
						$this->immutable = false;
					}

					// if this is a tablet field, get all tablet (base) data
					if($field->isSourceTablet())
					{
						$this->fetchDataAndCache(!$tabletLoaded, false);
					}
					// the same behaviour is for user field, but get both tablet (base) and user field data
					elseif($field->isSourceUserField())
					{
						$this->fetchDataAndCache(!$tabletLoaded, !$this->setUFLoaded());
					}
					// for other types - get just tablet (base) data
					else
					{
						$this->fetchDataAndCache(!$tabletLoaded, false);
					}

					// restore flag
					if($isImmutable)
					{
						$this->immutable = true;
					}

					if($isTabletOrUf)
					{
						$this->fetchInProgress = false;
					}
				}

				if(!$this->readingFailed) // still no error after download
				{
					$value = $field->getValue($offset, $this);
				}
			}
		}
		else
		{
			// we are beyond the map scope, but this field obviously was set manually
			$value = $this->offsetGetDirect($offset);
		}

		return $value;
	}

	/**
	 * Multi-purpose configurable offset setter
	 *
	 * @param $offset
	 * @param $value
	 * @param array $parameters
	 */
	private function offsetSetConfigurable($offset, $value, array $parameters = array())
	{
		if($this->isImmutable())
		{
			return; // todo: throw NotAllowedException?
		}

		// todo: pristine state here

		$map = $this->getMap();

		/** @var Field\Scalar $field */
		$field = $map[$offset];
		if($field)
		{
			$parameters['VALUE_SOURCE'] = Field\Scalar::VALUE_SOURCE_OUTSIDE;
			$field->setValue($value, $offset, $this, $parameters);

			$this->setFieldModified($offset);
			$this->onChange();
		}
		else
		{
			// set as-is, this field is beyond the map scope, but may be used by some external code
			$this->offsetSetDirect($offset, $value);
		}
	}

	/**
	 * Classic style offset getter
	 *
	 * @param $offset
	 * @return mixed
	 */
	public function offsetGetDirect($offset)
	{
		$data =& $this->getContextData();

		return ($data[$offset] ?? null);
	}

	/**
	 * Classic style offset setter
	 *
	 * @param $offset
	 * @param $value
	 */
	public function offsetSetDirect($offset, $value)
	{
		if($this->isImmutable())
		{
			return; // todo: throw NotAllowedException?
		}

		$offset = trim((string) $offset);
		if($offset)
		{
			$data =& $this->getContextData();
			$data[$offset] = $value;
		}
	}

	/**
	 * Get pristine offset value, i.e. the actual value presents in the database right now
	 *
	 * @param $offset
	 * @return mixed
	 */
	public function offsetGetPristine($offset)
	{
		$this->currentDataContext = 'pristine';
		$value = $this[$offset];
		$this->currentDataContext = null;

		return $value;
	}

	public function containsKey($key)
	{
		$data =& $this->getContextData();
		return array_key_exists($key, $data);
	}

	private function getCachedOffsetCodes()
	{
		$data =& $this->getContextData();
		return array_keys($data);
	}

	protected function fetchBaseData($fetchBase = true, $fetchUFs = false)
	{
		if(!$fetchBase && !$fetchUFs)
		{
			return array();
		}

		$ac = $this->getAccessController();
		$dc = static::getDataSourceClass();

		if($this->canFetchData()) // formally ask access controller if we can read the item first. May be it would tell us with no query making
		{
			$filter = array('=ID' => $this->id); // todo: '=ID' may not be a correct condition for searching by primary

			$map = $this->getMap();

			$types = array();
			if($fetchBase)
			{
				$types[] = Field\Scalar::SOURCE_TABLET;
			}
			if($fetchBase)
			{
				$types[] = Field\Scalar::SOURCE_UF;
			}

			$allFields = array_diff($map->getFieldDBNamesBySourceType($types), array('ID'));
			$cachedFields = array_diff($map->getFieldDBNamesByNames($this->getCachedOffsetCodes()), array('ID'));

			// minus fields that where loaded already
			$select = array_unique(array_diff($allFields, $cachedFields));
			if(!count($select))
			{
				return array();
			}

			$queryParameters = array(
				'filter' => $filter,
				'select' => $select,
			);

			$transState = $this->getTransitionState();

			$result = $dc::getList($queryParameters)->fetch();
			if(!is_array($result))
			{
				$result = null; // access denied or not found
			}
		}
		else // else access denied, definitely
		{
			$result = null;
		}

		return $result;
	}

	private function fetchDataAndCache($fetchBase = true, $fetchUFs = false)
	{
		$base = $this->fetchBaseData($fetchBase, $fetchUFs);
		if($base === null)
		{
			$this->readingFailed = true;
		}
		else
		{
			// in $base we have raw data read from database, now we need to apply in to the entity, with conversion
			$this->setDataFromDataBase($base);

			if($fetchBase)
			{
				$this->setTabletLoaded();
			}
			if($fetchUFs)
			{
				$this->setUFLoaded();
			}
		}
	}

	private function setDataFromDataBase($data)
	{
		if(!count($data))
		{
			return;
		}

		$map = $this->getMap();

		/**
		 * @var Field\Scalar $v
		 */
		foreach($map as $k => $v)
		{
			$name = $v->getDBName();

			if(array_key_exists($name, $data))
			{
				$v->setValue($data[$name], $k, $this, array(
					'KEEP_EXISTING_VALUE' => true, // if field already cached, do not touch it
					'VALUE_SOURCE' => Field\Scalar::VALUE_SOURCE_DB,
				));
			}
		}
	}

	private function &getContextData()
	{
		if($this->currentDataContext === null)
		{
			return $this->values;
		}
		else
		{
			if($this->dataContexts[$this->currentDataContext] === null)
			{
				$this->dataContexts[$this->currentDataContext] = array();
			}

			return $this->dataContexts[$this->currentDataContext];
		}
	}

	private function &getContextFlags()
	{
		$index = ($this->currentDataContext ?? 'def');

		if (($this->dataContextFlags[$index] ?? null) === null)
		{
			$this->dataContextFlags[$index] = [];
		}

		return $this->dataContextFlags[$index];
	}

	/**
	 * Returns instance ID
	 *
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Sets or drops instance ID manually. Use with caution.
	 *
	 * @param $id
	 */
	public function setId($id)
	{
		if($this->isImmutable())
		{
			return; // todo: throw NotAllowedException?
		}

		$id = intval($id);

//		if(!$id)
//		{
//			$this->id = 0;
//		}
//		else
//		{
//			$this->id = Assert::expectIntegerNonNegative($id, '$id'); // todo: do we need exception here?
//			$this->offsetSetDirect('ID', $this->id);
//		}

		$this->id = $id;
		$this->offsetSetDirect('ID', $this->id);
	}

	/**
	 * Returns true if instance has legal ID (it does not mean this instance is present in database, though)
	 *
	 * @return bool
	 */
	public function isAttached()
	{
		return $this->getId() > 0;
	}

	public function getTransitionState()
	{
		if(!$this->transitionState)
		{
			$this->transitionState = new State();
		}

		return $this->transitionState;
	}

	/**
	 * Returns user id that is used for rights checking
	 *
	 * @return int
	 */
	public function getUserId()
	{
		if($this->userId)
		{
			return $this->userId;
		}

		return $this->getContext()->getUserId();
	}

	/**
	 * Sets user id for instance
	 *
	 * @param int $userId
	 */
	public function setUserId($userId)
	{
		if($this->isImmutable())
		{
			return; // todo: throw NotAllowedException?
		}

		$userId = intval($userId);
		if($userId)
		{
			$this->userId = $userId;
		}
	}

	/**
	 * Returns access controller instance (from pool or from property, if was set manually)
	 *
	 * @return Item\Access
	 */
	public function getAccessController()
	{
		if($this->accessController !== null)
		{
			return $this->accessController;
		}

		return static::getAccessControllerDefault();
	}

	/**
	 * Returns default access controller instance for the current instance class
	 *
	 * @return Item\Access
	 */
	public static function getAccessControllerDefault()
	{
		// prefer to use default access controller
		$cache =& static::getCache();

		if(
			!isset($cache['INSTANCES'])
			|| !is_array($cache['INSTANCES'])
		)
		{
			$cache['INSTANCES'] = array();
		}

		if(!isset($cache['INSTANCES']['AC']))
		{
			$ac = static::getAccessControllerClass();
			/** @var Item\Access $ac */
			$ac = new $ac();
			$ac->setImmutable(); // once and for all lock this instance in the "immutable" state
			$cache['INSTANCES']['AC'] = $ac;
		}

		return $cache['INSTANCES']['AC'];
	}

	/**
	 * Set access controller manually
	 *
	 * @param Item\Access $instance
	 */
	public function setAccessController($instance)
	{
		if($this->isImmutable())
		{
			return; // todo: throw NotAllowedException?
		}

		// actually, there should be like "immutable" attribute
		//		if(!$this->configurable)
		//		{
		//			throw new SystemException('Controller is non-configurable');
		//		}

		$this->accessController = $instance;
	}

	/**
	 * Returns current environment context (from pool or from property, if was set manually)
	 *
	 * @return null|Context
	 */
	public function getContext()
	{
		if($this->context)
		{
			return $this->context;
		}

		$cache =& static::getCache();

		if(!is_array($cache['INSTANCES']))
		{
			$cache['INSTANCES'] = array();
		}

		if(!isset($cache['INSTANCES']['CTX']))
		{
			$ctx = Context::getDefault();
			$cache['INSTANCES']['CTX'] = $ctx;
		}

		return $cache['INSTANCES']['CTX'];
	}

	/**
	 * Set environment context manually
	 *
	 * @param $ctx
	 */
	public function setContext($ctx)
	{
		if($this->isImmutable())
		{
			return; // todo: throw NotAllowedException?
		}

		$this->context = $ctx;
	}

	/**
	 * Returns user field controller (from pool or from property, if was set manually)
	 *
	 * @return null|UserField
	 */
	public function getUserFieldController()
	{
		if($this->userFieldController)
		{
			return $this->userFieldController;
		}

		$className = static::getUserFieldControllerClass();
		if($className === null)
		{
			return null;
		}

		$cache =& static::getCache();

		if(!is_array($cache['INSTANCES'] ?? null))
		{
			$cache['INSTANCES'] = array();
		}

		if(!isset($cache['INSTANCES']['UFC']))
		{
			$ctx = new $className;
			$cache['INSTANCES']['UFC'] = $ctx;
		}

		return $cache['INSTANCES']['UFC'];
	}

	/**
	 * Set user field controller manually
	 *
	 * @param $ufc
	 */
	public function setUserFieldController($ufc)
	{
		if($this->isImmutable())
		{
			return; // todo: throw NotAllowedException?
		}

		$this->userFieldController = $ufc;
	}

	/**
	 * Make instance from source (typically, array)
	 *
	 * @param $data
	 * @param int $userId
	 * @return static
	 */
	public static function makeInstanceFromSource($data, $userId = 0)
	{
		$item = new static(0, $userId);

		if(is_array($data))
		{
			$item->setData($data);
			$item->clearModifiedFields(); // this is not a modification, this is just some loading
		}

		return $item;
	}

	/**
	 * Get instance from pool.
	 * todo: set immutable() here
	 *
	 * @param $id
	 * @param int $userId
	 * @return static|null
	 */
	public static function getInstance($id, $userId = 0)
	{
		$userId = intval($userId);
		if(!$userId)
		{
			$userId = Context::getDefault()->getUserId();
		}

		$id = intval($id);
		if(!$id || $id < 0)
		{
			return new static($id, $userId);
		}

		$cache =& static::getCache();
		$key = $id.'-'.$userId;

		if (!is_array($cache['ITEMS'] ?? null))
		{
			$cache['ITEMS'] = [];
		}

		if(!isset($cache['ITEMS'][$key]))
		{
			$instance = new static($id, $userId);
			$instance->setImmutable();

			$cache['ITEMS'][$key] = $instance;
		}

		return $cache['ITEMS'][$key];
	}

	/**
	 * Tries to add or update item depending on if $this->id is defined or not
	 *
	 * @return Result
	 * @param mixed $settings
	 * @throws SystemException
	 */
	public function save($settings = array())
	{
		$dc = static::getDataSourceClass();

		if($this->isImmutable())
		{
			$result = new Result();
			$result->getErrors()->add('IS_IMMUTABLE', 'Item is read-only');

			return $result;
		}

		$state = $this->getTransitionState();
		if($state->isInProgress())
		{
			$result = new Result();
			$result->getErrors()->add('IN_TRANSITION', 'Item is in transition state, no overlapping operations available');

			return $result;
		}

		$map = $this->getMap();
		$ufc = $this->getUserFieldController();

		$accessResult = new Result();

		// first - check access
		if($this->id) // we want update
		{
			$canPerform = $this->canUpdate($accessResult);
		}
		else
		{
			$canPerform = $this->canCreate($accessResult);
		}

		if($canPerform)
		{
			$state->enter(
				array(),
				$this->id ? State::MODE_UPDATE : State::MODE_CREATE
			);
			/** @var Result $result */
			$result = $state->getResult();

			/** @var Field\Scalar $field */
			foreach($map as $field)
			{
				$name = $field->getName();

				if(!$this->isFieldModified($name))
				{
					// assign default values here, like they were modified...
					if(!$this->id && $field->hasDefaultValue($name, $this))
					{
						$field->setValue($field->getDefaultValue($name, $this), $name, $this);
						$this->setFieldModified($name); // mark as modified, to be saved
						continue;
					}
				}
			}

			// if we can, then run deep into structure and prepare data
			$this->prepareData($result);

			// todo: onBeforeSave event here

			// after that, run deep one more time and check data before saving
			$this->checkData($result);

			if($result->isSuccess() && $this->doPreActions($state) && $this->executeHooksBefore($state))
			{
				$tablet = array();
				$extra = array();

				/** @var Item\Field\Scalar $field */
				foreach($map as $field)
				{
					$name = $field->getName();

					// skip non-writable fields
					// skip unchanged fields
					// skip non-cache-able fields that can NOT be written to the database
					if(!$field->isDBWritable() || !($this->isFieldModified($name) || (!$field->isCacheable() && $field->isDBWritable())))
					{
						continue;
					}

					$dbName = $field->getDBName();
					$value = $this[$name];

					$isTablet = $field->isSourceTablet();
					$isUf = $ufc && $field->isSourceUserField();
					$isCustom = $field->isSourceCustom();

					if ($value !== null)
					{
						if ($isTablet || $isUf)
						{
							$tablet[$dbName] = $field->translateValueToDatabase($value, $name, $this);
						}
						elseif ($isCustom)
						{
							$extra[$name] = $value; // the field will save data by itself
						}
					}
				}

				$tablet = $this->modifyTabletDataBeforeSave($tablet);

				$authContext = new \Bitrix\Main\Authentication\Context();
				$authContext->setUserId($this->getUserId());

				unset($tablet['ID']);

				$tablet = array("fields" => $tablet, "auth_context" => $authContext);

				if($this->id)
				{
					$dbResult = $dc::update($this->id, $tablet);
				}
				else
				{
					$dbResult = $dc::add($tablet);
				}

				if($dbResult->isSuccess())
				{
					if(!$this->id)
					{
						$this->setId($dbResult->getId()); // bind current instance to the newly created item
					}

					// now save each extra field separately
					// todo: not only custom fields could have saveValueToDataBase() implemented!!!
					// todo: for example, task`s PARENT_ID can create additional structures with saveValueToDataBase()
					foreach($extra as $k => $v)
					{
						/** @var Field\Scalar $fld */
						$fld = $map[$k];
						$subSaveResult = $fld->saveValueToDataBase($v, $k, $this);

						$result->adoptErrors($subSaveResult, array(
							'CODE' => $k.'.#CODE#',
							'MESSAGE' => Loc::getMessage('TASKS_ITEM_SUBITEM_SAVE_ERROR', array(
								'#ENTITY_NAME#' => $fld->getTitle()
							)).': #MESSAGE#',
						));
					}

					$this->executeHooksAfter($state);
					$this->doPostActions($state);

					// todo: onAfterSave event here
				}
				else
				{
					$result->adoptErrors($dbResult);
				}
			}

			$result->setInstance($this);
			$state->leave();
		}
		else
		{
			$result = new Result();
		}

		$result->adoptErrors($accessResult);

		if(
			$result->isSuccess()
			&&
			(
				!isset($settings['KEEP_DATA'])
				|| $settings['KEEP_DATA'] !== true
			)
		)
		{
			$this->clearData();
		}

		return $result;
	}

	/**
	 * Tries to delete item
	 *
	 * @param mixed[] $parameters
	 *
	 * @return Result
	 * @throws SystemException
	 */
	public function delete($parameters = null)
	{
		if($this->id)
		{
			if($this->isImmutable())
			{
				$result = new Result();
				$result->getErrors()->add('IS_IMMUTABLE', 'Item is read-only');

				return $result;
			}

			$dc = static::getDataSourceClass();

			$state = $this->getTransitionState();
			if($state->isInProgress())
			{
				$result = new Result();
				$result->getErrors()->add('IN_TRANSITION', 'Item is in transition state, no overlapping operations available');

				return $result;
			}

			$accessResult = new Result();

			// first - check access
			$canPerform = $this->canDelete($accessResult);

			if($canPerform)
			{
				$state->enter(array(), State::MODE_DELETE, $parameters);
				$result = $state->getResult();

				if($this->doPreActions($state) && $this->executeHooksBefore($state))
				{
					$map = $this->getMap();

					// remove all related entities
					/** @var Field\Scalar $field */
					foreach($map as $field)
					{
						if($field->isSourceCustom())
						{
							$name = $field->getName();
							$subSaveResult = $field->saveValueToDataBase(null, $name, $this);

							$result->adoptErrors($subSaveResult, array(
								'CODE' => $name.'.#CODE#',
								'MESSAGE' => Loc::getMessage('TASKS_ITEM_SUBITEM_DELETE_ERROR', array(
									'#ENTITY_NAME#' => $field->getTitle()
								)).': #MESSAGE#',
							));
						}
					}

					// remove item itself
					$dbResult = $dc::delete($this->id);
					if($dbResult->isSuccess())
					{
						$this->executeHooksAfter($state);
						$this->doPostActions($state);

						$this->setId(0);
					}
					else
					{
						$result->adoptErrors($dbResult);
					}
				}

				$result->setInstance($this);
				$state->leave();
			}
			else
			{
				$result = new Result();
			}

			$result->adoptErrors($accessResult);
		}
		else
		{
			$result = new Result();
			$result->getErrors()->add('NO_PRIMARY', 'Attempting to delete virtual item');
		}

		return $result;
	}

	/**
	 * Count items in database by condition
	 *
	 * @param array $dcParams
	 * @param null $settings
	 * @return int
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	public static function getCount(array $dcParams = array(), $settings = null)
	{
		if(!is_array($settings))
		{
			$settings = array();
		}

		if(!intval($settings['USER_ID']))
		{
			$settings['USER_ID'] = User::getId();
		}

		$dc = static::getDataSourceClass();

		// todo: filter select key carefully here!!! DO NOT query fields that have DB_READABLE == false, and also
		// todo: care about SOURCE == Scalar::SOURCE_CUSTOM here!

		// todo: this is the default access controller, we could specify our own in $settings and use here
		$ac = static::getAccessControllerDefault();

		$parameters = $ac->addDataBaseAccessCheck(
			$dcParams,
			array(
				'USER_ID' => $settings['USER_ID'],
			)
		);

		// catch some exceptions came from orm, and wrap it into a error
		try
		{
			$count = $dc::getCount($parameters);
		}
		catch(SystemException $e) // orm throws common SystemException, which is not good, but we cant do anything
		{
			throw $e;
		}

		return $count;
	}

	/**
	 * Find items in database by condition
	 *
	 * todo: pagenav support here, like NAV_PARAMS in old getlist()? (if yes, avoid usage of global variables)
	 *
	 * @param array $parameters
	 * @param null $settings
	 * @return array|\Bitrix\Tasks\Item\Collection
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	public static function find(array $parameters = array(), $settings = null)
	{
		if(!is_array($settings))
		{
			$settings = array();
		}
		if(!(int)($settings['USER_ID'] ?? null))
		{
			$settings['USER_ID'] = User::getId();
		}

		$dc = static::getDataSourceClass();
		$dcParams = array_intersect_key(
			$parameters,
			array('filter' => 1, 'select' => 1, 'order' => 1, 'limit' => 1, 'offset' => 1, 'count_total' => 1)
		);

		// todo: filter select key carefully here!!! DO NOT query fields that have DB_READABLE == false, and also
		// todo: care about SOURCE == Scalar::SOURCE_CUSTOM here!

		// todo: this is the default access controller, we could specify our own in $settings and use here
		$ac = static::getAccessControllerDefault();

		$items = array();
		$result = static::getCollectionInstance();

		$parameters = $ac->addDataBaseAccessCheck(
			$dcParams,
			array(
				'USER_ID' => $settings['USER_ID'],
			)
		);

		// catch some exceptions came from orm, and wrap it into a error
		try
		{
			$res = $dc::getList($parameters);
		}
		catch(SystemException $e) // orm throws common SystemException, which is not good, but we cant do anything
		{
			if($e->getCode() == 100) // errors like "Unknown field"
			{
				$message = $e->getMessage();
				$found = array();
				$data = array();
				if(preg_match('#Unknown field definition `([a-zA-Z0-9_]+)`#', $message, $found) && $found[1] && mb_strlen($found[1]))
				{
					$message = Loc::getMessage('TASKS_ITEM_UNKNOWN_FIELD', array('FIELD_NAME' => $found[1]));
					$data = array('FIELD_NAME' => $found[1]);
				}

				$result->addError('UNKNOWN_FIELD', $message, Error::TYPE_FATAL, $data);
				$res = null;
			}
			else
			{
				throw $e;
			}
		}

		if($res)
		{
			while($item = $res->fetch())
			{
				// todo: in $settings we could have RETURN_TYPE field: return item collection or array-of-array

				$items[] = static::makeInstanceFromSource($item, $settings['USER_ID']);
			}
			$result->set($items);
		}

		return $result;
	}

	/**
	 * Find items in database by condition
	 *
	 * todo: pagenav support here, like NAV_PARAMS in old getlist()? (if yes, avoid usage of global variables)
	 *
	 * @param array $parameters
	 * @param null $settings
	 * @return array|\Bitrix\Tasks\Item\Collection
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	public static function getList(array $parameters = array(), $settings = null)
	{
		if(!is_array($settings))
		{
			$settings = array();
		}
		if(!intval($settings['USER_ID']))
		{
			$settings['USER_ID'] = User::getId();
		}

		$dc = static::getDataSourceClass();
		$dcParams = array_intersect_key(
			$parameters,
			array('filter' => 1, 'select' => 1, 'order' => 1, 'limit' => 1, 'offset' => 1, 'count_total' => 1)
		);

		if(isset($dcParams['select']) && !in_array('*', $dcParams['select']) && !in_array('ID', $dcParams['select']))
		{
			$dcParams['select'][]='ID';
		}

		// todo: filter select key carefully here!!! DO NOT query fields that have DB_READABLE == false, and also
		// todo: care about SOURCE == Scalar::SOURCE_CUSTOM here!

		// todo: this is the default access controller, we could specify our own in $settings and use here
		$ac = static::getAccessControllerDefault();

		$items = array();
		$result = static::getCollectionInstance();

		$parameters = $ac->addDataBaseAccessCheck(
			$dcParams,
			array(
				'USER_ID' => $settings['USER_ID'],
			)
		);

		// catch some exceptions came from orm, and wrap it into a error
		try
		{
			$res = $dc::getList($parameters);
		}
		catch(SystemException $e) // orm throws common SystemException, which is not good, but we cant do anything
		{
			if($e->getCode() == 100) // errors like "Unknown field"
			{
				$message = $e->getMessage();
				$found = array();
				$data = array();
				if(preg_match('#Unknown field definition `([a-zA-Z0-9_]+)`#', $message, $found) && $found[1] && mb_strlen($found[1]))
				{
					$message = Loc::getMessage('TASKS_ITEM_UNKNOWN_FIELD', array('FIELD_NAME' => $found[1]));
					$data = array('FIELD_NAME' => $found[1]);
				}

				$result->addError('UNKNOWN_FIELD', $message, Error::TYPE_FATAL, $data);
				$res = null;
			}
			else
			{
				throw $e;
			}
		}

		if($res)
		{
			while($item = $res->fetch())
			{
				$items[ $item['ID'] ] = $item;
			}
			$result->set($items);
		}

		return $result;
	}

	/**
	 * Find one item in database by condition
	 *
	 * @param array $parameters
	 * @param null $settings
	 * @return Item|null
	 */
	public static function findOne(array $parameters, $settings = null)
	{
		$parameters['limit'] = 1;
		$parameters['offset'] = 0;

		return static::find($parameters, $settings)->first();
	}

	/**
	 * @param array|null $values
	 * @return \Bitrix\Tasks\Util\Collection
	 */
	public static function getCollectionInstance(array $values = null)
	{
		$className = static::getCollectionClass();
		return new $className($values);
	}

	/**
	 * Constructs an instance of Map object for current item class
	 *
	 * @param array $parameters
	 * @return Field\Map
	 * @throws NotImplementedException
	 */
	protected static function generateMap(array $parameters = array())
	{
		$dc = static::getDataSourceClass();
		$ufc = static::getUserFieldControllerClass();

		$map = new Field\Map();

		if (
			!isset($parameters['EXCLUDE'])
			|| !is_array($parameters['EXCLUDE'])
		)
		{
			$parameters['EXCLUDE'] = [];
		}

		// read from orm tablet
		/**
		 * @var mixed[]|\Bitrix\Main\Entity\BooleanField|\Bitrix\Main\Entity\DateTimeField|\Bitrix\Main\Entity\ScalarField $v
		 */
		foreach($dc::getMap() as $k => $v)
		{
			$name = $k;
			if(is_object($v))
			{
				$name = $v->getName();
			}
			if(array_key_exists($name, $parameters['EXCLUDE'])) // ignore some
			{
				continue;
			}

			// todo: refactor mess here, make some fabric maybe

			$isBoolean = is_object($v) ? is_a($v, '\\Bitrix\\Main\\Entity\\BooleanField') : $v['data_type'] == 'boolean';
			$isDate = is_object($v) ? is_a($v, '\\Bitrix\\Main\\Entity\\DateTimeField') : $v['data_type'] == 'datetime';
			$isReference = is_object($v) ? is_a($v, '\\Bitrix\\Main\\Entity\\ReferenceField') : isset($v['reference']);
			$isExpression = is_object($v) ? is_a($v, '\\Bitrix\\Main\\Entity\\ExpressionField') : isset($v['expression']);

			if(
				$isReference
				|| $isExpression
				|| (is_object($v) && !method_exists($v, 'getDefaultValue'))
			) // todo: make use of references and expressions too
			{
				continue;
			}

			$fParameters = array(
				'NAME' => $name,
				'SOURCE' => Field\Scalar::SOURCE_TABLET,
				//'DB_WRITABLE' => !($isReference || $isExpression),
				'DEFAULT' => is_object($v) ? $v->getDefaultValue() : ($v['default_value'] ?? null),
				'ENUMERATION' => $isBoolean ? (
					is_object($v) ? $v->getValues() : ($v['values'] ?? null)
				) : array(),
			);

			if($isDate)
			{
				$field = new Field\Date($fParameters);
			}
			elseif($isBoolean)
			{
				$field = new Field\Boolean($fParameters);
			}
			else
			{
				$field = new Field\Scalar($fParameters);
			}

			$map->placeField($field, $name);
		}

		// read from user field scheme
		if($ufc !== null && class_exists($ufc))
		{
			// as we pass 0 to the first argument, there is no need to pass userId also
			foreach($ufc::getScheme() as $name => $v)
			{
				if(array_key_exists($name, $parameters['EXCLUDE'])) // ignore some
				{
					continue;
				}

				$isDate = $v['USER_TYPE_ID'] == 'date';
				$isDateTime = $v['USER_TYPE_ID'] == 'datetime';

				$field = array(
					'NAME' => $name,
					'SOURCE' => Field\Scalar::SOURCE_UF,
					'DEFAULT' => ($v['SETTINGS']['DEFAULT_VALUE'] ?? null),
				);

				if($v['MULTIPLE'] == 'Y')
				{
					if(($isDate || $isDateTime))
					{
						$field = new Field\Collection\UFDate($field);
					}
					else
					{
						if($v['USER_TYPE_ID'] == 'integer')
						{
							$field = new Field\Collection\Integer($field);
						}
						else
						{
							$field = new Field\Collection\Scalar($field);
						}
					}
				}
				else
				{
					if($isDate || $isDateTime)
					{
						$field = new Field\UFDate($field);
					}
					else
					{
						$field = new Field\Scalar($field);
					}
				}

				$map->placeField($field, $name);
			}
		}

		// todo: make some onBuildMap event here to be able to modify it without inheritance, hmm?

		return $map;
	}

	/**
	 * Do some data rearrangements before save() performed
	 *
	 * @param Result $result
	 * @return boolean
	 * @access private
	 */
	public function prepareData($result)
	{
		$map = $this->getMap();
		/**
		 * @var Field\Scalar $v
		 */
		foreach($map as $k => $v)
		{
			$name = $v->getName();

			$v->prepareValue($v->getValue($name, $this), $name, $this, array(
				'RESULT' => $result
			));
		}

		return $result->isSuccess();
	}

	/**
	 * Checks data before save() performed
	 *
	 * @param Result $result
	 * @return boolean
	 * @access private
	 */
	public function checkData($result)
	{
		$map = $this->getMap();
		/**
		 * @var Field\Scalar $v
		 */
		foreach($map as $k => $v)
		{
			$name = $v->getName();

			$v->checkValue($v->getValue($name, $this), $name, $this, array(
				'RESULT' => $result
			));
		}

		// todo: also, there should be an ORM-based check for tablet data and user fields

		return $result->isSuccess();
	}

	/** Runs extra hook on tablet data right before ORM add() or update() call */
	protected function modifyTabletDataBeforeSave($data)
	{
		return $data;
	}

	/**
	 * Runs extra code before actions (save() and delete() performed)
	 *
	 * @param $state
	 * @return bool
	 */
	protected function doPreActions($state)
	{
		return true; // do nothing
	}

	/**
	 * Runs extra code after actions (save() and delete() performed)
	 *
	 * @param State $state
	 * @return bool
	 */
	protected function doPostActions($state)
	{
		return true; // do nothing
	}

	/**
	 * Execute possible hooks before action is done, but after checkData() prepareData() and doPreActions()
	 *
	 * @param State $state
	 * @return boolean
	 */
	protected function executeHooksBefore($state)
	{

		return true;
	}

	/**
	 * Execute possible hooks after action is done, but before doPostActions()
	 *
	 * @param State $state
	 * @return boolean
	 */
	protected function executeHooksAfter($state)
	{
		return true;
	}

	/**
	 * Exports item data using $exporter. Typically, exporting into array will be performed, but there could be custom exporters also
	 *
	 * @param array $select
	 * @param null $exporter
	 * @return array
	 */
	public function export($select = array(), $exporter = null)
	{
		if($exporter === null)
		{
			$exporter = new Canonical();
		}

		return $exporter->export($this, $select);
	}

	/**
	 * Returns item data in external-level format.
	 * The behaviour is similar to getData(), but returns a static structure without any objects.
	 * It does not return non-map offsets
	 *
	 * Practically, an alias for export() (exports all by default)
	 *
	 * @return array
	 */
	public function getArray()
	{
		return $this->export();
	}

	public function getRawValues()
	{
		return $this->values;
	}

	/**
	 * Converts current entity into a new one, using $converter
	 *
	 * @param Converter|null $converter
	 * @return Converter\Result
	 * @throws ArgumentException
	 */
	public function transform($converter)
	{
		$this->checkConverter($converter);
		return $converter->convert($this);
	}

	/**
	 * Alias for transform()
	 *
	 * @param Converter $converter
	 * @return mixed
	 */
	public function transformWith($converter)
	{
		return $this->transform($converter);
	}

	/**
	 * @param Converter $converter
	 * @return mixed
	 */
	public function abortTransformation($converter)
	{
		$this->checkConverter($converter);

		return $converter->abortConversion($this);
	}

	public function getUserFieldScheme($getValue = false, array $settings = array())
	{
		$result = new Util\Collection();
		$ufc = $this->getUserFieldController();
		if($ufc)
		{
			$scheme = $ufc->getScheme();
			if($getValue)
			{
				foreach($scheme as $field => $fieldDesc)
				{
					$fieldValue = $this[$field];
					if (
						($settings['COLLECTION_VALUE_TO_ARRAY'] ?? null)
						&& \Bitrix\Tasks\Util\Collection::isA($fieldValue)
					)
					{
						$fieldValue = $fieldValue->toArray();
					}

					$scheme[$field]['VALUE'] = $fieldValue;
				}
			}

			$result->set($scheme);
		}

		return $result;
	}

	public function __call($name, array $arguments)
	{
		$name = ToLower(trim((string) $name));

		// can*() methods stand for rights checking
		if(mb_strpos($name, 'can') === 0)
		{
			return $this->callCanMethod($name, $arguments);
		}
		else
		{
			throw new NotImplementedException('Call to unknown method '.$name);
		}
	}

	protected static function getBatchState()
	{
		$cache =& static::getCache();

		if (!($cache['BATCH_STATE'] ?? null))
		{
			$state = new State\Trigger();
			$state->setEnterCallback(static::getClass().'::processEnterBatchMode');
			$state->setLeaveCallback(static::getClass().'::processLeaveBatchMode');

			$cache['BATCH_STATE'] = $state;
		}

		return $cache['BATCH_STATE'];
	}

	public static function enterBatchState()
	{
		// todo: need for an event here

		static::getBatchState()->enter();
	}

	public static function leaveBatchState()
	{
		// todo: need for an event here, with detailed statistics on what items were created\updated\deleted

		static::getBatchState()->leave();
	}

	public static function processEnterBatchMode(State\Trigger $state)
	{
	}

	public static function processLeaveBatchMode(State\Trigger $state)
	{
	}

	protected static function &getCache()
	{
		$id = static::getClass();
		if(!array_key_exists($id, static::$cache))
		{
			static::$cache[$id] = array();
		}

		return static::$cache[$id];
	}

	protected function setDataContext($ctxName)
	{
		$this->currentDataContext = $ctxName;
	}

	protected function setDefaultDataContext()
	{
		$this->currentDataContext = null;
	}

	public function setImmutable()
	{
		$this->instanceCached = true;
		$this->immutable = true;
	}

	public function isImmutable()
	{
		return $this->immutable;
	}

	protected function callCanMethod($name, $arguments)
	{
		$method = array($this->getAccessController(), $name);
		if(is_callable($method))
		{
			$result = call_user_func_array($method, array($this));
			/** @var \Bitrix\Tasks\Util\Result $mainResult */
			$mainResult = ($arguments[0] ?? null);

			if(Result::isA($mainResult))
			{
				$mainResult->adoptErrors($result, array(
					'CODE' => 'ACCESS_DENIED.#CODE#'
				));
			}

			return $result->isSuccess();
		}
		else
		{
			return true; // unknown action, like "walk on ears" will be allowed
		}
	}

	private function checkConverter($converter)
	{
		if(!is_object($converter) || !Converter::isA($converter))
		{
			throw new ArgumentException('Illegal converter applied');
		}
	}

	private function setTabletLoaded()
	{
		$flags =& $this->getContextFlags();
		$flags['TABLET_LOADED'] = true;
	}

	private function isTabletLoaded()
	{
		$flags =& $this->getContextFlags();
		return (bool)($flags['TABLET_LOADED'] ?? null);
	}

	private function setUFLoaded()
	{
		$flags =& $this->getContextFlags();
		$flags['UF_LOADED'] = true;
	}
}