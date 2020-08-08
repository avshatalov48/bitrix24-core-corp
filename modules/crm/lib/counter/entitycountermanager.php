<?php
namespace Bitrix\Crm\Counter;
use Bitrix\Main;

class EntityCounterManager
{
	public static function parseCode($code)
	{
		$result = array(
			'ENTITY_TYPE_ID' => \CCrmOwnerType::Undefined,
			'TYPE_ID' => EntityCounterType::UNDEFINED,
			'EXTRAS' => array()

		);

		$parts = explode('_', $code);

		$qty = count($parts);
		if($qty >= 2)
		{
			$result['ENTITY_TYPE_ID'] = \CCrmOwnerType::ResolveID($parts[1]);
		}

		if($result['ENTITY_TYPE_ID'] === \CCrmOwnerType::Deal && $qty >= 4)
		{
			$categoryID = -1;
			if(preg_match('/c([0-9]+)/i', $parts[2], $m) === 1)
			{
				$categoryID = (int)$m[1];
			}
			if($categoryID >= 0)
			{
				$result['EXTRAS']['DEAL_CATEGORY_ID'] = $categoryID;
			}

			$result['TYPE_ID'] = EntityCounterType::resolveID($parts[3]);
		}
		elseif($qty >= 3)
		{
			$result['TYPE_ID'] = EntityCounterType::resolveID($parts[2]);
		}
		return $result;
	}
	public static function prepareCode($entityTypeID, $typeID, array $extras = null)
	{
		$codes = self::prepareCodes($entityTypeID, array($typeID), $extras);
		return isset($codes[0]) ? $codes[0] : '';
	}
	public static function prepareCodes($entityTypeID, $typeIDs, array $extras = null)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!is_array($typeIDs))
		{
			$typeIDs = array($typeIDs);
		}

		if(!is_array($extras))
		{
			$extras = array();
		}

		$results = array();
		if($entityTypeID === \CCrmOwnerType::Deal)
		{
			$categoryID = isset($extras['DEAL_CATEGORY_ID']) ? (int)$extras['DEAL_CATEGORY_ID'] : -1;
			if($categoryID < 0)
			{
				$entityID = isset($extras['ENTITY_ID']) ? (int)$extras['ENTITY_ID'] : 0;
				if($entityID > 0)
				{
					$categoryID = \CCrmDeal::GetCategoryID($entityID);
				}
			}

			$entityName = mb_strtolower(\CCrmOwnerType::DealName);
			$extendedMode = isset($extras['EXTENDED_MODE']) && $extras['EXTENDED_MODE'] === true;
			foreach($typeIDs as $typeID)
			{
				$typeName = mb_strtolower(EntityCounterType::resolveName($typeID));
				if($typeName === '')
				{
					continue;
				}

				if($categoryID >= 0)
				{
					$results[] = "crm_{$entityName}_c{$categoryID}_{$typeName}";
					if($extendedMode)
					{
						$results[] = "crm_{$entityName}_{$typeName}";
					}
				}
				else
				{
					$results[] = "crm_{$entityName}_{$typeName}";
				}
			}
		}
		else
		{
			$entityName = mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeID));
			if($entityName !== '')
			{
				foreach($typeIDs as $typeID)
				{
					$typeName = mb_strtolower(EntityCounterType::resolveName($typeID));
					if($typeName !== '')
					{
						$results[] = "crm_{$entityName}_{$typeName}";
					}
				}
			}
		}

		return $results;
	}
	public static function prepareValue($code, $userID = 0)
	{
		$counter = EntityCounterFactory::createNamed($code);
		if($counter !== null)
		{
			return $counter->getValue();
		}

		$parts = self::parseCode($code);
		if($parts['ENTITY_TYPE_ID'] !== \CCrmOwnerType::Undefined)
		{
			$counter = EntityCounterFactory::create(
				$parts['ENTITY_TYPE_ID'],
				$parts['TYPE_ID'],
				$userID > 0 ? $userID : \CCrmSecurityHelper::GetCurrentUserID(),
				$parts['EXTRAS']
			);
			return $counter->getValue();
		}

		return 0;
	}
	public static function reset(array $codes, array $userIDs)
	{
		$codes = array_unique($codes);
		if(!empty($userIDs))
		{
			foreach($userIDs as $userID)
			{
				foreach($codes as $code)
				{
					EntityCounter::resetByCode($code, $userID);
				}
			}
		}
		else
		{
			foreach($codes as $code)
			{
				EntityCounter::resetByCodeForAll($code);
			}
		}
	}
	public static function processSettingChange($name, $value)
	{
		if($name !== \CCrmUserCounterSettings::ReckonActivitylessItems)
		{
			return;
		}

		$codes = self::prepareCodes(\CCrmOwnerType::Lead, array(EntityCounterType::IDLE, EntityCounterType::ALL));
		foreach($codes as $code)
		{
			\CUserCounter::DeleteByCode($code);
		}

		$codes = self::prepareCodes(\CCrmOwnerType::Order, array(EntityCounterType::IDLE, EntityCounterType::ALL));
		foreach($codes as $code)
		{
			\CUserCounter::DeleteByCode($code);
		}

		$categoryIDs = \Bitrix\Crm\Category\DealCategory::getAllIDs();
		foreach($categoryIDs as $categoryID)
		{
			$codes = self::prepareCodes(
				\CCrmOwnerType::Deal,
				array(EntityCounterType::IDLE, EntityCounterType::ALL),
				array('DEAL_CATEGORY_ID' => $categoryID)
			);
			foreach($codes as $code)
			{
				\CUserCounter::DeleteByCode($code);
			}
		}

		$codes = self::prepareCodes(
			\CCrmOwnerType::Deal,
			array(EntityCounterType::IDLE, EntityCounterType::ALL)
		);
		foreach($codes as $code)
		{
			\CUserCounter::DeleteByCode($code);
		}
	}
}