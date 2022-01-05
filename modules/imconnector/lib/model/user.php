<?php
namespace Bitrix\ImConnector\Model;

use Bitrix\Main;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Relations\Reference;

/**
 * Class UserTable
 *
 * @package Bitrix\ImConnector\Model
 */
class UserTable extends DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_imconnectors_user';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			(new IntegerField('USER_ID'))
				->configurePrimary(),

			new StringField('UNITED_TYPE'),

			new StringField('UNITED_ID'),

			(new Reference(
				'USER',
				Main\UserTable::class,
				Join::on('this.USER_ID', 'ref.ID')
			)),
		];
	}
}