<?php

namespace Bitrix\Crm\Integration\Catalog;

use Bitrix\Crm\Timeline\StoreDocumentController;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class DocumentCardTimeline
{
	private const TIMELINE_GUID_TEMPLATE = 'store_document_#ID#_timeline';
	private const EDITOR_GUID_TEMPLATE = 'store_document_#ID#_editor';

	public static function onCollectRightColumnContent(Event $event)
	{
		\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/common.js');

		$documentId = $event->getParameter('DOCUMENT_ID');
		$documentFields = $event->getParameter('DOCUMENT_FIELDS');
		$guid = str_replace('#ID#', $documentId, self::TIMELINE_GUID_TEMPLATE);
		$editorId = str_replace('#ID#', $documentId, self::EDITOR_GUID_TEMPLATE);

		$activityEditorParams = [
			'CONTAINER_ID' => '',
			'EDITOR_ID' => $editorId,
			'PREFIX' => 'store_document',
			'ENABLE_UI' => false,
			'ENABLE_TOOLBAR' => false,
			'ENABLE_EMAIL_ADD' => false,
			'ENABLE_TASK_ADD' => false,
			'MARK_AS_COMPLETED_ON_VIEW' => false,
			'SKIP_VISUAL_COMPONENTS' => 'Y'
		];

		$entityInfo = [
			'ENTITY_ID' => $documentId,
			'ENTITY_TYPE_ID' => \CCrmOwnerType::StoreDocument,
			'ENTITY_TYPE_NAME' => \CCrmOwnerType::StoreDocumentName,
			'TITLE' => $documentFields['TITLE']
		];

		$timelineParams = [
			'GUID' => $guid,
			'ENTITY_ID' => $documentId,
			'ENTITY_TYPE_ID' => \CCrmOwnerType::StoreDocument,
			'ENTITY_TYPE_NAME' => \CCrmOwnerType::StoreDocumentName,
			'ENTITY_INFO' => $entityInfo,
			'ACTIVITY_EDITOR_ID' => $editorId,
			'ENABLE_TASK' => false,
			'ENABLE_WAIT' => false,
			'ENABLE_SMS' => false,
			'ENABLE_EMAIL' => false,
			'ENABLE_SALESCENTER' => false,
			'ENABLE_CALL' => false,
			'ENABLE_MEETING' => false,
			'ENABLE_VISIT' => false,
			'ENABLE_ZOOM' => false,
		];

		$eventResult = [
			'COMPONENT_NAME' => 'bitrix:crm.store_document.timeline',
			'COMPONENT_TEMPLATE' => '.default',
			'COMPONENT_PARAMS' => [
				'ACTIVITY_EDITOR_PARAMS' => $activityEditorParams,
				'TIMELINE_PARAMS' => $timelineParams,
			],
		];

		return new EventResult(EventResult::SUCCESS, $eventResult);
	}

	public static function onDocumentCreate($documentId, $documentFields)
	{
		\Bitrix\Crm\Timeline\StoreDocumentController::getInstance()->onCreate(
			$documentId,
			[
				'FIELDS' => $documentFields,
			]
		);
	}

	public static function onDocumentUpdate($documentId, $updatedFields, $oldFields)
	{
		StoreDocumentController::getInstance()->onModify(
			$documentId,
			[
				'FIELDS' => $updatedFields,
				'OLD_FIELDS' => $oldFields,
			]
		);
	}

	public static function onConductFailureAfterSave(Event $event)
	{
		$documentId = $event->getParameter('DOCUMENT_ID');
		$errorMessage = $event->getParameter('ERROR_MESSAGE');
		$userId = $event->getParameter('USER_ID');
		StoreDocumentController::getInstance()->onModify(
			$documentId,
			[
				'ERROR' => 'CONDUCT',
				'ERROR_MESSAGE' => $errorMessage,
				'FIELDS' => [
					'MODIFIED_BY' => $userId,
				],
			]
		);
	}
}
