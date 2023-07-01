<?php

namespace Bitrix\Crm;

use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Operation\FieldAfterSaveResult;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Result;

class Field
{
	public const ERROR_CODE_VALUE_NOT_UNIQUE = 'CRM_FIELD_ERROR_VALUE_NOT_UNIQUE';
	public const ERROR_CODE_REQUIRED_FIELD_ATTRIBUTE = 'CRM_FIELD_ERROR_REQUIRED';
	public const ERROR_CODE_PRODUCTS_NOT_FETCHED = 'CRM_FIELD_ERROR_PRODUCTS_NOT_FETCHED';
	public const ERROR_CODE_VALUE_NOT_VALID = 'CRM_FIELD_ERROR_VALUE_NOT_VALID';
	public const ERROR_CODE_CATEGORY_NOT_AVAILABLE = 'CRM_FIELD_ERROR_CATEGORY_NOT_AVAILABLE';

	public const MESSAGE_FIELD_VALUE_REQUIRED = 'CRM_FIELD_VALUE_REQUIRED_ERROR';
	public const MESSAGE_FIELD_VALUE_NOT_UNIQUE = 'CRM_FIELD_NOT_UNIQUE_ERROR';
	public const MESSAGE_FIELD_VALUE_NOT_VALID = 'CRM_FIELD_NOT_VALID_ERROR';

	public const TYPE_INTEGER = 'integer';
	public const TYPE_DOUBLE = 'double';
	public const TYPE_STRING = 'string';
	public const TYPE_CHAR = 'char';
	public const TYPE_TEXT = 'text';
	public const TYPE_BOOLEAN = 'boolean';
	public const TYPE_DATE = 'date';
	public const TYPE_DATETIME = 'datetime';
	public const TYPE_USER = 'user';
	public const TYPE_FILE = 'file';
	public const TYPE_LOCATION = 'location';
	public const TYPE_CRM_CATEGORY = 'crm_category';
	public const TYPE_CRM_STATUS = 'crm_status';
	public const TYPE_CRM_CURRENCY = 'crm_currency';
	public const TYPE_CRM_COMPANY = 'crm_company';
	public const TYPE_CRM_CONTACT = 'crm_contact';
	public const TYPE_CRM_LEAD = 'crm_lead';
	public const TYPE_CRM_DEAL = 'crm_deal';
	public const TYPE_CRM_QUOTE = 'crm_quote';
	public const TYPE_CRM_PRODUCT_ROW = 'crm_product_row';
	public const TYPE_CRM_ENTITY = 'crm_entity';
	public const TYPE_CRM_MULTIFIELD = 'crm_multifield';

	public const VALUE_TYPE_PLAIN_TEXT = 'text';
	public const VALUE_TYPE_HTML = 'html';
	public const VALUE_TYPE_IMAGE = 'image';

	/** @var string */
	protected $name;
	/** @var string */
	protected $type;
	/** @var string */
	protected $title;
	/** @var array */
	protected $attributes;
	/** @var array */
	protected $settings;
	/** @var array */
	protected $userField;
	/** @var string|null */
	protected $crmStatusType;
	protected $valueType;

	public function __construct(string $name, array $description)
	{
		$this->name = $name;
		$this->type = $description['TYPE'] ?? '';
		$this->title = $description['TITLE'] ?? '';
		$this->attributes = $description['ATTRIBUTES'] ?? [];
		$this->settings = $description['SETTINGS'] ?? [];
		$this->userField = $description['USER_FIELD'] ?? [];
		$this->valueType = $description['VALUE_TYPE'] ?? null;
		$this->crmStatusType = isset($description['CRM_STATUS_TYPE']) ? (string)$description['CRM_STATUS_TYPE'] : null;

		Loc::loadMessages(__FILE__);
	}

	/**
	 * Return true if attributes allows to change value of this field by user.
	 *
	 * @return bool
	 */
	public function isValueCanBeChanged(): bool
	{
		$immutableAttributes = [\CCrmFieldInfoAttr::ReadOnly, \CCrmFieldInfoAttr::Immutable];

		return empty(array_intersect($this->attributes, $immutableAttributes));
	}

	/**
	 * Process logic of this field that depends on user permissions.
	 *
	 * @param Item $item - item to process field on.
	 * @param UserPermissions $userPermissions - object to check permissions by.
	 * @return Result
	 */
	public function processWithPermissions(Item $item, UserPermissions $userPermissions): Result
	{
		return new Result();
	}

	/**
	 * Process attributes and specific business-logic of this field.
	 *
	 * @param Item $item - item to process logic on.
	 * @param Context|null $context
	 * @return Result
	 */
	public function process(Item $item, Context $context = null): Result
	{
		$result = $this->processAttributes($item);

		if(!$result->isSuccess())
		{
			return $result;
		}

		return $this->processLogic($item, $context);
	}

