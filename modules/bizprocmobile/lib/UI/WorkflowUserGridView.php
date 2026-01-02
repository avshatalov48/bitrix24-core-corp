<?php

namespace Bitrix\BizprocMobile\UI;

use Bitrix\Main\Web\Json;

class WorkflowUserGridView extends \Bitrix\Bizproc\UI\WorkflowUserGridView
{
	public function jsonSerialize(): array
	{
		return [
			'id' => $this->getId(),
			'data' => [
				'id' => $this->getId(),
				'typeName' => $this->getTypeName(),
				'itemName' => $this->getName(),
				'itemTime' => $this->getTime(),
				'statusText' => $this->getStatusText(),
				'faces' => $this->getFaces(),
				'tasks' => $this->getTasks(),
				'authorId' => $this->getAuthorId(),
				'newCommentsCounter' => $this->getCommentCounter(),
			],
		];
	}

	protected function getTime(): string
	{
		return $this->modified ? (string)($this->modified->getTimestamp()) : '';
	}

	protected function prepareTasks(array $myTasks): array
	{
		$tasks = [];
		foreach ($myTasks as $task)
		{
			$tasks[] = [
				'id' => $task['ID'],
				'name' => $task['~NAME'],
				'activity' => $task['ACTIVITY'],
				'hash' => $this->getTaskHash($task),
				'isInline' => \CBPHelper::getBool($task['IS_INLINE']),
				'buttons' => $this->getTaskControls($task)['buttons'] ?? null,
			];
		}

		return $tasks;
	}

	private function getTaskHash(array $task): string
	{
		$hashData = ['TEMPLATE_ID' => $this->workflow['WORKFLOW_TEMPLATE_ID'] ?? 0];

		if (isset($task['ACTIVITY_NAME']))
		{
			$hashData['ACTIVITY_NAME'] = $task['ACTIVITY_NAME'];
		}

		if (isset($task['ACTIVITY']) && $task['ACTIVITY'] === 'HandleExternalEventActivity')
		{
			$hashData['TASK_ID'] = $task['ID'];
		}

		$parameters = $task['PARAMETERS'] ?? null;

		if (is_array($parameters))
		{
			if (isset($parameters['ShowComment']))
			{
				$hashData['ShowComment'] = $parameters['ShowComment'];
			}

			if (isset($parameters['REQUEST']))
			{
				$hashData['REQUEST'] = $parameters['REQUEST'];
				if (is_array($parameters['REQUEST']))
				{
					foreach ($parameters['REQUEST'] as $property)
					{
						if ($property['Type'] === 'file' || $property['Type'] === 'S:DiskFile')
						{
							$hashData['TASK_ID'] = $task['ID'];

							break;
						}
					}
				}
			}
		}

		return md5(Json::encode($hashData));
	}
}
