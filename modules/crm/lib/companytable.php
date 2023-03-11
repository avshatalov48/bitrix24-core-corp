<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\CompanySettings;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Entity;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\EntityError;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\Relations\CascadePolicy;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Objectify\State;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\UserTable;

Loc::loadMessages(Path::combine(__DIR__, 'company.php'));

/**
 * Class CompanyTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Company_Query query()
 * @method static EO_Company_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Company_Result getById($id)
 * @method static EO_Company_Result getList(array $parameters = array())
 * @method static EO_Company_Entity getEntity()
 * @method static \Bitrix\Crm\EO_Company createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_Company_Collection createCollection()
 * @method static \Bitrix\Crm\EO_Company wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_Company_Collection wakeUpCollection($rows)
 */
class CompanyTable extends Entity\DataManager
{
	protected static $isCheckUserFields = true;

	public static function getTableName()
	{
		return 'b_crm_company';
	}

	public static function getUFId()
	{
		return 'CRM_COMPANY';
	}

	public static function getObjectClass()
	{
		return Company::class;
	}

	public static function getMap()
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		$map = [
			//fields here are sorted by b_crm_company columns order in install.sql. Please, keep it that way

			$fieldRepository->getId(),

			$fieldRepository->getCreatedTime('DATE_CREATE', true),

			$fieldRepository->getUpdatedTime('DATE_MODIFY', true),

			$fieldRepository->getCreatedBy('CREATED_BY_ID', true),

			(new Reference(
				'CREATED_BY',
				UserTable::class,
				Join::on('this.CREATED_BY_ID', 'ref.ID'),
			))
				->configureTitle(Loc::getMessage('CRM_COMPANY_ENTITY_CREATED_BY_FIELD'))
			,

			$fieldRepository->getUpdatedBy('MODIFY_BY_ID', true),

			(new Reference(
				'MODIFY_BY',
				UserTable::class,
				Join::on('this.MODIFY_BY_ID', 'ref.ID'),
			))
				->configureTitle(Loc::getMessage('CRM_COMPANY_ENTITY_MODIFY_BY_FIELD'))
			,

			$fieldRepository->getAssigned(),

			(new Reference(
				'ASSIGNED_BY',
				UserTable::class,
				Join::on('this.ASSIGNED_BY_ID', 'ref.ID'),
			))
				->configureTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_ASSIGNED_BY_ID'))
			,

			$fieldRepository->getOpened()
				->configureDefaultValue(fn() => CompanySettings::getCurrent()->getOpenedFlag())
			,

			$fieldRepository->getTitle(),

			(new StringField('LOGO'))
				->configureSize(10)
				->configureTitle(Loc::getMessage('CRM_COMPANY_ENTITY_LOGO_FIELD'))
			,

			$fieldRepository->getAddress(),

			/** @deprecated Addresses are stored separately */
			(new TextField('ADDRESS_LEGAL'))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_COMPANY_ENTITY_ADDRESS_LEGAL_FIELD'))
			,

			/** @deprecated Banking details are stored separately. Used for backwards compatibility only. */
			(new TextField('BANKING_DETAILS'))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_COMPANY_ENTITY_BANKING_DETAILS_FIELD'))
			,

			$fieldRepository->getComments(),

			$fieldRepository->getTypeId('COMPANY_TYPE', StatusTable::ENTITY_ID_COMPANY_TYPE)
				->configureTitle(Loc::getMessage('CRM_COMPANY_ENTITY_COMPANY_TYPE_BY_FIELD'))
			,

			(new Reference(
				'COMPANY_TYPE_BY',
				StatusTable::class,
				Join::on('this.COMPANY_TYPE', 'ref.STATUS_ID')
					->where('ref.ENTITY_ID', StatusTable::ENTITY_ID_COMPANY_TYPE)
				,
			))
				->configureTitle(Loc::getMessage('CRM_COMPANY_ENTITY_COMPANY_TYPE_BY_FIELD'))
			,

			$fieldRepository->getTypeId('INDUSTRY', StatusTable::ENTITY_ID_INDUSTRY)
				->configureTitle(Loc::getMessage('CRM_COMPANY_ENTITY_INDUSTRY_BY_FIELD'))
			,

			(new Reference(
				'INDUSTRY_BY',
				StatusTable::class,
				Join::on('this.INDUSTRY', 'ref.STATUS_ID')
					->where('ref.ENTITY_ID', StatusTable::ENTITY_ID_INDUSTRY)
				,
			))
				->configureTitle(Loc::getMessage('CRM_COMPANY_ENTITY_INDUSTRY_BY_FIELD'))
			,

			(new FloatField('REVENUE'))
				->configureDefaultValue(0)
				->configureTitle(Loc::getMessage('CRM_COMPANY_ENTITY_REVENUE_FIELD'))
			,

			$fieldRepository->getCurrencyId(),

			$fieldRepository->getTypeId('EMPLOYEES', StatusTable::ENTITY_ID_EMPLOYEES)
				->configureTitle(Loc::getMessage('CRM_COMPANY_ENTITY_EMPLOYEES_BY_FIELD'))
			,

			(new Reference(
				'EMPLOYEES_BY',
				StatusTable::class,
				Join::on('this.EMPLOYEES', 'ref.STATUS_ID')
					->where('ref.ENTITY_ID', StatusTable::class)
				,
			)),

			$fieldRepository->getLeadId(),

			$fieldRepository->getWebformId(),

			$fieldRepository->getOriginatorId(),

			$fieldRepository->getOriginId(),

			$fieldRepository->getOriginVersion(),

			$fieldRepository->getHasPhone(),

			$fieldRepository->getHasEmail(),

			$fieldRepository->getHasImol(),

			(new BooleanField('IS_MY_COMPANY'))
				->configureRequired()
				->configureStorageValues('N', 'Y')
				->configureDefaultValue(false)
			,

			$fieldRepository->getSearchContent(),

			$fieldRepository->getCategoryId(Item::FIELD_NAME_CATEGORY_ID, \CCrmOwnerType::Company)
				->configureTitle(Loc::getMessage('CRM_COMMON_CLIENT_CATEGORY'))
			,

			$fieldRepository->getLastActivityBy(),

			$fieldRepository->getLastActivityTime(),

			(new OneToMany('CONTACT_BINDINGS', Binding\ContactCompanyTable::class, 'COMPANY'))
				->configureCascadeDeletePolicy(CascadePolicy::FOLLOW)
			,

			(new Reference(
				'EVENT_RELATION',
				EventRelationsTable::class,
				Join::on('this.ID', 'ref.ENTITY_ID'),
			))
				->configureTitle(Loc::getMessage('CRM_COMPANY_ENTITY_EVENT_RELATION_FIELD'))
			,

			$fieldRepository->getMultifieldValue(
				'EMAIL_HOME',
				\CCrmOwnerType::Company,
				Multifield\Type\Email::ID,
				Multifield\Type\Email::VALUE_TYPE_HOME,
			),

			$fieldRepository->getMultifieldValue(
				'EMAIL_WORK',
				\CCrmOwnerType::Company,
				Multifield\Type\Email::ID,
				Multifield\Type\Email::VALUE_TYPE_WORK,
			),

			$fieldRepository->getMultifieldValue(
				'EMAIL_MAILING',
				\CCrmOwnerType::Company,
				Multifield\Type\Email::ID,
				Multifield\Type\Email::VALUE_TYPE_MAILING,
			),

			$fieldRepository->getMultifieldValue(
				'PHONE_MOBILE',
				\CCrmOwnerType::Company,
				Multifield\Type\Phone::ID,
				Multifield\Type\Phone::VALUE_TYPE_MOBILE,
			),

			$fieldRepository->getMultifieldValue(
				'PHONE_WORK',
				\CCrmOwnerType::Company,
				Multifield\Type\Phone::ID,
				Multifield\Type\Phone::VALUE_TYPE_WORK,
			),

			$fieldRepository->getMultifieldValue(
				'PHONE_MAILING',
				\CCrmOwnerType::Company,
				Multifield\Type\Phone::ID,
				Multifield\Type\Phone::VALUE_TYPE_MAILING,
			),

			$fieldRepository->getMultifieldValueLike(
				'IMOL',
				\CCrmOwnerType::Company,
				Multifield\Type\Im::ID,
				'imol|%%',
			),

			$fieldRepository->getMultifieldValue(
				'EMAIL',
				\CCrmOwnerType::Company,
				Multifield\Type\Email::ID,
			)
				->configureTitle(Loc::getMessage('CRM_COMPANY_ENTITY_EMAIL_FIELD'))
			,

			$fieldRepository->getMultifieldValue(
				'PHONE',
				\CCrmOwnerType::Company,
				Multifield\Type\Phone::ID,
			),
		];

		return array_merge($map, $fieldRepository->getUtm(\CCrmOwnerType::Company));
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

		global $USER_FIELD_MANAGER, $APPLICATION;

		$userId = ($object->authContext && $object->authContext->getUserId())
			? $object->authContext->getUserId()
			: false;
		$ufPrimary = ($object->sysGetState() === State::RAW) ? false : end($object->primary);
		if (
			!$USER_FIELD_MANAGER->CheckFields(
				$object->entity->getUfId(),
				$ufPrimary,
				$ufdata,
				$userId,
				true,
				null,
				$object->getFilteredUserFields()
			)
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
