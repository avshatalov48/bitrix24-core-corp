<?php

namespace Bitrix\Voximplant\Security;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Permissions
{
	const ENTITY_CALL_DETAIL = 'CALL_DETAIL';
	const ENTITY_CALL_RECORD = 'CALL_RECORD';
	const ENTITY_CALL = 'CALL';
	const ENTITY_USER = 'USER';
	const ENTITY_SETTINGS = 'SETTINGS';
	const ENTITY_LINE = 'LINE';
	public const ENTITY_BALANCE = 'BALANCE';

	const ACTION_VIEW = 'VIEW';
	const ACTION_LISTEN = 'LISTEN';
	const ACTION_PERFORM = 'PERFORM';
	const ACTION_MODIFY = 'MODIFY';

	const PERMISSION_NONE = '';
	const PERMISSION_SELF = 'A';
	const PERMISSION_CALL_CRM = 'C';
	const PERMISSION_CALL_USERS = 'K';
	const PERMISSION_DEPARTMENT = 'D';
	const PERMISSION_ANY = 'X';

	protected static $instances = array();

	protected $userId;
	protected $permissions;

	/**
	 * This class should not be instantiated directly. Use one of the named constructors.
	 */
	protected function __construct()
	{

	}

	/**
	 * Creates class instance for the current user.
	 * @return Permissions
	 */
	public static function createWithCurrentUser()
	{
		return self::createWithUserId(Helper::getCurrentUserId());
	}

	/**
	 * Creates class instance for the specified user.
	 * @param int $userId User's id.
	 * @return Permissions
	 */
	public static function createWithUserId($userId)
	{
		if(isset(self::$instances[$userId]))
			return self::$instances[$userId];

		$instance = new self;
		$instance->setUserId($userId);
		$instance->permissions = self::getNormalizedPermissions(RoleManager::getUserPermissions($userId));

		self::$instances[$userId] = $instance;
		return $instance;
	}

	/**
	 * Returns true if user can perform specified action on the entity.
	 * @param string $entityCode Code of the entity.
	 * @param string $actionCode Code of the action.
	 * @param string $minimumPermission Permission code.
	 * @return bool
	 * @throws ArgumentException
	 */
	public function canPerform($entityCode, $actionCode, $minimumPermission = null)
	{
		$permissionMap = $this->getMap();
		if(!isset($permissionMap[$entityCode][$actionCode]))
			throw new ArgumentException('Unknown entity or action code');

		if(is_null($minimumPermission))
		{
			$result = (
				isset($this->permissions[$entityCode][$actionCode]) &&
				$this->permissions[$entityCode][$actionCode] > self::PERMISSION_NONE
			);
		}
		else
		{
			$result = (
				isset($this->permissions[$entityCode][$actionCode]) &&
				$this->permissions[$entityCode][$actionCode] >= $minimumPermission
			);
		}

		return $result;
	}

	/**
	 * Shortcut method for checking MODIFY permission on ENTITY_SETTINGS
	 * @return bool
	 */
	public function canModifySettings()
	{
		return $this->canPerform(Permissions::ENTITY_SETTINGS, Permissions::ACTION_MODIFY);
	}

	/**
	 * Shortcut method for checking MODIFY permission on ENTITY_LINE
	 * @return bool
	 */
	public function canModifyLines()
	{
		return $this->canPerform(Permissions::ENTITY_LINE, Permissions::ACTION_MODIFY);
	}

	/**
	 * Returns permission code according to the user's permissions.
	 * @param string $entityCode Code of the entity.
	 * @param string $actionCode Code of the action.
	 * @return string
	 * @throws ArgumentException
	 */
	public function getPermission($entityCode, $actionCode)
	{
		$permissionMap = $this->getMap();
		if(!isset($permissionMap[$entityCode][$actionCode]))
			throw new ArgumentException('Unknown entity or action code');

		return (isset($this->permissions[$entityCode][$actionCode]) ? $this->permissions[$entityCode][$actionCode] : self::PERMISSION_NONE);
	}

