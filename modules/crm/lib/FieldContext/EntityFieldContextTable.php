<?php

namespace Bitrix\Crm\FieldContext;

interface EntityFieldContextTable
{
	public function getIdColumnName(): string;
	public static function deleteByFieldName(string $fieldName): void;
	public static function deleteByItemId(int $id): void;
}
