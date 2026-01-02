<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Esg\Detector;

use Bitrix\Tasks\V2\Internal\Integration\Im\NotificationType;

class ChecklistOperationDetector
{
	/**
	 * Detects all checklist operations by comparing before and after states
	 * 
	 * @param array|null $checklistBefore
	 * @param array|null $checklistAfter
	 * @return array Array of ChecklistOperation objects
	 */
	public function detectOperations(?array $checklistBefore, ?array $checklistAfter): array
	{
		$operations = [];
		
		// Handle null cases
		$checklistBefore = $checklistBefore ?? [];
		$checklistAfter = $checklistAfter ?? [];
		
		// Detect entire checklist additions/deletions first (higher priority)
		$operations = array_merge($operations, $this->detectEntireChecklistOperations($checklistBefore, $checklistAfter));
		
		// Only detect item-level operations if no entire checklist operations were detected
		if (empty($operations))
		{
			// Detect added items
			$operations = array_merge($operations, $this->detectAddedItems($checklistBefore, $checklistAfter));
			
			// Detect deleted items
			$operations = array_merge($operations, $this->detectDeletedItems($checklistBefore, $checklistAfter));
		}
		
		// Detect modified items
		$operations = array_merge($operations, $this->detectModifiedItems($checklistBefore, $checklistAfter));
		
		// Detect completed/unchecked items
		$operations = array_merge($operations, $this->detectStatusChanges($checklistBefore, $checklistAfter));
		
		// Detect auditor/accomplice assignments
		$operations = array_merge($operations, $this->detectMemberAssignments($checklistBefore, $checklistAfter));
		
		// Detect file additions
		$operations = array_merge($operations, $this->detectFileAdditions($checklistBefore, $checklistAfter));
		
		return $operations;
	}
	
	/**
	 * Detects entire checklist additions and deletions
	 */
	private function detectEntireChecklistOperations(array $checklistBefore, array $checklistAfter): array
	{
		$operations = [];
		
		// Get root checklist items (parentId = 0) from before and after
		$beforeRootItems = array_filter($checklistBefore, fn(array $item): bool => ($item['parentId'] ?? null) === 0);
		$afterRootItems = array_filter($checklistAfter, fn(array $item): bool => ($item['parentId'] ?? null) === 0);

		$beforeRootIds = array_map(fn(array $item): ?int => $this->getItemId($item), $beforeRootItems);
		$afterRootIds = array_map(fn(array $item): ?int => $this->getItemId($item), $afterRootItems);

		// Detect added checklists
		$addedRootIds = array_diff($afterRootIds, $beforeRootIds);
		foreach ($addedRootIds as $rootId) {
			$rootItem = $this->findItemById($checklistAfter, $rootId);
			if ($rootItem) {
				// Count child items for this checklist
				$childItems = array_filter($checklistAfter, fn(array $item): bool => ($item['parentId'] ?? null) === $rootId);
				$itemCount = count($childItems);
				
				$operations[] = new ChecklistOperation(
					type: NotificationType::ChecklistAdded,
					checklistName: $this->getItemTitle($rootItem),
					itemCount: $itemCount
				);
			}
		}
		
		// Detect deleted checklists
		$deletedRootIds = array_diff($beforeRootIds, $afterRootIds);
		foreach ($deletedRootIds as $rootId) {
			$rootItem = $this->findItemById($checklistBefore, $rootId);
			if ($rootItem) {
				$operations[] = new ChecklistOperation(
					type: NotificationType::ChecklistDeleted,
					checklistName: $this->getItemTitle($rootItem)
				);
			}
		}
		
		return $operations;
	}
	
	/**
	 * Detects newly added checklist items
	 */
	private function detectAddedItems(array $checklistBefore, array $checklistAfter): array
	{
		$operations = [];
		$beforeIds = $this->extractItemIds($checklistBefore);
		$afterIds = $this->extractItemIds($checklistAfter);
		
		$addedIds = array_diff($afterIds, $beforeIds);
		
		if (!empty($addedIds))
		{
			$addedItems = array_filter($checklistAfter, fn(array $item): bool => in_array($this->getItemId($item), $addedIds));

			// Only count non-root items (actual checklist items, not the checklist itself)
			$actualAddedItems = array_filter($addedItems, fn(array $item): bool => ($item['parentId'] ?? null) !== 0);

			if (!empty($actualAddedItems))
			{
				$checklistName = $this->getChecklistNameFromItems($checklistAfter, $actualAddedItems);
				
				$operations[] = new ChecklistOperation(
					type: NotificationType::ChecklistItemsAdded,
					checklistName: $checklistName,
					itemCount: count($actualAddedItems)
				);
			}
		}
		
		return $operations;
	}
	
