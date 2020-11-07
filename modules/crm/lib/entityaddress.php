<?php
namespace Bitrix\Crm;

use Bitrix\Location\Entity\Address\AddressLinkCollection;
use Bitrix\Location\Service\AddressService;
use Bitrix\Main;
use Bitrix\Main\Text\Encoding;
use \Bitrix\Sale;
use Bitrix\Location\Entity\Address;

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
	const Delivery = 11;

	const First = 1;
	const Last = 11;

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
					'LOC_ADDR_ID' => ['TYPE' => 'integer'],
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

	private static $locationModuleIncluded = null;

	/**
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function isLocationModuleIncluded()
	{
		if (self::$locationModuleIncluded === null)
		{
			self::$locationModuleIncluded = Main\Loader::includeModule('location');
		}

		return self::$locationModuleIncluded;
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

	protected static function getFieldsByLocationAddress($address)
	{
		$result = [];

		if (!(static::isLocationModuleIncluded() && $address instanceof Address))
		{
			return $result;
		}

		$addressFieldSourceMap = static::getAddressFieldMap();

		foreach ($addressFieldSourceMap as $crmAddressFieldName => $sourceFieldId)
		{
			$result[$crmAddressFieldName] = $address->getFieldValue($sourceFieldId);
		}
		$result['LOC_ADDR_ID'] = $address->getId();

		return $result;
	}

	protected static function getLocationAddressLinkIndentifier($typeID, $entityTypeID, $entityID)
	{
		return [
			'entityType' => 'CRM_'.\CCrmOwnerType::ResolveName($entityTypeID).'_ADDRESS',
			'entityId' => $typeID.'.'.$entityTypeID.'.'.$entityID
		];
	}

	protected static function resetLocationAddressLink($address, $typeID, $entityTypeID, $entityID,
		$prevEntityTypeId = null, $prevEntityId = null)
	{
		if (!($address instanceof Address))
		{
			throw new Main\ArgumentException('Must be instance of '.Address::class, 'address');
		}

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

		$linkIdentifier = static::getLocationAddressLinkIndentifier($typeID, $entityTypeID, $entityID);
		$prevLinkIdentifier = null;
		if ($prevEntityTypeId > 0 && $prevEntityId > 0)
		{
			$prevLinkIdentifier = static::getLocationAddressLinkIndentifier($typeID, $prevEntityTypeId, $prevEntityId);
		}
		/** @var $links Address\AddressLink[] */
		$links = $address->getLinks();
		$linksCount = count($links);
		if ($linksCount === 0)
		{
			$address->addLink($linkIdentifier['entityId'], $linkIdentifier['entityType']);
		}
		else if ($linksCount === 1)
		{
			$prevLinkEntityId = $links[0]->getAddressLinkEntityId();
			$linkEntityId = $linkIdentifier['entityId'];
			$targetLinkIdentifier = null;
			if ($prevLinkIdentifier)
			{
				$isEntityTypeAreDifferent = ($links[0]->getAddressLinkEntityType() !== $prevLinkIdentifier['entityType']);
				$isEntityIdAreDifferent = ($links[0]->getAddressLinkEntityId() !== $prevLinkIdentifier['entityId']);
				$targetLinkIdentifier = $prevLinkIdentifier;
			}
			else
			{
				$isEntityTypeAreDifferent = ($links[0]->getAddressLinkEntityType() !== $linkIdentifier['entityType']);
				$isEntityIdAreDifferent = ($links[0]->getAddressLinkEntityId() !== $linkIdentifier['entityId']);
				$targetLinkIdentifier = $linkIdentifier;
			}
			$prevIdComponents = [];
			$idComponents = [];
			$isOnlyAddressTypeDifferent = false;
			if (preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $prevLinkEntityId, $prevIdComponents)
				&& preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $linkEntityId, $idComponents))
			{
				$isOnlyAddressTypeDifferent = (
					$prevIdComponents[1] !== $idComponents[1]
					&& $prevIdComponents[2] === $idComponents[2]
					&& $prevIdComponents[3] === $idComponents[3]
				);
			}
			unset($prevLinkEntityId, $linkEntityId, $prevIdComponents, $idComponents);

			if (!$isEntityTypeAreDifferent && $isEntityIdAreDifferent && $isOnlyAddressTypeDifferent)
			{
				$locationAddressId = $address->getId();
				if ($locationAddressId > 0)
				{
					AddressTable::dropLocationAddressLink($locationAddressId);
				}
				unset($locationAddressId);
			}

			if ($isEntityTypeAreDifferent || $isEntityIdAreDifferent && !$isOnlyAddressTypeDifferent)
			{
				throw new Main\SystemException(
					'Location address has incorrect link "'.$links[0]->getAddressLinkEntityType().', '.
					$links[0]->getAddressLinkEntityId().'". Must be "'.$targetLinkIdentifier['entityType'].', '.
					$targetLinkIdentifier['entityId'].'".', 1010
				);
			}
		}
		else
		{
			throw new Main\SystemException(
				'Location address (id: '.$address->getId().', supposed link: "'.$linkIdentifier['entityType'].', '.
				$linkIdentifier['entityId'].'") must have only one link.', 1015
			);
		}

		// Clearing lost links and addresses
		// I did not want to do this in this way, but there are no others yet.
		// Could be:
		//   Address\AddressLink::deleteByIdentifiers($linkIdentifier['entityType'], $linkIdentifier['entityId']);
		/** @var $locationAddress Address */
		foreach (
			AddressService::getInstance()->findByLinkedEntity(
				$linkIdentifier['entityId'],
				$linkIdentifier['entityType']
			) as $locationAddress
		)
		{
			$modified = false;
			/** @var $addressLinks Address\AddressLinkCollection */
			$addressLinks = $locationAddress->getLinks();
			/** @var $link Address\AddressLink */
			foreach ($addressLinks as $offset => $link)
			{
				if ($locationAddress->getId() !== $address->getId()
					&& $link->getAddressLinkEntityType() === $linkIdentifier['entityType']
					&& $link->getAddressLinkEntityId() === $linkIdentifier['entityId'])
				{
					unset($addressLinks[$offset]);
					$modified = true;
				}
			}
			if (count($addressLinks) > 0)
			{
				if ($modified)
				{
					$locationAddress->setLinks($addressLinks);
					$locationAddress->save();
				}
			}
			else
			{
				$locationAddress->delete();
			}
		}
	}

	/**
	 * @param array $fields
	 * @param string $languageId
	 * @return Address|null
	 * @throws Main\LoaderException
	 */
	protected static function getLocationAddressByFields(array $fields, string $languageId)
	{
		if ($languageId === '' || mb_strlen($languageId) !== 2)
		{
			$languageId = static::getDefaultLanguageId();
		}

		if (!static::isLocationModuleIncluded())
		{
			return null;
		}

		$locationAddress = new Address($languageId);
		static::updateLocationAddressFields($locationAddress, $fields);

		return $locationAddress;
	}

	/**
	 * @param Address $locationAddress
	 * @param array $fields
	 * @return bool
	 */
	protected static function updateLocationAddressFields(Address $locationAddress, array $fields): bool
	{
		$addressFieldMap = static::getAddressFieldMap();

		$result = false;
		foreach ($addressFieldMap as $crmAddressFieldName => $locationAddressFieldId)
		{
			if (isset($fields[$crmAddressFieldName]) && is_string($fields[$crmAddressFieldName]))
			{
				$locationFieldValue = $locationAddress->getFieldValue($locationAddressFieldId);
				if ($fields[$crmAddressFieldName] !== '' || ($locationFieldValue !== null && $fields[$crmAddressFieldName] !== $locationFieldValue))
				{
					$locationAddress->setFieldValue($locationAddressFieldId, $fields[$crmAddressFieldName]);
					$result = true;
				}
			}
		}
		return $result;
	}

	/**
	 * @param array $fields
	 * @param string $languageId
	 * @return Address|null
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function makeLocationAddressByFields(array $fields, string $languageId = '')
	{
		if ($languageId === '' || mb_strlen($languageId) !== 2)
		{
			$languageId = static::getDefaultLanguageId();
		}

		$result = null;

		if (!static::isLocationModuleIncluded())
		{
			return $result;
		}

		$locationAddressId = 0;
		if (is_array($fields) && isset($fields['LOC_ADDR_ID']) && $fields['LOC_ADDR_ID'] > 0)
		{
			$locationAddressId = (int)$fields['LOC_ADDR_ID'];
		}

		if ($locationAddressId > 0)
		{
			$addr = Address::load($locationAddressId);
			if ($addr instanceof Address)
			{
				$result = $addr;
			}
			unset($addr);
		}

		if (!$result && $fields['LOC_ADDR'] instanceof Address)
		{
			$result = $fields['LOC_ADDR'];
		}

		if (!$result)
		{
			$result = static::getLocationAddressByFields($fields, $languageId);
		}

		return $result;
	}

	public static function getDefaultLanguageId()
	{
		$languageId = '';

		if (defined("ADMIN_SECTION"))
		{
			$res = Main\SiteTable::getList(
				[
					'select' => ['LANGUAGE_ID'],
					'filter' => ['=DEF' => 'Y', '=ACTIVE' => 'Y']
				]
			);
			$row = $res->fetch();
			if (is_array($row) && isset($row['LANGUAGE_ID']))
			{
				$languageId = (string)$row['LANGUAGE_ID'];
			}
		}
		else
		{
			$languageId = LANGUAGE_ID;
		}

		if ($languageId == '')
		{
			$languageId = 'en';
		}

		return $languageId;
	}

	/**
	 * @param int $locationAddressId
	 * @return Address
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function cloneLocationAddress(int $locationAddressId): Address
	{
		if ($locationAddressId > 0)
		{
			$addr = Address::load($locationAddressId);
			if ($addr instanceof Address)
			{
				$addr->setId(0);
				$addr->setLinks(new AddressLinkCollection());
				return $addr;
			}
		}
		throw new Main\ArgumentException('Location address id '.$locationAddressId.' not found');
	}

	/**
	 * @param $locationAddressId
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected static function deleteLocationAddress($locationAddressId)
	{
		if (static::isLocationModuleIncluded() && $locationAddressId > 0)
		{
			// TODO: ... [LOC_ADDR_DELETE_1] - Need delete by ID
			$addr = Address::load((int)$locationAddressId);
			if ($addr instanceof Address)
			{
				$addr->delete();
			}
		}
	}

	protected static function deleteLocationAddresses(Array $locationAddressIds)
	{
		if (!empty($locationAddressIds) && static::isLocationModuleIncluded())
		{
			// TODO: ... [LOC_ADDR_DELETE_2] - Need multiple delete method by IDs
			foreach ($locationAddressIds as $locationAddressId)
			{
				if ($locationAddressId > 0)
				{
					static::deleteLocationAddress($locationAddressId);
				}
			}
		}
	}

	public static function onLocationAddressUpdate(/** @var $event Main\ORM\Event */ $event)
	{
		$param = $event->getParameter('id');
		$locationAddressId = isset($param['ID']) ? (int)$param['ID'] : 0;
		if ($locationAddressId > 0 && static::isLocationModuleIncluded())
		{
			$res = AddressTable::getList(
				['filter' => ['=LOC_ADDR_ID' => $locationAddressId]]
			);
			while ($row = $res->fetch())
			{
				$locationAddress = Address::load($locationAddressId);
				if ($locationAddress instanceof Address)
				{
					$data = static::getFieldsByLocationAddress($locationAddress);
					$country = isset($data['COUNTRY']) ? $data['COUNTRY'] : '';
					$countryCode = $row['COUNTRY_CODE'];
					if ($country !== $row['COUNTRY'])
					{
						$countryCode = isset($data['COUNTRY_CODE']) ? $data['COUNTRY_CODE'] : '';
						if ($countryCode !== ''
							&& ($country === '' || !self::checkCountryCaption($countryCode, $country)))
						{
							$countryCode = '';
						}
					}
					$fields = [
						'TYPE_ID' => $row['TYPE_ID'],
						'ENTITY_TYPE_ID' => $row['ENTITY_TYPE_ID'],
						'ENTITY_ID' => $row['ENTITY_ID'],
						'ANCHOR_TYPE_ID' => $row['ANCHOR_TYPE_ID'],
						'ANCHOR_ID' => $row['ANCHOR_ID'],
						'ADDRESS_1' => isset($data['ADDRESS_1']) ? $data['ADDRESS_1'] : '',
						'ADDRESS_2' => isset($data['ADDRESS_2']) ? $data['ADDRESS_2'] : '',
						'CITY' => isset($data['CITY']) ? $data['CITY'] : '',
						'POSTAL_CODE' => isset($data['POSTAL_CODE']) ? $data['POSTAL_CODE'] : '',
						'REGION' => isset($data['REGION']) ? $data['REGION'] : '',
						'PROVINCE' => isset($data['PROVINCE']) ? $data['PROVINCE'] : '',
						'COUNTRY' => $country,
						'COUNTRY_CODE' => $countryCode,
						'LOC_ADDR_ID' => $locationAddressId,
						'IS_DEF' => ($row['IS_DEF'] == 1 || $row['IS_DEF'] === true)
					];

					AddressTable::upsert($fields);

					//region Send event
					$event = new Main\Event('crm', 'OnAfterAddressRegister', array('fields' => $fields));
					$event->send();
					//endregion Send event
				}
			}
		}
	}

	public static function onLocationAddressDelete(/** @var $event Main\ORM\Event */ $event)
	{
		$param = $event->getParameter('id');
		$locationAddressId = isset($param['ID']) ? (int)$param['ID'] : 0;
		if ($locationAddressId > 0)
		{
			AddressTable::dropLocationAddressLink($locationAddressId);
		}
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

	private static function checkNeedForCompatibleRegister($addressFields)
	{
		$result = true;

		if (static::isEmpty($addressFields))
		{
			$isAddressExists = false;
			$address = new self();
			if ($addressFields['ENTITY_TYPE_ID'] === \CCrmOwnerType::Company
				|| $addressFields['ENTITY_TYPE_ID'] === \CCrmOwnerType::Contact)
			{
				$res = $address->getList(
					[
						'filter' => [
							'TYPE_ID' => $addressFields['TYPE_ID'],
							'ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
							'ANCHOR_TYPE_ID' => $addressFields['ENTITY_TYPE_ID'],
							'ANCHOR_ID' => $addressFields['ENTITY_ID'],
							'IS_DEF' => 1
						],
						'select' => ['TYPE_ID'],
						'limit' => 1
					]
				);
			}
			else
			{
				$res = AddressTable::getByPrimary(
					[
						'ENTITY_TYPE_ID' => $addressFields['ENTITY_TYPE_ID'],
						'ENTITY_ID' => $addressFields['ENTITY_ID'],
						'TYPE_ID' => $addressFields['TYPE_ID']
					],
					['select' => ['TYPE_ID']]
				);
			}
			$row = $res->fetch();
			if (is_array($row))
			{
				$isAddressExists = true;
			}

			if (!$isAddressExists)
			{
				$result = false;
				if (isset($addressFields['LOC_ADDR_ID']) && $addressFields['LOC_ADDR_ID'] > 0)
				{
					static::deleteLocationAddress((int)$addressFields['LOC_ADDR_ID']);
				}
			}
		}

		return $result;
	}

	private static function applyCompatibility(array $addressFields)
	{
		$result = $addressFields;

		if ($addressFields['ENTITY_TYPE_ID'] === \CCrmOwnerType::Company
			|| $addressFields['ENTITY_TYPE_ID'] === \CCrmOwnerType::Contact)
		{
			$requisite = EntityRequisite::getSingleInstance();
			$isDefaultRequisite = true;
			$requisiteId = 0;
			$address = new self();
			$res = $address->getList(
				[
					'filter' => [
						'TYPE_ID' => $addressFields['TYPE_ID'],
						'ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
						'ANCHOR_TYPE_ID' => $addressFields['ENTITY_TYPE_ID'],
						'ANCHOR_ID' => $addressFields['ENTITY_ID'],
						'IS_DEF' => 1
					],
					'select' => ['ENTITY_ID'],
					'limit' => 1
				]
			);
			$row = $res->fetch();
			if (is_array($row) && isset($row['ENTITY_ID']))
			{
				$requisiteId = (int)$row['ENTITY_ID'];
				if (!$requisite->exists($requisiteId))
				{
					$requisiteId = 0;
				}
			}
			if ($requisiteId <= 0)
			{
				$settings = $requisite->loadSettings($addressFields['ENTITY_TYPE_ID'], $addressFields['ENTITY_ID']);
				if (is_array($settings))
				{
					if (isset($settings['REQUISITE_ID_SELECTED']) && $settings['REQUISITE_ID_SELECTED'] > 0)
					{
						$defRequisiteId = (int)$settings['REQUISITE_ID_SELECTED'];
						if ($requisite->exists($defRequisiteId))
						{
							$requisiteId = $defRequisiteId;
						}
					}
				}
				unset($settings, $defRequisiteId);
			}
			if ($requisiteId <= 0)
			{
				$isDefaultRequisite = false;
				$requisiteAddressMap = [];
				$res = $requisite->getList(
					array(
						'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
						'select' => ['ID'],
						'filter' => [
							'=ENTITY_TYPE_ID' => $addressFields['ENTITY_TYPE_ID'],
							'=ENTITY_ID' => $addressFields['ENTITY_ID']
						],
						'limit' => 1
					)
				);
				if ($row = $res->fetch())
				{
					$requisiteId = (int)$row['ID'];
					$requisiteAddressMap[$requisiteId] = EntityRequisite::getAddresses($requisiteId);
				}
			}
			if ($requisiteId <= 0)
			{
				$presetId = EntityRequisite::getDefaultPresetId($addressFields['ENTITY_TYPE_ID']);
				$requisiteAddResult = $requisite->add(
					array(
						'ENTITY_TYPE_ID' => $addressFields['ENTITY_TYPE_ID'],
						'ENTITY_ID' => $addressFields['ENTITY_ID'],
						'PRESET_ID' => $presetId,
						'NAME' => \CCrmOwnerType::GetCaption(
							$addressFields['ENTITY_TYPE_ID'],
							$addressFields['ENTITY_ID'],
							false
						),
						'SORT' => 500,
						'ACTIVE' => 'Y',
						'ADDRESS_ONLY' => 'Y'
					)
				);
				if($requisiteAddResult->isSuccess())
				{
					$requisiteId = (int)$requisiteAddResult->getId();
				}
				else
				{
					throw new Main\SystemException(
						'Cannot create a '.mb_strtolower(\CCrmOwnerType::ResolveName($addressFields['ENTITY_TYPE_ID'])).
						' details item (ID: '.$addressFields['ENTITY_ID'].'})'
					);
				}
			}
			if (!$isDefaultRequisite)
			{
				EntityRequisite::setDef(
					$addressFields['ENTITY_TYPE_ID'],
					$addressFields['ENTITY_ID'],
					$requisiteId,
					0,
					true
				);
			}
			$result['ENTITY_TYPE_ID'] = \CCrmOwnerType::Requisite;
			$result['ENTITY_ID'] = $requisiteId;
			$result['ANCHOR_TYPE_ID'] = $addressFields['ENTITY_TYPE_ID'];
			$result['ANCHOR_ID'] = $addressFields['ENTITY_ID'];
			// Modify address link
			if (isset($addressFields['LOC_ADDR_ID']) && $addressFields['LOC_ADDR_ID'] > 0)
			{
				$locationAddress = Address::load((int)$addressFields['LOC_ADDR_ID']);
				if ($locationAddress instanceof Address)
				{
					$addressLinks = $locationAddress->getLinks();
					$isLinksModified = false;
					/** @var $link Address\AddressLink */
					foreach ($addressLinks as $offset => $link)
					{
						$prevLinkIdentifier = static::getLocationAddressLinkIndentifier(
							$addressFields['TYPE_ID'],
							$addressFields['ENTITY_TYPE_ID'],
							$addressFields['ENTITY_ID']
						);
						$linkIdentifier = static::getLocationAddressLinkIndentifier(
							$result['TYPE_ID'],
							$result['ENTITY_TYPE_ID'],
							$result['ENTITY_ID']
						);
						if ($link->getAddressLinkEntityType() === $prevLinkIdentifier['entityType']
							&& $link->getAddressLinkEntityId() === $prevLinkIdentifier['entityId'])
						{
							$addressLinks[$offset] = new Address\AddressLink(
								$linkIdentifier['entityId'],
								$linkIdentifier['entityType']
							);
							$isLinksModified = true;
						}
					}
					if ($isLinksModified)
					{
						$locationAddress->setLinks($addressLinks);
						$locationAddress->save();
					}
				}
			}
		}

		return $result;
	}

	public static function register($entityTypeID, $entityID, $typeID, array $data, array $options = [])
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

		$isContactCompanyCompatibility = ($entityTypeID === \CCrmOwnerType::Company
			|| $entityTypeID === \CCrmOwnerType::Contact);

		$isDef = $isContactCompanyCompatibility || ($entityTypeID === \CCrmOwnerType::Requisite && isset($data['IS_DEF'])
				&& ($data['IS_DEF'] === true || $data['IS_DEF'] === 'Y' || $data['IS_DEF'] === '1'));

		if (isset($data['LANGUAGE_ID']) && is_string($data['LANGUAGE_ID']) && mb_strlen($data['LANGUAGE_ID']) === 2)
		{
			$languageId = $data['LANGUAGE_ID'];
		}
		else
		{
			$languageId = static::getDefaultLanguageId();
		}

		// Previous location address
		$prevAddressId = 0;
		$prevIsDef = false;
		$prevEntityTypeId = null;
		$prevEntityId = null;
		if ($isContactCompanyCompatibility)
		{
			$res = AddressTable::getList(
				['filter' => [
					'=TYPE_ID' => $typeID,
					'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
					'=ANCHOR_TYPE_ID' => $entityTypeID,
					'=ANCHOR_ID' => $entityID,
					'=IS_DEF' => 1
				]]
			);
		}
		else
		{
			$res = AddressTable::getList(
				['filter' => [
					'=TYPE_ID' => $typeID,
					'=ENTITY_TYPE_ID' => $entityTypeID,
					'=ENTITY_ID' => $entityID
				]]
			);
		}
		if ($row = $res->fetch())
		{
			$prevAddressId = (int)$row['LOC_ADDR_ID'];
			$prevIsDef = (bool)$row['IS_DEF'];
			$prevEntityTypeId = (int)$row['ENTITY_TYPE_ID'];
			$prevEntityId = (int)$row['ENTITY_ID'];
		}
		unset($res, $row);

		/** @var $locationAddress Address */
		$locationAddress = null;
		$isLocationAddressLost = false;
		if (static::isLocationModuleIncluded()
			&& isset($data['LOC_ADDR'])
			&& $data['LOC_ADDR'] instanceof Address)
		{
			$addr = $data['LOC_ADDR'];
			static::resetLocationAddressLink(
				$addr,
				$typeID,
				$entityTypeID,
				$entityID,
				$prevEntityTypeId,
				$prevEntityId
			);
			if ($addr->save()->isSuccess())
			{
				$locationAddress = $addr;
			}
			unset($addr);
		}
		if (static::isLocationModuleIncluded()
			&& !$locationAddress
			&& isset($data['LOC_ADDR_ID']) && $data['LOC_ADDR_ID'] > 0)
		{
			$addr = Address::load((int)$data['LOC_ADDR_ID']);
			if ($addr instanceof Address)
			{
				try
				{
					static::resetLocationAddressLink(
						$addr,
						$typeID,
						$entityTypeID,
						$entityID,
						$prevEntityTypeId,
						$prevEntityId
					);
				}
				catch (Main\SystemException $e)
				{
					if ($isContactCompanyCompatibility && $e->getCode() === 1010)
					{
						$isLocationAddressLost = true;
					}
					else
					{
						throw $e;
					}
				}
				if ($isLocationAddressLost)
				{
					$addr->setId(0);
					$linkIdentifier = static::getLocationAddressLinkIndentifier($typeID, $entityTypeID, $entityID);
					$addr->setLinks(
						new AddressLinkCollection(
							[
								new Address\AddressLink(
									$linkIdentifier['entityId'],
									$linkIdentifier['entityType']
								)
							]
						)
					);
					unset($linkIdentifier);
					if ($addr->save()->isSuccess())
					{
						$locationAddress = $addr;
					}
					unset($addr);
				}
				else
				{
					$locationAddress = $addr;
				}
			}
			unset($addr);
		}

		if (!$isLocationAddressLost && static::isLocationModuleIncluded() && $prevAddressId > 0)
		{
			if ($locationAddress)
			{
				if ($prevAddressId !== $locationAddress->getId())
				{
					static::deleteLocationAddress($prevAddressId);
				}
			}
			else
			{
				$addr = Address::load($prevAddressId);
				if ($addr instanceof Address)
				{
					$locationAddress = $addr;
				}
				unset($addr);
			}
		}
		unset($prevAddressId);

		if ($locationAddress
			&& ($isContactCompanyCompatibility
				|| isset($options['updateLocationAddress']) && $options['updateLocationAddress']))
		{
			if (static::updateLocationAddressFields($locationAddress, $data))
			{
				if (!$locationAddress->save()->isSuccess())
				{
					$locationAddress = null;
				}
			}
		}

		if ($locationAddress)
		{
			$data = static::getFieldsByLocationAddress($locationAddress);
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
			'COUNTRY_CODE' => $countryCode,
			'LOC_ADDR_ID' => ($locationAddress && isset($data['LOC_ADDR_ID'])) ? (int)$data['LOC_ADDR_ID'] : 0,
			'IS_DEF' => $prevIsDef
		);

		if (!$locationAddress)
		{
			if (static::isLocationModuleIncluded())
			{
				$locationAddress = static::getLocationAddressByFields($fields, $languageId);
				static::resetLocationAddressLink($locationAddress, $typeID, $entityTypeID, $entityID, $prevEntityTypeId, $prevEntityId);
			}
			if (static::isLocationModuleIncluded() && $locationAddress && $locationAddress->save()->isSuccess())
			{
				$fields['LOC_ADDR_ID'] = $locationAddress->getId();
			}
			else
			{
				$fields['LOC_ADDR_ID'] = 0;
			}
		}

		if (self::checkNeedForCompatibleRegister($fields))
		{
			$fields = self::applyCompatibility($fields);

			AddressTable::upsert($fields);
			if ($isDef)
			{
				AddressTable::setDef($fields);
			}

			//region Send event
			$event = new Main\Event('crm', 'OnAfterAddressRegister', array('fields' => $fields));
			$event->send();
			//endregion Send event
		}
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

		// Delete location address
		$locationAddressId = 0;
		$res = AddressTable::getByPrimary($primaryFields, ['select' => ['LOC_ADDR_ID']]);
		$row = $res->fetch();
		if (is_array($row) && isset($row['LOC_ADDR_ID']) && $row['LOC_ADDR_ID'] > 0)
		{
			$locationAddressId = (int)$row['LOC_ADDR_ID'];
		}
		unset($res, $row);
		if ($locationAddressId > 0)
		{
			static::deleteLocationAddress($locationAddressId);
		}
		unset($locationAddressId);

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
		$allAddressTypes = EntityAddressType::getAllIDs();

		// Delete location addresses
		$locationAddressIds = [];
		$res = AddressTable::getList(
			[
				'filter' => [
					'@TYPE_ID' => $allAddressTypes,
					'=ENTITY_TYPE_ID' => $entityTypeID,
					'=ENTITY_ID' => $entityID
				],
				'select' => ['LOC_ADDR_ID']
			]
		);
		while ($row = $res->fetch())
		{
			if (isset($row['LOC_ADDR_ID']) && $row['LOC_ADDR_ID'] > 0)
			{
				$locationAddressIds[] = (int)$row['LOC_ADDR_ID'];
			}
		}
		unset($res, $row);
		static::deleteLocationAddresses($locationAddressIds);
		unset($locationAddressIds);

		$typeSlug = implode(',', $allAddressTypes);
		$conditionSql = implode(
			' AND ',
			array(
				//HACK: DEFINE TYPE_ID IN WHERE CONDITION FOR MAKE MYSQL USE PK IN EFFECTIVE WAY
				"TYPE_ID IN({$typeSlug})",
				$helper->prepareAssignment($tableName, 'ENTITY_TYPE_ID', $entityTypeID),
				$helper->prepareAssignment($tableName, 'ENTITY_ID', $entityID)
			)
		);
		$connection->queryExecute('DELETE FROM '.$tableName.' WHERE '.$conditionSql);
	}

	public static function setDef($entityTypeID, $entityID, $typeID = 0)
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

		$fields = array(
			'TYPE_ID' => $typeID,
			'ENTITY_TYPE_ID' => $entityTypeID,
			'ENTITY_ID' => $entityID
		);

		AddressTable::setDef($fields);

		//region Send event
		$event = new Main\Event('crm', 'OnAfterAddressSetDef', array('fields' => $fields));
		$event->send();
		//endregion Send event
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
					'COUNTRY' => GetMessage('CRM_ENTITY_REG_ADDRESS_COUNTRY'),
					'LOC_ADDR_ID' => GetMessage('CRM_ENTITY_REG_ADDRESS_LOC_ADDR_ID')
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
					'COUNTRY' => GetMessage('CRM_ENTITY_ADDRESS_COUNTRY'),
					'LOC_ADDR_ID' => GetMessage('CRM_ENTITY_ADDRESS_LOC_ADDR_ID')
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
					'COUNTRY' => GetMessage('CRM_ENTITY_SHORT_REG_ADDRESS_COUNTRY'),
					'LOC_ADDR_ID' => GetMessage('CRM_ENTITY_SHORT_REG_ADDRESS_LOC_ADDR_ID'),
				);
			}
			else
			{
				self::$shortLabels[$typeID] = array(
					//For backward compatibility
					'ADDRESS' => static::getLocationFieldLabel('ADDRESS_1'),
					'ADDRESS_1' => static::getLocationFieldLabel('ADDRESS_1'),
					'ADDRESS_2' => static::getLocationFieldLabel('ADDRESS_2'),
					'CITY' => static::getLocationFieldLabel('CITY'),
					'POSTAL_CODE' => static::getLocationFieldLabel('POSTAL_CODE'),
					'REGION' => static::getLocationFieldLabel('REGION'),
					'PROVINCE' => static::getLocationFieldLabel('PROVINCE'),
					'COUNTRY' => static::getLocationFieldLabel('COUNTRY'),
					'LOC_ADDR_ID' => GetMessage('CRM_ENTITY_SHORT_ADDRESS_LOC_ADDR_ID'),
				);
			}
		}
		return self::$shortLabels[$typeID];
	}

	protected static function getAddressFieldMap(): array
	{
		return [
			'ADDRESS_1' => Address\FieldType::ADDRESS_LINE_1,
			'ADDRESS_2' => Address\FieldType::ADDRESS_LINE_2,
			'CITY' => Address\FieldType::LOCALITY,
			'POSTAL_CODE' => Address\FieldType::POSTAL_CODE,
			'PROVINCE' => Address\FieldType::ADM_LEVEL_1,
			'REGION' => Address\FieldType::ADM_LEVEL_2,
			'COUNTRY' => Address\FieldType::COUNTRY
			//'COUNTRY_CODE' => ?
		];
	}

	protected static function getLocationFieldLabel(string $fieldCode): string
	{

		if (Main\Loader::includeModule('location'))
		{
			$fieldMap = static::getAddressFieldMap();
			if ($fieldMap[$fieldCode])
			{
				$format = \Bitrix\Location\Service\FormatService::getInstance()
					->findDefault(LANGUAGE_ID);
				if ($format)
				{
					$field = $format
						->getFieldCollection()
						->getItemByType($fieldMap[$fieldCode]);
					if ($field)
					{
						return $field->getName();
					}
				}
			}
		}
		if (in_array($fieldCode, ['ADDRESS_1', 'ADDRESS_2']))
		{
			$fieldCode = str_replace('ADDRESS_', '', $fieldCode);
		}
		return GetMessage('CRM_ENTITY_SHORT_ADDRESS_'. $fieldCode);
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
					'=NAME.LANGUAGE_ID' => static::getDefaultLanguageId(),
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
			'=NAME.LANGUAGE_ID' => static::getDefaultLanguageId()
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
				$value = $helper->forSql(mb_strtoupper($value));
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
		$join = 'LEFT JOIN b_crm_addr ADDR_S ON '.$entityAlias.'.ID = ADDR_S.ENTITY_ID AND ADDR_S.TYPE_ID = '.$typeID.' AND ADDR_S.ENTITY_TYPE_ID = '.$entityTypeID;

		$listSort = array();
		foreach($sort as $fieldName => $order)
		{
			$fieldKey = "ADDR_S_{$fieldName}";
			$fields[$fieldKey] = array('FIELD' => 'ADDR_S.'.$fieldName, 'TYPE' => 'string', 'FROM'=> $join);
			$listSort[$fieldKey] = $order;
		}
		$fields['ADDR_ENTITY_ID'] = array('FIELD' => 'ADDR_S.ENTITY_ID', 'TYPE' => 'string', 'FROM'=> $join);
		$listSort['ADDR_ENTITY_ID'] = empty($listSort) ? null : reset($listSort);
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
				'COUNTRY_CODE' => isset($ary['COUNTRY_CODE']) ? $ary['COUNTRY_CODE'] : '',
				'LOC_ADDR_ID' => isset($ary['LOC_ADDR_ID']) ? (int)$ary['LOC_ADDR_ID'] : 0,
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

	public static function prepareJsonValue($data)
	{
		if (!Main\Application::isUtfMode() && is_string($data) && $data !== '')
		{
			$data = Encoding::convertEncoding($data, SITE_CHARSET, "UTF-8");
		}

		return $data;
	}
}