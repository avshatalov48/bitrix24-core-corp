<?php
namespace Bitrix\Controller;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;

Loc::loadMessages(__FILE__);

/**
 * Class AuthGrantTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TIMESTAMP_X datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> GRANTED_BY int mandatory
 * <li> CONTROLLER_MEMBER_ID int mandatory
 * <li> GRANTEE_USER_ID int optional
 * <li> GRANTEE_GROUP_ID int optional
 * <li> ACTIVE bool optional default 'Y'
 * <li> SCOPE string(20) mandatory
 * <li> DATE_START datetime optional
 * <li> DATE_END datetime optional
 * <li> NOTE string(255) optional
 * <li> CONTROLLER_MEMBER reference to {@link \Bitrix\Controller\MemberTable}
 * <li> GRANTED reference to {@link \Bitrix\Main\UserTable}
 * <li> GRANTEE_USER reference to {@link \Bitrix\Main\UserTable}
 * <li> GRANTEE_GROUP reference to {@link \Bitrix\Main\GroupTable}
 * </ul>
 *
 * @package Bitrix\Controller
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_AuthGrant_Query query()
 * @method static EO_AuthGrant_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_AuthGrant_Result getById($id)
 * @method static EO_AuthGrant_Result getList(array $parameters = array())
 * @method static EO_AuthGrant_Entity getEntity()
 * @method static \Bitrix\Controller\EO_AuthGrant createObject($setDefaultValues = true)
 * @method static \Bitrix\Controller\EO_AuthGrant_Collection createCollection()
 * @method static \Bitrix\Controller\EO_AuthGrant wakeUpObject($row)
 * @method static \Bitrix\Controller\EO_AuthGrant_Collection wakeUpCollection($rows)
 */

class AuthGrantTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_controller_auth_grant';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		return [
			new Fields\IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('AUTH_GRANT_ENTITY_ID_FIELD'),
				]
			),
			new Fields\DatetimeField(
				'TIMESTAMP_X',
				[
					'required' => true,
					'title' => Loc::getMessage('AUTH_GRANT_ENTITY_TIMESTAMP_X_FIELD'),
				]
			),
			new Fields\IntegerField(
				'GRANTED_BY',
				[
					'required' => true,
					'title' => Loc::getMessage('AUTH_GRANT_ENTITY_GRANTED_BY_FIELD'),
				]
			),
			new Fields\IntegerField(
				'CONTROLLER_MEMBER_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('AUTH_GRANT_ENTITY_CONTROLLER_MEMBER_ID_FIELD'),
				]
			),
			new Fields\IntegerField(
				'GRANTEE_USER_ID',
				[
					'title' => Loc::getMessage('AUTH_GRANT_ENTITY_GRANTEE_USER_ID_FIELD'),
				]
			),
			new Fields\IntegerField(
				'GRANTEE_GROUP_ID',
				[
					'title' => Loc::getMessage('AUTH_GRANT_ENTITY_GRANTEE_GROUP_ID_FIELD'),
				]
			),
			new Fields\BooleanField(
				'ACTIVE',
				[
					'values' => ['N', 'Y'],
					'default' => 'Y',
					'title' => Loc::getMessage('AUTH_GRANT_ENTITY_ACTIVE_FIELD'),
				]
			),
			new Fields\StringField(
				'SCOPE',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateScope'],
					'title' => Loc::getMessage('AUTH_GRANT_ENTITY_SCOPE_FIELD'),
				]
			),
			new Fields\DatetimeField(
				'DATE_START',
				[
					'title' => Loc::getMessage('AUTH_GRANT_ENTITY_DATE_START_FIELD'),
				]
			),
			new Fields\DatetimeField(
				'DATE_END',
				[
					'title' => Loc::getMessage('AUTH_GRANT_ENTITY_DATE_END_FIELD'),
				]
			),
			new Fields\StringField(
				'NOTE',
				[
					'validation' => [__CLASS__, 'validateNote'],
					'title' => Loc::getMessage('AUTH_GRANT_ENTITY_NOTE_FIELD'),
				]
			),
			new Fields\Relations\Reference(
				'CONTROLLER_MEMBER',
				'Bitrix\Controller\MemberTable',
				['=this.CONTROLLER_MEMBER_ID' => 'ref.ID']
			),
			new Fields\Relations\Reference(
				'GRANTED',
				'Bitrix\Main\UserTable',
				['=this.GRANTED_BY' => 'ref.ID']
			),
			new Fields\ExpressionField(
				'GRANTED_NAME',
				$helper->getConcatFunction("'('", '%s'," ') '", '%s', "' '", '%s'),
				['GRANTED.LOGIN', 'GRANTED.NAME', 'GRANTED.LAST_NAME']
			),
			new Fields\Relations\Reference(
				'GRANTEE_USER',
				'Bitrix\Main\UserTable',
				['=this.GRANTEE_USER_ID' => 'ref.ID']
			),
			new Fields\ExpressionField(
				'GRANTEE_USER_NAME',
				$helper->getConcatFunction("'('", '%s'," ') '", '%s', "' '", '%s'),
				['GRANTEE_USER.LOGIN', 'GRANTEE_USER.NAME', 'GRANTEE_USER.LAST_NAME']
			),
			new Fields\Relations\Reference(
				'GRANTEE_GROUP',
				'Bitrix\Main\GroupTable',
				['=this.GRANTEE_GROUP_ID' => 'ref.ID']
			),
			new Fields\ExpressionField(
				'GRANTEE_GROUP_NAME',
				$helper->getConcatFunction("'['", '%s'," '] '", '%s'),
				['GRANTEE_GROUP.ID', 'GRANTEE_GROUP.NAME'],
			),
		];
	}

	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	public static function validateScope()
	{
		return [
			new Fields\Validators\LengthValidator(null, 20),
		];
	}

	/**
	 * Returns validators for NOTE field.
	 *
	 * @return array
	 */
	public static function validateNote()
	{
		return [
			new Fields\Validators\LengthValidator(null, 255),
		];
	}

	/**
	 * Returns list of grants given to the $granteeUserId on $controllerMemberId.
	 * If $granteeGroups provided, then checks users groups as well.
	 * It is recommended to use \Bitrix\Controller\AuthGrantTable::getControllerMemberScopes instead.
	 *
	 * @param int $controllerMemberId Member identifier.
	 * @param int $granteeUserId User identifier.
	 * @param array[] $granteeGroups Optional array of user groups.
	 * @return \Bitrix\Main\DB\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @see \Bitrix\Controller\AuthGrantTable::getControllerMemberScopes
	 */
	public static function getActiveForControllerMember($controllerMemberId, $granteeUserId, $granteeGroups = [])
	{
		$filter = [
			'=CONTROLLER_MEMBER_ID' => $controllerMemberId,
			'=ACTIVE' => 'Y',
			[
				'LOGIC' => 'OR',
				'=DATE_START' => false,
				'<=DATE_START' => new \Bitrix\Main\Type\DateTime(),
			],
			[
				'LOGIC' => 'OR',
				'=DATE_END' => false,
				'>=DATE_END' => new \Bitrix\Main\Type\DateTime(),
			],
			'!=GRANTED_BY' => $granteeUserId,
		];

		if (is_array($granteeGroups) && $granteeGroups)
		{
			$filter[] = [
				'LOGIC' => 'OR',
				'=GRANTEE_USER.ID' => $granteeUserId,
				'@GRANTEE_GROUP_ID' => $granteeGroups,
			];
		}
		else
		{
			$filter['=GRANTEE_USER.ID'] = $granteeUserId;
		}

		return self::getList([
			'select' => ['ID', 'SCOPE'],
			'filter' => $filter,
			'order' => ['ID' => 'asc'],
		]);
	}

	/**
	 * Returns array of grants given to the $granteeUserId on $controllerMemberId.
	 * If $granteeGroups provided, then checks users groups as well.
	 * Fires event OnControllerMemberScopes to add/delete scopes.
	 *
	 * @param int $controllerMemberId Member identifier.
	 * @param int $granteeUserId User identifier.
	 * @param array[] $granteeGroups Optional array of user groups.
	 * @return \Bitrix\Main\DB\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @see \Bitrix\Controller\AuthGrantTable::getActiveForControllerMember
	 */
	public static  function getControllerMemberScopes($controllerMemberId, $granteeUserId, $granteeGroups = [])
	{
		$result = [];
		$grantList = self::getActiveForControllerMember($controllerMemberId, $granteeUserId, $granteeGroups);
		while ($authGrant = $grantList->fetch())
		{
			$result[] = $authGrant;
		}
		$event = new \Bitrix\Main\Event('controller', 'OnControllerMemberScopes', [&$result, $controllerMemberId, $granteeUserId, $granteeGroups]);
		$event->send();
		return $result;
	}

	/**
	 * Returns array of users who can get a grant on a member.
	 * This users must have controller_member_view operation.
	 *
	 * @param int $currentUserId Identifier of the current user.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getGranteeUserList($currentUserId)
	{
		$tasks = [];
		$groups = [];
		$users = [];

		$tasksList = \Bitrix\Main\TaskOperationTable::getList([
			'select' => ['TASK_ID'],
			'filter' => [
				'=OPERATION.NAME' => 'controller_member_view',
			],
		]);
		while ($a = $tasksList->fetch())
		{
			$tasks[$a['TASK_ID']] = $a['TASK_ID'];
		}

		if ($tasks)
		{
			$groupsList = \Bitrix\Main\GroupTaskTable::getList([
				'select' => ['GROUP_ID'],
				'filter' => [
					'=TASK_ID' => $tasks,
				],
			]);
			while ($a = $groupsList->fetch())
			{
				$groups[$a['GROUP_ID']] = $a['GROUP_ID'];
			}
		}

		if ($groups)
		{
			$usersList = \Bitrix\Main\UserGroupTable::getList([
				'select' => [
					'ID' => 'USER.ID',
					'LOGIN' => 'USER.LOGIN',
					'NAME' => 'USER.NAME',
					'LAST_NAME' => 'USER.LAST_NAME',
				],
				'filter' => [
					'=GROUP_ID' => $groups,
					[
						'LOGIC' => 'OR',
						'=DATE_ACTIVE_FROM' => false,
						'<=DATE_ACTIVE_FROM' => new \Bitrix\Main\Type\DateTime(),
					],
					[
						'LOGIC' => 'OR',
						'=DATE_ACTIVE_TO' => false,
						'>=DATE_ACTIVE_TO' => new \Bitrix\Main\Type\DateTime(),
					],
				],
			]);
			while ($a = $usersList->fetch())
			{
				if ($a['ID'] != $currentUserId)
				{
					$users[$a['ID']] = $a['LAST_NAME'] . ' ' . $a['NAME'] . ' (' . $a['LOGIN'] . ')';
				}
			}
		}

		asort($users);
		return $users;
	}
}
