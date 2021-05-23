<?php
namespace Bitrix\Location\Model;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Fields;

/**
 * Class NameTable
 * @package Bitrix\Location\Model
 */
class LocationNameTable extends Main\ORM\Data\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_location_name';
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
				->configurePrimary(true)
				->configureRequired(true),

			(new Fields\StringField('LANGUAGE_ID'))
				->configurePrimary(true)
				->configureRequired(true)
				->addValidator(new Main\ORM\Fields\Validators\LengthValidator(2, 2)),

			(new Fields\StringField('NAME'))
				->configureRequired(true)
				->addValidator(new Main\ORM\Fields\Validators\LengthValidator(1, 1000)),

			(new Fields\StringField('NAME_NORMALIZED'))
				->addValidator(new Main\ORM\Fields\Validators\LengthValidator(null, 1000)),

			// Ref

			(new Reference('LOCATION', LocationTable::class,
				Join::on('this.LOCATION_ID', 'ref.ID')))
				->configureJoinType('inner')
		];
	}

	/**
	 * @param int $locationId
	 * @throws Main\Db\SqlQueryException
	 */
	public static function deleteByLocationId(int $locationId)
	{
		Application::getConnection()->queryExecute("
			DELETE 
				FROM ".self::getTableName()." 
			WHERE LOCATION_ID=".(int)$locationId
		);
	}
}