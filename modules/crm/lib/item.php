<?php

namespace Bitrix\Crm;

use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Observer\Entity\EO_Observer;
use Bitrix\Crm\Observer\Entity\EO_Observer_Collection;
use Bitrix\Crm\Observer\Entity\ObserverTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Entity\BooleanField;
use Bitrix\Main\Entity\ScalarField;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\FieldTypeMask;
use Bitrix\Main\ORM\Fields\UserTypeField;
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
 * @method Item setBegindate(bool $begindate)
 * @method Date|null getClosedate()
 * @method Item setClosedate(bool $closedate)
 * @method int|null getCompanyId()
 * @method Item setCompanyId(int $companyId)
 * @method EO_Company|null getCompany()
 * @method int|null getContactId()
 * @method string|null getStageId()
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
 */
abstract class Item implements \JsonSerializable, \ArrayAccess, Arrayable
{
	public const FIELD_NAME_ID = 'ID';
	public const FIELD_NAME_TITLE = 'TITLE';
	public const FIELD_NAME_XML_ID = 'XML_ID';
	public const FIELD_NAME_CREATED_TIME = 'CREATED_TIME';
	public const FIELD_NAME_UPDATED_TIME = 'UPDATED_TIME';
	public const FIELD_NAME_MOVED_TIME = 'MOVED_TIME';
	public const FIELD_NAME_CREATED_BY = 'CREATED_BY';
	public const FIELD_NAME_UPDATED_BY = 'UPDATED_BY';
	public const FIELD_NAME_MOVED_BY = 'MOVED_BY';
	public const FIELD_NAME_ASSIGNED = 'ASSIGNED_BY_ID';
	public const FIELD_NAME_OPENED = 'OPENED';
	public const FIELD_NAME_BEGIN_DATE = 'BEGINDATE';
	public const FIELD_NAME_CLOSE_DATE = 'CLOSEDATE';
	public const FIELD_NAME_COMPANY_ID = 'COMPANY_ID';
	public const FIELD_NAME_COMPANY = 'COMPANY';
	public const FIELD_NAME_CONTACT_ID = 'CONTACT_ID';
	public const FIELD_NAME_CONTACTS = 'CONTACTS';
	public const FIELD_NAME_CONTACT_BINDINGS = 'CONTACT_BINDINGS';
	public const FIELD_NAME_STAGE_ID = 'STAGE_ID';
	public const FIELD_NAME_PREVIOUS_STAGE_ID = 'PREVIOUS_STAGE_ID';
	public const FIELD_NAME_CATEGORY_ID = 'CATEGORY_ID';
	public const FIELD_NAME_OPPORTUNITY = 'OPPORTUNITY';
	public const FIELD_NAME_IS_MANUAL_OPPORTUNITY = 'IS_MANUAL_OPPORTUNITY';
	public const FIELD_NAME_TAX_VALUE = 'TAX_VALUE';
	public const FIELD_NAME_CURRENCY_ID = 'CURRENCY_ID';
	public const FIELD_NAME_OPPORTUNITY_ACCOUNT = 'OPPORTUNITY_ACCOUNT';
	public const FIELD_NAME_TAX_VALUE_ACCOUNT = 'TAX_VALUE_ACCOUNT';
	public const FIELD_NAME_ACCOUNT_CURRENCY_ID = 'ACCOUNT_CURRENCY_ID';
	public const FIELD_NAME_MYCOMPANY_ID = 'MYCOMPANY_ID';
	public const FIELD_NAME_MYCOMPANY = 'MYCOMPANY';
	public const FIELD_NAME_PRODUCTS = 'PRODUCT_ROWS';
	public const FIELD_NAME_CLOSED = 'CLOSED';
	public const FIELD_NAME_SOURCE_ID = 'SOURCE_ID';
	public const FIELD_NAME_SOURCE_DESCRIPTION = 'SOURCE_DESCRIPTION';
	public const FIELD_NAME_OBSERVERS = 'OBSERVERS';
	public const FIELD_NAME_WEBFORM_ID = 'WEBFORM_ID';
	public const FIELD_NAME_LOCATION_ID = 'LOCATION_ID';

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
	protected $fieldsMap = [];
	protected $contacts = [];
	protected $actualValues = [];
	protected $currentValues = [];
	protected $disabledFieldNames = [];

	public function __construct(
		int $entityTypeId,
		EntityObject $entityObject,
		array $fieldsMap = [],
		array $disabledFieldNames = []
	)
	{
		$this->entityTypeId = $entityTypeId;
		$this->entityObject = $entityObject;
		$this->fieldsMap = $fieldsMap;
		$this->primary = $entityObject->primary;
		$this->disabledFieldNames = $disabledFieldNames;
	}

