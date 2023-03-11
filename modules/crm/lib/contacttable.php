<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Crm\Multifield;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\ContactSettings;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\EntityError;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\CascadePolicy;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Objectify\State;
use Bitrix\Main\UserTable;

Loc::loadMessages(Path::combine(__DIR__, 'contact.php'));

/**
 * Class ContactTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Contact_Query query()
 * @method static EO_Contact_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Contact_Result getById($id)
 * @method static EO_Contact_Result getList(array $parameters = array())
 * @method static EO_Contact_Entity getEntity()
 * @method static \Bitrix\Crm\Contact createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_Contact_Collection createCollection()
 * @method static \Bitrix\Crm\Contact wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_Contact_Collection wakeUpCollection($rows)
 */
class ContactTable extends ORM\Data\DataManager
{
	protected static $isCheckUserFields = true;

	public static function getTableName()
	{
		return 'b_crm_contact';
	}

	public static function getUFId()
	{
		return 'CRM_CONTACT';
	}

	public static function getObjectClass()
	{
		return Contact::class;
	}

	public static function getMap()
	{
		Container::getInstance()->getLocalization()->loadMessages();

		$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		$map = [
			//fields here are sorted by b_crm_contact columns order in install.sql. Please, keep it that way

			$fieldRepository->getId(),

			$fieldRepository->getCreatedTime('DATE_CREATE'),

			$fieldRepository->getUpdatedTime('DATE_MODIFY'),

			$fieldRepository->getCreatedBy('CREATED_BY_ID'),

			(new Reference(
				'CREATED_BY',
				UserTable::class,
				Join::on('this.CREATED_BY_ID', 'ref.ID'),
			))
				->configureTitle(Loc::getMessage('CRM_CONTACT_ENTITY_CREATED_BY_FIELD'))
			,

			$fieldRepository->getUpdatedBy('MODIFY_BY_ID'),

			(new Reference(
				'MODIFY_BY',
				UserTable::class,
				Join::on('this.MODIFY_BY_ID', 'ref.ID'),
			))
				->configureTitle(Loc::getMessage('CRM_CONTACT_ENTITY_MODIFY_BY_FIELD'))
			,

			$fieldRepository->getAssigned(),

			(new Reference(
				'ASSIGNED_BY',
				UserTable::class,
				Join::on('this.ASSIGNED_BY_ID', 'ref.ID'),
			))
				->configureTitle(Loc::getMessage('CRM_CONTACT_ENTITY_ASSIGNED_BY_FIELD'))
			,

			$fieldRepository->getOpened()
				->configureDefaultValue(static function (): bool {
					return ContactSettings::getCurrent()->getOpenedFlag();
				})
			,

			$fieldRepository->getCompanyId(),

			(new Reference(
				'COMPANY',
				CompanyTable::class,
				Join::on('this.COMPANY_ID', 'ref.ID'),
			))
				->configureTitle(\CCrmOwnerType::GetDescription(\CCrmOwnerType::Company))
			,

			$fieldRepository->getSourceId(),

			$fieldRepository->getSourceBy(),

			$fieldRepository->getSourceDescription(),

			$fieldRepository->getFullName(),

			$fieldRepository->getName(),

			$fieldRepository->getLastName(),

			$fieldRepository->getSecondName(),

			$fieldRepository->getShortName()
				->configureTitle(Loc::getMessage('CRM_CONTACT_ENTITY_SHORT_NAME_FIELD'))
			,

			(new IntegerField('PHOTO'))
				->configureNullable()
				->configureTitle(Loc::getMessage('CRM_CONTACT_ENTITY_PHOTO_FIELD'))
			,

			$fieldRepository->getPost(),

			$fieldRepository->getAddress(),

			$fieldRepository->getComments(),

			$fieldRepository->getLeadId(),

			(new BooleanField('EXPORT'))
				->configureStorageValues('N', 'Y')
				->configureDefaultValue(true)
				->configureTitle(Loc::getMessage('CRM_CONTACT_ENTITY_EXPORT_FIELD'))
			,

			$fieldRepository->getTypeId(Item::FIELD_NAME_TYPE_ID, StatusTable::ENTITY_ID_CONTACT_TYPE)
				->configureTitle(Loc::getMessage('CRM_CONTACT_ENTITY_TYPE_BY_FIELD'))
			,

			(new Reference(
				'TYPE_BY',
				StatusTable::class,
				Join::on('this.TYPE_ID', 'ref.STATUS_ID')
					->where('ref.ENTITY_ID', '=', StatusTable::ENTITY_ID_CONTACT_TYPE)
				,
			))
				->configureTitle(Loc::getMessage('CRM_CONTACT_ENTITY_TYPE_BY_FIELD'))
			,

			$fieldRepository->getWebformId(),

			$fieldRepository->getOriginatorId(),

			$fieldRepository->getOriginId(),

			$fieldRepository->getOriginVersion(),

			$fieldRepository->getBirthdate(),

			$fieldRepository->getBirthdaySort(),

			$fieldRepository->getHonorific(),

			$fieldRepository->getHasPhone(),

			$fieldRepository->getHasEmail(),

			$fieldRepository->getHasImol(),

			$fieldRepository->getFaceId(),

			$fieldRepository->getSearchContent(),

			$fieldRepository->getCategoryId(Item::FIELD_NAME_CATEGORY_ID, \CCrmOwnerType::Contact)
				->configureTitle(Loc::getMessage('CRM_COMMON_CLIENT_CATEGORY'))
			,

			$fieldRepository->getLastActivityBy(),

			$fieldRepository->getLastActivityTime(),

			(new OneToMany(
				'COMPANY_BINDINGS',
				Binding\ContactCompanyTable::class,
				'CONTACT'
			))
				->configureCascadeDeletePolicy(CascadePolicy::FOLLOW)
			,

			(new ExpressionField(
				'LOGIN',
				'NULL'
			))
				->configureValueType(StringField::class)
			,

			(new Reference(
				'EVENT_RELATION',
				EventRelationsTable::class,
				Join::on('this.ID', 'ref.ENTITY_ID'),
			))
				->configureTitle(Loc::getMessage('CRM_CONTACT_ENTITY_EVENT_RELATION_FIELD'))
			,

			$fieldRepository->getMultifieldValue(
				'EMAIL_HOME',
				\CCrmOwnerType::Contact,
				Multifield\Type\Email::ID,
				Multifield\Type\Email::VALUE_TYPE_HOME,
			),

			$fieldRepository->getMultifieldValue(
				'EMAIL_WORK',
				\CCrmOwnerType::Contact,
				Multifield\Type\Email::ID,
				Multifield\Type\Email::VALUE_TYPE_WORK,
			),

			$fieldRepository->getMultifieldValue(
				'EMAIL_MAILING',
				\CCrmOwnerType::Contact,
				Multifield\Type\Email::ID,
				Multifield\Type\Email::VALUE_TYPE_MAILING,
			),

			$fieldRepository->getMultifieldValue(
				'PHONE_MOBILE',
				\CCrmOwnerType::Contact,
				Multifield\Type\Phone::ID,
				Multifield\Type\Phone::VALUE_TYPE_MOBILE,
			),

			$fieldRepository->getMultifieldValue(
				'PHONE_WORK',
				\CCrmOwnerType::Contact,
				Multifield\Type\Phone::ID,
				Multifield\Type\Phone::VALUE_TYPE_WORK,
			),

			$fieldRepository->getMultifieldValue(
				'PHONE_MAILING',
				\CCrmOwnerType::Contact,
				Multifield\Type\Phone::ID,
				Multifield\Type\Phone::VALUE_TYPE_MAILING,
			),

			$fieldRepository->getMultifieldValueLike(
				'IMOL',
				\CCrmOwnerType::Contact,
				Multifield\Type\Im::ID,
				'imol|%%'
			),

			$fieldRepository->getMultifieldValue(
				'EMAIL',
				\CCrmOwnerType::Contact,
				Multifield\Type\Email::ID,
			)
				->configureTitle(Loc::getMessage('CRM_CONTACT_ENTITY_EMAIL_FIELD'))
			,

			$fieldRepository->getMultifieldValue(
				'PHONE',
				\CCrmOwnerType::Contact,
				Multifield\Type\Phone::ID,
			)
				->configureTitle(Loc::getMessage('CRM_CONTACT_ENTITY_PHONE_FIELD'))
			,
		];

		return array_merge($map, $fieldRepository->getUtm(\CCrmOwnerType::Contact));
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
