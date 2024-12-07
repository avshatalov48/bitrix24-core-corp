<?php

namespace Bitrix\Tasks\Flow\Controllers\Task;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Flow\Access\FlowAccessController;
use Bitrix\Tasks\Flow\Access\FlowAction;
use Bitrix\Tasks\Flow\Controllers\Dto\FlowDto;
use Bitrix\Tasks\Flow\Controllers\Trait\MessageTrait;
use Bitrix\Tasks\Flow\Controllers\Trait\TaskTrait;
use Bitrix\Tasks\Flow\Task\Status;
use Bitrix\Tasks\Flow\Time\DatePresenter;
use Bitrix\Tasks\Provider\Exception\TaskListException;

class Pending extends Controller
{
	use MessageTrait;
	use TaskTrait;

	/**
	 * @restMethod tasks.flow.task.pending.list
	 */
	public function listAction(FlowDto $flowData, PageNavigation $pageNavigation): ?array
	{
		if (!FlowAccessController::can($this->userId, FlowAction::READ, $flowData->id))
		{
			return $this->buildErrorResponse($this->getAccessDeniedError());
		}

		$filter = [
			'FLOW_ID' => $flowData->id,
			'REAL_STATUS' => Status::STATUS_MAP[Status::FLOW_PENDING],
		];

		$select = [
			'ID',
			'CREATED_BY',
			'RESPONSIBLE_ID',
			'CREATED_DATE',
		];

		$order = [
			'CREATED_DATE' => 'ASC',
		];

		$modifier = static function (array &$task) {
			static $date = new DateTime();
			$task['TIME_IN_STATUS'] = DatePresenter::get($date, $task['CREATED_DATE']);
		};

		try
		{
			return $this->getTaskList($select, $filter, $pageNavigation, $order, $modifier);
		}
		catch (TaskListException $e)
		{
			$this->log($e);
			return $this->buildErrorResponse($this->getUnknownError(__LINE__));
		}
	}
}