<?php

namespace Bitrix\BizprocMobile\EntityEditor;

use Bitrix\Bizproc\FileUploader\ParametersUploaderController;
use Bitrix\Bizproc\FileUploader\TaskUploaderController;
use Bitrix\BizprocMobile\EntityEditor\Fields\AddressField;
use Bitrix\BizprocMobile\EntityEditor\Fields\BaseField;
use Bitrix\BizprocMobile\EntityEditor\Fields\BoolField;
use Bitrix\BizprocMobile\EntityEditor\Fields\CrmField;
use Bitrix\BizprocMobile\EntityEditor\Fields\CrmMultiField;
use Bitrix\BizprocMobile\EntityEditor\Fields\CrmStatusField;
use Bitrix\BizprocMobile\EntityEditor\Fields\DatetimeField;
use Bitrix\BizprocMobile\EntityEditor\Fields\FileField;
use Bitrix\BizprocMobile\EntityEditor\Fields\IBlockElementField;
use Bitrix\BizprocMobile\EntityEditor\Fields\IBlockSectionField;
use Bitrix\BizprocMobile\EntityEditor\Fields\MoneyField;
use Bitrix\BizprocMobile\EntityEditor\Fields\NumberField;
use Bitrix\BizprocMobile\EntityEditor\Fields\SelectField;
use Bitrix\BizprocMobile\EntityEditor\Fields\StringField;
use Bitrix\BizprocMobile\EntityEditor\Fields\TextField;
use Bitrix\BizprocMobile\EntityEditor\Fields\UrlField;
use Bitrix\BizprocMobile\EntityEditor\Fields\UserField;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;

Loader::requireModule('ui');

final class Converter
{
	public const CONTEXT_TASK = 'task';
	public const CONTEXT_PARAMETERS = 'parameters';

	private string $context = '';
	private array $contextParameters = [];
	private ?array $documentId;
	private ?array $documentType = null;
	private array $originalProperties;
	private array $originalValues;
	private array $convertedProperties = [];
	private array $convertedValues = [];
	private bool $isConvertedToMobile = false;
	private bool $isConvertedToWeb = false;
	private array $pendingFiles = [];

	public function __construct(array $properties, ?array $documentId, array $values = [])
	{
		$this->documentId = $documentId;
		$this->originalProperties = $properties;
		$this->originalValues = $values;
	}

	public function setDocumentType(array $documentType): self
	{
		try
		{
			$parsedDocumentType = \CBPHelper::parseDocumentId($documentType);
			$this->documentType = $parsedDocumentType;
		}
		catch (\CBPArgumentNullException $exception)
		{}

		return $this;
	}

	public function setContext(string $context, array $parameters = []): self
	{
		if ($context === self::CONTEXT_TASK || $context === self::CONTEXT_PARAMETERS)
		{
			$this->context = $context;
			$this->contextParameters = $parameters;
		}

		return $this;
	}

	public function toMobile(): self
	{
		if ($this->isConvertedToMobile)
		{
			return $this;
		}

		$this->pendingFiles = [];

		foreach ($this->originalProperties as $name => $property)
		{
			$id = $property['Id'] ?? $name;
			$property['Id'] = $id;
			$field = $this->getField($property, $id);
			if ($field)
			{
				$value = $field->convertValueToMobile();

				$this->convertedProperties[$id] = [
					'name' => $field->getName(),
					'type' => $field->getType(),
					'title' => $field->getTitle(),
					'editable' => $field->isEditable(),
					'required' => $field->isRequired(),
					'multiple' => $field->isMultiple(),
					'data' => $field->getConfig(),
					'custom' => [
						'default' => $value,
					],
				];
				$this->convertedValues[$id] = $value;
			}
		}

		$this->isConvertedToMobile = true;
		$this->isConvertedToWeb = false;

		return $this;
	}

