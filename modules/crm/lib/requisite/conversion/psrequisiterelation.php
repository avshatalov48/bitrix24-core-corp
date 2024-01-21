<?php

namespace Bitrix\Crm\Requisite\Conversion;

use Bitrix\Main;
use Bitrix\Main\Entity;

/**
 * Class PSRequisiteRelationTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_PSRequisiteRelation_Query query()
 * @method static EO_PSRequisiteRelation_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_PSRequisiteRelation_Result getById($id)
 * @method static EO_PSRequisiteRelation_Result getList(array $parameters = [])
 * @method static EO_PSRequisiteRelation_Entity getEntity()
 * @method static \Bitrix\Crm\Requisite\Conversion\EO_PSRequisiteRelation createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Requisite\Conversion\EO_PSRequisiteRelation_Collection createCollection()
 * @method static \Bitrix\Crm\Requisite\Conversion\EO_PSRequisiteRelation wakeUpObject($row)
 * @method static \Bitrix\Crm\Requisite\Conversion\EO_PSRequisiteRelation_Collection wakeUpCollection($rows)
 */
class PSRequisiteRelationTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_ps_rq_conv_relation';
	}

	public static function getMap()
	{
		return array(
			'ENTITY_ID' => array('data_type' => 'integer', 'primary' => true, 'required' => true),
			'COMPANY_ID' => array('data_type' => 'integer'),
			'REQUISITE_ID' => array('data_type' => 'integer'),
			'BANK_DETAIL_ID' => array('data_type' => 'integer')
		);
	}

	public static function upsert(array $data)
	{
		$connection = Main\Application::getConnection();

		$entityId = (int)($data['ENTITY_ID'] ?? 0);
		$companyId = (int)($data['COMPANY_ID'] ?? 0);
		$requisiteId = (int)($data['REQUISITE_ID'] ?? 0);
		$bankDetailId = (int)($data['BANK_DETAIL_ID'] ?? 0);

		$sql = $connection->getSqlHelper()->prepareMerge(
			'b_crm_ps_rq_conv_relation',
			[
				'ENTITY_ID'
			],
			[
				'ENTITY_ID' => $entityId,
				'COMPANY_ID' => $companyId,
				'REQUISITE_ID' => $requisiteId,
				'BANK_DETAIL_ID' => $bankDetailId,
			],
			[
				'COMPANY_ID' => $companyId,
				'REQUISITE_ID' => $requisiteId,
				'BANK_DETAIL_ID' => $bankDetailId,
			],
		);

		$connection->queryExecute($sql[0]);
	}
}