	/**
	 * Detects deleted checklist items
	 */
	private function detectDeletedItems(array $checklistBefore, array $checklistAfter): array
	{
		$operations = [];
		$beforeIds = $this->extractItemIds($checklistBefore);
		$afterIds = $this->extractItemIds($checklistAfter);
		
		$deletedIds = array_diff($beforeIds, $afterIds);
		
		if (!empty($deletedIds))
		{
			$deletedItems = array_filter($checklistBefore, fn(array $item): bool => in_array($this->getItemId($item), $deletedIds));

			// Only count non-root items (actual checklist items, not the checklist itself)
			$actualDeletedItems = array_filter($deletedItems, fn(array $item): bool => ($item['parentId'] ?? null) !== 0);

			if (!empty($actualDeletedItems))
			{
				$checklistName = $this->getChecklistNameFromItems($checklistBefore, $actualDeletedItems);
				
				$operations[] = new ChecklistOperation(
					type: NotificationType::ChecklistItemsDeleted,
					checklistName: $checklistName,
					itemCount: count($actualDeletedItems)
				);
			}
		}
		
		return $operations;
	}
	
	/**
	 * Detects modified checklist items (title changes, co-executor changes, observer changes)
	 */
	private function detectModifiedItems(array $checklistBefore, array $checklistAfter): array
	{
		$operations = [];
		$modifiedItems = [];
		
		foreach ($checklistAfter as $afterItem)
		{
			$itemId = $this->getItemId($afterItem);
			$beforeItem = $this->findItemById($checklistBefore, $itemId);
			
			if ($beforeItem && $this->isItemModified($beforeItem, $afterItem)) {
				$modifiedItems[] = $afterItem;
			}
		}
		
		if (!empty($modifiedItems))
		{
			$checklistName = $this->getChecklistNameFromItems($checklistAfter, $modifiedItems);
			
			$operations[] = new ChecklistOperation(
				type: NotificationType::ChecklistItemsModified,
				checklistName: $checklistName,
				itemCount: count($modifiedItems)
			);
		}
		
		return $operations;
	}
	
	/**
	 * Detects status changes (completed/unchecked)
	 */
	private function detectStatusChanges(array $checklistBefore, array $checklistAfter): array
	{
		$operations = [];
		$completedItems = [];
		$uncheckedItems = [];
		
		foreach ($checklistAfter as $afterItem)
		{
			$itemId = $this->getItemId($afterItem);
			$beforeItem = $this->findItemById($checklistBefore, $itemId);
			
			if ($beforeItem)
			{
				$wasBefore = $this->isItemComplete($beforeItem);
				$isAfter = $this->isItemComplete($afterItem);
				
				if (!$wasBefore && $isAfter) {
					$completedItems[] = $afterItem;
				} elseif ($wasBefore && !$isAfter) {
					$uncheckedItems[] = $afterItem;
				}
			}
		}
		
		// Process completed items
		$operations = array_merge($operations, $this->detectCompletedItems($completedItems, $checklistAfter));
		
		// Process unchecked items
		$operations = array_merge($operations, $this->detectUncheckedItems($uncheckedItems, $checklistAfter));
		
		return $operations;
	}
	
	/**
	 * Detects completed items and distinguishes between single and multiple operations
	 */
	private function detectCompletedItems(array $completedItems, array $checklistAfter): array
	{
		$operations = [];
		
		if (empty($completedItems))
		{
			return $operations;
		}
		
		$checklistName = $this->getChecklistNameFromItems($checklistAfter, $completedItems);
		
		// Check if completing these items results in entire checklist completion
		// Only consider child items (not the root checklist item with parentId=0)
		$childItems = array_filter($checklistAfter, fn(array $item): bool => ($item['parentId'] ?? null) !== 0);
		$allItemsComplete = true;
		$completeCount = 0;
		$totalCount = count($childItems);
		
		foreach ($childItems as $item)
		{
			if ($this->isItemComplete($item))
			{
				$completeCount++;
			}
			else
			{
				$allItemsComplete = false;
			}
		}

		// If all items are now complete AND we have more than 1 item total, send checklist completion notification
		// This ensures we only send checklist completion for actual checklists, not single items
		if ($allItemsComplete && $totalCount > 1 && count($completedItems) > 0)
		{
			$operations[] = new ChecklistOperation(
				type: NotificationType::ChecklistCompleted,
				checklistName: $checklistName
			);
		}
		else
		{
			// Distinguish between single and multiple item operations
			if (count($completedItems) === 1)
			{
				// Single item - include item name
				$item = $completedItems[0];
				$operations[] = new ChecklistOperation(
					type: NotificationType::ChecklistSingleItemCompleted,
					checklistName: $checklistName,
					itemCount: 1,
					itemName: $this->getItemTitle($item)
				);
			}
			else
			{
				// Multiple items - use count format
				$operations[] = new ChecklistOperation(
					type: NotificationType::ChecklistItemsCompleted,
					checklistName: $checklistName,
					itemCount: count($completedItems)
				);
			}
		}
		
		return $operations;
	}
	
