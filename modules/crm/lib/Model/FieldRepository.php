<?php

namespace Bitrix\Crm\Model;

use Bitrix\Crm\Currency;
use Bitrix\Crm\Item;
use Bitrix\Crm\Observer\Entity\ObserverTable;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\StatusTable;
use Bitrix\Crm\UtmTable;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\CascadePolicy;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\Relations\Relation;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

/**
 * Contains descriptions for common fields in CRM ORM entities
 */
final class FieldRepository
{
	public function __construct()
	{
		Container::getInstance()->getLocalization()->loadMessages();
	}

	public function getId(string $fieldName = Item::FIELD_NAME_ID): ScalarField
	{
		return
			(new IntegerField($fieldName))
				->configurePrimary()
				->configureAutocomplete()
		;
	}

	public function getCreatedTime(string $fieldName = Item::FIELD_NAME_CREATED_TIME): ScalarField
	{
		return
			(new DatetimeField($fieldName))
				->configureRequired()
				->configureDefaultValue(static function () {
					return new DateTime();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CREATED_TIME'))
		;
	}

	public function getUpdatedTime(string $fieldName = Item::FIELD_NAME_UPDATED_TIME): ScalarField
	{
		return
			(new DatetimeField($fieldName))
				->configureRequired()
				->configureDefaultValue(static function () {
					return new DateTime();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_UPDATED_TIME'))
		;
	}

	public function getMovedTime(string $fieldName = Item::FIELD_NAME_MOVED_TIME): ScalarField
	{
		return
			(new DatetimeField($fieldName))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_MOVED_TIME'))
		;
	}

	public function getCreatedBy(string $fieldName = Item::FIELD_NAME_CREATED_BY): ScalarField
	{
		return
			(new IntegerField($fieldName))
				->configureRequired()
				->configureDefaultValue(static function () {
					return Container::getInstance()->getContext()->getUserId();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CREATED_BY'))
		;
	}

	public function getUpdatedBy(string $fieldName = Item::FIELD_NAME_UPDATED_BY): ScalarField
	{
		return
			(new IntegerField($fieldName))
				->configureRequired()
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_UPDATED_BY'))
		;
	}

	public function getMovedBy(string $fieldName = Item::FIELD_NAME_MOVED_BY): ScalarField
	{
		return
			(new IntegerField($fieldName))
				->configureDefaultValue(static function () {
					return Container::getInstance()->getContext()->getUserId();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_MOVED_BY'))
		;
	}

	public function getAssigned(string $fieldName = Item::FIELD_NAME_ASSIGNED): ScalarField
	{
		return
			(new IntegerField($fieldName))
				->configureRequired()
				->configureDefaultValue(static function () {
					return Container::getInstance()->getContext()->getUserId();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_ASSIGNED_BY_ID'))
		;
	}

	public function getOpened(string $fieldName = Item::FIELD_NAME_OPENED): ScalarField
	{
		return
			(new BooleanField($fieldName))
				->configureRequired()
				->configureStorageValues('N', 'Y')
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_OPENED'))
		;
	}

	public function getLeadId(string $fieldName = Item::FIELD_NAME_LEAD_ID): ScalarField
	{
		return
			(new IntegerField($fieldName))
				->configureNullable()
				->configureTitle(\CCrmOwnerType::GetAllDescriptions()[\CCrmOwnerType::Lead])
		;
	}

	public function getCompanyId(string $fieldName = Item::FIELD_NAME_COMPANY_ID): ScalarField
	{
		return
			(new IntegerField($fieldName))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_COMPANY_ID'))
		;
	}

	public function getMyCompanyId(string $fieldName = Item::FIELD_NAME_MYCOMPANY_ID): ScalarField
	{
		$defaultMyCompanyResolver = static function (): ?int {
			$defaultMyCompanyId = EntityLink::getDefaultMyCompanyId();
			if ($defaultMyCompanyId > 0)
			{
				return $defaultMyCompanyId;
			}

			return null;
		};

		return
			(new IntegerField($fieldName))
				->configureDefaultValue($defaultMyCompanyResolver)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_MYCOMPANY_ID'))
		;
	}

	public function getContactId(string $fieldName = Item::FIELD_NAME_CONTACT_ID): ScalarField
	{
		return
			(new IntegerField($fieldName))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CONTACT_ID'))
		;
	}

	public function getTitle(string $fieldName = Item::FIELD_NAME_TITLE): ScalarField
	{
		return
			(new StringField($fieldName))
				->configureSize(255)
				->configureTitle(Loc::getMessage('CRM_COMMON_TITLE'))
		;
	}

	public function getCategoryId(
		string $fieldName = Item::FIELD_NAME_CATEGORY_ID,
		int $entityTypeId = \CCrmOwnerType::Undefined
	): ScalarField
	{
		return
			(new IntegerField($fieldName))
				->configureRequired()
				->configureDefaultValue($this->getDefaultCategoryIdResolver($entityTypeId))
				->configureTitle(Loc::getMessage('CRM_COMMON_CATEGORY'))
		;
	}

	public function getDefaultCategoryIdResolver(int $entityTypeId): callable
	{
		return static function () use ($entityTypeId): ?int {
			$factory = Container::getInstance()->getFactory($entityTypeId);

			if ($factory && $factory->isCategoriesSupported())
			{
				return $factory->createDefaultCategoryIfNotExist()->getId();
			}

			return null;
		};
	}

	public function getStageId(
		string $fieldName = Item::FIELD_NAME_STAGE_ID,
		int $entityTypeId = \CCrmOwnerType::Undefined
	): ScalarField
	{
		return
			(new StringField($fieldName))
				->configureSize(50)
				->configureDefaultValue($this->getDefaultStageIdResolver($entityTypeId))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_STAGE_ID'))
		;
	}

	public function getDefaultStageIdResolver(int $entityTypeId): callable
	{
		return static function () use ($entityTypeId): ?string {
			$factory = Container::getInstance()->getFactory($entityTypeId);

			if (!$factory || !$factory->isStagesSupported())
			{
				return null;
			}

			$categoryId = null;
			if ($factory->isCategoriesSupported())
			{
				$categoryId = $factory->createDefaultCategoryIfNotExist()->getId();
			}

			$stages = $factory->getStages($categoryId)->getAll();
			$firstStage = reset($stages);

			return $firstStage ? $firstStage->getStatusId() : null;
		};
	}

	public function getStageSemanticId(string $fieldName = Item::FIELD_NAME_STAGE_SEMANTIC_ID): ScalarField
	{
		return
			(new StringField($fieldName))
				->configureNullable()
				->configureSize(3)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_STAGE_SEMANTIC_ID'))
		;
	}

	public function getIsReturnCustomer(string $fieldName = Item::FIELD_NAME_IS_RETURN_CUSTOMER): ScalarField
	{
		return
			(new BooleanField($fieldName))
				->configureRequired()
				->configureStorageValues('N', 'Y')
				->configureDefaultValue(false)
		;
	}

	public function getIsManualOpportunity(string $fieldName = Item::FIELD_NAME_IS_MANUAL_OPPORTUNITY): ScalarField
	{
		return
			(new BooleanField($fieldName))
				->configureRequired()
				->configureStorageValues('N', 'Y')
				->configureDefaultValue(false)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_IS_MANUAL_OPPORTUNITY'))
		;
	}

	public function getOpportunity(string $fieldName = Item::FIELD_NAME_OPPORTUNITY): ScalarField
	{
		return
			(new FloatField($fieldName))
				->configureScale(2)
				->configureDefaultValue(0.00)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_OPPORTUNITY'))
		;
	}

	public function getOpportunityAccount(string $fieldName = Item::FIELD_NAME_OPPORTUNITY_ACCOUNT): ScalarField
	{
		return
			(new FloatField($fieldName))
				->configureScale(2)
				->configureDefaultValue(0.00)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_OPPORTUNITY_ACCOUNT'))
		;
	}

	public function getTaxValue(string $fieldName = Item::FIELD_NAME_TAX_VALUE): ScalarField
	{
		return
			(new FloatField($fieldName))
				->configureScale(2)
				->configureDefaultValue(0.00)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_TAX_VALUE'))
		;
	}

	public function getTaxValueAccount(string $fieldName = Item::FIELD_NAME_TAX_VALUE_ACCOUNT): ScalarField
	{
		return
			(new FloatField($fieldName))
				->configureScale(2)
				->configureDefaultValue(0.00)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_TAX_VALUE_ACCOUNT'))
		;
	}

	public function getCurrencyId(string $fieldName = Item::FIELD_NAME_CURRENCY_ID): ScalarField
	{
		return
			(new StringField($fieldName))
				->configureSize(50)
				->configureDefaultValue(static function () {
					return Currency::getBaseCurrencyId();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CURRENCY_ID'))
		;
	}

	public function getAccountCurrencyId(string $fieldName = Item::FIELD_NAME_ACCOUNT_CURRENCY_ID): ScalarField
	{
		return
			(new StringField($fieldName))
				->configureSize(50)
				->configureDefaultValue(static function () {
					return Currency::getAccountCurrencyId();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_ACCOUNT_CURRENCY_ID'))
		;
	}

	/**
	 * @deprecated This field is not used anymore
	 *
	 * @param string $fieldName
	 * @return ScalarField
	 */
	public function getExchRate(string $fieldName = 'EXCH_RATE'): ScalarField
	{
		return
			/** @deprecated */
			(new FloatField($fieldName))
				->configureScale(4)
				->configureDefaultValue(1)
		;
	}

	/**
	 * @deprecated This field is not used anymore
	 *
	 * @param string $fieldName
	 * @return ScalarField
	 */
	public function getProductId(string $fieldName = 'PRODUCT_ID'): ScalarField
	{
		return
			(new StringField($fieldName))
				->configureNullable()
				->configureSize(50)
		;
	}

	public function getComments(string $fieldName = Item::FIELD_NAME_COMMENTS): ScalarField
	{
		return
			(new TextField($fieldName))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_COMMENTS'))
		;
	}

	public function getBeginDate(string $fieldName = Item::FIELD_NAME_BEGIN_DATE): ScalarField
	{
		return
			(new DateField($fieldName))
				->configureRequired()
				->configureDefaultValue(static function() {
					return new Date();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_BEGINDATE'))
		;
	}

	public function getCloseDate(string $fieldName = Item::FIELD_NAME_CLOSE_DATE): ScalarField
	{
		$defaultCloseDateResolver = static function (): Date {
			$currentDate = new Date();

			return $currentDate->add('7D');
		};

		return
			(new DateField($fieldName))
				->configureRequired()
				->configureDefaultValue($defaultCloseDateResolver)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CLOSEDATE'))
		;
	}

	public function getClosed(string $fieldName = Item::FIELD_NAME_CLOSED): ScalarField
	{
		return
			(new BooleanField($fieldName))
				->configureRequired()
				->configureStorageValues('N', 'Y')
				->configureDefaultValue(false)
		;
	}

	public function getLocationId(string $fieldName = Item::FIELD_NAME_LOCATION_ID): ScalarField
	{
		return
			(new StringField($fieldName))
				->configureNullable()
				->configureSize(100)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_LOCATION'))
		;
	}

	public function getWebformId(string $fieldName = Item::FIELD_NAME_WEBFORM_ID): ScalarField
	{
		return
			(new IntegerField($fieldName))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_WEBFORM_ID'))
		;
	}

	public function getSourceId(string $fieldName = Item::FIELD_NAME_SOURCE_ID): ScalarField
	{
		return
			(new StringField($fieldName))
				->configureSize(50)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_SOURCE_ID'))
		;
	}

	/**
	 * It seems that this field is used in reports.
	 * @return Relation
	 */
	public function getSourceBy(): Relation
	{
		return
			(new Reference(
				'SOURCE_BY',
				StatusTable::class,
				Join::on('this.SOURCE_ID', 'ref.STATUS_ID')
					->where('ref.ENTITY_ID', '=', 'SOURCE')
				,
			))
		;
	}

	public function getSourceDescription(string $fieldName = Item::FIELD_NAME_SOURCE_DESCRIPTION): ScalarField
	{
		return
			(new TextField($fieldName))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_SOURCE_DESCRIPTION'))
		;
	}

	public function getOriginatorId(string $fieldName = Item::FIELD_NAME_ORIGINATOR_ID): ScalarField
	{
		return
			(new StringField($fieldName))
				->configureNullable()
				->configureSize(255)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_ORIGINATOR_ID'))
		;
	}

	public function getOriginId(string $fieldName = Item::FIELD_NAME_ORIGIN_ID): ScalarField
	{
		return
			(new StringField($fieldName))
				->configureNullable()
				->configureSize(255)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_ORIGIN_ID'))
		;
	}

	public function getSearchContent(string $fieldName = 'SEARCH_CONTENT'): ScalarField
	{
		return
			(new TextField($fieldName))
				->configureNullable()
		;
	}

	public function getHasProducts(int $entityTypeId, string $fieldName = 'HAS_PRODUCTS'): ExpressionField
	{
		$productTable = ProductRowTable::getTableName();
		$ownerType = \CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId);

		return
			(new ExpressionField(
				$fieldName,
				"CASE WHEN EXISTS (SELECT ID FROM {$productTable} WHERE OWNER_ID = %s AND OWNER_TYPE = '{$ownerType}')"
				. ' THEN 1 ELSE 0 END',
				'ID',
			))
				->configureValueType(BooleanField::class)
		;
	}

	/**
	 * @param int $entityTypeId
	 * @return Relation[]
	 */
	public function getUtm(int $entityTypeId): array
	{
		$utm = [];

		foreach (UtmTable::getCodeNames() as $fieldName => $fieldTitle)
		{
			$utm[] =
				(new Reference(
					$fieldName,
					UtmTable::class,
					Join::on('this.ID', 'ref.ENTITY_ID')
						->where('ref.ENTITY_TYPE_ID', $entityTypeId)
						->where('ref.CODE', $fieldName)
				))
					->configureTitle($fieldTitle)
			;
		}

		return $utm;
	}

	/**
	 * @param string $referenceName - do not forget that you have to create reference to your table in ProductRowTable
	 * @param string $fieldName
	 * @return Relation
	 */
	public function getProductRows(string $referenceName, string $fieldName = Item::FIELD_NAME_PRODUCTS): Relation
	{
		return
			(new OneToMany(
				$fieldName,
				ProductRowTable::class,
				$referenceName,
			))
				// products will be deleted in onAfterDelete, if it's needed. DO NOT FORGET TO ADD IT THERE
				->configureCascadeDeletePolicy(CascadePolicy::NO_ACTION)
				->configureTitle(Loc::getMessage('CRM_COMMON_PRODUCTS'))
		;
	}

	/**
	 * @param string $referenceName - do not forget that you have to create reference to your table in ObserverTable
	 * @param string $fieldName
	 * @return Relation
	 */
	public function getObservers(string $referenceName, string $fieldName = Item::FIELD_NAME_OBSERVERS): Relation
	{
		return
			(new OneToMany(
				$fieldName,
				ObserverTable::class,
				$referenceName
			))
				->configureCascadeDeletePolicy(CascadePolicy::FOLLOW)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_OBSERVERS'))
		;
	}

	public function getShortDate(
		string $fieldName,
		array $buildFrom
	): ExpressionField
	{
		return
			(new ExpressionField(
				$fieldName,
				Application::getConnection()->getSqlHelper()->getDatetimeToDateFunction('%s'),
				$buildFrom,
			))
				->configureValueType(DatetimeField::class)
		;
	}
}
