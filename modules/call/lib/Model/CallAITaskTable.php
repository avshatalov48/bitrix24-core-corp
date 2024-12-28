<?php

namespace Bitrix\Call\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;
use Bitrix\Im\Model\CallTable;
use Bitrix\Call\Integration\AI\SenseType;
use Bitrix\Call\Integration\AI\Task\AITask;

\Bitrix\Main\Loader::includeModule('im');

/**
 * Class CallAITaskTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CallAITask_Query query()
 * @method static EO_CallAITask_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CallAITask_Result getById($id)
 * @method static EO_CallAITask_Result getList(array $parameters = [])
 * @method static EO_CallAITask_Entity getEntity()
 * @method static \Bitrix\Call\Model\EO_CallAITask createObject($setDefaultValues = true)
 * @method static \Bitrix\Call\Model\EO_CallAITask_Collection createCollection()
 * @method static \Bitrix\Call\Model\EO_CallAITask wakeUpObject($row)
 * @method static \Bitrix\Call\Model\EO_CallAITask_Collection wakeUpCollection($rows)
 */
class CallAITaskTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_call_ai_task';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new IntegerField('CALL_ID'))
				->configureRequired(),

			(new IntegerField('TRACK_ID'))
				->configureNullable(),

			(new IntegerField('OUTCOME_ID'))
				->configureNullable(),

			(new EnumField('TYPE'))
				->configureValues([
					SenseType::TRANSCRIBE->value,
					SenseType::SUMMARY->value,
					SenseType::OVERVIEW->value,
					SenseType::INSIGHTS->value,
				])
				->configureNullable(),

			(new DatetimeField('DATE_CREATE'))
				->configureDefaultValue(function (){return new DateTime;}),

			(new DatetimeField('DATE_FINISHED'))
				->configureNullable(),

			(new EnumField('STATUS'))
				->configureValues([
					AITask::STATUS_READY,
					AITask::STATUS_PENDING,
					AITask::STATUS_FINISHED,
					AITask::STATUS_FAILED,
				])
				->configureRequired()
				->configureDefaultValue(AITask::STATUS_READY),

			(new StringField('HASH'))
				->configureNullable()
				->configureSize(50),

			(new StringField('LANGUAGE_ID'))
				->configureNullable()
				->configureSize(2),

			(new StringField('ERROR_CODE'))
				->configureSize(100)
				->configureNullable(),

			(new StringField('ERROR_MESSAGE'))
				->configureNullable(),

			(new Reference('TRACK', CallTrackTable::class, Join::on('this.TRACK_ID', 'ref.ID'))),

			(new Reference('CALL', CallTable::class, Join::on('this.CALL_ID', 'ref.ID'))),

			(new Reference('OUTCOME', CallOutcomeTable::class, Join::on('this.OUTCOME_ID', 'ref.ID'))),
		];
	}
}