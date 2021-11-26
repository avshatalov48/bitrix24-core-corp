<?php

namespace Bitrix\Crm\Service\Factory;

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Category\Entity\DealCategory;
use Bitrix\Crm\Category\Entity\DealCategoryTable;
use Bitrix\Crm\Category\Entity\DealDefaultCategory;
use Bitrix\Crm\Conversion\EntityConversionConfig;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\EventHistory\TrackedObject;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Operation;
use Bitrix\Crm\StatusTable;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Localization\Loc;

final class Deal extends Factory
{
	protected $itemClassName = Item\Deal::class;

	public function __construct()
	{
		Loc::loadMessages(Path::combine(__DIR__, '..', '..', 'classes', 'general', 'crm_deal.php'));
	}

	public function isSourceEnabled(): bool
	{
		return true;
	}

	public function isObserversEnabled(): bool
	{
		return true;
	}

	public function isClientEnabled(): bool
	{
		return true;
	}

	public function isBeginCloseDatesEnabled(): bool
	{
		return true;
	}

	public function isAutomationEnabled(): bool
	{
		return true;
	}

	public function isBizProcEnabled(): bool
	{
		return true;
	}

	public function isCategoriesSupported(): bool
	{
		return true;
	}

	public function isCategoriesEnabled(): bool
	{
		return true;
	}

	public function getDataClass(): string
	{
		return DealTable::class;
	}

	/**
	 * @inheritDoc
	 */
	public function getFieldsMap(): array
	{
		return [
			Item::FIELD_NAME_CREATED_TIME => 'DATE_CREATE',
			Item::FIELD_NAME_UPDATED_TIME => 'DATE_MODIFY',
			Item::FIELD_NAME_CREATED_BY => 'CREATED_BY_ID',
			Item::FIELD_NAME_UPDATED_BY => 'MODIFY_BY_ID',
		];
	}

	public function isNewRoutingForDetailEnabled(): bool
	{
		return false;
	}

	public function isNewRoutingForListEnabled(): bool
	{
		return false;
	}

	public function isNewRoutingForAutomationEnabled(): bool
	{
		return false;
	}

	public function isUseInUserfieldEnabled(): bool
	{
		return true;
	}

	public function isCrmTrackingEnabled(): bool
	{
		return true;
	}

	public function isLinkWithProductsEnabled(): bool
	{
		return true;
	}

	public function isRecyclebinEnabled(): bool
	{
		return true;
	}

