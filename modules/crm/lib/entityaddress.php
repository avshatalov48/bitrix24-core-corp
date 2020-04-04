<?php
namespace Bitrix\Crm;
use Bitrix\Main;
use \Bitrix\Sale;

class EntityAddress
{
	const Undefined = 0;
	const Primary = 1;
	const Secondary = 2;
	const Third = 3;
	const Home = 4;
	const Work = 5;
	const Registered = 6;
	const Custom = 7;
	const Post = 8;
	const Beneficiary = 9;
	const Bank = 10;

	const First = 1;
	const Last = 10;

	private static $messagesLoaded = false;

	private static $FIELD_INFOS = null;

	public static function getFieldsInfo()
	{
		if(!self::$FIELD_INFOS)
		{
			self::$FIELD_INFOS = array(
					'TYPE_ID' => array(
							'TYPE' => 'integer',
							'ATTRIBUTES' => array(
									\CCrmFieldInfoAttr::Required,
									\CCrmFieldInfoAttr::Immutable)
					),
					'ENTITY_TYPE_ID' => array(
							'TYPE' => 'integer',
							'ATTRIBUTES' => array(
									\CCrmFieldInfoAttr::Required,
									\CCrmFieldInfoAttr::Immutable)
					),
					'ENTITY_ID' => array(
							'TYPE' => 'integer',
							'ATTRIBUTES' => array(
									\CCrmFieldInfoAttr::Required,
									\CCrmFieldInfoAttr::Immutable)
					),
					'ADDRESS_1' => array('TYPE' => 'string'),
					'ADDRESS_2' => array('TYPE' => 'string'),
					'CITY' => array('TYPE' => 'string'),
					'POSTAL_CODE' => array('TYPE' => 'string'),
					'REGION' => array('TYPE' => 'string'),
					'PROVINCE' => array('TYPE' => 'string'),
					'COUNTRY' => array('TYPE' => 'string'),
					'COUNTRY_CODE' => array('TYPE' => 'string'),
					'ANCHOR_TYPE_ID' => array(
							'TYPE' => 'integer',
							'ATTRIBUTES' => array(
								\CCrmFieldInfoAttr::ReadOnly)
					),
					'ANCHOR_ID' => array(
							'TYPE' => 'integer',
							'ATTRIBUTES' => array(
									\CCrmFieldInfoAttr::ReadOnly)
					)
			);
		}
		return self::$FIELD_INFOS;
	}

	public function getList($params)
	{
		return AddressTable::getList($params);
	}

