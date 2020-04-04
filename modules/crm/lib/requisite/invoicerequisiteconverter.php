<?php
namespace Bitrix\Crm\Requisite;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Format\AddressSeparator;
use Bitrix\Crm\Format\EntityAddressFormatter;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Requisite\EntityLink;

class InvoiceRequisiteConverter extends EntityRequisiteConverter
{
	/** @var int */
	private $personTypeID = 0;
	/** @var int */
	private $presetID = 0;
	/** @var EntityPreset|null */
	private $presetEntity = null;
	/** @var array|null  */
	private $presetFields = null;
	/** @var array|null  */
	private $presetFieldNames = null;
	/** @var array|null  */
	private $propertyInfos = null;
	/** @var array|null  */
	private $propertyMap = null;
	/** @var bool */
	private $enablePermissionCheck = false;
	/** @var int */
	private $limit = 1000;

	/**
	 * @param int $entityTypeID Entity Type ID.
	 * @param int $presetID Preset ID.
	 * @param bool|false $enablePermissionCheck Permission check flag.
	 * @param int $limit Row limit.
	 * @throws Main\NotSupportedException
	 */
	public function __construct($entityTypeID, $presetID, $enablePermissionCheck = true, $limit = 1000)
	{
		parent::__construct($entityTypeID);

		if($this->entityTypeID === \CCrmOwnerType::Company)
		{
			$this->personTypeID = (int)\CCrmInvoice::GetCompanyPersonTypeID();
		}
		elseif($this->entityTypeID === \CCrmOwnerType::Contact)
		{
			$this->personTypeID = (int)\CCrmInvoice::GetContactPersonTypeID();
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($this->entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		$this->presetID = $presetID;
		$this->presetEntity = new EntityPreset();
		$this->presetFields = $this->presetEntity->getById($this->presetID);
		if (!is_array($this->presetFields))
			$this->presetFields = array();
		$this->presetFieldNames = array();
		if (is_array($this->presetFields['SETTINGS']))
		{
			$this->presetFieldNames = $this->presetEntity->extractFieldNames(
				$this->presetEntity->settingsGetFields($this->presetFields['SETTINGS'])
			);
		}

		$this->enablePermissionCheck = $enablePermissionCheck;
		$this->limit = $limit;
	}
	/**
	 * Get preset field names
	 * @return array
	 */
	protected function getPresetFieldNames()
	{
		return $this->presetFieldNames;
	}
	/**
	 * Get preset keys
	 * @return array
	 */
	protected function getPresetKeys()
	{
		$names = $this->getPresetFieldNames();
		return array_combine($names, array_fill(0, count($names), true));
	}
	/**
	 * Get property infos
	 * @return array
	 */
	protected function getPropertyInfos()
	{
		if($this->propertyInfos === null)
		{
			$infos = \CCrmInvoice::GetPropertiesInfo($this->personTypeID);
			$this->propertyInfos = is_array($infos) && isset($infos[$this->personTypeID])
				? $infos[$this->personTypeID] : array();
		}
		return $this->propertyInfos;
	}
	/**
	 * Get preset binding count
	 * @return int
	 */
	protected function getPresetBindingCount()
	{
		return count($this->getPresetBindings());
	}
	/**
	 * Get preset bindings
	 * @return array
	 */
	protected function getPresetBindings()
	{
		$bindings = array();
		$presetKeys = $this->getPresetKeys();

		if($this->entityTypeID === \CCrmOwnerType::Company)
		{
			$propertyInfos = $this->getPropertyInfos();
			if(isset($presetKeys[EntityRequisite::COMPANY_FULL_NAME]))
			{
				$bindings['name'] = array(
					'propertyKey' => isset($propertyInfos['COMPANY_NAME']) ? 'COMPANY_NAME' : 'COMPANY',
					'requisiteName' => EntityRequisite::COMPANY_FULL_NAME
				);
			}
			elseif(isset($presetKeys[EntityRequisite::COMPANY_NAME]))
			{
				$bindings['name'] = array(
					'propertyKey' => isset($propertyInfos['COMPANY_NAME']) ? 'COMPANY_NAME' : 'COMPANY',
					'requisiteName' => EntityRequisite::COMPANY_NAME
				);
			}

			if(isset($presetKeys[EntityRequisite::INN]))
			{
				$bindings['inn'] = array('propertyKey' => 'INN', 'requisiteName' => EntityRequisite::INN);
			}

			if(isset($presetKeys[EntityRequisite::KPP]))
			{
				$bindings['kpp'] = array('propertyKey' => 'KPP', 'requisiteName' => EntityRequisite::KPP);
			}

			if(isset($presetKeys[EntityRequisite::ADDRESS]))
			{
				$bindings['address'] = array(
					'propertyKey' => 'COMPANY_ADR',
					'requisiteName' => EntityRequisite::ADDRESS,
					'type' => 'address',
					'params' => array('address_type' => EntityAddress::Registered)
				);
			}
		}
		elseif($this->entityTypeID === \CCrmOwnerType::Contact)
		{
			if(isset($presetKeys[EntityRequisite::ADDRESS]))
			{
				$bindings['address'] = array(
					'propertyKey' => 'ADDRESS',
					'requisiteName' => EntityRequisite::ADDRESS,
					'type' => 'address',
					'params' => array('address_type' => EntityAddress::Primary)
				);
			}
		}

		return $bindings;
	}
	/**
	 * Get property map item count
	 * @return int
	 */
	protected function getPropertyMapCount()
	{
		return count($this->getPropertyMap());
	}
	/**
	 * Get property map
	 * @return array
	 */
	protected function getPropertyMap()
	{
		if($this->propertyMap === null)
		{
			$propertyInfos = $this->getPropertyInfos();
			$bindings = $this->getPresetBindings();
			$this->propertyMap = array();
			foreach($bindings as $key => $binding)
			{
				$propertyKey = $binding['propertyKey'];
				if(isset($propertyInfos[$propertyKey]) && isset($propertyInfos[$propertyKey]['ID']))
				{
					$binding['propertyID'] = "PR_INVOICE_{$propertyInfos[$propertyKey]['ID']}";
					$this->propertyMap[$key] = $binding;
				}
			}
		}
		return $this->propertyMap;
	}
	/**
	 * Remove from text all non-alphanumeric characters.
	 * @param $str Source string;
	 * @return string
	 */
	private static function getAlphanum($str)
	{
		return preg_replace('/[^[:alnum:]]/'.BX_UTF_PCRE_MODIFIER, '', $str);
	}
	/**
	 * Prepare requisite comparison key.
	 * @param array $properties Source properties.
	 * @return string
	 */
	private function prepareRequisiteKey(array $properties)
	{
		$propertyMap = $this->getPropertyMap();
		if($this->entityTypeID === \CCrmOwnerType::Company)
		{
			$names = array('inn', 'name');
		}
		elseif($this->entityTypeID === \CCrmOwnerType::Contact)
		{
			$names = array('address');
		}
		else
		{
			$names = array();
		}

		foreach($names as $name)
		{
			if(!isset($propertyMap[$name]))
			{
				continue;
			}

			$propertyID = $propertyMap[$name]['propertyID'];
			$value = isset($properties[$propertyID]) && isset($properties[$propertyID]['VALUE'])
				? $properties[$propertyID]['VALUE'] : '';

			if($value === '')
			{
				continue;
			}

			$key = self::getAlphanum($value);
			if($key !== '')
			{
				return strtoupper($key);
			}
		}
		return '';
	}
	/**
	 * Prepare requisite fields.
	 * @param array $properties Source properties.
	 * @param int $entityID Entity ID.
	 * @param array $fields Destination fields.
	 * @return void
	 */
	private function prepareRequisiteFields(array $properties, $entityID, array &$fields)
	{
		$propertyMap = $this->getPropertyMap();
		foreach($propertyMap as $name => $binding)
		{
			$ID = $binding['propertyID'];
			$name = $binding['requisiteName'];

			$value = isset($properties[$ID]) && isset($properties[$ID]['VALUE'])
				? $properties[$ID]['VALUE'] : '';

			if($value === '')
			{
				continue;
			}

			$typeName = isset($binding['type']) ? $binding['type'] : '';
			if($typeName === 'address')
			{
				$params = isset($binding['params']) ? $binding['params'] : array();
				$addressType = isset($params['address_type'])
					? (int)$params['address_type'] : EntityAddress::Primary;

				if(!isset($fields[$name]))
				{
					$fields[$name] = array();
				}

				if(!isset($fields[$name][$addressType]))
				{
					$addressFields = EntityAddress::getByOwner($addressType, $this->entityTypeID, $entityID);
					$address = is_array($addressFields) ? EntityAddressFormatter::format($addressFields) : '';
					if($address !== '' && strcasecmp(self::getAlphanum($value), self::getAlphanum($address)) === 0)
					{
						$fields[$name][$addressType] = $addressFields;
					}
					else
					{
						$fields[$name][$addressType] = array('ADDRESS_1' => $value);
					}
				}
			}
			elseif(!isset($fields[$name]) || $fields[$name] === '')
			{
				$fields[$name] = $value;
			}
		}
	}
	/**
	 * Prepare requisite name.
	 * @param int $entityID Entity ID.
	 * @param array $fields Fields.
	 * @return string
	 */
	private function prepareRequisiteName($entityID, array $fields)
	{
		$fieldName = '';
		if($this->entityTypeID === \CCrmOwnerType::Company)
		{
			if(isset($fields[EntityRequisite::COMPANY_FULL_NAME]))
			{
				$fieldName = EntityRequisite::COMPANY_FULL_NAME;
			}
			elseif(isset($fields[EntityRequisite::COMPANY_NAME]))
			{
				$fieldName = EntityRequisite::COMPANY_NAME;
			}
			elseif(isset($fields[EntityRequisite::INN]))
			{
				$fieldName = EntityRequisite::INN;
			}
		}

		$name = $fieldName !== '' && isset($fields[$fieldName]) ? $fields[$fieldName] : '';
		if($name === '')
		{
			$name = \CCrmOwnerType::GetCaption($this->entityTypeID, $entityID, false);
		}

		if($name === '')
		{
			Loc::loadMessages(__FILE__);
			$name = GetMessage('CRM_DEFAULT_TITLE');
		}

		return $name;
	}
	/**
	 * Check converter settings
	 * @return void
	 * @throws RequisiteConvertException
	 */
	public function validate()
	{
		if($this->personTypeID <= 0)
		{
			throw new InvoiceRequisiteConvertException(
				$this->presetID,
				0,
				InvoiceRequisiteConvertException::PERSON_TYPE_NOT_FOUND
			);
		}
		elseif($this->getPresetBindingCount() === 0)
		{
			throw new InvoiceRequisiteConvertException(
				$this->presetID,
				$this->personTypeID,
				InvoiceRequisiteConvertException::PRESET_NOT_BOUND
			);
		}
		elseif($this->getPropertyMapCount() === 0)
		{
			throw new InvoiceRequisiteConvertException(
				$this->presetID,
				$this->personTypeID,
				InvoiceRequisiteConvertException::PROPERTY_NOT_FOUND
			);
		}
	}
	/**
	 * Process entity. Convert invoice requisites to entity requisites
	 * @param int $entityID Entity ID.
	 * @throws InvoiceRequisiteConvertException
	 * @return bool
	 */
	public function processEntity($entityID)
	{
		$this->validate();

		$filter = array();
		if($this->entityTypeID === \CCrmOwnerType::Company)
		{
			$filter['=UF_COMPANY_ID'] = $entityID;
		}
		elseif($this->entityTypeID === \CCrmOwnerType::Contact)
		{
			$filter['=UF_CONTACT_ID'] = $entityID;
			$filter['=UF_COMPANY_ID'] = 0;
		}
		else
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($this->entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		if(!$this->enablePermissionCheck)
		{
			$filter['CHECK_PERMISSIONS'] = 'N';
		}

		$dbResult = \CCrmInvoice::GetList(
			array('ID' => 'DESC'),
			$filter,
			false,
			array('nTopCount' => $this->limit),
			array('ID')
		);

		$itemIDs = array();
		while($ary = $dbResult->Fetch())
		{
			$itemIDs[] = (int)$ary['ID'];
		}

		$map = array();
		$count = $this->getPropertyMapCount();
		foreach($itemIDs as $itemID)
		{
			$properties = \CCrmInvoice::GetProperties($itemID, $this->personTypeID);
			$key = $this->prepareRequisiteKey($properties);
			if($key === '')
			{
				$key = '-';
			}

			$item = isset($map[$key]) ? $map[$key] : array();
			$fields = isset($item['fields']) ? $item['fields'] : array();
			if(count($fields) < $count)
			{
				$this->prepareRequisiteFields($properties, $entityID, $fields);
				$item['fields'] = $fields;
			}

			if(!isset($item['invoices']))
			{
				$item['invoices'] = array();
			}

			$item['invoices'][] = $itemID;
			$map[$key] = $item;
		}

		if(empty($map))
		{
			return false;
		}

		$requisiteEntity = new EntityRequisite();
		foreach($map as $key => $item)
		{
			$fields = isset($item['fields']) ? $item['fields'] : array();
			if(empty($fields))
			{
				continue;
			}

			$xmlID = $this->presetID.':'.$this->entityTypeID.':'.md5($key);
			$dbResult = $requisiteEntity->getList(
				array(
					'filter' => array(
						'=PRESET_ID' => $this->presetID,
						'=ENTITY_TYPE_ID' => $this->entityTypeID,
						'=ENTITY_ID' => $entityID,
						'=XML_ID' => $xmlID
					),
					'select' => array_merge(array('ID', 'XML_ID'), $this->getPresetFieldNames()),
					'limit' => 1
				)
			);
			$requisiteFields = $dbResult->fetch();

			$requisiteID = 0;

			if(is_array($requisiteFields))
			{
				$requisiteID = (int)$requisiteFields['ID'];
				unset($requisiteFields['ID']);

				//HACK: Remove empty values for array_merge can take new values form fields
				foreach($requisiteFields as $k => $v)
				{
					if($v === '')
					{
						unset($requisiteFields[$k]);
					}
				}

				$requisiteFields[EntityRequisite::ADDRESS] = EntityRequisite::getAddresses($requisiteID);
			}
			else
			{
				$requisiteFields = array(
					'ENTITY_TYPE_ID' => $this->entityTypeID,
					'ENTITY_ID' => $entityID,
					'PRESET_ID' => $this->presetID,
					'NAME' => $this->prepareRequisiteName($entityID, $fields),
					'SORT' => 500,
					'ACTIVE' => 'Y',
					'XML_ID' => $xmlID
				);
			}

			$requisiteFields = array_merge($fields, $requisiteFields);
			if($requisiteID > 0)
			{
				$result = $requisiteEntity->update($requisiteID, $requisiteFields);
			}
			else
			{
				$result = $requisiteEntity->add($requisiteFields);
				if($result->isSuccess())
				{
					$requisiteID = (int)$result->getId();
				}
			}

			if($result->isSuccess() && $requisiteID > 0 && isset($item['invoices']))
			{
				foreach($item['invoices'] as $invoiceID)
				{
					EntityLink::register(\CCrmOwnerType::Invoice, $invoiceID, $requisiteID);
				}
			}
		}
		return true;
	}
	/**
	 * Complete convertion process
	 * @return void
	 */
	public function complete()
	{
		$countryId = isset($this->presetFields['COUNTRY_ID']) ? (int)$this->presetFields['COUNTRY_ID'] : 0;
		if (Main\Loader::includeModule('sale') && $countryId > 0)
		{
			$convMap = array(
				'PROPERTY' => array(
					'COMPANY' => array(
						'TYPE' => 'REQUISITE',
						'VALUE' => 'RQ_COMPANY_NAME|'.$countryId
					),
					'COMPANY_NAME' => array(
						'TYPE' => 'REQUISITE',
						'VALUE' => 'RQ_COMPANY_NAME|'.$countryId
					),
					'INN' => array(
						'TYPE' => 'REQUISITE',
						'VALUE' => 'RQ_INN|'.$countryId
					),
					'COMPANY_ADR' => array(
						'TYPE' => 'REQUISITE',
						'VALUE' => 'RQ_ADDR_'.EntityAddress::Registered.'|'.$countryId
					),
					'PHONE' => array(
						'TYPE' => 'REQUISITE',
						'VALUE' => 'RQ_PHONE|'.$countryId
					),
					'FAX' => array(
						'TYPE' => 'REQUISITE',
						'VALUE' => 'RQ_FAX|'.$countryId
					),
					'CONTACT_PERSON' => array(
						'TYPE' => 'REQUISITE',
						'VALUE' => 'RQ_CONTACT|'.$countryId
					),
					'FIO' => array(
						'TYPE' => 'REQUISITE',
						'VALUE' => 'RQ_NAME|'.$countryId
					),
					'ADDRESS' => array(
						'TYPE' => 'REQUISITE',
						'VALUE' => 'RQ_ADDR_'.EntityAddress::Primary.'|'.$countryId
					)
				)
			);
			$personTypeIds = \CCrmPaySystem::getPersonTypeIDs();
			if (!empty($personTypeIds))
			{
				$paySystemList = array();
				$personTypeAbbrs = array('COMPANY', 'CONTACT');
				$innerPaySystemId = \Bitrix\Sale\PaySystem\Manager::getInnerPaySystemId();
				foreach ($personTypeAbbrs as $personTypeAbbr)
				{
					if (isset($personTypeIds[$personTypeAbbr]) && $personTypeIds[$personTypeAbbr] > 0
						&& $this->personTypeID === intval($personTypeIds[$personTypeAbbr]))
					{
						$paySystems = \CCrmPaySystem::GetPaySystems($personTypeIds[$personTypeAbbr]);
						if(is_array($paySystems))
						{
							$paySystemList = array_merge($paySystemList, $paySystems);
						}
					}
				}
				unset($personTypeAbbrs, $personTypeAbbr, $paySystems);
				if(is_array($paySystemList))
				{
					foreach($paySystemList as $paySystem)
					{
						$id = intval($paySystem['~ID']);
						$file = isset($paySystem['~PSA_ACTION_FILE']) ? $paySystem['~PSA_ACTION_FILE'] : '';
						if($id !== $innerPaySystemId
							&& preg_match('/(quote|bill)(_\w+)*$/i'.BX_UTF_PCRE_MODIFIER, $file))
						{
							$psaId = isset($paySystem['~PSA_ID']) ? (int)$paySystem['~PSA_ID'] : 0;
							if ($psaId > 0)
							{
								$psaParams = isset($paySystem['~PSA_PARAMS']) ?
									\CSalePaySystemAction::UnSerializeParams($paySystem['~PSA_PARAMS']) : array();
								if (is_array($psaParams) && !empty($psaParams))
								{
									$paramsModified = false;
									foreach ($psaParams as &$param)
									{
										if (isset($param['TYPE']) && $param['TYPE'] === 'PROPERTY'
											&& isset($param['VALUE']))
										{
											foreach ($convMap as $type => $typeMap)
											{
												if ($param['TYPE'] === $type)
												{
													foreach ($typeMap as $value => $newParam)
													{
														if ($param['VALUE'] === $value)
														{
															$param['TYPE'] = $newParam['TYPE'];
															$param['VALUE'] = $newParam['VALUE'];
															$paramsModified = true;
														}
													}
												}
											}
										}
									}
									unset($param);
									if ($paramsModified)
									{
										$psaParams = \CSalePaySystemAction::SerializeParams($psaParams);
										$psaFields = array('PARAMS' => $psaParams, 'PERSON_TYPE_ID' => $this->personTypeID);
										\CSalePaySystemAction::Update($psaId, $psaFields);
									}
								}
							}
						}
					}
				}
			}
		}
	}
}