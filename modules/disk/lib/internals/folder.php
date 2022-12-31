<?php

namespace Bitrix\Disk\Internals;

use Bitrix\Main\Entity\DeleteResult;
use Bitrix\Main\Entity\EntityError;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Result;
use Bitrix\Main\Localization\Loc;

/**
 * Class FolderTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Folder_Query query()
 * @method static EO_Folder_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Folder_Result getById($id)
 * @method static EO_Folder_Result getList(array $parameters = [])
 * @method static EO_Folder_Entity getEntity()
 * @method static \Bitrix\Disk\Internals\EO_Folder createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Internals\EO_Folder_Collection createCollection()
 * @method static \Bitrix\Disk\Internals\EO_Folder wakeUpObject($row)
 * @method static \Bitrix\Disk\Internals\EO_Folder_Collection wakeUpCollection($rows)
 */
final class FolderTable extends ObjectTable
{
	const TYPE = ObjectTable::TYPE_FOLDER;

	public static function getMap()
	{
		$map = parent::getMap();
		$map['TYPE']['validation'] = array(__CLASS__, 'validateType');
		$map[] = new ExpressionField('HAS_SUBFOLDERS',
			'CASE WHEN EXISTS(
			SELECT \'x\' FROM b_disk_object_path p
				INNER JOIN b_disk_object o ON o.ID=p.OBJECT_ID AND o.TYPE=2
			WHERE p.PARENT_ID = %1$s AND p.DEPTH_LEVEL = 1 AND o.DELETED_TYPE = 0) THEN 1 ELSE 0 END',
		array('REAL_OBJECT_ID',), array('data_type' => 'boolean',));


		return $map;
	}

	public static function checkFields(Result $result, $primary, array $data)
	{
		if($result instanceof DeleteResult)
		{
			if(!ObjectPathTable::isLeaf($primary))
			{
				$result->addError(new EntityError(Loc::getMessage("DISK_OBJECT_ENTITY_ERROR_DELETE_NODE")));
			}
		}

		parent::checkFields($result, $primary, $data);
	}

	public static function add(array $data)
	{
		$data['TYPE'] = static::TYPE;

		return parent::add($data);
	}

	public static function validateTypeLogic($value)
	{
		return $value == static::TYPE;
	}

	public static function validateType()
	{
		return array(
			array(__CLASS__, 'validateTypeLogic')
		);
	}

}
