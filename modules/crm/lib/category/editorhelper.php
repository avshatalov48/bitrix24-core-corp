<?php

namespace Bitrix\Crm\Category;

class EditorHelper
{
	protected $entityTypeId;

	public function __construct(int $entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;
	}

	public function getEditorConfigId(?int $categoryId, string $sourceFormId, $useUpperCase = true): string
	{
		if ($categoryId <= 0 || is_null($categoryId))
		{
			return $sourceFormId;
		}

		$key = $useUpperCase ? 'C' : 'c';
		return "{$sourceFormId}_{$key}_{$categoryId}";
	}
}
