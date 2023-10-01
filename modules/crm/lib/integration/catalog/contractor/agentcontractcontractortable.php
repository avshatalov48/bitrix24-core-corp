<?php

namespace Bitrix\Crm\Integration\Catalog\Contractor;

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;

/**
 * Class AgentContractContractorTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CONTRACT_ID int mandatory
 * <li> ENTITY_ID int mandatory
 * <li> ENTITY_TYPE_ID int mandatory
 * </ul>
 *
 * @package Bitrix\Crm
 **/
class AgentContractContractorTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_agent_contract_contractor';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			'ID' => new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('AGENT_CONTRACT_CONTRACTOR_ENTITY_ID_FIELD'),
				]
			),
			'CONTRACT_ID' => new IntegerField(
				'CONTRACT_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('AGENT_CONTRACT_CONTRACTOR_ENTITY_CONTRACT_ID_FIELD'),
				]
			),
			'ENTITY_ID' => new IntegerField(
				'ENTITY_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('AGENT_CONTRACT_CONTRACTOR_ENTITY_ENTITY_ID_FIELD'),
				]
			),
			'ENTITY_TYPE_ID' => new IntegerField(
				'ENTITY_TYPE_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('AGENT_CONTRACT_CONTRACTOR_ENTITY_ENTITY_TYPE_ID_FIELD'),
				]
			),
		];
	}

	/**
	 * @param int $documentId
	 * @param int $entityTypeId
	 * @param array $entityIds
	 */
	public static function deleteBindings(int $documentId, int $entityTypeId, array $entityIds): void
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();

		$entityIdSqlFilter = empty($entityIds)
			? ''
			: ' AND ENTITY_ID NOT IN (' . implode(',', array_map('intval', $entityIds)) . ')'
		;

		Application::getConnection()->query(sprintf(
			'
			DELETE FROM %s
			WHERE
				CONTRACT_ID = %d
				AND ENTITY_TYPE_ID = %d
				%s
			',
			self::getTableName(),
			$sqlHelper->convertToDbInteger($documentId),
			$sqlHelper->convertToDbInteger($entityTypeId),
			$entityIdSqlFilter
		));
	}

	/**
	 * @param int $documentId
	 */
	public static function deleteByDocumentId(int $documentId): void
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();

		Application::getConnection()->query(sprintf(
			'
			DELETE FROM %s
			WHERE
				CONTRACT_ID = %d
			',
			self::getTableName(),
			$sqlHelper->convertToDbInteger($documentId)
		));
	}

	/**
	 * @param int $entityTypeId
	 * @param int $oldEntityId
	 * @param int $newEntityId
	 */
	public static function rebind(int $entityTypeId, int $oldEntityId, int $newEntityId): void
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();

		Application::getConnection()->query(sprintf(
			'
			UPDATE IGNORE %s
			SET ENTITY_ID = %d
			WHERE
				ENTITY_TYPE_ID = %d
				AND ENTITY_ID = %d
			',
			self::getTableName(),
			$sqlHelper->convertToDbInteger($newEntityId),
			$sqlHelper->convertToDbInteger($entityTypeId),
			$sqlHelper->convertToDbInteger($oldEntityId)
		));
	}

	/**
	 * @param int $entityTypeId
	 * @param int $entityId
	 */
	public static function unbind(int $entityTypeId, int $entityId): void
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();

		Application::getConnection()->query(sprintf(
			'
			DELETE FROM %s
			WHERE
				ENTITY_TYPE_ID = %d
				AND ENTITY_ID = %d
			',
			self::getTableName(),
			$sqlHelper->convertToDbInteger($entityTypeId),
			$sqlHelper->convertToDbInteger($entityId)
		));
	}

	/**
	 * @param int $entityTypeId
	 * @param int $entityId
	 * @param array $documentIds
	 */
	public static function bindToDocuments(int $entityTypeId, int $entityId, array $documentIds): void
	{
		if (empty($documentIds))
		{
			return;
		}

		$sqlHelper = Application::getConnection()->getSqlHelper();

		$sqlValues = [];
		foreach ($documentIds as $documentId)
		{
			$sqlValues[] = sprintf(
				'(
						%d,
						%d,
						%d
					)',
				$sqlHelper->convertToDbInteger($entityTypeId),
				$sqlHelper->convertToDbInteger($entityId),
				$sqlHelper->convertToDbInteger($documentId)
			);
		}

		Application::getConnection()->query(sprintf(
			'INSERT INTO %s (
					ENTITY_TYPE_ID,
					ENTITY_ID,
					CONTRACT_ID
				) VALUES %s
				ON DUPLICATE KEY UPDATE ID = ID',
			self::getTableName(),
			implode(', ', $sqlValues)
		));
	}
}