	/**
	 * Detects unchecked items and distinguishes between single and multiple operations
	 */
	private function detectUncheckedItems(array $uncheckedItems, array $checklistAfter): array
	{
		$operations = [];
		
		if (empty($uncheckedItems))
		{
			return $operations;
		}
		
		$checklistName = $this->getChecklistNameFromItems($checklistAfter, $uncheckedItems);
		
		// Distinguish between single and multiple item operations
		if (count($uncheckedItems) === 1)
		{
			// Single item - include item name
			$item = $uncheckedItems[0];
			$operations[] = new ChecklistOperation(
				type: NotificationType::ChecklistSingleItemUnchecked,
				checklistName: $checklistName,
				itemCount: 1,
				itemName: $this->getItemTitle($item)
			);
		}
		else
		{
			// Multiple items - use count format
			$operations[] = new ChecklistOperation(
				type: NotificationType::ChecklistItemsUnchecked,
				checklistName: $checklistName,
				itemCount: count($uncheckedItems)
			);
		}
		
		return $operations;
	}
	
	/**
	 * Detects member assignments (auditors and accomplices)
	 * Note: Co-executor and observer changes are now handled as ITEMS_MODIFIED operations
	 * This method is kept for backward compatibility but returns empty array
	 */
	private function detectMemberAssignments(array $checklistBefore, array $checklistAfter): array
	{
		// Co-executor and observer changes are now classified as ITEMS_MODIFIED operations
		// and are handled in the detectModifiedItems method
		return [];
	}
	
	/**
	 * Detects file additions to checklist items
	 */
	private function detectFileAdditions(array $checklistBefore, array $checklistAfter): array
	{
		$operations = [];
		$itemsWithNewFiles = [];
		$totalNewFiles = 0;
		
		foreach ($checklistAfter as $afterItem)
		{
			$itemId = $this->getItemId($afterItem);
			$beforeItem = $this->findItemById($checklistBefore, $itemId);
			
			if ($beforeItem)
			{
				$beforeFiles = $this->getItemAttachments($beforeItem);
				$afterFiles = $this->getItemAttachments($afterItem);
				
				$newFilesCount = count($afterFiles) - count($beforeFiles);
				if ($newFilesCount > 0) {
					$itemsWithNewFiles[] = $afterItem;
					$totalNewFiles += $newFilesCount;
				}
			}
			else
			{
				// New item with files
				$files = $this->getItemAttachments($afterItem);
				if (!empty($files))
				{
					$itemsWithNewFiles[] = $afterItem;
					$totalNewFiles += count($files);
				}
			}
		}
		
		if (!empty($itemsWithNewFiles))
		{
			$checklistName = $this->getChecklistNameFromItems($checklistAfter, $itemsWithNewFiles);
			$operations[] = new ChecklistOperation(
				type: NotificationType::ChecklistFilesAdded,
				checklistName: $checklistName,
				itemCount: $totalNewFiles
			);
		}
		
		return $operations;
	}
	
	// Helper methods
	
	private function extractItemIds(array $checklist): array
	{
		return array_map(fn(array $item): ?int => $this->getItemId($item), $checklist);
	}
	
	private function getItemId(array $item): ?int
	{
		return isset($item['id']) ? (int)$item['id'] : null;
	}
	
	private function findItemById(array $checklist, ?int $id): ?array
	{
		foreach ($checklist as $item)
		{
			if ($this->getItemId($item) === $id)
			{
				return $item;
			}
		}
		return null;
	}
	
	private function isItemComplete(array $item): bool
	{
		$isComplete = $item['isComplete'] ?? false;
		
		// Handle empty string as false, and convert to boolean
		if ($isComplete === '' || $isComplete === null || $isComplete === false)
		{
			return false;
		}
		
		return (bool)$isComplete;
	}
	
