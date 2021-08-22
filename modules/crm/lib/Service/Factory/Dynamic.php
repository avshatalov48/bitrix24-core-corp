<?php

namespace Bitrix\Crm\Service\Factory;

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Category\Entity\ItemCategory;
use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Model\Dynamic\PrototypeItem;
use Bitrix\Crm\Model\Dynamic\PrototypeItemIndex;
use Bitrix\Crm\Model\Dynamic\Type;
use Bitrix\Crm\Model\ItemCategoryTable;
use Bitrix\Crm\Service;
use Bitrix\Crm\Service\EventHistory\TrackedObject;
use Bitrix\Crm\StatusTable;
use Bitrix\Main\DI\ServiceLocator;

class Dynamic extends Service\Factory
{
	protected $itemClassName = Item\Dynamic::class;

	/** @var Type */
	protected $type;

	public function __construct(Type $type)
	{
		$this->type = $type;
	}

	public function getType(): Type
	{
		return $this->type;
	}

	/**
	 * @return PrototypeItem|string
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	public function getDataClass(): string
	{
		return ServiceLocator::getInstance()->get('crm.type.factory')->getItemDataClass($this->type);
	}

	/**
	 * @return PrototypeItemIndex|string
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	public function getFulltextDataClass(): string
	{
		return $this->getDataClass()::getFullTextDataClass();
	}

	public function getEntityTypeId(): int
	{
		return $this->type->getEntityTypeId();
	}

	public function getEntityDescription(): string
	{
		return $this->type->getTitle();
	}

	public function getFieldsSettings(): array
	{
		$info =  [
			Item::FIELD_NAME_ID => [
				'TYPE' => Field::TYPE_INTEGER,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly],
			],
			Item::FIELD_NAME_TITLE => [
				'TYPE' => Field::TYPE_STRING,
			],
			Item::FIELD_NAME_XML_ID => [
				'TYPE' => Field::TYPE_STRING,
			],
			Item::FIELD_NAME_CREATED_TIME => [
				'TYPE' => Field::TYPE_DATETIME,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly],
				'CLASS' => Field\CreatedTime::class,
			],
			Item::FIELD_NAME_UPDATED_TIME => [
				'TYPE' => Field::TYPE_DATETIME,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly],
				'CLASS' => Field\UpdatedTime::class,
			],
			Item::FIELD_NAME_CREATED_BY => [
				'TYPE' => Field::TYPE_USER,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly],
				'CLASS' => Field\CreatedBy::class,
			],
			Item::FIELD_NAME_UPDATED_BY => [
				'TYPE' => Field::TYPE_USER,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly],
				'CLASS' => Field\UpdatedBy::class,
			],
			Item::FIELD_NAME_ASSIGNED => [
				'TYPE' => Field::TYPE_USER,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::Required],
				'CLASS' => Field\Assigned::class,
			],
			Item::FIELD_NAME_OPENED => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::Required],
				'CLASS' => Field\Opened::class,
			],
			Item::FIELD_NAME_WEBFORM_ID => [
				'TYPE' => Field::TYPE_INTEGER,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::NotDisplayed],
			],
		];

		if ($this->isMultipleAssignedEnabled())
		{
			$info[Item::FIELD_NAME_ASSIGNED]['ATTRIBUTES'][] = \CCrmFieldInfoAttr::Multiple;
		}

		if ($this->isBeginCloseDatesEnabled())
		{
			$info[Item::FIELD_NAME_BEGIN_DATE] = [
				'TYPE' => Field::TYPE_DATE,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::CanNotBeEmptied, \CCrmFieldInfoAttr::HasDefaultValue],
			];

			$info[Item::FIELD_NAME_CLOSE_DATE] = [
				'TYPE' => Field::TYPE_DATE,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::CanNotBeEmptied, \CCrmFieldInfoAttr::HasDefaultValue],
			];
		}

		if ($this->isClientEnabled())
		{
			$info[Item::FIELD_NAME_COMPANY_ID] = [
				'TYPE' => Field::TYPE_CRM_COMPANY,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::NotDisplayed],
			];

			$info[Item::FIELD_NAME_CONTACT_ID] = [
				'TYPE' => Field::TYPE_CRM_CONTACT,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::NotDisplayed, \CCrmFieldInfoAttr::Deprecated],
			];

			$info[Item::FIELD_NAME_CONTACTS] = [
				'TYPE' => Field::TYPE_CRM_CONTACT,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::NotDisplayed, \CCrmFieldInfoAttr::Multiple]
			];
		}

		if ($this->isObserversEnabled())
		{
			$info[Item::FIELD_NAME_OBSERVERS] = [
				'TYPE' => Field::TYPE_USER,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::Multiple],
				'CLASS' => Field\Observers::class,
			];
		}

		// first check category - only after if - stages.
		if($this->isCategoriesSupported())
		{
			$info[Item::FIELD_NAME_CATEGORY_ID] = [
				'TYPE' => Field::TYPE_INTEGER,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::NotDisplayed],
				'CLASS' => Field\Category::class,
			];
		}

		if($this->isStagesEnabled())
		{
			$info[Item::FIELD_NAME_MOVED_TIME] = [
				'TYPE' => Field::TYPE_DATETIME,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly],
				'CLASS' => Field\MovedTime::class,
			];
			$info[Item::FIELD_NAME_MOVED_BY] = [
				'TYPE' => Field::TYPE_USER,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly],
				'CLASS' => Field\MovedBy::class,
			];
			$info[Item::FIELD_NAME_STAGE_ID] = [
				'TYPE' => Field::TYPE_CRM_STATUS,
				'CLASS' => Field\Stage::class,
			];
			$info[Item::FIELD_NAME_PREVIOUS_STAGE_ID] = [
				'TYPE' => Field::TYPE_CRM_STATUS,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly],
				'CLASS' => Field\PreviousStageId::class,
			];
		}

		if ($this->isSourceEnabled())
		{
			$info[Item::FIELD_NAME_SOURCE_ID] = [
				'TYPE' => Field::TYPE_CRM_STATUS,
				'CRM_STATUS_TYPE' => StatusTable::ENTITY_ID_SOURCE,
			];
			$info[Item::FIELD_NAME_SOURCE_DESCRIPTION] = [
				'TYPE' => Field::TYPE_TEXT,
			];
		}

		if ($this->isLinkWithProductsEnabled())
		{
			$info[Item::FIELD_NAME_OPPORTUNITY] = [
				'TYPE' => Field::TYPE_DOUBLE,
				'CLASS' => Field\Opportunity::class,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::NotDisplayed],
			];
			$info[Item::FIELD_NAME_IS_MANUAL_OPPORTUNITY] = [
				'TYPE' => Field::TYPE_BOOLEAN,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::NotDisplayed],
			];
			$info[Item::FIELD_NAME_TAX_VALUE] = [
				'TYPE' => Field::TYPE_DOUBLE,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::NotDisplayed],
				'CLASS' => Field\TaxValue::class,
			];
			$info[Item::FIELD_NAME_CURRENCY_ID] = [
				'TYPE' => Field::TYPE_CRM_CURRENCY,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::NotDisplayed],
			];
			$info[Item::FIELD_NAME_OPPORTUNITY_ACCOUNT] = [
				'TYPE' => Field::TYPE_DOUBLE,
				'ATTRIBUTES' => [
					\CCrmFieldInfoAttr::NotDisplayed,
					\CCrmFieldInfoAttr::Hidden,
				],
				'CLASS' => Field\OpportunityAccount::class,
			];
			$info[Item::FIELD_NAME_TAX_VALUE_ACCOUNT] = [
				'TYPE' => Field::TYPE_DOUBLE,
				'ATTRIBUTES' => [
					\CCrmFieldInfoAttr::NotDisplayed,
					\CCrmFieldInfoAttr::Hidden,
				],
				'CLASS' => Field\TaxValueAccount::class,
			];
			$info[Item::FIELD_NAME_ACCOUNT_CURRENCY_ID] = [
				'TYPE' => Field::TYPE_CRM_CURRENCY,
				'ATTRIBUTES' => [
					\CCrmFieldInfoAttr::NotDisplayed,
					\CCrmFieldInfoAttr::Hidden,
				],
			];
		}

		if ($this->isMyCompanyEnabled())
		{
			$info[Item::FIELD_NAME_MYCOMPANY_ID] = [
				'TYPE' => Field::TYPE_CRM_COMPANY,
				'SETTINGS' => [
					'isMyCompany' => true,
				],
			];
		}

		return $info;
	}

	public function isCategoriesSupported(): bool
	{
		return true;
	}

	public function isCategoriesEnabled(): bool
	{
		return $this->type->getIsCategoriesEnabled();
	}

	public function isStagesEnabled(): bool
	{
		return $this->type->getIsStagesEnabled();
	}

	public function isBeginCloseDatesEnabled(): bool
	{
		return $this->type->getIsBeginCloseDatesEnabled();
	}

	public function isLinkWithProductsEnabled(): bool
	{
		return $this->type->getIsLinkWithProductsEnabled();
	}

	/**
	 * @inheritDoc
	 */
	public function getStagesEntityId(?int $categoryId = null): ?string
	{
		if (!$categoryId)
		{
			$categoryId = $this->createDefaultCategoryIfNotExist()->getId();
		}

		return $this->getEntityName() . '_STAGE_' . $categoryId;
	}

