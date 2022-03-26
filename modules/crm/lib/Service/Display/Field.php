<?php

namespace Bitrix\Crm\Service\Display;


use Bitrix\Crm\Kanban\Exception;
use Bitrix\Crm\Service\Display\Field\AddressField;
use Bitrix\Crm\Service\Display\Field\BooleanField;
use Bitrix\Crm\Service\Display\Field\CrmCurrencyField;
use Bitrix\Crm\Service\Display\Field\CrmField;
use Bitrix\Crm\Service\Display\Field\CrmStatusField;
use Bitrix\Crm\Service\Display\Field\EmployeeField;
use Bitrix\Crm\Service\Display\Field\FileField;
use Bitrix\Crm\Service\Display\Field\IblockElementField;
use Bitrix\Crm\Service\Display\Field\IblockSectionField;
use Bitrix\Crm\Service\Display\Field\MoneyField;
use Bitrix\Crm\Service\Display\Field\NumberField;
use Bitrix\Crm\Service\Display\Field\ResourceBookingField;
use Bitrix\Crm\Service\Display\Field\StringField;
use Bitrix\Crm\Service\Display\Field\TextField;
use Bitrix\Crm\Service\Display\Field\DateField;
use Bitrix\Crm\Service\Display\Field\DateTimeField;
use Bitrix\Crm\Service\Display\Field\EnumerationField;
use Bitrix\Crm\Service\Display\Field\UrlField;
use Bitrix\Crm\Service\Display\Field\UserField;

abstract class Field
{
	public const KANBAN_CONTEXT = 'kanban';
	public const GRID_CONTEXT = 'grid';
	public const EXPORT_CONTEXT = 'export';
	public const MOBILE_CONTEXT = 'mobile';

	protected $id = '';
	protected $entityTypeId;
	protected $title = '';
	protected $wasRenderedAsHtml = false;
	protected $isMultiple = false;
	protected $isUserField = false;
	protected $displayRawValue = false;
	protected $userFieldParams = [];
	protected $displayParams = [];
	protected $context;

	private function __construct(string $id)
	{
		$this->setId($id);
		$this->context = self::KANBAN_CONTEXT;
	}

	public static function createFromUserField(string $id, array $userFieldInfo): Field
	{
		$title = $userFieldInfo['LIST_COLUMN_LABEL']
			?? $userFieldInfo['EDIT_FORM_LABEL']
			?? $userFieldInfo['LIST_FILTER_LABEL']
			?? $id
		;

		return (self::getInstance($userFieldInfo['USER_TYPE_ID'], $id))
			->setTitle($title)
			->setIsMultiple($userFieldInfo['MULTIPLE'] === 'Y')
			->setDisplayParams((array)$userFieldInfo['SETTINGS'])
			->setIsUserField(true)
			->setUserFieldParams($userFieldInfo)
		;
	}

