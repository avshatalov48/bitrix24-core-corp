<?php

namespace Bitrix\Sign\Item\Field\HcmLink;

use Bitrix\HumanResources\Type\HcmLink\FieldEntityType;
use Bitrix\Sign\Item;

class HcmLinkFieldDelayedValueReferenceMap
{
	private array $map = [];

	private array $employeeFieldIds = [];
	private array $documentFieldIds = [];

	private array $signerIdByEmployeeIdMap = [];

	public function add(HcmLinkDelayedValue $value): static
	{
		if ($value->entityType === FieldEntityType::DOCUMENT->value)
		{
			$this->documentFieldIds[$value->fieldId] = $value->fieldId;
		}
		else
		{
			$this->employeeFieldIds[$value->fieldId] = $value->fieldId;
		}

		$this->map[$value->employeeId][$value->fieldId][] = $value;

		if ($value->signerMemberId)
		{
			$this->signerIdByEmployeeIdMap[$value->employeeId] = $value->signerMemberId;
		}

		return $this;
	}

	public function addByDocument(Item\Document $document): void
	{
		if (!$document->hcmLinkCompanyId || !$document->hcmLinkDocumentTypeSettingId)
		{
			return;
		}

		$this->documentFieldIds[$document->hcmLinkDocumentTypeSettingId] = $document->hcmLinkDocumentTypeSettingId;
	}

	public function getSignerIdByEmployeeIdMap(): array
	{
		return $this->signerIdByEmployeeIdMap;
	}

	public function getSignerIds(): array
	{
		return array_values($this->signerIdByEmployeeIdMap);
	}

	public function getEmployeeIds(): array
	{
		return array_keys($this->map);
	}

	public function getFieldIds(): array
	{
		return [
			...array_keys($this->employeeFieldIds),
			...array_keys($this->documentFieldIds),
		];
	}

	public function getDocumentFieldIds(): array
	{
		return array_keys($this->documentFieldIds);
	}

	public function getEmployeeFieldIds(): array
	{
		return array_keys($this->employeeFieldIds);
	}

	public function getEmployeeIdBySignerId(int $signerId): ?int
	{
		return array_flip($this->signerIdByEmployeeIdMap)[$signerId] ?? null;
	}

	/**
	 * @param int $employeeId
	 * @param int $fieldId
	 *
	 * @return array<HcmLinkDelayedValue>
	 */
	public function get(int $employeeId, int $fieldId): array
	{
		return $this->map[$employeeId][$fieldId] ?? [];
	}
}