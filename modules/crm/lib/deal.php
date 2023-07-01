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
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\DealSettings;
use Bitrix\Main;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\CascadePolicy;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Query\Join;

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
		$fieldRepository = Main\DI\ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		$map = [
			//fields here are sorted by b_crm_deal columns order in install.sql. Please, keep it that way

			$fieldRepository->getId(),

			$fieldRepository->getCreatedTime('DATE_CREATE', true),

			$fieldRepository->getShortDate(
				'DATE_CREATE_SHORT',
				['DATE_CREATE'],
			)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_DATE_CREATE_SHORT_FIELD'))
			,

			$fieldRepository->getUpdatedTime('DATE_MODIFY', true),

			$fieldRepository->getShortDate(
				'DATE_MODIFY_SHORT',
				['DATE_MODIFY'],
			)
				->configureTitle('CRM_DEAL_ENTITY_DATE_MODIFY_SHORT_FIELD')
			,

			$fieldRepository->getCreatedBy('CREATED_BY_ID', true),

			(new ReferenceField(
				'CREATED_BY',
				Main\UserTable::class,
				Join::on('this.CREATED_BY_ID', 'ref.ID'),
			))
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_CREATED_BY_FIELD'))
			,

			$fieldRepository->getUpdatedBy('MODIFY_BY_ID', true),

			(new ReferenceField(
				'MODIFY_BY',
				Main\UserTable::class,
				Join::on('this.MODIFY_BY_ID', 'ref.ID'),
			))
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_MODIFY_BY_FIELD'))
			,

			$fieldRepository->getAssigned(),

			(new ReferenceField(
				'ASSIGNED_BY',
				Main\UserTable::class,
				Join::on('this.ASSIGNED_BY_ID', 'ref.ID'),
			))
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_ASSIGNED_BY_FIELD'))
			,

			$fieldRepository->getOpened()
				->configureDefaultValue(static function () {
					return DealSettings::getCurrent()->getOpenedFlag();
				})
			,

			$fieldRepository->getLeadId(),

			(new ReferenceField(
				'LEAD_BY',
				LeadTable::class,
				Join::on('this.LEAD_ID', 'ref.ID'),
			))
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_LEAD_BY_FIELD'))
			,

			$fieldRepository->getCompanyId(),

			(new ReferenceField(
				'COMPANY_BY',
				CompanyTable::class,
				Join::on('this.COMPANY_ID', 'ref.ID'),
			))
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_COMPANY_BY_FIELD'))
			,

			$fieldRepository->getCompany(),

			$fieldRepository->getContactId(),

			(new ReferenceField(
				'CONTACT_BY',
				ContactTable::class,
				Join::on('this.CONTACT_ID', 'ref.ID'),
			))
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_CONTACT_BY_FIELD'))
			,

			(new ReferenceField(
				'CONTACT',
				ContactTable::class,
				Join::on('this.CONTACT_ID', 'ref.ID'),
			))
				->configureTitle(\CCrmOwnerType::GetDescription(\CCrmOwnerType::Contact))
			,

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

			$fieldRepository->getTitle(),

			/** @deprecated */
			$fieldRepository->getProductId(),

			$fieldRepository->getCategoryId(Item::FIELD_NAME_CATEGORY_ID, \CCrmOwnerType::Deal),

			$fieldRepository->getStageId(Item::FIELD_NAME_STAGE_ID, \CCrmOwnerType::Deal),

			(new ReferenceField(
				'STAGE_BY',
				StatusTable::class,
				Join::on('this.STAGE_ID', 'ref.STATUS_ID')
					->where('ref.ENTITY_ID', '=', 'DEAL_STAGE')
				,
			))
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_STAGE_BY_FIELD'))
			,

			$fieldRepository->getStageSemanticId(),

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

			$fieldRepository->getIsReturnCustomer()
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_IS_RETURN_CUSTOMER_FIELD'))
			,

			(new BooleanField('IS_REPEATED_APPROACH'))
				->configureRequired()
				->configureStorageValues('N', 'Y')
				->configureDefaultValue(false)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_IS_REPEATED_APPROACH_FIELD'))
			,

			$fieldRepository->getClosed()
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_CLOSED_FIELD'))
			,

			$fieldRepository->getTypeId(Item::FIELD_NAME_TYPE_ID, StatusTable::ENTITY_ID_DEAL_TYPE)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_TYPE_ID_FIELD'))
			,

			(new ReferenceField(
				'TYPE_BY',
				StatusTable::class,
				Join::on('this.TYPE_ID', 'ref.STATUS_ID')
					->where('ref.ENTITY_ID', '=', StatusTable::ENTITY_ID_DEAL_TYPE)
				,
			))
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_TYPE_BY_FIELD'))
			,

			$fieldRepository->getOpportunity(),

			$fieldRepository->getIsManualOpportunity(),

			$fieldRepository->getTaxValue(),

			$fieldRepository->getCurrencyId(),

			$fieldRepository->getOpportunityAccount(),

			$fieldRepository->getTaxValueAccount(),

			$fieldRepository->getAccountCurrencyId(),

			(new IntegerField('PROBABILITY'))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_PROBABILITY_FIELD'))
			,

			$fieldRepository->getComments(),

			$fieldRepository->getBeginDate(),

			$fieldRepository->getShortDate(
				'BEGINDATE_SHORT',
				['BEGINDATE'],
			)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_BEGINDATE_SHORT_FIELD'))
			,

			$fieldRepository->getCloseDate(),

			$fieldRepository->getShortDate(
				'CLOSEDATE_SHORT',
				['CLOSEDATE'],
			)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_CLOSEDATE_SHORT_FIELD'))
			,

			(new DatetimeField('EVENT_DATE'))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_EVENT_DATE_FIELD'))
			,

			$fieldRepository->getShortDate(
				'EVENT_DATE_SHORT',
				['EVENT_DATE'],
			)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_EVENT_DATE_SHORT_FIELD'))
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
			))
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_EVENT_BY_FIELD'))
			,

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

			$fieldRepository->getExchRate(),

			$fieldRepository->getLocationId(),

			$fieldRepository->getWebformId(),

			$fieldRepository->getSourceId()
				->configureDefaultValue(null)
			,

			$fieldRepository->getSourceBy(),

			$fieldRepository->getSourceDescription(),

			$fieldRepository->getOriginatorId(),

			(new ReferenceField(
				'ORIGINATOR_BY',
				ExternalSaleTable::class,
				Join::on('this.ORIGINATOR_ID', 'ref.ID'),
			))
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_ORIGINATOR_BY_FIELD'))
			,

			$fieldRepository->getOriginId(),

			(new TextField('ADDITIONAL_INFO'))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_ADDITIONAL_INFO_FIELD'))
			,

			$fieldRepository->getSearchContent(),

			(new StringField('ORDER_STAGE'))
				->configureNullable()
				->configureSize(255)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_ORDER_STAGE_FIELD'))
			,

			$fieldRepository->getMovedBy('MOVED_BY_ID', true),

			$fieldRepository->getMovedTime(),

			$fieldRepository->getLastActivityBy(),

			$fieldRepository->getLastActivityTime(),

			(new ExpressionField(
				'IS_WORK',
				'CASE WHEN %s = \'P\' THEN 1 ELSE 0 END',
				'STAGE_SEMANTIC_ID',
				['values' => [0, 1]]
			))
				->configureValueType(BooleanField::class)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_IS_WORK_FIELD'))
			,

			(new ExpressionField(
				'IS_WON',
				'CASE WHEN %s = \'S\' THEN 1 ELSE 0 END',
				'STAGE_SEMANTIC_ID',
				['values' => [0, 1]]
			))
				->configureValueType(BooleanField::class)
				->configureTitle(Loc::getMessage('CRM_DEAL_ENTITY_IS_WON_FIELD'))
			,

			(new ExpressionField(
				'IS_LOSE',
				'CASE WHEN %s = \'F\' THEN 1 ELSE 0 END',
				'STAGE_SEMANTIC_ID',
				['values' => [0, 1]]
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

			$fieldRepository->getHasProducts(\CCrmOwnerType::Deal)
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

			$fieldRepository->getProductRows('DEAL_OWNER'),

			$fieldRepository->getObservers('DEAL', 'OBSERVER_IDS'),
		];

		return array_merge($map, $fieldRepository->getUtm(\CCrmOwnerType::Deal));
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

	/**
	 * @inheritDoc
	 */
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