	public function __clone()
	{
		$this->entityObject = clone $this->entityObject;
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
		$entityFieldName = $this->transformToEntityFieldName($calledFieldName);

		if ($method === 'get')
		{
			return $this->get($entityFieldName);
		}
		if ($method === 'set')
		{
			return $this->set($entityFieldName, ...$arguments);
		}
		$method = mb_substr($name, 0, 9);
		$calledFieldName = mb_substr($name, 9);
		$entityFieldName = $this->transformToEntityFieldName($calledFieldName);
		if ($method === 'isChanged')
		{
			return $this->isChanged($entityFieldName);
		}

		throw new SystemException(sprintf(
			'Unknown method `%s` for object `%s`', $name, static::class
		));
	}

	protected function transformToEntityFieldName(string $calledFieldName): string
	{
		$snake = StringHelper::camel2snake($calledFieldName);

		return StringHelper::strtoupper($snake);
	}

	protected function transformToCalledFieldName(string $entityFieldName): string
	{
		return StringHelper::snake2camel($entityFieldName);
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

	public function hasField(string $fieldName): bool
	{
		$customMethod = $this->getCustomMethodNameIfExists('has', $fieldName);
		if ($customMethod)
		{
			return $this->$customMethod();
		}

		return $this->hasFieldInEntityObject($fieldName);
	}

	protected function hasFieldInEntityObject(string $commonFieldName): bool
	{
		$fieldName = $this->getEntityFieldNameByMap($commonFieldName);

		return ($this->entityObject->sysGetEntity()->hasField($fieldName));
	}

	public function getDefaultValue(string $fieldName)
	{
		if($this->hasFieldInEntityObject($fieldName))
		{
			$fieldName = $this->getEntityFieldNameByMap($fieldName);

			$field = $this->entityObject->sysGetEntity()->getField($fieldName);
			if($field instanceof ScalarField)
			{
				return $field->getDefaultValue();
			}
		}

		return null;
	}

	public function get(string $fieldName)
	{
		$customMethod = $this->getCustomMethodNameIfExists('get', $fieldName);
		if ($customMethod)
		{
			return $this->$customMethod();
		}

		$fieldName = $this->getEntityFieldNameByMap($fieldName);

		return $this->entityObject->get($fieldName);
	}

	public function set(string $fieldName, $value): self
	{
		if (is_array($value))
		{
			$value = $this->clearEmptyMultipleValues($value);
		}
		$customMethod = $this->getCustomMethodNameIfExists('set', $fieldName);
		if ($customMethod)
		{
			return $this->$customMethod($value);
		}

		$fieldName = $this->getEntityFieldNameByMap($fieldName);

		$this->entityObject->set($fieldName, $value);

		return $this;
	}

	public function reset(string $fieldName): self
	{
		$customMethod = $this->getCustomMethodNameIfExists('reset', $fieldName);
		if ($customMethod)
		{
			return $this->$customMethod();
		}

		$fieldName = $this->getEntityFieldNameByMap($fieldName);

		$this->entityObject->reset($fieldName);

		return $this;
	}

	public function unset(string $fieldName): self
	{
		$customMethod = $this->getCustomMethodNameIfExists('unset', $fieldName);
		if($customMethod)
		{
			$this->$customMethod($fieldName);
		}

		$fieldName = $this->getEntityFieldNameByMap($fieldName);
		$this->entityObject->sysUnset($fieldName);

		return $this;
	}

	public function remindActual(string $fieldName)
	{
		$customMethod = $this->getCustomMethodNameIfExists('remindActual', $fieldName);
		if ($customMethod)
		{
			return $this->$customMethod();
		}

		$fieldName = $this->getEntityFieldNameByMap($fieldName);

		return $this->entityObject->remindActual($fieldName);
	}

	public function isChanged(string $fieldName): bool
	{
		$customMethod = $this->getCustomMethodNameIfExists('isChanged', $fieldName);
		if ($customMethod)
		{
			return $this->$customMethod();
		}

		$fieldName = $this->getEntityFieldNameByMap($fieldName);

		return $this->entityObject->isChanged($fieldName);
	}

	protected function getCustomMethodNameIfExists(string $methodPrefix, string $fieldName): ?string
	{
		$customMethod = $methodPrefix.$this->transformToCalledFieldName($fieldName);
		if (method_exists($this, $customMethod))
		{
			return $customMethod;
		}

		return null;
	}

	public function getData(int $valuesType = Values::ALL): array
	{
		//todo temporary decision to avoid error on jsonSerialization of EntityObjects
		$fieldTypeMask = FieldTypeMask::SCALAR|FieldTypeMask::USERTYPE;

		$entityFieldNames = array_merge($this->getEntityFieldNames($fieldTypeMask), $this->utmTableClassName::getCodeList());
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
			$externalData[$entityFieldName] = $this->transformToExternalValue($entityFieldName, $value);
		}

		return $externalData;
	}