	/**
	 * This method process some additional logic of this field that should be invoked after saving.
	 * Return object that may have some new values for $item that should be saved separately.
	 * This method should not perform changing of $item, only some related data.
	 *
	 * @param Item $itemBeforeSave - item with in condition before it was saved.
	 * @param Item $item - item in actual condition.
	 * @param Context|null $context
	 * @return FieldAfterSaveResult
	 */
	public function processAfterSave(Item $itemBeforeSave, Item $item, Context $context = null): FieldAfterSaveResult
	{
		return new FieldAfterSaveResult();
	}

	protected function processLogic(Item $item, Context $context = null): Result
	{
		return new Result();
	}

	final protected function processAttributes(Item $item): Result
	{
		$result = new Result();

		if(empty($this->attributes))
		{
			return $result;
		}

		$isValueChanged = $item->isChanged($this->name);
		$fieldValue = $item->get($this->name);
		$isValueEmpty = $this->isValueEmpty($fieldValue);
		$isNew = $item->isNew();

		foreach($this->attributes as $attribute)
		{
			if(
				$attribute === \CCrmFieldInfoAttr::ReadOnly
				&& $isValueChanged
			)
			{
				$item->reset($this->name);
				if($isNew)
				{
					$item->set($this->name, $item->getDefaultValue($this->name));
				}
			}
			elseif(
				$attribute === \CCrmFieldInfoAttr::HasDefaultValue
				&& $isValueEmpty
				&& $isNew
			)
			{
				$item->set($this->name, $item->getDefaultValue($this->name));
			}
			elseif(
				$attribute === \CCrmFieldInfoAttr::Immutable
				&& $isValueChanged
				&& !$isNew
			)
			{
				$item->reset($this->name);
			}
//			elseif(
//				$attribute === \CCrmFieldInfoAttr::Required
//				&& $isValueEmpty
//			)
//			{
//				$result->addError(static::getRequiredEmptyError($this->getName(), $this->getTitle()));
//			}
			elseif(
				$attribute === \CCrmFieldInfoAttr::Unique
				&& !$isValueEmpty
				&& !$this->isValueUnique($fieldValue, $item->getId() ?? 0)
			)
			{
				$result->addError($this->getValueNotUniqueError());
			}
			elseif ($attribute === \CCrmFieldInfoAttr::CanNotBeEmptied && !$isNew && $isValueEmpty)
			{
				if ($isValueChanged && !$this->isValueEmpty($item->remindActual($this->getName())))
				{
					$item->reset($this->getName());
				}
				elseif ($this->isHasDefaultValue())
				{
					$item->set($this->getName(), $item->getDefaultValue($this->getName()));
				}
			}
		}

		return $result;
	}

	/**
	 * Return true if $item's value of this field is empty.
	 *
	 * @param Item $item
	 * @return bool
	 */
	public function isItemValueEmpty(Item $item): bool
	{
		return $this->isValueEmpty($item->get($this->getName()));
	}

