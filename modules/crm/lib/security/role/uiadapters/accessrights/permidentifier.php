<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights;

class PermIdentifier
{
	public function __construct(
		public readonly string $entityCode,
		public readonly string $permCode,
		public readonly ?string $field = null,
		public readonly ?string $fieldValue = null,
	)
	{
	}

	public function isEqual(PermIdentifier $other): bool
	{
		$field1 = $this->field === '-' ? null : $this->field;
		$field2 = $other->field === '-' ? null : $other->field;

		return $this->entityCode === $other->entityCode &&
			$this->permCode === $other->permCode &&
			$field1 === $field2 &&
			$this->fieldValue === $other->fieldValue;
	}
}