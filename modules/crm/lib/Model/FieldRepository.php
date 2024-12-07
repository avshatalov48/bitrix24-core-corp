<?php

namespace Bitrix\Crm\Model;

use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\Currency;
use Bitrix\Crm\Entity\CommentsHelper;
use Bitrix\Crm\FieldMultiTable;
use Bitrix\Crm\Format\TextHelper;
use Bitrix\Crm\Item;
use Bitrix\Crm\Observer\Entity\ObserverTable;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Reservation;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\StatusTable;
use Bitrix\Crm\UtmTable;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlHelper;
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
 * @internal This is not a part of module public API. For internal system usage only.
 * Is not covered by backwards compatibility
 */
final class FieldRepository
{
	/** @var SqlHelper */
	private $sqlHelper;

	public function __construct()
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$this->sqlHelper = Application::getConnection()->getSqlHelper();
	}

	public function getId(string $fieldName = Item::FIELD_NAME_ID): ScalarField
	{
		return
			(new IntegerField($fieldName))
				->configurePrimary()
				->configureAutocomplete()
		;
	}

	public function getCreatedTime(
		string $fieldName = Item::FIELD_NAME_CREATED_TIME,
		bool $feminine = false
	): ScalarField
	{
		$messageCode = 'CRM_TYPE_ITEM_FIELD_CREATED_TIME';
		$this->prepareMessageCode($messageCode, $feminine);

		return
			(new DatetimeField($fieldName))
				->configureRequired()
				->configureDefaultValue(static function () {
					return new DateTime();
				})
				->configureTitle(Loc::getMessage($messageCode))
		;
	}

	public function getUpdatedTime(
		string $fieldName = Item::FIELD_NAME_UPDATED_TIME,
		bool $feminine = false
	): ScalarField
	{
		$messageCode = 'CRM_TYPE_ITEM_FIELD_UPDATED_TIME';
		$this->prepareMessageCode($messageCode, $feminine);

		return
			(new DatetimeField($fieldName))
				->configureRequired()
				->configureDefaultValue(static function () {
					return new DateTime();
				})
				->configureTitle(Loc::getMessage($messageCode))
		;
	}

	public function getMovedTime(
		string $fieldName = Item::FIELD_NAME_MOVED_TIME,
		bool $feminine = false
	): ScalarField
	{
		$messageCode = 'CRM_TYPE_ITEM_FIELD_MOVED_TIME';
		$this->prepareMessageCode($messageCode, $feminine);

		return
			(new DatetimeField($fieldName))
				->configureTitle(Loc::getMessage($messageCode))
				->configureDefaultValue(static function () {
					return new DateTime();
				})
		;
	}

	public function getLastActivityTime(string $fieldName = Item::FIELD_NAME_LAST_ACTIVITY_TIME): ScalarField
	{
		return
			(new DatetimeField($fieldName))
				->configureDefaultValue(static function () {
					return new DateTime();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_LAST_ACTIVITY_TIME_2'))
		;
	}

	public function getCreatedBy(
		string $fieldName = Item::FIELD_NAME_CREATED_BY,
		bool $feminine = false
	): ScalarField
	{
		$messageCode = 'CRM_TYPE_ITEM_FIELD_CREATED_BY';
		$this->prepareMessageCode($messageCode, $feminine);

		return
			(new IntegerField($fieldName))
				->configureRequired()
				->configureDefaultValue(static function () {
					return Container::getInstance()->getContext()->getUserId();
				})
				->configureTitle(Loc::getMessage($messageCode))
		;
	}

	public function getUpdatedBy(
		string $fieldName = Item::FIELD_NAME_UPDATED_BY,
		bool $feminine = false
	): ScalarField
	{
		$messageCode = 'CRM_TYPE_ITEM_FIELD_UPDATED_BY';
		$this->prepareMessageCode($messageCode, $feminine);

		return
			(new IntegerField($fieldName))
				->configureRequired()
				->configureTitle(Loc::getMessage($messageCode))
		;
	}

	public function getMovedBy(
		string $fieldName = Item::FIELD_NAME_MOVED_BY,
		bool $feminine = false
	): ScalarField
	{
		$messageCode = 'CRM_TYPE_ITEM_FIELD_MOVED_BY';
		$this->prepareMessageCode($messageCode, $feminine);

		return
			(new IntegerField($fieldName))
				->configureDefaultValue(static function () {
					return Container::getInstance()->getContext()->getUserId();
				})
				->configureTitle(Loc::getMessage($messageCode))
		;
	}

	private function prepareMessageCode(string &$messageCode, bool $feminine = false): void
	{
		if ($feminine)
		{
			$messageCode .= '_FEMININE';
		}
	}

	public function getLastActivityBy(string $fieldName = Item::FIELD_NAME_LAST_ACTIVITY_BY): ScalarField
	{
		return
			(new IntegerField($fieldName))
				->configureDefaultValue(static function () {
					return Container::getInstance()->getContext()->getUserId();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_LAST_ACTIVITY_BY'))
		;
	}

	public function getAssigned(string $fieldName = Item::FIELD_NAME_ASSIGNED): ScalarField
	{
		return
			(new IntegerField($fieldName))
				->configureRequired()
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

	public function getCompany(): Relation
	{
		return
			(new Reference(
				'COMPANY',
				CompanyTable::class,
				Join::on('this.COMPANY_ID', 'ref.ID')
			))
			->configureTitle(\CCrmOwnerType::GetDescription(\CCrmOwnerType::Company))
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

			return 0;
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
		$title = Loc::getMessage($entityTypeId === \CCrmOwnerType::Quote
			? 'CRM_TYPE_QUOTE_FIELD_STATUS_MSGVER_2'
			: 'CRM_TYPE_ITEM_FIELD_STAGE_ID'
		);

		return
			(new StringField($fieldName))
				->configureSize(50)
				->configureDefaultValue($this->getDefaultStageIdResolver($entityTypeId))
				->configureTitle($title)
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

	public function getExchRate(string $fieldName = Item::FIELD_NAME_EXCH_RATE): ScalarField
	{
		return
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

	/**
	 * @deprecated This field is not used anymore
	 *
	 * @param string $fieldName
	 * @return ScalarField
	 */
	public function getAddress(string $fieldName = 'ADDRESS'): ScalarField
	{
		return
			(new TextField($fieldName))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_COMMON_ADDRESS'))
		;
	}

	public function getComments(string $fieldName = Item::FIELD_NAME_COMMENTS): ScalarField
	{
		return
			(new TextField($fieldName))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_COMMENTS'))
				->addSaveDataModifier($this->getCommentsNormalizer())
		;
	}

	/**
	 * Returns save data modifier that normalizes value for fields with text content. As they are used to be html,
	 * and now - bb, html -> bb conversion is implemented for backwards compatibility.
	 *
	 * @return callable
	 */
	public function getCommentsNormalizer(): callable
	{
		return static function ($value): string {
			return CommentsHelper::normalizeComment($value, ['p']);
		};
	}

	/**
	 * Returns save data modifier that normalizes value for html field
	 *
	 * @return callable
	 */
	public function getHtmlNormalizer(): callable
	{
		return static function ($value): string {
			return TextHelper::sanitizeHtml($value);
		};
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

	public function getTypeId(string $fieldName = Item::FIELD_NAME_TYPE_ID, ?string $statusEntityId = null): ScalarField
	{
		$typeId =
			(new StringField($fieldName))
				->configureNullable()
				->configureSize(50)
		;

		if (!empty($statusEntityId))
		{
			$typeId->configureDefaultValue($this->getDefaultStatusIdResolver($statusEntityId));
		}

		return $typeId;
	}

	private function getDefaultStatusIdResolver(string $statusEntityId): callable
	{
		return static function () use ($statusEntityId) {
			$statusIds = StatusTable::getStatusesIds($statusEntityId);

			return reset($statusIds);
		};
	}

	public function getSourceId(string $fieldName = Item::FIELD_NAME_SOURCE_ID): ScalarField
	{
		return
			(new StringField($fieldName))
				->configureSize(50)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_SOURCE_ID'))
				->configureDefaultValue($this->getDefaultStatusIdResolver(StatusTable::ENTITY_ID_SOURCE))
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
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_SOURCE_ID'))
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

	public function getOriginVersion(string $fieldName = Item::FIELD_NAME_ORIGIN_VERSION): ScalarField
	{
		return
			(new StringField($fieldName))
				->configureNullable()
				->configureSize(255)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_ORIGIN_VERSION'))
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
				$this->sqlHelper->getDatetimeToDateFunction('%s'),
				$buildFrom,
			))
				->configureValueType(DatetimeField::class)
		;
	}

	public function getFullName(string $fieldName = Item::FIELD_NAME_FULL_NAME): ScalarField
	{
		return
			(new StringField($fieldName))
				->configureNullable()
				->configureSize(100)
		;
	}

	public function getName(string $fieldName = Item::FIELD_NAME_NAME): ScalarField
	{
		return
			(new StringField($fieldName))
				->configureNullable()
				->configureSize(50)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_NAME'))
		;
	}

	public function getSecondName(string $fieldName = Item::FIELD_NAME_SECOND_NAME): ScalarField
	{
		return
			(new StringField($fieldName))
				->configureNullable()
				->configureSize(50)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_SECOND_NAME'))
		;
	}

	public function getLastName(string $fieldName = Item::FIELD_NAME_LAST_NAME): ScalarField
	{
		return
			(new StringField($fieldName))
				->configureNullable()
				->configureSize(50)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_LAST_NAME'))
		;
	}

	public function getShortName(): ExpressionField
	{
		return
			(new ExpressionField(
				'SHORT_NAME',
				$this->sqlHelper->getConcatFunction(
					'%s',
					"' '",
					'UPPER(' . $this->sqlHelper->getSubstrFunction('%s', 1, 1) . ')',
					"'.'",
				),
				['LAST_NAME', 'NAME'],
			))
				->configureValueType(StringField::class)
		;
	}

	public function getHonorific(string $fieldName = Item::FIELD_NAME_HONORIFIC): ScalarField
	{
		return
			(new StringField($fieldName))
				->configureNullable()
				->configureSize(128)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_HONORIFIC'))
		;
	}

	public function getPost(string $fieldName = Item::FIELD_NAME_POST): ScalarField
	{
		return
			(new StringField($fieldName))
				->configureNullable()
				->configureSize(255)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_POST'))
		;
	}

	public function getBirthdate(string $fieldName = Item::FIELD_NAME_BIRTHDATE): ScalarField
	{
		return
			(new DateField($fieldName))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_BIRTHDATE'))
		;
	}

	public function getBirthdaySort(string $fieldName = Item::FIELD_NAME_BIRTHDAY_SORT): ScalarField
	{
		return
			(new IntegerField($fieldName))
				->configureRequired()
				->configureDefaultValue(1024)
		;
	}

	public function getHasPhone(string $fieldName = Item::FIELD_NAME_HAS_PHONE): ScalarField
	{
		return
			(new BooleanField($fieldName))
				->configureRequired()
				->configureStorageValues('N', 'Y')
				->configureDefaultValue(false)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_HAS_PHONE'))
		;
	}

	public function getHasEmail(string $fieldName = Item::FIELD_NAME_HAS_EMAIL): ScalarField
	{
		return
			(new BooleanField($fieldName))
				->configureRequired()
				->configureStorageValues('N', 'Y')
				->configureDefaultValue(false)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_HAS_EMAIL'))
		;
	}

	public function getHasImol(string $fieldName = Item::FIELD_NAME_HAS_IMOL): ScalarField
	{
		return
			(new BooleanField($fieldName))
				->configureRequired()
				->configureStorageValues('N', 'Y')
				->configureDefaultValue(false)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_HAS_IMOL'))
		;
	}

	public function getMultifieldValue(
		string $fieldName,
		int $entityTypeId,
		string $typeId,
		?string $valueType = null
	): ExpressionField
	{
		return $this->getMultifieldValueExpression(
			$fieldName,
			$entityTypeId,
			$typeId,
			$valueType,
		);
	}

	public function getMultifieldValueLike(
		string $fieldName,
		int $entityTypeId,
		string $typeId,
		string $valueLike
	): ExpressionField
	{
		return $this->getMultifieldValueExpression(
			$fieldName,
			$entityTypeId,
			$typeId,
			null,
			$valueLike,
		);
	}

	private function getMultifieldValueExpression(
		string $fieldName,
		int $entityTypeId,
		string $typeId,
		?string $valueType = null,
		?string $valueLike = null
	): ExpressionField
	{
		$fmTableName = $this->sqlHelper->quote(FieldMultiTable::getTableName());
		$entityId = $this->sqlHelper->convertToDbString(\CCrmOwnerType::ResolveName($entityTypeId));
		$typeId = $this->sqlHelper->convertToDbString($typeId);

		$sql = "SELECT FM.VALUE FROM {$fmTableName} FM WHERE FM.ENTITY_ID = {$entityId}"
			. " AND FM.ELEMENT_ID = %s AND FM.TYPE_ID = {$typeId}"
		;

		if (!empty($valueType))
		{
			$sql .= ' AND FM.VALUE_TYPE = ' . $this->sqlHelper->convertToDbString($valueType);
		}

		if (!empty($valueLike))
		{
			$sql .= ' AND FM.VALUE LIKE ' . $this->sqlHelper->convertToDbString($valueLike);
		}

		$sql .= ' ORDER BY FM.ID';

		return
			(new ExpressionField(
				$fieldName,
				'(' . $this->sqlHelper->getTopSql($sql, 1) . ')',
				['ID'],
			))
				->configureValueType(StringField::class)
		;
	}

	public function getFaceId(string $fieldName = Item::FIELD_NAME_FACE_ID): ScalarField
	{
		return
			(new IntegerField($fieldName))
				->configureNullable()
		;
	}

	public function getProductRowReservation(): Relation
	{
		return (
			new Reference(
				Reservation\Internals\ProductRowReservationTable::PRODUCT_ROW_RESERVATION_NAME,
				Reservation\Internals\ProductRowReservationTable::class,
				Join::on('this.ID', 'ref.ROW_ID')
			)
		);
	}
}
