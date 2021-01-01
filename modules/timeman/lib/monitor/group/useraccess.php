<?php
namespace Bitrix\Timeman\Monitor\Group;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Type\DateTime;
use Bitrix\Timeman\Model\Monitor\MonitorUserAccessTable;

class UserAccess
{
	public static function allowSite(int $userId, int $siteId): bool
	{
		return self::allowEntity($userId, EntityType::SITE, $siteId);
	}

	public static function allowApp(int $userId, int $appId): bool
	{
		return self::allowEntity($userId, EntityType::APP, $appId);
	}

	private static function allowEntity(int $userId, string $entityType, int $entityId, DateTime $dateStart = null, DateTime $dateFinish = null): bool
	{
		if (self::isUserAccessExists($userId, $entityType, $entityId))
		{
			return false;
		}

		global $USER;
		$approvedUserId = $USER->GetID();

		return MonitorUserAccessTable::add([
			'USER_ID' => $userId,
			'ENTITY_TYPE' => $entityType,
			'ENTITY_ID' => $entityId,
			'DATE_START' => $dateStart ?: new DateTime(),
			'DATE_FINISH' => $dateFinish,
			'APPROVED_USER_ID' => $approvedUserId,
			'DATE_CREATE' => new DateTime(),
			'GROUP_CODE' => Group::CODE_WORKING,
		])->isSuccess();
	}

	public static function isUserAccessExists(int $userId, string $entityType, int $entityId): bool
	{
		$existingAccesses = MonitorUserAccessTable::getList([
			'select' => [
				'USER_ID',
				'ENTITY_TYPE',
				'ENTITY_ID',
				'DATE_START',
				'DATE_FINISH',
			],
			'filter' => [
				'=USER_ID' => $userId,
				'=ENTITY_TYPE' => $entityType,
				'=ENTITY_ID' => $entityId,
			],
		])->fetchAll();

		if (!$existingAccesses)
		{
			return false;
		}

		return true;
	}

	public static function getAccessForUser(int $userId): array
	{
		$unsortedAccesses = MonitorUserAccessTable::getList([
			'select' => [
				'USER_ID',
				'ENTITY_TYPE',
				'GROUP_CODE',
				'ENTITY_ID',
				'DATE_START',
				'DATE_FINISH',
				'APPROVED_USER_ID',
				'TIME',
			],
			'filter' => [
				'=USER_ID' => $userId
			],
			'runtime' => [
				new ExpressionField(
					'TIME',
					'unix_timestamp(%s) - unix_timestamp(%s)',
					['DATE_FINISH', 'DATE_START']
				),
			],
		])->fetchAll();

		$accesses = [];
		foreach ($unsortedAccesses as $access)
		{
			$accesses[$access['ENTITY_TYPE']][$access['ENTITY_ID']] = [
				'GROUP_CODE' => $access['GROUP_CODE'],
				'DATE_START' => $access['DATE_START'],
				'DATE_FINISH' => $access['DATE_FINISH'],
				'TIME' => $access['TIME'],
				'APPROVED_USER_ID' => $access['APPROVED_USER_ID'],
			];
		}

		return $accesses;
	}
}