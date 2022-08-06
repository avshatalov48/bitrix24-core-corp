<?php

namespace Bitrix\Crm\Requisite;

use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Error;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\Result;

class DefaultRequisite
{
	/**
	 * @var ItemIdentifier
	 */
	protected $itemIdentifier;

	/**
	 * @var EntityRequisite
	 */
	protected $entityRequisite;
	protected $checkPermissions = true;
	protected $defaultRequisiteData;

	public function __construct(ItemIdentifier $itemIdentifier)
	{
		if (!in_array($itemIdentifier->getEntityTypeId(), [\CCrmOwnerType::Contact, \CCrmOwnerType::Company]))
		{
			throw new NotSupportedException('Only contacts and companies supported');
		}
		$this->itemIdentifier = $itemIdentifier;
		$this->entityRequisite = EntityRequisite::getSingleInstance();
	}

	public function getId(): ?int
	{
		$defaultRequisiteData = $this->getDefaultRequisiteData();

		return $defaultRequisiteData['REQUISITE_ID'] > 0
			? $defaultRequisiteData['REQUISITE_ID']
			: null;
	}

	public function get(): ?array
	{
		if (
			$this->checkPermissions
			&& !$this->entityRequisite->validateEntityReadPermission(
				$this->itemIdentifier->getEntityTypeId(),
				$this->itemIdentifier->getEntityId()
			)
		)
		{
			return null;
		}

		$requisiteId = $this->getId();
		if (!$requisiteId)
		{
			return null;
		}

		return $this->entityRequisite->getById($requisiteId);
	}

	public function upsertField(string $fieldName, $fieldValue): Result
	{
		return $this->upsert([$fieldName => $fieldValue]);
	}

	public function upsert(array $requisiteFields): Result
	{
		if (
			$this->checkPermissions
			&& !$this->entityRequisite->validateEntityUpdatePermission(
				$this->itemIdentifier->getEntityTypeId(),
				$this->itemIdentifier->getEntityId()
			)
		)
		{
			$result = new Result();
			$result->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return $result;
		}

		$requisiteId = $this->getId();

		return
			$requisiteId
				? $this->update($requisiteId, $requisiteFields)
				: $this->add($requisiteFields);
	}

	public function setCheckPermissions(bool $checkPermissions): self
	{
		$this->checkPermissions = $checkPermissions;

		return $this;
	}

	protected function add(array $requisiteFields): Result
	{
		$result = new Result();
		$defaultPresetId = $this->entityRequisite->getDefaultPresetId($this->itemIdentifier->getEntityTypeId());
		if (!$defaultPresetId)
		{
			$result->addError(new Error('Default preset not found'));

			return $result;
		}
		$preset = EntityPreset::getSingleInstance()->getById($defaultPresetId);

		$requisiteFields['ENTITY_TYPE_ID'] = $this->itemIdentifier->getEntityTypeId();
		$requisiteFields['ENTITY_ID'] = $this->itemIdentifier->getEntityId();
		$requisiteFields['PRESET_ID'] = $defaultPresetId;
		$requisiteFields['ACTIVE'] = 'Y';
		$requisiteFields['ADDRESS_ONLY'] = 'N';
		if (!isset($requisiteFields['NAME']))
		{
			$requisiteFields['NAME'] = EntityPreset::formatName($defaultPresetId, $preset['NAME']);
		}
		if (!isset($requisiteFields['SORT']))
		{
			$requisiteFields['SORT'] = 500;
		}

		return $this->entityRequisite->add($requisiteFields);
	}

	protected function update(int $requisiteId, array $requisiteFields): Result
	{
		return $this->entityRequisite->update($requisiteId, $requisiteFields);
	}

	protected function getDefaultRequisiteData(): array
	{
		if (!$this->defaultRequisiteData)
		{
			$this->defaultRequisiteData = $this->entityRequisite->getDefaultRequisiteInfoLinked([
				[
					'ENTITY_TYPE_ID' => $this->itemIdentifier->getEntityTypeId(),
					'ENTITY_ID' => $this->itemIdentifier->getEntityId(),
				],
			]);
		}

		return $this->defaultRequisiteData;
	}
}
