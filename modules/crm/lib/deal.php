<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Crm\History\Entity\DealStageHistoryTable;
use Bitrix\Crm\History\Entity\DealStageHistoryWithSupposedTable;
use Bitrix\Crm\Observer\Entity\ObserverTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\DealSettings;
use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\Relations\CascadePolicy;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

/**
 * Class DealTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Deal_Query query()
 * @method static EO_Deal_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Deal_Result getById($id)
 * @method static EO_Deal_Result getList(array $parameters = array())
 * @method static EO_Deal_Entity getEntity()
 * @method static \Bitrix\Crm\EO_Deal createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_Deal_Collection createCollection()
 * @method static \Bitrix\Crm\EO_Deal wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_Deal_Collection wakeUpCollection($rows)
 */
class DealTable extends Main\ORM\Data\DataManager
{
	protected static $isCheckUserFields = true;

	public static function getTableName()
	{
		return 'b_crm_deal';
	}

	public static function getUfId()
	{
		return 'CRM_DEAL';
	}

	public static function getMap()
	{
		Container::getInstance()->getLocalization()->loadMessages();

		//todo move common fields descriptions in some other place to eliminate duplication
		$map = [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,

			(new DatetimeField('DATE_CREATE'))
				->configureNullable()
				->configureDefaultValue(static function () {
					return new DateTime();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CREATED_TIME'))
			,

			(new ExpressionField(
				'DATE_CREATE_SHORT',
				static::getShortDateExpression(),
				'DATE_CREATE',
			))
				->configureValueType(DatetimeField::class)
			,

			(new DatetimeField('DATE_MODIFY'))
				->configureNullable()
				->configureDefaultValue(static function () {
					return new DateTime();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_UPDATED_TIME'))
			,

			(new ExpressionField(
				'DATE_MODIFY_SHORT',
				static::getShortDateExpression(),
				'DATE_MODIFY',
			))
				->configureValueType(DatetimeField::class)
			,

			(new IntegerField('CREATED_BY_ID'))
				->configureRequired()
				->configureDefaultValue(static function () {
					return Container::getInstance()->getContext()->getUserId();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CREATED_BY'))
			,

			(new ReferenceField(
				'CREATED_BY',
				Main\UserTable::class,
				Join::on('this.CREATED_BY_ID', 'ref.ID'),
			)),

			(new IntegerField('MODIFY_BY_ID'))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_UPDATED_BY'))
			,

			(new ReferenceField(
				'MODIFY_BY',
				Main\UserTable::class,
				Join::on('this.MODIFY_BY_ID', 'ref.ID'),
			)),

			(new IntegerField('ASSIGNED_BY_ID'))
				->configureNullable()
				->configureDefaultValue(static function () {
					return Container::getInstance()->getContext()->getUserId();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_ASSIGNED_BY_ID'))
			,

			(new ReferenceField(
				'ASSIGNED_BY',
				Main\UserTable::class,
				Join::on('this.ASSIGNED_BY_ID', 'ref.ID'),
			)),

			(new BooleanField('OPENED'))
				->configureRequired()
				->configureStorageValues('N', 'Y')
				->configureDefaultValue(static function () {
					return DealSettings::getCurrent()->getOpenedFlag();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_OPENED'))
			,

			(new IntegerField('LEAD_ID'))
				->configureNullable()
				->configureTitle(\CCrmOwnerType::GetAllDescriptions()[\CCrmOwnerType::Lead])
			,

			(new ReferenceField(
				'LEAD_BY',
				LeadTable::class,
				Join::on('this.LEAD_ID', 'ref.ID'),
			)),

			(new IntegerField('COMPANY_ID'))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_COMPANY_ID'))
			,

			(new ReferenceField(
				'COMPANY_BY',
				CompanyTable::class,
				Join::on('this.COMPANY_ID', 'ref.ID'),
			)),

			(new ReferenceField(
				'COMPANY',
				CompanyTable::class,
				Join::on('this.COMPANY_ID', 'ref.ID'),
			)),

			(new IntegerField('CONTACT_ID'))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CONTACT_ID'))
			,

			(new ReferenceField(
				'CONTACT_BY',
				ContactTable::class,
				Join::on('this.CONTACT_ID', 'ref.ID'),
			)),

			(new ReferenceField(
				'CONTACT',
				ContactTable::class,
				Join::on('this.CONTACT_ID', 'ref.ID'),
			)),

			(new ReferenceField(
				'BINDING_CONTACT',
				Binding\DealContactTable::class,
				Join::on('this.ID', 'ref.DEAL_ID'),
			)),

			(new OneToMany(
				'CONTACT_BINDINGS',
				Binding\DealContactTable::class,
				'DEAL'
			))
				->configureCascadeDeletePolicy(CascadePolicy::FOLLOW)
			,

			(new IntegerField('QUOTE_ID'))
				->configureNullable()
				->configureTitle(\CCrmOwnerType::GetAllDescriptions()[\CCrmOwnerType::Quote])
			,

			(new StringField('TITLE'))
				->configureNullable()
				->configureSize(255)
				->configureTitle(Loc::getMessage('CRM_COMMON_TITLE'))
			,

			/** @deprecated */
			(new StringField('PRODUCT_ID'))
				->configureNullable()
				->configureSize(50)
			,

			(new IntegerField('CATEGORY_ID'))
				->configureDefaultValue([static::class, 'getDefaultCategoryId'])
				->configureTitle(Loc::getMessage('CRM_COMMON_CATEGORY'))
			,

			(new StringField('STAGE_ID'))
				->configureNullable()
				->configureSize(50)
				->configureDefaultValue([static::class, 'getDefaultStageId'])
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_STAGE_ID'))
			,

			(new ReferenceField(
				'STAGE_BY',
				StatusTable::class,
				Join::on('this.STAGE_ID', 'ref.STATUS_ID')
					->where('ref.ENTITY_ID', '=', 'DEAL_STAGE')
				,
			)),

			(new StringField('STAGE_SEMANTIC_ID'))
				->configureNullable()
				->configureSize(3)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_STAGE_SEMANTIC_ID_FIELD'))
			,

			(new BooleanField('IS_NEW'))
				->configureNullable()
				->configureValues('N', 'Y')
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_IS_NEW_FIELD'))
			,

			(new BooleanField('IS_RECURRING'))
				->configureRequired()
				->configureStorageValues('N', 'Y')
				->configureDefaultValue(false)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_IS_RECURRING_FIELD'))
			,

			(new ReferenceField(
				'CRM_DEAL_RECURRING',
				DealRecurTable::class,
				Join::on('this.ID', 'ref.DEAL_ID'),
			)),

			(new BooleanField('IS_RETURN_CUSTOMER'))
				->configureRequired()
				->configureStorageValues('N', 'Y')
				->configureDefaultValue(false)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_IS_RETURN_CUSTOMER_FIELD'))
			,

			(new BooleanField('IS_REPEATED_APPROACH'))
				->configureRequired()
				->configureStorageValues('N', 'Y')
				->configureDefaultValue(false)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_IS_REPEATED_APPROACH_FIELD'))
			,

			(new BooleanField('CLOSED'))
				->configureRequired()
				->configureStorageValues('N', 'Y')
				->configureDefaultValue(false)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_CLOSED_FIELD'))
			,

			(new StringField('TYPE_ID'))
				->configureNullable()
				->configureSize(50)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_TYPE_ID_FIELD'))
			,

			(new ReferenceField(
				'TYPE_BY',
				StatusTable::class,
				Join::on('this.TYPE_ID', 'ref.STATUS_ID')
					->where('ref.ENTITY_ID', '=', 'DEAL_TYPE')
				,
			)),

			(new FloatField('OPPORTUNITY'))
				->configureScale(2)
				->configureDefaultValue(0.00)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_OPPORTUNITY'))
			,

			(new BooleanField('IS_MANUAL_OPPORTUNITY'))
				->configureRequired()
				->configureStorageValues('N', 'Y')
				->configureDefaultValue(false)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_IS_MANUAL_OPPORTUNITY'))
			,

			(new FloatField('TAX_VALUE'))
				->configureScale(2)
				->configureDefaultValue(0.00)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_TAX_VALUE'))
			,

			(new StringField('CURRENCY_ID'))
				->configureNullable()
				->configureSize(50)
				->configureDefaultValue(Currency::getBaseCurrencyId())
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CURRENCY_ID'))
			,

			(new FloatField('OPPORTUNITY_ACCOUNT'))
				->configureScale(2)
				->configureDefaultValue(0.00)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_OPPORTUNITY_ACCOUNT'))
			,

			(new FloatField('TAX_VALUE_ACCOUNT'))
				->configureScale(2)
				->configureDefaultValue(0.00)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_TAX_VALUE_ACCOUNT'))
			,

			(new StringField('ACCOUNT_CURRENCY_ID'))
				->configureNullable()
				->configureSize(50)
				->configureDefaultValue(Currency::getAccountCurrencyId())
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_ACCOUNT_CURRENCY_ID'))
			,

			(new IntegerField('PROBABILITY'))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_PROBABILITY_FIELD'))
			,

			(new TextField('COMMENTS'))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_COMMENTS'))
			,

			(new DateField('BEGINDATE'))
				->configureRequired()
				->configureDefaultValue(static function() {
					return new Date();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_BEGINDATE'))
			,

			(new ExpressionField(
				'BEGINDATE_SHORT',
				static::getShortDateExpression(),
				'BEGINDATE',
			))
				->configureValueType(DatetimeField::class)
			,

			(new DateField('CLOSEDATE'))
				->configureRequired()
				->configureDefaultValue([static::class, 'getDefaultCloseDate'])
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CLOSEDATE'))
			,

			(new ExpressionField(
				'CLOSEDATE_SHORT',
				static::getShortDateExpression(),
				'CLOSEDATE',
			))
				->configureValueType(DatetimeField::class)
			,

			(new DatetimeField('EVENT_DATE'))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_EVENT_DATE_FIELD'))
			,

			(new ExpressionField(
				'EVENT_DATE_SHORT',
				static::getShortDateExpression(),
				'EVENT_DATE',
			))
				->configureValueType(DatetimeField::class)
			,

			(new StringField('EVENT_ID'))
				->configureNullable()
				->configureSize(50)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_EVENT_ID_FIELD'))
			,

			(new ReferenceField(
				'EVENT_BY',
				StatusTable::class,
				Join::on('this.EVENT_ID', 'ref.STATUS_ID')
					->where('ref.ENTITY_ID', '=', 'EVENT_TYPE')
				,
			)),

			(new ReferenceField(
				'EVENT_RELATION',
				EventRelationsTable::class,
				Join::on('this.ID', 'ref.ENTITY_ID'),
			))
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_EVENT_RELATION_FIELD'))
			,

			(new TextField('EVENT_DESCRIPTION'))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_EVENT_DESCRIPTION_FIELD'))
			,

			/** @deprecated */
			(new FloatField('EXCH_RATE'))
				->configureRequired()
				->configureScale(4)
				->configureDefaultValue(1)
			,

			(new StringField('LOCATION_ID'))
				->configureNullable()
				->configureSize(100)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_LOCATION'))
			,

			(new IntegerField('WEBFORM_ID'))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_WEBFORM_ID'))
			,

			(new StringField('SOURCE_ID'))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_SOURCE_ID'))
			,

			(new ReferenceField(
				'SOURCE_BY',
				StatusTable::class,
				Join::on('this.SOURCE_ID', 'ref.STATUS_ID')
					->where('ref.ENTITY_ID', '=', 'SOURCE')
				,
			)),

			(new TextField('SOURCE_DESCRIPTION'))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_SOURCE_DESCRIPTION'))
			,

			(new StringField('ORIGINATOR_ID'))
				->configureNullable()
				->configureSize(255)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_ORIGINATOR_ID'))
			,

			(new ReferenceField(
				'ORIGINATOR_BY',
				ExternalSaleTable::class,
				Join::on('this.ORIGINATOR_ID', 'ref.ID'),
			)),

			(new StringField('ORIGIN_ID'))
				->configureNullable()
				->configureSize(255)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_ORIGIN_ID'))
			,

			(new TextField('ADDITIONAL_INFO'))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_ADDITIONAL_INFO_FIELD'))
			,

			(new TextField('SEARCH_CONTENT'))
				->configureNullable()
			,

			(new StringField('ORDER_STAGE'))
				->configureNullable()
				->configureSize(255)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_ORDER_STAGE_FIELD'))
			,

			(new IntegerField('MOVED_BY_ID'))
				->configureDefaultValue(static function () {
					return Container::getInstance()->getContext()->getUserId();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_MOVED_BY'))
			,

			(new DatetimeField('MOVED_TIME'))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_MOVED_TIME'))
			,

			(new ExpressionField(
				'IS_WORK',
				'CASE WHEN %s = \'P\' THEN 1 ELSE 0 END',
				'STAGE_SEMANTIC_ID',
			))
				->configureValueType(BooleanField::class)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_IS_WORK_FIELD'))
			,

			(new ExpressionField(
				'IS_WON',
				'CASE WHEN %s = \'S\' THEN 1 ELSE 0 END',
				'STAGE_SEMANTIC_ID',
			))
				->configureValueType(BooleanField::class)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_IS_WON_FIELD'))
			,

			(new ExpressionField(
				'IS_LOSE',
				'CASE WHEN %s = \'F\' THEN 1 ELSE 0 END',
				'STAGE_SEMANTIC_ID',
			))
				->configureValueType(BooleanField::class)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_IS_LOSE_FIELD'))
			,

			(new ExpressionField(
				'RECEIVED_AMOUNT',
				'CASE WHEN %s = \'S\' THEN %s ELSE 0 END',
				['STAGE_SEMANTIC_ID', 'OPPORTUNITY_ACCOUNT'],
			))
				->configureValueType(IntegerField::class)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_RECEIVED_AMOUNT_FIELD'))
			,

			(new ExpressionField(
				'LOST_AMOUNT',
				'CASE WHEN %s = \'F\' THEN %s ELSE 0 END',
				['STAGE_SEMANTIC_ID', 'OPPORTUNITY_ACCOUNT'],
			))
				->configureValueType(IntegerField::class)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_LOST_AMOUNT_FIELD'))
			,

			(new ExpressionField(
				'HAS_PRODUCTS',
				'CASE WHEN EXISTS (SELECT ID FROM b_crm_product_row WHERE OWNER_ID = %s AND OWNER_TYPE = \'D\') THEN 1 ELSE 0 END',
				'ID',
			))
				->configureValueType(BooleanField::class)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_HAS_PRODUCTS_FIELD'))
			,

			(new ReferenceField(
				'PRODUCT_ROW',
				ProductRowTable::class,
				Join::on('this.ID', 'ref.OWNER_ID')
					->where('ref.OWNER_TYPE', \CCrmOwnerTypeAbbr::Deal)
				,
			)),

			(new ReferenceField(
				'HISTORY',
				DealStageHistoryTable::class,
				Join::on('this.ID', 'ref.OWNER_ID'),
			))
				->configureJoinType(Join::TYPE_INNER)
			,

			(new ReferenceField(
				'FULL_HISTORY',
				DealStageHistoryWithSupposedTable::class,
				Join::on('this.ID', 'ref.OWNER_ID'),
			))
				->configureJoinType(Join::TYPE_INNER)
			,

			(new ReferenceField(
				'ORDER_BINDING',
				Binding\OrderEntityTable::class,
				Join::on('this.ID', 'ref.OWNER_ID')
					->where('ref.OWNER_TYPE_ID', \CCrmOwnerType::Deal)
				,
			)),

			(new OneToMany(
				'PRODUCT_ROWS',
				ProductRowTable::class,
				'DEAL_OWNER',
			))
				// products will be deleted in onAfterDelete, if it's needed
				->configureCascadeDeletePolicy(CascadePolicy::NO_ACTION)
				->configureTitle(Loc::getMessage('CRM_COMMON_PRODUCTS'))
			,

			(new OneToMany(
				'OBSERVER_IDS',
				ObserverTable::class,
				'DEAL'
			))
				->configureCascadeDeletePolicy(CascadePolicy::FOLLOW)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_OBSERVERS'))
			,
		];

		foreach (UtmTable::getCodeList() as $utmFieldName)
		{
			$map[] = new ReferenceField(
				$utmFieldName,
				UtmTable::class,
				Join::on('this.ID', 'ref.ENTITY_ID')
					->where('ref.ENTITY_TYPE_ID', \CCrmOwnerType::Deal)
					->where('ref.CODE', $utmFieldName)
				,
			);
		}

		return $map;
	}

	protected static function getShortDateExpression(): string
	{
		return Application::getConnection()->getSqlHelper()->getDatetimeToDateFunction('%s');
	}

	public static function disableUserFieldsCheck(): void
	{
		static::$isCheckUserFields = false;
	}

	protected static function checkUfFields($object, $ufdata, $result)
	{
		if (!static::$isCheckUserFields)
		{
			static::$isCheckUserFields = true;
			return;
		}

		parent::checkUfFields($object, $ufdata, $result);
	}

	protected static function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Deal;
	}

	protected static function getEntityTypeName(): string
	{
		return \CCrmOwnerType::ResolveName(static::getEntityTypeId());
	}

	protected static function getFactory(): \Bitrix\Crm\Service\Factory
	{
		return Container::getInstance()->getFactory(static::getEntityTypeId());
	}

	public static function getDefaultCloseDate(): Date
	{
		$currentDate = new Date();

		return $currentDate->add(static::getCloseDateOffset());
	}

	protected static function getCloseDateOffset(): string
	{
		return '7D';
	}

	public static function getDefaultCategoryId(): ?int
	{
		$factory = static::getFactory();

		if($factory)
		{
			return $factory->createDefaultCategoryIfNotExist()->getId();
		}

		return null;
	}

	public static function getDefaultStageId(): ?string
	{
		$factory = static::getFactory();
		if ($factory)
		{
			$categoryId = $factory->createDefaultCategoryIfNotExist()->getId();
			$stages = $factory->getStages($categoryId);
			$firstStage = $stages->getAll()[0] ?? null;

			return $firstStage ? $firstStage->getStatusId() : null;
		}

		return null;
	}

	//todo move common event handlers in some common place
	public static function onBeforeAdd(Event $event): EventResult
	{
		$result = new EventResult();

		/** @var EO_Deal $item */
		$item = $event->getParameter('object');
		$factory = static::getFactory();
		if ($factory && $item && empty($item->getStageId()))
		{
			$categoryId = $item->getCategoryId();
			if (!$factory->isCategoryExists($categoryId))
			{
				$categoryId = $factory->createDefaultCategoryIfNotExist()->getId();
			}

			$stage = $factory->getStages($categoryId)->getAll()[0];
			$result->modifyFields(array_merge($result->getModified(), [
				'STAGE_ID' => $stage->getStatusId(),
			]));
		}

		return $result;
	}

	public static function onAfterUpdate(Event $event): EventResult
	{
		$item = $event->getParameter('object');
		if (!$item)
		{
			return new EventResult();
		}

		$result = new EventResult();
		ProductRowTable::handleOwnerUpdate($item, $result);

		return $result;
	}
}
