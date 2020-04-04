<?php


namespace Bitrix\Disk\Rest\Entity;


use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Main\Type\DateTime;

class BaseObject extends Base
{
	/**
	 * Gets all fields (DataManager fields).
	 * @return array
	 */
	public function getDataManagerFields()
	{
		return ObjectTable::getMap();
	}

	/**
	 * Gets fields which entity can show in response.
	 * @return array
	 */
	public function getFieldsForShow()
	{
		return array(
			'ID' => true,
			'NAME' => true,
			'CODE' => true,
			'STORAGE_ID' => true,
			'TYPE' => true,
			'PARENT_ID' => true,
			'DELETED_TYPE' => true,
			'GLOBAL_CONTENT_VERSION' => true,
			'FILE_ID' => true,
			'SIZE' => true,
			'CREATE_TIME' => true,
			'UPDATE_TIME' => true,
			'DELETE_TIME' => true,
			'CREATED_BY' => true,
			'UPDATED_BY' => true,
			'DELETED_BY' => true,
		);
	}

	/**
	 * Gets fields which entity can filter in getList().
	 * @return array
	 */
	public function getFieldsForFilter()
	{
		return array(
			'ID' => true,
			'NAME' => true,
			'CODE' => true,
			'STORAGE_ID' => true,
			'TYPE' => true,
			'PARENT_ID' => true,
			'DELETED_TYPE' => true,
			'CREATE_TIME' => true,
			'UPDATE_TIME' => true,
			'DELETE_TIME' => true,
		);
	}

	/**
	 * Gets fields which Externalizer or Internalizer should modify.
	 * @return array
	 */
	public function getFieldsForMap()
	{
		return array(
			'TYPE' => array(
				'IN' => function($externalValue){
					switch($externalValue)
					{
						case 'folder':
							return ObjectTable::TYPE_FOLDER;
						case 'file':
							return ObjectTable::TYPE_FILE;
					}

					return null;
				},
				'OUT' => function($internalValue){
					switch($internalValue)
					{
						case ObjectTable::TYPE_FOLDER:
							return 'folder';
						case ObjectTable::TYPE_FILE:
							return 'file';
					}

					return null;
				}
			),
			'CREATE_TIME' => array(
				'IN' => function($externalValue){
					return \CRestUtil::unConvertDateTime($externalValue);
				},
				'OUT' => function(DateTime $internalValue = null){
					return \CRestUtil::convertDateTime($internalValue);
				},
			),
			'UPDATE_TIME' => array(
				'IN' => function($externalValue){
					return \CRestUtil::unConvertDateTime($externalValue);
				},
				'OUT' => function(DateTime $internalValue = null){
					return \CRestUtil::convertDateTime($internalValue);
				},
			),
			'DELETE_TIME' => array(
				'IN' => function($externalValue){
					return \CRestUtil::unConvertDateTime($externalValue);
				},
				'OUT' => function(DateTime $internalValue = null){
					return \CRestUtil::convertDateTime($internalValue);
				},
			),
		);
	}
}