	public static function createFromBaseField(string $id, array $baseFieldInfo): Field
	{
		$displayParams = [];

		$type = $baseFieldInfo['TYPE'] ?? 'string';
		$settings = $baseFieldInfo['SETTINGS'] ?? [];

		// @todo maybe move it to a children classes?
		switch ($type)
		{
			case 'crm_entity':
				$parentEntityTypeId = (int)($settings['parentEntityTypeId'] ?? 0);
				if ($parentEntityTypeId > 0)
				{
					$type = 'crm';
					$displayParams = [
						\CCrmOwnerType::ResolveName($parentEntityTypeId) => 'Y',
					];
				}
				break;
			case 'crm':
				$crmFieldsMap = [
					'LEAD_ID' => [\CCrmOwnerType::LeadName => 'Y'],
					'DEAL_ID' => [\CCrmOwnerType::DealName => 'Y'],
					'CONTACT_ID' => [\CCrmOwnerType::ContactName => 'Y'],
					'COMPANY_ID' => [\CCrmOwnerType::CompanyName => 'Y'],
					'QUOTE_ID' => [\CCrmOwnerType::QuoteName => 'Y'],
					'INVOICE_ID' => [\CCrmOwnerType::InvoiceName => 'Y'],
				];
				$displayParams = $crmFieldsMap[$id] ?? [];
				break;
			case 'crm_lead':
				$displayParams = [\CCrmOwnerType::LeadName => 'Y'];
				$type = 'crm';
				break;
			case 'crm_deal':
				$displayParams = [\CCrmOwnerType::DealName => 'Y'];
				$type = 'crm';
				break;
			case 'crm_contact':
				$displayParams = [\CCrmOwnerType::ContactName => 'Y'];
				$type = 'crm';
				break;
			case 'crm_company':
				$displayParams = [\CCrmOwnerType::CompanyName => 'Y'];
				$type = 'crm';
				break;
			case 'crm_quote':
				$displayParams = [\CCrmOwnerType::QuoteName => 'Y'];
				$type = 'crm';
				break;
			case 'crm_invoice':
				$displayParams = [\CCrmOwnerType::InvoiceName => 'Y'];
				$type = 'crm';
				break;
			case 'crm_status':
				$displayParams = [
					'ENTITY_TYPE' => $baseFieldInfo['CRM_STATUS_TYPE'],
				];
				break;

			case 'char':
				$type = 'boolean';
				break;
		}
		if (isset($baseFieldInfo['VALUE_TYPE']))
		{
			$displayParams['VALUE_TYPE'] = $baseFieldInfo['VALUE_TYPE'];
		}
		$field =
			(self::getInstance($type, $id))
				->setDisplayParams($displayParams)
		;

		if (isset($baseFieldInfo['TITLE']))
		{
			$field->setTitle($baseFieldInfo['TITLE']);
		}

		return $field;
	}

	/**
	 * @param string $type
	 * @param string $id
	 * @return Field
	 */
	public static function createByType(string $type, string $id = ''): Field
	{
		return self::getInstance($type, $id);
	}

	protected static function getInstance(string $type, string $id): Field
	{
		if ($type === 'string')
		{
			return new StringField($id);
		}

		if ($type === 'text')
		{
			return new TextField($id);
		}

		if ($type === 'date')
		{
			return new DateField($id);
		}

		if ($type === 'datetime')
		{
			return new DateTimeField($id);
		}

		if ($type === 'enumeration')
		{
			return new EnumerationField($id);
		}

		if ($type === 'employee')
		{
			return new EmployeeField($id);
		}

		if ($type === 'file')
		{
			return new FileField($id);
		}

		if ($type === 'iblock_element')
		{
			return new IblockElementField($id);
		}

		if ($type === 'iblock_section')
		{
			return new IblockSectionField($id);
		}

		if ($type === 'user')
		{
			return new UserField($id);
		}

		if ($type === 'crm_status')
		{
			return new CrmStatusField($id);
		}

		if ($type === 'resourcebooking')
		{
			return new ResourceBookingField($id);
		}

		if ($type === 'money')
		{
			return new MoneyField($id);
		}

		if ($type === 'address')
		{
			return new AddressField($id);
		}

		if ($type === 'url')
		{
			return new UrlField($id);
		}

		if ($type === 'crm_currency')
		{
			return new CrmCurrencyField($id);
		}

		if ($type === 'crm')
		{
			return new CrmField($id);
		}

		if ($type === 'number')
		{
			return new NumberField($id);
		}

		if ($type === 'boolean')
		{
			return new BooleanField($id);
		}

		return new StringField($id);
	}

	public function getType(): string
	{
		return static::TYPE;
	}

	public function setEntityTypeId(int $entityTypeId): Field
	{
		$this->entityTypeId = $entityTypeId;
		return $this;
	}

	protected function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	/**
	 * @return string
	 */
	public function getContext(): string
	{
		return $this->context;
	}

	/**
	 * @param string $context
	 * @return $this
	 */
	public function setContext(string $context): Field
	{
		$this->context = $context;
		return $this;
	}

	public function getDisplayParam(string $paramId, $defaultValue = null)
	{
		return $this->displayParams[$paramId] ?? $defaultValue;
	}

