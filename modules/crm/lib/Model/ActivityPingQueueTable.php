<?php

namespace Bitrix\Crm\Model;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\Type\DateTime;

/**
 * Class ActivityPingQueueTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ActivityPingQueue_Query query()
 * @method static EO_ActivityPingQueue_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ActivityPingQueue_Result getById($id)
 * @method static EO_ActivityPingQueue_Result getList(array $parameters = [])
 * @method static EO_ActivityPingQueue_Entity getEntity()
 * @method static \Bitrix\Crm\Model\EO_ActivityPingQueue createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Model\EO_ActivityPingQueue_Collection createCollection()
 * @method static \Bitrix\Crm\Model\EO_ActivityPingQueue wakeUpObject($row)
 * @method static \Bitrix\Crm\Model\EO_ActivityPingQueue_Collection wakeUpCollection($rows)
 */
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
