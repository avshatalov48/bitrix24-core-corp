<?php

namespace Bitrix\BizprocMobile\Controller;

use Bitrix\BizprocMobile\EntityEditor\Converter;
use Bitrix\BizprocMobile\EntityEditor\TaskProvider;
use Bitrix\BizprocMobile\UI\CommentCounterView;
use Bitrix\BizprocMobile\Workflow\Task\Fields;
use Bitrix\BizprocMobile\UI\TaskView;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
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

	public function loadDetailsAction(int $taskId, int $targetUserId = null)
	{
		$currentUserId = (int)($this->getCurrentUser()?->getId());
		$targetUserId = $targetUserId !== null && $targetUserId > 0 ? $targetUserId : $currentUserId;

		$taskService = new Bizproc\Api\Service\TaskService(
			new Bizproc\Api\Service\TaskAccessService($currentUserId)
		);

		$taskRequest = new Bizproc\Api\Request\TaskService\GetUserTaskRequest($taskId, $targetUserId);

		$getTaskResponse = $taskService->getUserTask($taskRequest);
		if (!$getTaskResponse->isSuccess())
		{
			$this->addErrors($getTaskResponse->getErrors());

			return null;
		}

		$task = $getTaskResponse->getTask();

		if ($currentUserId !== $targetUserId)
		{
			if (isset($task['PARAMETERS']['AccessControl']) && $task['PARAMETERS']['AccessControl'] === 'Y')
			{
				$task['DESCRIPTION'] = '';
			}

			unset($task['FIELDS'], $task['BUTTONS']);
		}

		$provider = null;
		if (isset($task['FIELDS'], $task['COMPLEX_DOCUMENT_ID']))
		{
			$converter = (new Converter($task['FIELDS'], $task['COMPLEX_DOCUMENT_ID']));
			$converter->setContext(Converter::CONTEXT_TASK);

			$provider = new TaskProvider((int)$task['ID'], $converter->toMobile()->getConvertedProperties());
		}

		$commentCounter = new CommentCounterView($task['WORKFLOW_ID'], $currentUserId);

		return [
			'task' => new TaskView($task),
			'allCount' => $this->countParallelTasks($task['WORKFLOW_ID'], $targetUserId),
			'editor' => $provider ? (new FormWrapper($provider))->getResult() : null,
			'taskResponsibleMessage' => Loc::getMessage(
				'M_BP_LIB_CONTROLLER_TASK_RESPONSIBLE',
				['#USER#' => $this->getUserFormatName($targetUserId)]
			),
			'rights' => [
				'delegate' =>
					(int)$task['DELEGATION_TYPE'] !== 3 // todo: use \CBPTaskDelegationType::ExactlyNone
					&& (((int)$task['DELEGATION_TYPE'] !== \CBPTaskDelegationType::None) || $this->isCurrentUserAdmin()),
			],
			'commentCounter' => $commentCounter,
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

	public function doCollectionAction(): ?AjaxJson
	{
		$createInternalError = static fn (string $reason) => new Error('', 0, ['reason' => $reason]);

		if (!$this->request->isJson())
		{
			$this->addError($createInternalError('Wrong request format. Expected json in request body.'));

			return null;
		}

		$taskIds = $this->request->getJsonList()->get('taskIds');
		$taskRequest = $this->request->getJsonList()->get('taskRequest');

		if (!is_array($taskIds))
		{
			$this->addError(
				$createInternalError('Wrong request format. Expected taskIds array in request json body.')
			);

			return null;
		}
		if (!is_array($taskRequest))
		{
			$this->addError(
				$createInternalError('Wrong request format. Expected taskRequest in request json body.')
			);

			return null;
		}

		$results = [];
		foreach ($taskIds as $id)
		{
			if (is_int($id))
			{
				$results[] = $this->doAction($id, $taskRequest) ?? false;
			}
			else
			{
				$results[] = false;
			}
		}

		$this->errorCollection->clear();

		return AjaxJson::createSuccess($results);
	}

	public function doAction(int $taskId, array $taskRequest): ?bool
	{
		$currentUserId = $this->getCurrentUser()->getId();

		$taskService = new Bizproc\Api\Service\TaskService(
			new Bizproc\Api\Service\TaskAccessService($currentUserId)
		);

		$taskFields = new Fields($taskId);
		if (isset($taskRequest['fields']) && is_array($taskRequest['fields']))
		{
			$taskRequest['fields'] = $taskFields->extract($taskRequest['fields'], $currentUserId);
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

	private function getUserFormatName(int $userId)
	{
		$format = \CSite::GetNameFormat(false);
		$user = \CUser::GetList(
			'id',
			'asc',
			['ID_EQUAL_EXACT' => $userId],
			[
				'FIELDS' => [
					'TITLE',
					'NAME',
					'LAST_NAME',
					'SECOND_NAME',
					'NAME_SHORT',
					'LAST_NAME_SHORT',
					'SECOND_NAME_SHORT',
					'EMAIL',
					'ID'
				],
			]
		)->Fetch();

		return $user ? \CUser::FormatName($format, $user, true, false) : '';
	}

	private function isCurrentUserAdmin(): bool
	{
		$currentUser = $this->getCurrentUser();
		if ($currentUser)
		{
			return (
				$currentUser->isAdmin()
				|| (Loader::includeModule('bitrix24') && \CBitrix24::IsPortalAdmin($currentUser->getId()))
			);
		}

		return false;
	}
}
