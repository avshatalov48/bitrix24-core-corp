<?php

namespace Bitrix\Disk\Rest\Entity;

use Bitrix\Disk\Internals\VersionTable;
use Bitrix\Main\Type\DateTime;

final class Version extends Base
{
	/**
	 * Gets all fields (DataManager fields).
	 * @return array
	 */
	public function getDataManagerFields()
	{
		return VersionTable::getMap();
	}

	/**
	 * Gets fields which entity can show in response.
	 * @return array
	 */
	public function getFieldsForShow()
	{
		return array(
			'ID' => true,
			'OBJECT_ID' => true,
			'SIZE' => true,
			'NAME' => true,
			'CREATE_TIME' => true,
			'CREATED_BY' => true,
			'GLOBAL_CONTENT_VERSION' => true,
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
			'SIZE' => true,
			'NAME' => true,
			'CREATE_TIME' => true,
			'CREATED_BY' => true,
		);
	}

	/**
	 * Gets fields which Externalizer or Internalizer should modify.
	 * @return array
	 */
	public function getFieldsForMap()
	{
		return array(
			'CREATE_TIME' => array(
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