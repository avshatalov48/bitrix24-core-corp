<?php
namespace Bitrix\Controller;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;

Loc::loadMessages(__FILE__);

/**
 * Class MemberTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> MEMBER_ID string(32) mandatory
 * <li> SECRET_ID string(32) mandatory
 * <li> NAME string(255) mandatory
 * <li> URL string(255) mandatory
 * <li> EMAIL string(255) optional
 * <li> CONTACT_PERSON string(255) optional
 * <li> CONTROLLER_GROUP_ID int mandatory
 * <li> DISCONNECTED bool optional default 'N'
 * <li> SHARED_KERNEL bool optional default 'N'
 * <li> ACTIVE bool optional default 'Y'
 * <li> DATE_ACTIVE_FROM datetime optional
 * <li> DATE_ACTIVE_TO datetime optional
 * <li> SITE_ACTIVE bool optional default 'Y'
 * <li> TIMESTAMP_X datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> MODIFIED_BY int optional
 * <li> DATE_CREATE datetime mandatory
 * <li> CREATED_BY int optional
 * <li> IN_GROUP_FROM datetime optional
 * <li> NOTES string optional
 * <li> COUNTER_FREE_SPACE double optional
 * <li> COUNTER_SITES int optional
 * <li> COUNTER_USERS int optional
 * <li> COUNTER_LAST_AUTH datetime optional
 * <li> COUNTERS_UPDATED datetime optional
 * <li> HOSTNAME string(255) mandatory
 * <li> CONTROLLER_GROUP reference to {@link \Bitrix\Controller\GroupTable}
 * <li> CREATED reference to {@link \Bitrix\Main\UserTable}
 * <li> MODIFIED reference to {@link \Bitrix\Main\UserTable}
 * </ul>
 *
 * @package Bitrix\Controller
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Member_Query query()
 * @method static EO_Member_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Member_Result getById($id)
 * @method static EO_Member_Result getList(array $parameters = array())
 * @method static EO_Member_Entity getEntity()
 * @method static \Bitrix\Controller\EO_Member createObject($setDefaultValues = true)
 * @method static \Bitrix\Controller\EO_Member_Collection createCollection()
 * @method static \Bitrix\Controller\EO_Member wakeUpObject($row)
 * @method static \Bitrix\Controller\EO_Member_Collection wakeUpCollection($rows)
 */

class MemberTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_controller_member';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new Fields\IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('MEMBER_ENTITY_ID_FIELD'),
				]
			),
			new Fields\StringField(
				'MEMBER_ID',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateMemberId'],
					'title' => Loc::getMessage('MEMBER_ENTITY_MEMBER_ID_FIELD'),
				]
			),
			new Fields\StringField(
				'SECRET_ID',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateSecretId'],
					'title' => Loc::getMessage('MEMBER_ENTITY_SECRET_ID_FIELD'),
				]
			),
			new Fields\StringField(
				'NAME',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateName'],
					'title' => Loc::getMessage('MEMBER_ENTITY_NAME_FIELD'),
				]
			),
			new Fields\StringField(
				'URL',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateUrl'],
					'title' => Loc::getMessage('MEMBER_ENTITY_URL_FIELD'),
				]
			),
			new Fields\StringField(
				'HOSTNAME',
				[
					'validation' => [__CLASS__, 'validateHostname'],
					'title' => Loc::getMessage('MEMBER_ENTITY_HOSTNAME_FIELD'),
				]
			),
			new Fields\StringField(
				'EMAIL',
				[
					'validation' => [__CLASS__, 'validateEmail'],
					'title' => Loc::getMessage('MEMBER_ENTITY_EMAIL_FIELD'),
				]
			),
			new Fields\StringField(
				'CONTACT_PERSON',
				[
					'validation' => [__CLASS__, 'validateContactPerson'],
					'title' => Loc::getMessage('MEMBER_ENTITY_CONTACT_PERSON_FIELD'),
				]
			),
			new Fields\IntegerField(
				'CONTROLLER_GROUP_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('MEMBER_ENTITY_CONTROLLER_GROUP_ID_FIELD'),
				]
			),
			new Fields\BooleanField(
				'DISCONNECTED',
				[
					'values' => ['N', 'Y'],
					'default' => 'N',
					'title' => Loc::getMessage('MEMBER_ENTITY_DISCONNECTED_FIELD'),
				]
			),
			new Fields\BooleanField(
				'SHARED_KERNEL',
				[
					'values' => ['N', 'Y'],
					'default' => 'N',
					'title' => Loc::getMessage('MEMBER_ENTITY_SHARED_KERNEL_FIELD'),
				]
			),
			new Fields\BooleanField(
				'ACTIVE',
				[
					'values' => ['N', 'Y'],
					'default' => 'Y',
					'title' => Loc::getMessage('MEMBER_ENTITY_ACTIVE_FIELD'),
				]
			),
			new Fields\DatetimeField(
				'DATE_ACTIVE_FROM',
				[
					'title' => Loc::getMessage('MEMBER_ENTITY_DATE_ACTIVE_FROM_FIELD'),
				]
			),
			new Fields\DatetimeField(
				'DATE_ACTIVE_TO',
				[
					'title' => Loc::getMessage('MEMBER_ENTITY_DATE_ACTIVE_TO_FIELD'),
				]
			),
			new Fields\BooleanField(
				'SITE_ACTIVE',
				[
					'values' => ['N', 'Y'],
					'default' => 'Y',
					'title' => Loc::getMessage('MEMBER_ENTITY_SITE_ACTIVE_FIELD'),
				]
			),
			new Fields\DatetimeField(
				'TIMESTAMP_X',
				[
					'required' => true,
					'title' => Loc::getMessage('MEMBER_ENTITY_TIMESTAMP_X_FIELD'),
				]
			),
			new Fields\IntegerField(
				'MODIFIED_BY',
				[
					'title' => Loc::getMessage('MEMBER_ENTITY_MODIFIED_BY_FIELD'),
				]
			),
			new Fields\DatetimeField(
				'DATE_CREATE',
				[
					'required' => true,
					'title' => Loc::getMessage('MEMBER_ENTITY_DATE_CREATE_FIELD'),
				]
			),
			new Fields\IntegerField(
				'CREATED_BY',
				[
					'title' => Loc::getMessage('MEMBER_ENTITY_CREATED_BY_FIELD'),
				]
			),
			new Fields\DatetimeField(
				'IN_GROUP_FROM',
				[
					'title' => Loc::getMessage('MEMBER_ENTITY_IN_GROUP_FROM_FIELD'),
				]
			),
			new Fields\TextField(
				'NOTES',
				[
					'title' => Loc::getMessage('MEMBER_ENTITY_NOTES_FIELD'),
				]
			),
			new Fields\FloatField(
				'COUNTER_FREE_SPACE',
				[
					'title' => Loc::getMessage('MEMBER_ENTITY_COUNTER_FREE_SPACE_FIELD'),
				]
			),
			new Fields\IntegerField(
				'COUNTER_SITES',
				[
					'title' => Loc::getMessage('MEMBER_ENTITY_COUNTER_SITES_FIELD'),
				]
			),
			new Fields\IntegerField(
				'COUNTER_USERS',
				[
					'title' => Loc::getMessage('MEMBER_ENTITY_COUNTER_USERS_FIELD'),
				]
			),
			new Fields\DatetimeField(
				'COUNTER_LAST_AUTH',
				[
					'title' => Loc::getMessage('MEMBER_ENTITY_COUNTER_LAST_AUTH_FIELD'),
				]
			),
			new Fields\DatetimeField(
				'COUNTERS_UPDATED',
				[
					'title' => Loc::getMessage('MEMBER_ENTITY_COUNTERS_UPDATED_FIELD'),
				]
			),
			new Fields\Relations\Reference(
				'CONTROLLER_GROUP',
				'\Bitrix\Controller\ControllerGroup',
				['=this.CONTROLLER_GROUP_ID' => 'ref.ID']
			),
			new Fields\Relations\Reference(
				'CREATED',
				'Bitrix\Main\UserTable',
				['=this.CREATED_BY' => 'ref.ID']
			),
			new Fields\Relations\Reference(
				'MODIFIED',
				'Bitrix\Main\UserTable',
				['=this.MODIFIED_BY' => 'ref.ID']
			),
		];
	}

	/**
	 * Returns validators for MEMBER_ID field.
	 *
	 * @return array
	 */
	public static function validateMemberId()
	{
		return [
			new Fields\Validators\LengthValidator(null, 32),
		];
	}

	/**
	 * Returns validators for SECRET_ID field.
	 *
	 * @return array
	 */
	public static function validateSecretId()
	{
		return [
			new Fields\Validators\LengthValidator(null, 32),
		];
	}

	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	public static function validateName()
	{
		return [
			new Fields\Validators\LengthValidator(null, 255),
		];
	}

	/**
	 * Returns validators for URL field.
	 *
	 * @return array
	 */
	public static function validateUrl()
	{
		return [
			new Fields\Validators\LengthValidator(null, 255),
		];
	}

	/**
	 * Returns validators for EMAIL field.
	 *
	 * @return array
	 */
	public static function validateEmail()
	{
		return [
			new Fields\Validators\LengthValidator(null, 255),
		];
	}

	/**
	 * Returns validators for CONTACT_PERSON field.
	 *
	 * @return array
	 */
	public static function validateContactPerson()
	{
		return [
			new Fields\Validators\LengthValidator(null, 255),
		];
	}

	/**
	 * Returns validators for HOSTNAME field.
	 *
	 * @return array
	 */
	public static function validateHostname()
	{
		return [
			new Fields\Validators\LengthValidator(null, 255),
		];
	}
}
