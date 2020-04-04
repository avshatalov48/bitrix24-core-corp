<?php


namespace Bitrix\Disk\Rest\Entity;


final class Folder extends BaseObject
{
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
			'REAL_OBJECT_ID' => true,
			'PARENT_ID' => true,
			'DELETED_TYPE' => true,
			'CREATE_TIME' => true,
			'UPDATE_TIME' => true,
			'DELETE_TIME' => true,
			'CREATED_BY' => true,
			'UPDATED_BY' => true,
			'DELETED_BY' => true,
		);
	}
}