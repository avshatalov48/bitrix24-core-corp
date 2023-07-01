<?php
/**
 * Created by PhpStorm.
 * User: evgenik
 * Date: 16.05.2016
 * Time: 15:19
 */

namespace Bitrix\Crm;


use Bitrix\Crm\Category\Entity\ItemCategory;
use Bitrix\Crm\Service\Container;
use CCrmOwnerType;

class EntityAddressType
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
	const Billing = 12;

	const First = 1;
	const Last = 12;

	const PrimaryName = 'PRIMARY';
	const SecondaryName = 'SECONDARY';
	const ThirdName = 'THIRD';
	const HomeName = 'HOME';
	const WorkName = 'WORK';
	const RegisteredName = 'REGISTERED';
	const CustomName = 'CUSTOM';
	const PostName = 'POST';
	const BeneficiaryName = 'BENEFICIARY';
	const BankName = 'BANK';
	const DeliveryName = 'DELIVERY';
	const BillingName = 'BILLING';

	private static $ALL_DESCRIPTIONS = array();

	private static $zoneMap = null;

	public static function isDefined($typeID)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}
		return $typeID >= self::First && $typeID <= self::Last;
	}

	public static function getAllIDs()
	{
		return array(
			self::Primary,
			self::Secondary,
			self::Third,
			self::Home,
			self::Work,
			self::Registered,
			self::Custom,
			self::Post,
			self::Beneficiary,
			self::Bank,
			self::Delivery,
			self::Billing
		);
	}

	public static function getAvailableIds()
	{
		// List of all types used in the zone map.
		// Could be obtained from the map, but it is not effective.
		return [
			self::Delivery,
			self::Primary,
			self::Registered,
			self::Home,
			self::Post,
			self::Beneficiary
		];
	}

	public static function resolveID($name)
	{
		$name = mb_strtoupper(trim(strval($name)));
		if($name == '')
		{
			return self::Undefined;
		}

		switch($name)
		{
			case self::PrimaryName:
				return self::Primary;

			case self::SecondaryName:
				return self::Secondary;

			case self::ThirdName:
				return self::Third;

			case self::HomeName:
				return self::Home;

			case self::WorkName:
				return self::Work;

			case self::RegisteredName:
				return self::Registered;

			case self::CustomName:
				return self::Custom;

			case self::PostName:
				return self::Post;

			case self::BeneficiaryName:
				return self::Beneficiary;

			case self::BankName:
				return self::Bank;

			case self::DeliveryName:
				return self::Delivery;

			case self::BillingName:
				return self::Billing;

			default:
				return self::Undefined;
		}
	}

	public static function resolveName($typeID)
	{
		if(!is_numeric($typeID))
		{
			return '';
		}

		$typeID = intval($typeID);
		if($typeID <= 0)
		{
			return '';
		}

		switch($typeID)
		{
			case self::Primary:
				return self::PrimaryName;

			case self::Secondary:
				return self::SecondaryName;

			case self::Third:
				return self::ThirdName;

			case self::Home:
				return self::HomeName;

			case self::Work:
				return self::WorkName;

			case self::Registered:
				return self::RegisteredName;

			case self::Custom:
				return self::CustomName;

			case self::Post:
				return self::PostName;

			case self::Beneficiary:
				return self::BeneficiaryName;

			case self::Bank:
				return self::BankName;

			case self::Delivery:
				return self::DeliveryName;

			case self::Billing:
				return self::BillingName;

			case self::Undefined:
			default:
				return '';
		}
	}

	public static function getAllDescriptions()
	{
		if(!isset(self::$ALL_DESCRIPTIONS[LANGUAGE_ID]))
		{
			IncludeModuleLangFile(__FILE__);
			self::$ALL_DESCRIPTIONS[LANGUAGE_ID] = array(
				self::Primary => GetMessage('CRM_ADDRESS_TYPE_PRIMARY'),
				self::Secondary => GetMessage('CRM_ADDRESS_TYPE_SECONDARY'),
				self::Third => GetMessage('CRM_ADDRESS_TYPE_THIRD'),
				self::Home => GetMessage('CRM_ADDRESS_TYPE_HOME'),
				self::Work => GetMessage('CRM_ADDRESS_TYPE_WORK'),
				self::Registered => GetMessage('CRM_ADDRESS_TYPE_REGISTERED'),
				self::Custom => GetMessage('CRM_ADDRESS_TYPE_CUSTOM'),
				self::Post => GetMessage('CRM_ADDRESS_TYPE_POST'),
				self::Beneficiary => GetMessage('CRM_ADDRESS_TYPE_BENEFICIARY'),
				self::Bank => GetMessage('CRM_ADDRESS_TYPE_BANK'),
				self::Delivery => GetMessage('CRM_ADDRESS_TYPE_DELIVERY'),
				self::Billing => GetMessage('CRM_ADDRESS_TYPE_BILLING')			);
		}

		return self::$ALL_DESCRIPTIONS[LANGUAGE_ID];
	}

	public static function getDescription($typeID)
	{
		$typeID = intval($typeID);
		$all = self::getAllDescriptions();
		return isset($all[$typeID]) ? $all[$typeID] : '';
	}

	public static function getDescriptions($types)
	{
		$result = array();
		if(is_array($types))
		{
			foreach($types as $typeID)
			{
				$typeID = intval($typeID);
				$descr = self::getDescription($typeID);
				if($descr !== '')
				{
					$result[$typeID] = $descr;
				}
			}
		}
		return $result;
	}

	public static function getZoneMap() : array
	{
		if (self::$zoneMap === null)
		{
			// Need sync with EntityAddress::getZoneMap()
			// See the getAvailableIds, need sync...
			self::$zoneMap = [
				'ru' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'ua' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'ur' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'by' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'kz' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'en' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'eu' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'de' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'la' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'br' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'fr' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'it' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'pl' => [
					'types' => [
						self::Primary, self::Delivery, self::Home,
						self::Post, self::Registered, self::Beneficiary
					],
					'default' => self::Primary
				],
				'tr' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'cn' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'sc' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'tc' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'ja' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'vn' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'id' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'ms' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'th' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'in' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'hi' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'uk' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'co' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				],
				'mx' => [
					'types' => [
						self::Delivery, self::Primary, self::Registered,
						self::Home, self::Beneficiary
					],
					'default' => self::Delivery
				]
			];
		}

		return self::$zoneMap;
	}

	public static function getDefaultIdByZone(string $addressZoneId) : int
	{
		$zoneMap = self::getZoneMap();

		return isset($zoneMap[$addressZoneId]) ? $zoneMap[$addressZoneId]['default'] : self::Undefined;
	}

	public static function getDefaultIdByEntityCategory(int $entityTypeId, int $categoryId) : int
	{
		$result = static::Undefined;

		if (CCrmOwnerType::IsDefined($entityTypeId) && $categoryId > 0)
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);
			if ($factory && $factory->isCategoryAvailable($categoryId))
			{
				$category = $factory->getCategory($categoryId);
				if ($category instanceof ItemCategory)
				{
					$result = $category->getDefaultAddressType();
				}
			}
		}

		return $result;
	}

	public static function getDefaultIdByEntityId(int $entityTypeId, int $entityId) : int
	{
		$result = static::Undefined;

		if (CCrmOwnerType::IsDefined($entityTypeId) && $entityId > 0)
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);

			if ($factory)
			{
				$categoryId = 0;

				if ($factory->isCategoriesSupported())
				{
					$item = $factory->getItem($entityId);
					if ($item)
					{
						$categoryId = $item->getCategoryId();
					}
				}

				if ($categoryId > 0 && $factory->isCategoryAvailable($categoryId))
				{
					$category = $factory->getCategory($categoryId);
					if ($category instanceof ItemCategory)
					{
						$result = $category->getDefaultAddressType();
					}
				}
			}
		}

		return $result;
	}

	public static function getDescriptionsByZonesOrValues(array $addressZones = [], array $values = []) : array
	{
		return self::getDescriptions(self::getIdsByZonesOrValues($addressZones, $values));
	}

	public static function getIdsByZonesOrValues(array $addressZoneIds = [], array $values = []) : array
	{
		$ids = [];

		$addressTypeMap = array_fill_keys(EntityAddressType::getAllIDs(), 0);
		$zoneMap = self::getZoneMap();

		$addressZoneIds = array_unique($addressZoneIds);

		$typeIndex = 1;
		foreach ($addressZoneIds as $addressZoneId)
		{
			if (isset($zoneMap[$addressZoneId]))
			{
				foreach ($zoneMap[$addressZoneId]['types'] as $typeId)
				{
					if (isset($addressTypeMap[$typeId]) && $addressTypeMap[$typeId] <= 0)
					{
						$addressTypeMap[$typeId] = $typeIndex++;
					}
				}
			}
		}

		foreach ($values as $typeId)
		{
			if (isset($addressTypeMap[$typeId]) && $addressTypeMap[$typeId] <= 0)
			{
				$addressTypeMap[$typeId] = $typeIndex++;
			}
		}

		asort($addressTypeMap);

		foreach ($addressTypeMap as $typeId => $index)
		{
			if ($index > 0)
			{
				$ids[] = $typeId;
			}
		}

		return $ids;
	}
}