	public function setFromCompatibleData(array $data): self
	{
		$isContactBindingsPassed = array_key_exists(static::FIELD_NAME_CONTACT_BINDINGS, $data);
		if ($isContactBindingsPassed)
		{
			if (empty($data[static::FIELD_NAME_CONTACT_BINDINGS]))
			{
				$this->unbindContacts($this->getContactBindings());
			}
		}
		else
		{
			if(isset($data['CONTACT_IDS']) && is_array($data['CONTACT_IDS']))
			{
				$data[static::FIELD_NAME_CONTACT_BINDINGS] = EntityBinding::prepareEntityBindings(
					\CCrmOwnerType::Contact,
					$data['CONTACT_IDS']
				);
			}
			elseif (isset($data['CONTACT_ID']) && $data['CONTACT_ID'] > 0)
			{
				$data[static::FIELD_NAME_CONTACT_BINDINGS] = EntityBinding::prepareEntityBindings(
					\CCrmOwnerType::Contact,
					[$data['CONTACT_ID']]
				);
			}
		}

		$fieldNames = $this->getInternalizableFieldNames();
		foreach ($fieldNames as $fieldName)
		{
			if(array_key_exists($fieldName, $data))
			{
				$this->setFromExternalValue($fieldName, $data[$fieldName]);
			}
			elseif($this->isNew())
			{
				$defaultValue = $this->getDefaultValue($fieldName);
				if ($defaultValue !== null)
				{
					$this->setFromExternalValue($fieldName, $defaultValue);
				}
			}
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

	protected function getEntityName(): string
	{
		return \CCrmOwnerType::ResolveName($this->getEntityTypeId());
	}

	protected function getEntityAbbreviation(): string
	{
		return \CCrmOwnerTypeAbbr::ResolveByTypeID($this->getEntityTypeId());
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
		$result = $this->entityObject->save();
		if($result->isSuccess())
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
			$this->actualValues = $this->getUtm();
			$this->currentValues = [];

			$factory = Container::getInstance()->getFactory($this->getEntityTypeId());
			if ($factory)
			{
				foreach ($factory->getFieldsCollection() as $field)
				{
					if ($field->isFileUserField())
					{
						$files = (array)$this->get($field->getName());
						foreach($files as $fileId)
						{
							Container::getInstance()->getFileUploader()->markFileAsPersistent((int)$fileId);
						}
					}
				}
			}
		}

		return $result;
	}

	protected function disableCheckUserFields(): void
	{

	}

	public function delete(): Result
	{
		return $this->entityObject->delete();
	}

	// region Contacts
	public function getPrimaryContact(): ?Contact
	{
		$firstContact = null;
		foreach ($this->getContacts() as $contact)
		{
			if (!$firstContact)
			{
				$firstContact = $contact;
			}
			if ($contact->getId() === $this->getContactId())
			{
				$primaryContact = $contact;
			}
		}

		return $primaryContact ?? $firstContact;
	}

	/**
	 * @return Contact[]
	 */
	public function getContacts(): array
	{
		if (!empty($this->contacts))
		{
			return $this->contacts;
		}

		$bindings = $this->getContactBindingsCollection();
		if (is_null($bindings))
		{
			return [];
		}

		$this->contacts = $this->loadContacts($bindings);

		return $this->contacts;
	}

	protected function hasContacts(): bool
	{
		return $this->hasField(static::FIELD_NAME_CONTACT_BINDINGS);
	}

	protected function isChangedContacts(): bool
	{
		return $this->isChangedContactBindings();
	}

	protected function remindActualContacts(): array
	{
		$actualBindings = $this->remindActualContactBindingsCollection();
		if (is_null($actualBindings))
		{
			return [];
		}

		return $this->loadContacts($actualBindings);
	}

	protected function resetContacts(): Item
	{
		$this->contacts = $this->remindActualContacts();

		return $this;
	}

	protected function unsetContacts(): Item
	{
		$this->clearContactsCache();

		return $this;
	}

	protected function loadContacts(Collection $bindingObjects): array
	{
		$contactIds = [];
		foreach ($bindingObjects as $bindingObject)
		{
			$contactIds[] = $bindingObject->getContactId();
		}

		return Container::getInstance()->getContactBroker()->getBunchByIds($contactIds);
	}

	protected function clearContactsCache(): void
	{
		$this->contacts = [];
	}

	/**
	 * Bind multiple contacts to the item.
	 *
	 * @param array $contactBindings Array of bindings.
	 * Binding array format is specified in \Bitrix\Crm\Binding\EntityBinding
	 *
	 * @return void
	 * @throws SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @see \Bitrix\Crm\Binding\EntityBinding
	 */
	public function bindContacts(array $contactBindings): void
	{
		if(empty($contactBindings))
		{
			return;
		}

		$normalizedContactBindings = $contactBindings;
		EntityBinding::normalizeEntityBindings(\CCrmOwnerType::Contact, $normalizedContactBindings);
		EntityBinding::removeBindingsWithDuplicatingEntityIDs(
			\CCrmOwnerType::Contact,
			$normalizedContactBindings
		);

		$existingBindings = $this->getContactBindingsCollection();

		foreach ($normalizedContactBindings as $contactBinding)
		{
			if (!is_null($existingBindings))
			{
				$existingBinding = $this->findBindingInCollection($existingBindings, $contactBinding);
			}
			$bindingObject = $this->createOrUpdateBindingObject(
				$contactBinding,
				$existingBinding ?? null
			);
			$this->addToContactBindingsCollection($bindingObject);
		}

		$primaryBinding = $this->ensureExactlyOnePrimaryBindingExists(
			$this->getContactBindingsCollection(),
			$contactBindings
		);
		$this->saveContactId($primaryBinding->getContactId());

		$this->clearContactsCache();
	}

	abstract protected function compilePrimaryForBinding(array $contactBinding): array;

	protected function createOrUpdateBindingObject(
		array $contactBinding,
		EntityObject $existingBinding = null
	): EntityObject
	{
		if (is_null($existingBinding))
		{
			$primary = $this->compilePrimaryForBinding($contactBinding);
			$existingBinding = $this->getContactBindingTableClassName()::createObject($primary);
		}

		foreach ($this->getNotPrimaryBindingFields() as $fieldName)
		{
			if (isset($contactBinding[$fieldName]))
			{
				$existingBinding->set($fieldName, $contactBinding[$fieldName]);
			}
		}

		return $existingBinding;
	}

	protected function getNotPrimaryBindingFields(): array
	{
		$fieldNames = [];
		$fields = $this->getContactBindingTableClassName()::getEntity()->getScalarFields();
		foreach ($fields as $field)
		{
			if (!$field->isPrimary())
			{
				$fieldNames[] = $field->getName();
			}
		}

		return $fieldNames;
	}

	/**
	 * @return string|DataManager
	 */
	abstract protected function getContactBindingTableClassName(): string;

	/**
	 * Unbind specified contacts from the item.
	 *
	 * @param array $contactBindings Array of bindings.
	 * Binding array format is specified in \Bitrix\Crm\Binding\EntityBinding
	 *
	 * @return void
	 * @throws SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @see \Bitrix\Crm\Binding\EntityBinding
	 */
	public function unbindContacts(array $contactBindings): void
	{
		EntityBinding::normalizeEntityBindings(\CCrmOwnerType::Contact, $contactBindings);
		EntityBinding::removeBindingsWithDuplicatingEntityIDs(\CCrmOwnerType::Contact, $contactBindings);
		if (empty($contactBindings))
		{
			return;
		}

		$bindingsCollection = $this->getContactBindingsCollection();
		if (is_null($bindingsCollection))
		{
			return;
		}

		foreach ($contactBindings as $contactBinding)
		{
			$bindingObject = $this->findBindingInCollection($bindingsCollection, $contactBinding);
			if (!is_null($bindingObject))
			{
				$this->removeFromContactBindingsCollection($bindingObject);
			}
		}

		if (count($bindingsCollection) === 0)
		{
			$this->saveContactId(0);
		}
		else
		{
			$primaryBinging = $this->ensureExactlyOnePrimaryBindingExists($bindingsCollection);
			$this->saveContactId($primaryBinging->getContactId());
		}

		$this->clearContactsCache();
	}

	public function getContactBindings(): array
	{
		return $this->bindingsCollectionToArray($this->getContactBindingsCollection(), 'get');
	}

	protected function isChangedContactBindings(): bool
	{
		if (is_null($this->getContactBindingsCollection()))
		{
			return false;
		}

		return $this->isCollectionChanged($this->getContactBindingsCollection());
	}

	protected function remindActualContactBindings(): array
	{
		return $this->bindingsCollectionToArray($this->remindActualContactBindingsCollection(), 'remindActual');
	}

	protected function bindingsCollectionToArray(?Collection $collection, string $method): array
	{
		if (!$collection)
		{
			return [];
		}

		$bindings = [];
		foreach ($collection as $bindingObject)
		{
			$bindings[] = [
				EntityBinding::resolveEntityFieldName(\CCrmOwnerType::Contact) => $bindingObject->$method('CONTACT_ID'),
				'SORT' => $bindingObject->$method('SORT'),
				'ROLE_ID' => $bindingObject->$method('ROLE_ID'),
			];
		}

		foreach ($collection as $bindingObject)
		{
			if ($bindingObject->$method('IS_PRIMARY'))
			{
				$primary = $bindingObject;
			}
		}

		if (isset($primary))
		{
			EntityBinding::markAsPrimary(
				$bindings,
				\CCrmOwnerType::Contact,
				$primary->$method('CONTACT_ID')
			);
		}

		return $bindings;
	}

	protected function resetContactBindings(): Item
	{
		$collection = $this->getContactBindingsCollection();
		if (!$collection)
		{
			return $this;
		}

		$this->resetCollection($collection);

		foreach ($collection as $bindingObject)
		{
			foreach ($this->getNotPrimaryBindingFields() as $fieldName)
			{
				$bindingObject->reset($fieldName);
			}
		}

		return $this;
	}

	protected function getContactBindingsCollection(): ?Collection
	{
		return $this->entityObject->get(static::FIELD_NAME_CONTACT_BINDINGS);
	}

	protected function addToContactBindingsCollection(EntityObject $contactBinding): void
	{
		$this->entityObject->addTo(static::FIELD_NAME_CONTACT_BINDINGS, $contactBinding);
	}

	protected function removeFromContactBindingsCollection(EntityObject $contactBinding): void
	{
		$this->entityObject->removeFrom(static::FIELD_NAME_CONTACT_BINDINGS, $contactBinding);
	}

	protected function remindActualContactBindingsCollection(): ?Collection
	{
		$bindings = $this->getContactBindingsCollection();
		if (is_null($bindings))
		{
			return null;
		}

		return $this->getActualCollection($this->getContactBindingsCollection());
	}

	protected function ensureExactlyOnePrimaryBindingExists(
		Collection $existingBindings,
		array $providedBindings = []
	): EntityObject
	{
		$currentPrimaries = [];
		$allPrimaries = [];
		foreach ($existingBindings as $binding)
		{
			if ($binding->getIsPrimary())
			{
				if ($binding->remindActualIsPrimary())
				{
					$currentPrimaries[] = $binding;
				}

				$allPrimaries[] = $binding;
			}
		}

		if (count($allPrimaries) === 1)
		{
			return array_pop($allPrimaries);
		}

		// If there are multiple primaries - prioritize one, that was explicitly set in the provided bindings
		$explicitlySetPrimary = EntityBinding::findPrimaryBinding($providedBindings);
		if (is_array($explicitlySetPrimary))
		{
			$newPrimaryBinding = $this->findBindingInCollection($existingBindings, $explicitlySetPrimary);
			if (!$newPrimaryBinding)
			{
				throw new ObjectNotFoundException("Can't find primary binding in collection");
			}
		}
		// If primary is not set explicitly and there is a current primary, prioritize this one
		elseif (count($currentPrimaries) === 1)
		{
			$newPrimaryBinding = array_pop($currentPrimaries);
		}
		else
		{
			//Make the first one primary
			$existingBindingsArray = $existingBindings->getAll();
			$newPrimaryBinding = array_shift($existingBindingsArray);
		}

		// There should be only one primary binding
		foreach ($allPrimaries as $existingPrimaryBinding)
		{
			$existingPrimaryBinding->setIsPrimary(false);
		}

		$newPrimaryBinding->setIsPrimary(true);

		return $newPrimaryBinding;
	}

	/**
	 * @param Collection|EntityObject[] $bindingsCollection
	 * @param array $contactBinding
	 *
	 * @return EntityObject|null
	 */
	protected function findBindingInCollection($bindingsCollection, array $contactBinding): ?EntityObject
	{
		$contactId = EntityBinding::prepareEntityID(\CCrmOwnerType::Contact, $contactBinding);
		foreach ($bindingsCollection as $bindingObject)
		{
			if ($bindingObject->getContactId() === $contactId)
			{
				return $bindingObject;
			}
		}

		return null;
	}

	public function setContactId(?int $contactId): self
	{
		$primaryContact = $this->getPrimaryContact();

		$isUnbindPrimaryContact = ($contactId <= 0);
		if ($primaryContact && $isUnbindPrimaryContact)
		{
			$primaryContactBinding = EntityBinding::prepareEntityBindings(
				\CCrmOwnerType::Contact,
				[$primaryContact->getId()]
			);
			$this->unbindContacts($primaryContactBinding);
		}
		elseif (!$isUnbindPrimaryContact)
		{
			$contacts = EntityBinding::prepareEntityBindings(
				\CCrmOwnerType::Contact,
				[$contactId]
			);
			$this->bindContacts($contacts);
		}

		return $this;
	}

	protected function saveContactId(int $contactId): self
	{
		$fieldName = $this->getEntityFieldNameByMap(static::FIELD_NAME_CONTACT_ID);

		$this->entityObject->set($fieldName, $contactId);

		return $this;
	}
	// endregion

	//region Observers
	/**
	 * @return int[]
	 * @throws SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function getObservers(): array
	{
		/** @var EO_Observer_Collection|null $collection */
		$collection = $this->entityObject->get(static::FIELD_NAME_OBSERVERS);
		return ($collection ? $collection->getUserIdList() : []);
	}

