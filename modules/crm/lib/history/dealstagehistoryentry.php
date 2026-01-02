<?php

namespace Bitrix\Crm\History;

use Bitrix\Crm\History\StageHistory\DealStageHistory;
use Bitrix\Crm\History\Entity\DealStageHistoryTable;

use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Category\DealCategory;

class DealStageHistoryEntry
{
	public static function getAll($ownerID)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$query = new Query(DealStageHistoryTable::getEntity());
		$query->addFilter('=OWNER_ID', $ownerID);
		$query->addSelect('*');

		$results = array();
		$dbResult = $query->exec();
		while($fields = $dbResult->fetch())
		{
			$results[] = $fields;
		}
		return $results;
	}
	public static function getLatest($ownerID)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$subQuery = new Query(DealStageHistoryTable::getEntity());
		$subQuery->registerRuntimeField('', new ExpressionField('MAX_ID', 'MAX(ID)'));
		$subQuery->addSelect('MAX_ID');
		$subQuery->addFilter('=OWNER_ID', $ownerID);

		$query = new Query(DealStageHistoryTable::getEntity());
		$query->addSelect('*');
		$query->registerRuntimeField('',
			new ReferenceField('M',
				Base::getInstanceByQuery($subQuery),
				array('=this.ID' => 'ref.MAX_ID'),
				array('join_type' => 'INNER')
			)
		);

		$dbResult = $query->exec();
		$result = $dbResult->fetch();
		return is_array($result) ? $result : null;
	}
	public static function isRegistered($ownerID)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$query = new Query(DealStageHistoryTable::getEntity());
		$query->addSelect('ID');
		$query->addFilter('=OWNER_ID', $ownerID);
		$query->setLimit(1);

		$dbResult = $query->exec();
		$result = $dbResult->fetch();
		return is_array($result);
	}

	/**
	 * @deprecated
	 * @see DealStageHistory::registerItemAdd()
	 * @see DealStageHistory::registerItemUpdate()
	 */
	public static function register($ownerID, array $entityFields = null, array $options = null)
	{
		return false;
	}
	/**
	 * @deprecated
	 * @see DealStageHistory::registerItemDelete()
	 */
	public static function unregister($ownerID)
	{
	}
	/**
	 * @deprecated
	 * @see DealStageHistory::registerItemUpdate()
	 */
	public static function synchronize($ownerID, array $entityFields = null)
	{
		return false;
	}

	/**
	 * @deprecated
	 * @see DealStageHistory::registerItemUpdate()
	 */
	public static function processCagegoryChange($ownerID)
	{
	}
	protected static function parseDateString($str)
	{
		if($str === '')
		{
			return null;
		}

		try
		{
			$date = new Date($str, Date::convertFormatToPhp(FORMAT_DATE));
		}
		catch(Main\ObjectException $e)
		{
			try
			{
				$date = new DateTime($str, Date::convertFormatToPhp(FORMAT_DATETIME));
				$date->setTime(0, 0, 0);
			}
			catch(Main\ObjectException $e)
			{
				return null;
			}
		}
		return $date;
	}
	protected static function resolveEffectiveDate(array $fields)
	{
		$typeID = isset($fields['TYPE_ID']) ? (int)$fields['TYPE_ID'] : HistoryEntryType::MODIFICATION;
		if($typeID === HistoryEntryType::CREATION && isset($fields['START_DATE']))
		{
			return $fields['START_DATE'];
		}
		elseif($typeID === HistoryEntryType::FINALIZATION && isset($fields['END_DATE']))
		{
			return $fields['END_DATE'];
		}

		return isset($fields['CREATED_DATE']) ? $fields['CREATED_DATE'] : null;
	}

	public static function getListFilteredByPermissions(
		array $parameters,
		?int $userId = null,
		string $operation = UserPermissions::OPERATION_READ
	)
	{
		$userPermissions = Container::getInstance()->getUserPermissions($userId);
		if ($userPermissions->getUserId() === 0)
		{
			// no data for unauthorized user
			return [];
		}
		$entityTypes = array_merge(
			[
				\CCrmOwnerType::DealName,
			],
			DealCategory::getPermissionEntityTypeList()
		);
		$parameters['filter'] = $userPermissions->itemsList()->applyAvailableItemsFilter(
			$parameters['filter'] ?? [],
			$entityTypes,
			$operation,
			'OWNER_ID'
		);

		return DealStageHistoryTable::getList($parameters);
	}

	public static function getItemsCountFilteredByPermissions(
		array $filter,
		?int $userId = null,
		string $operation = UserPermissions::OPERATION_READ
	): int
	{
		$userPermissions = Container::getInstance()->getUserPermissions($userId);
		if ($userPermissions->getUserId() === 0)
		{
			// no data for unauthorized user
			return 0;
		}
		$entityTypes = array_merge(
			[
				\CCrmOwnerType::DealName,
			],
			DealCategory::getPermissionEntityTypeList()
		);
		$filter = $userPermissions->itemsList()->applyAvailableItemsFilter(
			$filter,
			$entityTypes,
			$operation,
			'OWNER_ID'
		);

		return DealStageHistoryTable::getCount($filter);
	}
}
