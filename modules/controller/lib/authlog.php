<?php
namespace Bitrix\Controller;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class AuthLogTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TIMESTAMP_X datetime optional default 'CURRENT_TIMESTAMP'
 * <li> FROM_CONTROLLER_MEMBER_ID int optional
 * <li> TO_CONTROLLER_MEMBER_ID int optional
 * <li> TYPE string(50) optional
 * <li> STATUS bool optional default 'Y'
 * <li> USER_ID int optional
 * <li> USER_NAME string(255) optional
 * <li> FROM_CONTROLLER_MEMBER reference to {@link \Bitrix\Controller\MemberTable}
 * <li> TO_CONTROLLER_MEMBER reference to {@link \Bitrix\Controller\MemberTable}
 * <li> USER reference to {@link \Bitrix\Main\UserTable}
 * </ul>
 *
 * @package Bitrix\Controller
 **/

class AuthLogTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_controller_auth_log';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('AUTH_LOG_ENTITY_ID_FIELD'),
			),
			'TIMESTAMP_X' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('AUTH_LOG_ENTITY_TIMESTAMP_X_FIELD'),
			),
			'FROM_CONTROLLER_MEMBER_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('AUTH_LOG_ENTITY_FROM_CONTROLLER_MEMBER_ID_FIELD'),
			),
			'TO_CONTROLLER_MEMBER_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('AUTH_LOG_ENTITY_TO_CONTROLLER_MEMBER_ID_FIELD'),
			),
			'TYPE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateType'),
				'title' => Loc::getMessage('AUTH_LOG_ENTITY_TYPE_FIELD'),
			),
			'STATUS' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('AUTH_LOG_ENTITY_STATUS_FIELD'),
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('AUTH_LOG_ENTITY_USER_ID_FIELD'),
			),
			'USER_NAME' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateUserName'),
				'title' => Loc::getMessage('AUTH_LOG_ENTITY_USER_NAME_FIELD'),
			),
			'FROM_CONTROLLER_MEMBER' => array(
				'data_type' => 'Bitrix\Controller\MemberTable',
				'reference' => array('=this.FROM_CONTROLLER_MEMBER_ID' => 'ref.ID'),
			),
			'TO_CONTROLLER_MEMBER' => array(
				'data_type' => 'Bitrix\Controller\MemberTable',
				'reference' => array('=this.TO_CONTROLLER_MEMBER_ID' => 'ref.ID'),
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.USER_ID' => 'ref.ID'),
			),
		);
	}
	/**
	 * Returns validators for TYPE field.
	 *
	 * @return array
	 */
	public static function validateType()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}
	/**
	 * Returns validators for USER_NAME field.
	 *
	 * @return array
	 */
	public static function validateUserName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns true if logging is enabled.
	 * Check before logging.
	 * 
	 * @return boolean
	 * @see \Bitrix\Controller\AuthLogTable::logSiteToControllerAuth
	 * @see \Bitrix\Controller\AuthLogTable::logControllerToSiteAuth
	 * @see \Bitrix\Controller\AuthLogTable::logSiteToSiteAuth
	 */
	public static function isEnabled()
	{
		return true;
	}

	/**
	 * Logs authorization on the controller.
	 * 
	 * @param integer $controllerMemberId Controller member identifier.
	 * @param integer $userId User identifier.
	 * @param boolean $isSuccess Success flag.
	 * @param string $type Optional type string.
	 * @param string $userName Optional user name details.
	 * 
	 * @return Main\Entity\AddResult
	 * @throws \Exception
	 * @see \Bitrix\Controller\AuthLogTable::isEnabled
	 */
	public static function logSiteToControllerAuth($controllerMemberId, $userId, $isSuccess = true, $type = '', $userName = '')
	{
		$fields = array(
			"FROM_CONTROLLER_MEMBER_ID" => $controllerMemberId,
			"USER_ID" => $userId,
			"STATUS" => $isSuccess? "Y": "N",
			"TYPE" => $type?: false,
			"USER_NAME" => $userName?: false,
		);
		return self::add($fields);
	}

	/**
	 * Logs authorization on the site (from the controller).
	 *
	 * @param integer $controllerMemberId Controller member identifier.
	 * @param integer $userId User identifier.
	 * @param boolean $isSuccess Success flag.
	 * @param string $type Optional type.
	 * @param string $userName Optional user name details.
	 *
	 * @return Main\Entity\AddResult
	 * @throws \Exception
	 * @see \Bitrix\Controller\AuthLogTable::isEnabled
	 */
	public static function logControllerToSiteAuth($controllerMemberId, $userId, $isSuccess = true, $type = '', $userName = '')
	{
		$fields = array(
			"TO_CONTROLLER_MEMBER_ID" => $controllerMemberId,
			"USER_ID" => $userId,
			"STATUS" => $isSuccess? "Y": "N",
			"TYPE" => $type?: false,
			"USER_NAME" => $userName?: false,
		);
		return self::add($fields);
	}

	/**
	 * Logs authorization between sites.
	 *
	 * @param integer $fromControllerMemberId Controller member identifier.
	 * @param integer $toControllerMemberId Controller member identifier.
	 * @param boolean $isSuccess Success flag.
	 * @param string $type Optional type.
	 * @param string $userName Optional user name details.
	 *
	 * @return Main\Entity\AddResult
	 * @throws \Exception
	 * @see \Bitrix\Controller\AuthLogTable::isEnabled
	 */
	public static function logSiteToSiteAuth($fromControllerMemberId, $toControllerMemberId, $isSuccess = true, $type = '', $userName = '')
	{
		$fields = array(
			"FROM_CONTROLLER_MEMBER_ID" => $fromControllerMemberId,
			"TO_CONTROLLER_MEMBER_ID" => $toControllerMemberId,
			"STATUS" => $isSuccess? "Y": "N",
			"TYPE" => $type?: false,
			"USER_NAME" => $userName?: false,
		);
		return self::add($fields);
	}
	
	private static $agentName = "\\Bitrix\\Controller\\AuthLogTable::cleanupAgent";

	/**
	 * Adds agent function for log cleanup.
	 *
	 * @param integer $days How many days to preserve in the log.
	 * @return void
	 */
	public static function setupAgent($days)
	{
		$days = intval($days);

		$agentList = \CAgent::GetList(array(), array("NAME" => self::$agentName."%"));
		while ($agent = $agentList->Fetch())
		{
			\CAgent::Delete($agent['ID']);
		}

		if ($days > 0)
		{
			\CAgent::AddAgent(self::$agentName.'('.$days.');', "controller", "N", $days * 3600 *24);
		}
		
	}

	/**
	 * Agent function. Deletes obsolete records.
	 *
	 * @param integer $days How many days to preserve in the log.
	 * @return string
	 */
	public static function cleanupAgent($days)
	{
		$days = intval($days);
		$application = \Bitrix\Main\Application::getInstance();
		$connection = $application->getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$connection->query("
			DELETE FROM ".self::getTableName()."
			WHERE TIMESTAMP_X < ".$sqlHelper->addSecondsToDateTime(-$days * 3600 *24, $sqlHelper->getCurrentDateTimeFunction())
		);
		return self::$agentName.'('.$days.');';
	}
}