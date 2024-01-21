<?php

namespace Bitrix\BizprocMobile\EntityEditor\Fields;

use Bitrix\Bizproc\FieldType;

abstract class BaseField
{
	protected array $property;
	protected mixed $value;
	protected ?FieldType $fieldTypeObject;
	protected array $documentType;
	protected ?array $documentId = null;

	public function __construct(array $property, mixed $value, array $documentType)
	{
		$this->property = $property;
		$this->value = $value;
		$this->documentType = $documentType;

		$documentService = \CBPRuntime::getRuntime()->getDocumentService();
		$this->fieldTypeObject = $documentService->getFieldTypeObject($documentType, $property);
	}

	public function setDocumentId(array $documentId): static
	{
		$this->documentId = $documentId;
		$this->fieldTypeObject?->setDocumentId($documentId);

		return $this;
	}

	abstract public function getType(): string;

	abstract public function getConfig(): array;

	public function getName(): string
	{
		return $this->property['Id'] ?? '';
	}

	public function getTitle(): string
	{
		return $this->property['Name'] ?? '';
	}

	public function isMultiple(): bool
	{
		if ($this->fieldTypeObject)
		{
			return $this->fieldTypeObject->isMultiple();
		}

		return ($this->property['Multiple'] ?? false) === true;
	}

	public function isRequired(): bool
	{
		if ($this->fieldTypeObject)
		{
			return $this->fieldTypeObject->isRequired();
		}

		return ($this->property['Required'] ?? false) === true;
	}

	public function isEditable(): bool
	{
		return true;
	}

	abstract protected function convertToMobileType($value): mixed;

	public function convertValueToMobile(): mixed
	{
		if (!$this->isMultiple())
		{
			return $this->convertToMobileType($this->value);
		}

		$multipleValue = [];
		if (is_array($this->value))
		{
			foreach ($this->value as $singleValue)
			{
				$multipleValue[] = $this->convertToMobileType($singleValue);
			}
		}

		return $multipleValue;
	}

	abstract protected function convertToWebType($value): mixed;

	public function convertValueToWeb(): mixed
	{
		if (!$this->isMultiple())
		{
			return $this->convertToWebType($this->value);
		}

		$multipleValue = [];
		if (is_array($this->value))
		{
			foreach ($this->value as $singleValue)
			{
				if (is_array($singleValue) && isset($singleValue['value']))
				{
					$singleValue = $singleValue['value'];
				}

				$multipleValue[] = $this->convertToWebType($singleValue);
			}
		}

		return $multipleValue;
	}

	public function getDocumentType(): array
	{
		return $this->documentType;
	}

	public function getDocumentId(): ?array
	{
		return $this->documentId;
	}
}
