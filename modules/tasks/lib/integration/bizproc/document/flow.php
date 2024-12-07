<?php

namespace Bitrix\Tasks\Integration\Bizproc\Document;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Flow\Provider\FlowProvider;
use Bitrix\Tasks\Flow\Provider\TaskProvider;
use Bitrix\Tasks\Flow\Task\Status;
use Bitrix\Tasks\Internals\Registry\GroupRegistry;

if (!Loader::includeModule('bizproc'))
{
	return;
}

class Flow implements \IBPWorkflowDocument
{
	public static function getDocumentType($documentId)
	{
		return 'FLOW';
	}

	public static function getEntityName($entity)
	{
		return Loc::getMessage('TASKS_BP_DOCUMENT_ENTITY_NAME');
	}

	public static function getDocumentName($documentId)
	{
		$documentId = (int)$documentId;
		$flow = FlowRegistry::getInstance()->get($documentId);

		if (!$flow)
		{
			return null;
		}

		return $flow->getName();
	}

	public static function getDocument($documentId)
	{
		$documentId = (int)$documentId;
		$flow = (new FlowProvider())->getFlow($documentId, ['*', 'OPTIONS']);

		$taskProvider = new TaskProvider();
		$groupRegistry = GroupRegistry::getInstance();
		$group = $groupRegistry->get($flow->getGroupId());
		$tasksInQueue = $taskProvider->getTotalTasksWithStatus($flow->getId(), Status::FLOW_PENDING);
		$tasksInProgress = $taskProvider->getTotalTasksWithStatus($flow->getId(), Status::FLOW_AT_WORK);

		$ownerLang = self::getOwnerLanguage($flow->getOwnerId());

		$templateParams = [
			'{ownerId}' => $flow->getOwnerId(),
			'{groupId}' => $flow->getGroupId(),
			'{flowId}' => $flow->getId(),
			'{flowName}' => $flow->getName(),
			'{groupName}' => $group['NAME'] ?? '',
			'{totalTasksInQueue}' => $tasksInQueue,
			'{totalTasksInProgress}' => $tasksInProgress,
			'{targetEfficiency}' => $flow->getTargetEfficiency(),
			'[flowUrl]' => '[url=/company/personal/user/' . $flow->getOwnerId() . '/tasks/flow/?apply_filter=Y&ID_numsel=exact&ID_from=' . $flow->getId() . ']',
			'[/flowUrl]' => '[/url]',
			'[flowSettingsUrl]' => '[url=/company/personal/user/' . $flow->getOwnerId() . '/tasks/flow/?apply_filter=Y&ID_numsel=exact&ID_from=' . $flow->getId() . '&editFormFlowId=' . $flow->getId() . ']',
			'[/flowSettingsUrl]' => '[/url]',
			'[groupUrl]' => '[url=/workgroups/group/' . $flow->getGroupId() . '/tasks/]',
			'[/groupUrl]' => '[/url]',
		];

		return [
			'ID' => $flow->getId(),
			'OWNER' => 'user_' . $flow->getOwnerId(),
			'OWNER_ID' => $flow->getOwnerId(),
			'NAME' => $flow->getName(),
			'GROUP_ID' => $flow->getGroupId(),
			'GROUP_NAME' => $group['NAME'] ?? '',
			'EFFICIENCY' => $flow->getEfficiency(),
			'TARGET_EFFICIENCY' => $flow->getTargetEfficiency(),
			'TOTAL_TASKS_IN_QUEUE_MESSAGE' => Loc::getMessagePlural('TASKS_FLOW_NOTIFICATION_MESSAGE_BUSY_QUEUE', $tasksInQueue, $templateParams, $ownerLang),
			'TOTAL_TASKS_IN_PROGRESS_MESSAGE' => Loc::getMessagePlural('TASKS_FLOW_NOTIFICATION_MESSAGE_BUSY_RESPONSIBLE', $tasksInProgress, $templateParams, $ownerLang),
			'EFFICIENCY_LOWER_MESSAGE' => Loc::getMessage('TASKS_FLOW_NOTIFICATION_MESSAGE_EFFICIENCY_LOWER_MESSAGE', $templateParams, $ownerLang),
			'SWITCH_TO_MANUAL_MESSAGE' => Loc::getMessage('TASKS_FLOW_NOTIFICATION_MESSAGE_FORCED_FLOW_SWITCH_TO_MANUAL_DISTRIBUTION_MESSAGE', $templateParams, $ownerLang),
			'SWITCH_TO_MANUAL_ABSENT_MESSAGE' => Loc::getMessage('TASKS_FLOW_NOTIFICATION_MESSAGE_FORCED_FLOW_SWITCH_TO_MANUAL_DISTRIBUTION_ABSENT_MESSAGE', $templateParams, $ownerLang),
			'DISTRIBUTOR_CHANGE_MESSAGE' => Loc::getMessage('TASKS_FLOW_NOTIFICATION_MESSAGE_FORCED_FLOW_MANUAL_DISTRIBUTOR_CHANGE_MESSAGE', $templateParams, $ownerLang),
			'DISTRIBUTOR_CHANGE_ABSENT_MESSAGE' => Loc::getMessage('TASKS_FLOW_NOTIFICATION_MESSAGE_FORCED_FLOW_MANUAL_DISTRIBUTOR_CHANGE_ABSENT', $templateParams, $ownerLang),
		];
	}

