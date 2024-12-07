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
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Settings\QuoteSettings;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\CascadePolicy;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\Date;
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

		$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		return [
			$fieldRepository->getId(),

			$fieldRepository->getCreatedTime('DATE_CREATE'),

			$fieldRepository->getShortDate(
				'DATE_CREATE_SHORT',
				['DATE_CREATE'],
			),

			$fieldRepository->getUpdatedTime('DATE_MODIFY'),

			$fieldRepository->getShortDate(
				'DATE_MODIFY_SHORT',
				['DATE_MODIFY'],
			),

			$fieldRepository->getCreatedBy('CREATED_BY_ID'),

			(new Reference('CREATED_BY', UserTable::class, Join::on('this.CREATED_BY_ID', 'ref.ID'))),

			$fieldRepository->getUpdatedBy('MODIFY_BY_ID'),

			(new Reference('MODIFY_BY', UserTable::class, Join::on('this.MODIFY_BY_ID', 'ref.ID'))),

			$fieldRepository->getAssigned(),

			(new Reference('ASSIGNED_BY', UserTable::class, Join::on('this.ASSIGNED_BY_ID', 'ref.ID'))),

			$fieldRepository->getOpened()
				->configureDefaultValue(static function() {
					return QuoteSettings::getCurrent()->getOpenedFlag();
				})
			,

			$fieldRepository->getLeadId(),

			(new Reference('LEAD_BY', LeadTable::class, Join::on('this.LEAD_ID', 'ref.ID'))),

			(new IntegerField('DEAL_ID'))
				->configureTitle(\CCrmOwnerType::GetAllDescriptions()[\CCrmOwnerType::Deal]),

			(new Reference('DEAL', DealTable::class, Join::on('this.DEAL_ID', 'ref.ID')))
				->configureTitle(\CCrmOwnerType::GetAllDescriptions()[\CCrmOwnerType::Deal]),

			$fieldRepository->getCompanyId(),

			(new Reference('COMPANY_BY', CompanyTable::class, Join::on('this.COMPANY_ID', 'ref.ID'))),

			$fieldRepository->getContactId(),

			(new Reference('CONTACT_BY', ContactTable::class, Join::on('this.CONTACT_ID', 'ref.ID'))),

			(new OneToMany('CONTACT_BINDINGS', QuoteContactTable::class, 'QUOTE'))
				->configureCascadeDeletePolicy(CascadePolicy::FOLLOW),

			(new EnumField('PERSON_TYPE_ID'))
				->configureRequired()
				->configureValues(static::getPersonTypeValues())
				->configureDefaultValue([static::class, 'getDefaultPersonType']),

			$fieldRepository->getMyCompanyId(),

			(new Reference('MYCOMPANY', CompanyTable::class, Join::on('this.MYCOMPANY_ID', 'ref.ID'))),

			$fieldRepository->getTitle()
				->configureTitle(Loc::getMessage('CRM_QUOTE_TITLE_TITLE'))
			,

			$fieldRepository->getStageId('STATUS_ID', \CCrmOwnerType::Quote),

			$fieldRepository->getClosed()
				->configureTitle(Loc::getMessage('CRM_QUOTE_CLOSED_TITLE_MSGVER_1'))
			,

			$fieldRepository->getOpportunity(),

			$fieldRepository->getIsManualOpportunity(),

			$fieldRepository->getTaxValue(),

			$fieldRepository->getCurrencyId(),

			$fieldRepository->getOpportunityAccount(),

			$fieldRepository->getTaxValueAccount(),

			$fieldRepository->getAccountCurrencyId(),

			$fieldRepository->getComments(),

			(new IntegerField('COMMENTS_TYPE'))
				->configureNullable()
				->configureDefaultValue(static::DEFAULT_TEXT_TYPE)
			,

			$fieldRepository->getBeginDate(),

			$fieldRepository->getShortDate(
				'BEGINDATE_SHORT',
				['BEGINDATE'],
			),

			$fieldRepository->getCloseDate(),

			$fieldRepository->getShortDate(
				'CLOSEDATE_SHORT',
				['CLOSEDATE']
			),

			$fieldRepository->getExchRate(),

			(new StringField('QUOTE_NUMBER'))
				->configureSize(100)
				->configureUnique()
				->configureTitle(Loc::getMessage('CRM_QUOTE_QUOTE_NUMBER_TITLE_MSGVER_1'))
			,

			(new TextField('CONTENT'))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_CONTENT'))
				->addSaveDataModifier($fieldRepository->getHtmlNormalizer())
			,

			(new IntegerField('CONTENT_TYPE'))
				->configureDefaultValue(static::DEFAULT_TEXT_TYPE)
			,

			(new TextField('TERMS'))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_TERMS'))
				->addSaveDataModifier($fieldRepository->getHtmlNormalizer())
			,

			(new IntegerField('TERMS_TYPE'))
				->configureDefaultValue(static::DEFAULT_TEXT_TYPE)
			,

			(new EnumField('STORAGE_TYPE_ID'))
				->configureValues(static::getStorageTypeIdValues())
				->configureDefaultValue([static::class, 'getDefaultStorageTypeId'])
			,

			(new ArrayField('STORAGE_ELEMENT_IDS'))
				// For compatibility reasons
				->configureSerializationPhp()
				->addSaveDataModifier([static::class, 'normalizeStorageElementIds'])
			,

			$fieldRepository->getLocationId(),

			$fieldRepository->getWebformId(),

			(new StringField('CLIENT_TITLE'))
				->configureSize(255)
			,

			(new StringField('CLIENT_ADDR'))
				->configureSize(255)
			,

			(new StringField('CLIENT_CONTACT'))
				->configureSize(255)
			,

			(new StringField('CLIENT_EMAIL'))
				->configureSize(255)
			,

			(new StringField('CLIENT_PHONE'))
				->configureSize(255)
			,

			(new StringField('CLIENT_TP_ID'))
				->configureSize(255)
			,

			(new StringField('CLIENT_TPA_ID'))
				->configureSize(255)
			,

			$fieldRepository->getSearchContent(),

			$fieldRepository->getLastActivityBy(),

			$fieldRepository->getLastActivityTime(),

			$fieldRepository->getHasProducts(
				\CCrmOwnerType::Quote,
			),

			$fieldRepository->getProductRows(
				'QUOTE_OWNER',
			),

			(new OneToMany('ELEMENTS', QuoteElementTable::class, 'QUOTE'))
				->configureJoinType(Join::TYPE_INNER)
				->configureCascadeDeletePolicy(CascadePolicy::FOLLOW)
			,

			(new DateField(Item\Quote::FIELD_NAME_ACTUAL_DATE))
				->configureDefaultValue(static function(): Date {
					return (new Date())->add('7D');
				})
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_ACTUAL_DATE'))
			,
		];
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
				$value = array_filter($elementIds, fn($fileId) => static::isValidStorageElementId($fileId));
				$value = array_unique($value);
				$value = serialize($value);
			}
		}

		return $value;
	}

	/**
	 * @see \Bitrix\Disk\Uf\FileUserType::detectType()
	 */
	private static function isValidStorageElementId(mixed $fileId): bool
	{
		if (intval($fileId) > 0)
		{
			return true;
		}

		// newly attached files has 'n' prefix before file id. example: 'n150'
		return preg_match('/^n\d+$/', $fileId);
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

	protected static function checkUfFields($object, $ufdata, $result)
	{
		if (!static::$isCheckUserFields)
		{
			static::$isCheckUserFields = true;
			return;
		}

		parent::checkUfFields($object, $ufdata, $result);
	}
}
