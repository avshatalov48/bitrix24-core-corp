<?php

namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Bizproc\Automation\Trigger\Entity\TriggerTable;
use Bitrix\Tasks\Integration\Bizproc;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;

class RobotService implements Errorable
{
	const ERROR_COULD_NOT_UPDATE_ROBOTS = 'TASKS_RS_01';

	private $errorCollection;

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	public function updateRobotsOfLastSprint(int $groupId, array $stageIdsMap): bool
	{
		try
		{
			$projectDocumentType = Bizproc\Document\Task::resolveScrumProjectTaskType($groupId);
			$currentDocumentType = ['tasks', Bizproc\Document\Task::class, $projectDocumentType];

			$helper = new \Bitrix\Bizproc\Copy\Integration\Helper($currentDocumentType);

			$robotIds = $helper->getWorkflowTemplateIds();
			$triggerIds = $helper->getTriggerIds();

			foreach ($robotIds as $robotId)
			{
				$queryResult = \CBPWorkflowTemplateLoader::getList([], ['ID' => $robotId]);
				if ($fields = $queryResult->fetch())
				{
					if (array_key_exists($fields['DOCUMENT_STATUS'], $stageIdsMap))
					{
						$fields['DOCUMENT_STATUS'] = $stageIdsMap[$fields['DOCUMENT_STATUS']];

						if (is_array($fields['TEMPLATE']))
						{
							foreach ($fields['TEMPLATE'] as &$activity)
							{
								$activity = $this->updateChangeStageActivity($activity, $stageIdsMap);
							}
						}

						\CBPWorkflowTemplateLoader::update($robotId, $fields, true);
					}
				}
			}

			foreach ($triggerIds as $triggerId)
			{
				$queryResult = TriggerTable::getList(['filter' => ['=ID' => $triggerId]]);
				if ($fields = $queryResult->fetch())
				{
					if (array_key_exists($fields['DOCUMENT_STATUS'], $stageIdsMap))
					{
						$fields['DOCUMENT_STATUS'] = $stageIdsMap[$fields['DOCUMENT_STATUS']];

						TriggerTable::update($triggerId, $fields);
					}
				}
			}

			return true;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_UPDATE_ROBOTS)
			);

			return false;
		}
	}

	public function hasRobots(int $groupId, array $stageIds): bool
	{
		$projectDocumentType = Bizproc\Document\Task::resolveScrumProjectTaskType($groupId);
		$currentDocumentType = ['tasks', Bizproc\Document\Task::class, $projectDocumentType];

		$helper = new \Bitrix\Bizproc\Copy\Integration\Helper($currentDocumentType);

		$robotIds = $helper->getWorkflowTemplateIds();
		$triggerIds = $helper->getTriggerIds();

		if (empty($robotIds) && empty($triggerIds))
		{
			return false;
		}

		$has = false;
		$queryResult = \CBPWorkflowTemplateLoader::getList(
			[],
			['ID' => $robotIds],
			false,
			false,
			['DOCUMENT_STATUS']
		);
		while ($fields = $queryResult->fetch())
		{
			if (in_array($fields['DOCUMENT_STATUS'], $stageIds))
			{
				$has = true;

				break;
			}
		}

		if ($has)
		{
			return true;
		}

		$queryResult = TriggerTable::getList(
			[
				'select' => ['DOCUMENT_STATUS'],
				'filter' => ['=ID' => $triggerIds]
			]
		);
		while ($fields = $queryResult->fetch())
		{
			if (in_array($fields['DOCUMENT_STATUS'], $stageIds))
			{
				$has = true;

				break;
			}
		}

		return $has;
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	private function updateChangeStageActivity($activity, array $stageIdsMap): array
	{
		if ($activity['Type'] === 'TasksChangeStageActivity')
		{
			if (isset($activity['Properties']['TargetStage']))
			{
				$activity['Properties']['TargetStage'] = $stageIdsMap[$activity['Properties']['TargetStage']];
			}
		}

		if (is_array($activity['Children']))
		{
			foreach ($activity['Children'] as &$child)
			{
				$child = $this->updateChangeStageActivity($child, $stageIdsMap);
			}
		}

		return $activity;
	}
}