	/**
	 * @param int[] $observerIds
	 *
	 * @return Item
	 * @throws SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function setObservers($observerIds): Item
	{
		$observerIds = $this->normalizeObserverIds($observerIds);

		/** @var EO_Observer[] $found */
		/** @var EO_Observer[] $removed */
		['found' => $found, 'removed' => $removed] = $this->separateFoundAndRemovedObservers($observerIds);

		$sortIndex = 0;
		foreach ($observerIds as $observerId)
		{
			$observer = $found[$observerId] ?? null;
			if (!$observer)
			{
				// This observer is entirely new and wasn't added previously
				$observer = $this->createObserver($observerId);
				$this->entityObject->addTo(static::FIELD_NAME_OBSERVERS, $observer);
			}

			$observer->setLastUpdatedTime(new DateTime());

			$sort = static::SORT_OFFSET + $sortIndex*static::SORT_OFFSET;
			$observer->setSort($sort);
			$sortIndex++;
		}

		foreach ($removed as $removedObserver)
		{
			$this->entityObject->removeFrom(static::FIELD_NAME_OBSERVERS, $removedObserver);
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
		/** @var EO_Observer_Collection|null $actualCollection */
		$collection = $this->entityObject->get(static::FIELD_NAME_OBSERVERS);
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
	 * @throws SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function separateFoundAndRemovedObservers(array $observerIds): array
	{
		/** @var EO_Observer_Collection|null $collection */
		$collection = $this->entityObject->get(static::FIELD_NAME_OBSERVERS);
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
	 * Unbind an existing product from this item
	 *
	 * @param ProductRow $product
	 */
	public function removeFromProductRows(ProductRow $product): void
	{
		$fieldName = $this->getEntityFieldNameByMap(static::FIELD_NAME_PRODUCTS);
		$this->entityObject->removeFrom($fieldName, $product);
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
			if ($normalizationResult->isSuccess())
			{
				$this->saveProduct($product);
			}
		}

		$this->deleteNotProvidedProducts($products);

		return $this->mergeResults($results);
	}

	protected function normalizeProduct(ProductRow $product): Result
	{
		$this->normalizeProductLinkToItem($product);

		return $product->normalize($this->getCurrencyId());
	}

	protected function normalizeProductLinkToItem(ProductRow $product): void
	{
		$product->set($this->getItemReferenceFieldNameInProduct(), $this->entityObject);
		$product->setOwnerType($this->getEntityAbbreviation());
	}

	protected function getItemReferenceFieldNameInProduct(): ?string
	{
		return null;
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
		}
		else
		{
			$this->addToProductsCollection($product);
		}
	}

