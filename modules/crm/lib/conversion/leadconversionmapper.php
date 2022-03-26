<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;
use Bitrix\Crm\UtmTable;
use Bitrix\Crm\Synchronization\UserFieldSynchronizer;

class LeadConversionMapper extends EntityConversionMapper
{
	/** @var array */
	protected $srcFields = null;
	/** @var array */
	protected $srcMultiFields = null;
	public function __construct($srcEntityID)
	{
		parent::__construct(\CCrmOwnerType::Lead, $srcEntityID);
	}
	/**
	 * Get source fields
	 * @return array
	 */
	public function getSourceFields()
	{
		if($this->srcFields !== null)
		{
			return $this->srcFields;
		}

		$dbResult = \CCrmLead::GetListEx(
			array(),
			array('=ID'=> $this->srcEntityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('*', 'UF_*')
		);

		$result = $dbResult->Fetch();
		return ($this->srcFields = is_array($result) ? $result : array());
	}
	public function getSourceMultiFields()
	{
		if($this->srcMultiFields !== null)
		{
			return $this->srcMultiFields;
		}

		$this->srcMultiFields = array();
		$dbResult = \CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => $this->srcEntityID)
		);

		while($multiField = $dbResult->Fetch())
		{
			$typeID = $multiField['TYPE_ID'];
			if(!$this->srcMultiFields[$typeID])
			{
				$this->srcMultiFields[$typeID] = array();
			}

			$index = count($this->srcMultiFields[$typeID]);
			$this->srcMultiFields[$typeID]["n{$index}"] = array(
				'VALUE' => $multiField['VALUE'],
				'VALUE_TYPE' => $multiField['VALUE_TYPE']
			);
		}