	public static function getDocumentFields($documentType)
	{
		return [
			'ID' => [
				'Name' => 'ID',
				'Type' => 'int',
			],
			'OWNER_ID' => [
				'Name' => 'OWNER_ID',
				'Type' => 'int',
			],
			'NAME' => [
				'Name' => 'Name',
				'Type' => 'string',
			],
		];
	}

	public static function createDocument($parentDocumentId, $arFields)
	{
		throw new NotImplementedException('Currently unavailable.');
	}

	public static function updateDocument($documentId, $arFields)
	{
		throw new NotImplementedException('Currently unavailable.');
	}

	public static function deleteDocument($documentId)
	{
		throw new NotImplementedException('Currently unavailable.');
	}

	public static function publishDocument($documentId)
	{
		throw new NotImplementedException('Currently unavailable.');
	}

	public static function unpublishDocument($documentId)
	{
		throw new NotImplementedException('Currently unavailable.');
	}

	public static function lockDocument($documentId, $workflowId)
	{
		throw new NotImplementedException('Currently unavailable.');
	}

	public static function unlockDocument($documentId, $workflowId)
	{
		throw new NotImplementedException('Currently unavailable.');
	}

	public static function isDocumentLocked($documentId, $workflowId)
	{
		throw new NotImplementedException('Currently unavailable.');
	}

	public static function canUserOperateDocument($operation, $userId, $documentId, $arParameters = array())
	{
		return true;
	}

	public static function canUserOperateDocumentType($operation, $userId, $documentType, $arParameters = array())
	{
		return true;
	}

	public static function getDocumentAdminPage($documentId)
	{
		return null;
	}

	public static function getDocumentForHistory($documentId, $historyIndex)
	{
		return null;
	}

	public static function recoverDocumentFromHistory($documentId, $arDocument)
	{
		return null;
	}

	public static function getAllowableOperations($documentType)
	{
		return null;
	}

	public static function getAllowableUserGroups($documentType)
	{
		return null;
	}

	public static function getUsersFromUserGroup($group, $documentId)
	{
		return [];
	}

	private static function getOwnerLanguage(null|int $ownerId): null|string
	{
		if (!$ownerId)
		{
			return null;
		}

		$res = UserTable::query()
			->where('ID', $ownerId)
			->setSelect([
				'NOTIFICATION_LANGUAGE_ID'
			])
			->exec()
			->fetchObject()
		;

		return ($res)
			? $res->getNotificationLanguageId()
			: null
		;
	}
}