	/**
	 * @param EO_ProductRow_Collection|ProductRow[] $products
	 */
	protected function deleteNotProvidedProducts($products): void
	{
		if (!$this->getProductRows())
		{
			return;
		}

		$rowIds = [];
		foreach ($products as $product)
		{
			$rowIds[] = (int)$product->getId();
		}

		foreach ($this->getProductRows() as $existingProduct)
		{
			if (!$existingProduct->isNew() && !in_array($existingProduct->getId(), $rowIds, true))
			{
				$this->removeFromProductRows($existingProduct);
			}
		}
	}

	protected function addToProductsCollection(ProductRow $product): void
	{
		$fieldName = $this->getEntityFieldNameByMap(static::FIELD_NAME_PRODUCTS);
		$this->entityObject->addTo($fieldName, $product);
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

		/** @noinspection PhpParamsInspection */
		return $this->isCollectionChanged($this->getProductRows());
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
		if(!array_key_exists($this->utmTableClassName::ENUM_CODE_UTM_SOURCE, $this->actualValues))
		{
			if($this->isNew())
			{
				$this->actualValues = array_fill_keys($this->utmTableClassName::getCodeList(), null);
			}
			else
			{
				$this->actualValues = $this->utmTableClassName::getEntityUtm($this->getEntityTypeId(), $this->getId());
			}
		}
	}

