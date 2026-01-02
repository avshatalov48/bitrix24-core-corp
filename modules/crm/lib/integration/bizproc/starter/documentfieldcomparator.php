<?php

namespace Bitrix\Crm\Integration\BizProc\Starter;

use Bitrix\Main\Loader;
use CBPHelper;

final class DocumentFieldComparator
{
	private array $actual;
	private array $previous;
	private int $entityTypeId;

	public function __construct(int $entityTypeId, array $actual, array $previous)
	{
		$this->actual = $actual;
		$this->previous = $previous;
		$this->entityTypeId = $entityTypeId;
	}

	public function compare(): array
	{
		$diff = [];
		foreach ($this->actual as $key => $field)
		{
			if (
				$key !== 'ID'
				&& (!array_key_exists($key, $this->previous) || $this->previous[$key] != $field)
			)
			{
				if (!$this->isDefaultValue($key, $field) || !$this->isEmptyValue($previous[$key] ?? null))
				{
					$diff[] = $key;
				}
			}
		}

		return $diff;
	}

	private function isDefaultValue(string $fieldName, $fieldValue): bool
	{
		static $documentFields = null;
		if (is_null($documentFields))
		{
			$documentFields = $this->getDocumentFields();
		}

		return (
			isset($documentFields[$fieldName]['Default']) && $documentFields[$fieldName]['Default'] === $fieldValue
		);
	}

	private function isEmptyValue(mixed $value): bool
	{
		return $this->loadBizproc() && CBPHelper::isEmptyValue($value);
	}

	private function getDocumentFields(): array
	{
		if (!$this->loadBizproc())
		{
			return [];
		}

		$documentFields = \CBPRuntime::getRuntime()->getDocumentService()->getDocumentFields($this->getDocumentType());

		return is_array($documentFields) ? $documentFields : [];
	}

	private function getDocumentType(): ?array
	{
		return \CCrmBizProcHelper::ResolveDocumentType($this->entityTypeId);
	}

	private function loadBizproc(): bool
	{
		return Loader::includeModule('bizproc');
	}
}
