<?php

namespace Bitrix\Crm;

use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Item\DisabledField;
use Bitrix\Crm\Item\FieldImplementation;
use Bitrix\Crm\Observer\Entity\EO_Observer;
use Bitrix\Crm\Observer\Entity\EO_Observer_Collection;
use Bitrix\Crm\Observer\Entity\ObserverTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\ParentFieldManager;
use Bitrix\Crm\UserField\UserFieldFilterable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\BooleanField;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\ScalarField;
use Bitrix\Main\Error;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\FieldTypeMask;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\UserTypeField;
use Bitrix\Main\ORM\Fields\UserTypeUtsMultipleField;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\Text\StringHelper;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

/**
 * Class Item
 * @package Bitrix\Crm
 *
 * @method string|null getTitle()
 * @method Item setTitle(string $title)
 * @method bool isChangedTitle()
 * @method DateTime|null getCreatedTime()
 * @method Item setCreatedTime(DateTime $dateTime)
 * @method DateTime|null getUpdatedTime()
 * @method Item setUpdatedTime(DateTime $dateTime)
 * @method DateTime|null getMovedTime()
 * @method Item setMovedTime(DateTime $dateTime)
 * @method int|null getCreatedBy()
 * @method Item setCreatedBy(int $createdBy)
 * @method int|null getUpdatedBy()
 * @method Item setUpdatedBy(int $updatedBy)
 * @method int|null getMovedBy()
 * @method Item setMovedBy(int $movedBy)
 * @method int|null getAssignedById()
 * @method Item setAssignedById(int $assignedById)
 * @method bool|null getOpened()
 * @method Item setOpened(bool $isOpened)
 * @method Date|null getBegindate()
 * @method Item setBegindate(Date $begindate)
 * @method Date|null getClosedate()
 * @method Item setClosedate(Date $closedate)
 * @method int|null getCompanyId()
 * @method Item setCompanyId(int $companyId)
 * @method EO_Company|null getCompany()
 * @method int|null getContactId()
 * @method Item setContactId(int $contactId)
 * @method array getContactIds()
 * @method Item setContactIds(array $contactIds)
 * @method array getContactBindings()
 * @method Item setContactBindings(array $contactBindings)
 * @method array getContacts()
 * @method string|null getStageId()
 * @method string|null getPreviousStageId()
 * @method Item setStageId(string $stageId)
 * @method bool isChangedStageId()
 * @method int|null getCategoryId()
 * @method Item setCategoryId(int $categoryId)
 * @method bool isChangedCategoryId()
 * @method float|null getOpportunity()
 * @method Item setOpportunity(float $opportunity)
 * @method bool|null getIsManualOpportunity()
 * @method Item setIsManualOpportunity(bool $isManualOpportunity)
 * @method float|null getTaxValue()
 * @method Item setTaxValue(float $taxValue)
 * @method string|null getCurrencyId()
 * @method Item setCurrencyId(string $currencyId)
 * @method float|null getExchRate()
 * @method Item setExchRate(float $exchRate)
 * @method float|null getOpportunityAccount()
 * @method Item setOpportunityAccount(float $opportunityAccount)
 * @method float|null getTaxValueAccount()
 * @method Item setTaxValueAccount(float $taxValueAccount)
 * @method string|null getAccountCurrencyId()
 * @method Item setAccountCurrencyId(string $accountCurrencyId)
 * @method int|null getMycompanyId()
 * @method Item setMycompanyId(int $mycompanyId)
 * @method EO_Company|null getMycompany()
 * @method ProductRowCollection|null getProductRows()
 * @method bool|null getClosed()
 * @method Item setClosed(bool $isClosed)
 * @method string|null getSourceId()
 * @method Item setSourceId(string $sourceId)
 * @method string|null getSourceDescription()
 * @method Item setSourceDescription(string $sourceDescription)
 * @method int|null getWebformId()
 * @method Item setWebformId(int $webformId)
 * @method int|null getLocationId()
 * @method Item setLocationId(int $locationId)
 * @method string|null getComments()
 * @method Item setComments(string $comments)
 * @method string|null getHonorific()
 * @method Item setHonorific(string $honorific)
 * @method string|null getName()
 * @method Item setName(string $name)
 * @method string|null getSecondName()
 * @method Item setSecondName(string $secondName)
 * @method string|null getLastName()
 * @method Item setLastName(string $lastName)
 * @method string|null getFullName()
 * @method Item setFullName(string $fullName)
 * @method string|null getPost()
 * @method Item setPost(string $post)
 * @method Date|null getBirthdate()
 * @method Item setBirthdate(Date $birthdate)
 * @method int|null getBirthdaySort()
 * @method Item setBirthdaySort(int $birthdaySort)
 * @method string|null getOriginatorId()
 * @method Item setOriginatorId(string $originatorId)
 * @method int|null getOriginId()
 * @method Item setOriginId(int $originId)
 * @method string|null getOriginVersion()
 * @method Item setOriginVersion(string $originVersion)
 * @method int|null getFaceId()
 * @method Item setFaceId(int $faceId)
 * @method string|null getTypeId()
 * @method Item setTypeId(string $typeId)
 * @method string|null getStageSemanticId()
 * @method Item setStageSemanticId(string $stageSemanticId)
 * @method bool|null getIsRecurring()
 * @method Item setIsRecurring(bool $isRecurring)
 * @method bool|null getIsReturnCustomer()
 * @method Item setIsReturnCustomer(bool $isReturnCustomer)
 * @method int|null getLeadId()
 * @method Item setLeadId(int $leadId)
 * @method Multifield\Collection getFm()
 * @method Item setFm(Multifield\Collection $fm)
 * @method bool isChangedFm()
 */