	public function getUtm(): array
	{
		$this->loadUtm();

		return array_merge($this->actualValues, $this->currentValues);
	}

	public function jsonSerialize(): array
	{
		return Container::getInstance()->getItemConverter()->toJson($this);
	}

	public function offsetExists($offset): bool
	{
		return $this->hasField($offset) && $this->get($offset) !== null;
	}

	public function offsetGet($offset)
	{
		if($this->offsetExists($offset))
		{
			return $this->get($offset);
		}

		$fieldName = $this->getEntityFieldNameByMap($offset);

		return $this->entityObject->offsetGet($fieldName);
	}

	public function offsetSet($offset, $value): void
	{
		$this->set($offset, $value);
	}

	public function offsetUnset($offset): void
	{
		$this->unset($offset);
	}

	protected function collectValues(array $entityFieldNames, int $valuesType = Values::ALL): array
	{
		$data = [];

		foreach ($entityFieldNames as $entityFieldName)
		{
			$commonFieldName = $this->getCommonFieldNameByMap($entityFieldName);

			if ($valuesType === Values::ACTUAL)
			{
				$data[$entityFieldName] = $this->remindActual($commonFieldName);
			}
			elseif ($valuesType === Values::CURRENT)
			{
				if ($this->isChanged($entityFieldName))
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

	protected function transformToExternalValue(string $entityFieldName, $fieldValue)
	{
		if(is_bool($fieldValue))
		{
			return ($fieldValue === true ? 'Y' : 'N');
		}

		if($fieldValue instanceof Date)
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
			if ($field && $field->isValueEmpty($fieldValue))
			{
				return null;
			}
		}

		return $fieldValue;
	}

	protected function setFromExternalValue(string $fieldName, $value): self
	{
		$factory = Container::getInstance()->getFactory($this->getEntityTypeId());
		if ($factory)
		{
			$field = $factory->getFieldsCollection()->getField($fieldName);
			if ($field)
			{
				if ($field->isItemValueEmpty($this) && $field->isValueEmpty($value))
				{
					return $this;
				}
				if (is_array($value) && $field->isFileUserField())
				{
					if ($field->isMultiple())
					{
						$files = [];
						foreach ($value as $singleValue)
						{
							if (is_int($singleValue))
							{
								$files[] = $singleValue;
								continue;
							}
							if (is_array($singleValue))
							{
								$fileId = Container::getInstance()->getFileUploader()->saveFileTemporary(
									$field,
									$singleValue
								);
								if ($fileId > 0)
								{
									$files[] = $fileId;
								}
							}
						}
						$value = $files;
					}
					else
					{
						$value = Container::getInstance()->getFileUploader()->saveFileTemporary($field, $value);
					}
				}
			}
		}
		if ($this->isUtmField($fieldName))
		{
			return $this->set($fieldName, $value);
		}
		$entityField = $this->entityObject->sysGetEntity()->getField($fieldName);
		if ($entityField instanceof ScalarField || $entityField instanceof UserTypeField)
		{
			if($entityField instanceof BooleanField && !is_bool($value))
			{
				$value = ($value === 'Y');
			}

			$this->set($fieldName, $value);
		}
		elseif ($fieldName === static::FIELD_NAME_CONTACT_BINDINGS && is_array($value))
		{
			$added = [];
			$removed = [];

			EntityBinding::prepareBindingChanges(
				\CCrmOwnerType::Contact,
				$this->getContactBindings(),
				$value,
				$added,
				$removed
			);

			$this->bindContacts($added);
			$this->unbindContacts($removed);
		}
		elseif ($fieldName === static::FIELD_NAME_PRODUCTS)
		{
			$this->setProductRowsFromArrays((array)$value);
		}

		return $this;
	}

	/**
	 * Return entity-dependant field names.
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function getExternalizableFieldNames(): array
	{
		$names = [
			Item::FIELD_NAME_CONTACT_BINDINGS,
			Item::FIELD_NAME_PRODUCTS,
		];
		$names = array_merge(
			$names,
			$this->utmTableClassName::getCodeList(),
			$this->getEntityFieldNames(FieldTypeMask::SCALAR|FieldTypeMask::USERTYPE)
		);

		$names = array_diff($names, $this->disabledFieldNames);

		return $names;
	}

	/**
	 * Return entity-dependant field names.
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function getInternalizableFieldNames(): array
	{
		$names = $this->getExternalizableFieldNames();

		return array_diff($names, [
				static::FIELD_NAME_ID,
				static::FIELD_NAME_CONTACT_ID,
			]
		);
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

	protected function isUtmField(string $fieldName): bool
	{
		return in_array($fieldName, $this->utmTableClassName::getCodeList(), true);
	}

	public function isCategoriesSupported(): bool
	{
		return (!in_array(static::FIELD_NAME_CATEGORY_ID, $this->disabledFieldNames, true));
	}

	public function isStagesEnabled(): bool
	{
		return (!in_array(static::FIELD_NAME_STAGE_ID, $this->disabledFieldNames, true));
	}

	/**
	 * Returns a CATEGORY_ID value that is used for user permissions calculation
	 *
	 * @return int
	 */
	public function getCategoryIdForPermissions(): int
	{
		if (!$this->isCategoriesSupported())
		{
			return 0;
		}

		if ($this->isNew())
		{
			return (int)$this->getCategoryId();
		}

		return (int)$this->remindActual(static::FIELD_NAME_CATEGORY_ID);
	}

	/**
	 * Return event name fired by DataManager.
	 *
	 * @param string $eventName
	 * @return string
	 */
	public function getEntityEventName(string $eventName): string
	{
		return $this->entityObject->sysGetEntity()->getNamespace() . $this->entityObject->sysGetEntity()->getName() . '::';
	}

	protected function clearEmptyMultipleValues(array $values): array
	{
		$result = [];

		foreach($values as $value)
		{
			if (!empty($value))
			{
				$result[] = $value;
			}
		}

		return $result;
	}

	//region custom utm methods
	public function hasUtmSource(): bool
	{
		return false;
	}

	public function hasUtmMedium(): bool
	{
		return false;
	}

	public function hasUtmCampaign(): bool
	{
		return false;
	}

	public function hasUtmContent(): bool
	{
		return false;
	}

	public function hasUtmTerm(): bool
	{
		return false;
	}

	public function getUtmSource(): ?string
	{
		return $this->getUtm()[UtmTable::ENUM_CODE_UTM_SOURCE] ?? null;
	}

	public function getUtmMedium(): ?string
	{
		return $this->getUtm()[UtmTable::ENUM_CODE_UTM_MEDIUM] ?? null;
	}

	public function getUtmCampaign(): ?string
	{
		return $this->getUtm()[UtmTable::ENUM_CODE_UTM_CAMPAIGN] ?? null;
	}

	public function getUtmContent(): ?string
	{
		return $this->getUtm()[UtmTable::ENUM_CODE_UTM_CONTENT] ?? null;
	}

	public function getUtmTerm(): ?string
	{
		return $this->getUtm()[UtmTable::ENUM_CODE_UTM_TERM] ?? null;
	}

	public function setUtmSource(?string $utmSource): self
	{
		$this->currentValues[UtmTable::ENUM_CODE_UTM_SOURCE] = $utmSource;
		return $this;
	}

	public function setUtmMedium(?string $utmMedium): self
	{
		$this->currentValues[UtmTable::ENUM_CODE_UTM_MEDIUM] = $utmMedium;
		return $this;
	}

	public function setUtmCampaign(?string $utmCampaign): self
	{
		$this->currentValues[UtmTable::ENUM_CODE_UTM_CAMPAIGN] = $utmCampaign;
		return $this;
	}

	public function setUtmContent(?string $utmContent): self
	{
		$this->currentValues[UtmTable::ENUM_CODE_UTM_CONTENT] = $utmContent;
		return $this;
	}

	public function setUtmTerm(?string $utmTerm): self
	{
		$this->currentValues[UtmTable::ENUM_CODE_UTM_TERM] = $utmTerm;
		return $this;
	}

	public function resetUtmSource(): self
	{
		unset($this->currentValues[UtmTable::ENUM_CODE_UTM_SOURCE]);
		return $this;
	}

	public function resetUtmMedium(): self
	{
		unset($this->currentValues[UtmTable::ENUM_CODE_UTM_MEDIUM]);
		return $this;
	}

	public function resetUtmCampaign(): self
	{
		unset($this->currentValues[UtmTable::ENUM_CODE_UTM_CAMPAIGN]);
		return $this;
	}

	public function resetUtmContent(): self
	{
		unset($this->currentValues[UtmTable::ENUM_CODE_UTM_CONTENT]);
		return $this;
	}

	public function resetUtmTerm(): self
	{
		unset($this->currentValues[UtmTable::ENUM_CODE_UTM_TERM]);
		return $this;
	}

	public function unsetUtmSource(): self
	{
		unset($this->currentValues[UtmTable::ENUM_CODE_UTM_SOURCE]);
		unset($this->actualValues[UtmTable::ENUM_CODE_UTM_SOURCE]);
		return $this;
	}

	public function unsetUtmMedium(): self
	{
		unset($this->currentValues[UtmTable::ENUM_CODE_UTM_MEDIUM]);
		unset($this->actualValues[UtmTable::ENUM_CODE_UTM_MEDIUM]);
		return $this;
	}

	public function unsetUtmCampaign(): self
	{
		unset($this->currentValues[UtmTable::ENUM_CODE_UTM_CAMPAIGN]);
		unset($this->actualValues[UtmTable::ENUM_CODE_UTM_CAMPAIGN]);
		return $this;
	}

	public function unsetUtmContent(): self
	{
		unset($this->currentValues[UtmTable::ENUM_CODE_UTM_CONTENT]);
		unset($this->actualValues[UtmTable::ENUM_CODE_UTM_CONTENT]);
		return $this;
	}

	public function unsetUtmTerm(): self
	{
		unset($this->currentValues[UtmTable::ENUM_CODE_UTM_TERM]);
		unset($this->actualValues[UtmTable::ENUM_CODE_UTM_TERM]);
		return $this;
	}

	public function remindActualUtmSource(): ?string
	{
		return $this->actualValues[UtmTable::ENUM_CODE_UTM_SOURCE] ?? null;
	}

	public function remindActualUtmMedium(): ?string
	{
		return $this->actualValues[UtmTable::ENUM_CODE_UTM_MEDIUM] ?? null;
	}

	public function remindActualUtmCampaign(): ?string
	{
		return $this->actualValues[UtmTable::ENUM_CODE_UTM_CAMPAIGN] ?? null;
	}

	public function remindActualUtmContent(): ?string
	{
		return $this->actualValues[UtmTable::ENUM_CODE_UTM_CONTENT] ?? null;
	}

	public function remindActualUtmTerm(): ?string
	{
		return $this->actualValues[UtmTable::ENUM_CODE_UTM_TERM] ?? null;
	}

	public function isChangedUtmSource(): bool
	{
		return (array_key_exists(UtmTable::ENUM_CODE_UTM_SOURCE, $this->currentValues));
	}

	public function isChangedUtmMedium(): bool
	{
		return (array_key_exists(UtmTable::ENUM_CODE_UTM_MEDIUM, $this->currentValues));
	}

	public function isChangedUtmCampaign(): bool
	{
		return (array_key_exists(UtmTable::ENUM_CODE_UTM_CAMPAIGN, $this->currentValues));
	}

	public function isChangedUtmContent(): bool
	{
		return (array_key_exists(UtmTable::ENUM_CODE_UTM_CONTENT, $this->currentValues));
	}

	public function isChangedUtmTerm(): bool
	{
		return (array_key_exists(UtmTable::ENUM_CODE_UTM_TERM, $this->currentValues));
	}

	public function toArray(): array
	{
		return $this->jsonSerialize();
	}
	//endregion
}