	/**
	 * Returns permissions map.
	 * @return array 
	 * @internal
	 */
	public static function getMap()
	{
		return [
			self::ENTITY_CALL_DETAIL => [
				self::ACTION_VIEW => [
					self::PERMISSION_NONE,
					self::PERMISSION_SELF,
					self::PERMISSION_DEPARTMENT,
					self::PERMISSION_ANY
				],
				/*self::ACTION_MODIFY => array(
					self::PERMISSION_NONE,
					self::PERMISSION_ANY
				)*/
			],
			self::ENTITY_CALL_RECORD => [
				self::ACTION_LISTEN => [
					self::PERMISSION_NONE,
					self::PERMISSION_SELF,
					self::PERMISSION_DEPARTMENT,
					self::PERMISSION_ANY
				],
				self::ACTION_MODIFY => [
					self::PERMISSION_NONE,
					self::PERMISSION_SELF,
					self::PERMISSION_DEPARTMENT,
					self::PERMISSION_ANY
				],
			],
			self::ENTITY_CALL => [
				self::ACTION_PERFORM => [
					self::PERMISSION_NONE,
					self::PERMISSION_CALL_CRM,
					self::PERMISSION_CALL_USERS,
					self::PERMISSION_ANY
				],
			],
			self::ENTITY_USER => [
				self::ACTION_MODIFY => [
					self::PERMISSION_NONE,
					self::PERMISSION_SELF,
					self::PERMISSION_DEPARTMENT,
					self::PERMISSION_ANY
				]
			],
			self::ENTITY_SETTINGS => [
				self::ACTION_MODIFY => [
					self::PERMISSION_NONE,
					self::PERMISSION_ANY
				]
			],
			self::ENTITY_LINE => [
				self::ACTION_MODIFY => [
					self::PERMISSION_NONE,
					self::PERMISSION_ANY
				]
			],
			self::ENTITY_BALANCE => [
				self::ACTION_MODIFY => [
					self::PERMISSION_NONE,
					self::PERMISSION_ANY,
				]
			]
		];
	}

	/**
	 * Returns user id.
	 * @return int
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * Sets user id.
	 * @param int $userId User id.
	 * @return $this
	 */
	protected function setUserId($userId)
	{
		$userId = (int)$userId;

		$this->userId = $userId;
		return $this;
	}

	/**
	 * Returns normalized permissions array.
	 * @param array $permissions Some not normalized permissions array.
	 * @return array
	 */
	public static function getNormalizedPermissions(array $permissions)
	{
		$permissionMap = self::getMap();
		$result = array();

		foreach ($permissionMap as $entity => $actions)
		{
			foreach ($actions as $action => $permission)
			{
				if(isset($permissions[$entity][$action]))
					$result[$entity][$action] = $permissions[$entity][$action];
				else
					$result[$entity][$action] = self::PERMISSION_NONE;
			}
		}

		return $result;
	}

	/**
	 * Returns name of the entity by its code.
	 * @param string $entity Entity code.
	 * @return string
	 */
	public static function getEntityName($entity)
	{
		return Loc::getMessage('VOXIMPLANT_SECURITY_ENTITY_'.$entity);
	}

	/**
	 * Returns name of the action by its code.
	 * @param string $action Action code.
	 * @return string
	 */
	public static function getActionName($action)
	{
		return Loc::getMessage('VOXIMPLANT_SECURITY_ACTION_'.$action);
	}

	/**
	 * Returns name of the permission by its code.
	 * @param string $permission Permission code.
	 * @return string
	 */
	public static function getPermissionName($permission)
	{
		switch ($permission)
		{
			case self::PERMISSION_NONE:
				$result = Loc::getMessage('VOXIMPLANT_SECURITY_PERMISSION_NONE');
				break;
			case self::PERMISSION_SELF:
				$result = Loc::getMessage('VOXIMPLANT_SECURITY_PERMISSION_SELF');
				break;
			case self::PERMISSION_DEPARTMENT:
				$result = Loc::getMessage('VOXIMPLANT_SECURITY_PERMISSION_DEPARTMENT');
				break;
			case self::PERMISSION_CALL_CRM:
				$result = Loc::getMessage('VOXIMPLANT_SECURITY_PERMISSION_CALL_CRM');
				break;
			case self::PERMISSION_CALL_USERS:
				$result = Loc::getMessage('VOXIMPLANT_SECURITY_PERMISSION_CALL_USERS');
				break;
			case self::PERMISSION_ANY:
				$result = Loc::getMessage('VOXIMPLANT_SECURITY_PERMISSION_ANY');
				break;
			default:
				$result = '';
				break;
		}
		return $result;
	}
}