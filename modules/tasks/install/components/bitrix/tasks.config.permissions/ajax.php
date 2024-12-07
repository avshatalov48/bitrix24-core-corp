<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !==true) die();

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Tasks\Access\Component\ConfigPermissions;

Loc::loadMessages(__FILE__);

class TasksConfigPermissionsAjaxController extends \Bitrix\Main\Engine\Controller
{
	public function configureActions()
	{
		return [
			'save' => [
				'prefilters' => [
					new ActionFilter\Authentication(),
					new ActionFilter\HttpMethod(['POST']),
					new ActionFilter\Csrf(),
				],
			],
			'delete' => [
				'prefilters' => [
					new ActionFilter\Authentication(),
					new ActionFilter\HttpMethod(['POST']),
					new ActionFilter\Csrf(),
				],
			],
			'load' => [
				'prefilters' => [
					new ActionFilter\Authentication(),
				]
			]
		];
	}

	public function saveAction($userGroups = [])
	{
		foreach ($userGroups as $roleSettings)
		{
			$this->saveRoleSettings($roleSettings);
		}
	}

	public function deleteAction($roleId)
	{
		$this->deleteRole((int) $roleId);
	}

	public function loadAction()
	{
		$configPermissions = new ConfigPermissions();

		return [
			'USER_GROUPS' => $configPermissions->getUserGroups(),
			'ACCESS_RIGHTS' => $configPermissions->getAccessRights()
		];
	}

	protected function init()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			$this->errorCollection[] = new Error('');
		}

		parent::init();
	}

	protected function deleteRole(int $roleId)
	{
		(new \Bitrix\Tasks\Access\Role\RoleUtil($roleId))->deleteRole();
	}

	protected function processBeforeAction(Action $action)
	{
		if (!$this->checkPermissions($action))
		{
			return false;
		}

		return parent::processBeforeAction($action);
	}

	private function checkPermissions(Action $action): bool
	{
		if (
			!\Bitrix\Tasks\Integration\Bitrix24::checkFeatureEnabled(
				\Bitrix\Tasks\Integration\Bitrix24\FeatureDictionary::TASK_ACCESS_PERMISSIONS
			)
		)
		{
			return false;
		}

		return \Bitrix\Tasks\Access\TaskAccessController::can(
			\Bitrix\Tasks\Util\User::getId(),
			\Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_ADMIN
		);
	}

	private function saveRoleSettings(array $roleSettings)
	{
		$roleSettings = $this->prepareSettings($roleSettings);

		$roleId = $roleSettings['id'];
		$roleTitle = $roleSettings['title'];

		if ($roleId === 0)
		{
			try
			{
				$roleId = \Bitrix\Tasks\Access\Role\RoleUtil::createRole($roleTitle);
			}
			catch (\Bitrix\Main\Access\Exception\RoleSaveException $e)
			{
				$this->errorCollection[] = new Error(Loc::getMessage('TASKS_CONFIG_PERMISSIONS_DB_ERROR'));
			}
		}

		if (!$roleId)
		{
			return;
		}

		$role = new \Bitrix\Tasks\Access\Role\RoleUtil($roleId);
		try
		{
			$role->updateTitle($roleTitle);
		}
		catch (\Bitrix\Main\Access\Exception\AccessException $e)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('TASKS_CONFIG_PERMISSIONS_DB_ERROR'));
			return;
		}

		$permissions = array_combine(array_column($roleSettings['accessRights'], 'id'), array_column($roleSettings['accessRights'], 'value'));
		try
		{
			$role->updatePermissions($permissions);
		}
		catch (\Bitrix\Main\Access\Exception\PermissionSaveException $e)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('TASKS_CONFIG_PERMISSIONS_DB_ERROR'));
			return;
		}

		try
		{
			$role->updateRoleRelations($roleSettings['accessCodes']);
		}
		catch (\Bitrix\Main\Access\Exception\RoleRelationSaveException $e)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('TASKS_CONFIG_PERMISSIONS_DB_ERROR'));
			return;
		}
	}

	private function prepareSettings(array $settings): array
	{
		$settings['id'] = (int) $settings['id'];
		$settings['title'] = \Bitrix\Main\Text\Encoding::convertEncodingToCurrent($settings['title']);

		if (!array_key_exists('accessRights', $settings))
		{
			$settings['accessRights'] = [];
		}

		if (!array_key_exists('accessCodes', $settings))
		{
			$settings['accessCodes'] = [];
		}

		return $settings;
	}
}

