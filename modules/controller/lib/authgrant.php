<?php
namespace Bitrix\Controller;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
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

class AuthGrantTable extends Main\Entity\DataManager
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
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('AUTH_GRANT_ENTITY_ID_FIELD'),
			),
			'TIMESTAMP_X' => array(
				'data_type' => 'datetime',
				'required' => true,
				'title' => Loc::getMessage('AUTH_GRANT_ENTITY_TIMESTAMP_X_FIELD'),
			),
			'GRANTED_BY' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('AUTH_GRANT_ENTITY_GRANTED_BY_FIELD'),
			),
			'CONTROLLER_MEMBER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('AUTH_GRANT_ENTITY_CONTROLLER_MEMBER_ID_FIELD'),
			),
			'GRANTEE_USER_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('AUTH_GRANT_ENTITY_GRANTEE_USER_ID_FIELD'),
			),
			'GRANTEE_GROUP_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('AUTH_GRANT_ENTITY_GRANTEE_GROUP_ID_FIELD'),
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('AUTH_GRANT_ENTITY_ACTIVE_FIELD'),
			),
			'SCOPE' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateScope'),
				'title' => Loc::getMessage('AUTH_GRANT_ENTITY_SCOPE_FIELD'),
			),
			'DATE_START' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('AUTH_GRANT_ENTITY_DATE_START_FIELD'),
			),
			'DATE_END' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('AUTH_GRANT_ENTITY_DATE_END_FIELD'),
			),
			'NOTE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateNote'),
				'title' => Loc::getMessage('AUTH_GRANT_ENTITY_NOTE_FIELD'),
			),
			'CONTROLLER_MEMBER' => array(
				'data_type' => 'Bitrix\Controller\MemberTable',
				'reference' => array('=this.CONTROLLER_MEMBER_ID' => 'ref.ID'),
			),
			'GRANTED' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.GRANTED_BY' => 'ref.ID'),
			),
			'GRANTED_NAME' => array(
				'data_type' => 'string',
				'expression' => array(
					$helper->getConcatFunction("'('", "%s"," ') '", "%s", "' '", "%s"),
					'GRANTED.LOGIN', 'GRANTED.NAME', 'GRANTED.LAST_NAME'
				),
			),
			'GRANTEE_USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.GRANTEE_USER_ID' => 'ref.ID'),
			),
			'GRANTEE_USER_NAME' => array(
				'data_type' => 'string',
				'expression' => array(
					$helper->getConcatFunction("'('", "%s"," ') '", "%s", "' '", "%s"),
					'GRANTEE_USER.LOGIN', 'GRANTEE_USER.NAME', 'GRANTEE_USER.LAST_NAME'
				),
			),
			'GRANTEE_GROUP' => array(
				'data_type' => 'Bitrix\Main\GroupTable',
				'reference' => array('=this.GRANTEE_GROUP_ID' => 'ref.ID'),
			),
			'GRANTEE_GROUP_NAME' => array(
				'data_type' => 'string',
				'expression' => array(
					$helper->getConcatFunction("'['", "%s"," '] '", "%s"),
					'GRANTEE_GROUP.ID', 'GRANTEE_GROUP.NAME'
				),
			),
		);
	}
	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	public static function validateScope()
	{
		return array(
			new Main\Entity\Validator\Length(null, 20),
		);
	}

	/**
	 * Returns validators for NOTE field.
	 *
	 * @return array
	 */
	public static function validateNote()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns list of grants given to the $granteeUserId on $controllerMemberId.
	 * If $granteeGroups provided, then checks users groups as well.
	 * It is recommended to use \Bitrix\Controller\AuthGrantTable::getControllerMemberScopes instead.
	 *
	 * @param integer $controllerMemberId Member identifier.
	 * @param integer $granteeUserId User identifier.
	 * @param array[] $granteeGroups Optional array of user groups.
	 * @return \Bitrix\Main\DB\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @see \Bitrix\Controller\AuthGrantTable::getControllerMemberScopes
	 */
	public static function getActiveForControllerMember($controllerMemberId, $granteeUserId, $granteeGroups = array())
	{
		$filter = array(
			"=CONTROLLER_MEMBER_ID" => $controllerMemberId,
			"=ACTIVE" => "Y",
			array(
				"LOGIC" => "OR",
				"=DATE_START" => false,
				"<=DATE_START" => new \Bitrix\Main\Type\DateTime(),
			),
			array(
				"LOGIC" => "OR",
				"=DATE_END" => false,
				">=DATE_END" => new \Bitrix\Main\Type\DateTime(),
			),
			"!=GRANTED_BY" => $granteeUserId,
		);

		if (is_array($granteeGroups) && $granteeGroups)
		{
			$filter[] = array(
				"LOGIC" => "OR",
				"=GRANTEE_USER.ID" => $granteeUserId,
				"@GRANTEE_GROUP_ID" => $granteeGroups,
			);
		}
		else
		{
			$filter["=GRANTEE_USER.ID"] = $granteeUserId;
		}

		return self::getList(array(
			"select" => array("ID", "SCOPE"),
			"filter" => $filter,
			"order" => array("ID" => "asc"),
		));
	}

	/**
	 * Returns array of grants given to the $granteeUserId on $controllerMemberId.
	 * If $granteeGroups provided, then checks users groups as well.
	 * Fires event OnControllerMemberScopes to add/delete scopes.
	 *
	 * @param integer $controllerMemberId Member identifier.
	 * @param integer $granteeUserId User identifier.
	 * @param array[] $granteeGroups Optional array of user groups.
	 * @return \Bitrix\Main\DB\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @see \Bitrix\Controller\AuthGrantTable::getActiveForControllerMember
	 */
	public static  function getControllerMemberScopes($controllerMemberId, $granteeUserId, $granteeGroups = array())
	{
		$result = array();
		$grantList = self::getActiveForControllerMember($controllerMemberId, $granteeUserId, $granteeGroups);
		while ($authGrant = $grantList->fetch())
		{
			$result[] = $authGrant;
		}
		$event = new \Bitrix\Main\Event("controller", "OnControllerMemberScopes", array(&$result, $controllerMemberId, $granteeUserId, $granteeGroups));
		$event->send();
		return $result;
	}
	/**
	 * Returns array of users who can get a grant on a member.
	 * This users must have controller_member_view operation.
	 *
	 * @param integer $currentUserId Identifier of the current user.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getGranteeUserList($currentUserId)
	{
		$tasks = array();
		$groups = array();
		$users = array();

		$tasksList = \Bitrix\Main\TaskOperationTable::getList(array(
			"select" => array("TASK_ID"),
			"filter" => array(
				"=OPERATION.NAME" => "controller_member_view",
			),
		));
		while($a = $tasksList->fetch())
		{
			$tasks[$a['TASK_ID']] = $a['TASK_ID'];
		}

		if ($tasks)
		{
			$groupsList = \Bitrix\Main\GroupTaskTable::getList(array(
				"select" => array("GROUP_ID"),
				"filter" => array(
					"=TASK_ID" => $tasks,
				),
			));
			while($a = $groupsList->fetch())
			{
				$groups[$a['GROUP_ID']] = $a['GROUP_ID'];
			}
		}

		if ($groups)
		{
			$usersList = \Bitrix\Main\UserGroupTable::getList(array(
				"select" => array(
					"ID" => "USER.ID",
					"LOGIN" => "USER.LOGIN",
					"NAME" => "USER.NAME",
					"LAST_NAME" => "USER.LAST_NAME",
				),
				"filter" => array(
					"=GROUP_ID" => $groups,
					array(
						"LOGIC" => "OR",
						"=DATE_ACTIVE_FROM" => false,
						"<=DATE_ACTIVE_FROM" => new \Bitrix\Main\Type\DateTime(),
					),
					array(
						"LOGIC" => "OR",
						"=DATE_ACTIVE_TO" => false,
						">=DATE_ACTIVE_TO" => new \Bitrix\Main\Type\DateTime(),
					),
				),
			));
			while($a = $usersList->fetch())
			{
				if ($a['ID'] != $currentUserId)
				{
					$users[$a['ID']] = $a['LAST_NAME'].' '.$a['NAME'].' ('.$a['LOGIN'].')';
				}
			}
		}

		asort($users);
		return $users;
	}
}