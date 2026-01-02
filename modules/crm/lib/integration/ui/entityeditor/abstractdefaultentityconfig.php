<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor;

abstract class AbstractDefaultEntityConfig
{
	protected const UTM_FIELD_CODE = 'UTM';

	abstract public function get(): array;

	protected function formatFieldNames(array $fieldNames): array
	{
		return array_map(static fn ($fieldName) => ['name' => $fieldName], $fieldNames);
	}
}