abstract class Item implements \JsonSerializable, \ArrayAccess, Arrayable
{
	public const FIELD_NAME_ID = 'ID';
	public const FIELD_NAME_TITLE = 'TITLE';
	public const FIELD_NAME_XML_ID = 'XML_ID';
	public const FIELD_NAME_CREATED_TIME = 'CREATED_TIME';
	public const FIELD_NAME_UPDATED_TIME = 'UPDATED_TIME';
	public const FIELD_NAME_MOVED_TIME = 'MOVED_TIME';
	public const FIELD_NAME_LAST_ACTIVITY_TIME = 'LAST_ACTIVITY_TIME';
	public const FIELD_NAME_CREATED_BY = 'CREATED_BY';
	public const FIELD_NAME_UPDATED_BY = 'UPDATED_BY';
	public const FIELD_NAME_MOVED_BY = 'MOVED_BY';
	public const FIELD_NAME_LAST_ACTIVITY_BY = 'LAST_ACTIVITY_BY';
	public const FIELD_NAME_ASSIGNED = 'ASSIGNED_BY_ID';
	public const FIELD_NAME_OPENED = 'OPENED';
	public const FIELD_NAME_BEGIN_DATE = 'BEGINDATE';
	public const FIELD_NAME_CLOSE_DATE = 'CLOSEDATE';
	public const FIELD_NAME_COMPANY_ID = 'COMPANY_ID';
	public const FIELD_NAME_COMPANY = 'COMPANY';
	public const FIELD_NAME_CONTACT_ID = 'CONTACT_ID';
	public const FIELD_NAME_CONTACTS = 'CONTACTS';
	public const FIELD_NAME_CONTACT_BINDINGS = 'CONTACT_BINDINGS';
	public const FIELD_NAME_CONTACT_IDS = 'CONTACT_IDS';
	public const FIELD_NAME_STAGE_ID = 'STAGE_ID';
	public const FIELD_NAME_PREVIOUS_STAGE_ID = 'PREVIOUS_STAGE_ID';
	public const FIELD_NAME_CATEGORY_ID = 'CATEGORY_ID';
	public const FIELD_NAME_OPPORTUNITY = 'OPPORTUNITY';
	public const FIELD_NAME_IS_MANUAL_OPPORTUNITY = 'IS_MANUAL_OPPORTUNITY';
	public const FIELD_NAME_TAX_VALUE = 'TAX_VALUE';
	public const FIELD_NAME_CURRENCY_ID = 'CURRENCY_ID';
	public const FIELD_NAME_EXCH_RATE = 'EXCH_RATE';
	public const FIELD_NAME_OPPORTUNITY_ACCOUNT = 'OPPORTUNITY_ACCOUNT';
	public const FIELD_NAME_TAX_VALUE_ACCOUNT = 'TAX_VALUE_ACCOUNT';
	public const FIELD_NAME_ACCOUNT_CURRENCY_ID = 'ACCOUNT_CURRENCY_ID';
	public const FIELD_NAME_MYCOMPANY_ID = 'MYCOMPANY_ID';
	public const FIELD_NAME_MYCOMPANY = 'MYCOMPANY';
	public const FIELD_NAME_PRODUCTS = 'PRODUCT_ROWS';
	public const FIELD_NAME_PRODUCT_RESERVATION = 'PRODUCT_ROW_RESERVATION';
	public const FIELD_NAME_CLOSED = 'CLOSED';
	public const FIELD_NAME_SOURCE_ID = 'SOURCE_ID';
	public const FIELD_NAME_SOURCE_DESCRIPTION = 'SOURCE_DESCRIPTION';
	public const FIELD_NAME_OBSERVERS = 'OBSERVERS';
	public const FIELD_NAME_WEBFORM_ID = 'WEBFORM_ID';
	public const FIELD_NAME_LOCATION_ID = 'LOCATION_ID';
	public const FIELD_NAME_COMMENTS = 'COMMENTS';
	public const FIELD_NAME_HONORIFIC = 'HONORIFIC';
	public const FIELD_NAME_NAME = 'NAME';
	public const FIELD_NAME_SECOND_NAME = 'SECOND_NAME';
	public const FIELD_NAME_LAST_NAME = 'LAST_NAME';
	public const FIELD_NAME_FULL_NAME = 'FULL_NAME';
	public const FIELD_NAME_POST = 'POST';
	public const FIELD_NAME_HAS_PHONE = 'HAS_PHONE';
	public const FIELD_NAME_HAS_EMAIL = 'HAS_EMAIL';
	public const FIELD_NAME_HAS_IMOL = 'HAS_IMOL';
	public const FIELD_NAME_BIRTHDATE = 'BIRTHDATE';
	public const FIELD_NAME_BIRTHDAY_SORT = 'BIRTHDAY_SORT';
	public const FIELD_NAME_ORIGINATOR_ID = 'ORIGINATOR_ID';
	public const FIELD_NAME_ORIGIN_ID = 'ORIGIN_ID';
	public const FIELD_NAME_ORIGIN_VERSION = 'ORIGIN_VERSION';
	public const FIELD_NAME_FACE_ID = 'FACE_ID';
	public const FIELD_NAME_TYPE_ID = 'TYPE_ID';
	public const FIELD_NAME_STAGE_SEMANTIC_ID = 'STAGE_SEMANTIC_ID';
	public const FIELD_NAME_IS_RECURRING = 'IS_RECURRING';
	public const FIELD_NAME_IS_RETURN_CUSTOMER = 'IS_RETURN_CUSTOMER';
	public const FIELD_NAME_LEAD_ID = 'LEAD_ID';
	public const FIELD_NAME_FM = 'FM';

	protected const SORT_OFFSET = 10;

	/** @var array */
	public $primary;

	/** @var UtmTable */
	protected $utmTableClassName = UtmTable::class;
	/** @var ObserverTable */
	protected $observerDataClass = ObserverTable::class;

	protected $entityTypeId;
	/** @var EntityObject */
	protected $entityObject;
	// commonFieldName => entityFieldName
	protected $fieldsMap = [];
	protected $actualValues = [];
	protected $currentValues = [];
	/** @var DisabledField[] */
	protected $disabledFieldNames = [];
	protected $isUtmLoaded = false;

	/** @var Array<string, FieldImplementation> - commonFieldName => implementation*/
	private $fieldToImplementationMap = [];
	/** @var FieldImplementation[]|null */
	private $allImplementationsCache;
	private array $badges = [];

	public function __construct(
		int $entityTypeId,
		EntityObject $entityObject,
		array $fieldsMap = [],
		array $disabledFields = []
	)
	{
		$this->entityTypeId = $entityTypeId;
		$this->entityObject = $entityObject;
		$this->fieldsMap = $fieldsMap;
		$this->primary = $entityObject->primary;

		foreach ($disabledFields as $disabledField)
		{
			if (is_string($disabledField))
			{
				$disabledField = new DisabledField($disabledField);
			}

			if (!$disabledField instanceof DisabledField)
			{
				throw new ArgumentException(sprintf(
					'Disabled field must be instance of %s',
					DisabledField::class
				));
			}

			$this->disabledFieldNames[] = $disabledField;
		}

		$this->fillExpressionFields($entityObject);
	}

	public function __clone()
	{
		$clonedEntityObject = clone $this->entityObject;
		$this->entityObject = $clonedEntityObject;

		$this->fieldToImplementationMap = \Bitrix\Main\Type\Collection::clone($this->fieldToImplementationMap);
		unset($this->allImplementationsCache);
		foreach ($this->getAllImplementations() as $implementation)
		{
			$implementation->afterItemClone($this, $clonedEntityObject);
		}
	}

	final public function addImplementation(FieldImplementation $implementation): self
	{
		$newImplementationHandledList = $implementation->getHandledFieldNames();
		$intersection = [];
		foreach ($newImplementationHandledList as $commonFieldName)
		{
			if (isset($this->fieldToImplementationMap[$commonFieldName]))
			{
				$intersection[] = $commonFieldName;
			}
		}
		if (!empty($intersection))
		{
			throw new ArgumentException(
				'The provided implementation handles fields that are already'
				. ' handled by other implementations: ' . var_export($intersection, true)
			);
		}

		foreach ($newImplementationHandledList as $commonFieldName)
		{
			$this->fieldToImplementationMap[$commonFieldName] = $implementation;
		}
		unset($this->allImplementationsCache);

		return $this;
	}

	private function getImplementation(string $commonFieldName): ?FieldImplementation
	{
		return $this->fieldToImplementationMap[$commonFieldName] ?? null;
	}

	private function getAllImplementations(): array
	{
		if (isset($this->allImplementationsCache) && is_array($this->allImplementationsCache))
		{
			return $this->allImplementationsCache;
		}

		$implementations = [];
		foreach ($this->fieldToImplementationMap as $implementation)
		{
			if (!in_array($implementation, $implementations, true))
			{
				$implementations[] = $implementation;
			}
		}

		$this->allImplementationsCache = $implementations;

		return $implementations;
	}

	/**
	 * Return item`s representation title.
	 * Real title can be empty, and in this case the item can have some other way of naming.
	 *
	 * @return string|null
	 */
	public function getHeading(): ?string
	{
		$title = $this->getTitle();

		if (empty($title))
		{
			$title = $this->getTitlePlaceholder();
		}

		return $title;
	}

	/**
	 * Implements default 'get' and 'set' behaviour
	 *
	 * @param $name
	 * @param $arguments
	 *
	 * @return mixed|null
	 * @throws SystemException
	 */
	public function __call($name, $arguments)
	{
		$method = mb_substr($name, 0, 3);
		$calledFieldName = mb_substr($name, 3);
		$commonFieldName = $this->transformToCommonFieldName($calledFieldName);

		if ($method === 'get')
		{
			return $this->get($commonFieldName);
		}
		if ($method === 'set')
		{
			return $this->set($commonFieldName, ...$arguments);
		}
		$method = mb_substr($name, 0, 9);
		$calledFieldName = mb_substr($name, 9);
		$commonFieldName = $this->transformToCommonFieldName($calledFieldName);
		if ($method === 'isChanged')
		{
			return $this->isChanged($commonFieldName);
		}

		throw new SystemException(sprintf(
			'Unknown method `%s` for object `%s`', $name, static::class
		));
	}