	private function getField(array $property, $id): ?BaseField
	{
		$documentType = $this->getDocumentType();
		if (!$documentType)
		{
			return null;
		}

		$value = $this->getValue($property, $id);

		if (in_array($property['Type'], ['file', 'S:DiskFile'], true))
		{
			$field = new FileField($property, $value, $documentType);
			if ($this->documentId !== null)
			{
				$field->setDocumentId($this->documentId);
			}
			$field->setEndpoint(
				$this->context === self::CONTEXT_TASK
					? 'bizproc.FileUploader.TaskUploaderController'
					: 'bizproc.FileUploader.ParametersUploaderController'
			);
			$field->setControllerOptionNames(
				$this->context === self::CONTEXT_TASK
					? ['taskId' => 'TASK_ID']
					: [
						'templateId' => 'TEMPLATE_ID_' . $this->contextParameters['templateId'],
						'signedDocument' => 'SIGNED_DOCUMENT',
					]
			);
			$field->setController(
				$this->context === self::CONTEXT_TASK
					? new TaskUploaderController([
						'taskId' => $this->contextParameters['taskId'] ?? 0,
						'fieldName' => $field->getName(),
					])
					: new ParametersUploaderController([
						'templateId' => $this->contextParameters['templateId'] ?? 0,
						'signedDocument' => $this->contextParameters['signedDocument'] ?? null,
						'fieldName' => $field->getName(),
					])
			);

			return $field;
		}

		$fieldClass = match ($property['Type'])
		{
			'string' => StringField::class,
			'int', 'double' => NumberField::class,
			'text', 'S:HTML' => TextField::class,
			'user', 'S:employee' => UserField::class,
			'UF:date', 'date', 'datetime' => DatetimeField::class,
			'bool' => BoolField::class,
			'UF:money', 'S:Money' => MoneyField::class,
			'select', 'internalselect', 'deal_category', 'deal_stage', 'lead_status', 'sms_sender', 'mail_sender' => SelectField::class,
			'UF:url' => UrlField::class,
			'UF:address' => AddressField::class,
			'UF:crm_status' => CrmStatusField::class,
			'phone', 'email', 'web', 'im' => CrmMultiField::class,
			'UF:crm', 'E:ECrm' => CrmField::class,
			'UF:iblock_section' => IBlockSectionField::class,
			'UF:iblock_element', 'E:EList' => IBlockElementField::class,
			default => null,
		};

		if ($fieldClass)
		{
			/** @var $field BaseField*/
			$field = new $fieldClass($property, $value, $documentType);

			if ($this->documentId !== null)
			{
				$field->setDocumentId($this->documentId);
			}

			return $field;
		}

		return null;
	}

	private function getDocumentType(): ?array
	{
		if ($this->documentType === null && $this->documentId !== null)
		{
			$documentService = \CBPRuntime::getRuntime()->getDocumentService();
			try
			{
				$documentType = $documentService->getDocumentType($this->documentId);
				if (is_array($documentType))
				{
					$this->setDocumentType($documentType);
				}
			}
			catch (\Exception $exception)
			{}
		}

		return $this->documentType;
	}

	private function getValue(array $property, string $id)
	{
		$value = $property['Default'] ?? null;
		if (array_key_exists($id, $this->originalValues))
		{
			$value = $this->originalValues[$id];
		}

		return $value;
	}

	public function toWeb(): self
	{
		if ($this->isConvertedToWeb)
		{
			return $this;
		}

		$this->pendingFiles = [];

		foreach ($this->originalProperties as $name => $property)
		{
			$id = $property['Id'] ?? $name;
			$property['Id'] = $id;
			$property['Default'] = null;
			$field = $this->getField($property, $id);
			if ($field)
			{
				$value = $field->convertValueToWeb();

				if ($field->getType() === 'user')
				{
					/** @var UserField $field*/
					if (
						$this->context === self::CONTEXT_TASK
						&& ($property['Type'] === 'user' || !$field->isEmployeeCompatibleMode())
					)
					{
						$errors = [];
						$value = \CBPHelper::usersStringToArray($value, $field->getDocumentType(), $errors);
					}
				}

				/** @var BaseField $field */

				if (in_array($property['Type'], ['file', 'S:DiskFile'], true))
				{
					[$value, $pendingFiles] = $value;
					$this->pendingFiles[$field->getName()] = $pendingFiles;

					if ($this->context === self::CONTEXT_PARAMETERS)
					{
						$multipleValue = [];
						foreach ($value as $singleValue)
						{
							if (is_numeric($singleValue))
							{
								$multipleValue[] =
									$property['Type'] === 'file'
										? \CBPDocument::signParameters([$singleValue])
										: 'n' . $singleValue
								;
							}
						}
						$value = $multipleValue;
					}
				}

				$property['Default'] = $value;
				$this->convertedProperties = $property;
				$this->convertedValues[$id] = $property['Default'];
			}
		}

		$this->isConvertedToWeb = true;
		$this->isConvertedToMobile = false;

		return $this;
	}

	public function getConvertedProperties(): array
	{
		if ($this->isConvertedToMobile || $this->isConvertedToWeb)
		{
			return $this->convertedProperties;
		}

		return $this->originalProperties;
	}

	public function getConvertedValues(): array
	{
		if ($this->isConvertedToMobile || $this->isConvertedToWeb)
		{
			return $this->convertedValues;
		}

		return $this->originalValues;
	}

	public function getPendingFiles(): array
	{
		return $this->pendingFiles;
	}
}
