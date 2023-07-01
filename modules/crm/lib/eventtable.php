<?php

namespace Bitrix\Crm;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class EventTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Event_Query query()
 * @method static EO_Event_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Event_Result getById($id)
 * @method static EO_Event_Result getList(array $parameters = [])
 * @method static EO_Event_Entity getEntity()
 * @method static \Bitrix\Crm\EO_Event createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_Event_Collection createCollection()
 * @method static \Bitrix\Crm\EO_Event wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_Event_Collection wakeUpCollection($rows)
 */
class EventTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_event';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			new DatetimeField('DATE_CREATE'),
			new IntegerField('CREATED_BY_ID'),
			new StringField('EVENT_ID'),
			new StringField('EVENT_NAME'),
			new StringField('EVENT_TEXT_1'),
			new StringField('EVENT_TEXT_2'),
			new IntegerField('EVENT_TYPE'),
			// it's serialized int[]. serialization method - php. StringField instead of ArrayField for backwards compatibility
			new StringField('FILES'),
			(new OneToMany('EVENT_RELATION', EventRelationsTable::class, 'EVENT_BY')),
		];
	}
}