	protected function transformToCommonFieldName(string $calledFieldName): string
	{
		$snake = StringHelper::camel2snake($calledFieldName);

		return StringHelper::strtoupper($snake);
	}

	protected function transformToCalledFieldName(string $commonFieldName): string
	{
		return StringHelper::snake2camel($commonFieldName);
	}

	protected function getEntityFieldNameByMap(string $commonFieldName): string
	{
		return $this->fieldsMap[$commonFieldName] ?? $commonFieldName;
	}

	protected function getCommonFieldNameByMap(string $entityFieldName): string
	{
		return array_flip($this->fieldsMap)[$entityFieldName] ?? $entityFieldName;
	}

	protected function getEntityFieldNames(int $fieldTypeMask = FieldTypeMask::ALL): array
	{
		$names = [];
		foreach ($this->entityObject->sysGetEntity()->getFields() as $field)
		{
			if ($this->isFieldMatchesTypeMask($fieldTypeMask, $field))
			{
				$names[] = $field->getName();
			}
		}

		return $names;
	}

	protected function isFieldMatchesTypeMask(int $fieldTypeMask, \Bitrix\Main\ORM\Fields\Field $field): bool
	{
		return (bool)($fieldTypeMask & $field->getTypeMask());
	}

	public function hasField(string $commonFieldName): bool
	{
		$implementation = $this->getImplementation($commonFieldName);
		if ($implementation)
		{
			return true;
		}

		$customMethod = $this->getCustomMethodNameIfExists('has', $commonFieldName);
		if ($customMethod)
		{
			return $this->$customMethod();
		}

		if ($this->isExpressionField($commonFieldName))
		{
			return true;
		}

		return $this->hasFieldInEntityObject($commonFieldName);
	}

	protected function hasFieldInEntityObject(string $commonFieldName): bool
	{
		if ($this->isFieldDisabled($commonFieldName))
		{
			return false;
		}

		$entityFieldName = $this->getEntityFieldNameByMap($commonFieldName);

		return ($this->entityObject->sysGetEntity()->hasField($entityFieldName));
	}

	public function getDefaultValue(string $commonFieldName)
	{
		$implementation = $this->getImplementation($commonFieldName);
		if ($implementation)
		{
			return $implementation->getDefaultValue($commonFieldName);
		}

		if($this->hasFieldInEntityObject($commonFieldName))
		{
			$entityFieldName = $this->getEntityFieldNameByMap($commonFieldName);

			$field = $this->entityObject->sysGetEntity()->getField($entityFieldName);

			if ($field instanceof ScalarField)
			{
				return $field->getDefaultValue();
			}

			if ($field instanceof UserTypeField && $field->getValueField() instanceof ScalarField)
			{
				return $field->getValueField()->getDefaultValue();
			}
		}

		return null;
	}

	public function get(string $commonFieldName)
	{
		if ($this->isFieldDisabled($commonFieldName))
		{
			throw new ArgumentException(sprintf(
				'Item has no %s field',
				$commonFieldName
			));
		}

		$implementation = $this->getImplementation($commonFieldName);
		if ($implementation)
		{
			return $implementation->get($commonFieldName);
		}

		$customMethod = $this->getCustomMethodNameIfExists('get', $commonFieldName);
		if ($customMethod)
		{
			return $this->$customMethod();
		}

		if ($this->isExpressionField($commonFieldName))
		{
			return $this->getExpressionField($commonFieldName);
		}

		$entityFieldName = $this->getEntityFieldNameByMap($commonFieldName);

		return $this->entityObject->get($entityFieldName);
	}

	public function set(string $commonFieldName, $value): self
	{
		if ($this->isFieldDisabled($commonFieldName))
		{
			throw new ArgumentException(sprintf(
				'Item has no %s field',
				$commonFieldName
			));
		}

		$implementation = $this->getImplementation($commonFieldName);
		if ($implementation)
		{
			$implementation->set($commonFieldName, $value);

			return $this;
		}

		$customMethod = $this->getCustomMethodNameIfExists('set', $commonFieldName);
		if ($customMethod)
		{
			$this->$customMethod($value);

			return $this;
		}

		if ($this->isExpressionField($commonFieldName))
		{
			return $this->setExpressionField($commonFieldName, $value);
		}

		$entityFieldName = $this->getEntityFieldNameByMap($commonFieldName);

		if (is_array($value) && $commonFieldName !== static::FIELD_NAME_FM)
		{
			$value = $this->clearEmptyMultipleValues($commonFieldName, $value);
		}
		if (empty($value))
		{
			$value = $this->prepareNullValue($entityFieldName, $value);
		}

		$oldValue = $this->get($commonFieldName);
		$this->entityObject->set($entityFieldName, $value);
		$this->onAfterFieldSet($commonFieldName, $oldValue, $value);

		return $this;
	}

	protected function onAfterFieldSet(string $commonFieldName, $oldValue, $newValue): void
	{
		if ($oldValue === $newValue)
		{
			return;
		}

		if (
			$commonFieldName === static::FIELD_NAME_CATEGORY_ID
			&& $this->isCategoriesSupported()
		)
		{
			$this->refreshCategoryDependentDisabledFields();
		}
	}

	public function reset(string $commonFieldName): self
	{
		$implementation = $this->getImplementation($commonFieldName);
		if ($implementation)
		{
			$implementation->reset($commonFieldName);

			return $this;
		}

		$customMethod = $this->getCustomMethodNameIfExists('reset', $commonFieldName);
		if ($customMethod)
		{
			$this->$customMethod();

			return $this;
		}

		if ($this->isExpressionField($commonFieldName))
		{
			return $this->resetExpressionField($commonFieldName);
		}

		if (!$this->hasFieldInEntityObject($commonFieldName))
		{
			throw new ArgumentException(sprintf(
				'Entity object has no %s field',
				$commonFieldName
			));
		}

		$entityFieldName = $this->getEntityFieldNameByMap($commonFieldName);

		$this->entityObject->reset($entityFieldName);

		return $this;
	}

	public function unset(string $commonFieldName): self
	{
		$implementation = $this->getImplementation($commonFieldName);
		if ($implementation)
		{
			$implementation->unset($commonFieldName);

			return $this;
		}

		$customMethod = $this->getCustomMethodNameIfExists('unset', $commonFieldName);
		if($customMethod)
		{
			$this->$customMethod($commonFieldName);

			return $this;
		}

		if ($this->isExpressionField($commonFieldName))
		{
			return $this->unsetExpressionField($commonFieldName);
		}

		if (!$this->hasFieldInEntityObject($commonFieldName))
		{
			throw new ArgumentException(sprintf(
				'Entity object has no %s field',
				$commonFieldName
			));
		}

		$entityFieldName = $this->getEntityFieldNameByMap($commonFieldName);
		$this->entityObject->sysUnset($entityFieldName);

		return $this;
	}

	public function remindActual(string $commonFieldName)
	{
		$implementation = $this->getImplementation($commonFieldName);
		if ($implementation)
		{
			return $implementation->remindActual($commonFieldName);
		}

		$customMethod = $this->getCustomMethodNameIfExists('remindActual', $commonFieldName);
		if ($customMethod)
		{
			return $this->$customMethod();
		}

		if ($this->isExpressionField($commonFieldName))
		{
			return $this->remindActualExpressionField($commonFieldName);
		}

		if (!$this->hasFieldInEntityObject($commonFieldName))
		{
			throw new ArgumentException(sprintf(
				'Entity object has no %s field',
				$commonFieldName
			));
		}

		$entityFieldName = $this->getEntityFieldNameByMap($commonFieldName);

		return $this->entityObject->remindActual($entityFieldName);
	}

