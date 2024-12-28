<?php

namespace Bitrix\Tasks\Flow\Controllers\Task;

use Bitrix\Main\Engine\AutoWire\BinderArgumentException;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Flow\Access\FlowAccessController;
use Bitrix\Tasks\Flow\Access\FlowAction;
use Bitrix\Tasks\Flow\Controllers\Dto\DaysAgoDto;
use Bitrix\Tasks\Flow\Controllers\Dto\FlowDto;
use Bitrix\Tasks\Flow\Controllers\Trait\MessageTrait;
use Bitrix\Tasks\Flow\Controllers\Trait\TaskTrait;
use Bitrix\Tasks\Flow\Task\Status;
use Bitrix\Tasks\Flow\Time\DatePresenter;
use Bitrix\Tasks\Provider\Exception\TaskListException;

class Completed extends Controller
{
	use MessageTrait;
	use TaskTrait;

	/**
	 * @throws BinderArgumentException
	 */
	public function getAutoWiredParameters(): array
	{
		return [
			new ExactParameter(
				FlowDto::class,
				'flowData',
				function($className, $flowData): ?FlowDto {
					$dto = FlowDto::createFromArray($flowData);
					$dto->checkPrimary();

					return $dto;
				}
			),
			new ExactParameter(
				DaysAgoDto::class,
				'ago',
				function($className, $ago): ?DaysAgoDto {
					$dto = DaysAgoDto::createFromArray($ago);
					$dto->validate();

					return $dto->setDate((new DateTime())->add("-{$dto->days} days"));
				}
			),
		];
	}

	/**
	 * @restMethod tasks.flow.task.completed.list
	 */
	public function listAction(FlowDto $flowData, PageNavigation $pageNavigation, DaysAgoDto $ago): ?array
	{
		if (!FlowAccessController::can($this->userId, FlowAction::READ, $flowData->id))
		{
			return $this->buildErrorResponse($this->getAccessDeniedError());
		}

		$filter = [
			'FLOW_ID' => $flowData->id,
			'REAL_STATUS' => Status::STATUS_MAP[Status::FLOW_COMPLETED],
			'>=CLOSED_DATE' => $ago->date,
		];

		$select = [
			'ID',
			'CREATED_BY',
			'RESPONSIBLE_ID',
			'CLOSED_DATE',
			$this->getStartPointExpression(),
		];

		$order = [
			'START_POINT' => 'DESC',
		];

		$modifier = static function (array &$task) {
			$task['TIME_IN_STATUS'] = DatePresenter::get($task['CLOSED_DATE'] ?? new DateTime(), $task['START_POINT']);
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

	private function getStartPointExpression(): ExpressionField
	{
		return new ExpressionField(
			'START_POINT',
			'CASE
							WHEN CREATED_DATE IS NOT NULL THEN CREATED_DATE
							WHEN DATE_START IS NOT NULL THEN DATE_START
							ELSE NOW()
						END'
		);
	}
}
