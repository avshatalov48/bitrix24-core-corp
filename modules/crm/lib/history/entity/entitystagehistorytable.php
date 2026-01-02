<?php

namespace Bitrix\Crm\History\Entity;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class EntityStageHistoryTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_EntityStageHistory_Query query()
 * @method static EO_EntityStageHistory_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_EntityStageHistory_Result getById($id)
 * @method static EO_EntityStageHistory_Result getList(array $parameters = [])
 * @method static EO_EntityStageHistory_Entity getEntity()
 * @method static \Bitrix\Crm\History\Entity\EO_EntityStageHistory createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\History\Entity\EO_EntityStageHistory_Collection createCollection()
 * @method static \Bitrix\Crm\History\Entity\EO_EntityStageHistory wakeUpObject($row)
 * @method static \Bitrix\Crm\History\Entity\EO_EntityStageHistory_Collection wakeUpCollection($rows)
 */
final class EntityStageHistoryTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_entity_stage_history';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,
			(new IntegerField('TYPE_ID'))
				->configureRequired()
			,
			(new IntegerField('OWNER_TYPE_ID'))
				->configureRequired()
			,
			(new IntegerField('OWNER_ID'))
				->configureRequired()
			,
			(new DatetimeField('CREATED_TIME'))
				->configureRequired()
			,
			(new DateField('CREATED_DATE'))
				->configureRequired()
			,
			(new DateField('EFFECTIVE_DATE'))
				->configureRequired()
			,
			(new DateField('START_DATE'))
				->configureRequired()
			,
			(new DateField('END_DATE'))
				->configureRequired()
			,
			(new IntegerField('PERIOD_YEAR'))
				->configureRequired()
			,
			(new IntegerField('PERIOD_QUARTER'))
				->configureRequired()
			,
			(new IntegerField('PERIOD_MONTH'))
				->configureRequired()
			,
			(new IntegerField('START_PERIOD_YEAR'))
				->configureRequired()
			,
			(new IntegerField('START_PERIOD_QUARTER'))
				->configureRequired()
			,
			(new IntegerField('START_PERIOD_MONTH'))
				->configureRequired()
			,
			(new IntegerField('END_PERIOD_YEAR'))
				->configureRequired()
			,
			(new IntegerField('END_PERIOD_QUARTER'))
				->configureRequired()
			,
			(new IntegerField('END_PERIOD_MONTH'))
				->configureRequired()
			,
			(new IntegerField('RESPONSIBLE_ID'))
				->configureRequired()
			,
			(new IntegerField('CATEGORY_ID'))
				->configureNullable()
			,
			(new StringField('STAGE_SEMANTIC_ID'))
				->configureRequired()
			,
			(new StringField('STAGE_ID'))
				->configureRequired()
			,
			(new BooleanField('IS_LOST'))
				->configureRequired()
				->configureValues('N', 'Y')
			,
		];
	}
}
