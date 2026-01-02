<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Im;

enum NotificationType: string
{
	case ChatCreatedForExistingTask = 'chat_created_for_existing_task';
	case TaskHasForumComments = 'task_has_forum_comments';
	case TaskHasLegacyChat = 'task_has_legacy_chat';
	case TaskCreated = 'task_created';
	case ResponsibleChanged = 'responsible_changed';
	case OwnerChanged = 'owner_changed';
	case DeadlineChanged = 'deadline_changed';
	case AuditorsChanged = 'auditors_changed';
	case AccomplicesChanged = 'accomplices_changed';
	case GroupChanged = 'group_changed';
	case TaskOverdue = 'task_overdue';
	case TaskStatusChanged = 'task_status_changed';
	case TaskTimerStarted = 'task_timer_started';
	case TaskTimerStopped = 'task_timer_stopped';
	case ChecklistItemsAdded = 'checklist_items_added';
	case ChecklistItemsDeleted = 'checklist_items_deleted';
	case ChecklistItemsModified = 'checklist_items_modified';
	case ChecklistItemsCompleted = 'checklist_items_completed';
	case ChecklistItemsUnchecked = 'checklist_items_unchecked';
	case ChecklistSingleItemCompleted = 'checklist_single_item_completed';
	case ChecklistSingleItemUnchecked = 'checklist_single_item_unchecked';
	case ChecklistAuditorAssigned = 'checklist_auditor_assigned';
	case ChecklistAccompliceAssigned = 'checklist_accomplice_assigned';
	case ChecklistFilesAdded = 'checklist_files_added';
	case ChecklistCompleted = 'checklist_completed';
	case ChecklistGroupedOperations = 'checklist_grouped_operations';
	case ChecklistAdded = 'checklist_added';
	case ChecklistDeleted = 'checklist_deleted';
	// Add more as needed
}
