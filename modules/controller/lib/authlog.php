<?php
namespace Bitrix\Controller;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;

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
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_AuthLog_Query query()
 * @method static EO_AuthLog_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_AuthLog_Result getById($id)
 * @method static EO_AuthLog_Result getList(array $parameters = array())
 * @method static EO_AuthLog_Entity getEntity()
 * @method static \Bitrix\Controller\EO_AuthLog createObject($setDefaultValues = true)
 * @method static \Bitrix\Controller\EO_AuthLog_Collection createCollection()
 * @method static \Bitrix\Controller\EO_AuthLog wakeUpObject($row)
 * @method static \Bitrix\Controller\EO_AuthLog_Collection wakeUpCollection($rows)
 */

class AuthLogTable extends DataManager
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
		return [
			new Fields\IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('AUTH_LOG_ENTITY_ID_FIELD'),
				]
			),
			new Fields\DatetimeField(
				'TIMESTAMP_X',
				[
					'required' => true,
					'title' => Loc::getMessage('AUTH_LOG_ENTITY_TIMESTAMP_X_FIELD'),
				]
			),
			new Fields\IntegerField(
				'FROM_CONTROLLER_MEMBER_ID',
				[
					'title' => Loc::getMessage('AUTH_LOG_ENTITY_FROM_CONTROLLER_MEMBER_ID_FIELD'),
				]
			),
			new Fields\IntegerField(
				'TO_CONTROLLER_MEMBER_ID',
				[
					'title' => Loc::getMessage('AUTH_LOG_ENTITY_TO_CONTROLLER_MEMBER_ID_FIELD'),
				]
			),
			new Fields\StringField(
				'TYPE',
				[
					'validation' => [__CLASS__, 'validateType'],
					'title' => Loc::getMessage('AUTH_LOG_ENTITY_TYPE_FIELD'),
				]
			),
			new Fields\BooleanField(
				'STATUS',
				[
					'values' => ['N', 'Y'],
					'default' => 'Y',
					'title' => Loc::getMessage('AUTH_LOG_ENTITY_STATUS_FIELD'),
				]
			),
			new Fields\IntegerField(
				'USER_ID',
				[
					'title' => Loc::getMessage('AUTH_LOG_ENTITY_USER_ID_FIELD'),
				]
			),
			new Fields\StringField(
				'USER_NAME',
				[
					'validation' => [__CLASS__, 'validateUserName'],
					'title' => Loc::getMessage('AUTH_LOG_ENTITY_USER_NAME_FIELD'),
				]
			),
			new Fields\Relations\Reference(
				'FROM_CONTROLLER_MEMBER',
				'Bitrix\Controller\MemberTable',
				['=this.FROM_CONTROLLER_MEMBER_ID' => 'ref.ID']
			),
			new Fields\Relations\Reference(
				'TO_CONTROLLER_MEMBER',
				'Bitrix\Controller\MemberTable',
				['=this.TO_CONTROLLER_MEMBER_ID' => 'ref.ID']
			),
			new Fields\Relations\Reference(
				'USER',
				'Bitrix\Main\UserTable',
				['=this.USER_ID' => 'ref.ID']
			),
		];
	}

	/**
	 * Returns validators for TYPE field.
	 *
	 * @return array
	 */
	public static function validateType()
	{
		return [
			new Fields\Validators\LengthValidator(null, 50),
		];
	}

	/**
	 * Returns validators for USER_NAME field.
	 *
	 * @return array
	 */
	public static function validateUserName()
	{
		return [
			new Fields\Validators\LengthValidator(null, 255),
		];
	}

	/**
	 * Returns true if logging is enabled.
	 * Check before logging.
	 *
	 * @return bool
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
	 * @param int $controllerMemberId Controller member identifier.
	 * @param int $userId User identifier.
	 * @param bool $isSuccess Success flag.
	 * @param string $type Optional type string.
	 * @param string $userName Optional user name details.
	 *
	 * @return \Bitrix\Main\ORM\Data\AddResult
	 * @throws \Exception
	 * @see \Bitrix\Controller\AuthLogTable::isEnabled
	 */
	public static function logSiteToControllerAuth($controllerMemberId, $userId, $isSuccess = true, $type = '', $userName = '')
	{
		$fields = [
			'FROM_CONTROLLER_MEMBER_ID' => $controllerMemberId,
			'USER_ID' => $userId,
			'STATUS' => $isSuccess ? 'Y' : 'N',
			'TYPE' => $type ?: false,
			'USER_NAME' => $userName ?: false,
		];
		return self::add($fields);
	}

	/**
	 * Logs authorization on the site (from the controller).
	 *
	 * @param int $controllerMemberId Controller member identifier.
	 * @param int $userId User identifier.
	 * @param bool $isSuccess Success flag.
	 * @param string $type Optional type.
	 * @param string $userName Optional user name details.
	 *
	 * @return \Bitrix\Main\ORM\Data\AddResult
	 * @throws \Exception
	 * @see \Bitrix\Controller\AuthLogTable::isEnabled
	 */
	public static function logControllerToSiteAuth($controllerMemberId, $userId, $isSuccess = true, $type = '', $userName = '')
	{
		$fields = [
			'TO_CONTROLLER_MEMBER_ID' => $controllerMemberId,
			'USER_ID' => $userId,
			'STATUS' => $isSuccess ? 'Y' : 'N',
			'TYPE' => $type ?: false,
			'USER_NAME' => $userName ?: false,
		];
		return self::add($fields);
	}

	/**
	 * Logs authorization between sites.
	 *
	 * @param int $fromControllerMemberId Controller member identifier.
	 * @param int $toControllerMemberId Controller member identifier.
	 * @param bool $isSuccess Success flag.
	 * @param string $type Optional type.
	 * @param string $userName Optional user name details.
	 *
	 * @return \Bitrix\Main\ORM\Data\AddResult
	 * @throws \Exception
	 * @see \Bitrix\Controller\AuthLogTable::isEnabled
	 */
	public static function logSiteToSiteAuth($fromControllerMemberId, $toControllerMemberId, $isSuccess = true, $type = '', $userName = '')
	{
		$fields = [
			'FROM_CONTROLLER_MEMBER_ID' => $fromControllerMemberId,
			'TO_CONTROLLER_MEMBER_ID' => $toControllerMemberId,
			'STATUS' => $isSuccess ? 'Y' : 'N',
			'TYPE' => $type ?: false,
			'USER_NAME' => $userName ?: false,
		];
		return self::add($fields);
	}

	private static $agentName = '\\Bitrix\\Controller\\AuthLogTable::cleanupAgent';

	/**
	 * Adds agent function for log cleanup.
	 *
	 * @param int $days How many days to preserve in the log.
	 * @return void
	 */
	public static function setupAgent($days)
	{
		$days = intval($days);

		$agentList = \CAgent::GetList([], ['NAME' => self::$agentName . '%']);
		while ($agent = $agentList->Fetch())
		{
			\CAgent::Delete($agent['ID']);
		}

		if ($days > 0)
		{
			\CAgent::AddAgent(self::$agentName . '(' . $days . ');', 'controller', 'N', $days * 3600 * 24);
		}

	}

	/**
	 * Agent function. Deletes obsolete records.
	 *
	 * @param int $days How many days to preserve in the log.
	 * @return string
	 */
	public static function cleanupAgent($days)
	{
		$days = intval($days);
		$connection = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$connection->query('
			DELETE FROM ' . self::getTableName() . '
			WHERE TIMESTAMP_X < ' . $sqlHelper->addSecondsToDateTime(-$days * 3600 * 24, $sqlHelper->getCurrentDateTimeFunction())
		);
		return self::$agentName . '(' . $days . ');';
	}
}