	public function isChanged(string $commonFieldName): bool
	{
		$implementation = $this->getImplementation($commonFieldName);
		if ($implementation)
		{
			return $implementation->isChanged($commonFieldName);
		}

		$customMethod = $this->getCustomMethodNameIfExists('isChanged', $commonFieldName);
		if ($customMethod)
		{
			return $this->$customMethod();
		}

		if ($this->isExpressionField($commonFieldName))
		{
			return $this->isChangedExpressionField($commonFieldName);
		}

		if (!$this->hasFieldInEntityObject($commonFieldName))
		{
			throw new ArgumentException(sprintf(
				'Entity object has no %s field',
				$commonFieldName
			));
		}

		$entityFieldName = $this->getEntityFieldNameByMap($commonFieldName);

		return $this->entityObject->isChanged($entityFieldName);
	}

	protected function getCustomMethodNameIfExists(string $methodPrefix, string $commonFieldName): ?string
	{
		$customMethod = $methodPrefix.$this->transformToCalledFieldName($commonFieldName);
		if (method_exists($this, $customMethod))
		{
			return $customMethod;
		}

		return null;
	}

	public function getData(int $valuesType = Values::ALL): array
	{
		//todo temporary decision to avoid error on jsonSerialization of EntityObjects
		$fieldTypeMask = FieldTypeMask::SCALAR|FieldTypeMask::USERTYPE|FieldTypeMask::EXPRESSION;

		$entityFieldNames = array_merge(
			$this->getEntityFieldNames($fieldTypeMask),
			$this->utmTableClassName::getCodeList(),
		);

		if ($this->hasField(static::FIELD_NAME_OBSERVERS))
		{
			$entityFieldNames[] = $this->getEntityFieldNameByMap(static::FIELD_NAME_OBSERVERS);
		}

		/** @var string[][] $fromImplementation */
		$fromImplementation = [];
		foreach ($this->getAllImplementations() as $implementation)
		{
			$fromImplementation[] = $implementation->getSerializableFieldNames();
		}
		$entityFieldNames = array_merge($entityFieldNames, ...$fromImplementation);

		$data = $this->collectValues($entityFieldNames, $valuesType);

		$commonFieldNames = array_map([$this, 'getCommonFieldNameByMap'], array_keys($data));

		return array_combine($commonFieldNames, array_values($data));
	}

	public function getCompatibleData(int $valuesType = Values::ALL): array
	{
		$data = $this->collectValues($this->getExternalizableFieldNames(), $valuesType);

		$externalData = [];
		foreach ($data as $entityFieldName => $value)
		{
			$externalData[$entityFieldName] = $this->transformToExternalValue($entityFieldName, $value, $valuesType);
		}

		return $externalData;
	}

	public function setFromCompatibleData(array $data): self
	{
		$entityFieldNames = $this->getInternalizableFieldNames();
		foreach ($entityFieldNames as $entityFieldName)
		{
			$commonFieldName = $this->getCommonFieldNameByMap($entityFieldName);
			if ($this->isFieldDisabled($commonFieldName))
			{
				continue;
			}

			if (array_key_exists($entityFieldName, $data))
			{
				$this->setFromExternalValue($commonFieldName, $data[$entityFieldName]);
			}
			elseif ($this->isNew())
			{
				$defaultValue = $this->getDefaultValue($commonFieldName);
				if ($defaultValue !== null)
				{
					$this->setFromExternalValue($commonFieldName, $defaultValue);
				}
			}
		}

		$dataWithCommonFieldNames = [];
		foreach ($data as $entityFieldName => $value)
		{
			$dataWithCommonFieldNames[$this->getCommonFieldNameByMap($entityFieldName)] = $value;
		}

		foreach ($this->getAllImplementations() as $implementation)
		{
			$implementation->setFromExternalValues($dataWithCommonFieldNames);
		}

		return $this;
	}

	public function isNew(): bool
	{
		return (!($this->getId() > 0));
	}

	public function getId(): int
	{
		return (int) $this->entityObject->getId();
	}

	public function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	public function getTitlePlaceholder(): ?string
	{
		return null;
	}

	public function save(bool $isCheckUserFields = true): Result
	{
		$isNew = $this->isNew();
		if (!$isCheckUserFields)
		{
			$this->disableCheckUserFields();
		}

		foreach ($this->getAllImplementations() as $implementation)
		{
			$implementation->beforeItemSave($this, $this->entityObject);
		}

		$result = $this->entityObject->save();
		if($result->isSuccess())
		{
			foreach ($this->getAllImplementations() as $implementation)
			{
				$implementation->afterSuccessfulItemSave($this, $this->entityObject);

				$implementationSaveResult = $implementation->save();
				if (!$implementationSaveResult->isSuccess())
				{
					$result->addErrors($implementationSaveResult->getErrors());
				}
			}

			$saveExpressionFieldsResult = $this->saveExpressionFields($isNew);
			if ($saveExpressionFieldsResult->isSuccess())
			{
				$this->actualValues = $this->currentValues + $this->actualValues;
				$this->currentValues = [];
			}
			else
			{
				$result->addErrors($saveExpressionFieldsResult->getErrors());
			}
		}

		return $result;
	}

	protected function disableCheckUserFields(): void
	{

	}

	public function fill(): void
	{
		if ($this->isNew())
		{
			throw new InvalidOperationException('Cannot fill new item');
		}

		// if we set fm again from compatible data, new values will be duplicated
		$changedValues = $this->getCompatibleData(Values::CURRENT);

		$entityFieldsToFill = $this->getEntityFieldNames(FieldTypeMask::SCALAR|FieldTypeMask::USERTYPE);
		foreach ($this->getAllImplementations() as $implementation)
		{
			$fieldsFromImplementation = array_map(
				[$this, 'getEntityFieldNameByMap'],
				$implementation->getFieldNamesToFill(),
			);

			$entityFieldsToFill = array_merge($entityFieldsToFill, $fieldsFromImplementation);
		}

		if ($this->hasField(static::FIELD_NAME_OBSERVERS))
		{
			$entityFieldsToFill[] = $this->getEntityFieldNameByMap(static::FIELD_NAME_OBSERVERS);
		}
		if ($this->hasField(static::FIELD_NAME_PRODUCTS))
		{
			$entityFieldsToFill[] = $this->getEntityFieldNameByMap(static::FIELD_NAME_PRODUCTS);
		}

		$entityFieldsToFill = array_unique($entityFieldsToFill);

		$this->entityObject->fill($entityFieldsToFill);
		$this->fillExpressionFields($this->entityObject);

		$this->setFromCompatibleData($changedValues);
	}

	public function delete(): Result
	{
		return $this->entityObject->delete();
	}

	// region Contacts
	/**
	 * Returns false if any of item client fields have non-empty value
	 *
	 * @return bool
	 */
	public function isClientEmpty(): bool
	{
		if ($this->hasField(static::FIELD_NAME_COMPANY_ID) && $this->getCompanyId() > 0)
		{
			return false;
		}

		if ($this->hasField(static::FIELD_NAME_CONTACTS) && !is_null($this->getPrimaryContact()))
		{
			return false;
		}

		return true;
	}

	public function getPrimaryContact(): ?Contact
	{
		$primaryOrDefaultContactId = EntityBinding::getPrimaryEntityID(\CCrmOwnerType::Contact, $this->getContactBindings());

		/** @var Contact $contact */
		foreach ($this->getContacts() as $contact)
		{
			if ($contact->getId() === $primaryOrDefaultContactId)
			{
				return $contact;
			}
		}

		return null;
	}

	/**
	 * Bind multiple contacts to the item.
	 *
	 * @param array $contactBindings Array of bindings.
	 * Binding array format is specified in \Bitrix\Crm\Binding\EntityBinding
	 *
	 * @return void
	 * @see \Bitrix\Crm\Binding\EntityBinding
	 */
	public function bindContacts(array $contactBindings): void
	{
		if(empty($contactBindings))
		{
			return;
		}

		$currentContactBindings = $this->getContactBindings();

		$newPrimary = null;
		foreach ($contactBindings as $providedBinding)
		{
			$contactId = EntityBinding::prepareEntityID(\CCrmOwnerType::Contact, $providedBinding);
			$index = EntityBinding::findBindingIndexByEntityID(\CCrmOwnerType::Contact, $contactId, $currentContactBindings);

			if ($index >= 0)
			{
				$currentContactBindings[$index] = $providedBinding;
			}
			else
			{
				$currentContactBindings[] = $providedBinding;
			}

			if (!$newPrimary && EntityBinding::isPrimary($providedBinding))
			{
				$newPrimary = $providedBinding;
			}
		}

		if ($newPrimary)
		{
			$primaryId = EntityBinding::prepareEntityID(\CCrmOwnerType::Contact, $newPrimary);

			EntityBinding::markAsPrimary($currentContactBindings, \CCrmOwnerType::Contact, $primaryId);
		}

		$this->setContactBindings($currentContactBindings);
	}

