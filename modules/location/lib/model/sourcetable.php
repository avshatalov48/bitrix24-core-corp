<?php

namespace Bitrix\Location\Model;

use Bitrix\Main;
use	Bitrix\Main\ORM\Fields;

/**
 * Class SourceTable
 * @package Bitrix\Location\Model
 * @internal
 */
class SourceTable extends Main\ORM\Data\DataManager
{
	/**
	 * @inheritDoc
	 */
	public static function getTableName()
	{
		return 'b_location_source';
	}

	/**
	 * @inheritDoc
	 */
	public static function getMap()
	{
		return [
			(new Fields\StringField('CODE'))
				->addValidator(new Main\ORM\Fields\Validators\LengthValidator(1, 15))
				->configurePrimary(true),
			(new Fields\StringField('NAME'))
				->configureRequired(true)
				->addValidator(new Main\ORM\Fields\Validators\LengthValidator(1, 255)),
			new Fields\TextField('CONFIG'),
		];
	}
}
