<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityRequisite;

class DuplicateRequisiteCriterion extends DuplicateCriterion
{
	private static $langIncluded = false;

	protected $entityTypeID = 0;
	protected $countryId = 0;
	protected $fieldName = '';
	protected $value = '';

	//** @var EntityRequisite $requisite */
	private static $requisite = null;

	public function __construct($countryId, $fieldName, $value)
	{
		parent::__construct();

		$this->useStrictComparison = true;
		$this->setCountryId($countryId);
		$this->setFieldName($fieldName);
		$this->setValue($value);
	}
	public function getCountryId()
	{
		return $this->countryId;
	}
	public function setCountryId($countryId)
	{
		if(!is_int($countryId))
		{
			throw new Main\ArgumentTypeException('countryId', 'integer');
		}
		$this->countryId = $countryId;
	}
	public function getFieldName()
	{
		return $this->fieldName;
	}
	public function setFieldName($fieldName)
	{
		if(!is_string($fieldName))
		{
			throw new Main\ArgumentTypeException('fieldName', 'string');
		}
		$this->fieldName = $fieldName;
	}
	public function getValue()
	{
		return $this->value;
	}
	public function setValue($value)
	{
		if(!is_string($value))
		{
			throw new Main\ArgumentTypeException('value', 'string');
		}
		$this->value = $value;
	}
	protected static function getRequisite()
	{
		if (self::$requisite === null)
		{
			self::$requisite = new EntityRequisite();
		}

		return self::$requisite;
	}
	public static function prepareEntityRequisiteValues($entityTypeID, $entityID)
	{
		$result = array();

		$requisite = self::getRequisite();
		$rqFields = array();
		foreach (self::getFieldsMap() as $countryId => $fields)
		{
			foreach ($fields as $fieldName)
				$rqFields[$fieldName] = true;
		}
		$rqFields = array_keys($rqFields);

		$res = $requisite->getList(
			array(
				'select' => array_merge($rqFields, array(0 => 'PRESET_ID', 'PRESET_COUNTRY_ID' => 'PRESET.COUNTRY_ID')),
				'filter' => array('=ENTITY_TYPE_ID' => $entityTypeID, '=ENTITY_ID' => $entityID)
			)
		);
		while ($row = $res->fetch())
		{
			$result[] = $row;
		}

		return $result;
	}
	public static function prepareBulkData(array $requisites)
	{
		$result = array();

		$presetCountryMap = array();
		$rqFieldsMap = self::getFieldsMap();

		foreach ($requisites as $requisiteFields)
		{
			$countryId = 0;
			if (isset($requisiteFields['PRESET_COUNTRY_ID']))
			{
				$countryId = (int)$requisiteFields['PRESET_COUNTRY_ID'];
			}
			else if (isset($requisiteFields['PRESET_ID']))
			{
				$presetId = (int)$requisiteFields['PRESET_ID'];
				if ($presetId > 0)
				{
					if (isset($presetCountryMap[$presetId]))
					{
						$countryId = $presetCountryMap[$presetId];
					}
					else
					{
						$preset = new EntityPreset();
						$presetInfo = $preset->getById($presetId);
						if (is_array($presetInfo) && isset($presetInfo['COUNTRY_ID']))
							$countryId = $presetCountryMap[$presetId] = (int)$presetInfo['COUNTRY_ID'];
					}
				}
				unset($presetId);
			}

			if ($countryId > 0)
			{
				if (is_array($rqFieldsMap[$countryId]))
				{
					foreach ($rqFieldsMap[$countryId] as $rqFieldName)
					{
						if (isset($requisiteFields[$rqFieldName]) && !empty($requisiteFields[$rqFieldName]))
						{
							if (!is_array($result[$countryId]))
								$result[$countryId] = array();
							if (!is_array($result[$countryId][$rqFieldName]))
								$result[$countryId][$rqFieldName] = array();
							$result[$countryId][$rqFieldName][] = $requisiteFields[$rqFieldName];
						}
					}
				}
			}
		}

		return $result;
	}
	public static function prepareCodes($countryId, $fieldName, array $values)
	{
		if(!is_int($countryId))
		{
			throw new Main\ArgumentTypeException('countryId', 'integer');
		}
		if(!is_string($fieldName))
		{
			throw new Main\ArgumentTypeException('fieldName', 'string');
		}

		$result = array();
		foreach($values as $value)
		{
			if(!is_string($value))
			{
				continue;
			}

			$value = preg_replace('/[^0-9]/i', '', $value);
			if($value !== '')
			{
				$result[] = mb_strtolower($value);
			}
		}
		return array_unique($result);
	}
	public static function prepareCode($countryId, $fieldName, $value)
	{
		$result = self::prepareCodes($countryId, $fieldName, array($value));
		return !empty($result) ? $result[0] : $value;
	}
	public static function register($entityTypeID, $entityID, $countryId, $fieldName,
									array $values, $isRaw = true)
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!is_int($entityID))
		{
			throw new Main\ArgumentTypeException('entityID', 'integer');
		}

		if(!is_int($countryId))
		{
			throw new Main\ArgumentTypeException('countryId', 'integer');
		}

		if(!is_string($fieldName))
		{
			throw new Main\ArgumentTypeException('fieldName', 'string');
		}

		if($isRaw)
		{
			$values = self::prepareCodes($countryId, $fieldName, $values);
		}

		DuplicateRequisiteMatchCodeTable::replaceValues($entityTypeID, $entityID, $countryId, $fieldName, $values);

		$typeID = DuplicateIndexType::resolveID($fieldName);
		if(in_array($typeID, self::getSupportedDedupeTypes(), true))
		{
			$scope = EntityRequisite::formatDuplicateCriterionScope($countryId);
			DuplicateEntityMatchHash::unregisterEntity($entityTypeID, $entityID, $typeID, $scope);
			foreach($values as $value)
			{
				$matches = array(
					'RQ_COUNTRY_ID' => $countryId,
					'RQ_FIELD_NAME' => $fieldName,
					'VALUE' => $value
				);
				DuplicateEntityMatchHash::register(
					$entityTypeID,
					$entityID,
					$typeID,
					self::prepareMatchHash($matches),
					true,
					$scope
				);
			}
		}
	}
	public static function bulkRegister($entityTypeID, $entityID, array $data, $isRaw = true)
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!is_int($entityID))
		{
			throw new Main\ArgumentTypeException('entityID', 'integer');
		}

		if(!$isRaw)
		{
			$effectiveData = $data;
		}
		else
		{
			$effectiveData = array();
			foreach($data as $countryId => $fields)
			{
				if(is_array($fields))
				{
					foreach ($fields as $fieldName => $values)
					{
						if (is_array($values))
						{
							$effectiveData[$countryId][$fieldName] =
								self::prepareCodes($countryId, $fieldName, $values);
						}
					}
				}
			}
		}

		DuplicateRequisiteMatchCodeTable::bulkReplaceValues($entityTypeID, $entityID, $effectiveData);

		$typesToUnregister = array();
		$scopesToUnregister = array();
		foreach (self::getFieldsMap() as $countryId => $fields)
		{
			$scope = EntityRequisite::formatDuplicateCriterionScope($countryId);
			if (is_array($effectiveData[$countryId]) && !empty($effectiveData[$countryId]))
			{
				foreach ($fields as $fieldName)
				{
					$typeID = DuplicateIndexType::resolveID($fieldName);
					if (is_array($effectiveData[$countryId][$fieldName])
						&& !empty($effectiveData[$countryId][$fieldName]))
					{
						DuplicateEntityMatchHash::unregisterEntity($entityTypeID, $entityID, $typeID, $scope);
						foreach($effectiveData[$countryId][$fieldName] as $value)
						{
							DuplicateEntityMatchHash::register(
								$entityTypeID, $entityID, $typeID,
								self::prepareMatchHash(array(
									'RQ_COUNTRY_ID' => $countryId,
									'RQ_FIELD_NAME' => $fieldName,
									'VALUE' => $value
								)),
								true, $scope
							);
						}
					}
					else
					{
						if (!is_array($typesToUnregister[$scope]))
							$typesToUnregister[$scope] = array();
						$typesToUnregister[$scope][] = $typeID;
					}
				}
			}
			else
			{
				if (!is_array($scopesToUnregister[$scope]))
					$scopesToUnregister[$scope] = array();
				foreach ($fields as $fieldName)
				{
					$typeID = DuplicateIndexType::resolveID($fieldName);
					$scopesToUnregister[$scope][] = $typeID;
				}
			}
		}
		foreach ($typesToUnregister as $scope => $types)
		{
			DuplicateEntityMatchHash::unregisterEntity($entityTypeID, $entityID, $types, $scope);
		}
		foreach ($scopesToUnregister as $scope => $types)
		{
			DuplicateEntityMatchHash::unregisterEntity($entityTypeID, $entityID, $types, $scope);
		}
	}
	public static function registerByEntity($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if (!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentException('The parameter "entityTypeID" has invalid value.');
		}

		if(!is_int($entityID))
		{
			throw new Main\ArgumentTypeException('entityID', 'integer');
		}

		if ($entityID <= 0)
		{
			throw new Main\ArgumentException('The parameter "entityID" has invalid value.');
		}

		$requisites = self::prepareEntityRequisiteValues($entityTypeID, $entityID);
		self::bulkRegister(
			$entityTypeID,
			$entityID,
			self::prepareBulkData($requisites)
		);

	}
	public static function registerByRequisite($requisiteId)
	{
		if(!is_int($requisiteId))
		{
			throw new Main\ArgumentTypeException('requisiteId', 'integer');
		}

		$entityInfo = self::getRequisite()->getOwnerEntityById($requisiteId);
		self::registerByEntity($entityInfo['ENTITY_TYPE_ID'], $entityInfo['ENTITY_ID']);
	}
	public static function unregister($entityTypeID, $entityID, $countryId = 0, $fieldName = '')
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!is_int($entityID))
		{
			throw new Main\ArgumentTypeException('entityID', 'integer');
		}

		if(!is_int($countryId))
		{
			throw new Main\ArgumentTypeException('countryId', 'integer');
		}

		if(!is_string($fieldName))
		{
			throw new Main\ArgumentTypeException('fieldName', 'string');
		}

		$filter = array(
			'ENTITY_TYPE_ID' => $entityTypeID,
			'ENTITY_ID' => $entityID
		);

		$scope = null;
		if($countryId > 0)
		{
			$filter['RQ_COUNTRY_ID'] = $countryId;
			$scope = EntityRequisite::formatDuplicateCriterionScope($countryId);
		}

		if($fieldName !== '')
		{
			$filter['RQ_FIELD_NAME'] = $fieldName;
		}

		$dbResult = DuplicateRequisiteMatchCodeTable::getList(
			array(
				'select' =>array('ID'),
				'order' => array('ID' =>'ASC'),
				'filter' => $filter
			)
		);
		while($fields = $dbResult->fetch())
		{
			DuplicateRequisiteMatchCodeTable::delete($fields['ID']);
		}

		$typeId = DuplicateIndexType::resolveID($fieldName);
		if(in_array($typeId, self::getSupportedDedupeTypes(), true))
		{
			DuplicateEntityMatchHash::unregisterEntity($entityTypeID, $entityID, $typeId, $scope);
		}
		elseif($fieldName === '')
		{
			foreach (self::getSupportedDedupeTypes() as $typeId)
			{
				DuplicateEntityMatchHash::unregisterEntity($entityTypeID, $entityID, $typeId, $scope);
			}
		}
	}
	public static function getRegisteredEntityMatches($entityTypeID, $entityID, $countryId = 0, $fieldName = '')
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!is_int($entityID))
		{
			throw new Main\ArgumentTypeException('entityID', 'integer');
		}

		if(!is_int($countryId))
		{
			throw new Main\ArgumentTypeException('countryId', 'integer');
		}

		if(!is_string($fieldName))
		{
			throw new Main\ArgumentTypeException('fieldName', 'string');
		}

		$params = array(
			'select' =>array('ID', 'RQ_COUNTRY_ID', 'RQ_FIELD_NAME', 'VALUE'),
			'order' => array('ID' =>'ASC'),
			'filter' =>  array(
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_ID' => $entityID
			)
		);

		if($countryId > 0)
		{
			$params['filter']['RQ_COUNTRY_ID'] = $countryId;
		}

		if($fieldName !== '')
		{
			$params['filter']['RQ_FIELD_NAME'] = $fieldName;
		}

		$dbResult = DuplicateRequisiteMatchCodeTable::getList($params);
		$results = array();
		while($fields = $dbResult->fetch())
		{
			$matches = array(
				'RQ_COUNTRY_ID' => isset($fields['RQ_COUNTRY_ID']) ? $fields['RQ_COUNTRY_ID'] : '',
				'RQ_FIELD_NAME' => isset($fields['RQ_FIELD_NAME']) ? $fields['RQ_FIELD_NAME'] : '',
				'VALUE' => isset($fields['VALUE']) ? $fields['VALUE'] : ''
			);
			$results[self::prepareMatchHash($matches)] = $matches;
		}
		return $results;
	}
	public static function prepareSortParams($entityTypeID, array $entityIDs, $countryId = 0, $fieldName = '')
	{
		if(empty($entityIDs))
		{
			return array();
		}

		if(!is_int($countryId))
		{
			$countryId = 0;
		}

		if(!is_string($fieldName))
		{
			$fieldName = '';
		}

		$query = new Main\Entity\Query(DuplicateRequisiteMatchCodeTable::getEntity());

		$query->addSelect('ENTITY_ID');
		$query->addSelect('RQ_COUNTRY_ID');
		$query->addSelect('RQ_FIELD_NAME');
		$query->addSelect('VALUE');

		$subQuery = new Main\Entity\Query(DuplicateRequisiteMatchCodeTable::getEntity());
		$subQuery->registerRuntimeField('', new Main\Entity\ExpressionField('MIN_ID', 'MIN(ID)'));
		$subQuery->addSelect('MIN_ID');

		$subQuery->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$subQuery->addFilter('@ENTITY_ID', $entityIDs);

		if($countryId > 0)
		{
			$subQuery->addFilter('=RQ_COUNTRY_ID', $countryId);
		}

		if($fieldName !== '')
		{
			$subQuery->addFilter('=RQ_FIELD_NAME', $fieldName);
		}

		$subQuery->addGroup('ENTITY_ID');
		$subQuery->addGroup('RQ_COUNTRY_ID');
		$subQuery->addGroup('RQ_FIELD_NAME');

		$query->registerRuntimeField('',
			new Main\Entity\ReferenceField('M',
				Main\Entity\Base::getInstanceByQuery($subQuery),
				array('=this.ID' => 'ref.MIN_ID'),
				array('join_type' => 'INNER')
			)
		);

		$result = array();

		$dbResult = $query->exec();
		while($fields = $dbResult->fetch())
		{
			$entityID = intval($fields['ENTITY_ID']);
			if(!isset($result[$entityID]))
			{
				$result[$entityID] = array();
			}

			$rqCountryId = isset($fields['RQ_COUNTRY_ID']) ? (int)$fields['RQ_COUNTRY_ID'] : 0;
			$rqFieldName = isset($fields['RQ_FIELD_NAME']) ? $fields['RQ_FIELD_NAME'] : '';
			$value = isset($fields['VALUE']) ? $fields['VALUE'] : '';
			$scope = EntityRequisite::formatDuplicateCriterionScope($rqCountryId);
			$result[$entityID][$rqFieldName][$scope] = $value;
		}
		return $result;
	}
	public static function checkIndex(array $params)
	{
		$entityTypeID = isset($params['ENTITY_TYPE_ID']) ? intval($params['ENTITY_TYPE_ID']) : \CCrmOwnerType::Undefined;
		if($entityTypeID !== \CCrmOwnerType::Undefined
			/*&& $entityTypeID !== \CCrmOwnerType::Lead*/
			&& $entityTypeID !== \CCrmOwnerType::Contact
			&& $entityTypeID !== \CCrmOwnerType::Company)
		{
			throw new Main\NotSupportedException("Entity type: '".\CCrmOwnerType::ResolveName($entityTypeID)."' is not supported in current context");
		}

		$typeID = isset($params['TYPE_ID']) ? intval($params['TYPE_ID']) : DuplicateIndexType::UNDEFINED;
		if(!in_array($typeID, self::getSupportedDedupeTypes(), true))
		{
			throw new Main\NotSupportedException("Criterion type(s): '".DuplicateIndexType::resolveName($typeID)."' is not supported in current context");
		}

		$userID = isset($params['USER_ID']) ? intval($params['USER_ID']) : 0;

		$scope = null;
		if (isset($params['SCOPE']))
		{
			$scope = $params['SCOPE'];
			if (!DuplicateIndexType::checkScopeValue($scope))
			{
				throw new Main\ArgumentException("Parameter has invalid value", 'SCOPE');
			}
		}

		$filter = array(
			'=USER_ID' => $userID,
			'=ENTITY_TYPE_ID' => $entityTypeID,
			'=TYPE_ID' => $typeID
		);
		if ($scope !== null)
			$filter['=SCOPE'] = $scope;

		$listParams = array(
			'select' => array('USER_ID', 'TYPE_ID', 'ENTITY_TYPE_ID'),
			'order' => array('USER_ID'=>'ASC', 'TYPE_ID'=>'ASC', 'ENTITY_TYPE_ID'=>'ASC'),
			'filter' => $filter,
			'limit' => 1
		);

		$dbResult = Entity\DuplicateIndexTable::getList($listParams);

		return is_array($dbResult->fetch());
	}
	/**
	* @return Main\Entity\Query
	*/
	protected static function createQuery()
	{
		return (new Main\Entity\Query(DuplicateRequisiteMatchCodeTable::getEntity()));
	}
	protected static function setQueryFilter(Main\Entity\Query $query, array $matches)
	{
		$countryId = isset($matches['RQ_COUNTRY_ID']) ? (int)$matches['RQ_COUNTRY_ID'] : null;

		$fieldName = isset($matches['RQ_FIELD_NAME']) ? $matches['RQ_FIELD_NAME'] : '';
		if($fieldName === '')
		{
			throw new Main\ArgumentException("Parameter 'TYPE' is required.", 'matches');
		}

		$value = isset($matches['VALUE']) ? $matches['VALUE'] : '';
		if($value === '')
		{
			throw new Main\ArgumentException("Parameter 'VALUE' is required.", 'matches');
		}

		if ($countryId !== null)
			$query->addFilter('=RQ_COUNTRY_ID', $countryId);
		$query->addFilter('=RQ_FIELD_NAME', $fieldName);
		$query->addFilter('=VALUE', $value);
	}

	/**
	 * Prepare duplicate search query
	 * @param \CCrmOwnerType|int $entityTypeID Target Entity Type ID
	 * @param array|null $select
	 * @param array|null $order
	 * @param int $limit Limit of result query
	 * @return Main\Entity\Query
	 * @throws Main\ArgumentTypeException
	 * @throws Main\InvalidOperationException
	 */
	public function prepareSearchQuery($entityTypeID = \CCrmOwnerType::Undefined, array $select = null, array $order = null, $limit = 0)
	{
		if($this->fieldName === '')
		{
			throw new Main\InvalidOperationException('The field "fieldName" is not assigned.');
		}

		if($this->value === '')
		{
			throw new Main\InvalidOperationException('The field "value" is not assigned.');
		}

		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		$query = new Main\Entity\Query(DuplicateRequisiteMatchCodeTable::getEntity());
		if(!is_array($select))
		{
			$select = array();
		}
		if(empty($select))
		{
			$select = array('ENTITY_TYPE_ID', 'ENTITY_ID');
		}
		$query->setSelect($select);

		if(is_array($order) && !empty($order))
		{
			$query->setOrder($order);
		}

		$filter = array();
		if ($this->countryId > 0)
			$filter['=RQ_COUNTRY_ID'] = $this->countryId;
		$filter['=RQ_FIELD_NAME'] = $this->fieldName;

		$value = self::prepareCode($this->countryId, $this->fieldName, $this->value);
		if($this->useStrictComparison)
		{
			$filter['=VALUE'] = $value;
		}
		else
		{
			$filter['%VALUE'] = new Main\DB\SqlExpression('?s', $value.'%');
		}

		if(\CCrmOwnerType::IsDefined($entityTypeID))
		{
			$filter['ENTITY_TYPE_ID'] = $entityTypeID;
		}

		$query->setFilter($filter);

		if($limit > 0)
		{
			$query->setLimit($limit);
		}

		return $query;
	}
	/*
	 *  @return Duplicate;
	 */
	public function find($entityTypeID = \CCrmOwnerType::Undefined, $limit = 50)
	{
		if($this->fieldName === '')
		{
			//Invalid Operation?
			return null;
		}

		if($this->value === '')
		{
			//Invalid Operation?
			return null;
		}

		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!is_int($limit))
		{
			throw new Main\ArgumentTypeException('limit', 'integer');
		}

		if($limit <= 0)
		{
			$limit = 50;
		}

		$filter = array();
		if ($this->countryId > 0)
			$filter['=RQ_COUNTRY_ID'] = $this->countryId;
		$filter['=RQ_FIELD_NAME'] = $this->fieldName;
		$filter['=VALUE'] = self::prepareCode($this->countryId, $this->fieldName, $this->value);

		if(\CCrmOwnerType::IsDefined($entityTypeID))
		{
			$filter['ENTITY_TYPE_ID'] = $entityTypeID;
		}

		$listParams = $this->applyEntityCategoryFilter($entityTypeID, [
			'select' => ['ENTITY_TYPE_ID', 'ENTITY_ID'],
			'order' => [
				'ENTITY_TYPE_ID' => $this->sortDescendingByEntityTypeId ? 'DESC' : 'ASC',
				'ENTITY_ID' => 'ASC'
			],
			'filter' => $filter,
			'limit' => $limit,
		]);
		$dbResult = DuplicateRequisiteMatchCodeTable::getList($listParams);

		$entities = array();
		while($fields = $dbResult->fetch())
		{
			$entityTypeID = isset($fields['ENTITY_TYPE_ID']) ? intval($fields['ENTITY_TYPE_ID']) : 0;
			$entityID = isset($fields['ENTITY_ID']) ? intval($fields['ENTITY_ID']) : 0;

			if(\CCrmOwnerType::IsDefined($entityTypeID) && $entityID > 0)
			{
				$entities[] = new DuplicateEntity($entityTypeID, $entityID);
			}
		}
		return !empty($entities) ? new Duplicate($this, $entities) : null;
	}
	public function equals(DuplicateCriterion $item)
	{
		if(!($item instanceof DuplicateRequisiteCriterion))
		{
			return false;
		}

		/** @var DuplicateRequisiteCriterion $item */
		if($this->countryId !== $item->getCountryId())
		{
			return false;
		}

		if($this->fieldName !== $item->getFieldName())
		{
			return false;
		}

		return self::prepareCode($this->countryId, $this->fieldName, $this->value) ===
			self::prepareCode($this->countryId, $this->fieldName, $item->getValue());
	}
	public function getIndexTypeID()
	{
		if($this->fieldName ===  DuplicateIndexType::RQ_INN_NAME)
		{
			return DuplicateIndexType::RQ_INN;
		}
		elseif($this->fieldName ===  DuplicateIndexType::RQ_OGRN_NAME)
		{
			return DuplicateIndexType::RQ_OGRN;
		}
		elseif($this->fieldName ===  DuplicateIndexType::RQ_OGRNIP_NAME)
		{
			return DuplicateIndexType::RQ_OGRNIP;
		}
		elseif($this->fieldName ===  DuplicateIndexType::RQ_BIN_NAME)
		{
			return DuplicateIndexType::RQ_BIN;
		}
		elseif($this->fieldName ===  DuplicateIndexType::RQ_EDRPOU_NAME)
		{
			return DuplicateIndexType::RQ_EDRPOU;
		}
		elseif($this->fieldName ===  DuplicateIndexType::RQ_VAT_ID_NAME)
		{
			return DuplicateIndexType::RQ_VAT_ID;
		}
		else
		{
			return DuplicateIndexType::UNDEFINED;
		}
	}
	public function getTypeName()
	{
		return 'REQUISITE';
	}
	public function getMatches()
	{
		return array(
			'RQ_COUNTRY_ID' => $this->countryId,
			'RQ_FIELD_NAME' => $this->fieldName,
			'VALUE' => $this->value
		);
	}
	public static function createFromMatches(array $matches)
	{
		$countryId = isset($matches['RQ_COUNTRY_ID']) ? (int)$matches['RQ_COUNTRY_ID'] : 0;
		$fieldName = isset($matches['RQ_FIELD_NAME']) ? $matches['RQ_FIELD_NAME'] : '';
		$value = isset($matches['VALUE']) ? $matches['VALUE'] : '';
		return new DuplicateRequisiteCriterion($countryId, $fieldName, $value);
	}
	public static function loadEntityMatches($entityTypeID, $entityID, $countryId, $fieldName)
	{
		if(!is_int($countryId))
		{
			throw new Main\ArgumentTypeException('countryId', 'integer');
		}

		if(!is_string($fieldName))
		{
			throw new Main\ArgumentTypeException('fieldName', 'string');
		}

		$query = new Main\Entity\Query(DuplicateRequisiteMatchCodeTable::getEntity());
		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$query->addFilter('=ENTITY_ID', $entityID);
		if ($countryId > 0)
			$query->addFilter('=RQ_COUNTRY_ID', $countryId);
		$query->addFilter('=RQ_FIELD_NAME', $fieldName);

		$query->addSelect('VALUE');
		$query->addSelect('RQ_COUNTRY_ID');

		$dbResult = $query->exec();
		$results = array();
		while($fields = $dbResult->fetch())
		{
			$results[] = array(
				'RQ_COUNTRY_ID' => $fields['RQ_COUNTRY_ID'],
				'RQ_FIELD_NAME' => $fieldName,
				'VALUE' => isset($fields['VALUE']) ? $fields['VALUE'] : ''
			);
		}
		return $results;
	}
	public static function loadEntitiesMatches($entityTypeID, array $entityIDs, $countryId, $fieldName)
	{
		if(!is_int($countryId))
		{
			throw new Main\ArgumentTypeException('countryId', 'integer');
		}

		if(!is_string($fieldName))
		{
			throw new Main\ArgumentTypeException('fieldName', 'string');
		}

		$query = new Main\Entity\Query(DuplicateRequisiteMatchCodeTable::getEntity());
		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$query->addFilter('@ENTITY_ID', $entityIDs);
		if ($countryId > 0)
			$query->addFilter('=RQ_COUNTRY_ID', $countryId);
		$query->addFilter('=RQ_FIELD_NAME', $fieldName);

		$query->addSelect('VALUE');
		$query->addSelect('RQ_COUNTRY_ID');

		$dbResult = $query->exec();
		$results = array();
		while($fields = $dbResult->fetch())
		{
			$entityID = isset($fields['ENTITY_ID']) ? (int)$fields['ENTITY_ID'] : 0;
			if($entityID <= 0)
			{
				continue;
			}

			if(!isset($results[$entityID]))
			{
				$results[$entityID] = array();
			}

			$results[$entityID][] = array(
				'RQ_COUNTRY_ID' => $fields['RQ_COUNTRY_ID'],
				'RQ_FIELD_NAME' => $fieldName,
				'VALUE' => isset($fields['VALUE']) ? $fields['VALUE'] : ''
			);
		}
		return $results;
	}
	public function getMatchHash()
	{
		$scope = EntityRequisite::formatDuplicateCriterionScope($this->countryId);
		return $this->value !== '' ? md5("{$scope};{$this->fieldName};{$this->value}") : '';
	}
	public function getScope()
	{
		return EntityRequisite::formatDuplicateCriterionScope($this->countryId);
	}
	public static function prepareMatchHash(array $matches)
	{
		$scope = isset($matches['RQ_COUNTRY_ID']) ?
			EntityRequisite::formatDuplicateCriterionScope($matches['RQ_COUNTRY_ID']) : '';
		$type = isset($matches['RQ_FIELD_NAME']) ? $matches['RQ_FIELD_NAME'] : '';
		$value = isset($matches['VALUE']) ? $matches['VALUE'] : '';
		return $value !== '' ? md5("{$scope};{$type};{$value}") : '';
	}
	public function getMatchDescription()
	{
		return $this->value;
	}
	public static function getRegisteredCodes($entityTypeID, $entityID, $enablePermissionCheck = false, $userID = 0, $limit = 50)
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!is_int($entityID))
		{
			throw new Main\ArgumentTypeException('entityID', 'integer');
		}

		if(!is_int($userID))
		{
			throw new Main\ArgumentTypeException('userID', 'integer');
		}

		if(!is_bool($enablePermissionCheck))
		{
			throw new Main\ArgumentTypeException('enablePermissionCheck', 'boolean');
		}

		if(!is_int($limit))
		{
			throw new Main\ArgumentTypeException('limit', 'integer');
		}

		$query = new Main\Entity\Query(DuplicateRequisiteMatchCodeTable::getEntity());
		$query->addSelect('RQ_COUNTRY_ID');
		$query->addSelect('RQ_FIELD_NAME');
		$query->addSelect('VALUE');

		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$query->addFilter('=ENTITY_ID', $entityID);

		if($enablePermissionCheck && $userID > 0)
		{
			$permissions = isset($params['PERMISSIONS']) ? $params['PERMISSIONS'] : null;
			if($permissions === null)
			{
				$permissions = \CCrmPerms::GetUserPermissions($userID);
			}

			$permissionSql = \CCrmPerms::BuildSql(
				\CCrmOwnerType::ResolveName($entityTypeID),
				'',
				'READ',
				array('RAW_QUERY' => true, 'PERMS'=> $permissions)
			);

			if($permissionSql === false)
			{
				//Access denied;
				return array();
			}
			elseif($permissionSql !== '')
			{
				$query->addFilter('@ENTITY_ID', new Main\DB\SqlExpression($permissionSql));
			}
		}

		if($limit > 0)
		{
			$query->setLimit($limit);
		}

		$dbResult = $query->exec();

		$results = array();
		while($fields = $dbResult->fetch())
		{
			$countryId = isset($fields['RQ_COUNTRY_ID']) ? (int)$fields['RQ_COUNTRY_ID'] : 0;
			$fieldName = isset($fields['RQ_FIELD_NAME']) ? $fields['RQ_FIELD_NAME'] : '';
			$value = isset($fields['VALUE']) ? $fields['VALUE'] : '';
			$scope = EntityRequisite::formatDuplicateCriterionScope($countryId);
			if (!isset($results[$fieldName]))
				$results[$fieldName] = array();
			if (!isset($results[$fieldName][$scope]))
				$results[$fieldName][$scope] = array();
			$results[$fieldName][$scope][] = $value;
		}
		return $results;
	}
	public function getMatchTitle()
	{
		$requisite = self::getRequisite();

		$rqFieldTitleMap = $requisite->getRqFieldTitleMap();

		if (isset($rqFieldTitleMap[$this->fieldName][$this->countryId])
			&& !empty($rqFieldTitleMap[$this->fieldName][$this->countryId]))
		{
			$title = $rqFieldTitleMap[$this->fieldName][$this->countryId];
		}
		else
		{
			$title = $this->fieldName;
		}

		return $title;
	}
	public function getSummary()
	{
		self::includeLangFile();

		return GetMessage(
			"CRM_DUP_CRITERION_RQ_SUMMARY",
			array(
				'#TITLE#'=> $this->getMatchTitle(),
				'#DESCR#'=> $this->getMatchDescription()
			)
		);
	}
	public function getTextTotals($count, $limit = 0)
	{
		self::includeLangFile();

		if(!is_int($count))
		{
			$count = (int)$count;
		}

		if(!is_int($limit))
		{
			$limit = (int)$limit;
		}

		$exceeded = $limit > 0 && $count > $limit;
		if($exceeded)
		{
			$count = $limit;
		}

		return GetMessage(
			($exceeded
				? "CRM_DUP_CRITERION_RQ_ENTITY_TOTAL_EXCEEDED"
				: "CRM_DUP_CRITERION_RQ_ENTITY_TOTAL"),
			array(
				'#TITLE#'=> $this->getMatchTitle(),
				'#DESCR#'=> $this->getMatchDescription(),
				'#QTY#'=> Duplicate::entityCountToText($count)
			)
		);
	}
	/**
	 * Get types supported by deduplication system.
	 * @return array
	 */
	public static function getSupportedDedupeTypes()
	{
		return array(
			DuplicateIndexType::RQ_INN,
			DuplicateIndexType::RQ_OGRN,
			DuplicateIndexType::RQ_OGRNIP,
			DuplicateIndexType::RQ_BIN,
			DuplicateIndexType::RQ_EDRPOU,
			DuplicateIndexType::RQ_VAT_ID
		);
	}
	public static function getFieldsMap()
	{
		return EntityRequisite::getDuplicateCriterionFieldsMap();
	}
	public static function getIndexedFieldsMap($entityTypeID = \CCrmOwnerType::Undefined, $byScope = false)
	{
		$result = array();

		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		$fieldsMap = self::getFieldsMap();

		$indexedFieldsMap = array();
		foreach (DuplicateRequisiteMatchCodeTable::getIndexedFieldsMap($entityTypeID) as $countryId => $fields)
			$indexedFieldsMap[$countryId] = array_fill_keys($fields, true);

		if (!empty($indexedFieldsMap))
		{
			foreach ($fieldsMap as $countryId => $fields)
			{
				foreach ($fields as $fieldName)
				{
					if (isset($indexedFieldsMap[$countryId][$fieldName]))
					{
						$scope = $byScope ? EntityRequisite::formatDuplicateCriterionScope($countryId) : $countryId;
						if (!isset($result[$scope]))
							$result[$scope] = array();
						$result[$scope][] = $fieldName;
					}
				}
			}
		}

		return $result;
	}
	private static function includeLangFile()
	{
		if(!self::$langIncluded)
		{
			self::$langIncluded = IncludeModuleLangFile(__FILE__);
		}
	}
}