	/**
	 * Unbind specified contacts from the item.
	 *
	 * @param array $contactBindings Array of bindings.
	 * Binding array format is specified in \Bitrix\Crm\Binding\EntityBinding
	 *
	 * @return void
	 * @see \Bitrix\Crm\Binding\EntityBinding
	 */
	public function unbindContacts(array $contactBindings): void
	{
		$idsToDelete = EntityBinding::prepareEntityIDs(\CCrmOwnerType::Contact, $contactBindings);
		$currentBindings = $this->getContactBindings();
		foreach ($currentBindings as $index => $currentBinding)
		{
			$contactId = EntityBinding::prepareEntityID(\CCrmOwnerType::Contact, $currentBinding);
			if (in_array($contactId, $idsToDelete, true))
			{
				unset($currentBindings[$index]);
			}
		}

		$this->setContactBindings($currentBindings);
	}
	// endregion

	//region Observers
	/**
	 * @return int[]
	 */
	public function getObservers(): array
	{
		$entityFieldName = $this->getEntityFieldNameByMap(static::FIELD_NAME_OBSERVERS);

		/** @var EO_Observer_Collection|null $collection */
		$collection = $this->entityObject->get($entityFieldName);

		return ($collection ? $collection->getUserIdList() : []);
	}

	/**
	 * @param int[] $observerIds
	 *
	 * @return Item
	 */
	public function setObservers($observerIds): Item
	{
		$observerIds = $this->normalizeObserverIds($observerIds);

		/** @var EO_Observer[] $found */
		/** @var EO_Observer[] $removed */
		['found' => $found, 'removed' => $removed] = $this->separateFoundAndRemovedObservers($observerIds);

		$entityFieldName = $this->getEntityFieldNameByMap(static::FIELD_NAME_OBSERVERS);

		$sortIndex = 0;
		foreach ($observerIds as $observerId)
		{
			$observer = $found[$observerId] ?? null;
			if (!$observer)
			{
				// This observer is entirely new and wasn't added previously
				$observer = $this->createObserver($observerId);
				$this->entityObject->addTo($entityFieldName, $observer);
			}

			$observer->setLastUpdatedTime(new DateTime());

			$sort = static::SORT_OFFSET + $sortIndex*static::SORT_OFFSET;
			$observer->setSort($sort);
			$sortIndex++;
		}

		foreach ($removed as $removedObserver)
		{
			$this->entityObject->removeFrom($entityFieldName, $removedObserver);
		}

		return $this;
	}

	protected function normalizeObserverIds($observerIds): array
	{
		$array = (array)$observerIds;
		$arrayOfIntegers = array_map('intval', $array);

		return array_filter($arrayOfIntegers);
	}

	protected function remindActualObservers(): array
	{
		$entityFieldName = $this->getEntityFieldNameByMap(static::FIELD_NAME_OBSERVERS);

		/** @var EO_Observer_Collection|null $actualCollection */
		$collection = $this->entityObject->get($entityFieldName);
		if (is_null($collection))
		{
			return [];
		}

		$actualCollection = $this->getActualCollection($collection);
		return $actualCollection->getUserIdList();
	}

	/**
	 * Separates EO_Observer objects based on the provided ids.
	 * If an object's USER_ID in $observerIds array, it's marked as 'found'. Otherwise - 'removed'
	 *
	 * @param int[] $observerIds
	 *
	 * @return array[]
	 */
	protected function separateFoundAndRemovedObservers(array $observerIds): array
	{
		$entityFieldName = $this->getEntityFieldNameByMap(static::FIELD_NAME_OBSERVERS);

		/** @var EO_Observer_Collection|null $collection */
		$collection = $this->entityObject->get($entityFieldName);
		if (is_null($collection))
		{
			return [
				'found' => [],
				'removed' => [],
			];
		}

		$found = [];
		$removed = [];
		foreach ($collection as $observer)
		{
			$observerId = $observer->getUserId();
			if (in_array($observerId, $observerIds, true))
			{
				$found[$observerId] = $observer;
			}
			else
			{
				$removed[$observerId] = $observer;
			}
		}

		return [
			'found' => $found,
			'removed' => $removed,
		];
	}

	protected function createObserver(int $observerId): EO_Observer
	{
		return $this->observerDataClass::createObject([
			'ENTITY_TYPE_ID' => $this->getEntityTypeId(),
			'ENTITY_ID' => $this->getId(),
			'CREATED_TIME' => new DateTime(),
			'USER_ID' => $observerId,
		]);
	}
	//endregion

	//region Products
	/**
	 * Bind a new product to this item
	 *
	 * @param ProductRow $product
	 *
	 * @return Result
	 */
	public function addToProductRows(ProductRow $product): Result
	{
		$normalizationResult = $this->normalizeProduct($product);

		if ($normalizationResult->isSuccess())
		{
			$this->addToProductsCollection($product);
		}

		return $normalizationResult;
	}

	/**
	 * Update fields of a product that is already bound to this item.
	 * If the provided product is new and not bound, error will be returned.
	 *
	 * @param int $productRowId - id of a ProductRow that is being updated
	 * @param array $productRowArray - array of field values that need to change. If some value for some field is not
	 * provided, it's considered not changed and previous value remains
	 *
	 * @return Result
	 */
	public function updateProductRow(int $productRowId, array $productRowArray): Result
	{
		$originalProduct = $this->getProductRows() ? $this->getProductRows()->getByPrimary($productRowId) : null;
		if (!$originalProduct)
		{
			return (new Result())
				->addError(new Error('The provided product is not bound to the item'))
				;
		}

		foreach ($productRowArray as $fieldName => $value)
		{
			if ($originalProduct->entity->hasField($fieldName))
			{
				$originalProduct->set($fieldName, $value);
			}
		}

		$normalizationResult = $this->normalizeProduct($originalProduct);
		if (!$normalizationResult->isSuccess())
		{
			$originalProduct->resetAll();
		}

		return $normalizationResult;
	}

	/**
	 * Unbind an existing product from this item
	 *
	 * @param ProductRow $product
	 */
	public function removeFromProductRows(ProductRow $product): void
	{
		$entityFieldName = $this->getEntityFieldNameByMap(static::FIELD_NAME_PRODUCTS);
		$this->entityObject->removeFrom($entityFieldName, $product);
	}

	/**
	 * Alias for @see Item::setProductRows()
	 *
	 * @param array[] $productArrays - array of product arrays
	 *
	 * @return Result
	 */
	public function setProductRowsFromArrays(array $productArrays): Result
	{
		$products = [];
		foreach ($productArrays as $productArray)
		{
			$products[] = ProductRow::createFromArray($productArray);
		}

		return $this->setProductRows($products);
	}

	/**
	 * Set products for this item
	 * Adds new products, updates existing ones
	 * If some products were not sent to this method - they are deleted
	 *
	 * @param ProductRow[]|EO_ProductRow_Collection $products
	 *
	 * @return Result
	 */
	public function setProductRows($products): Result
	{
		$results = [];
		foreach ($products as $product)
		{
			$normalizationResult = $this->normalizeProduct($product);
			$results[] = $normalizationResult;
			if (
				$normalizationResult->isSuccess()
				&& !$this->isSameProductInCollection($this->getProductRows() ?? [], $product)
			)
			{
				$this->saveProduct($product);
			}
		}

		$this->deleteNotProvidedProducts($products);

		return $this->mergeResults($results);
	}

