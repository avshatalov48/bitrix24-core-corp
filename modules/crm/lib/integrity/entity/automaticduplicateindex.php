<?php

namespace Bitrix\Crm\Integrity\Entity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

use Bitrix\Crm\Integrity\DuplicateIndexType;
use Bitrix\Crm\Integrity\DuplicateStatus;
use Bitrix\Main\ORM\Fields\Relations\Reference;

Loc::loadMessages(__FILE__);

/**
 * Class AutomaticDuplicateIndexTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_AutomaticDuplicateIndex_Query query()
 * @method static EO_AutomaticDuplicateIndex_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_AutomaticDuplicateIndex_Result getById($id)
 * @method static EO_AutomaticDuplicateIndex_Result getList(array $parameters = [])
 * @method static EO_AutomaticDuplicateIndex_Entity getEntity()
 * @method static \Bitrix\Crm\Integrity\Entity\EO_AutomaticDuplicateIndex createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Integrity\Entity\EO_AutomaticDuplicateIndex_Collection createCollection()
 * @method static \Bitrix\Crm\Integrity\Entity\EO_AutomaticDuplicateIndex wakeUpObject($row)
 * @method static \Bitrix\Crm\Integrity\Entity\EO_AutomaticDuplicateIndex_Collection wakeUpCollection($rows)
 */
class AutomaticDuplicateIndexTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_dp_automatic_index';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'ENTITY_TYPE_ID' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'TYPE_ID' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'MATCH_HASH' => array(
				'data_type' => 'string',
				'required' => true
			),
			'MATCHES' => array(
				'data_type' => 'string',
				'required' => false
			),
			'QUANTITY' => array(
				'data_type' => 'integer',
				'default_value' => 0
			),
			'SCOPE' => array(
				'data_type' => 'string',
				'default_value' => DuplicateIndexType::DEFAULT_SCOPE
			),
			'STATUS_ID' => array(
				'data_type' => 'integer',
				'default_value' => DuplicateStatus::UNDEFINED
			),
			'IS_DIRTY' => array(
				'data_type' => 'boolean',
				'default_value' => 'N',
				'values' => ['N', 'Y']
			),
			'ROOT_ENTITY_ID' => array(
				'data_type' => 'integer',
				'default_value' => 0
			),
			'ROOT_ENTITY_NAME' => array(
				'data_type' => 'string',
				'default_value' => ''
			),
			'ROOT_ENTITY_TITLE' => array(
				'data_type' => 'string',
				'default_value' => ''
			),
			'ROOT_ENTITY_PHONE' => array(
				'data_type' => 'string',
				'default_value' => ''
			),
			'ROOT_ENTITY_EMAIL' => array(
				'data_type' => 'string',
				'default_value' => ''
			),
			'ROOT_ENTITY_RQ_INN' => array(
				'data_type' => 'string',
				'default_value' => ''
			),
			'ROOT_ENTITY_RQ_OGRN' => array(
				'data_type' => 'string',
				'default_value' => ''
			),
			'ROOT_ENTITY_RQ_OGRNIP' => array(
				'data_type' => 'string',
				'default_value' => ''
			),
			'ROOT_ENTITY_RQ_BIN' => array(
				'data_type' => 'string',
				'default_value' => ''
			),
			'ROOT_ENTITY_RQ_EDRPOU' => array(
				'data_type' => 'string',
				'default_value' => ''
			),
			'ROOT_ENTITY_RQ_VAT_ID' => array(
				'data_type' => 'string',
				'default_value' => ''
			),
			'ROOT_ENTITY_RQ_ACC_NUM' => array(
				'data_type' => 'string',
				'default_value' => ''
			),
			'ROOT_ENTITY_RQ_IBAN' => array(
				'data_type' => 'string',
				'default_value' => ''
			),
			'ROOT_ENTITY_RQ_IIK' => array(
				'data_type' => 'string',
				'default_value' => ''
			)
		);
	}

	public static function markAsDirty($entityTypeID, $entityID)
	{
		$connection = Main\Application::getConnection();

		$query = static::query();
		$query->registerRuntimeField('',
			new Reference(
				'MATCH_HASH_ENTITY',
				DuplicateEntityMatchHashTable::class,
				[
					'=this.MATCH_HASH' => 'ref.MATCH_HASH',
					'ref.ENTITY_TYPE_ID' => new Main\DB\SqlExpression('?i', $entityTypeID),
					'ref.ENTITY_ID' => new Main\DB\SqlExpression('?i', $entityID),
				],
				['join_type' => \Bitrix\Main\ORM\Query\Join::TYPE_INNER]
			));
		$query->setSelect(['ID']);
		$indexIds = array_map(
			function ($item) {
				return (int)$item['ID'];
			}, $query->fetchAll()
		);

		if (empty($indexIds))
		{
			return;
		}
		$table = static::getTableName();
		$splittedIds = array_chunk($indexIds, 1000);
		foreach ($splittedIds as $ids)
		{
			$idsSql = implode(',', $ids);
			$connection->queryExecute("UPDATE {$table} SET IS_DIRTY='Y' WHERE ID IN ({$idsSql})");
		}
	}

	public static function setStatusByFilter(int $statusId, array $filter)
	{
		$connection = Main\Application::getConnection();
		$conditionSql = \Bitrix\Main\ORM\Query\Query::buildFilterSql(static::getEntity(), $filter);

		if (!empty($conditionSql))
		{
			$table = static::getTableName();
			$connection->queryExecute("UPDATE {$table} SET STATUS_ID=$statusId WHERE {$conditionSql}");
		}
	}
	
	public static function deleteByFilter(array $filter)
	{
		$connection = Main\Application::getConnection();
		$conditionSql = \Bitrix\Main\ORM\Query\Query::buildFilterSql(static::getEntity(), $filter);

		if (!empty($conditionSql))
		{
			$table = static::getTableName();
			$connection->queryExecute("DELETE FROM {$table} WHERE {$conditionSql}");
		}
	}
}