	public function createCategory(array $data = []): Category
	{
		$object = ItemCategoryTable::createObject();
		$object->setEntityTypeId($this->getEntityTypeId());

		return new ItemCategory($object);
	}

	protected function loadCategories(): array
	{
		$categories = [];

		$list = ItemCategoryTable::getList([
			'filter' => [
				'=ENTITY_TYPE_ID' => $this->getEntityTypeId(),
			],
			'order' => [
				'SORT' => 'ASC',
			],
		]);
		while($item = $list->fetchObject())
		{
			$categories[] = new ItemCategory($item);
		}

		return $categories;
	}

	public function isClientEnabled(): bool
	{
		return $this->type->getIsClientEnabled();
	}

	public function isObserversEnabled(): bool
	{
		return $this->type->getIsObserversEnabled();
	}

	final public function isCrmTrackingEnabled(): bool
	{
		return false;
//		return $this->type->getIsCrmTrackingEnabled();
	}

	public function isMyCompanyEnabled(): bool
	{
		return $this->type->getIsMycompanyEnabled();
	}

	public function isDocumentGenerationEnabled(): bool
	{
		return $this->type->getIsDocumentsEnabled();
	}

	public function isSourceEnabled(): bool
	{
		return $this->type->getIsSourceEnabled();
	}

