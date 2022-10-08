<?php

namespace Bitrix\DocumentGenerator\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\Type\DateTime;

class ActualizeQueueTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_documentgenerator_actualize_queue';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('DOCUMENT_ID'))
				->configurePrimary()
			,
			(new IntegerField('USER_ID'))
				->configureNullable(),
			(new DatetimeField('ADDED_TIME'))
				->configureDefaultValue(static function() {
					return new DateTime();
				})
			,
		];
	}
}
