<?php

namespace Bitrix\Recyclebin\Internals\Models;

use Bitrix\Main\Entity;

/**
 * Class RecyclebinDataTable
 * @package Bitrix\Recyclebin\Model
 */
final class RecyclebinDataTable extends Entity\DataManager
{
	/**
	 * @inheritdoc
	 */
	public static function getMap()
	{
		$fieldsMap = array(
			new Entity\IntegerField(
				'ID', array(
						'primary'      => true,
						'autocomplete' => true
					)
			),
			new Entity\IntegerField('RECYCLEBIN_ID'),
			new Entity\ReferenceField(
				'RECYCLEBIN', 'Bitrix\Recyclebin\Model\Recyclebin', array('=this.RECYCLEBIN_ID' => 'ref.ID')
			),
			new Entity\StringField('ACTION'),
			new Entity\TextField('DATA')
		);

		return $fieldsMap;
	}

	public static function deleteByRecyclebinId($recyclebinId)
	{
		$connection = self::getEntity()->getConnection();
		$sql = "DELETE FROM ".self::getTableName()." WHERE `RECYCLEBIN_ID` = ".(int)$recyclebinId;

		return $connection->query($sql);
	}

	/**
	 * @inheritdoc
	 */
	public static function getTableName()
	{
		return 'b_recyclebin_data';
	}
}