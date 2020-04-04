<?php
namespace Bitrix\ImConnector\Model;

use Bitrix\Main\Entity;
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
	public static function getTableName()
	{
		return 'b_imconnectors_info_connectors';
	}

	/**
	 * Returns entity map definition
	 *
	 * @return array
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getMap()
	{
		return array(
			new  Entity\IntegerField('LINE_ID', array(
				'primary' => true
			)),
			new  Entity\TextField('DATA', array(
				'serialized' => true,
			)),
			new  Entity\DatetimeField('EXPIRES', array(
				'default_value' => new Type\DateTime,
			)),
		);
	}
}