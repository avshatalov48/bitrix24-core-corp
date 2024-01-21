<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Crm\History\Entity\LeadStatusHistoryTable;
use Bitrix\Crm\History\Entity\LeadStatusHistoryWithSupposedTable;
use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\CascadePolicy;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Query\Join;

Loc::loadMessages(__FILE__);

/**
 * Class LeadTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Lead_Query query()
 * @method static EO_Lead_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Lead_Result getById($id)
 * @method static EO_Lead_Result getList(array $parameters = [])
 * @method static EO_Lead_Entity getEntity()
 * @method static \Bitrix\Crm\EO_Lead createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_Lead_Collection createCollection()
 * @method static \Bitrix\Crm\EO_Lead wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_Lead_Collection wakeUpCollection($rows)
 */
class LeadTable extends Main\ORM\Data\DataManager
{
	private static $STATUS_INIT = false;
	private static $WORK_STATUSES = array();
	private static $REJECT_STATUSES = array();
	protected static $isCheckUserFields = true;

	public static function getTableName()
	{
		return 'b_crm_lead';
	}

	public static function getUFId()
	{
		return 'CRM_LEAD';
	}

	public static function getMap()
	{
		$fieldRepository = Main\DI\ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		$map = [
			//fields here are sorted by b_crm_lead columns order in install.sql. Please, keep it that way

			$fieldRepository->getId(),

			$fieldRepository->getCreatedTime('DATE_CREATE'),

			$fieldRepository->getShortDate(
				'DATE_CREATE_SHORT',
				['DATE_CREATE'],
			)
				->configureTitle(Loc::getMessage('CRM_LEAD_ENTITY_DATE_CREATE_SHORT_FIELD'))
			,

			$fieldRepository->getUpdatedTime('DATE_MODIFY'),

			$fieldRepository->getShortDate(
				'DATE_MODIFY_SHORT',
				['DATE_MODIFY'],
			)
				->configureTitle(Loc::getMessage('CRM_LEAD_ENTITY_DATE_MODIFY_SHORT_FIELD'))
			,

			$fieldRepository->getCreatedBy('CREATED_BY_ID'),

			(new Reference(
				'CREATED_BY',
				Main\UserTable::class,
				Join::on('this.CREATED_BY_ID', 'ref.ID'),
			))
				->configureTitle(Loc::getMessage('CRM_LEAD_ENTITY_CREATED_BY_FIELD'))
			,

			$fieldRepository->getUpdatedBy('MODIFY_BY_ID'),

			(new Reference(
				'MODIFY_BY',
				Main\UserTable::class,
				Join::on('this.MODIFY_BY_ID', 'ref.ID'),
			))
				->configureTitle(Loc::getMessage('CRM_LEAD_ENTITY_MODIFY_BY_FIELD'))
			,

			$fieldRepository->getAssigned(),

			(new Reference(
				'ASSIGNED_BY',
				Main\UserTable::class,
				Join::on('this.ASSIGNED_BY_ID', 'ref.ID'),
			))
				->configureTitle(Loc::getMessage('CRM_LEAD_ENTITY_ASSIGNED_BY_FIELD'))
			,

			$fieldRepository->getOpened()
				->configureDefaultValue(static function () {
					return LeadSettings::getCurrent()->getOpenedFlag();
				})
			,

			$fieldRepository->getCompanyId(),

			$fieldRepository->getCompany(),

			$fieldRepository->getContactId(),

			$fieldRepository->getStageId('STATUS_ID', \CCrmOwnerType::Lead),

			(new Reference(
				'STATUS_BY',
				StatusTable::class,
				Join::on('this.STATUS_ID', 'ref.STATUS_ID')
					->where('ref.ENTITY_ID', '=', 'STATUS')
				,
			))
				->configureTitle(Loc::getMessage('CRM_LEAD_ENTITY_STATUS_BY_FIELD'))
			,

			(new ExpressionField(
				'IS_CONVERT',
				'CASE WHEN %s = \'CONVERTED\' THEN 1 ELSE 0 END',
				['STATUS_ID'],
				['values' => [0, 1]]
			))
				->configureValueType(BooleanField::class)
				->configureTitle(Loc::getMessage('CRM_LEAD_ENTITY_IS_CONVERT_FIELD'))
			,

			(new TextField('STATUS_DESCRIPTION'))
				->configureNullable()
				->configureTitle(\CCrmLead::GetFieldCaption('STATUS_DESCRIPTION'))
			,

			$fieldRepository->getStageSemanticId('STATUS_SEMANTIC_ID'),

			/** @deprecated */
			$fieldRepository->getProductId()
				->configureTitle(Loc::getMessage('CRM_LEAD_ENTITY_PRODUCT_ID_FIELD'))
			,

			$fieldRepository->getOpportunity(),

			$fieldRepository->getCurrencyId(),

			$fieldRepository->getOpportunityAccount(),

			$fieldRepository->getAccountCurrencyId(),

			$fieldRepository->getSourceId(),

			$fieldRepository->getSourceBy(),

			$fieldRepository->getSourceDescription(),

			$fieldRepository->getTitle(),

			$fieldRepository->getFullName(),

			$fieldRepository->getName(),

			$fieldRepository->getLastName(),

			$fieldRepository->getSecondName(),

			$fieldRepository->getShortName()
				->configureTitle(Loc::getMessage('CRM_LEAD_ENTITY_SHORT_NAME_FIELD'))
			,

			(new StringField('COMPANY_TITLE'))
				->configureNullable()
				->configureSize(255)
				->configureTitle(Loc::getMessage('CRM_LEAD_ENTITY_COMPANY_TITLE_FIELD'))
			,

			$fieldRepository->getPost(),

			$fieldRepository->getAddress(),

			$fieldRepository->getComments(),

			$fieldRepository->getExchRate(),

			$fieldRepository->getWebformId(),

			$fieldRepository->getOriginatorId(),

			$fieldRepository->getOriginId(),

			(new DatetimeField('DATE_CLOSED'))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_LEAD_ENTITY_DATE_CLOSED_FIELD'))
			,

			$fieldRepository->getBirthdate(),

			$fieldRepository->getBirthdaySort(),

			$fieldRepository->getHonorific(),

			$fieldRepository->getHasPhone(),

			$fieldRepository->getHasEmail(),

			$fieldRepository->getHasImol(),

			(new ExpressionField(
				'LOGIN',
				'NULL'
			))
				->configureValueType(StringField::class)
			,

			$fieldRepository->getIsReturnCustomer()
				->configureTitle(Loc::getMessage('CRM_LEAD_ENTITY_IS_RETURN_CUSTOMER_FIELD'))
			,

			$fieldRepository->getFaceId(),

			$fieldRepository->getSearchContent(),

			$fieldRepository->getIsManualOpportunity(),

			$fieldRepository->getMovedBy('MOVED_BY_ID'),

			$fieldRepository->getMovedTime(),

			$fieldRepository->getLastActivityBy(),

			$fieldRepository->getLastActivityTime(),

			(new Reference(
				'EVENT_RELATION',
				EventRelationsTable::class,
				Join::on('this.ID', 'ref.ENTITY_ID'),
			))
				->configureTitle(Loc::getMessage('CRM_LEAD_ENTITY_EVENT_RELATION_FIELD'))
			,

			$fieldRepository->getMultifieldValue(
				'PHONE_MOBILE',
				\CCrmOwnerType::Lead,
				Multifield\Type\Phone::ID,
				Multifield\Type\Phone::VALUE_TYPE_MOBILE
			)
				->configureTitle(Loc::getMessage('CRM_LEAD_ENTITY_PHONE_MOBILE_FIELD'))
			,

			$fieldRepository->getMultifieldValue(
				'PHONE_WORK',
				\CCrmOwnerType::Lead,
				Multifield\Type\Phone::ID,
				Multifield\Type\Phone::VALUE_TYPE_WORK
			)
				->configureTitle(Loc::getMessage('CRM_LEAD_ENTITY_PHONE_WORK_FIELD'))
			,

			$fieldRepository->getMultifieldValue(
				'PHONE_MAILING',
				\CCrmOwnerType::Lead,
				Multifield\Type\Phone::ID,
				Multifield\Type\Phone::VALUE_TYPE_MAILING
			),

			$fieldRepository->getMultifieldValue(
				'EMAIL_HOME',
				\CCrmOwnerType::Lead,
				Multifield\Type\Email::ID,
				Multifield\Type\Email::VALUE_TYPE_HOME
			)
				->configureTitle(Loc::getMessage('CRM_LEAD_ENTITY_EMAIL_HOME_FIELD'))
			,

			$fieldRepository->getMultifieldValue(
				'EMAIL_WORK',
				\CCrmOwnerType::Lead,
				Multifield\Type\Email::ID,
				Multifield\Type\Email::VALUE_TYPE_WORK
			)
				->configureTitle(Loc::getMessage('CRM_LEAD_ENTITY_EMAIL_WORK_FIELD'))
			,

			$fieldRepository->getMultifieldValue(
				'EMAIL_MAILING',
				\CCrmOwnerType::Lead,
				Multifield\Type\Email::ID,
				Multifield\Type\Email::VALUE_TYPE_MAILING
			),

			$fieldRepository->getMultifieldValue(
				'SKYPE',
				\CCrmOwnerType::Lead,
				Multifield\Type\Im::ID,
				Multifield\Type\Im::VALUE_TYPE_SKYPE
			)
				->configureTitle(Loc::getMessage('CRM_LEAD_ENTITY_SKYPE_FIELD'))
			,

			$fieldRepository->getMultifieldValue(
				'ICQ',
				\CCrmOwnerType::Lead,
				Multifield\Type\Im::ID,
				Multifield\Type\Im::VALUE_TYPE_ICQ
			)
				->configureTitle(Loc::getMessage('CRM_LEAD_ENTITY_ICQ_FIELD'))
			,

			$fieldRepository->getMultifieldValueLike(
				'IMOL',
				\CCrmOwnerType::Lead,
				Multifield\Type\Im::ID,
				'imol|%%'
			),

			$fieldRepository->getMultifieldValue(
				'EMAIL',
				\CCrmOwnerType::Lead,
				Multifield\Type\Email::ID,
			)
				->configureTitle(Loc::getMessage('CRM_LEAD_ENTITY_EMAIL_FIELD'))
			,

			$fieldRepository->getMultifieldValue(
				'PHONE',
				\CCrmOwnerType::Lead,
				Multifield\Type\Phone::ID,
			)
				->configureTitle(Loc::getMessage('CRM_LEAD_ENTITY_PHONE_FIELD'))
			,

			(new Reference(
				'ADDRESS_ENTITY',
				AddressTable::class,
				Join::on('this.ID', 'ref.ENTITY_ID')
					->where('ref.TYPE_ID', EntityAddressType::Primary)
					->where('ref.ENTITY_TYPE_ID', \CCrmOwnerType::Lead)
				,
			)),

			(new Reference(
				'PRODUCT_ROW',
				ProductRowTable::class,
				Join::on('this.ID', 'ref.OWNER_ID')
					->where('ref.OWNER_TYPE', \CCrmOwnerTypeAbbr::Lead)
				,
			)),

			(new Reference(
				'HISTORY',
				LeadStatusHistoryTable::class,
				Join::on('this.ID', 'ref.OWNER_ID'),
			)),

			(new Reference(
				'FULL_HISTORY',
				LeadStatusHistoryWithSupposedTable::class,
				Join::on('this.ID', 'ref.OWNER_ID'),
			))
				->configureJoinType(Join::TYPE_INNER)
			,

			(new Reference(
				'BINDING_CONTACT',
				Binding\LeadContactTable::class,
				Join::on('this.ID', 'ref.LEAD_ID'),
			)),

			(new OneToMany(
				'CONTACT_BINDINGS',
				Binding\LeadContactTable::class,
				'LEAD',
			))
				->configureCascadeDeletePolicy(CascadePolicy::FOLLOW)
			,

			$fieldRepository->getProductRows('LEAD_OWNER'),

			$fieldRepository->getObservers('LEAD', 'OBSERVER_IDS'),
		];

		return array_merge($map, $fieldRepository->getUtm(\CCrmOwnerType::Lead));
	}

