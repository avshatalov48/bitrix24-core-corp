<?php

namespace Bitrix\Crm\Service\Display;

use Bitrix\Crm\Kanban\Exception;
use Bitrix\Crm\Service\Display\Field\AddressField;
use Bitrix\Crm\Service\Display\Field\BooleanField;
use Bitrix\Crm\Service\Display\Field\CrmActivityProvider;
use Bitrix\Crm\Service\Display\Field\CrmActivityProviderType;
use Bitrix\Crm\Service\Display\Field\CrmCurrencyField;
use Bitrix\Crm\Service\Display\Field\CrmField;
use Bitrix\Crm\Service\Display\Field\CrmStatusField;
use Bitrix\Crm\Service\Display\Field\DateField;
use Bitrix\Crm\Service\Display\Field\DateTimeField;
use Bitrix\Crm\Service\Display\Field\DeliveryStatusField;
use Bitrix\Crm\Service\Display\Field\EmployeeField;
use Bitrix\Crm\Service\Display\Field\EnumerationField;
use Bitrix\Crm\Service\Display\Field\FileField;
use Bitrix\Crm\Service\Display\Field\HlBlockField;
use Bitrix\Crm\Service\Display\Field\IblockElementField;
use Bitrix\Crm\Service\Display\Field\IblockSectionField;
use Bitrix\Crm\Service\Display\Field\MoneyField;
use Bitrix\Crm\Service\Display\Field\NumberField;
use Bitrix\Crm\Service\Display\Field\OtherField;
use Bitrix\Crm\Service\Display\Field\PaymentStatusField;
use Bitrix\Crm\Service\Display\Field\ResourceBookingField;
use Bitrix\Crm\Service\Display\Field\Sign\B2e\ResultStatusField;
use Bitrix\Crm\Service\Display\Field\Sign\B2e\UserListField;
use Bitrix\Crm\Service\Display\Field\Sign\B2e\UserNameListField;
use Bitrix\Crm\Service\Display\Field\StringField;
use Bitrix\Crm\Service\Display\Field\TextField;
use Bitrix\Crm\Service\Display\Field\UrlField;
use Bitrix\Crm\Service\Display\Field\UserField;
use Bitrix\Main\ArgumentException;
use CCrmOwnerType;

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

	protected function __construct(string $id)
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
			->addDisplayParams((array)$userFieldInfo['SETTINGS'])
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
						CCrmOwnerType::ResolveName($parentEntityTypeId) => 'Y',
					];
				}
				break;
			case 'crm':
				$crmFieldsMap = [
					'LEAD_ID' => [CCrmOwnerType::LeadName => 'Y'],
					'DEAL_ID' => [CCrmOwnerType::DealName => 'Y'],
					'CONTACT_ID' => [CCrmOwnerType::ContactName => 'Y'],
					'COMPANY_ID' => [CCrmOwnerType::CompanyName => 'Y'],
					'QUOTE_ID' => [CCrmOwnerType::QuoteName => 'Y'],
					'INVOICE_ID' => [CCrmOwnerType::InvoiceName => 'Y'],
				];
				$displayParams = $crmFieldsMap[$id] ?? [];
				break;
			case 'crm_lead':
				$displayParams = [CCrmOwnerType::LeadName => 'Y'];
				$type = 'crm';
				break;
			case 'crm_deal':
				$displayParams = [CCrmOwnerType::DealName => 'Y'];
				$type = 'crm';
				break;
			case 'crm_contact':
				$displayParams = [CCrmOwnerType::ContactName => 'Y'];
				$type = 'crm';
				break;
			case 'crm_company':
				$displayParams = [CCrmOwnerType::CompanyName => 'Y'];
				$type = 'crm';
				break;
			case 'crm_quote':
				$displayParams = [CCrmOwnerType::QuoteName => 'Y'];
				$type = 'crm';
				break;
			case 'crm_invoice':
				$displayParams = [CCrmOwnerType::InvoiceName => 'Y'];
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
				->addDisplayParams($displayParams)
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
		if ($type === StringField::TYPE)
		{
			return new StringField($id);
		}

		// @todo support later if needed
		// if ($type === 'status')
		// {
		// 	return new StatusField($id);
		// }

		if ($type === ResultStatusField::TYPE)
		{
			return new ResultStatusField($id);
		}

		if ($type === UserListField::TYPE)
		{
			return new UserListField($id);
		}

		if ($type === UserNameListField::TYPE)
		{
			return new UserNameListField($id);
		}

		if ($type === PaymentStatusField::TYPE)
		{
			return new PaymentStatusField($id);
		}

		if ($type === DeliveryStatusField::TYPE)
		{
			return new DeliveryStatusField($id);
		}

		if ($type === HlBlockField::TYPE)
		{
			return new HlBlockField($id);
		}

		if ($type === TextField::TYPE)
		{
			return new TextField($id);
		}

		if ($type === DateField::TYPE)
		{
			return new DateField($id);
		}

		if ($type === DateTimeField::TYPE)
		{
			return new DateTimeField($id);
		}

		if ($type === EnumerationField::TYPE)
		{
			return new EnumerationField($id);
		}

		if ($type === EmployeeField::TYPE)
		{
			return new EmployeeField($id);
		}

		if ($type === FileField::TYPE)
		{
			return new FileField($id);
		}

		if ($type === IblockElementField::TYPE)
		{
			return new IblockElementField($id);
		}

		if ($type === IblockSectionField::TYPE)
		{
			return new IblockSectionField($id);
		}

		if ($type === UserField::TYPE)
		{
			return new UserField($id);
		}

		if ($type === ResourceBookingField::TYPE)
		{
			return new ResourceBookingField($id);
		}

		if ($type === MoneyField::TYPE)
		{
			return new MoneyField($id);
		}

		if ($type === AddressField::TYPE)
		{
			return new AddressField($id);
		}

		if ($type === UrlField::TYPE)
		{
			return new UrlField($id);
		}

		if ($type === BooleanField::TYPE)
		{
			return new BooleanField($id);
		}

		if (in_array($type, ['number', 'double', 'integer', 'float'], true))
		{
			return new NumberField($id);
		}

		if ($type === CrmStatusField::TYPE)
		{
			return new CrmStatusField($id);
		}

		if ($type === CrmCurrencyField::TYPE)
		{
			return new CrmCurrencyField($id);
		}

		//@todo one of these fields is redundant
		if ($type === CrmActivityProvider::TYPE)
		{
			return new CrmActivityProvider($id);
		}

		if ($type === CrmActivityProviderType::TYPE)
		{
			return new CrmActivityProviderType($id);
		}

		$crmField = static::resolveCrmField($type, $id);
		if ($crmField)
		{
			return $crmField;
		}

		return new OtherField($id);
	}

	private static function resolveCrmField(string $type, string $id): ?Field
	{
		$displayParams = [];

		if ($type === \Bitrix\Crm\Field::TYPE_CRM_LEAD)
		{
			$type = CrmField::TYPE;
			$displayParams = [CCrmOwnerType::LeadName => 'Y'];
		}

		if ($type === \Bitrix\Crm\Field::TYPE_CRM_DEAL)
		{
			$type = CrmField::TYPE;
			$displayParams = [CCrmOwnerType::DealName => 'Y'];
		}

		if ($type === \Bitrix\Crm\Field::TYPE_CRM_QUOTE)
		{
			$type = CrmField::TYPE;
			$displayParams = [CCrmOwnerType::QuoteName => 'Y'];
		}

		if ($type === 'crm_invoice')
		{
			$type = CrmField::TYPE;
			$displayParams = [CCrmOwnerType::InvoiceName => 'Y'];
		}

		// these fields render with client_light type
		// if ($type === \Bitrix\Crm\Field::TYPE_CRM_CONTACT)
		// {
		// 	$type = CrmField::TYPE;
		// 	$displayParams = [\CCrmOwnerType::ContactName => 'Y'];
		// }
		// if ($type === \Bitrix\Crm\Field::TYPE_CRM_COMPANY)
		// {
		// 	$type = CrmField::TYPE;
		// 	$displayParams = [\CCrmOwnerType::CompanyName => 'Y'];
		// }

		if ($type === \Bitrix\Crm\Field::TYPE_CRM_ENTITY)
		{
			$type = CrmField::TYPE;

			if (strpos($id, 'PARENT_ID_') === 0)
			{
				$entityTypeId = (int)substr($id, 10);
				$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);

				if ($entityTypeName !== '')
				{
					$displayParams[$entityTypeName] = 'Y';
				}
			}
		}

		if ($type === CrmField::TYPE)
		{
			$field = new CrmField($id);

			if (!empty($displayParams))
			{
				$field->addDisplayParams($displayParams);
			}

			return $field;
		}

		return null;
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

	public function addDisplayParams(array $displayParams): Field
	{
		$this->displayParams = array_merge($this->displayParams, $displayParams);

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

		throw new ArgumentException('Unknown context: ' . $context);
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

	/**
	 * @param $fieldValue
	 * @param int $itemId
	 * @param Options $displayOptions
	 * @return array
	 */
	protected function getFormattedValueForMobile($fieldValue, int $itemId, Options $displayOptions): array
	{
		if ($this->isMultiple())
		{
			$results = [];

			$fieldValue = (is_array($fieldValue) ? $fieldValue : [$fieldValue]);
			foreach ($fieldValue as $value)
			{
				$results[] = $this->renderSingleValue($value, $itemId, $displayOptions);
			}

			return [
				'value' => $results,
				'config' => $this->getMobileConfig($fieldValue, $itemId, $displayOptions),
			];
		}

		return [
			'value' => $this->renderSingleValue($fieldValue, $itemId, $displayOptions),
			'config' => $this->getMobileConfig($fieldValue, $itemId, $displayOptions),
		];
	}

	protected function getMobileConfig($fieldValue, int $itemId, Options $displayOptions): array
	{
		return [];
	}

	/**
	 * @param array $values
	 * @return array
	 */
	protected function getPreparedArrayValues(array $values): array
	{
		if ($this->isUserField())
		{
			$values = array_map(
				'htmlspecialcharsback',
				array_filter($values, 'is_scalar')
			);
		}

		return array_values($values);
	}

	protected function renderSingleValue($fieldValue, int $itemId, Options $displayOptions): string
	{
		if ($this->isMobileContext())
		{
			if (is_string($fieldValue) || is_numeric($fieldValue))
			{
				return str_replace(
					'<br>',
					'',
					html_entity_decode($fieldValue, ENT_QUOTES, SITE_CHARSET)
				);
			}

			return '';
		}

		if (!$this->isMultiple() && $fieldValue !== '')
		{
			if ($this->isUserField())
			{
				$fieldValue = htmlspecialcharsback($fieldValue);
			}

			return $this->render(
				$displayOptions,
				$itemId,
				$fieldValue
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

		if ($this->isExportContext() || $displayOptions->isShowOnlyText())
		{
			return (string)((new \Bitrix\Main\UserField\Renderer(
				$userFieldParams,
				[
					'CONTEXT' => 'CRM_GRID',
					'mode' => 'main.public_text',
					'renderContext' => 'export',
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

	protected function sanitizeString(string $string): string
	{
		if ($this->isMobileContext())
		{
			return $string;
		}

		return htmlspecialcharsbx($string);
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