	public static function isDefined($typeID)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}
		return $typeID >= self::First && $typeID <= self::Last;
	}

	private static $labels = array();
	private static $shortLabels = array();
	private static $typeLabels = null;
	private static $typeInfos = null;

	private static function checkCountryCaption($code, $caption)
	{
		$fields = self::getCountryByCode($code);
		return $fields !== null && isset($fields['CAPTION']) && $fields['CAPTION'] === $caption;
	}

	/**
	* @return \CCrmEntityListBuilder
	*/
	protected static function createEntityListBuilder()
	{
		throw new Main\NotImplementedException('Method createEntityListBuilder must be overridden');
	}

	/**
	* @return int
	*/
	protected static function getEntityTypeID()
	{
		throw new Main\NotImplementedException('Method getEntityTypeID must be overridden');
	}

	/**
	* @return array
	*/
	protected static function getSupportedTypeIDs()
	{
		return array(EntityAddress::Primary);
	}

	/**
	* @return array
	*/
	protected static function getFieldMap($typeID)
	{
		throw new Main\NotImplementedException('Method getFieldMap must be overridden');
	}

	/**
	* @return array
	*/
	protected static function getInvertedFieldMap($typeID)
	{
		return array_flip(static::getFieldMap($typeID));
	}

	/**
	* @return int
	*/
	public static function resolveEntityFieldTypeID($fieldName, array $aliases = null)
	{
		if(is_array($aliases) && isset($aliases[$fieldName]))
		{
			$fieldName = $aliases[$fieldName];
		}

		$typeIDs = static::getSupportedTypeIDs();
		foreach($typeIDs as $typeID)
		{
			$map = static::getInvertedFieldMap($typeID);
			if(isset($map[$fieldName]))
			{
				return $typeID;
			}
		}

		return EntityAddress::Primary;
	}

	/**
	* @return string
	*/
	public static function mapEntityField($fieldName, $typeID, array $aliases = null)
	{
		if(!EntityAddress::isDefined($typeID))
		{
			$typeID = EntityAddress::Primary;
		}

		if(is_array($aliases) && isset($aliases[$fieldName]))
		{
			$fieldName = $aliases[$fieldName];
		}

		$map = static::getInvertedFieldMap($typeID);
		return isset($map[$fieldName]) ? $map[$fieldName] : $fieldName;
	}

	public static function mapEntityFields(array $fields, array $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$typeID = isset($options['TYPE_ID']) ? $options['TYPE_ID'] : EntityAddress::Undefined;
		if(!EntityAddress::isDefined($typeID))
		{
			$typeID = EntityAddress::Primary;
		}

		$skipEmpty = isset($options['SKIP_EMPTY']) ? $options['SKIP_EMPTY'] : false;
		$skipNotAssigned = isset($options['SKIP_NOT_ASSIGNED']) ? $options['SKIP_NOT_ASSIGNED'] : false;
		$aliases = isset($options['ALIASES']) && is_array($options['ALIASES'])
			? array_flip($options['ALIASES']) : null;

		$result = array();
		$map = static::getFieldMap($typeID);
		foreach($map as $k => $v)
		{
			if($aliases !== null && isset($aliases[$v]))
			{
				$v = $aliases[$v];
			}

			if(isset($fields[$v]))
			{
				$fieldValue = $fields[$v];
			}
			elseif(!$skipNotAssigned)
			{
				$fieldValue = '';
			}
			else
			{
				continue;
			}

			if($fieldValue !== '' || !$skipEmpty)
			{
				$result[$k] = $fieldValue;
			}
		}
		return $result;
	}

	public static function register($entityTypeID, $entityID, $typeID, array $data)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		$anchorTypeID = isset($data['ANCHOR_TYPE_ID']) ? (int)$data['ANCHOR_TYPE_ID'] : 0;
		if(!\CCrmOwnerType::IsDefined($anchorTypeID))
		{
			$anchorTypeID = $entityTypeID;
		}

		$anchorID = isset($data['ANCHOR_ID']) ? (int)$data['ANCHOR_ID'] : 0;
		if($anchorID <= 0)
		{
			$anchorID = $entityID;
		}

		$country = isset($data['COUNTRY']) ? $data['COUNTRY'] : '';
		$countryCode = isset($data['COUNTRY_CODE']) ? $data['COUNTRY_CODE'] : '';
		if($countryCode !== '' && ($country === '' || !self::checkCountryCaption($countryCode, $country)))
		{
			$countryCode = '';
		}

		$fields = array(
			'TYPE_ID' => $typeID,
			'ENTITY_TYPE_ID' => $entityTypeID,
			'ENTITY_ID' => $entityID,
			'ANCHOR_TYPE_ID' => $anchorTypeID,
			'ANCHOR_ID' => $anchorID,
			'ADDRESS_1' => isset($data['ADDRESS_1']) ? $data['ADDRESS_1'] : '',
			'ADDRESS_2' => isset($data['ADDRESS_2']) ? $data['ADDRESS_2'] : '',
			'CITY' => isset($data['CITY']) ? $data['CITY'] : '',
			'POSTAL_CODE' => isset($data['POSTAL_CODE']) ? $data['POSTAL_CODE'] : '',
			'REGION' => isset($data['REGION']) ? $data['REGION'] : '',
			'PROVINCE' => isset($data['PROVINCE']) ? $data['PROVINCE'] : '',
			'COUNTRY' => $country,
			'COUNTRY_CODE' => $countryCode
		);
		AddressTable::upsert($fields);

		//region Send event
		$event = new Main\Event('crm', 'OnAfterAddressRegister', array('fields' => $fields));
		$event->send();
		//endregion Send event
	}
	public static function unregister($entityTypeID, $entityID, $typeID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		if(!self::IsDefined($typeID))
		{
			throw new Main\ArgumentOutOfRangeException('typeID', self::First, self::Last);
		}

		$primaryFields = array('ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => $entityID, 'TYPE_ID' => $typeID);

		$event = new Main\Event('crm', 'OnAddressUnregister', array('fields' => $primaryFields));
		$event->send();

		$result = AddressTable::delete($primaryFields);

		//region Send event
		if ($result->isSuccess())
		{
			$event = new Main\Event('crm', 'OnAfterAddressUnregister', array('fields' => $primaryFields));
			$event->send();
		}
		//endregion Send event
	}

	public static function rebind($oldEntityTypeID, $oldEntityID, $newEntityTypeID, $newEntityID)
	{
		self::deleteByEntity($newEntityTypeID, $newEntityID);
		AddressTable::rebind($oldEntityTypeID, $oldEntityID, $newEntityTypeID, $newEntityID);
	}

	public static function deleteByEntity($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		$connection = Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		$tableName = AddressTable::getTableName();
		$conditionSql = implode(
			' AND ',
			array(
				$helper->prepareAssignment($tableName, 'ENTITY_TYPE_ID', $entityTypeID),
				$helper->prepareAssignment($tableName, 'ENTITY_ID', $entityID)
			)
		);
		$connection->queryExecute('DELETE FROM '.$tableName.' WHERE '.$conditionSql);
	}

	public static function getTypeInfos()
	{
		if(self::$typeInfos === null)
		{
			self::includeModuleFile();

			self::$typeInfos = array(
				self::Primary => array(
					'ID' => self::Primary,
					'DESCRIPTION' => GetMessage('CRM_ENTITY_ADDRESS_PRY')
				),
				self::Registered => array(
					'ID' => self::Registered,
					'DESCRIPTION' => GetMessage('CRM_ENTITY_ADDRESS_REG')
				)
			);
		}
		return self::$typeInfos;
	}

	/**
	 * Check if address is empty
	 * @param array $fields
	 * @return bool
	 */
	public static function isEmpty(array $fields)
	{
		return (!isset($fields['ADDRESS_1']) || $fields['ADDRESS_1'] === '')
			&& (!isset($fields['ADDRESS_2']) || $fields['ADDRESS_2'] === '')
			&& (!isset($fields['CITY']) || $fields['CITY'] === '')
			&& (!isset($fields['POSTAL_CODE']) || $fields['POSTAL_CODE'] === '')
			&& (!isset($fields['REGION']) || $fields['REGION'] === '')
			&& (!isset($fields['PROVINCE']) || $fields['PROVINCE'] === '')
			&& (!isset($fields['COUNTRY']) || $fields['COUNTRY'] === '');
	}

	/**
	 * Check if addresses are equals
	 * @param array $a First address.
	 * @param array $b Second address.
	 * @return bool
	 */
	public static function areEquals(array $a, array $b)
	{
		return (
			(isset($a['ADDRESS_1']) ? $a['ADDRESS_1'] : '') === (isset($b['ADDRESS_1']) ? $b['ADDRESS_1'] : '')
			&& (isset($a['ADDRESS_2']) ? $a['ADDRESS_2'] : '') === (isset($b['ADDRESS_2']) ? $b['ADDRESS_2'] : '')
			&& (isset($a['CITY']) ? $a['CITY'] : '') === (isset($b['CITY']) ? $b['CITY'] : '')
			&& (isset($a['POSTAL_CODE']) ? $a['POSTAL_CODE'] : '') === (isset($b['POSTAL_CODE']) ? $b['POSTAL_CODE'] : '')
			&& (isset($a['REGION']) ? $a['REGION'] : '') === (isset($b['REGION']) ? $b['REGION'] : '')
			&& (isset($a['PROVINCE']) ? $a['PROVINCE'] : '') === (isset($b['PROVINCE']) ? $b['PROVINCE'] : '')
			&& (isset($a['COUNTRY']) ? $a['COUNTRY'] : '') === (isset($b['COUNTRY']) ? $b['COUNTRY'] : '')
			&& (isset($a['COUNTRY_CODE']) ? $a['COUNTRY_CODE'] : '') === (isset($b['COUNTRY_CODE']) ? $b['COUNTRY_CODE'] : '')
		);
	}
	public static function getClientTypeInfos()
	{
		self::includeModuleFile();
		return array(
			array('id' => self::Primary, 'name' => GetMessage('CRM_ENTITY_ADDRESS_PRY')),
			array('id' => self::Registered, 'name' => GetMessage('CRM_ENTITY_ADDRESS_REG'))
		);
	}

	public static function getTypeDescription($typeID)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		if(!self::isDefined($typeID))
		{
			$typeID = self::Primary;
		}

		$typeInfos = self::getTypeInfos();
		return $typeInfos[$typeID]['DESCRIPTION'];
	}

	public static function getTypeLabels()
	{
		if(self::$typeLabels === null)
		{
			self::includeModuleFile();

			self::$typeLabels = array(
				self::Primary => GetMessage('CRM_ENTITY_FULL_ADDRESS'),
				self::Registered => GetMessage('CRM_ENTITY_FULL_REG_ADDRESS')
			);
		}
		return self::$typeLabels;
	}
	public static function getFullAddressLabel($typeID = 0)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		if(!self::isDefined($typeID))
		{
			$typeID = self::Primary;
		}

		$labels = self::getTypeLabels();
		return isset($labels[$typeID]) ? $labels[$typeID] : "[{$typeID}]";
	}
	public static function getLabels($typeID = 0)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		if(!self::isDefined($typeID))
		{
			$typeID = self::Primary;
		}

		if(!isset(self::$labels[$typeID]))
		{
			self::includeModuleFile();

			if($typeID === self::Registered)
			{
				self::$labels[self::Registered] = array(
					//For backward compatibility
					'ADDRESS' => GetMessage('CRM_ENTITY_REG_ADDRESS_1'),
					'ADDRESS_1' => GetMessage('CRM_ENTITY_REG_ADDRESS_1'),
					'ADDRESS_2' => GetMessage('CRM_ENTITY_REG_ADDRESS_2'),
					'CITY' => GetMessage('CRM_ENTITY_REG_ADDRESS_CITY'),
					'POSTAL_CODE' => GetMessage('CRM_ENTITY_REG_ADDRESS_POSTAL_CODE'),
					'REGION' => GetMessage('CRM_ENTITY_REG_ADDRESS_REGION'),
					'PROVINCE' => GetMessage('CRM_ENTITY_REG_ADDRESS_PROVINCE'),
					'COUNTRY' => GetMessage('CRM_ENTITY_REG_ADDRESS_COUNTRY')
				);
			}
			else
			{
				self::$labels[$typeID] = array(
					//For backward compatibility
					'ADDRESS' => GetMessage('CRM_ENTITY_ADDRESS_1'),
					'ADDRESS_1' => GetMessage('CRM_ENTITY_ADDRESS_1'),
					'ADDRESS_2' => GetMessage('CRM_ENTITY_ADDRESS_2'),
					'CITY' => GetMessage('CRM_ENTITY_ADDRESS_CITY'),
					'POSTAL_CODE' => GetMessage('CRM_ENTITY_ADDRESS_POSTAL_CODE'),
					'REGION' => GetMessage('CRM_ENTITY_ADDRESS_REGION'),
					'PROVINCE' => GetMessage('CRM_ENTITY_ADDRESS_PROVINCE'),
					'COUNTRY' => GetMessage('CRM_ENTITY_ADDRESS_COUNTRY')
				);
			}
		}
		return self::$labels[$typeID];
	}
	public static function getLabel($fieldName, $typeID = 0)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		if(!self::isDefined($typeID))
		{
			$typeID = self::Primary;
		}

		$labels = self::getLabels($typeID);
		return isset($labels[$fieldName]) ? $labels[$fieldName] : $fieldName;
	}
	public static function getShortLabels($typeID = 0)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		if(!self::isDefined($typeID))
		{
			$typeID = self::Primary;
		}

		if(!isset(self::$shortLabels[$typeID]))
		{
			self::includeModuleFile();

			if($typeID === self::Registered)
			{
				self::$shortLabels[self::Registered] = array(
					//For backward compatibility
					'ADDRESS' => GetMessage('CRM_ENTITY_SHORT_REG_ADDRESS_1'),
					'ADDRESS_1' => GetMessage('CRM_ENTITY_SHORT_REG_ADDRESS_1'),
					'ADDRESS_2' => GetMessage('CRM_ENTITY_SHORT_REG_ADDRESS_2'),
					'CITY' => GetMessage('CRM_ENTITY_SHORT_REG_ADDRESS_CITY'),
					'POSTAL_CODE' => GetMessage('CRM_ENTITY_SHORT_REG_ADDRESS_POSTAL_CODE'),
					'REGION' => GetMessage('CRM_ENTITY_SHORT_REG_ADDRESS_REGION'),
					'PROVINCE' => GetMessage('CRM_ENTITY_SHORT_REG_ADDRESS_PROVINCE'),
					'COUNTRY' => GetMessage('CRM_ENTITY_SHORT_REG_ADDRESS_COUNTRY')
				);
			}
			else
			{
				self::$shortLabels[$typeID] = array(
					//For backward compatibility
					'ADDRESS' => GetMessage('CRM_ENTITY_SHORT_ADDRESS_1'),
					'ADDRESS_1' => GetMessage('CRM_ENTITY_SHORT_ADDRESS_1'),
					'ADDRESS_2' => GetMessage('CRM_ENTITY_SHORT_ADDRESS_2'),
					'CITY' => GetMessage('CRM_ENTITY_SHORT_ADDRESS_CITY'),
					'POSTAL_CODE' => GetMessage('CRM_ENTITY_SHORT_ADDRESS_POSTAL_CODE'),
					'REGION' => GetMessage('CRM_ENTITY_SHORT_ADDRESS_REGION'),
					'PROVINCE' => GetMessage('CRM_ENTITY_SHORT_ADDRESS_PROVINCE'),
					'COUNTRY' => GetMessage('CRM_ENTITY_SHORT_ADDRESS_COUNTRY')
				);
			}
		}
		return self::$shortLabels[$typeID];
	}

	public static function getCountryByCode($code)
	{
		if (!Main\Loader::includeModule('sale'))
		{
			return null;
		}

		$dbResult = Sale\Location\LocationTable::getList(
			array(
				'filter' => array(
					'=TYPE.CODE' => 'COUNTRY',
					'=NAME.LANGUAGE_ID' => LANGUAGE_ID,
					'=CODE' => $code
				),
				'select' => array('CODE', 'CAPTION' => 'NAME.NAME')
			)
		);

		$fields = $dbResult->fetch();
		return is_array($fields)  ? $fields : null;
	}
	public static function getCountries(array $filter = null)
	{
		if (!Main\Loader::includeModule('sale'))
		{
			return array();
		}

		$listFilter = array(
			'=TYPE.CODE' => 'COUNTRY',
			'=NAME.LANGUAGE_ID' => LANGUAGE_ID
		);

		if(is_array($filter) && !empty($filter))
		{
			$caption = isset($filter['CAPTION']) ? $filter['CAPTION'] : '';
			if($caption !== '')
			{
				$listFilter['%NAME.NAME'] = $caption;
			}
		}

		$dbResult = Sale\Location\LocationTable::getList(
			array(
				'filter' => $listFilter,
				'select' => array('CODE', 'CAPTION' => 'NAME.NAME')
			)
		);

		$result = array();
		while($fields = $dbResult->fetch())
		{
			$result[] = $fields;
		}

		return $result;
	}

	public static function resolveFieldNames(array $fields, array $options = null)
	{
		if(empty($fields))
		{
			return array();
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$typeID = isset($options['TYPE_ID']) ? $options['TYPE_ID'] : EntityAddress::Undefined;
		if(!EntityAddress::isDefined($typeID))
		{
			$typeID = EntityAddress::Primary;
		}

		$result = array();
		$map = array_flip(static::getFieldMap($typeID));
		foreach($fields as $name)
		{
			if(isset($map[$name]))
			{
				$result[] = $map[$name];
			}
		}
		return $result;
	}

	public static function resolveEntityFieldName($fieldName, array $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$typeID = isset($options['TYPE_ID']) ? $options['TYPE_ID'] : EntityAddress::Undefined;
		if(!EntityAddress::isDefined($typeID))
		{
			$typeID = EntityAddress::Primary;
		}

		$map = static::getFieldMap($typeID);
		return isset($map[$fieldName]) ? $map[$fieldName] : $fieldName;
	}

	public static function prepareChangeEvents(array $original, array $modified, $typeID = 0, array $options = null)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		if(!self::isDefined($typeID))
		{
			$typeID = self::Primary;
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$events = array();

		$original = static::mapEntityFields(
			$original,
			array('TYPE_ID' => $typeID, 'SKIP_EMPTY' => false, 'SKIP_NOT_ASSIGNED' => false)
		);
		$modified = static::mapEntityFields(
			$modified,
			array('TYPE_ID' => $typeID, 'SKIP_EMPTY' => false, 'SKIP_NOT_ASSIGNED' => true)
		);

		$fieldNames = isset($options['FIELDS']) && is_array($options['FIELDS'])
			? static::resolveFieldNames($options['FIELDS'], array('TYPE_ID' => $typeID)) : array();

		if(empty($fieldNames))
		{
			$fieldNames = array(
				'ADDRESS_1', 'ADDRESS_2', 'CITY',
				'POSTAL_CODE', 'REGION', 'PROVINCE', 'COUNTRY'
			);
		}

		$anyDefined = false;
		foreach($fieldNames as $name)
		{
			$anyDefined = isset($modified[$name]);
			if($anyDefined)
			{
				break;
			}
		}

		if($anyDefined)
		{
			foreach($fieldNames as $name)
			{
				self::prepareFieldChangeEvent($name, $events,  $original, $modified, $typeID);
			}
		}

		return $events;
	}

	public static function prepareFilterSql($entityTypeID, $typeID, array $filter, $tableAlias)
	{
		$query = new Main\Entity\Query(AddressTable::getEntity());
		$query->addSelect('ANCHOR_ID');
		$query->addFilter('=ANCHOR_TYPE_ID', $entityTypeID);
		$query->addFilter('=TYPE_ID', $typeID);
		foreach($filter as $fieldName => $value)
		{
			$value = trim($value);
			if($value !== '')
			{
				$query->addFilter($fieldName, $value);
			}
		}

		$sql = $query->getQuery();
		return "{$tableAlias}.ID IN ({$sql})";
	}

	public static function prepareFilterJoinSql($entityTypeID, $typeID, array $filter, $tableAlias)
	{
		$connection = Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		$conditions = array();
		foreach($filter as $fieldName => $value)
		{
			$value = trim($value);
			if($value !== '')
			{
				$value = $helper->forSql(strtoupper($value));
				$conditions[] = "{$fieldName} LIKE '{$value}'";
			}
		}

		$addrAlias = "{$tableAlias}_ADDR{$typeID}";
		return 'INNER JOIN(SELECT DISTINCT ANCHOR_ID ID FROM b_crm_addr '
			.'WHERE TYPE_ID = '.$typeID.' AND ANCHOR_TYPE_ID = '.$entityTypeID
			.' AND '.implode(' AND ', $conditions).') '.$addrAlias.' ON '.$addrAlias.'.ID = '.$tableAlias.'.ID';
	}

	public static function getEntityList($typeID, array $sort, array $filter, $navParams = false, array $options = null)
	{
		$typeID = (int)$typeID;
		$sort = static::mapEntityFields($sort, array('TYPE_ID' => $typeID, 'SKIP_EMPTY' => true));

		$entityTypeID = static::getEntityTypeID();
		$lb = static::createEntityListBuilder();


		$fields = $lb->GetFields();
		$entityAlias = $lb->GetTableAlias();
		$join = 'INNER JOIN b_crm_addr ADDR_S ON '.$entityAlias.'.ID = ADDR_S.ENTITY_ID AND ADDR_S.TYPE_ID = '.$typeID.' AND ADDR_S.ENTITY_TYPE_ID = '.$entityTypeID;

		$listSort = array();
		foreach($sort as $fieldName => $order)
		{
			$fieldKey = "ADDR_S_{$fieldName}";
			$fields[$fieldKey] = array('FIELD' => 'ADDR_S.'.$fieldName, 'TYPE' => 'string', 'FROM'=> $join);
			$listSort[$fieldKey] = $order;
		}
		$fields['ADDR_ENTITY_ID'] = array('FIELD' => 'ADDR_S.ENTITY_ID', 'TYPE' => 'string', 'FROM'=> $join);
		$listSort['ADDR_ENTITY_ID'] = array_shift(array_slice($listSort, 0, 1));
		$lb->SetFields($fields);

		if($options === null)
		{
			$options = array();
		}
		$options = array_merge($options, array('PERMISSION_SQL_TYPE' => 'FROM', 'PERMISSION_SQL_UNION' => 'DISTINCT'));
		return $lb->Prepare($listSort, $filter, false, $navParams, array('ID'), $options);
	}

	public static function getByOwner($typeID, $ownerTypeID, $ownerID)
	{
		$typeID = (int)$typeID;
		$ownerTypeID = (int)$ownerTypeID;
		$ownerID = (int)$ownerID;

		$dbResult = AddressTable::getList(
			array('filter' => array('=TYPE_ID' => $typeID, '=ENTITY_TYPE_ID' => $ownerTypeID, '=ENTITY_ID' => $ownerID))
		);
		$ary = $dbResult->fetch();
		return is_array($ary) ? $ary : null;
	}

	public static function getListByOwner($ownerTypeID, $ownerID)
	{
		$ownerTypeID = (int)$ownerTypeID;
		$ownerID = (int)$ownerID;

		$dbResult = AddressTable::getList(
			array('filter' => array('ENTITY_TYPE_ID' => $ownerTypeID, 'ENTITY_ID' => $ownerID))
		);

		$results = array();
		while($ary = $dbResult->fetch())
		{
			$typeID = (int)$ary['TYPE_ID'];
			$results[$typeID] = array(
				'ADDRESS_1' => isset($ary['ADDRESS_1']) ? $ary['ADDRESS_1'] : '',
				'ADDRESS_2' => isset($ary['ADDRESS_2']) ? $ary['ADDRESS_2'] : '',
				'CITY' => isset($ary['CITY']) ? $ary['CITY'] : '',
				'POSTAL_CODE' => isset($ary['POSTAL_CODE']) ? $ary['POSTAL_CODE'] : '',
				'REGION' => isset($ary['REGION']) ? $ary['REGION'] : '',
				'PROVINCE' => isset($ary['PROVINCE']) ? $ary['PROVINCE'] : '',
				'COUNTRY' => isset($ary['COUNTRY']) ? $ary['COUNTRY'] : '',
				'COUNTRY_CODE' => isset($ary['COUNTRY_CODE']) ? $ary['COUNTRY_CODE'] : ''
			);
		}
		return $results;
	}

	protected static function prepareFieldChangeEvent($fieldName, array &$events, array $original, array $modified, $typeID = 0)
	{
		$originalValue = isset($original[$fieldName]) ? $original[$fieldName] : '';
		$modifiedValue = isset($modified[$fieldName]) ? $modified[$fieldName] : '';

		if($originalValue === $modifiedValue)
		{
			return false;
		}

		$events[] = array(
			'ENTITY_FIELD' => static::resolveEntityFieldName($fieldName),
			'EVENT_NAME' => self::getLabel($fieldName, $typeID),
			'EVENT_TEXT_1' => $originalValue !== '' ? $originalValue : GetMessage('CRM_ENTITY_ADDRESS_CHANGE_EVENT_EMPTY'),
			'EVENT_TEXT_2' => $modifiedValue !== '' ? $modifiedValue : GetMessage('CRM_ENTITY_ADDRESS_CHANGE_EVENT_EMPTY'),
		);
		return true;
	}

	protected static function includeModuleFile()
	{
		if(self::$messagesLoaded)
		{
			return;
		}

		Main\Localization\Loc::loadMessages(__FILE__);
		self::$messagesLoaded = true;
	}

	public static function checkCreatePermissionOwnerEntity($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if ($entityTypeID === \CCrmOwnerType::Requisite ||
				$entityTypeID === \CCrmOwnerType::Company ||
				$entityTypeID === \CCrmOwnerType::Contact ||
				$entityTypeID === \CCrmOwnerType::Lead
		)
		{
			if ($entityTypeID === \CCrmOwnerType::Requisite)
			{
				$r = EntityRequisite::getOwnerEntityById($entityID);
				$entityTypeID = intval($r['ENTITY_TYPE_ID']);
			}

			$entityType = \CCrmOwnerType::ResolveName($entityTypeID);
			return \CCrmAuthorizationHelper::CheckCreatePermission($entityType);
		}

		return false;
	}

	public static function checkUpdatePermissionOwnerEntity($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if ($entityTypeID === \CCrmOwnerType::Requisite ||
				$entityTypeID === \CCrmOwnerType::Company ||
				$entityTypeID === \CCrmOwnerType::Contact ||
				$entityTypeID === \CCrmOwnerType::Lead
		)
		{
			if ($entityTypeID === \CCrmOwnerType::Requisite)
			{
				$r = EntityRequisite::getOwnerEntityById($entityID);
				$entityTypeID = intval($r['ENTITY_TYPE_ID']);
				$entityID = intval($r['ENTITY_ID']);
			}

			$entityType = \CCrmOwnerType::ResolveName($entityTypeID);
			return \CCrmAuthorizationHelper::CheckUpdatePermission($entityType, $entityID);
		}

		return false;
	}

	public static function checkDeletePermissionOwnerEntity($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if ($entityTypeID === \CCrmOwnerType::Requisite ||
				$entityTypeID === \CCrmOwnerType::Company ||
				$entityTypeID === \CCrmOwnerType::Contact ||
				$entityTypeID === \CCrmOwnerType::Lead
		)
		{
			if ($entityTypeID === \CCrmOwnerType::Requisite)
			{
				$r = EntityRequisite::getOwnerEntityById($entityID);
				$entityTypeID = intval($r['ENTITY_TYPE_ID']);
				$entityID = intval($r['ENTITY_ID']);
			}

			$entityType = \CCrmOwnerType::ResolveName($entityTypeID);
			return \CCrmAuthorizationHelper::CheckDeletePermission($entityType, $entityID);
		}

		return false;
	}

	public static function checkReadPermissionOwnerEntity($entityTypeID = 0, $entityID = 0)
	{
		if(intval($entityTypeID)<=0 && intval($entityID) <= 0)
		{
			return (EntityRequisite::checkReadPermissionOwnerEntity() &&
					\CCrmCompany::CheckReadPermission(0) &&
					\CCrmContact::CheckReadPermission(0) &&
					\CCrmLead::CheckReadPermission(0));
		}

		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if ($entityTypeID === \CCrmOwnerType::Requisite ||
				$entityTypeID === \CCrmOwnerType::Company ||
				$entityTypeID === \CCrmOwnerType::Contact ||
				$entityTypeID === \CCrmOwnerType::Lead
		)
		{
			if ($entityTypeID === \CCrmOwnerType::Requisite)
			{
				$r = EntityRequisite::getOwnerEntityById($entityID);
				$entityTypeID = $r['ENTITY_TYPE_ID'];
				$entityID = $r['ENTITY_ID'];
			}

			$entityType = \CCrmOwnerType::ResolveName($entityTypeID);
			return \CCrmAuthorizationHelper::CheckReadPermission($entityType, $entityID);
		}

		return false;
	}

}