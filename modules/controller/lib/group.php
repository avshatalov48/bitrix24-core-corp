<?php
namespace Bitrix\Controller;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;

Loc::loadMessages(__FILE__);

/**
 * Class GroupTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TIMESTAMP_X datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> NAME string(255) mandatory
 * <li> UPDATE_PERIOD int mandatory default -1
 * <li> DISABLE_DEACTIVATED bool optional default 'N'
 * <li> DESCRIPTION string optional
 * <li> MODIFIED_BY int optional
 * <li> DATE_CREATE datetime mandatory
 * <li> CREATED_BY int optional
 * <li> INSTALL_INFO string optional
 * <li> UNINSTALL_INFO string optional
 * <li> INSTALL_PHP string optional
 * <li> UNINSTALL_PHP string optional
 * <li> TRIAL_PERIOD int optional
 * <li> COUNTER_UPDATE_PERIOD int optional
 * <li> CHECK_COUNTER_FREE_SPACE string(1) optional
 * <li> CHECK_COUNTER_SITES string(1) optional
 * <li> CHECK_COUNTER_USERS string(1) optional
 * <li> CHECK_COUNTER_LAST_AUTH string(1) optional
 * <li> CREATED reference to {@link \Bitrix\Main\UserTable}
 * <li> MODIFIED reference to {@link \Bitrix\Main\UserTable}
 * </ul>
 *
 * @package Bitrix\Controller
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Group_Query query()
 * @method static EO_Group_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Group_Result getById($id)
 * @method static EO_Group_Result getList(array $parameters = array())
 * @method static EO_Group_Entity getEntity()
 * @method static \Bitrix\Controller\EO_Group createObject($setDefaultValues = true)
 * @method static \Bitrix\Controller\EO_Group_Collection createCollection()
 * @method static \Bitrix\Controller\EO_Group wakeUpObject($row)
 * @method static \Bitrix\Controller\EO_Group_Collection wakeUpCollection($rows)
 */

class GroupTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_controller_group';
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
					'title' => Loc::getMessage('GROUP_ENTITY_ID_FIELD'),
				]
			),
			new Fields\DatetimeField(
				'TIMESTAMP_X',
				[
					'required' => true,
					'title' => Loc::getMessage('GROUP_ENTITY_TIMESTAMP_X_FIELD'),
				]
			),
			new Fields\StringField(
				'NAME',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateName'],
					'title' => Loc::getMessage('GROUP_ENTITY_NAME_FIELD'),
				]
			),
			new Fields\IntegerField(
				'UPDATE_PERIOD',
				[
					'default' => -1,
					'title' => Loc::getMessage('GROUP_ENTITY_UPDATE_PERIOD_FIELD'),
				]
			),
			new Fields\BooleanField(
				'DISABLE_DEACTIVATED',
				[
					'values' => ['N', 'Y'],
					'default' => 'N',
					'title' => Loc::getMessage('GROUP_ENTITY_DISABLE_DEACTIVATED_FIELD'),
				]
			),
			new Fields\TextField(
				'DESCRIPTION',
				[
					'title' => Loc::getMessage('GROUP_ENTITY_DESCRIPTION_FIELD'),
				]
			),
			new Fields\IntegerField(
				'MODIFIED_BY',
				[
					'title' => Loc::getMessage('GROUP_ENTITY_MODIFIED_BY_FIELD'),
				]
			),
			new Fields\DatetimeField(
				'DATE_CREATE',
				[
					'required' => true,
					'title' => Loc::getMessage('GROUP_ENTITY_DATE_CREATE_FIELD'),
				]
			),
			new Fields\IntegerField(
				'CREATED_BY',
				[
					'title' => Loc::getMessage('GROUP_ENTITY_CREATED_BY_FIELD'),
				]
			),
			new Fields\TextField(
				'INSTALL_INFO',
				[
					'title' => Loc::getMessage('GROUP_ENTITY_INSTALL_INFO_FIELD'),
				]
			),
			new Fields\TextField(
				'UNINSTALL_INFO',
				[
					'title' => Loc::getMessage('GROUP_ENTITY_UNINSTALL_INFO_FIELD'),
				]
			),
			new Fields\TextField(
				'INSTALL_PHP',
				[
					'title' => Loc::getMessage('GROUP_ENTITY_INSTALL_PHP_FIELD'),
				]
			),
			new Fields\TextField(
				'UNINSTALL_PHP',
				[
					'title' => Loc::getMessage('GROUP_ENTITY_UNINSTALL_PHP_FIELD'),
				]
			),
			new Fields\IntegerField(
				'TRIAL_PERIOD',
				[
					'title' => Loc::getMessage('GROUP_ENTITY_TRIAL_PERIOD_FIELD'),
				]
			),
			new Fields\IntegerField(
				'COUNTER_UPDATE_PERIOD',
				[
					'title' => Loc::getMessage('GROUP_ENTITY_COUNTER_UPDATE_PERIOD_FIELD'),
				]
			),
			new Fields\StringField(
				'CHECK_COUNTER_FREE_SPACE',
				[
					'validation' => [__CLASS__, 'validateCheckCounterFreeSpace'],
					'title' => Loc::getMessage('GROUP_ENTITY_CHECK_COUNTER_FREE_SPACE_FIELD'),
				]
			),
			new Fields\StringField(
				'CHECK_COUNTER_SITES',
				[
					'validation' => [__CLASS__, 'validateCheckCounterSites'],
					'title' => Loc::getMessage('GROUP_ENTITY_CHECK_COUNTER_SITES_FIELD'),
				]
			),
			new Fields\StringField(
				'CHECK_COUNTER_USERS',
				[
					'validation' => [__CLASS__, 'validateCheckCounterUsers'],
					'title' => Loc::getMessage('GROUP_ENTITY_CHECK_COUNTER_USERS_FIELD'),
				]
			),
			new Fields\StringField(
				'CHECK_COUNTER_LAST_AUTH',
				[
					'validation' => [__CLASS__, 'validateCheckCounterLastAuth'],
					'title' => Loc::getMessage('GROUP_ENTITY_CHECK_COUNTER_LAST_AUTH_FIELD'),
				]
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
	 * Returns validators for CHECK_COUNTER_FREE_SPACE field.
	 *
	 * @return array
	 */
	public static function validateCheckCounterFreeSpace()
	{
		return [
			new Fields\Validators\LengthValidator(null, 1),
		];
	}

	/**
	 * Returns validators for CHECK_COUNTER_SITES field.
	 *
	 * @return array
	 */
	public static function validateCheckCounterSites()
	{
		return [
			new Fields\Validators\LengthValidator(null, 1),
		];
	}

	/**
	 * Returns validators for CHECK_COUNTER_USERS field.
	 *
	 * @return array
	 */
	public static function validateCheckCounterUsers()
	{
		return [
			new Fields\Validators\LengthValidator(null, 1),
		];
	}

	/**
	 * Returns validators for CHECK_COUNTER_LAST_AUTH field.
	 *
	 * @return array
	 */
	public static function validateCheckCounterLastAuth()
	{
		return [
			new Fields\Validators\LengthValidator(null, 1),
		];
	}
}
