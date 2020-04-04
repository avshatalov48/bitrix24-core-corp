<?php


namespace Bitrix\Disk\Rest\Entity;


use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\AttachedObjectTable;
use Bitrix\Main\Type\DateTime;

final class AttachedObject extends Base
{
	/**
	 * Gets all fields (DataManager fields).
	 * @return array
	 */
	public function getDataManagerFields()
	{
		return AttachedObjectTable::getMap();
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
			'MODULE_ID' => true,
			'ENTITY_TYPE' => true,
			'ENTITY_ID' => true,
			'CREATE_TIME' => true,
			'CREATED_BY' => true,
		);
	}

	/**
	 * Gets fields which entity can filter in getList().
	 * @return array
	 */
	public function getFieldsForFilter()
	{
		return array();
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
			'ENTITY_TYPE' => array(
				'IN' => function($externalValue){
					$userFieldManager = Driver::getInstance()->getUserFieldManager();
					list($connector, ) = $userFieldManager->getConnectorDataByEntityType($externalValue);

					return $connector;
				},
				'OUT' => function($internalValue){
					$userFieldManager = Driver::getInstance()->getUserFieldManager();
					foreach($userFieldManager->getConnectors() as $code => $connectorData)
					{
						list($connector, ) = $connectorData;
						if($connector === $internalValue)
						{
							return $code;
						}
					}

					return null;
				}
			)
		);
	}
}