	/**
	 * Normalize all product rows bound to this item.
	 *
	 * @internal
	 */
	final public function normalizeProductRows(): Result
	{
		$results = [];
		$productRows = $this->getProductRows();
		if ($productRows === null)
		{
			return new Result();
		}

		foreach ($productRows as $product)
		{
			$results[] = $this->normalizeProduct($product);
		}

		return $this->mergeResults($results);
	}

	protected function normalizeProduct(ProductRow $product): Result
	{
		$product->set($this->getItemReferenceFieldNameInProduct(), $this->entityObject);
		$product->setOwnerType(\CCrmOwnerTypeAbbr::ResolveByTypeID($this->getEntityTypeId()));

		return $product->normalize(
			$this->getCurrencyId(),
			$this->hasField(Item::FIELD_NAME_EXCH_RATE) ? $this->getExchRate() : null,
		);
	}

	protected function getItemReferenceFieldNameInProduct(): ?string
	{
		return null;
	}

	/**
	 * @param EO_ProductRow_Collection|ProductRow[] $products
	 * @param ProductRow $productToFind
	 * @return bool
	 */
	protected function isSameProductInCollection($products, ProductRow $productToFind): bool
	{
		foreach ($products as $existingProduct)
		{
			if ($productToFind->isEqualTo($existingProduct))
			{
				return true;
			}
		}

		return false;
	}

	protected function saveProduct(ProductRow $product): void
	{
		$originalProduct = $this->getProductRows() ? $this->getProductRows()->getByPrimary($product->getId()) : null;
		if ($originalProduct)
		{
			$data = $product->collectValues(Values::ALL, FieldTypeMask::SCALAR);
			// can't change primary key
			unset($data['ID']);

			foreach ($data as $fieldName => $value)
			{
				$originalProduct->set($fieldName, $value);
			}

			$productReservation = $product->getProductRowReservation();
			$originalProductReservation = $originalProduct->getProductRowReservation();
			if ($productReservation && $originalProductReservation)
			{
				$data = $productReservation->collectValues(Values::CURRENT, FieldTypeMask::SCALAR);
				// can't change primary key
				unset($data['ID']);

				foreach ($data as $fieldName => $value)
				{
					$originalProductReservation->set($fieldName, $value);
				}

				$originalProductReservation->setProductRow($originalProduct);
			}
			elseif ($productReservation)
			{
				$productReservation->setProductRow($originalProduct);
				$originalProduct->setProductRowReservation($productReservation);
			}
		}
		else
		{
			$this->addToProductsCollection($product);
		}
	}

	/**
	 * @param EO_ProductRow_Collection|ProductRow[] $providedProducts
	 */
	protected function deleteNotProvidedProducts($providedProducts): void
	{
		if (!$this->getProductRows())
		{
			return;
		}

		$providedIds = [];
		foreach ($providedProducts as $providedProduct)
		{
			if ($providedProduct->getId() > 0)
			{
				$providedIds[] = $providedProduct->getId();
			}
		}

		foreach ($this->getProductRows() as $existingProduct)
		{
			$wasProvided = in_array($existingProduct->getId(), $providedIds, true);
			$wasSameProvided = $this->isSameProductInCollection($providedProducts, $existingProduct);

			if (
				!$wasProvided
				&& !$wasSameProvided
			)
			{
				$this->removeFromProductRows($existingProduct);
			}
		}
	}

	protected function addToProductsCollection(ProductRow $product): void
	{
		$entityFieldName = $this->getEntityFieldNameByMap(static::FIELD_NAME_PRODUCTS);
		$this->entityObject->addTo($entityFieldName, $product);
	}

	protected function remindActualProductRows(): ?EO_ProductRow_Collection
	{
		$products = $this->getProductRows();
		if (is_null($products))
		{
			return null;
		}

		/** @noinspection PhpParamsInspection */
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $this->getActualCollection($products);
	}

	protected function isChangedProductRows(): bool
	{
		if (is_null($this->getProductRows()))
		{
			return false;
		}

		$productRows = $this->getProductRows();

		/** @noinspection PhpParamsInspection */
		$isProductCollectionChanged = $this->isCollectionChanged($productRows);
		if ($isProductCollectionChanged)
		{
			return true;
		}

		/** @var ProductRow $productRow */
		foreach ($productRows as $productRow)
		{
			$productReservation = $productRow->getProductRowReservation();
			if ($productReservation)
			{
				$scalarFields = $productReservation->entity->getScalarFields();
				foreach ($scalarFields as $field)
				{
					if ($productReservation->isChanged($field->getName()))
					{
						return true;
					}
				}
			}
		}

		return false;
	}
	//endregion

	protected function isCollectionChanged(Collection $collection): bool
	{
		if ($collection->sysIsChanged())
		{
			//some object were added or removed
			return true;
		}

		$scalarFields = $collection->entity->getScalarFields();
		foreach ($collection as $entityObject)
		{
			foreach ($scalarFields as $field)
			{
				if ($entityObject->isChanged($field->getName()))
				{
					return true;
				}
			}
		}

		return false;
	}

	protected function getActualCollection(Collection $collection): Collection
	{
		$actualCollection = clone $collection;
		$this->resetCollection($actualCollection);

		return $actualCollection;
	}

	protected function resetCollection(Collection $collection): void
	{
		if ($collection->sysIsChanged())
		{
			$collection->sysResetChanges(true);
		}
	}

	/**
	 * @param Result|Result[] ...$results
	 *
	 * @return Result
	 */
	protected function mergeResults(...$results): Result
	{
		$mergedResult = new Result();

		foreach ($results as $result)
		{
			if (is_array($result))
			{
				$result = static::mergeResults(...$result);
			}

			if (!$result->isSuccess())
			{
				$mergedResult->addErrors($result->getErrors());
			}
		}

		return $mergedResult;
	}

	protected function loadUtm(): void
	{
		if (!$this->isUtmLoaded)
		{
			$this->isUtmLoaded = true;
			if($this->isNew())
			{
				$utmValues = array_fill_keys($this->utmTableClassName::getCodeList(), null);
			}
			else
			{
				$utmValues = $this->utmTableClassName::getEntityUtm($this->getEntityTypeId(), $this->getId());
			}

			$this->actualValues = array_merge($this->actualValues, $utmValues);
		}
	}

	public function getUtm(): array
	{
		$this->loadUtm();

		$result = [];

		foreach ($this->utmTableClassName::getCodeNames() as $commonFieldName => $title)
		{
			$result[$commonFieldName] = $this->currentValues[$commonFieldName] ?? $this->actualValues[$commonFieldName] ?? null;
		}

		return $result;
	}

	public function jsonSerialize(): array
	{
		return Container::getInstance()->getItemConverter()->toJson($this);
	}

	public function offsetExists(mixed $offset): bool
	{
		$commonFieldName = (string)$offset;

		return $this->hasField($commonFieldName) && $this->get($commonFieldName) !== null;
	}

	public function offsetGet(mixed $offset): mixed
	{
		$commonFieldName = (string)$offset;

		if($this->offsetExists($commonFieldName))
		{
			return $this->get($commonFieldName);
		}

		$entityFieldName = $this->getEntityFieldNameByMap($commonFieldName);

		return $this->entityObject->offsetGet($entityFieldName);
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
		$commonFieldName = (string)$offset;

		$this->set($commonFieldName, $value);
	}

	public function offsetUnset(mixed $offset): void
	{
		$commonFieldName = (string)$offset;

		$this->unset($commonFieldName);
	}

	protected function collectValues(array $entityFieldNames, int $valuesType = Values::ALL): array
	{
		$data = [];

		foreach ($entityFieldNames as $entityFieldName)
		{
			$commonFieldName = $this->getCommonFieldNameByMap($entityFieldName);

			if ($this->isFieldDisabled($commonFieldName))
			{
				continue;
			}

			if ($valuesType === Values::ACTUAL)
			{
				$data[$entityFieldName] = $this->remindActual($commonFieldName);
			}
			elseif ($valuesType === Values::CURRENT)
			{
				if ($this->isChanged($commonFieldName))
				{
					$data[$entityFieldName] = $this->get($commonFieldName);
				}
			}
			else
			{
				$data[$entityFieldName] = $this->get($commonFieldName);
			}
		}

		return $data;
	}

