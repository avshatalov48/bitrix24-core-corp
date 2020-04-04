<?php

namespace Bitrix\ImOpenlines\Security;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Permissions
{
	const ENTITY_LINES = 'LINES';
	const ENTITY_CONNECTORS = 'CONNECTORS';
	const ENTITY_SESSION = 'SESSION';
	const ENTITY_HISTORY = 'HISTORY';
	const ENTITY_JOIN = 'JOIN';
	const ENTITY_VOTE_HEAD = 'VOTE_HEAD';
	const ENTITY_SETTINGS = 'SETTINGS';
	
	const ACTION_VIEW = 'VIEW';
	const ACTION_PERFORM = 'PERFORM';
	const ACTION_MODIFY = 'MODIFY';

	const PERMISSION_NONE = '';
	const PERMISSION_SELF = 'A';
	const PERMISSION_DEPARTMENT = 'D';
	const PERMISSION_ANY = 'X';
	const PERMISSION_ALLOW = 'X';

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
	 * Shortcut method for checking MODIFY permission on ENTITY_LINES
	 * @return bool
	 */
	public function canModifyLines()
	{
		return $this->canPerform(Permissions::ENTITY_LINES, Permissions::ACTION_MODIFY);
	}
	
	/**
	 * Shortcut method for checking MODIFY permission on ENTITY_LINES
	 * @return bool
	 */
	public function canViewLines()
	{
		return $this->canPerform(Permissions::ENTITY_LINES, Permissions::ACTION_VIEW);
	}
	
	/**
	 * Shortcut method for checking MODIFY permission on ENTITY_LINES
	 * @return bool
	 */
	public function canViewStatistics()
	{
		return $this->canPerform(Permissions::ENTITY_SESSION, Permissions::ACTION_VIEW);
	}
	
	/**
	 * Shortcut method for checking MODIFY permission on ENTITY_CONNECTORS
	 * @return bool
	 */
	public function canModifyConnectors()
	{
		return $this->canPerform(Permissions::ENTITY_CONNECTORS, Permissions::ACTION_MODIFY);
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
		return array(
			self::ENTITY_LINES => array(
				self::ACTION_VIEW => array(
					self::PERMISSION_NONE,
					self::PERMISSION_SELF,
					self::PERMISSION_DEPARTMENT,
					self::PERMISSION_ANY
				),
				self::ACTION_MODIFY => array(
					self::PERMISSION_NONE,
					self::PERMISSION_SELF,
					self::PERMISSION_DEPARTMENT,
					self::PERMISSION_ANY
				),
			),
			self::ENTITY_CONNECTORS => array(
				self::ACTION_MODIFY => array(
					self::PERMISSION_NONE,
					self::PERMISSION_SELF,
					self::PERMISSION_DEPARTMENT,
					self::PERMISSION_ANY
				),
			),
			self::ENTITY_SESSION => array(
				self::ACTION_VIEW => array(
					self::PERMISSION_NONE,
					self::PERMISSION_SELF,
					self::PERMISSION_DEPARTMENT,
					self::PERMISSION_ANY
				)
			),
			self::ENTITY_HISTORY => array(
				self::ACTION_VIEW => array(
					self::PERMISSION_NONE,
					self::PERMISSION_SELF,
					self::PERMISSION_DEPARTMENT,
					self::PERMISSION_ANY
				)
			),
			self::ENTITY_JOIN => array(
				self::ACTION_PERFORM => array(
					self::PERMISSION_SELF,
					self::PERMISSION_DEPARTMENT,
					self::PERMISSION_ANY
				),
			),
			self::ENTITY_VOTE_HEAD => array(
				self::ACTION_PERFORM => array(
					self::PERMISSION_DEPARTMENT,
					self::PERMISSION_ANY,
					self::PERMISSION_NONE
				),
			),
			self::ENTITY_SETTINGS => array(
				self::ACTION_MODIFY => array(
					self::PERMISSION_NONE,
					self::PERMISSION_ALLOW
				)
			),
		);
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
		return Loc::getMessage('IMOL_SECURITY_ENTITY_'.$entity);
	}

	/**
	 * Returns name of the action by its code.
	 * @param string $action Action code.
	 * @return string
	 */
	public static function getActionName($action)
	{
		return Loc::getMessage('IMOL_SECURITY_ACTION_'.$action);
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
				$result = Loc::getMessage('IMOL_SECURITY_PERMISSION_NONE');
				break;
			case self::PERMISSION_SELF:
				$result = Loc::getMessage('IMOL_SECURITY_PERMISSION_SELF');
				break;
			case self::PERMISSION_DEPARTMENT:
				$result = Loc::getMessage('IMOL_SECURITY_PERMISSION_DEPARTMENT');
				break;
			case self::PERMISSION_ANY:
				$result = Loc::getMessage('IMOL_SECURITY_PERMISSION_ANY');
				break;
			default:
				$result = '';
				break;
		}
		return $result;
	}
}