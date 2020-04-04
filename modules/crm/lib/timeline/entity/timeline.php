<?php
namespace Bitrix\Crm\Timeline\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;

class TimelineTable  extends Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_timeline';
	}
	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array('data_type' => 'integer', 'primary' => true, 'autocomplete' => true),
			'TYPE_ID' => array('data_type' => 'integer', 'required' => true),
			'TYPE_CATEGORY_ID' => array('data_type' => 'integer'),
			'CREATED' => array('data_type' => 'datetime', 'required' => true),
			'AUTHOR_ID' => array('data_type' => 'integer'),
			'ASSOCIATED_ENTITY_ID' => array('data_type' => 'integer'),
			'ASSOCIATED_ENTITY_TYPE_ID' => array('data_type' => 'integer'),
			'ASSOCIATED_ENTITY_CLASS_NAME' => array('data_type' => 'string'),
			'COMMENT' => array('data_type' => 'text'),
			'SETTINGS' => array('data_type' => 'text', 'serialized' => true),
			'BINDINGS' => array(
				'data_type' => TimelineBindingTable::class,
				'reference' => array('=this.ID' => 'ref.OWNER_ID')
			),
		);
	}
	public static function deleteByFilter(array $filter)
	{
		$values = array();

		if(isset($filter['TYPE_ID']))
		{
			$typeID = (int)$filter['TYPE_ID'];
			$values[] = "TYPE_ID = {$typeID}";
		}

		if(isset($filter['ASSOCIATED_ENTITY_TYPE_ID']) && isset($filter['ASSOCIATED_ENTITY_ID']))
		{
			$entityTypeID = (int)$filter['ASSOCIATED_ENTITY_TYPE_ID'];
			$values[] = "ASSOCIATED_ENTITY_TYPE_ID = {$entityTypeID}";

			$entityID = (int)$filter['ASSOCIATED_ENTITY_ID'];
			$values[] = "ASSOCIATED_ENTITY_ID = {$entityID}";
		}

		Main\Application::getConnection()->queryExecute("DELETE from b_crm_timeline WHERE ".implode(' AND ', $values));
	}
}