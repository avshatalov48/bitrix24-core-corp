<?php

namespace Bitrix\Crm\History\Entity;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class EntityStageHistoryWithSupposedTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_EntityStageHistoryWithSupposed_Query query()
 * @method static EO_EntityStageHistoryWithSupposed_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_EntityStageHistoryWithSupposed_Result getById($id)
 * @method static EO_EntityStageHistoryWithSupposed_Result getList(array $parameters = [])
 * @method static EO_EntityStageHistoryWithSupposed_Entity getEntity()
 * @method static \Bitrix\Crm\History\Entity\EO_EntityStageHistoryWithSupposed createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\History\Entity\EO_EntityStageHistoryWithSupposed_Collection createCollection()
 * @method static \Bitrix\Crm\History\Entity\EO_EntityStageHistoryWithSupposed wakeUpObject($row)
 * @method static \Bitrix\Crm\History\Entity\EO_EntityStageHistoryWithSupposed_Collection wakeUpCollection($rows)
 */
class EntityStageHistoryWithSupposedTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_entity_stage_history_with_supposed';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
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
			(new IntegerField('CATEGORY_ID'))
				->configureRequired()
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
			(new BooleanField('IS_SUPPOSED'))
				->configureRequired()
				->configureValues('N', 'Y')
			,
			(new DateField('LAST_UPDATE_DATE'))
				->configureRequired()
			,
			(new DateField('CLOSE_DATE'))
				->configureRequired()
			,
			(new IntegerField('SPENT_TIME'))
				->configureRequired()
			,
		];
	}
}
