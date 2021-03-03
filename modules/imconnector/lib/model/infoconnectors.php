<?php
namespace Bitrix\ImConnector\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\Entity\Validator\Length;
use	Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type;

Loc::loadMessages(__FILE__);

/**
 * Class ConnectorsInfoTable
 * @package Bitrix\ImOpenLines\Model
 */
class InfoConnectorsTable extends Entity\DataManager
{
	/**
	 * Return DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_imconnectors_info_connectors';
	}

	/**
	 * Returns entity map definition
	 *
	 * @return array
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getMap(): array
	{
		return [
			new Entity\IntegerField('LINE_ID', [
				'primary' => true
			]),
			new Entity\TextField('DATA', [
				'serialized' => true,
			]),
			new Entity\DatetimeField('EXPIRES', [
				'default_value' => new Type\DateTime,
			]),
			new Entity\StringField('DATA_HASH', [
				'size' => 32
			]),
		];
	}
}