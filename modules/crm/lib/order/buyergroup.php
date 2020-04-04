<?php

namespace Bitrix\Crm\Order;

use Bitrix\Main\Config\Option;
use Bitrix\Main\GroupTable;
use Bitrix\Main\Type\Collection;

class BuyerGroup
{
	const BUYER_GROUP_NAME = 'CRM_SHOP_BUYER';

	/**
	 * Returns buyer groups list.
	 *
	 * @return array
	 */
	public static function getPublicList()
	{
		$buyerGroupIterator = GroupTable::getList([
			'filter' => [
				'LOGIC' => 'OR',
				'=IS_SYSTEM' => 'N',
				'=STRING_ID' => self::BUYER_GROUP_NAME,
			],
			'order' => [
				'C_SORT' => 'ASC',
				'ID' => 'ASC',
			],
		]);

		return $buyerGroupIterator->fetchAll();
	}

	/**
	 * Returns default buyer group id.
	 *
	 * @return string
	 */
	public static function getSystemGroupId()
	{
		return Option::get('crm', 'shop_buyer_group', 0);
	}

	/**
	 * Returns array of default buyer group ids.
	 *
	 * @return array
	 */
	public static function getDefaultGroups()
	{
		$defaultGroups = [];

		$defaultBuyerGroup = static::getSystemGroupId();

		if (!empty($defaultBuyerGroup))
		{
			$defaultGroups[] = $defaultBuyerGroup;
		}

		return $defaultGroups;
	}

	/**
	 * Appends buyer groups to existing system groups and returns new array of group ids.
	 * Mainly used for discount group lists.
	 *
	 * @param array $existingGroupIds
	 * @param array $newGroupIds
	 * @return array
	 */
	public static function prepareGroupIds(array $existingGroupIds, array $newGroupIds)
	{
		$buyerGroupIds = array_column(static::getPublicList(), 'ID');

		Collection::normalizeArrayValuesByInt($existingGroupIds, false);
		Collection::normalizeArrayValuesByInt($newGroupIds, false);
		Collection::normalizeArrayValuesByInt($buyerGroupIds, false);

		// filter unacceptable system groups
		$userGroupIds = array_intersect($newGroupIds, $buyerGroupIds);

		// add 2nd group by default if we are in "All buyers" group and backward operation
		if (in_array(static::getSystemGroupId(), $newGroupIds))
		{
			if (!in_array(2, $existingGroupIds))
			{
				$userGroupIds[] = 2;
			}
		}
		else
		{
			$existingGroupIds = array_diff($existingGroupIds, [2]);
		}

		// keep existing system groups and add new buyer groups
		return array_merge(array_diff($existingGroupIds, $buyerGroupIds), $userGroupIds);
	}
}