	/**
	 * Return true if $fieldValue considered not filled.
	 *
	 * @param $fieldValue
	 * @return bool
	 */
	public function isValueEmpty($fieldValue): bool
	{
		if (
			is_array ($fieldValue)
			&& in_array(\CCrmFieldInfoAttr::Multiple, $this->getAttributes(), true)
		)
		{
			foreach ($fieldValue as $singleValue)
			{
				if (!$this->isValueEmpty($singleValue))
				{
					return false;
				}
			}

			return true;
		}

		if (
			$this->type === static::TYPE_CRM_CATEGORY
			&& ($fieldValue === 0 || $fieldValue === "0")
		)
		{
			return false;
		}

		// Interpret bool 'false' as non-empty for boolean type
		if ($this->type === static::TYPE_BOOLEAN && (bool)$fieldValue === false)
		{
			return false;
		}

		if (
			($this->type === static::TYPE_INTEGER || $this->type === static::TYPE_DOUBLE)
			&& ($fieldValue === 0 || $fieldValue === 0.0 || $fieldValue === "0" || $fieldValue === "0.0" || $fieldValue === "0,0")
		)
		{
			return false;
		}

		if ($this->type === static::TYPE_STRING && $fieldValue === "0")
		{
			return false;
		}

		return empty($fieldValue);
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): Field
	{
		$this->name = $name;

		return $this;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function setTitle(string $title): self
	{
		$this->title = $title;
		return $this;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function setType(string $type): Field
	{
		$this->type = $type;

		return $this;
	}

	public function getAttributes(): array
	{
		return $this->attributes;
	}

	public function setAttributes(array $attributes): Field
	{
		$this->attributes = $attributes;

		return $this;
	}

	public function getSettings(): array
	{
		return $this->settings;
	}

	public function setSettings(array $settings): Field
	{
		$this->settings = $settings;

		return $this;
	}

	public function isUserField(): bool
	{
		return !empty($this->userField);
	}

	public function getUserField(): array
	{
		return $this->userField;
	}

	public function getValueType(): ?string
	{
		return $this->valueType;
	}

	/**
	 * Sets a CRM_STATUS_TYPE value
	 *
	 * @param string $crmStatusType
	 *
	 * @return $this
	 */
	public function setCrmStatusType(string $crmStatusType): Field
	{
		$this->crmStatusType = $crmStatusType;

		return $this;
	}

	/**
	 * Returns a CRM_STATUS_TYPE value
	 * If not set, returns null
	 *
	 * @return string|null
	 */
	public function getCrmStatusType(): ?string
	{
		return $this->crmStatusType;
	}

	/**
	 * Returns true if this field has the 'AutoGenerated' attribute.
	 *
	 * @return bool
	 */
	public function isAutoGenerated(): bool
	{
		return in_array(\CCrmFieldInfoAttr::AutoGenerated, $this->getAttributes(), true);
	}

	/**
	 * Return true if this field has the 'Multiple' attribute.
	 *
	 * @return bool
	 */
	public function isMultiple(): bool
	{
		return in_array(\CCrmFieldInfoAttr::Multiple, $this->getAttributes(), true);
	}

	/**
	 * Return true if this field does not has the 'NotDisplayed' attribute.
	 *
	 * @return bool
	 */
	public function isDisplayed(): bool
	{
		return !in_array(\CCrmFieldInfoAttr::NotDisplayed, $this->getAttributes(), true);
	}

	/**
	 * Return true if this field has the 'Required' attribute.
	 *
	 * @return bool
	 */
	public function isRequired(): bool
	{
		return in_array(\CCrmFieldInfoAttr::Required, $this->getAttributes(), true);
	}

	/**
	 * Return true if this field has 'Hidden' attribute
	 *
	 * @return bool
	 */
	public function isHidden(): bool
	{
		return in_array(\CCrmFieldInfoAttr::Hidden, $this->getAttributes(), true);
	}

	/**
	 * Return true if this field has 'Progress' attribute
	 *
	 * @return bool
	 */
	public function isProgress(): bool
	{
		return in_array(\CCrmFieldInfoAttr::Progress, $this->getAttributes(), true);
	}

	public function isHasDefaultValue(): bool
	{
		return in_array(\CCrmFieldInfoAttr::HasDefaultValue, $this->getAttributes(), true);
	}

	/**
	 * Get data about this field as array.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$array = [
			'TITLE' => $this->getTitle(),
			'TYPE' => $this->getType(),
			'ATTRIBUTES' => $this->getAttributes(),
			'SETTINGS' => $this->getSettings(),
			'USER_FIELD' => $this->getUserField(),
			'VALUE_TYPE' => $this->valueType,
		];

		if (!is_null($this->getCrmStatusType()))
		{
			$array['CRM_STATUS_TYPE'] = $this->getCrmStatusType();
		}

		return $array;
	}

	/**
	 * Return true if this is a user field for files.
	 *
	 * @return bool
	 */
	public function isFileUserField(): bool
	{
		return ($this->isUserField() && $this->getUserField()['USER_TYPE']['BASE_TYPE'] === 'file');
	}

	protected function isValueUnique($value, int $id = null): bool
	{
		if($this->isValueEmpty($value))
		{
			return false;
		}
		if(!is_scalar($value))
		{
			return true;
		}
		$tableClassName = $this->settings['tableClassName'] ?? null;
		if(!$tableClassName || !is_a($tableClassName, DataManager::class, true))
		{
			return true;
		}

		$filter = [
			'=' . $this->getName() => $value,
		];
		if($id > 0)
		{
			$filter['!=' . Item::FIELD_NAME_ID] = $id;
		}

		return ((int) $tableClassName::getCount($filter) === 0);
	}

	public function getValueNotValidError(): Error
	{
		$title = $this->getTitle() ?? $this->getName();
		return new Error(
			Loc::getMessage(static::MESSAGE_FIELD_VALUE_NOT_VALID, [
				'#FIELD#' => $title,
			]),
			static::ERROR_CODE_VALUE_NOT_VALID
		);
	}

	public static function getRequiredEmptyError(string $fieldName, ?string $title = null): Error
	{
		$title = $title ?? $fieldName;

		return new Error(
			Loc::getMessage(static::MESSAGE_FIELD_VALUE_REQUIRED, [
				'#FIELD#' => $title,
			]),
			static::ERROR_CODE_REQUIRED_FIELD_ATTRIBUTE,
			[
				'fieldName' => $fieldName,
			]
		);
	}

	public function getValueNotUniqueError(): Error
	{
		$title = $this->getTitle() ?? $this->getName();
		$message = $this->getSettings()['fieldValueNotUniqueErrorMessage'] ?? null;
		if (!$message)
		{
			$message = Loc::getMessage(static::MESSAGE_FIELD_VALUE_NOT_UNIQUE, [
				'#FIELD#' => $title,
			]);
		}

		return new Error(
			$message,
			static::ERROR_CODE_VALUE_NOT_UNIQUE,
			[
				'fieldName' => $this->getName(),
			]
		);
	}

	protected function getFactoryNotFoundError(int $entityTypeId): Error
	{
		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);

		return new Error("Can not find factory for the entity type {$entityTypeName}");
	}
}
