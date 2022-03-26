<?php

namespace Bitrix\Recyclebin\Internals\Models;

use Bitrix\Main\Entity;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class RecyclebinFileTable
 * @package Bitrix\Recyclebin\Model
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RecyclebinFile_Query query()
 * @method static EO_RecyclebinFile_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_RecyclebinFile_Result getById($id)
 * @method static EO_RecyclebinFile_Result getList(array $parameters = array())
 * @method static EO_RecyclebinFile_Entity getEntity()
 * @method static \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile createObject($setDefaultValues = true)
 * @method static \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile_Collection createCollection()
 * @method static \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile wakeUpObject($row)
 * @method static \Bitrix\Recyclebin\Internals\Models\EO_RecyclebinFile_Collection wakeUpCollection($rows)
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