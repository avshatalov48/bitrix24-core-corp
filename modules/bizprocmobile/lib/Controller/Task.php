<?php

namespace Bitrix\BizprocMobile\Controller;

use Bitrix\BizprocMobile\EntityEditor\Converter;
use Bitrix\BizprocMobile\EntityEditor\TaskProvider;
use Bitrix\BizprocMobile\Workflow\Task\Fields;
use Bitrix\BizprocMobile\UI\TaskView;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Mobile\UI\EntityEditor\FormWrapper;
use Bitrix\Mobile\UI\StatefulList\BaseController;
use Bitrix\Bizproc;
use Bitrix\Bizproc\Workflow\Task\TaskTable;

Loader::requireModule('bizproc');
Loader::requireModule('mobile');

class Task extends BaseController
{
	public function configureActions(): array
	{
		return [];
	}

	public function loadDetailsAction(int $taskId)
	{
		$currentUserId = $this->getCurrentUser()->getId();

		$taskService = new Bizproc\Api\Service\TaskService(
			new Bizproc\Api\Service\TaskAccessService($currentUserId)
		);

		$tasksRequest = new Bizproc\Api\Request\TaskService\GetUserTasksRequest(
			additionalSelectFields: ['NAME', 'DESCRIPTION'],
			filter: [
				'ID' => $taskId,
				'USER_ID' => $currentUserId,
			],
		);
		$getTasksResult = $taskService->getTasks($tasksRequest);
		if (!$getTasksResult->isSuccess())
		{
			$this->addErrors($getTasksResult->getErrors());

			return null;
		}

		$task = current($getTasksResult->getTasks());

		if (!$task)
		{
			$this->addError(new Error('Task not found')); // todo: localize

			return null;
		}

		$provider = null;
		if (isset($task['FIELDS'], $task['COMPLEX_DOCUMENT_ID']))
		{
			$converter = (new Converter($task['FIELDS'], $task['COMPLEX_DOCUMENT_ID']));
			$converter->setContext(Converter::CONTEXT_TASK);

			$provider = new TaskProvider((int)$task['ID'], $converter->toMobile()->getConvertedProperties());
		}

		return [
			'task' => new TaskView($task),
			'allCount' => $this->countParallelTasks($task['WORKFLOW_ID'], $currentUserId),
			'editor' => $provider ? (new FormWrapper($provider))->getResult() : null,
		];
	}

	private function countParallelTasks(string $workflowId, int $userId): int
	{
		return TaskTable::getCount([
			'=WORKFLOW_ID' => $workflowId,
			'=TASK_USERS.USER_ID' => $userId,
			'=TASK_USERS.STATUS' => \CBPTaskUserStatus::Waiting,
		]);
	}

	public function doAction(int $taskId, array $taskRequest)
	{
		$currentUserId = $this->getCurrentUser()->getId();

		$taskService = new Bizproc\Api\Service\TaskService(
			new Bizproc\Api\Service\TaskAccessService($currentUserId)
		);

		$taskFields = new Fields($taskId);
		if (isset($taskRequest['fields']) && is_array($taskRequest['fields']))
		{
			$taskRequest['fields'] = $taskFields->extract($taskRequest['fields']);
		}

		if (isset($taskRequest['INLINE_USER_STATUS']) && is_numeric($taskRequest['INLINE_USER_STATUS']))
		{
			$taskRequest['INLINE_USER_STATUS'] = \CBPTaskUserStatus::resolveStatus($taskRequest['INLINE_USER_STATUS']);
		}

		$request = new Bizproc\Api\Request\TaskService\DoTaskRequest(
			taskId: $taskId,
			userId: $currentUserId,
			taskRequest: $taskRequest,
		);

		$getTasksResult = $taskService->doTask($request);
		if (!$getTasksResult->isSuccess())
		{
			$this->addErrors($getTasksResult->getErrors());

			return null;
		}

		$taskFields->savePendingFiles();

		return true;
	}

	protected function getDefaultPreFilters(): array
	{
		return [
			new ActionFilter\Authentication(),
			new ActionFilter\Csrf(),
			new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
			new ActionFilter\Scope(ActionFilter\Scope::NOT_REST),
		];
	}
}
