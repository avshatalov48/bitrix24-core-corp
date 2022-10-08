<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;
use Bitrix\Crm\CommunicationType;

class DuplicateCommunicationCriterion extends DuplicateCriterion
{
	private static $langIncluded = false;
	protected $entityTypeID = 0;
	protected $communicationType = '';
	protected $value = '';

	private static $entityMultiFields = array();

	public function __construct($communicationType, $value)
	{
		parent::__construct();

		$this->useStrictComparison = true;
		$this->setCommunicationType($communicationType);
		$this->setValue($value);
	}
	public function getCommunicationType()
	{
		return $this->communicationType;
	}
	public function setCommunicationType($communicationType)
	{
		if(!is_string($communicationType))
		{
			throw new Main\ArgumentTypeException('communicationType', 'string');
		}
		$this->communicationType = $communicationType;
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

	public static function getMultifieldsValues(array $multifields, $communicationType)
	{
		if($communicationType === CommunicationType::EMAIL_NAME)
		{
			return self::extractMultifieldsValues($multifields, \CCrmFieldMulti::EMAIL);
		}
		elseif($communicationType === CommunicationType::PHONE_NAME)
		{
			return self::extractMultifieldsValues($multifields, \CCrmFieldMulti::PHONE);
		}
		elseif($communicationType === CommunicationType::SLUSER_NAME)
		{
			return self::extractMultifieldsValues($multifields, \CCrmFieldMulti::LINK, 'USER');
		}
		elseif($communicationType === CommunicationType::FACEBOOK_NAME)
		{
			return self::extractMultifieldsValues($multifields, \CCrmFieldMulti::IM, 'FACEBOOK');
		}
		elseif($communicationType === CommunicationType::TELEGRAM_NAME)
		{
			return self::extractMultifieldsValues($multifields, \CCrmFieldMulti::IM, 'TELEGRAM');
		}
		elseif($communicationType === CommunicationType::VK_NAME)
		{
			return self::extractMultifieldsValues($multifields, \CCrmFieldMulti::IM, 'VK');
		}
		elseif($communicationType === CommunicationType::SKYPE_NAME)
		{
			return self::extractMultifieldsValues($multifields, \CCrmFieldMulti::IM, 'SKYPE');
		}
		elseif($communicationType === CommunicationType::BITRIX24_NAME)
		{
			return self::extractMultifieldsValues($multifields, \CCrmFieldMulti::IM, 'BITRIX24');
		}
		elseif($communicationType === CommunicationType::OPENLINE_NAME)
		{
			return self::extractMultifieldsValues($multifields, \CCrmFieldMulti::IM, 'OPENLINE');
		}
		elseif($communicationType === CommunicationType::VIBER_NAME)
		{
			return self::extractMultifieldsValues($multifields, \CCrmFieldMulti::IM, 'VIBER');
		}
		elseif($communicationType === CommunicationType::IMOL_NAME)
		{
			return self::extractMultifieldsValues($multifields, \CCrmFieldMulti::IM, 'IMOL');
		}

		return array();
	}
	public static function extractMultifieldsValues(array $multifields, $type, $valueType = '')
	{
		if(!is_string($type))
		{
			throw new Main\ArgumentTypeException('type', 'string');
		}

		$result = array();
		if(isset($multifields[$type]) && is_array($multifields[$type]))
		{
			$checkValueType = $valueType !== '';
			foreach($multifields[$type] as &$data)
			{
				$curentValue = isset($data['VALUE']) ? $data['VALUE'] : '';
				$currentValueType = isset($data['VALUE_TYPE']) ? $data['VALUE_TYPE'] : '';
				if($curentValue === '' || ($checkValueType && $currentValueType !== $valueType))
				{
					continue;
				}

				$result[] = $curentValue;
			}
			unset($data);
		}
		return $result;
	}

	protected static function invalidateCache($entityTypeID, $entityID)
	{
		if(isset(self::$entityMultiFields[$entityTypeID]))
		{
			unset(self::$entityMultiFields[$entityTypeID][$entityID]);
		}
	}

	public static function processMultifieldsChange($entityTypeID, $entityID)
	{
		self::invalidateCache($entityTypeID, $entityID);
	}

	public static function prepareEntityMultifieldValues($entityTypeID, $entityID, array $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		if(isset($options['invalidateCache']) && $options['invalidateCache'])
		{
			self::invalidateCache($entityTypeID, $entityID);
		}

		if(isset(self::$entityMultiFields[$entityTypeID])
			&& is_array(self::$entityMultiFields[$entityTypeID])
			&& isset(self::$entityMultiFields[$entityTypeID][$entityID])
		)
		{
			return self::$entityMultiFields[$entityTypeID][$entityID];
		}

		if(!isset(self::$entityMultiFields[$entityTypeID]))
		{
			self::$entityMultiFields[$entityTypeID] = array();
		}

		$dbResult = \CCrmFieldMulti::GetListEx(
			array(),
			array(
				'=ENTITY_ID' => \CCrmOwnerType::ResolveName($entityTypeID),
				'=ELEMENT_ID' => $entityID,
				'@TYPE_ID' => CommunicationType::getMultiFieldTypeIDs()
			),
			false,
			false,
			array('TYPE_ID', 'VALUE', 'VALUE_TYPE')
		);

		$results = array();
		if(is_object($dbResult))
		{
			while($fields = $dbResult->Fetch())
			{
				$typeID = isset($fields['TYPE_ID']) ? $fields['TYPE_ID'] : '';
				$value = isset($fields['VALUE']) ? $fields['VALUE'] : '';
				$valueType = isset($fields['VALUE_TYPE']) ? $fields['VALUE_TYPE'] : '';
				if($typeID === '' || $value === '')
				{
					continue;
				}

				if(!isset($results[$typeID]))
				{
					$results[$typeID] = array();
				}
				$results[$typeID][] = array('VALUE'=> $value, 'VALUE_TYPE' => $valueType);
			}
		}
		self::$entityMultiFields[$entityTypeID][$entityID] = $results;
		return $results;
	}
	public static function prepareBatchEntityMultifieldValues($entityTypeID, array $entityIDs)
	{
		$dbResult = \CCrmFieldMulti::GetListEx(
			array(),
			array(
				'=ENTITY_ID' => \CCrmOwnerType::ResolveName($entityTypeID),
				'@ELEMENT_ID' => $entityIDs,
				'@TYPE_ID' => CommunicationType::getMultiFieldTypeIDs()
			)
		);

		$results = array();
		if(is_object($dbResult))
		{
			while($fields = $dbResult->Fetch())
			{
				$elementID = isset($fields['ELEMENT_ID']) ? $fields['ELEMENT_ID'] : '';
				$typeID = isset($fields['TYPE_ID']) ? $fields['TYPE_ID'] : '';
				$value = isset($fields['VALUE']) ? $fields['VALUE'] : '';
				$valueType = isset($fields['VALUE_TYPE']) ? $fields['VALUE_TYPE'] : '';
				if($elementID === '' || $typeID === '' || $value === '')
				{
					continue;
				}

				if(!isset($results[$elementID]))
				{
					$results[$elementID] = array();
				}

				if(!isset($results[$elementID][$typeID]))
				{
					$results[$elementID][$typeID] = array();
				}
				$results[$elementID][$typeID][] = array('VALUE'=> $value, 'VALUE_TYPE' => $valueType);
			}
		}
		return $results;
	}
	public static function prepareBulkData(array $multifields)
	{
		$results = array();
		foreach(CommunicationType::getAllNames() as $typeName)
		{
			$values = self::getMultifieldsValues($multifields, $typeName);
			if(!empty($values))
			{
				$results[$typeName] = $values;
			}
		}
		return $results;
	}
	public static function prepareCodes($communicationType, array $values)
	{
		if(!is_string($communicationType))
		{
			throw new Main\ArgumentTypeException('type', 'string');
		}

		$result = array();
		if($communicationType === CommunicationType::PHONE_NAME)
		{
			foreach($values as $value)
			{
				$value = self::normalizePhone($value);
				if(is_string($value) && $value !== '')
				{
					$result[] = $value;
				}
			}
		}
		else    // EMAIL_NAME || SLUSER_NAME
		{
			foreach($values as $value)
			{
				if(!is_string($value))
				{
					continue;
				}

				$value = trim($value);
				if($value !== '')
				{
					$result[] = mb_strtolower($value);
				}
			}
		}
		return array_unique($result);
	}
	public static function prepareCode($communicationType, $value)
	{
		$result = self::prepareCodes($communicationType, array($value));
		return !empty($result) ? $result[0] : $value;
	}
	public static function sanitizePhone($value)
	{
		return preg_replace("/[^0-9\#\*,;]/i", "", $value);
	}
	public static function normalizePhone($value)
	{
		if(!is_string($value) || $value === '')
		{
			return '';
		}

		$result = \NormalizePhone($value, 1);
		if($result === false || $result == '')
		{
			// Is not valid phone - just clear value
			$result = preg_replace("/[^0-9\#\*,;]/i", "", $value);
		}

		$result = preg_replace('/(\d+)([;#]*)([\d,]*)/', '$1', $result);
		return is_string($result) ? $result : '';
	}
	public static function register($entityTypeID, $entityID, $type, array $values, $isRaw = true)
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!is_int($entityID))
		{
			throw new Main\ArgumentTypeException('entityID', 'integer');
		}

