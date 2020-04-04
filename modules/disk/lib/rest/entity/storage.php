<?php


namespace Bitrix\Disk\Rest\Entity;


use Bitrix\Disk\Internals\StorageTable;
use Bitrix\Disk\ProxyType;

final class Storage extends Base
{
	/**
	 * Gets all fields (DataManager fields).
	 * @return array
	 */
	public function getDataManagerFields()
	{
		return StorageTable::getMap();
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
			'ENTITY_ID' => true,
			'ENTITY_TYPE' => true,
			'CODE' => true,
			'MODULE_ID' => true,
			'ROOT_OBJECT_ID' => true,
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
			'ENTITY_ID' => true,
			'ENTITY_TYPE' => true,
			'CODE' => true,
		);
	}

	/**
	 * Gets fields which Externalizer or Internalizer should modify.
	 * @return array
	 */
	public function getFieldsForMap()
	{
		return array(
			'ENTITY_TYPE' => array(
				'IN' => function($externalValue){
					switch($externalValue)
					{
						case 'user':
							return ProxyType\User::className();
						case 'group':
							return ProxyType\Group::className();
						case 'common':
							return ProxyType\Common::className();
					}

					return null;
				},
				'OUT' => function($internalValue){
					switch($internalValue)
					{
						case ProxyType\User::className():
							return 'user';
						case ProxyType\Group::className():
							return 'group';
						case ProxyType\Common::className():
							return 'common';
						case ProxyType\RestApp::className():
							return 'restapp';
					}

					return null;
				}
			)
		);
	}
}