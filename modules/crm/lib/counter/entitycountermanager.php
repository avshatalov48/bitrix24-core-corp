<?php
namespace Bitrix\Crm\Counter;
use Bitrix\Crm\Service\Container;

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

		$factory = Container::getInstance()->getFactory($result['ENTITY_TYPE_ID']);
		if($factory && $factory->isCategoriesSupported() && $qty >= 4)
		{
			$categoryID = -1;
			if(preg_match('/c([0-9]+)/i', $parts[2], $m) === 1)
			{
				$categoryID = (int)$m[1];
			}
			if($categoryID >= 0)
			{
				$extrasCategoryKey =
					$result['ENTITY_TYPE_ID'] === \CCrmOwnerType::Deal
					? 'DEAL_CATEGORY_ID'
					: 'CATEGORY_ID'
				;
				$result['EXTRAS'][$extrasCategoryKey] = $categoryID;
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
		$codes = self::prepareCodes($entityTypeID, [$typeID], $extras);

		return isset($codes[0]) ? $codes[0] : '';
	}

	public static function prepareCodes($entityTypeID, $typeIDs, array $extras = null)
	{
		$entityTypeID = (int)$entityTypeID;

		if(!is_array($typeIDs))
		{
			$typeIDs = [$typeIDs];
		}

		if(!is_array($extras))
		{
			$extras = [];
		}

		$factory = Container::getInstance()->getFactory($entityTypeID);
		if (!$factory || !$factory->isCountersEnabled())
		{
			return [];
		}

		$entityName = mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeID));

		$results = [];

		$categoryId = null;
		if ($factory->isCategoriesSupported())
		{
			$categoryId = $extras['CATEGORY_ID'] ?? $extras['DEAL_CATEGORY_ID'] ?? null;
			if (!is_null($categoryId))
			{
				$categoryId = (int)$categoryId;
				if ($categoryId < 0) // compatibility with $categoryId=-1 for all deal categories
				{
					$categoryId = null;
				}
			}
			if (is_null($categoryId))
			{
				$entityId = (int)($extras['ENTITY_ID'] ?? 0);
				if ($entityId > 0)
				{
					$categoryId = $factory->getItemCategoryId($entityId);
				}
			}
		}

		foreach($typeIDs as $typeID)
		{
			$typeName = mb_strtolower(EntityCounterType::resolveName($typeID));
			if($typeName === '')
			{
				continue;
			}

			if(!is_null($categoryId)) // counter for definite category
			{
				$results[] = "crm_{$entityName}_c{$categoryId}_{$typeName}";
			}
			$results[] = "crm_{$entityName}_{$typeName}";
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
				array('CATEGORY_ID' => $categoryID)
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