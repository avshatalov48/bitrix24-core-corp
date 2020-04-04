<?php

namespace Bitrix\Recyclebin\Internals\Models;

use Bitrix\Main\Entity;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class RecyclebinFileTable
 * @package Bitrix\Recyclebin\Model
 */
final class RecyclebinFileTable extends Entity\DataManager
{
	/**
	 * @inheritdoc
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getMap()
	{
		$fieldsMap = array(
			new IntegerField(
				'ID', array(
						'primary'      => true,
						'autocomplete' => true
					)
			),
			new IntegerField('RECYCLEBIN_ID'),
			new ReferenceField(
				'RECYCLEBIN', 'Bitrix\Recyclebin\Model\Recyclebin', array('=this.RECYCLEBIN_ID' => 'ref.ID')
			),
			new IntegerField('FILE_ID'),
			new StringField('STORAGE_TYPE'),
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
		return 'b_recyclebin_files';
	}
}