		if(!is_string($type))
		{
			throw new Main\ArgumentTypeException('type', 'string');
		}

		if($isRaw)
		{
			$values = self::prepareCodes($type, $values);
		}

		DuplicateCommunicationMatchCodeTable::replaceValues($entityTypeID, $entityID, $type, $values);

		$typeID = DuplicateIndexType::convertFromCommunicationType(CommunicationType::resolveID($type));
		$supportedTypes = array_merge(self::getSupportedDedupeTypes(), self::getHiddenSupportedDedupeTypes());
		if(in_array($typeID, $supportedTypes, true))
		{
			DuplicateEntityMatchHash::unregisterEntity($entityTypeID, $entityID, $typeID);
			foreach($values as $value)
			{
				$matches = array('TYPE' => $type, 'VALUE' => $value);
				DuplicateEntityMatchHash::register(
					$entityTypeID,
					$entityID,
					$typeID,
					self::prepareMatchHash($matches),
					true
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
			foreach($data as $type => $values)
			{
				if(is_array($values))
				{
					$effectiveData[$type] = self::prepareCodes($type, $values);
				}
			}
		}

		DuplicateCommunicationMatchCodeTable::bulkReplaceValues($entityTypeID, $entityID, $effectiveData);

		$supportedTypes = array_merge(self::getSupportedDedupeTypes(), self::getHiddenSupportedDedupeTypes());
		foreach($supportedTypes as $typeID)
		{
			DuplicateEntityMatchHash::unregisterEntity($entityTypeID, $entityID, $typeID);

			$type = CommunicationType::resolveName(DuplicateIndexType::convertToCommunicationType($typeID));
			if(!isset($effectiveData[$type]))
			{
				continue;
			}

			$values = $effectiveData[$type];
			foreach($values as $value)
			{
				DuplicateEntityMatchHash::register(
					$entityTypeID,
					$entityID,
					$typeID,
					self::prepareMatchHash(array('TYPE' => $type, 'VALUE' => $value)),
					true
				);
			}
		}
	}
	public static function unregister($entityTypeID, $entityID, $type = '')
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!is_int($entityID))
		{
			throw new Main\ArgumentTypeException('entityID', 'integer');
		}

