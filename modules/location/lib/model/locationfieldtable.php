<?php

namespace Bitrix\Location\Model;

use Bitrix\Main;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Query\Join;

class LocationFieldTable extends Main\ORM\Data\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_location_field';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [

			(new Fields\IntegerField('LOCATION_ID'))
				->configureRequired(true)
				->configurePrimary(true),

			(new Fields\IntegerField('TYPE'))
				->configureRequired(true)
				->configurePrimary(true),

			(new Fields\StringField('VALUE'))
				->addValidator(new Main\ORM\Fields\Validators\LengthValidator(null, 255)),

			// Ref

			(new Fields\Relations\Reference('LOCATION', LocationTable::class,
				Join::on('this.LOCATION_ID', 'ref.ID')))
				->configureJoinType('inner')
		];
	}

	public static function deleteByLocationId(int $locationId)
	{
		Main\Application::getConnection()->queryExecute("
			DELETE 
				FROM ".self::getTableName()." 
			WHERE 
				LOCATION_ID=".(int)$locationId
		);
	}
}