	public function isUseInUserfieldEnabled(): bool
	{
		return $this->type->getIsUseInUserfieldEnabled();
	}

	public function isRecyclebinEnabled(): bool
	{
		return $this->type->getIsRecyclebinEnabled();
	}

	public function isAutomationEnabled(): bool
	{
		return ($this->type->getIsStagesEnabled() && $this->type->getIsAutomationEnabled());
	}

	public function isBizProcEnabled(): bool
	{
		return $this->type->getIsBizProcEnabled();
	}

	protected function getTrackedFieldNames(): array
	{
		$trackedFieldNames = [
			Item::FIELD_NAME_TITLE,
			Item::FIELD_NAME_ASSIGNED,
		];

		if ($this->isLinkWithProductsEnabled())
		{
			$trackedFieldNames[] = Item::FIELD_NAME_CURRENCY_ID;
			$trackedFieldNames[] = Item::FIELD_NAME_IS_MANUAL_OPPORTUNITY;
		}

		if ($this->isStagesEnabled())
		{
			$trackedFieldNames[] = Item::FIELD_NAME_STAGE_ID;
		}

		if ($this->isMyCompanyEnabled())
		{
			$trackedFieldNames[] = Item::FIELD_NAME_MYCOMPANY_ID;
		}

		if ($this->isClientEnabled())
		{
			$trackedFieldNames[] = Item::FIELD_NAME_COMPANY_ID;
		}

		if ($this->isSourceEnabled())
		{
			$trackedFieldNames[] = Item::FIELD_NAME_SOURCE_ID;
		}

		if($this->isCategoriesSupported())
		{
			$trackedFieldNames[] = Item::FIELD_NAME_CATEGORY_ID;
		}

		return $trackedFieldNames;
	}

	protected function getDependantTrackedObjects(): array
	{
		$objects = [];

		if ($this->isLinkWithProductsEnabled())
		{
			$productTrackedObject = new TrackedObject\Product();
			$productTrackedObject->makeThisObjectDependant(Item::FIELD_NAME_PRODUCTS);
			$objects[] = $productTrackedObject;
		}

		if ($this->isClientEnabled())
		{
			$contactTrackedObject = new TrackedObject\Contact();
			$contactTrackedObject->makeThisObjectDependant(Item::FIELD_NAME_CONTACTS);
			$objects[] = $contactTrackedObject;
		}

		return $objects;
	}
}
