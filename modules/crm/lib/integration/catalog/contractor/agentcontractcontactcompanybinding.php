<?php

namespace Bitrix\Crm\Integration\Catalog\Contractor;

/**
 * Class AgentContractContactCompanyBinding
 *
 * @package Bitrix\Crm\Integration\Catalog\Contractor
 */
class AgentContractContactCompanyBinding
{
	private int $entityTypeId;

	/**
	 * @param int $entityTypeId
	 */
	public function __construct(int $entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;
	}

	/**
	 * Replace all bindings of a contact/company to another ones
	 *
	 * @param int $oldEntityId
	 * @param int $newEntityId
	 * @return void
	 */
	public function rebind(int $oldEntityId, int $newEntityId)
	{
		AgentContractContractorTable::rebind($this->entityTypeId, $oldEntityId, $newEntityId);
	}

	/**
	 * Remove all bindings of a contact/company
	 *
	 * @param int $entityId
	 * @return void
	 */
	public function unbind(int $entityId)
	{
		AgentContractContractorTable::unbind($this->entityTypeId, $entityId);
	}

	/**
	 * @param int $documentId
	 * @param int $entityId
	 * @return bool
	 */
	public function isDocumentBoundToEntity(int $documentId, int $entityId): bool
	{
		$item = AgentContractContractorTable::query()
			->where('CONTRACT_ID', $documentId)
			->where('ENTITY_TYPE_ID', $this->entityTypeId)
			->where('ENTITY_ID', $this->$entityId)
			->exec()
			->fetch()
		;

		return (bool)$item;
	}

	/**
	 * Bulk bind a contact/company to documents
	 *
	 * @param int $entityId
	 * @param array $documentIds
	 * @return void
	 */
	public function bindToDocuments(int $entityId, array $documentIds)
	{
		AgentContractContractorTable::bindToDocuments(
			$this->entityTypeId,
			$entityId,
			$documentIds
		);
	}
}
