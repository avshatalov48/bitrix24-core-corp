<?php

namespace Bitrix\Location\Model;

use Bitrix\Main;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Query\Join;

class AddressFieldTable extends Main\ORM\Data\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_location_addr_fld';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [

			(new Fields\IntegerField('ADDRESS_ID'))
				->configureRequired(true)
				->configurePrimary(true),

			(new Fields\IntegerField('TYPE'))
				->configureRequired(true)
				->configurePrimary(true),

			(new Fields\StringField('VALUE'))
				->addValidator(new Main\ORM\Fields\Validators\LengthValidator(null, 1024)),

			(new Fields\StringField('VALUE_NORMALIZED'))
				->addValidator(new Main\ORM\Fields\Validators\LengthValidator(null, 1024)),

			// Ref

			(new Fields\Relations\Reference('ADDRESS', AddressTable::class,
				Join::on('this.ADDRESS_ID', 'ref.ID')))
				->configureJoinType('inner')
		];
	}

	public static function deleteByAddressId(int $addressId)
	{
		Main\Application::getConnection()->queryExecute("
			DELETE 
				FROM ".self::getTableName()." 
			WHERE 
				ADDRESS_ID=".(int)$addressId
		);
	}
}
