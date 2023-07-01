<?php

namespace Bitrix\Crm\Model;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;

/**
 * Class ActivityPingOffsetsTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ActivityPingOffsets_Query query()
 * @method static EO_ActivityPingOffsets_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ActivityPingOffsets_Result getById($id)
 * @method static EO_ActivityPingOffsets_Result getList(array $parameters = [])
 * @method static EO_ActivityPingOffsets_Entity getEntity()
 * @method static \Bitrix\Crm\Model\EO_ActivityPingOffsets createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Model\EO_ActivityPingOffsets_Collection createCollection()
 * @method static \Bitrix\Crm\Model\EO_ActivityPingOffsets wakeUpObject($row)
 * @method static \Bitrix\Crm\Model\EO_ActivityPingOffsets_Collection wakeUpCollection($rows)
 */
class ActivityPingOffsetsTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_act_ping_offsets';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new IntegerField('ACTIVITY_ID'))
				->configureRequired(),
			(new IntegerField('OFFSET'))
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

	public static function getOffsetsByActivityId(int $activityId): array
	{
		return static::getList([
			'select' => ['OFFSET'],
			'filter' => [
				'=ACTIVITY_ID' => $activityId,
			],
		])->fetchCollection()->getList('OFFSET');
	}

	public static function deleteByActivityId(int $activityId): void
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		$connection->query(
			sprintf(
				'DELETE FROM %s WHERE ACTIVITY_ID = %d',
				$helper->quote(static::getTableName()),
				$activityId
			)
		);
	}
}