	public function getDisplayParams(): array
	{
		return $this->displayParams;
	}

	public function setDisplayParams(array $displayParams): Field
	{
		$this->displayParams = $displayParams;

		return $this;
	}

	public function addDisplayParam(string $paramId, $paramValue): Field
	{
		$this->displayParams[$paramId] = $paramValue;

		return $this;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function setTitle(string $title): Field
	{
		$this->title = $title;

		return $this;
	}

	public function isMultiple(): bool
	{
		return $this->isMultiple;
	}

	public function setIsMultiple(bool $isMultiple): Field
	{
		$this->isMultiple = $isMultiple;

		return $this;
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function setId(string $id): Field
	{
		$this->id = $id;

		return $this;
	}

	public function isUserField(): bool
	{
		return $this->isUserField;
	}

	public function setIsUserField(bool $isUserField): Field
	{
		$this->isUserField = $isUserField;

		return $this;
	}

	public function getUserFieldParams(): array
	{
		return $this->userFieldParams;
	}

	public function setUserFieldParams(array $userFieldParams): Field
	{
		$this->userFieldParams = $userFieldParams;

		return $this;
	}

	public function wasRenderedAsHtml(): bool
	{
		return $this->wasRenderedAsHtml;
	}

	public function setWasRenderedAsHtml(bool $wasRenderedAsHtml): Field
	{
		$this->wasRenderedAsHtml = $wasRenderedAsHtml;

		return $this;
	}

	public function isValueEmpty($value): bool
	{
		if ($this->getType() === 'boolean')
		{
			return false;
		}

		if ($value === null)
		{
			return true;
		}

		if ($this->isMultiple() && $value === false)
		{
			return true;
		}

		$isBaseTypeString = $this->getType() === 'string';
		if (
			!$isBaseTypeString
			&& $this->isUserField()
			&& isset($this->userFieldParams['USER_TYPE']['BASE_TYPE'])
			&& $this->userFieldParams['USER_TYPE']['BASE_TYPE'] === 'string'
		)
		{
			$isBaseTypeString = true;
		}

		if (
			$isBaseTypeString
			&& (empty($value) || (is_string($value) && empty(trim($value))))
		)
		{
			return true;
		}

		return false;
	}

	public function needDisplayRawValue(): bool
	{
		return $this->displayRawValue;
	}

	public function setDisplayRawValue(bool $displayRawValue): Field
	{
		$this->displayRawValue = $displayRawValue;

		return $this;
	}

	public function prepareField(): void
	{
		// may be implement in children classes
	}

	/**
	 * @param $fieldValue
	 * @param int|null $itemId
	 * @param Options|null $displayOptions
	 * @return array|string
	 * @throws Exception
	 */
	public function getFormattedValue(
		$fieldValue,
		?int $itemId = null,
		?Options $displayOptions = null
	)
	{
		if ($displayOptions === null || $itemId === null)
		{
			return '';
		}

		$context = $this->getContext();

		if ($this->isKanbanContext())
		{
			return $this->getFormattedValueForKanban($fieldValue, $itemId, $displayOptions);
		}

		if ($this->isGridContext())
		{
			$value = $this->getFormattedValueForGrid($fieldValue, $itemId, $displayOptions);
			return $this->convertToString($value, $displayOptions);
		}

		if ($this->isExportContext())
		{
			$value = $this->getFormattedValueForExport($fieldValue, $itemId, $displayOptions);
			return $this->convertToString($value, $displayOptions);
		}

		if ($this->isMobileContext())
		{
			return $this->getFormattedValueForMobile($fieldValue, $itemId, $displayOptions);
		}

		throw new Exception('Unknown context: ' . $context);
	}

	protected function getFormattedValueForKanban($fieldValue, int $itemId, Options $displayOptions)
	{
		$isMultiple = $this->isMultiple();

		if ($isMultiple && is_array($fieldValue))
		{
			$value = $this->getPreparedArrayValues($fieldValue);
			if (!empty($value))
			{
				$result = [];
				foreach ($value as $valueArrayItem)
				{
					$result[] = $this->render($displayOptions, $itemId, [$valueArrayItem]);
				}
				return $result;
			}
		}

		return $this->renderSingleValue($fieldValue, $itemId, $displayOptions);
	}

	protected function convertToString($value, Options $displayOptions): string
	{
		return (is_array($value)
			? implode($displayOptions->getMultipleFieldsDelimiter(), $value)
			: (string)$value
		);
	}

	protected function getFormattedValueForGrid($fieldValue, int $itemId, Options $displayOptions)
	{
		$isMultiple = $this->isMultiple();

		if ($isMultiple && is_array($fieldValue))
		{
			$values = $this->getPreparedArrayValues($fieldValue);
			return $this->render($displayOptions, $itemId, $values);
		}

		return $this->renderSingleValue($fieldValue, $itemId, $displayOptions);
	}

	protected function getFormattedValueForExport($fieldValue, int $itemId, Options $displayOptions)
	{
		return $this->getFormattedValueForGrid($fieldValue, $itemId, $displayOptions);
	}

	protected function getFormattedValueForMobile($fieldValue, int $itemId, Options $displayOptions)
	{
		return '';
		// @todo return formatted value for mobile
	}

	/**
	 * @param array $values
	 * @return array
	 */
	protected function getPreparedArrayValues(array $values): array
	{
		return array_values(
			array_map('htmlspecialcharsback', $values)
		);
	}

	protected function renderSingleValue($fieldValue, int $itemId, Options $displayOptions): string
	{
		if (!$this->isMultiple() && $fieldValue !== '')
		{
			return $this->render(
				$displayOptions,
				$itemId,
				htmlspecialcharsback($fieldValue)
			);
		}

		return '';
	}

	protected function render(Options $displayOptions, $entityId, $value): string
	{
		if (!$this->isUserField() || empty($this->getUserFieldParams()))
		{
			return $this->convertToString($value, $displayOptions);
		}

		$userFieldParams = array_merge(
			$this->getUserFieldParams(),
			[
				'ENTITY_VALUE_ID' => $entityId,
				'VALUE' => $value,
			]
		);

		if ($this->isExportContext())
		{
			return (string)((new \Bitrix\Main\UserField\Renderer(
				$userFieldParams,
				[
					'CONTEXT' => 'CRM_GRID',
					'mode' => 'main.public_text',
				]
			)
			)->render());
		}

		$this->setWasRenderedAsHtml(true);

		return (string)($GLOBALS['USER_FIELD_MANAGER']->getPublicView(
			$userFieldParams,
			[
				'CONTEXT' => 'CRM_GRID',
			]
		));
	}

	public function useLinkedEntities(): bool
	{
		return false;
	}

	public function prepareLinkedEntities(
		array &$linkedEntities,
		$fieldValue,
		int $itemId,
		string $fieldId
	): void
	{
		// must be implement in children classes which use linked entities
	}

	/**
	 * @param array $linkedEntitiesValues
	 * @param array $linkedEntity
	 */
	public function loadLinkedEntities(array &$linkedEntitiesValues, array $linkedEntity): void
	{
		// must be implement in children classes which use linked entities
	}

	/**
	 * @param array $linkedEntitiesValues
	 * @param string $fieldValueType
	 * @param mixed $fieldValueId
	 * @return mixed
	 */
	public function getPreparedEntityValue(array $linkedEntitiesValues, string $fieldValueType, $fieldValueId)
	{
		$fieldType = $this->getType();
		return $linkedEntitiesValues[$fieldType][$fieldValueId];
	}

	public function isKanbanContext(): bool
	{
		return ($this->getContext() === self::KANBAN_CONTEXT);
	}

	public function isGridContext(): bool
	{
		return ($this->getContext() === self::GRID_CONTEXT);
	}

	public function isExportContext(): bool
	{
		return ($this->getContext() === self::EXPORT_CONTEXT);
	}

	public function isMobileContext(): bool
	{
		return ($this->getContext() === self::MOBILE_CONTEXT);
	}
}
