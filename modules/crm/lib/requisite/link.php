<?php
namespace Bitrix\Crm\Requisite;
use Bitrix\Main;
use Bitrix\Main\Entity;

/**
 * Class LinkTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Link_Query query()
 * @method static EO_Link_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Link_Result getById($id)
 * @method static EO_Link_Result getList(array $parameters = [])
 * @method static EO_Link_Entity getEntity()
 * @method static \Bitrix\Crm\Requisite\EO_Link createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Requisite\EO_Link_Collection createCollection()
 * @method static \Bitrix\Crm\Requisite\EO_Link wakeUpObject($row)
 * @method static \Bitrix\Crm\Requisite\EO_Link_Collection wakeUpCollection($rows)
 */
class LinkTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_requisite_link';
	}
	public static function getMap()
	{
		return array(
			'ENTITY_ID' => array('data_type' => 'integer', 'primary' => true, 'required' => true),
			'ENTITY_TYPE_ID' => array('data_type' => 'integer', 'primary' => true, 'required' => true),
			'REQUISITE_ID' => array('data_type' => 'integer'),
			'BANK_DETAIL_ID' => array('data_type' => 'integer'),
			'MC_REQUISITE_ID' => array('data_type' => 'integer'),
			'MC_BANK_DETAIL_ID' => array('data_type' => 'integer')
		);
	}

	public static function upsert(array $data)
	{
		$connection = Main\Application::getConnection();

		$entityTypeId = (int)($data['ENTITY_TYPE_ID'] ?? 0);
		$entityId = (int)($data['ENTITY_ID'] ?? 0);
		$requisiteId = (int)($data['REQUISITE_ID'] ?? 0);
		$bankDetailId = (int)($data['BANK_DETAIL_ID'] ?? 0);
		$mcRequisiteId = (int)($data['MC_REQUISITE_ID'] ?? 0);
		$mcBankDetailId = (int)($data['MC_BANK_DETAIL_ID'] ?? 0);

		$sql = $connection->getSqlHelper()->prepareMerge(
			'b_crm_requisite_link',
			[
				'ENTITY_TYPE_ID',
				'ENTITY_ID',
			],
			[
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId,
				'REQUISITE_ID' => $requisiteId,
				'BANK_DETAIL_ID' => $bankDetailId,
				'MC_REQUISITE_ID' => $mcRequisiteId,
				'MC_BANK_DETAIL_ID' => $mcBankDetailId,
			],
			[
				'REQUISITE_ID' => $requisiteId,
				'BANK_DETAIL_ID' => $bankDetailId,
				'MC_REQUISITE_ID' => $mcRequisiteId,
				'MC_BANK_DETAIL_ID' => $mcBankDetailId,
			]
		);
		$connection->queryExecute($sql[0]);
	}

	public static function updateDependencies(array $newFields, array $oldFields)
	{
		$setFields = [];
		$filterFields = [];
		if (isset($newFields['REQUISITE_ID']) && isset($oldFields['REQUISITE_ID']))
		{
			$newRequisiteId = $newFields['REQUISITE_ID'];
			$oldRequisiteId = $oldFields['REQUISITE_ID'];
			if ($newRequisiteId > 0 && $oldRequisiteId > 0)
			{
				$setFields['REQUISITE_ID'] = $newRequisiteId;
				$filterFields['REQUISITE_ID'] = $oldRequisiteId;
				if (isset($newFields['BANK_DETAIL_ID']) && isset($oldFields['BANK_DETAIL_ID']))
				{
					$newBankDetailId = $newFields['BANK_DETAIL_ID'];
					$oldBankDetailId = $oldFields['BANK_DETAIL_ID'];
					if ($newBankDetailId > 0 && $oldBankDetailId > 0)
					{
						$setFields['BANK_DETAIL_ID'] = $newBankDetailId;
						$filterFields['BANK_DETAIL_ID'] = $oldBankDetailId;
					}
				}
			}
		}
		else if (isset($newFields['MC_REQUISITE_ID']) && isset($oldFields['MC_REQUISITE_ID']))
		{
			$newMcRequisiteId = $newFields['MC_REQUISITE_ID'];
			$oldMcRequisiteId = $oldFields['MC_REQUISITE_ID'];
			if ($newMcRequisiteId > 0 && $oldMcRequisiteId > 0)
			{
				$setFields['MC_REQUISITE_ID'] = $newMcRequisiteId;
				$filterFields['MC_REQUISITE_ID'] = $oldMcRequisiteId;
				if (isset($newFields['MC_BANK_DETAIL_ID']) && isset($oldFields['MC_BANK_DETAIL_ID']))
				{
					$newMcBankDetailId = $newFields['MC_BANK_DETAIL_ID'];
					$oldMcBankDetailId = $oldFields['MC_BANK_DETAIL_ID'];
					if ($newMcBankDetailId > 0 && $oldMcBankDetailId > 0)
					{
						$setFields['MC_BANK_DETAIL_ID'] = $newMcBankDetailId;
						$filterFields['MC_BANK_DETAIL_ID'] = $oldMcBankDetailId;
					}
				}
			}
		}

		if (!empty($setFields) && !empty($filterFields))
		{
			$setSql = '';
			foreach ($setFields as $fieldName => $value)
			{
				if (!empty($setSql))
					$setSql .= ', ';
				$setSql .= "{$fieldName} = {$value}";
			}
			$whereSql = '';
			foreach ($filterFields as $fieldName => $value)
			{
				if (!empty($whereSql))
					$whereSql .= ' AND ';
				$whereSql .= "{$fieldName} = {$value}";
			}
			$connection = Main\Application::getConnection();
			$connection->queryExecute(
				"UPDATE b_crm_requisite_link SET {$setSql} WHERE {$whereSql}"
			);
		}
	}
}