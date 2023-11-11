<?php
namespace Bitrix\ImOpenLines\Model;

use Bitrix\Main;
use Bitrix\Main\ORM\Data\Internal\MergeTrait;
use Bitrix\Main\ORM\Fields\BooleanField;

use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;

use Bitrix\Main\UserTable;


class UserOptionTable extends Main\Entity\DataManager
{
	use MergeTrait;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_imopenlines_user_option';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			new IntegerField('USER_ID', [
				'primary' => true,
			]),
			new BooleanField('PAUSE', [
				'values' => ['N', 'Y'],
				'default_value' => 'N',
			]),
			new Reference(
				'USER',
				UserTable::class,
				Join::on('this.USER_ID', 'ref.ID')
			),
		];
	}
}
