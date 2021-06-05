<?php
namespace Bitrix\Timeman\Controller;

use Bitrix\Main\Application;
use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Form\Security\TaskForm;
use Bitrix\Timeman\Model\Security\TaskAccessCodeTable;
use Bitrix\Timeman\Security\UserPermissionsManager;
use CTask;

Loc::loadMessages(__FILE__);

class Permissions extends Controller
{
	protected function getDefaultPreFilters()
	{
		return array_merge(
			parent::getDefaultPreFilters(),
			[
				new Scope(Scope::AJAX),
			]
		);
	}

	public function saveTaskAction()
	{
		if (!$this->checkAccess())
		{
			$this->addError(new Error(Loc::getMessage('TIMEMAN_REST_SETTINGS_ERROR_ACCESS_DENIED')));
			return;
		}
		$form = new TaskForm();
		$form->load($this->getRequest());
		if (!$form->validate())
		{
			$this->addError($form->getFirstError());
			return;
		}
		if ($form->isSystem === 'Y')
		{
			$this->addError(new Error(Loc::getMessage('TIMEMAN_REST_SETTINGS_ERROR_CAN_NOT_EDIT_SYSTEM_TASK')));
			return;
		}
		$operations = $form->getOperationsNames();

		// every role includes this operation by default
		$operations[] = UserPermissionsManager::OP_MANAGE_WORKTIME;

		if ($form->id)
		{
			$task = \CTask::getList(['ID' => 'asc'], ['MODULE_ID' => 'timeman', 'ID' => $form->id])->fetch();
			if ($task && !empty($task['ID']))
			{
				\CTask::Update(['NAME' => $form->name], $task['ID']);
				\CTask::SetOperations($task['ID'], $operations, true);
			}
		}
		else
		{
			$newTaskId = CTask::Add([
				'NAME' => $form->name,
				'DESCRIPTION' => '',
				'LETTER' => '',
				'BINDING' => 'module',
				'MODULE_ID' => 'timeman',
			]);
			if ($newTaskId)
			{
				\CTask::SetOperations($newTaskId, $operations, true);
			}
			return [
				'task' => [
					'id' => $newTaskId,
					'name' => $form->name,
				],
			];
		}
	}

	public function deleteTaskAction($id)
	{
		if (!$this->checkAccess())
		{
			$this->addError(new Error(Loc::getMessage('TIMEMAN_REST_SETTINGS_ERROR_ACCESS_DENIED')));
			return;
		}
		CTask::Delete($id);

		$connection = Application::getConnection();
		$connection->query(
			'DELETE FROM ' . $connection->getSqlHelper()->forSql(TaskAccessCodeTable::getTableName()) . ' WHERE TASK_ID = ' . (int)$id
		);
	}

	public function addTaskToAccessCodeAction()
	{
		if (!$this->checkAccess())
		{
			$this->addError(new Error(Loc::getMessage('TIMEMAN_REST_SETTINGS_ERROR_ACCESS_DENIED')));
			return;
		}
		// TODO
		$connection = Application::getConnection();
		$connection->truncateTable(TaskAccessCodeTable::getTableName());

		foreach ($this->getRequest()->get('accesses') as $access)
		{
			$addResult = TaskAccessCodeTable::add([
				'TASK_ID' => $access['taskId'],
				'ACCESS_CODE' => $access['accessCode'],
			]);
			if (!$addResult->isSuccess())
			{
				$this->addErrors($addResult->getErrors());
			}
		}

		(TaskAccessCodeTable::getEntity())->cleanCache();
	}

	private function checkAccess()
	{
		global $USER;
		return \Bitrix\Timeman\Service\DependencyManager::getInstance()
			->getUserPermissionsManager($USER)
			->canUpdateSettings();
	}
}