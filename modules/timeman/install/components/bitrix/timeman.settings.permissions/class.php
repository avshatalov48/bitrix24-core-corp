<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\OperationTable;
use Bitrix\Main\TaskOperationTable;

Loc::loadMessages(__FILE__);
if (!Loader::includeModule('timeman'))
{
	ShowError(htmlspecialcharsbx(Loc::getMessage('TIMEMAN_MODULE_NOT_INSTALLED')));
	return;
}

class TimemanSettingsPermissionsComponent extends \Bitrix\Timeman\Component\BaseComponent
{
	/**
	 * @var \Bitrix\Timeman\Security\UserPermissionsManager
	 */
	private $userPermissionsManager;

	public function __construct($component = null)
	{
		global $USER;
		$this->userPermissionsManager = \Bitrix\Timeman\Service\DependencyManager::getInstance()
			->getUserPermissionsManager($USER);
		parent::__construct($component);
	}

	public function onPrepareComponentParams($arParams)
	{
		$arParams['taskId'] = $this->getFromParamsOrRequest($arParams, 'taskId', 'int');
		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		if (!$this->userPermissionsManager->canUpdateSettings())
		{
			$this->showError(Loc::getMessage('TIMEMAN_SETTINGS_PERMS_PERMISSIONS_ERROR'));
			return;
		}
		$tasks = \CTask::getList(['ID' => 'asc'], ['MODULE_ID' => 'timeman']);
		$this->arResult['tasks'] = [];

		$convertedRoles = [
			'timeman_denied_converted_editable',
			'timeman_subordinate_converted_editable',
			'timeman_read_converted_editable',
			'timeman_write_converted_editable',
			'timeman_full_access_converted_editable',
		];

		$taskIds = [];
		while ($task = $tasks->fetch())
		{
			if (in_array($task['NAME'], $convertedRoles))
			{
				continue;
			}

			$name = Loc::getMessage('TASK_NAME_'.mb_strtoupper($task['NAME']).'_CONVERTED_EDITABLE') ?: $task['NAME'];
			$this->arResult['tasks'][] = [
				'ID' => $task['ID'],
				'NAME' => $name,
				'CAN_BE_EDIT' => $task['SYS'] !== 'Y',
				'CAN_BE_DELETED' => $task['SYS'] !== 'Y',
				'SYS' => $task['SYS'],
			];

			$taskIds[] = $task['ID'];
		}

		$taskAccessCodes = \Bitrix\Timeman\Model\Security\TaskAccessCodeTable::query()
			->addSelect('*')
			->whereIn('TASK_ID', $taskIds)
			->exec()
			->fetchAll();

		if (isset($this->arParams['taskId']))
		{
			$this->arResult['userPermissionsManager'] = $this->userPermissionsManager;
			$this->arResult['operationsMap'] = $this->userPermissionsManager->getMap();
			$this->setTitle(Loc::getMessage('TIMEMAN_SETTINGS_PERMS_ADD_ROLE_TITLE'));
			$updatingTask = null;
			$operations = [];
			if ($this->arParams['taskId'] > 0)
			{
				foreach ($this->arResult['tasks'] as $taskItem)
				{
					if ((int)$taskItem['ID'] === (int)$this->arParams['taskId'])
					{
						$updatingTask = $taskItem;
						break;
					}
				}
				if (!$updatingTask)
				{
					$this->showError(Loc::getMessage('TIMEMAN_SETTINGS_PERMS_EDIT_ROLE_NOT_FOUND'));
					return;
				}
				$operations = OperationTable::query()
					->registerRuntimeField(
						(new \Bitrix\Main\ORM\Fields\Relations\Reference('TO', TaskOperationTable::class, ['this.ID' => 'ref.OPERATION_ID']))
							->configureJoinType('INNER')
					)
					->addSelect('NAME')
					->where('TO.TASK_ID', $this->arParams['taskId'])
					->where('MODULE_ID', 'timeman')
					->exec()
					->fetchAll();
				$this->setTitle(Loc::getMessage('TIMEMAN_SETTINGS_PERMS_EDIT_ROLE_TITLE', ['#ROLE#' => $updatingTask['NAME']]));
			}
			$this->arResult['taskForm'] = new \Bitrix\Timeman\Form\Security\TaskForm($updatingTask, $operations);

			$this->includeComponentTemplate('edit_role');
			return;
		}

		$accessCodes = $this->getAccessCodesInfo(array_column($taskAccessCodes, 'ACCESS_CODE'));
		foreach ($taskAccessCodes as $id => $taskAccessCode)
		{
			if (isset($accessCodes[$taskAccessCode['ACCESS_CODE']]))
			{
				$codeDescription = $accessCodes[$taskAccessCode['ACCESS_CODE']];
				$taskAccessCodes[$id]['ACCESS_PROVIDER'] = $codeDescription['provider'];
				$taskAccessCodes[$id]['ACCESS_NAME'] = $codeDescription['name'];
			}
		}
		$this->arResult['taskAccessCodes'] = $taskAccessCodes;

		$this->setTitle(Loc::getMessage('TIMEMAN_SETTINGS_PERMS_TITLE'));
		$this->includeComponentTemplate();
	}

	/**
	 * @param $task
	 * @return bool|string
	 */
	public function getEditTaskUrl($task = null)
	{
		$path = \Bitrix\Timeman\Service\DependencyManager::getInstance()->getUrlManager()
			->getUriTo(\Bitrix\Timeman\TimemanUrlManager::URI_SETTINGS_PERMISSIONS);

		$taskId = 0;
		if ($task)
		{
			$taskId = $task['ID'];
		}
		$uri = new \Bitrix\Main\Web\Uri($path);
		$uri->addParams(['taskId' => $taskId]);

		return $uri->getLocator();
	}

	/**
	 * @param array $accessCodes
	 * @return array
	 */
	protected function getAccessCodesInfo(array $accessCodes)
	{
		$accessManager = new CAccess();
		return $accessManager->GetNames($accessCodes);
	}

	protected function showError($error)
	{
		ShowError($error);
	}

	/**
	 * @param string $title
	 */
	protected function setTitle($title)
	{
		global $APPLICATION;
		$APPLICATION->SetTitle(htmlspecialcharsbx($title));
	}
}