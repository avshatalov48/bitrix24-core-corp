<?php

namespace Bitrix\Crm;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

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
			new StringField('FILES'),
		];
	}
}