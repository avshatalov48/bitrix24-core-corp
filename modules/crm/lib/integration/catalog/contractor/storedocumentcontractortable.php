<?php

namespace Bitrix\Crm\Integration\Catalog\Contractor;

use Bitrix\Crm\Relation\RelationType;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\DB\SqlQueryException;

/**
 * Class StoreDocumentContractorTable
 *
 * @package Bitrix\Crm\Integration\Catalog\Contractor
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_StoreDocumentContractor_Query query()
 * @method static EO_StoreDocumentContractor_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_StoreDocumentContractor_Result getById($id)
 * @method static EO_StoreDocumentContractor_Result getList(array $parameters = [])
 * @method static EO_StoreDocumentContractor_Entity getEntity()
 * @method static \Bitrix\Crm\Integration\Catalog\Contractor\EO_StoreDocumentContractor createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Integration\Catalog\Contractor\EO_StoreDocumentContractor_Collection createCollection()
 * @method static \Bitrix\Crm\Integration\Catalog\Contractor\EO_StoreDocumentContractor wakeUpObject($row)
 * @method static \Bitrix\Crm\Integration\Catalog\Contractor\EO_StoreDocumentContractor_Collection wakeUpCollection($rows)
 */
class StoreDocumentContractorTable extends DataManager
{
	/**
	 * @inheritDoc
	 */
	public static function getTableName()
	{
		return 'b_crm_store_document_contractor';
	}

	/**
	 * @inheritDoc
	 */
	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new IntegerField('DOCUMENT_ID'))
				->configureRequired(),
			(new IntegerField('ENTITY_ID'))
				->configureRequired(),
			(new IntegerField('ENTITY_TYPE_ID'))
				->configureRequired(),
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
			DELETE FROM b_crm_store_document_contractor
			WHERE
				DOCUMENT_ID = %d
				AND ENTITY_TYPE_ID = %d
				%s
			',
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
			DELETE FROM b_crm_store_document_contractor
			WHERE
			    DOCUMENT_ID = %d
			',
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

		try
		{
			Application::getConnection()->query(sprintf(
				'
			UPDATE b_crm_store_document_contractor
			SET ENTITY_ID = %d
			WHERE
				ENTITY_TYPE_ID = %d
			  	AND ENTITY_ID = %d
			',
				$sqlHelper->convertToDbInteger($newEntityId),
				$sqlHelper->convertToDbInteger($entityTypeId),
				$sqlHelper->convertToDbInteger($oldEntityId)
			));
		}
		catch (SqlQueryException $e) // most likely there is a duplication of unique keys, so try to update every item separately
		{
			$items = self::query()
				->setSelect(['ID'])
				->where('ENTITY_TYPE_ID', $entityTypeId)
				->where('ENTITY_ID', $oldEntityId)
				->fetchAll()
			;

			foreach ($items as $item)
			{
				try
				{
					self::update($item['ID'], ['ENTITY_ID' => $newEntityId]);
				}
				catch (SqlQueryException $e)
				{
					// unique keys have been duplicated, so delete this duplicate:
					self::delete($item['ID']);
				}
			}
		}
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
			DELETE FROM b_crm_store_document_contractor
			WHERE
				ENTITY_TYPE_ID = %d
				AND ENTITY_ID = %d
			',
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
		$sql = $sqlHelper->getInsertIgnore(
			'b_crm_store_document_contractor',
			'(
				ENTITY_TYPE_ID,
				ENTITY_ID,
				DOCUMENT_ID
			)',
			'VALUES ' . implode(', ', $sqlValues)
		);

		Application::getConnection()->query($sql);
	}
}
