<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Crm\Binding\QuoteContactTable;
use Bitrix\Crm\Integration\StorageType;
use Bitrix\Crm\Kanban\SortTable;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\EntityError;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\DecimalField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\CascadePolicy;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Objectify\State;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;

Loc::loadMessages(__FILE__);

class QuoteTable extends DataManager
{
	protected const DEFAULT_TEXT_TYPE = \CCrmContentType::Html;

	protected static $isCheckUserFields = true;

	public static function disableUserFieldsCheck(): void
	{
		static::$isCheckUserFields = false;
	}

	/**
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_crm_quote';
	}

	/**
	 * @return string
	 */
	public static function getUfId(): string
	{
		return 'CRM_QUOTE';
	}

	public static function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Quote;
	}

	protected static function getTypeAbbreviation(): string
	{
		return \CCrmOwnerTypeAbbr::Quote;
	}

	protected static function getFactory(): Factory
	{
		return Container::getInstance()->getFactory(static::getEntityTypeId());
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getMap(): array
	{
		Container::getInstance()->getLocalization()->loadMessages();

		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),

			(new DatetimeField('DATE_CREATE'))
				->configureRequired()
				->configureDefaultValue(static function()
				{
					return new DateTime();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CREATED_TIME')),
			(new ExpressionField('DATE_CREATE_SHORT', static::getShortDateExpression(), 'DATE_CREATE'))
				->configureValueType(DateField::class),

			(new DatetimeField('DATE_MODIFY'))
				->configureRequired()
				->configureDefaultValue(static function()
				{
					return new DateTime();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_UPDATED_TIME')),
			(new ExpressionField('DATE_MODIFY_SHORT', static::getShortDateExpression(), 'DATE_MODIFY'))
				->configureValueType(DateField::class),

			(new IntegerField('CREATED_BY_ID'))
				->configureRequired()
				->configureDefaultValue(static function()
				{
					return Container::getInstance()->getContext()->getUserId();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CREATED_BY')),

			(new Reference('CREATED_BY', UserTable::class, Join::on('this.CREATED_BY_ID', 'ref.ID'))),

			(new IntegerField('MODIFY_BY_ID'))
				->configureRequired()
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_UPDATED_BY')),

			(new Reference('MODIFY_BY', UserTable::class, Join::on('this.MODIFY_BY_ID', 'ref.ID'))),

			(new IntegerField('ASSIGNED_BY_ID'))
				->configureDefaultValue(static function()
				{
					return Container::getInstance()->getContext()->getUserId();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_ASSIGNED_BY_ID')),

			(new Reference('ASSIGNED_BY', UserTable::class, Join::on('this.ASSIGNED_BY_ID', 'ref.ID'))),

			(new BooleanField('OPENED'))
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('N')
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_OPENED')),

			(new IntegerField('LEAD_ID'))
				->configureTitle(\CCrmOwnerType::GetAllDescriptions()[\CCrmOwnerType::Lead]),

			(new Reference('LEAD_BY', LeadTable::class, Join::on('this.LEAD_ID', 'ref.ID'))),

			(new IntegerField('DEAL_ID'))
				->configureTitle(\CCrmOwnerType::GetAllDescriptions()[\CCrmOwnerType::Deal]),

			(new Reference('DEAL', DealTable::class, Join::on('this.DEAL_ID', 'ref.ID')))
				->configureTitle(\CCrmOwnerType::GetAllDescriptions()[\CCrmOwnerType::Deal]),

			(new IntegerField('COMPANY_ID')),

			(new Reference('COMPANY_BY', CompanyTable::class, Join::on('this.COMPANY_ID', 'ref.ID'))),

			(new IntegerField('CONTACT_ID')),

			(new Reference('CONTACT_BY', ContactTable::class, Join::on('this.CONTACT_ID', 'ref.ID'))),

			(new OneToMany('CONTACT_BINDINGS', QuoteContactTable::class, 'QUOTE'))
				->configureCascadeDeletePolicy(CascadePolicy::FOLLOW),

			(new EnumField('PERSON_TYPE_ID'))
				->configureRequired()
				->configureValues(static::getPersonTypeValues())
				->configureDefaultValue([static::class, 'getDefaultPersonType']),

			(new IntegerField('MYCOMPANY_ID'))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_MYCOMPANY_ID')),

			(new Reference('MYCOMPANY', CompanyTable::class, Join::on('this.MYCOMPANY_ID', 'ref.ID'))),

			(new StringField('TITLE'))
				->configureSize(255)
				->configureTitle(Loc::getMessage('CRM_QUOTE_TITLE_TITLE')),

			(new StringField('STATUS_ID'))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_STAGE_ID'))
				->configureSize(50)
				->configureDefaultValue([static::class, 'getDefaultStatusId']),

			(new BooleanField('CLOSED'))
				->configureStorageValues('N', 'Y')
				->configureDefaultValue('N')
				->configureTitle(Loc::getMessage('CRM_QUOTE_CLOSED_TITLE')),

			(new DecimalField('OPPORTUNITY'))
				->configurePrecision(18)
				->configureScale(2)
				->configureDefaultValue(0.00)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_OPPORTUNITY')),

			(new DecimalField('TAX_VALUE'))
				->configurePrecision(18)
				->configureScale(2)
				->configureDefaultValue(0.00)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_TAX_VALUE')),

			(new StringField('CURRENCY_ID'))
				->configureSize(50)
				->configureDefaultValue(Currency::getBaseCurrencyId())
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CURRENCY_ID')),

			(new DecimalField('OPPORTUNITY_ACCOUNT'))
				->configurePrecision(18)
				->configureScale(2)
				->configureDefaultValue(0.00)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_OPPORTUNITY_ACCOUNT')),

			(new DecimalField('TAX_VALUE_ACCOUNT'))
				->configurePrecision(18)
				->configureScale(2)
				->configureDefaultValue(0.00)
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_TAX_VALUE_ACCOUNT')),

			(new StringField('ACCOUNT_CURRENCY_ID'))
				->configureSize(50)
				->configureDefaultValue(Currency::getAccountCurrencyId())
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_ACCOUNT_CURRENCY_ID')),

			(new TextField('COMMENTS'))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_COMMENTS')),

			(new IntegerField('COMMENTS_TYPE'))
				->configureDefaultValue(static::DEFAULT_TEXT_TYPE),

			(new DateField('BEGINDATE'))
				->configureDefaultValue(static function()
				{
					return new Date();
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_BEGINDATE')),
			(new ExpressionField('BEGINDATE_SHORT', static::getShortDateExpression(), 'BEGINDATE'))
				->configureValueType(DateField::class),

			(new DateField('CLOSEDATE'))
				->configureDefaultValue([static::class, 'getDefaultCloseDate'])
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CLOSEDATE')),
			(new ExpressionField('CLOSEDATE_SHORT', static::getShortDateExpression(), 'CLOSEDATE'))
				->configureValueType(DateField::class),

			(new FloatField('EXCH_RATE'))
				->configureScale(4),

			(new StringField('QUOTE_NUMBER'))
				->configureSize(100)
				->configureUnique()
				->configureTitle(Loc::getMessage('CRM_QUOTE_QUOTE_NUMBER_TITLE')),

			(new TextField('CONTENT'))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CONTENT')),

			(new IntegerField('CONTENT_TYPE'))
				->configureDefaultValue(static::DEFAULT_TEXT_TYPE),

			(new TextField('TERMS'))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_TERMS')),

			(new IntegerField('TERMS_TYPE'))
				->configureDefaultValue(static::DEFAULT_TEXT_TYPE),

			(new EnumField('STORAGE_TYPE_ID'))
				->configureValues(static::getStorageTypeIdValues())
				->configureDefaultValue([static::class, 'getDefaultStorageTypeId']),

			(new ArrayField('STORAGE_ELEMENT_IDS'))
				// For compatibility reasons
				->configureSerializationPhp()
				->addSaveDataModifier([static::class, 'normalizeStorageElementIds']),

			(new StringField('LOCATION_ID'))
				->configureSize(100),

			(new IntegerField('WEBFORM_ID')),

			(new StringField('CLIENT_TITLE'))
				->configureSize(255),

			(new StringField('CLIENT_ADDR'))
				->configureSize(255),

			(new StringField('CLIENT_CONTACT'))
				->configureSize(255),

			(new StringField('CLIENT_EMAIL'))
				->configureSize(255),

			(new StringField('CLIENT_PHONE'))
				->configureSize(255),

			(new StringField('CLIENT_TP_ID'))
				->configureSize(255),

			(new StringField('CLIENT_TPA_ID'))
				->configureSize(255),

			(new StringField('SEARCH_CONTENT')),

			(new ExpressionField('HAS_PRODUCTS',
				"CASE WHEN EXISTS (SELECT ID FROM ".ProductRowTable::getTableName().
				" WHERE OWNER_ID = %s AND OWNER_TYPE = '".static::getTypeAbbreviation()."') THEN 1 ELSE 0 END",
				'ID'
			))
				->configureValueType(BooleanField::class),

			(new OneToMany('PRODUCT_ROWS', ProductRowTable::class, 'QUOTE_OWNER'))
				// products will be deleted in onAfterDelete, if it's needed
				->configureCascadeDeletePolicy(CascadePolicy::NO_ACTION)
				->configureTitle(Loc::getMessage('CRM_COMMON_PRODUCTS')),

			(new OneToMany('ELEMENTS', QuoteElementTable::class, 'QUOTE'))
				->configureJoinType(Join::TYPE_INNER),
		];
	}

	protected static function getShortDateExpression(): string
	{
		return Application::getConnection()->getSqlHelper()->getDatetimeToDateFunction('%s');
	}

	public static function getDefaultStorageTypeId(): int
	{
		static $typeId;

		if (is_null($typeId))
		{
			$userStorageType = \CUserOptions::getOption('crm', 'quote_storage_type_id');
			$typeId = $userStorageType ? $userStorageType : StorageType::getDefaultTypeID();
		}

		return $typeId;
	}

	/**
	 * @param array $elementIds
	 */
	public static function normalizeStorageElementIds($value)
	{
		if(is_string($value))
		{
			$elementIds = unserialize($value, ['allowed_classes' => false]);
			if(is_array($elementIds))
			{
				$value = array_map('intval', $elementIds);
				$value = array_unique($value, SORT_NUMERIC);
				$value = serialize($value);
			}
		}

		return $value;
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

	public static function onAfterUpdate(Event $event): EventResult
	{
		/** @var EO_Quote|EntityObject|null $item */
		$item = $event->getParameter('object');
		if (!$item)
		{
			return new EventResult();
		}

		$result = new EventResult();
		ProductRowTable::handleOwnerUpdate($item, $result);

		return $result;
	}

	public static function onAfterDelete(Event $event)
	{
		$id = $event->getParameter('primary');
		if (is_array($id))
		{
			$id = $id['ID'];
		}

		QuoteContactTable::unbindAllContacts($id);
		ProductRowTable::deleteByItem(static::getEntityTypeId(), $id);
		QuoteElementTable::deleteByQuoteId($id);
		SortTable::clearEntity($id, \CCrmOwnerType::QuoteName);
		EntityPermsTable::clearByEntity(\CCrmOwnerType::QuoteName, $id);

		$CCrmEvent = new \CCrmEvent();
		$CCrmEvent->DeleteByElement(\CCrmOwnerType::QuoteName, $id);

		\CCrmActivity::DeleteByOwner(\CCrmOwnerType::Quote, $id);
		EntityLink::unregister(\CCrmOwnerType::Quote, $id);

		// delete utm fields
		UtmTable::deleteEntityUtm(\CCrmOwnerType::Quote, $id);
		Tracking\Entity::deleteTrace(\CCrmOwnerType::Quote, $id);
	}

	/**
	 * @return int[]
	 */
	protected static function getPersonTypeValues(): array
	{
		$personTypes = \CCrmPaySystem::getPersonTypeIDs();
		// 0 is also a valid value
		// 0 is used when a site has no person types at all
		$personTypes[] = 0;

		return array_map('intval', $personTypes);
	}

	public static function getDefaultPersonType(): int
	{
		return static::getPersonTypeValues()['CONTACT'] ?? 0;
	}

	public static function getStorageTypeIdValues(): array
	{
		$values = StorageType::getAllTypes();

		return array_merge($values, array_map('strval', $values));
	}

	public static function getDefaultStatusId(): ?string
	{
		$factory = Container::getInstance()->getFactory(\CCrmOwnerType::Quote);
		if(!$factory)
		{
			return null;
		}

		$firstStage = $factory->getStages()->getAll()[0] ?? null;
		if($firstStage)
		{
			return $firstStage->getStatusId();
		}

		return null;
	}

	protected static function checkUfFields($object, $ufdata, $result)
	{
		global $USER_FIELD_MANAGER, $APPLICATION;

		if (!static::$isCheckUserFields)
		{
			static::$isCheckUserFields = true;
			return;
		}

		$userId = ($object->authContext && $object->authContext->getUserId())
			? $object->authContext->getUserId()
			: false;

		$ufPrimary = ($object->sysGetState() === State::RAW)
			? false
			: end($object->primary);

		if (!$USER_FIELD_MANAGER->CheckFields(
			$object->entity->getUfId(),
			$ufPrimary,
			$ufdata,
			$userId,
			false)
		)
		{
			if (is_object($APPLICATION) && $APPLICATION->getException())
			{
				$e = $APPLICATION->getException();
				$result->addError(new EntityError($e->getString()));
				$APPLICATION->resetException();
			}
			else
			{
				$result->addError(new EntityError("Unknown error while checking userfields"));
			}
		}
	}
}
