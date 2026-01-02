<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Im\Action;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\User;
use Bitrix\Tasks\V2\Internal\Integration\Im\MessageSenderInterface;
use Bitrix\Tasks\V2\Internal\Integration\Im\NotificationType;
use Bitrix\Tasks\V2\Internal\Service\Esg\Detector\ChecklistOperationGroup;

class NotifyChecklistGroupedOperations
{
	public function __construct(
		Task $task,
		MessageSenderInterface $sender,
		?User $triggeredBy = null,
		array $operations = [],
	)
	{
		$checklistName = $operations['checklistName'] ?? '';
		$operationGroup = $operations['operationGroup'] ?? null;

		if (!$operationGroup instanceof ChecklistOperationGroup)
		{
			return;
		}

		$code = 'TASKS_IM_CHECKLIST_GROUPED_OPERATIONS_' . $triggeredBy?->getGender()->value;

		$bulletList = $this->buildBulletList($operationGroup, $triggeredBy);

		$message = Loc::getMessage(
			$code,
			[
				'#USER#' => '[USER=' . $triggeredBy?->id . ']' . $triggeredBy?->name . '[/USER]',
				'#CHECKLIST_NAME#' => $checklistName,
				'#OPERATIONS#' => $bulletList,
			]
		);

		$sender->sendMessage(task: $task, text: $message);
	}

	private function buildBulletList(ChecklistOperationGroup $operationGroup, $triggeredBy): string
	{
		$bullets = [];
		$operationCounts = $operationGroup->getOperationCounts();

		foreach ($operationCounts as $operationType => $count)
		{
			$bullet = $this->formatOperationBullet($operationType, $count, $triggeredBy);
			if ($bullet)
			{
				$bullets[] = $bullet;
			}
		}

		return implode("\n", $bullets);
	}

	private function formatOperationBullet(string $operationType, int $count, $triggeredBy): ?string
	{
		$genderSuffix = $triggeredBy?->getGender()->value === 'F' ? '_F' : '';
		
		return match ($operationType)
		{
			NotificationType::ChecklistItemsCompleted->value => 
				Loc::getMessagePlural(
					code: 'TASKS_IM_CHECKLIST_GROUPED_COMPLETED' . $genderSuffix,
					value: $count,
					replace: ['#COUNT#' => $count]
				),
			NotificationType::ChecklistItemsModified->value =>
				Loc::getMessagePlural(
					code: 'TASKS_IM_CHECKLIST_GROUPED_MODIFIED' . $genderSuffix,
					value: $count,
					replace: ['#COUNT#' => $count]
				),
			NotificationType::ChecklistItemsDeleted->value =>
				Loc::getMessagePlural(
					code: 'TASKS_IM_CHECKLIST_GROUPED_DELETED' . $genderSuffix,
					value: $count,
					replace: ['#COUNT#' => $count]
				),
			NotificationType::ChecklistItemsAdded->value =>
				Loc::getMessagePlural(
					code: 'TASKS_IM_CHECKLIST_GROUPED_ADDED' . $genderSuffix,
					value: $count,
					replace: ['#COUNT#' => $count]
				),
			NotificationType::ChecklistItemsUnchecked->value =>
				Loc::getMessagePlural(
					code: 'TASKS_IM_CHECKLIST_GROUPED_UNCHECKED' . $genderSuffix,
					value: $count,
					replace: ['#COUNT#' => $count]
				),
			NotificationType::ChecklistFilesAdded->value =>
				Loc::getMessagePlural(
					code: 'TASKS_IM_CHECKLIST_GROUPED_FILES_ADDED' . $genderSuffix,
					value: $count,
					replace: ['#COUNT#' => $count]
				),
			NotificationType::ChecklistAccompliceAssigned->value =>
				Loc::getMessage('TASKS_IM_CHECKLIST_GROUPED_ACCOMPLICE_ASSIGNED' . $genderSuffix),
			NotificationType::ChecklistAuditorAssigned->value => 
				Loc::getMessage('TASKS_IM_CHECKLIST_GROUPED_AUDITOR_ASSIGNED' . $genderSuffix),
			default => null,
		};
	}
}