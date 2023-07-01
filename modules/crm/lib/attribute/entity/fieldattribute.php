<?php
namespace Bitrix\Crm\Attribute\Entity;

use Bitrix\Main;
use Bitrix\Crm;

/**
 * Class FieldAttributeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_FieldAttribute_Query query()
 * @method static EO_FieldAttribute_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_FieldAttribute_Result getById($id)
 * @method static EO_FieldAttribute_Result getList(array $parameters = [])
 * @method static EO_FieldAttribute_Entity getEntity()
 * @method static \Bitrix\Crm\Attribute\Entity\EO_FieldAttribute createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Attribute\Entity\EO_FieldAttribute_Collection createCollection()
 * @method static \Bitrix\Crm\Attribute\Entity\EO_FieldAttribute wakeUpObject($row)
 * @method static \Bitrix\Crm\Attribute\Entity\EO_FieldAttribute_Collection wakeUpCollection($rows)
 */
class FieldAttributeTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_field_attr';
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array('data_type' => 'integer', 'primary' => true, 'autocomplete' => true),
			'ENTITY_TYPE_ID' => array('data_type' => 'integer', 'required' => true),
			'ENTITY_SCOPE' => array('data_type' => 'string'/*, 'required' => true*/),
			'TYPE_ID' => array('data_type' => 'string', 'required' => true),
			'FIELD_NAME' => array('data_type' => 'string', 'required' => true),
			'CREATED_TIME' => array('data_type' => 'datetime', 'required' => true),
			'START_PHASE' => array('data_type' => 'string'/*, 'required' => true*/),
			'FINISH_PHASE' => array('data_type' => 'string'/*, 'required' => true*/),
			'PHASE_GROUP_TYPE_ID' => array('data_type' => 'integer'/*, 'required' => true*/),
			'IS_CUSTOM_FIELD' => array('data_type' => 'boolean', 'values' => array('N', 'Y'), 'required' => true)
		);
	}

	public static function deleteByPhase($phaseID, $entityTypeID, $entityScope)
	{
		if(!is_string($phaseID))
		{
			$phaseID = (string)$phaseID;
		}

		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!is_string($entityScope))
		{
			$entityScope = (string)$entityScope;
		}

		$res = self::getList([
			'filter' => [
				'LOGIC' => 'AND',
				[
					'=ENTITY_TYPE_ID' => $entityTypeID,
					'=ENTITY_SCOPE' => $entityScope,
				],
				[
					'LOGIC' => 'OR',
					'=START_PHASE' => $phaseID,
					'=FINISH_PHASE' => $phaseID
				],
			],
			'select' => [
				'ID',
			],
		]);

		while($item = $res->fetch())
		{
			self::delete($item['ID']);
		}
	}
}
