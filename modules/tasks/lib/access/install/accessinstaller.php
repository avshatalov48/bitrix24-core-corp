<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Install;


use Bitrix\Main\Access\AccessCode;
use Bitrix\Tasks\Access\Permission\PermissionDictionary;
use Bitrix\Tasks\Access\Permission\TasksPermissionTable;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Access\Role\TasksRoleRelationTable;
use Bitrix\Tasks\Access\Role\TasksRoleTable;

class AccessInstaller
{
	private $db;

	public function __construct(\CDatabase $db)
	{
		$this->db = $db;
	}

	public function createTables()
	{
		$this->db->Query("
			CREATE TABLE IF NOT EXISTS b_tasks_role (
				ID INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				NAME VARCHAR(250) NOT NULL,
				PRIMARY KEY (ID)
			);
		");

		$this->db->Query("
			CREATE TABLE IF NOT EXISTS b_tasks_role_relation (
				ID INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				ROLE_ID INT(10) UNSIGNED NOT NULL,
				RELATION VARCHAR(8) NOT NULL DEFAULT '',
				PRIMARY KEY (ID),
				INDEX ROLE_ID (ROLE_ID),
				INDEX RELATION (RELATION)
			);
		");

		$this->db->Query("
			CREATE TABLE IF NOT EXISTS b_tasks_permission (
				ID INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				ROLE_ID INT(10) UNSIGNED NOT NULL,
				PERMISSION_ID VARCHAR(32) NOT NULL DEFAULT '0',
				VALUE TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (ID),
				INDEX ROLE_ID (ROLE_ID),
				INDEX PERMISSION_ID (PERMISSION_ID)
			);
		");

		$this->db->Query("
			CREATE TABLE `b_tasks_template_permission` (
				`ID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				`TEMPLATE_ID` INT(10) UNSIGNED NULL DEFAULT NULL,
				`ACCESS_CODE` VARCHAR(8) NULL DEFAULT NULL,
				`PERMISSION_ID` VARCHAR(32) NULL DEFAULT NULL,
				`VALUE` TINYINT(3) UNSIGNED NULL DEFAULT '0',
				PRIMARY KEY (`ID`),
				INDEX `TEMPLATE_ID` (`TEMPLATE_ID`),
				INDEX `ACCESS_CODE` (`ACCESS_CODE`),
				INDEX `PERMISSION_ID` (`PERMISSION_ID`)
			);
		");
	}

	public static function installAgent()
	{
		global $DB;
		(new self($DB))->install();
		return '';
	}

	public function install()
	{
		$this->fillSystemPermissions();
		$this->fillRelations();
		$this->removeAgents();
	}

	private function removeAgents()
	{
		\CAgent::RemoveAgent('\Bitrix\Tasks\Access\Install\AccessInstaller::installAgent();', 'tasks');
		\CAgent::RemoveAgent('\Bitrix\Tasks\Access\Install\Migration::migrateAgent();', 'tasks');
	}

	private function fillSystemPermissions()
	{
		if (TasksPermissionTable::getCount())
		{
			return;
		}

		$map = RoleMap::getDefaultMap();

		$query = [];
		foreach ($map as $roleName => $permissions)
		{
			$role = TasksRoleTable::add([
				'NAME' => $roleName
			]);

			if (!$role->isSuccess())
			{
				// @ToDo add errors handler
				continue;
			}

			foreach ($permissions as $permission)
			{
				$query[] = '('. $role->getId() .', '. $permission .', '. PermissionDictionary::VALUE_YES .')';
			}
		}

		$query = '
			INSERT INTO b_tasks_permission
				(ROLE_ID, PERMISSION_ID, `VALUE`)
				VALUES '. implode(',', $query) .'
		';
		$this->db->Query($query);
	}

	private function fillRelations()
	{
		$res = TasksRoleRelationTable::getList([
			'select' => ['ID', 'RELATION']
		]);
		while ($row = $res->fetch())
		{
			if (preg_match('/^G\d+$/', $row['RELATION']))
			{
				TasksRoleRelationTable::delete($row['ID']);
			}
		}

		$res = TasksRoleTable::getList([
			'select' => ['ID', 'NAME']
		]);

		while ($role = $res->fetch())
		{
			$query = null;

			if ($role['NAME'] === RoleDictionary::TASKS_ROLE_CHIEF)
			{
				$query = '
					INSERT INTO b_tasks_role_relation 
					    (ROLE_ID, RELATION)
					    VALUES ('. $role['ID'] .', "'. AccessCode::ACCESS_DIRECTOR .'0")';
			}
			elseif ($role['NAME'] === RoleDictionary::TASKS_ROLE_MANAGER)
			{
				$query = '
					INSERT INTO b_tasks_role_relation 
					    (ROLE_ID, RELATION)
					    VALUES ('. $role['ID'] .', "'. AccessCode::ACCESS_EMPLOYEE .'0")';
			}

			if ($query)
			{
				$this->db->Query($query);
			}
		}
	}
}