		if(!is_string($type))
		{
			throw new Main\ArgumentTypeException('type', 'string');
		}

		$filter = array(
			'ENTITY_TYPE_ID' => $entityTypeID,
			'ENTITY_ID' => $entityID
		);

		if($type !== '')
		{
			$filter['TYPE'] = $type;
		}

		$dbResult = DuplicateCommunicationMatchCodeTable::getList(
			array(
				'select' =>array('ID'),
				'order' => array('ID' =>'ASC'),
				'filter' => $filter
			)
		);
		while($fields = $dbResult->fetch())
		{
			DuplicateCommunicationMatchCodeTable::delete($fields['ID']);
		}

		if($type === CommunicationType::PHONE_NAME)
		{
			DuplicateEntityMatchHash::unregisterEntity(
				$entityTypeID,
				$entityID,
				DuplicateIndexType::COMMUNICATION_PHONE
			);
		}
		elseif($type === CommunicationType::EMAIL_NAME)
		{
			DuplicateEntityMatchHash::unregisterEntity(
				$entityTypeID,
				$entityID,
				DuplicateIndexType::COMMUNICATION_EMAIL
			);
		}
		elseif($type === CommunicationType::SLUSER_NAME)
		{
			DuplicateEntityMatchHash::unregisterEntity(
				$entityTypeID,
				$entityID,
				DuplicateIndexType::COMMUNICATION_SLUSER
			);
		}
		elseif($type === '')
		{
			DuplicateEntityMatchHash::unregisterEntity(
				$entityTypeID,
				$entityID,
				DuplicateIndexType::COMMUNICATION_PHONE
			);
			DuplicateEntityMatchHash::unregisterEntity(
				$entityTypeID,
				$entityID,
				DuplicateIndexType::COMMUNICATION_EMAIL
			);
			DuplicateEntityMatchHash::unregisterEntity(
				$entityTypeID,
				$entityID,
				DuplicateIndexType::COMMUNICATION_SLUSER
			);
		}
	}
	public static function getRegisteredEntityMatches($entityTypeID, $entityID, $type = '')
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!is_int($entityID))
		{
			throw new Main\ArgumentTypeException('entityID', 'integer');
		}

		$params = array(
			'select' =>array('ID', 'TYPE', 'VALUE'),
			'order' => array('ID' =>'ASC'),
			'filter' =>  array(
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_ID' => $entityID
			)
		);

		if($type !== '')
		{
			$params['filter']['TYPE'] = $type;
		}

		$dbResult = DuplicateCommunicationMatchCodeTable::getList($params);
		$results = array();
		while($fields = $dbResult->fetch())
		{
			$matches = array(
				'TYPE' => isset($fields['TYPE']) ? $fields['TYPE'] : '',
				'VALUE' => isset($fields['VALUE']) ? $fields['VALUE'] : ''
			);
			$results[self::prepareMatchHash($matches)] = $matches;
		}
		return $results;
	}
	public static function prepareSortParams($entityTypeID, array $entityIDs, $type = '')
	{
		if(empty($entityIDs))
		{
			return array();
		}

		if(!is_string($type))
		{
			$type = '';
		}

		$query = new Main\Entity\Query(DuplicateCommunicationMatchCodeTable::getEntity());

		$query->addSelect('ENTITY_ID');
		$query->addSelect('TYPE');
		$query->addSelect('VALUE');

		$subQuery = new Main\Entity\Query(DuplicateCommunicationMatchCodeTable::getEntity());
		$subQuery->registerRuntimeField('', new Main\Entity\ExpressionField('MIN_ID', 'MIN(ID)'));
		$subQuery->addSelect('MIN_ID');

		$subQuery->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$subQuery->addFilter('@ENTITY_ID', $entityIDs);

		if($type !== '')
		{
			$subQuery->addFilter('=TYPE', $type);
		}

		$subQuery->addGroup('ENTITY_ID');
		$subQuery->addGroup('TYPE');

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

			$type = isset($fields['TYPE']) ? $fields['TYPE'] : '';
			$value = isset($fields['VALUE']) ? $fields['VALUE'] : '';
			$result[$entityID][$type] = $value;
		}
		return $result;
	}
	public static function checkIndex(array $params)
	{
		$entityTypeID = isset($params['ENTITY_TYPE_ID']) ? intval($params['ENTITY_TYPE_ID']) : \CCrmOwnerType::Undefined;
		if($entityTypeID !== \CCrmOwnerType::Undefined
			&& $entityTypeID !== \CCrmOwnerType::Lead
			&& $entityTypeID !== \CCrmOwnerType::Contact
			&& $entityTypeID !== \CCrmOwnerType::Company)
		{
			throw new Main\NotSupportedException("Entity type: '".\CCrmOwnerType::ResolveName($entityTypeID)."' is not supported in current context");
		}

		$typeID = isset($params['TYPE_ID']) ? intval($params['TYPE_ID']) : DuplicateIndexType::UNDEFINED;
		if(
			$typeID !== DuplicateIndexType::COMMUNICATION_PHONE
			&& $typeID !== DuplicateIndexType::COMMUNICATION_EMAIL
			&& $typeID !== DuplicateIndexType::COMMUNICATION_SLUSER
		)
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
		return (new Main\Entity\Query(DuplicateCommunicationMatchCodeTable::getEntity()));
	}
	protected static function setQueryFilter(Main\Entity\Query $query, array $matches)
	{
		$type = isset($matches['TYPE']) ? $matches['TYPE'] : '';
		if($type === '')
		{
			throw new Main\ArgumentException("Parameter 'TYPE' is required.", 'matches');
		}

		$value = isset($matches['VALUE']) ? $matches['VALUE'] : '';
		if($type === '')
		{
			throw new Main\ArgumentException("Parameter 'VALUE' is required.", 'matches');
		}

		$query->addFilter('=TYPE', $type);
		$query->addFilter('=VALUE', $value);
	}
	public static function getRegisteredTypes($entityTypeID, $entityID)
	{
		$dbResult = DuplicateCommunicationMatchCodeTable::getList(
			array(
				'select' => array('TYPE'),
				'filter' => array(
					'ENTITY_TYPE_ID' => $entityTypeID,
					'ENTITY_ID' => $entityID
				),
				'group' => array('TYPE'),
				'limit' => 2
			)
		);

		$result = array();
		if(is_object($dbResult))
		{
			while($fields = $dbResult->fetch())
			{
				if(isset($fields['TYPE']))
				{
					$result[] = $fields['TYPE'];
				}
			}
		}
		return $result;
	}
	/**
	 * Prepare duplicate search query
	 * @param \CCrmOwnerType $entityTypeID Target Entity Type ID
	 * @param int $limit Limit of result query
	 * @return Main\Entity\Query
	 * @throws Main\ArgumentTypeException
	 * @throws Main\InvalidOperationException
	 */
	public function prepareSearchQuery($entityTypeID = \CCrmOwnerType::Undefined, array $select = null, array $order = null, $limit = 0)
	{
		if($this->communicationType === '')
		{
			throw new Main\InvalidOperationException('The field "communicationType" is not assigned.');
		}

		if($this->value === '')
		{
			throw new Main\InvalidOperationException('The field "value" is not assigned.');
		}

		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		$query = new Main\Entity\Query(DuplicateCommunicationMatchCodeTable::getEntity());
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

		$filter = array('=TYPE' => $this->communicationType,);
		$value = self::prepareCode($this->communicationType, $this->value);
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
		if($this->communicationType === '')
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

		$filter = array(
			'=TYPE' => $this->communicationType,
			'=VALUE' => self::prepareCode($this->communicationType, $this->value)
		);

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

		$dbResult = DuplicateCommunicationMatchCodeTable::getList($listParams);
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
		if(!($item instanceof DuplicateCommunicationCriterion))
		{
			return false;
		}
		/** @var DuplicateCommunicationCriterion $item */
		if($this->communicationType !== $item->getCommunicationType())
		{
			return false;
		}

		if($this->communicationType === CommunicationType::PHONE_NAME)
		{
			return self::normalizePhone($this->value) === self::normalizePhone($item->getValue());
		}

		return $this->value === $item->getValue();
	}
	public function getIndexTypeID()
	{
		if($this->communicationType === CommunicationType::PHONE_NAME)
		{
			return DuplicateIndexType::COMMUNICATION_PHONE;
		}
		elseif($this->communicationType === CommunicationType::EMAIL_NAME)
		{
			return DuplicateIndexType::COMMUNICATION_EMAIL;
		}
		elseif($this->communicationType === CommunicationType::SLUSER_NAME)
		{
			return DuplicateIndexType::COMMUNICATION_SLUSER;
		}
		elseif($this->communicationType === CommunicationType::FACEBOOK_NAME)
		{
			return DuplicateIndexType::COMMUNICATION_FACEBOOK;
		}
		elseif($this->communicationType === CommunicationType::TELEGRAM_NAME)
		{
			return DuplicateIndexType::COMMUNICATION_TELEGRAM;
		}
		elseif($this->communicationType === CommunicationType::VK_NAME)
		{
			return DuplicateIndexType::COMMUNICATION_VK;
		}
		elseif($this->communicationType === CommunicationType::SKYPE_NAME)
		{
			return DuplicateIndexType::COMMUNICATION_SKYPE;
		}
		elseif($this->communicationType === CommunicationType::BITRIX24_NAME)
		{
			return DuplicateIndexType::COMMUNICATION_BITRIX24;
		}
		elseif($this->communicationType === CommunicationType::OPENLINE_NAME)
		{
			return DuplicateIndexType::COMMUNICATION_OPENLINE;
		}
		else
		{
			return DuplicateIndexType::UNDEFINED;
		}
	}
	public static function resolveTypeByIndexTypeID($indexTypeID)
	{
		if($indexTypeID === DuplicateIndexType::COMMUNICATION_PHONE)
		{
			return CommunicationType::PHONE_NAME;
		}
		elseif($indexTypeID === DuplicateIndexType::COMMUNICATION_EMAIL)
		{
			return CommunicationType::EMAIL_NAME;
		}
		elseif($indexTypeID === DuplicateIndexType::COMMUNICATION_SLUSER)
		{
			return CommunicationType::SLUSER_NAME;
		}
		elseif($indexTypeID === DuplicateIndexType::COMMUNICATION_FACEBOOK)
		{
			return CommunicationType::FACEBOOK_NAME;
		}
		elseif($indexTypeID === DuplicateIndexType::COMMUNICATION_TELEGRAM)
		{
			return CommunicationType::TELEGRAM_NAME;
		}
		elseif($indexTypeID === DuplicateIndexType::COMMUNICATION_VK)
		{
			return CommunicationType::VK_NAME;
		}
		elseif($indexTypeID === DuplicateIndexType::COMMUNICATION_SKYPE)
		{
			return CommunicationType::SKYPE_NAME;
		}
		elseif($indexTypeID === DuplicateIndexType::COMMUNICATION_BITRIX24)
		{
			return CommunicationType::BITRIX24_NAME;
		}
		elseif($indexTypeID === DuplicateIndexType::COMMUNICATION_OPENLINE)
		{
			return CommunicationType::OPENLINE_NAME;
		}
		elseif($indexTypeID === DuplicateIndexType::COMMUNICATION_VIBER)
		{
			return CommunicationType::VIBER_NAME;
		}

		return '';
	}
	public function getTypeName()
	{
		return 'COMMUNICATION';
	}
	public function getMatches()
	{
		return array(
			'TYPE' => $this->communicationType,
			'VALUE' => $this->value
		);
	}
	public static function createFromMatches(array $matches)
	{
		$type = isset($matches['TYPE']) ? $matches['TYPE'] : '';
		$value = isset($matches['VALUE']) ? $matches['VALUE'] : '';
		return new DuplicateCommunicationCriterion($type, $value);
	}
	public static function loadEntityMatches($entityTypeID, $entityID, $communicationType)
	{
		$query = new Main\Entity\Query(DuplicateCommunicationMatchCodeTable::getEntity());
		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$query->addFilter('=ENTITY_ID', $entityID);
		$query->addFilter('=TYPE', $communicationType);

		$query->addSelect('VALUE');
		$query->addSelect('TYPE');

		$dbResult = $query->exec();
		$results = array();
		while($fields = $dbResult->fetch())
		{
			$results[] = array(
				'TYPE' => $communicationType,
				'VALUE' => isset($fields['VALUE']) ? $fields['VALUE'] : '',
			);
		}
		return $results;
	}
	public static function loadEntitiesMatches($entityTypeID, array $entityIDs, $communicationType)
	{
		$query = new Main\Entity\Query(DuplicateCommunicationMatchCodeTable::getEntity());
		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$query->addFilter('@ENTITY_ID', $entityIDs);
		$query->addFilter('=TYPE', $communicationType);

		$query->addSelect('ENTITY_ID');
		$query->addSelect('VALUE');

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
				'TYPE' => $communicationType,
				'VALUE' => isset($fields['VALUE']) ? $fields['VALUE'] : '',
			);
		}
		return $results;
	}
	public function getMatchHash()
	{
		return $this->value !== '' ? md5("{$this->communicationType};{$this->value}") : '';
	}
	public static function prepareMatchHash(array $matches)
	{
		$value = isset($matches['VALUE']) ? $matches['VALUE'] : '';
		$type = isset($matches['TYPE']) ? $matches['TYPE'] : '';
		return $value !== '' ? md5("{$type};{$value}") : '';
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

		$query = new Main\Entity\Query(DuplicateCommunicationMatchCodeTable::getEntity());
		$query->addSelect('TYPE');
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
			$type = isset($fields['TYPE']) ? $fields['TYPE'] : '';
			$value = isset($fields['VALUE']) ? $fields['VALUE'] : '';
			if(!isset($results[$type]))
			{
				$results[$type] = array();
			}
			$results[$type][] = $value;
		}
		return $results;
	}
	public function getSummary()
	{
		self::includeLangFile();

		/*
		 * CRM_DUP_CRITERION_COMM_PHONE_SUMMARY
		 * CRM_DUP_CRITERION_COMM_EMAIL_SUMMARY
		 */

		return GetMessage(
			"CRM_DUP_CRITERION_COMM_{$this->communicationType}_SUMMARY",
			array('#DESCR#'=> $this->getMatchDescription())
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

		/*
		 * CRM_DUP_CRITERION_COMM_PHONE_ENTITY_TOTAL
		 * CRM_DUP_CRITERION_COMM_PHONE_ENTITY_TOTAL_EXCEEDED
		 * CRM_DUP_CRITERION_COMM_EMAIL_ENTITY_TOTAL
		 * CRM_DUP_CRITERION_COMM_EMAIL_ENTITY_TOTAL_EXCEEDED
		 */
		return GetMessage(
			($exceeded
				? "CRM_DUP_CRITERION_COMM_{$this->communicationType}_ENTITY_TOTAL_EXCEEDED"
				: "CRM_DUP_CRITERION_COMM_{$this->communicationType}_ENTITY_TOTAL"),
			array(
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
		//TODO: Please add
		//TODO: DuplicateIndexType::COMMUNICATION_FACEBOOK, DuplicateIndexType::COMMUNICATION_SKYPE
		//TODO: and etc. if required
		return [
			DuplicateIndexType::COMMUNICATION_PHONE,
			DuplicateIndexType::COMMUNICATION_EMAIL,
		];
	}
	public static function getHiddenSupportedDedupeTypes()
	{
		return [
			DuplicateIndexType::COMMUNICATION_SLUSER,
		];
	}
	private static function includeLangFile()
	{
		if(!self::$langIncluded)
		{
			self::$langIncluded = IncludeModuleLangFile(__FILE__);
		}
	}
}