		return $this->srcMultiFields;
	}

	/**
	 * Create conversion map for destination entity type
	 * @static
	 * @param int $entityTypeID Destination Entity Type ID
	 * @return EntityConversionMap
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 */
	public static function createMap($entityTypeID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('dstEntityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if($entityTypeID !== \CCrmOwnerType::Contact
			&& $entityTypeID !== \CCrmOwnerType::Company
			&& $entityTypeID !== \CCrmOwnerType::Deal)
		{
			$dstEntityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$dstEntityTypeName}' is not supported in current context");
		}

		$map = new EntityConversionMap(\CCrmOwnerType::Lead, $entityTypeID);
		if($entityTypeID === \CCrmOwnerType::Contact)
		{
			//region Contact Map Static Field Bindings
			$map->createItem('HONORIFIC');
			$map->createItem('NAME');
			$map->createItem('SECOND_NAME');
			$map->createItem('LAST_NAME');
			$map->createItem('BIRTHDATE');
			$map->createItem('POST');
			$map->createItem('COMMENTS');
			$map->createItem('OPENED');
			$map->createItem('SOURCE_ID');
			$map->createItem('SOURCE_DESCRIPTION');
			$map->createItem('ADDRESS');
			$map->createItem('ADDRESS_2');
			$map->createItem('ADDRESS_CITY');
			$map->createItem('ADDRESS_POSTAL_CODE');
			$map->createItem('ADDRESS_REGION');
			$map->createItem('ADDRESS_PROVINCE');
			$map->createItem('ADDRESS_COUNTRY');
			$map->createItem('ADDRESS_COUNTRY_CODE');
			$map->createItem('PHONE');
			$map->createItem('EMAIL');
			$map->createItem('WEB');
			$map->createItem('IM');
			$map->createItem('LINK');
			$map->createItem('ASSIGNED_BY_ID');
			$map->createItem('ORIGINATOR_ID');
			$map->createItem('ORIGIN_ID');
			$map->createItem('FACE_ID');
			//endregion
			//region Contact Map User Field Bindings
			$intersections = UserFieldSynchronizer::getIntersection(\CCrmOwnerType::Lead, \CCrmOwnerType::Contact);
			foreach($intersections as $intersection)
			{
				$map->createItem($intersection['SRC_FIELD_NAME'], $intersection['DST_FIELD_NAME']);
			}
			//endregion
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			//region Company Map Static Field Bindings
			$map->createItem('COMPANY_TITLE', 'TITLE', array('ALT_SRC_FIELD_IDS' => array('TITLE')));
			$map->createItem('COMMENTS');
			$map->createItem('OPENED');
			$map->createItem('ADDRESS');
			$map->createItem('ADDRESS_2');
			$map->createItem('ADDRESS_CITY');
			$map->createItem('ADDRESS_POSTAL_CODE');
			$map->createItem('ADDRESS_REGION');
			$map->createItem('ADDRESS_PROVINCE');
			$map->createItem('ADDRESS_COUNTRY');
			$map->createItem('ADDRESS_COUNTRY_CODE');
			$map->createItem('PHONE');
			$map->createItem('EMAIL');
			$map->createItem('WEB');
			$map->createItem('IM');
			$map->createItem('LINK');
			$map->createItem('ASSIGNED_BY_ID');
			$map->createItem('ORIGINATOR_ID');
			$map->createItem('ORIGIN_ID');
			//endregion
			//region Company Map User Field Bindings
			$intersections = UserFieldSynchronizer::getIntersection(\CCrmOwnerType::Lead, \CCrmOwnerType::Company);
			foreach($intersections as $intersection)
			{
				$map->createItem($intersection['SRC_FIELD_NAME'], $intersection['DST_FIELD_NAME']
				);
			}
			//endregion
		}
		elseif($entityTypeID === \CCrmOwnerType::Deal)
		{
			//region Deal Map Static Field Bindings
			$map->createItem('TITLE');
			$map->createItem('OPPORTUNITY');
			$map->createItem('CURRENCY_ID');
			$map->createItem('COMMENTS');
			$map->createItem('OPENED');
			$map->createItem('SOURCE_ID');
			$map->createItem('SOURCE_DESCRIPTION');
			$map->createItem('ADDRESS');
			$map->createItem('ADDRESS_2');
			$map->createItem('ADDRESS_CITY');
			$map->createItem('ADDRESS_POSTAL_CODE');
			$map->createItem('ADDRESS_REGION');
			$map->createItem('ADDRESS_PROVINCE');
			$map->createItem('ADDRESS_COUNTRY');
			$map->createItem('ADDRESS_COUNTRY_CODE');
			$map->createItem('PRODUCT_ROWS');
			$map->createItem('ASSIGNED_BY_ID');
			$map->createItem('ORIGINATOR_ID');
			$map->createItem('ORIGIN_ID');
			$map->createItem('WEBFORM_ID');
			//endregion
			//region Deal Map User Field Bindings
			$intersections = UserFieldSynchronizer::getIntersection(\CCrmOwnerType::Lead, \CCrmOwnerType::Deal);
			foreach($intersections as $intersection)
			{
				$map->createItem($intersection['SRC_FIELD_NAME'], $intersection['DST_FIELD_NAME']);
			}
			//endregion
		}

		//region UTM Fields
		foreach(UtmTable::getCodeList() as $fieldID)
		{
			$map->createItem($fieldID);
		}
		//endregion

		return $map;
	}
	/**
	 * Map entity fields to specified type.
	 * @param EntityConversionMap $map Entity map.
	 * @param array|null $options Mapping options.
	 * @return array
	 */
	public function map(EntityConversionMap $map, array $options = null)
	{
		$srcFields = $this->getSourceFields();
		if(empty($srcFields))
		{
			return array();
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$multiFieldKeys = array('PHONE' => true, 'EMAIL' => true, 'WEB' => true, 'IM' => true, 'LINK' => true);

		$dstFields = array();
		$dstEntityTypeID = $map->getDestinationEntityTypeID();
		foreach($map->getItems() as $item)
		{
			$dstFieldID = $item->getDestinationField();

			//Skip empty binding
			if($dstFieldID === '-')
			{
				continue;
			}

			$srcFieldID = $item->getSourceField();
			if((!isset($srcFields[$srcFieldID]) || $srcFields[$srcFieldID] === '')
				&& !isset($multiFieldKeys[$srcFieldID]) && $srcFieldID !== 'PRODUCT_ROWS')
			{
				$altSrcFieldID = '';
				foreach($item->getAlternativeSourceFields() as $fieldID)
				{
					if(isset($srcFields[$fieldID]))
					{
						$altSrcFieldID = $fieldID;
						break;
					}
				}

				if($altSrcFieldID !== '')
				{
					$srcFieldID = $altSrcFieldID;
				}
			}

			if($dstFieldID === '')
			{
				$dstFieldID = $srcFieldID;
			}

			if(mb_strpos($srcFieldID, 'UF_') === 0 && mb_strpos($dstFieldID, 'UF_') === 0)
			{
				self::mapUserField(\CCrmOwnerType::Lead, $srcFieldID, $srcFields, $dstEntityTypeID, $dstFieldID, $dstFields, $options);
			}
			elseif(isset($multiFieldKeys[$srcFieldID]))
			{
				$multifields = $this->getSourceMultiFields();
				if(isset($multifields[$dstFieldID]))
				{
					if(!isset($dstFields['FM']))
					{
						$dstFields['FM'] = array();
					}
					$dstFields['FM'][$dstFieldID] = $multifields[$dstFieldID];
				}
			}
			elseif($srcFieldID === 'PRODUCT_ROWS')
			{
				if($dstEntityTypeID === \CCrmOwnerType::Deal)
				{
					$productRows = \CCrmLead::LoadProductRows($this->srcEntityID);
					if(count($productRows) > 0)
					{
						$entityProductRows = array();
						$entityDbResult = \CCrmDeal::GetListEx(
							array(),
							array('=LEAD_ID' => $this->srcEntityID, 'CHECK_PERMISSIONS' => 'N'),
							false,
							false,
							array('ID')
						);
						while($entityFields = $entityDbResult->Fetch())
						{
							$entityProductRows[] = \CCrmDeal::LoadProductRows((int)$entityFields['ID']);
						}
						$dstFields[$dstFieldID] = \CCrmProductRow::GetDiff(array($productRows), $entityProductRows);
					}
				}
			}
			elseif(isset($srcFields[$srcFieldID]))
			{
				$dstFields[$dstFieldID] = $srcFields[$srcFieldID];
			}
		}

		$initData = isset($options['INIT_DATA']) && is_array($options['INIT_DATA'])
			? $options['INIT_DATA'] : array();

		//region Setup fields by default
		if($dstEntityTypeID === \CCrmOwnerType::Contact)
		{
			$name = isset($dstFields['NAME']) ? $dstFields['NAME'] : '';
			$secondName = isset($dstFields['SECOND_NAME']) ? $dstFields['SECOND_NAME'] : '';
			$lastName = isset($dstFields['LAST_NAME']) ? $dstFields['LAST_NAME'] : '';

			if($name === '' && $secondName === '' && $lastName === '' && isset($initData['defaultName']))
			{
				$dstFields['NAME'] = $initData['defaultName'];
			}
		}
		//endregion

		if(!empty($dstFields))
		{
			$dstFields['LEAD_ID'] = $this->srcEntityID;

			if($dstEntityTypeID === \CCrmOwnerType::Deal)
			{
				if(isset($dstFields['PRODUCT_ROWS'])
					&& is_array($dstFields['PRODUCT_ROWS'])
					&& !empty($dstFields['PRODUCT_ROWS']))
				{
					$totalInfo = \CCrmProductRow::CalculateTotalInfo('D', 0, false, $dstFields, $dstFields['PRODUCT_ROWS']);
					$dstFields['OPPORTUNITY'] = isset($totalInfo['OPPORTUNITY']) ? $totalInfo['OPPORTUNITY'] : 1.0;
					$dstFields['TAX_VALUE'] = isset($totalInfo['TAX_VALUE']) ? $totalInfo['TAX_VALUE'] : 0.0;
				}

				$dealTypeList = \CCrmStatus::GetStatusList('DEAL_TYPE');
				if(!empty($dealTypeList))
				{
					$dstFields['TYPE_ID'] = current(array_keys($dealTypeList));
				}

				if(isset($initData['categoryId']) && $initData['categoryId'] > 0)
				{
					$dstFields['CATEGORY_ID'] = (int)$initData['categoryId'];
				}
			}

			if(!(isset($options['DISABLE_USER_FIELD_INIT']) && $options['DISABLE_USER_FIELD_INIT'] === true))
			{
				self::initializeUserFields($dstEntityTypeID, $dstFields);
			}
		}

		return $dstFields;
	}
}