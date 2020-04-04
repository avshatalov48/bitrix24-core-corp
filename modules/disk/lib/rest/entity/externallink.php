<?php


namespace Bitrix\Disk\Rest\Entity;


use Bitrix\Disk\Internals\ExternalLinkTable;
use Bitrix\Disk\ProxyType;
use Bitrix\Main\Type\DateTime;

final class ExternalLink extends Base
{
	/**
	 * Gets all fields (DataManager fields).
	 * @return array
	 */
	public function getDataManagerFields()
	{
		return ExternalLinkTable::getMap();
	}

	/**
	 * Gets fields which entity can show in response.
	 * @return array
	 */
	public function getFieldsForShow()
	{
		return array(
			'OBJECT_ID' => true,
			'VERSION_ID' => true,
			'DESCRIPTION' => true,
			'DOWNLOAD_COUNT' => true,
			'CREATE_TIME' => true,
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