	protected function transformToExternalValue(string $entityFieldName, $fieldValue, int $valuesType = Values::ALL)
	{
		$commonFieldName = $this->getCommonFieldNameByMap($entityFieldName);
		$implementation = $this->getImplementation($commonFieldName);
		if ($implementation)
		{
			return $implementation->transformToExternalValue($commonFieldName, $fieldValue, $valuesType);
		}

		if (is_array($fieldValue))
		{
			$result = [];
			foreach ($fieldValue as $key => $singleValue)
			{
				if ($singleValue instanceof Date)
				{
					$result[$key] = $singleValue->toString();
				}
				else
				{
					$result[$key] = $singleValue;
				}
			}

			return $result;
		}
		if ($fieldValue instanceof Date)
		{
			return $fieldValue->toString();
		}

		if ($fieldValue instanceof ProductRowCollection)
		{
			return $fieldValue->toArray();
		}

		$factory = Container::getInstance()->getFactory($this->getEntityTypeId());
		if ($factory)
		{
			$field = $factory->getFieldsCollection()->getField($entityFieldName);
			if ($field)
			{
				if ($field->isValueEmpty($fieldValue))
				{
					return null;
				}
				if (is_bool($fieldValue) && $field->getType() === Field::TYPE_BOOLEAN)
				{
					return ($fieldValue === true ? 'Y' : 'N');
				}
			}
		}

		return $fieldValue;
	}

	protected function setFromExternalValue(string $commonFieldName, $value): self
	{
		$factory = Container::getInstance()->getFactory($this->getEntityTypeId());
		$field = null;
		if ($factory)
		{
			$field = $factory->getFieldsCollection()->getField($commonFieldName);
			if ($field)
			{
				if ($field->isItemValueEmpty($this) && $field->isValueEmpty($value))
				{
					return $this;
				}
			}
		}
		if ($this->isExpressionField($commonFieldName))
		{
			return $this->set($commonFieldName, $value);
		}

		$entityField = null;
		$entityFieldName = $this->getEntityFieldNameByMap($commonFieldName);
		if ($this->entityObject->sysGetEntity()->hasField($entityFieldName))
		{
			$entityField = $this->entityObject->sysGetEntity()->getField($entityFieldName);
		}

		if ($entityField instanceof ScalarField || $entityField instanceof UserTypeField)
		{
			$baseField = null;
			if ($entityField instanceof UserTypeField)
			{
				$baseField = $entityField->getValueField();
				if ($baseField instanceof UserTypeUtsMultipleField)
				{
					$baseField = $baseField->getUtmField();
				}
			}

			$isFieldInstanceOf = fn(string $type) => ($entityField instanceof $type) || ($baseField instanceof $type);

			if ($field && ($isFieldInstanceOf(BooleanField::class) || $field->getType() === Field::TYPE_BOOLEAN) && !is_null($value))
			{
				$value = $this->prepareExternalBooleanValue($value, $field);
			}

			if ($isFieldInstanceOf(DateField::class) && is_string($value) && !DateTime::isCorrect($value))
			{
				$value = null;
			}

			if ($isFieldInstanceOf(DatetimeField::class) && $field && !is_null($value))
			{
				$value = $this->prepareExternalDateTimeValue($value, $field);
			}

			if ($field && $field->isUserField() && $field->getType() === Field::TYPE_DOUBLE)
			{
				$value = $this->prepareExternalUserFieldDoubleValue($value, $field);
			}

			$this->set($commonFieldName, $value);
		}
		elseif ($commonFieldName === static::FIELD_NAME_PRODUCTS)
		{
			$this->setProductRowsFromArrays((array)$value);
		}
		elseif ($commonFieldName === static::FIELD_NAME_OBSERVERS)
		{
			$this->setObservers((array)$value);
		}

		return $this;
	}

	private function prepareExternalBooleanValue(mixed $value, Field $field): array|bool
	{
		$castSingleValue = static function ($singleValue): bool {
			if ($singleValue === 'Y' || $singleValue === 'N')
			{
				return $singleValue === 'Y';
			}

			return (bool)$singleValue;
		};

		if ($field->isMultiple())
		{
			if (!is_array($value))
			{
				$value = [$value];
			}

			return array_map($castSingleValue, $value);
		}

		return $castSingleValue($value);
	}

	private function prepareExternalDateTimeValue($value, Field $field)
	{
		if ($field->isMultiple())
		{
			$shouldTransformValue = is_array($value) && !empty(array_filter($value, 'is_string'));
		}
		else
		{
			$shouldTransformValue = is_string($value);
		}

		if (!$shouldTransformValue)
		{
			return $value;
		}

		$useTimezones = true;
		if ($field->isUserField())
		{
			$userField = $field->getUserField();
			if (isset($userField['SETTINGS']['USE_TIMEZONE']) && $userField['SETTINGS']['USE_TIMEZONE'] === 'N')
			{
				$useTimezones = false;
			}
		}

		if (!$useTimezones)
		{
			// leave it as it is. ORM will handle it just fine
			return $value;
		}

		if (is_array($value) && $field->isMultiple())
		{
			foreach ($value as &$singleValue)
			{
				if (is_string($singleValue))
				{
					$singleValue = DateTime::createFromUserTime($singleValue);
				}
			}
			unset($singleValue);
		}
		elseif (is_string($value))
		{
			$value = DateTime::createFromUserTime($value);
		}

		return $value;
	}

	private function prepareExternalUserFieldDoubleValue(mixed $value, Field $field): mixed
	{
		$castSingleValue = static function ($singleValue) use ($field): mixed {
			if (is_string($singleValue))
			{
				return \Bitrix\Main\UserField\Types\DoubleType::onBeforeSave($field->getUserField(), $singleValue);
			}

			return $singleValue;
		};

		if (!$field->isMultiple())
		{
			return $castSingleValue($value);
		}

		if (!is_array($value))
		{
			return $value;
		}

		$casted = [];
		foreach ($value as $key => $single)
		{
			$casted[$key] = $castSingleValue($single);
		}

		return $casted;
	}

	/**
	 * Return entity-dependant field names.
	 *
	 * @return array
	 */
	protected function getExternalizableFieldNames(): array
	{
		$names = [];

		if ($this->hasField(static::FIELD_NAME_PRODUCTS))
		{
			$names[] = $this->getEntityFieldNameByMap(static::FIELD_NAME_PRODUCTS);
		}
		if ($this->hasField(static::FIELD_NAME_OBSERVERS))
		{
			$names[] = $this->getEntityFieldNameByMap(static::FIELD_NAME_OBSERVERS);
		}

		$namesFromImplementations = array_map(
			[$this, 'getEntityFieldNameByMap'],
			$this->getExternalizableFieldNamesFromImplementations(),
		);

		return array_merge(
			$names,
			$namesFromImplementations,
			$this->utmTableClassName::getCodeList(),
			$this->getEntityFieldNames(
				FieldTypeMask::SCALAR
				| FieldTypeMask::USERTYPE
				| FieldTypeMask::EXPRESSION
			),
		);
	}

	protected function getInternalizableFieldNames(): array
	{
		$fieldsToExclude = array_map(
			[$this, 'getEntityFieldNameByMap'],
			$this->getExternalizableFieldNamesFromImplementations(),
		);

		// can not change primary key
		$fieldsToExclude[] = static::FIELD_NAME_ID;

		return array_diff($this->getExternalizableFieldNames(), $fieldsToExclude);
	}

	private function getExternalizableFieldNamesFromImplementations(): array
	{
		$arraysOfNames = [];
		foreach ($this->getAllImplementations() as $implementation)
		{
			$arraysOfNames[] = $implementation->getExternalizableFieldNames();
		}

		return array_merge(...$arraysOfNames);
	}

