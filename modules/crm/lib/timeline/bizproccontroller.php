<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Main;

class BizprocController extends EntityController
{
	//region BizprocController
	public function onWorkflowStatusChange($workflowId, $status)
	{
		if (!$workflowId)
		{
			throw new Main\ArgumentException('Workflow ID is empty.', 'workflowId');
		}

		if ($status !== \CBPWorkflowStatus::Created
			&& $status !== \CBPWorkflowStatus::Completed
			&& $status !== \CBPWorkflowStatus::Terminated
		)
		{
			return;
		}

		$fields = \CBPStateService::getWorkflowStateInfo($workflowId);

		if (!is_array($fields))
		{
			return;
		}

		[$entityTypeName, $entityId] = explode('_', $fields['DOCUMENT_ID'][2]);
		$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);

		$historyEntryID = BizprocEntry::create(
			[
				'AUTHOR_ID' => self::resolveCreatorID($fields),
				'SETTINGS' => [
					'WORKFLOW_ID' => $fields['ID'],
					'WORKFLOW_TEMPLATE_ID' => $fields['WORKFLOW_TEMPLATE_ID'],
					'WORKFLOW_TEMPLATE_NAME' => $fields['WORKFLOW_TEMPLATE_NAME'],
					'WORKFLOW_STATUS' => $status,
					'WORKFLOW_STATUS_NAME' => \CBPWorkflowStatus::Out($status),
				],
				'BINDINGS' => [
					[
						'ENTITY_TYPE_ID' => $entityTypeId,
						'ENTITY_ID' => $entityId,
					],
				],
			]
		);

		$enableHistoryPush = $historyEntryID > 0;
		if ($enableHistoryPush && Main\Loader::includeModule('pull'))
		{
			$pushParams = [];
			if ($enableHistoryPush)
			{
				$historyFields = TimelineEntry::getByID($historyEntryID);
				if (is_array($historyFields))
				{
					$pushParams['HISTORY_ITEM'] = $this->prepareHistoryDataModel(
						$historyFields,
						['ENABLE_USER_INFO' => true]
					);
				}
			}

			$tag = TimelineEntry::prepareEntityPushTag($entityTypeId, $entityId);
			\CPullWatch::AddToStack(
				$tag,
				[
					'module_id' => 'crm',
					'command' => 'timeline_bizproc_status',
					'params' => array_merge($pushParams, ['TAG' => $tag]),
				]
			);
		}
	}

	public function onActivityError(\CBPActivity $activity, $userId, $errorText)
	{
		[$entityTypeName, $entityId] = explode('_', $activity->GetDocumentId()[2]);
		$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);

		$historyEntryID = BizprocEntry::create(
			[
				'AUTHOR_ID' => $userId,
				'SETTINGS' => [
					'TYPE' => 'ACTIVITY_ERROR',
					'WORKFLOW_ID' => $activity->GetWorkflowInstanceId(),
					'ACTIVITY_TITLE' => $activity->Title,
					'ERROR_TEXT' => $errorText
				],
				'BINDINGS' => [
					[
						'ENTITY_TYPE_ID' => $entityTypeId,
						'ENTITY_ID' => $entityId
					]
				]
			]
		);

		$enableHistoryPush = $historyEntryID > 0;
		if ($enableHistoryPush && Main\Loader::includeModule('pull'))
		{
			$pushParams = [];
			if ($enableHistoryPush)
			{
				$historyFields = TimelineEntry::getByID($historyEntryID);
				if (is_array($historyFields))
				{
					$pushParams['HISTORY_ITEM'] = $this->prepareHistoryDataModel(
						$historyFields,
						['ENABLE_USER_INFO' => true]
					);
				}
			}

			$tag = TimelineEntry::prepareEntityPushTag($entityTypeId, $entityId);
			\CPullWatch::AddToStack(
				$tag,
				[
					'module_id' => 'crm',
					'command' => 'timeline_bizproc_status',
					'params' => array_merge($pushParams, ['TAG' => $tag]),
				]
			);
		}
	}

	public function onDebugDocumentStatusChange(string $documentId, int $userId, string $text)
	{
		[$entityTypeName, $entityId] = explode('_', $documentId);
		$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);

		$historyEntryID = BizprocEntry::create(
			[
				'AUTHOR_ID' => $userId,
				'SETTINGS' => [
					'TYPE' => 'AUTOMATION_DEBUG_INFORMATION',
					'AUTOMATION_DEBUG_TEXT' => $text,
				],
				'BINDINGS' => [
					[
						'ENTITY_TYPE_ID' => $entityTypeId,
						'ENTITY_ID' => $entityId,
					],
				],
			]
		);

		$enableHistoryPush = $historyEntryID > 0;
		if ($enableHistoryPush && Main\Loader::includeModule('pull'))
		{
			$pushParams = [];
			$historyFields = TimelineEntry::getByID($historyEntryID);
			if (is_array($historyFields))
			{
				$pushParams['HISTORY_ITEM'] = $this->prepareHistoryDataModel(
					$historyFields,
					['ENABLE_USER_INFO' => true]
				);
			}

			$tag = TimelineEntry::prepareEntityPushTag($entityTypeId, $entityId);
			\CPullWatch::AddToStack(
				$tag,
				[
					'module_id' => 'crm',
					'command' => 'timeline_bizproc_status',
					'params' => array_merge($pushParams, ['TAG' => $tag]),
				]
			);
		}
	}

	protected static function resolveCreatorID(array $fields)
	{
		$authorID = 0;
		if(isset($fields['STARTED_BY']))
		{
			$authorID = (int)$fields['STARTED_BY'];
		}

		if($authorID <= 0)
		{
			//Set portal admin as default creator
			$authorID = 1;
		}

		return $authorID;
	}

	public function prepareHistoryDataModel(array $data, array $options = null)
	{
		$settings = $data['SETTINGS'] ?? [];
		$data['TYPE'] = $settings['TYPE'] ?? null;
		$data['ACTIVITY_TITLE'] = $settings['ACTIVITY_TITLE'] ?? null;
		$data['ERROR_TEXT'] = $settings['ERROR_TEXT'] ?? null;
		$data['WORKFLOW_TEMPLATE_NAME'] = $settings['WORKFLOW_TEMPLATE_NAME'] ?? null;
		$data['WORKFLOW_STATUS_NAME'] = $settings['WORKFLOW_STATUS_NAME'] ?? null;
		$data['AUTOMATION_DEBUG_TEXT'] = $settings['AUTOMATION_DEBUG_TEXT'] ?? null;
		unset($data['SETTINGS']);
		return parent::prepareHistoryDataModel($data, $options);
	}
	//endregion
}
