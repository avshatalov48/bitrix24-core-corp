<?php

namespace Bitrix\Crm\Model;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\Type\DateTime;

class ActivityPingQueueTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_act_ping_queue';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new IntegerField('ACTIVITY_ID'))
				->configureRequired(),
			(new DatetimeField('PING_DATETIME'))
				->configureDefaultValue(static fn(): DateTime => new DateTime())
				->configureRequired(),
		];
	}

	public static function getIdsByActivityId(int $activityId): array
	{
		return static::getList([
			'select' => ['ID'],
			'filter' => [
				'=ACTIVITY_ID' => $activityId,
			],
		])->fetchCollection()->getList('ID');
	}

	public static function deleteByActivityId(int $activityId): void
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		$connection->query(
			sprintf(
				'DELETE FROM %s WHERE ACTIVITY_ID = %d',
				$helper->quote(static::getTableName()),
				$helper->convertToDbInteger($activityId)
			)
		);
	}
}
