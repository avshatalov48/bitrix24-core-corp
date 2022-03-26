<?php

namespace Bitrix\Crm\Timeline\HistoryDataModel;

/**
 * Class EntityImplementation
 *
 * This class is used in history data model generation.
 * An object of this class modifies and customizes data model for specific entity type (e.g., Deal or Lead)
 *
 * Implements the pattern 'Bridge'. This class is an 'implementation'
 *
 * This base class is used as 'null-object', therefore it is not abstract
 */
class EntityImplementation
{
	/** @var \CCrmOwnerType */
	protected $crmOwnerType = \CCrmOwnerType::class;

	/** @var int */
	protected $entityTypeId;

	public function __construct(int $entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;
	}

	/**
	 * Get title (caption) for a specified field
	 *
	 * @param string $fieldName
	 *
	 * @return string|null
	 */
	public function getFieldTitle(string $fieldName): ?string
	{
		return null;
	}

	/**
	 * Return display info about the specified entity
	 *
	 * @param int $entityId
	 *
	 * @return array
	 */
	public function getEntityInfo(int $entityId): array
	{
		$entityInfo = [];

		$entityInfoBatch = [$entityId => &$entityInfo];
		$this->crmOwnerType::PrepareEntityInfoBatch(
			$this->entityTypeId,
			$entityInfoBatch,
			false,
		);

		$entityInfo['ENTITY_TYPE_ID'] = $this->entityTypeId;
		$entityInfo['ENTITY_TYPE_CAPTION'] = $this->getEntityTypeCaption();
		$entityInfo['ENTITY_ID'] = $entityId;

		return $entityInfo;
	}

	protected function getEntityTypeCaption(): string
	{
		return (string)$this->crmOwnerType::GetDescription($this->entityTypeId);
	}
}