	/**
	 * If item has field associated with $commonFieldName then entity field name will be returned.
	 * If item has not such a field - return null.
	 *
	 * @param string $commonFieldName - common field name like in this class constants.
	 * @return string|null
	 */
	public function getEntityFieldNameIfExists(string $commonFieldName): ?string
	{
		if($this->hasField($commonFieldName))
		{
			return $this->getEntityFieldNameByMap($commonFieldName);
		}

		return null;
	}

	/**
	 * Returns map that describes this-entity-specific aliases for common field names.
	 * If some common field name is not present in the map, it means that this field has no entity-specific alias
	 *  and common name is used.
	 *
	 * @return array [$commonFieldName => $entityFieldName]
	 */
	final public function getFieldsMap(): array
	{
		return $this->fieldsMap;
	}

	public function isCategoriesSupported(): bool
	{
		return !$this->isFieldDisabled(static::FIELD_NAME_CATEGORY_ID);
	}

	public function isStagesEnabled(): bool
	{
		return !$this->isFieldDisabled(static::FIELD_NAME_STAGE_ID);
	}

	/**
	 * Returns a CATEGORY_ID value that is used for user permissions calculation
	 *
	 * @return int|null
	 */
	public function getCategoryIdForPermissions(): ?int
	{
		if (!$this->isCategoriesSupported())
		{
			return null;
		}

		if ($this->isNew())
		{
			return $this->getCategoryId();
		}

		return $this->remindActual(static::FIELD_NAME_CATEGORY_ID);
	}

	/**
	 * This method is subject to change and is not covered by backwards compatibility
	 *
	 * @internal
	 */
	final public function refreshCategoryDependentDisabledFields(): void
	{
		$this->disabledFieldNames = array_filter(
			$this->disabledFieldNames,
			static function (DisabledField $disabledField)
			{
				return !$disabledField->isCategoryDependent();
			}
		);

		$categoryId = $this->getCategoryId();
		if (!$categoryId)
		{
			return;
		}

		$category = null;
		$factory = Container::getInstance()->getFactory($this->getEntityTypeId());
		if ($factory)
		{
			$category = $factory->getCategory($categoryId);
		}

		if (!$category)
		{
			return;
		}

		$disabledFieldNames = $category->getDisabledFieldNames();
		foreach ($disabledFieldNames as $disabledFieldName)
		{
			$this->reset($disabledFieldName);

			$disabledField = (new DisabledField($disabledFieldName))
				->setIsCategoryDependent(true);

			$this->disabledFieldNames[] = $disabledField;
		}
	}

	/**
	 * Return event name fired by DataManager.
	 *
	 * @param string $eventName
	 * @return string
	 */
	public function getEntityEventName(string $eventName): string
	{
		return
			$this->entityObject->sysGetEntity()->getNamespace()
			. $this->entityObject->sysGetEntity()->getName()
			. '::'
			. $eventName
			;
	}

	public function getFilteredUserFields(): ?array
	{
		if (!($this->entityObject instanceof UserFieldFilterable)) // currently it allows for Company/Contacts)
		{
			return null;
		}

		return $this->entityObject->getFilteredUserFields();
	}

	protected function clearEmptyMultipleValues(string $commonFieldName, array $values): array
	{
		$result = [];

		$factory = Container::getInstance()->getFactory($this->getEntityTypeId());
		$field = $factory ? $factory->getFieldsCollection()->getField($commonFieldName) : null;
		if (!$field)
		{
			return $values;
		}

		foreach($values as $value)
		{
			if (!$field->isValueEmpty($value))
			{
				$result[] = $value;
			}
		}

		return $result;
	}

	protected function prepareNullValue(string $entityFieldName, $value)
	{
		$entityField = $this->entityObject->sysGetEntity()->getField($entityFieldName);
		if (
			$value === ''
			&& (
				$entityField instanceof IntegerField
				|| $entityField instanceof FloatField
				|| $entityField instanceof UserTypeField
			)
		)
		{
			return null;
		}

		return $value;
	}

	public function toArray(): array
	{
		return $this->jsonSerialize();
	}

	// region expression fields
	protected function fillExpressionFields(EntityObject $object): void
	{
		$data = $object->collectValues(Values::ACTUAL, FieldTypeMask::EXPRESSION);

		foreach ($data as $name => $value)
		{
			if (ParentFieldManager::isParentFieldName($name))
			{
				if ((int)$value > 0)
				{
					$this->actualValues[$name] = (int)$value;
				}
				else
				{
					$this->actualValues[$name] = null;
				}
			}
		}
	}

	private function loadExpressionField(string $commonFieldName): void
	{
		if ($this->isUtmField($commonFieldName))
		{
			$this->loadUtm();
		}
	}

	protected function isExpressionField(string $commonFieldName): bool
	{
		if ($this->isUtmField($commonFieldName))
		{
			return true;
		}

		return ParentFieldManager::isParentFieldName($commonFieldName);
	}

	protected function isUtmField(string $commonFieldName): bool
	{
		return isset($this->utmTableClassName::getCodeNames()[$commonFieldName]);
	}

	protected function getExpressionField(string $commonFieldName)
	{
		$this->loadExpressionField($commonFieldName);

		if (array_key_exists($commonFieldName, $this->currentValues))
		{
			return $this->currentValues[$commonFieldName];
		}
		else
		{
			return $this->actualValues[$commonFieldName] ?? null;
		}
	}

	protected function setExpressionField(string $commonFieldName, $value): self
	{
		$this->loadExpressionField($commonFieldName);

		if (isset($this->actualValues[$commonFieldName]) && $this->actualValues[$commonFieldName] === $value)
		{
			unset($this->currentValues[$commonFieldName]);
		}
		else
		{
			$this->currentValues[$commonFieldName] = $value;
		}

		return $this;
	}

	protected function unsetExpressionField(string $commonFieldName): self
	{
		$this->loadExpressionField($commonFieldName);

		unset(
			$this->currentValues[$commonFieldName],
			$this->actualValues[$commonFieldName],
		);

		return $this;
	}

	protected function resetExpressionField(string $commonFieldName): self
	{
		$this->loadExpressionField($commonFieldName);

		unset($this->currentValues[$commonFieldName]);

		return $this;
	}

	protected function isChangedExpressionField(string $commonFieldName): bool
	{
		$this->loadExpressionField($commonFieldName);

		if (!array_key_exists($commonFieldName, $this->currentValues))
		{
			return false;
		}

		$actualValue = $this->actualValues[$commonFieldName] ?? null;
		$currentValue = $this->currentValues[$commonFieldName];

		return $actualValue !== $currentValue;
	}

	protected function remindActualExpressionField(string $commonFieldName)
	{
		$this->loadExpressionField($commonFieldName);

		return $this->actualValues[$commonFieldName] ?? null;
	}

	protected function saveExpressionFields(bool $isNew): Result
	{
		if($isNew)
		{
			$this->utmTableClassName::addEntityUtmFromFields(
				$this->getEntityTypeId(),
				$this->getId(),
				$this->getUtm()
			);
		}
		else
		{
			$this->utmTableClassName::updateEntityUtmFromFields(
				$this->getEntityTypeId(),
				$this->getId(),
				$this->getUtm()
			);
		}

		return Container::getInstance()->getParentFieldManager()->saveItemRelations(
			$this,
			$this->currentValues,
		);
	}
	// endregion

	/**
	 * @param string $commonFieldName
	 * @return bool
	 */
	public function isFieldDisabled(string $commonFieldName): bool
	{
		return in_array(
			$commonFieldName,
			array_map(
				static function (DisabledField $disabledField)
				{
					return $disabledField->getName();
				},
				$this->disabledFieldNames
			),
			true
		);
	}

	public function getBadges(): array
	{
		return $this->badges;
	}

	public function addBadge(array $badge): void
	{
		$this->badges[] = $badge;
	}
}