	private function isItemModified(array $beforeItem, array $afterItem): bool
	{
		// Check if title changed
		$beforeTitle = $beforeItem['title'] ?? '';
		$afterTitle = $afterItem['title'] ?? '';
		
		if ($beforeTitle !== $afterTitle) {
			return true;
		}
		
		// Check if co-executors (accomplices) changed
		if ($this->hasMemberChanges($beforeItem, $afterItem, 'accomplices')) {
			return true;
		}
		
		// Check if observers (auditors) changed
		if ($this->hasMemberChanges($beforeItem, $afterItem, 'auditors')) {
			return true;
		}
		
		return false;
	}
	
	private function getItemTitle(array $item): string
	{
		return $item['title'] ?? 'Checklist Item';
	}
	
	/**
	 * Gets the checklist name from the parent checklist based on child items
	 * This method finds the parent checklist name for child items
	 */
	private function getChecklistNameFromItems(array $allItems, array $childItems): string
	{
		if (empty($childItems))
		{
			return 'Checklist';
		}
		
		// Get the parent ID from the first child item
		$firstChild = reset($childItems);
		$parentId = $firstChild['parentId'] ?? null;
		
		// Convert parentId to integer for consistent comparison
		$parentIdInt = $parentId !== null ? (int)$parentId : null;
		
		// Check if this is a root item (parentId is null or explicitly 0 as integer)
		// Note: parentId of '0' (string) means child of item with ID 0, not a root item
		if ($parentIdInt === null || ($parentId === 0 && !is_string($parentId)))
		{
			// If it's already a root item, use its title
			return $this->getItemTitle($firstChild);
		}
		
		// Find the parent checklist item
		foreach ($allItems as $item)
		{
			if ($this->getItemId($item) === $parentIdInt)
			{
				return $this->getItemTitle($item);
			}
		}
		
		// Fallback to default name
		return 'Checklist';
	}
	
	private function getItemMembers(array $item, string $memberType): array
	{
		// Handle the old format (accomplices/auditors arrays)
		if (isset($item[$memberType]))
		{
			return $item[$memberType];
		}
		
		// Handle the new format (members object with type property)
		if (isset($item['members']))
		{
			$members = [];
			foreach ($item['members'] as $member)
			{
				// Check if this member matches the requested type
				// Try multiple possible type values and formats
				$memberTypeValue = $member['type'] ?? $member['memberType'] ?? '';

				$accomplicesTypes = ['A', 'accomplice'];
				$auditorsTypes = ['U', 'O', 'auditor'];
				
				if ($memberType === 'accomplices' && in_array($memberTypeValue, $accomplicesTypes, true))
				{
					$members[] = $member;
				}
				elseif ($memberType === 'auditors' && in_array($memberTypeValue, $auditorsTypes, true))
				{
					$members[] = $member;
				}
			}
			return $members;
		}
		
		// Handle direct member arrays with different keys
		$alternativeKeys = [
			'accomplices' => ['accomplices'],
			'auditors' => ['auditors', 'watchers']
		];
		
		if (isset($alternativeKeys[$memberType])) {
			foreach ($alternativeKeys[$memberType] as $key) {
				if (isset($item[$key])) {
					return $item[$key];
				}
			}
		}
		
		return [];
	}
	
	/**
	 * Checks if there are changes in members (accomplices or auditors) for an item
	 */
	private function hasMemberChanges(array $beforeItem, array $afterItem, string $memberType): bool
	{
		$beforeMembers = $this->getItemMembers($beforeItem, $memberType);
		$afterMembers = $this->getItemMembers($afterItem, $memberType);
		
		$beforeIds = array_map(fn(array $member): int => $member['id'] ?? 0, $beforeMembers);
		$afterIds = array_map(fn(array $member): int => $member['id'] ?? 0, $afterMembers);

		// Sort arrays to ensure consistent comparison
		sort($beforeIds);
		sort($afterIds);
		
		return $beforeIds !== $afterIds;
	}
	
	private function getItemAttachments(array $item): array
	{
		$attachments = $item['attachments'] ?? [];
		
		// Handle different attachment formats
		if (is_array($attachments)) {
			// If it's already an array (old format), return as is
			return $attachments;
		}

		if (is_object($attachments)
			|| (is_array($attachments) && !empty($attachments) && !is_numeric(array_keys($attachments)[0])))
		{
			// If it's an object or associative array (new format), convert to array
			return array_values((array)$attachments);
		}

		return [];
	}
}
