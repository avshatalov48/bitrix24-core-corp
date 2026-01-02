<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Esg\Handler;

use Bitrix\Tasks\V2\Internal\Integration\Im\ChatNotificationInterface;
use Bitrix\Tasks\V2\Internal\Integration\Im\NotificationType;
use Bitrix\Tasks\V2\Internal\Repository\UserRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Service\Esg\Detector\ChecklistOperationDetector;
use Bitrix\Tasks\V2\Internal\Service\Esg\Detector\ChecklistOperation;
use Bitrix\Tasks\V2\Internal\Service\Esg\Detector\ChecklistOperationGrouper;
use Bitrix\Tasks\V2\Internal\Service\Esg\Detector\ChecklistOperationGroup;
use Bitrix\Tasks\V2\Internal\Logger;
use Bitrix\Tasks\V2\Public\Command\CheckList\SaveCheckListCommand;
use Bitrix\Tasks\V2\Internal\Entity\User;
use Throwable;

class SaveCheckListHandler
{
	public function __construct(
		private readonly UserRepositoryInterface     $userRepository,
		private readonly ChatNotificationInterface   $chatNotification,
		private readonly ChecklistOperationDetector  $operationDetector,
		private readonly ChecklistOperationGrouper   $operationGrouper,
		private readonly Logger                       $logger
	)
	{
	}

	public function handle(SaveCheckListCommand $command): void
	{
		try
		{
			// Get checklist data from before and after states
			$checklistBefore = $command->taskBeforeUpdate?->checklist ?? [];
			$checklistAfter = $command->task->checklist ?? [];
			
			// Normalize both arrays to have the same structure (indexed arrays)
			$normalizedBefore = $this->normalizeChecklistArray($checklistBefore);
			$normalizedAfter = $this->normalizeChecklistArray($checklistAfter);
			
			// Detect all operations that occurred
			$operations = $this->operationDetector->detectOperations($normalizedBefore, $normalizedAfter);
			
			// Group operations by checklist and operation type
			$groupedOperations = $this->operationGrouper->groupOperations($operations);

			foreach ($groupedOperations as $checklistName => $operationGroup)
			{
				$this->processOperationGroup($operationGroup, $checklistName, $command);
			}
			
		}
		catch (Throwable $e)
		{
			// Log error but don't let notification failures affect checklist save
			$this->logger->logError($e);
		}
	}
	
	/**
	 * Normalizes checklist array to ensure consistent structure for comparison
	 * Converts both indexed arrays and key-value arrays to indexed arrays
	 */
	private function normalizeChecklistArray(array $checklist): array
	{
		// If already an indexed array (numeric keys starting from 0), return as is
		if (array_keys($checklist) === range(0, count($checklist) - 1))
		{
			return $checklist;
		}
		
		// If it's a key-value array (nodeId keys), convert to indexed array
		return array_values($checklist);
	}
	
	private function processOperationGroup(
		ChecklistOperationGroup $operationGroup, 
		string $checklistName, 
		SaveCheckListCommand $command
	): void
	{
		// Check if entire checklist was completed - this takes priority over individual item completions
		$checklistCompletedOperations = $operationGroup->getOperationsByType(NotificationType::ChecklistCompleted);

		if (!empty($checklistCompletedOperations))
		{
			// Send only the checklist completed message, skip individual item completions
			foreach ($checklistCompletedOperations as $operation)
			{
				$this->sendIndividualNotification($operation, $command);
			}

			return;
		}
		
		if ($operationGroup->hasMultipleOperationTypes())
		{
			// Send grouped message
			$this->sendGroupedNotification($operationGroup, $checklistName, $command);

			return;
		}

		// Send individual messages for single operation type
		foreach ($operationGroup->getOperations() as $operation)
		{
			$this->sendIndividualNotification($operation, $command);
		}
	}

	private function sendGroupedNotification(
		ChecklistOperationGroup $operationGroup, 
		string $checklistName, 
		SaveCheckListCommand $command
	): void
	{
		try
		{
			// Get the user who triggered the change
			$triggeredBy = $this->userRepository->getByIds([$command->updatedBy])->findOneById($command->updatedBy);
			
			$this->chatNotification->notify(
				type: NotificationType::ChecklistGroupedOperations,
				task: $command->task,
				args: [
					'triggeredBy' => $triggeredBy,
					'checklistName' => $checklistName,
					'operationGroup' => $operationGroup
				],
			);
		}
		catch (Throwable $e)
		{
			// Log error but continue processing
			$this->logger->logError($e);
		}
	}

	private function sendIndividualNotification(ChecklistOperation $operation, SaveCheckListCommand $command): void
	{
		try
		{
			// Get the user who triggered the change
			$triggeredBy = $this->userRepository->getByIds([$command->updatedBy])->findOneById($command->updatedBy);
			
			// Prepare arguments for notification
			$args = [
				'triggeredBy' => $triggeredBy,
				'checklistName' => $operation->getChecklistName(),
				'itemCount' => $operation->getItemCount(),
			];
			
			// For file additions, use fileCount instead of itemCount
			if ($operation->getType() === NotificationType::ChecklistFilesAdded)
			{
				$args['fileCount'] = $operation->getItemCount();
			}
			
			// Add assignee name and ID if present (for auditor/accomplice assignments)
			if ($operation->getAssigneeName() !== null)
            {
				$args['assignee'] = new User(
					id: $operation->getAssigneeId(),
					name: $operation->getAssigneeName()
				);
			}
			
			// Add item name if present (for single item notifications)
			if ($operation->getItemName() !== null)
			{
				$args['itemName'] = $operation->getItemName();
			}
			
			// Send notification
			$this->chatNotification->notify(
				type: $operation->getType(),
				task: $command->task,
				args: $args
			);
			
		}
		catch (Throwable $e)
		{
			// Log individual operation errors but continue processing other operations
			$this->logger->logError($e);
		}
	}
}
