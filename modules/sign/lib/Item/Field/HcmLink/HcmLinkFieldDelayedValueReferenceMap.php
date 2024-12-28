<?php

namespace Bitrix\Sign\Item\Field\HcmLink;

class HcmLinkFieldDelayedValueReferenceMap
{
	private array $map = [];
	private array $fieldIds = [];

	public function add(HcmLinkDelayedValue $value): static
	{
		$this->map[$value->employeeId][$value->fieldId][] = $value;
		$this->fieldIds[$value->fieldId] = $value->fieldId;

		return $this;
	}

	public function getEmployeeIds(): array
	{
		return array_keys($this->map);
	}

	public function getFieldIds(): array
	{
		return array_keys($this->fieldIds);
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