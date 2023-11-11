<?php
namespace Bitrix\ImOpenLines\Model;

use Bitrix\Main;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\DateTime;

use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\Relations\Reference;

use Bitrix\Main\UserTable;


class UserLogTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_imopenlines_user_log';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			new IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new IntegerField('USER_ID', [
				'required' => true,
			]),
			new StringField('TYPE', [
				'required' => true,
			]),
			new StringField('DATA', [
				'required' => true,
			]),
			new DatetimeField('DATE_CREATE', [
				'default_value' => [__CLASS__, 'getCurrentDate'],
			]),
			new Reference(
				'USER',
				UserTable::class,
				Join::on('this.USER_ID', 'ref.ID')
			),
		];
	}

	public static function getCurrentDate(): DateTime
	{
		return new DateTime();
	}
}