	private static function ensureStatusesLoaded()
	{
		if(self::$STATUS_INIT)
		{
			return;
		}

		global $DB;

		$convertStatus = null;
		$arStatuses = array();
		$rsStatuses = $DB->Query('SELECT STATUS_ID, SORT FROM b_crm_status WHERE ENTITY_ID = \'STATUS\'');
		while($arStatus = $rsStatuses->Fetch())
		{
			if(!$convertStatus && strval($arStatus['STATUS_ID']) === 'CONVERTED')
			{
				$convertStatus = $arStatus;
				continue;
			}

			$arStatuses[$arStatus['STATUS_ID']] = $arStatus;
		}

		self::$WORK_STATUSES = array();
		self::$REJECT_STATUSES = array();

		if($convertStatus)
		{
			$convertStatusSort = intval($convertStatus['SORT']);
			foreach($arStatuses as $statusID => $arStatus)
			{
				$sort = intval($arStatus['SORT']);
				if($sort < $convertStatusSort)
				{
					self::$WORK_STATUSES[] = '\''.$DB->ForSql($statusID).'\'';
				}
				elseif($sort > $convertStatusSort)
				{
					self::$REJECT_STATUSES[] = '\''.$DB->ForSql($statusID).'\'';
				}
			}
		}

		self::$STATUS_INIT = true;
	}

	public static function processQueryOptions(&$options)
	{
		$stub = '_BX_STATUS_STUB_';
		self::ensureStatusesLoaded();
		$options['WORK_STATUS_IDS'] = '('.(!empty(self::$WORK_STATUSES) ? implode(',', self::$WORK_STATUSES) : "'$stub'").')';
		$options['REJECT_STATUS_IDS'] = '('.(!empty(self::$REJECT_STATUSES) ? implode(',', self::$REJECT_STATUSES) : "'$stub'").')';
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