	public function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Deal;
	}

	protected function getFieldsSettings(): array
	{
		$info = [
			Item::FIELD_NAME_ID => [
				'TYPE' => Field::TYPE_INTEGER,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly],
			],
			Item::FIELD_NAME_TITLE => [
				'TYPE' => Field::TYPE_STRING,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::Required],
			],
			Item::FIELD_NAME_TYPE_ID => [
				'TYPE' => Field::TYPE_CRM_STATUS,
				'CRM_STATUS_TYPE' => StatusTable::ENTITY_ID_DEAL_TYPE,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::HasDefaultValue],
			],
			Item::FIELD_NAME_CATEGORY_ID => [
				'TYPE' => Field::TYPE_INTEGER,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::NotDisplayed],
				'CLASS' => Field\Category::class,
			],
			Item::FIELD_NAME_STAGE_ID => [
				'TYPE' => Field::TYPE_CRM_STATUS,
				'CRM_STATUS_TYPE' => 'DEAL_STAGE',
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::Progress],
				'CLASS' => Field\Stage::class,
			],
			Item::FIELD_NAME_STATUS_SEMANTIC_ID => [
				'TYPE' => Field::TYPE_STRING,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly, \CCrmFieldInfoAttr::NotDisplayed],
				'CLASS' => Field\StageSemanticId::class,
			],
			Item\Deal::FIELD_NAME_IS_NEW => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly, \CCrmFieldInfoAttr::NotDisplayed],
				'CLASS' => Field\IsNew::class,
			],
			Item::FIELD_NAME_IS_RECURRING => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly, \CCrmFieldInfoAttr::NotDisplayed],
			],
			Item::FIELD_NAME_IS_RETURN_CUSTOMER => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly, \CCrmFieldInfoAttr::NotDisplayed],
			],
			Item\Deal::FIELD_NAME_IS_REPEATED_APPROACH => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly, \CCrmFieldInfoAttr::NotDisplayed],
			],
			Item\Deal::FIELD_NAME_PROBABILITY => [
				'TYPE' => Field::TYPE_INTEGER,
			],
			Item::FIELD_NAME_CURRENCY_ID => [
				'TYPE' => Field::TYPE_CRM_CURRENCY,
			],
			Item::FIELD_NAME_OPPORTUNITY => [
				'TYPE' => Field::TYPE_DOUBLE,
				'CLASS' => Field\Opportunity::class,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::NotDisplayed],
			],
			Item::FIELD_NAME_IS_MANUAL_OPPORTUNITY => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::NotDisplayed],
			],
			Item::FIELD_NAME_TAX_VALUE => [
				'TYPE' => Field::TYPE_DOUBLE,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::NotDisplayed],
				'CLASS' => Field\TaxValue::class,
			],
			Item::FIELD_NAME_OPPORTUNITY_ACCOUNT => [
				'TYPE' => Field::TYPE_DOUBLE,
				'ATTRIBUTES' => [
					\CCrmFieldInfoAttr::NotDisplayed,
					\CCrmFieldInfoAttr::Hidden,
				],
				'CLASS' => Field\OpportunityAccount::class,
			],
			Item::FIELD_NAME_TAX_VALUE_ACCOUNT => [
				'TYPE' => Field::TYPE_DOUBLE,
				'ATTRIBUTES' => [
					\CCrmFieldInfoAttr::NotDisplayed,
					\CCrmFieldInfoAttr::Hidden,
				],
				'CLASS' => Field\TaxValueAccount::class,
			],
			Item::FIELD_NAME_ACCOUNT_CURRENCY_ID => [
				'TYPE' => Field::TYPE_CRM_CURRENCY,
				'ATTRIBUTES' => [
					\CCrmFieldInfoAttr::NotDisplayed,
					\CCrmFieldInfoAttr::Hidden,
				],
			],
			'EXCH_RATE' => [
				'TYPE' => Field::TYPE_DOUBLE,
				'ATTRIBUTES' => [
					\CCrmFieldInfoAttr::Hidden,
				],
			],
			Item::FIELD_NAME_COMPANY_ID => [
				'TYPE' => Field::TYPE_CRM_COMPANY,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::NotDisplayed],
				'SETTINGS' => [
					'parentEntityTypeId' => \CCrmOwnerType::Company,
				],
			],
			Item::FIELD_NAME_CONTACT_ID => [
				'TYPE' => Field::TYPE_CRM_CONTACT,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::NotDisplayed, \CCrmFieldInfoAttr::Deprecated],
			],
			Item::FIELD_NAME_CONTACTS => [
				'TYPE' => Field::TYPE_CRM_CONTACT,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::NotDisplayed, \CCrmFieldInfoAttr::Multiple]
			],
			Item::FIELD_NAME_QUOTE_ID => [
				'TYPE' => Field::TYPE_CRM_COMPANY,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly],
				'SETTINGS' => [
					'parentEntityTypeId' => \CCrmOwnerType::Quote,
				],
			],
			Item::FIELD_NAME_BEGIN_DATE => [
				'TYPE' => Field::TYPE_DATE,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::CanNotBeEmptied, \CCrmFieldInfoAttr::HasDefaultValue],
			],
			Item::FIELD_NAME_CLOSE_DATE => [
				'TYPE' => Field::TYPE_DATE,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::CanNotBeEmptied, \CCrmFieldInfoAttr::HasDefaultValue],
			],
			Item::FIELD_NAME_OPENED => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::Required],
				'CLASS' => Field\Opened::class,
			],
			Item::FIELD_NAME_CLOSED => [
				'TYPE' => Field::TYPE_BOOLEAN,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly],
				'CLASS' => Field\Closed::class,
			],
			Item::FIELD_NAME_COMMENTS => [
				'TYPE' => Field::TYPE_TEXT,
				'ATTRIBUTES' => [],
				'VALUE_TYPE' => Field::VALUE_TYPE_HTML,
			],
			Item::FIELD_NAME_ASSIGNED => [
				'TYPE' => Field::TYPE_USER,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::Required],
				'CLASS' => Field\Assigned::class,
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
			Item::FIELD_NAME_SOURCE_ID => [
				'TYPE' => Field::TYPE_CRM_STATUS,
				'CRM_STATUS_TYPE' => StatusTable::ENTITY_ID_SOURCE,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::HasDefaultValue],
			],
			Item::FIELD_NAME_SOURCE_DESCRIPTION => [
				'TYPE' => Field::TYPE_TEXT,
			],
			Item::FIELD_NAME_LEAD_ID => [
				'TYPE' => Field::TYPE_CRM_LEAD,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::ReadOnly],
				'SETTINGS' => [
					'parentEntityTypeId' => \CCrmOwnerType::Lead,
				],
			],
			Item::FIELD_NAME_ADDITIONAL_INFO => [
				'TYPE' => Field::TYPE_STRING,
			],
			Item::FIELD_NAME_ORIGINATOR_ID => [
				'TYPE' => Field::TYPE_STRING,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::NotDisplayed],
			],
			Item::FIELD_NAME_ORIGIN_ID => [
				'TYPE' => Field::TYPE_STRING,
				'ATTRIBUTES' => [\CCrmFieldInfoAttr::NotDisplayed],
			],
		];

		$locationAttributes = [];
		if (!Container::getInstance()->getAccounting()->isTaxMode())
		{
			$locationAttributes = [\CCrmFieldInfoAttr::NotDisplayed];
		}
		$info[Item::FIELD_NAME_LOCATION_ID] = [
			'TYPE' => Field::TYPE_LOCATION,
			'ATTRIBUTES' => $locationAttributes,
		];

		return $info;
	}

	/**
	 * @inheritDoc
	 */
	public function getStagesEntityId(?int $categoryId = null): ?string
	{
		$categoryId = (int)$categoryId;

		if ($categoryId > 0)
		{
			return 'DEAL_STAGE_' . $categoryId;
		}

		return 'DEAL_STAGE';
	}

	public function getCategoryFieldsInfo(): array
	{
		$fieldsInfo = parent::getCategoryFieldsInfo();

		$fieldsInfo['ENTITY_TYPE_ID']['ATTRIBUTES'][] = \CCrmFieldInfoAttr::ReadOnly;
		$fieldsInfo['IS_DEFAULT']['ATTRIBUTES'][] = \CCrmFieldInfoAttr::ReadOnly;

		$fieldsInfo['ORIGIN_ID'] = [
			'TYPE' => Field::TYPE_STRING,
		];
		$fieldsInfo['ORIGINATOR_ID'] = [
			'TYPE' => Field::TYPE_STRING,
		];

		return $fieldsInfo;
	}

	public function createCategory(array $data = []): Category
	{
		$object = DealCategoryTable::createObject($data);

		return new DealCategory($object);
	}

	protected function loadCategories(): array
	{
		$defaultCategory = new DealDefaultCategory(
			\Bitrix\Crm\Category\DealCategory::getDefaultCategoryName(),
			\Bitrix\Crm\Category\DealCategory::getDefaultCategorySort()
		);

		$result = [$defaultCategory];

		$categories = DealCategoryTable::getList([
			'filter' => [
				'=IS_LOCKED' => 'N',
			],
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'ASC',
			]
		])->fetchCollection();
		foreach ($categories as $category)
		{
			$result[] = new DealCategory($category);
		}

		usort(
			$result,
			static function(Category $a, Category $b) {
				if ($a->getSort() === $b->getSort())
				{
					return 0;
				}

				return ($a->getSort() < $b->getSort()) ? -1 : 1;
			}
		);

		return $result;
	}

	protected function getTrackedFieldNames(): array
	{
		return [
			Item::FIELD_NAME_TITLE,
			Item::FIELD_NAME_ASSIGNED,
			Item::FIELD_NAME_CURRENCY_ID,
			Item::FIELD_NAME_STAGE_ID,
			Item::FIELD_NAME_COMPANY_ID,
			Item::FIELD_NAME_IS_MANUAL_OPPORTUNITY,
		];
	}

	protected function getDependantTrackedObjects(): array
	{
		$objects = [];

		$productTrackedObject = new TrackedObject\Product();
		$productTrackedObject->makeThisObjectDependant(Item::FIELD_NAME_PRODUCTS);
		$objects[] = $productTrackedObject;

		$contactTrackedObject = new TrackedObject\Contact();
		$contactTrackedObject->makeThisObjectDependant(Item::FIELD_NAME_CONTACTS);
		$objects[] = $contactTrackedObject;

		return $objects;
	}

	public function getAddOperation(Item $item, Context $context = null): Operation\Add
	{
		// duplication and statistic procession is not ready yet
		throw new InvalidOperationException('Deal factory is not ready to work with operations yet');
	}

	public function getUpdateOperation(Item $item, Context $context = null): Operation\Update
	{
		throw new InvalidOperationException('Deal factory is not ready to work with operations yet');
	}

	public function getDeleteOperation(Item $item, Context $context = null): Operation\Delete
	{
		throw new InvalidOperationException('Deal factory is not ready to work with operations yet');
	}

	public function getItemCategoryId(int $id): ?int
	{
		return \CCrmDeal::GetCategoryID($id);
	}

	public function getConversionOperation(
		Item $item,
		EntityConversionConfig $configs,
		Context $context = null
	): Operation\Conversion
	{
		throw new InvalidOperationException('Deal factory is not ready to work with operations yet');
	}
}
