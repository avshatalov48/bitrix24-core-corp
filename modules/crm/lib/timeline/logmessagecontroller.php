<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Integration\Mobile\Notifier;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Timeline\TimelineEntry\Facade;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

class LogMessageController extends Controller
{
	protected function __construct()
	{
	}

	protected function __clone()
	{
	}

	public function onCreate(array $input, int $typeCategoryId, ?int $authorId = null): void
	{
		if (empty($input))
		{
			return;
		}

		// LEAD or DEAL
		$entityTypeId = $input['ENTITY_TYPE_ID'] ?? null;
		$entityId = $input['ENTITY_ID'] ?? null;
		if (!isset($entityTypeId, $entityId))
		{
			return;
		}
		$settings = $input['SETTINGS'] ?? [];
		$bindings[] = [
			'ENTITY_TYPE_ID' => $entityTypeId,
			'ENTITY_ID' => $entityId
		];
		$sourceId = '';

		// COMPANY or CONTACT
		$baseEntityTypeId = $input['BASE_ENTITY_TYPE_ID'] ?? null;
		$baseEntityId = $input['BASE_ENTITY_ID'] ?? null;

		if (isset($entityTypeId, $entityId))
		{
			$base = [];
			if (isset($baseEntityTypeId))
			{
				$base['ENTITY_TYPE_ID'] = $baseEntityTypeId;
			}

			if (isset($baseEntityId))
			{
				$base['ENTITY_ID'] = $baseEntityId;
			}

			$settings['BASE'] = $base;

			if (isset($baseEntityTypeId, $baseEntityId))
			{
				$bindings[] = [
					'ENTITY_TYPE_ID' => $baseEntityTypeId,
					'ENTITY_ID' => $baseEntityId
				];
			}

			if (isset($input['BASE_SOURCE']))
			{
				$sourceId = $input['BASE_SOURCE'];
			}

			if (isset($input['BASE_SOURCE_ID']))
			{
				$sourceId = $input['BASE_SOURCE_ID'];
			}
		}

		$params = [
			'TYPE_CATEGORY_ID' => $typeCategoryId,
			'ENTITY_TYPE_ID' => $entityTypeId,
			'ENTITY_ID' => $entityId,
			'AUTHOR_ID' => ($authorId > 0) ? $authorId : static::getCurrentOrDefaultAuthorId(),
			//'CREATED' => (new DateTime())->add('PT1S'), // for the correct order of records in the timeline
			'SETTINGS' => $settings,
			'SOURCE_ID' => $sourceId,
			'BINDINGS' => $bindings,
		];
		if ($input['ASSOCIATED_ENTITY_TYPE_ID'])
		{
			$params['ASSOCIATED_ENTITY_TYPE_ID'] = $input['ASSOCIATED_ENTITY_TYPE_ID'];
		}
		if ($input['ASSOCIATED_ENTITY_ID'])
		{
			$params['ASSOCIATED_ENTITY_ID'] = $input['ASSOCIATED_ENTITY_ID'];
		}
		if (isset($input['CREATED']) && $input['CREATED'])
		{
			$params['CREATED'] = $input['CREATED'];
		}
		
		$timelineEntryId = $this->getTimelineEntryFacade()->create(
			Facade::LOG_MESSAGE,
			$params
		);
		if ($timelineEntryId <= 0)
		{
			return;
		}

		foreach ($bindings as $binding)
		{
			$this->sendPullEventOnAdd(
				new ItemIdentifier($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']),
				$timelineEntryId
			);
		}

		if ($typeCategoryId === LogMessageType::PING)
		{
			$this->sendPingMobileNotification($params);
		}
	}

	private function sendPingMobileNotification(array $params): void
	{
		$entityTypeId = (int)($params['ENTITY_TYPE_ID'] ?? \CCrmOwnerType::Undefined);

		if (
			$entityTypeId !== \CCrmOwnerType::Deal
			&& $entityTypeId !== \CCrmOwnerType::Contact
			&& $entityTypeId !== \CCrmOwnerType::Company
		)
		{
			return;
		}

		if ($params['ASSOCIATED_ENTITY_TYPE_ID'] !== \CCrmOwnerType::Activity)
		{
			return;
		}

		$activity = \CCrmActivity::GetByID($params['ASSOCIATED_ENTITY_ID'], false);
		if (!$activity)
		{
			return;
		}

		if (!isset($activity['DEADLINE']) || \CCrmDateTimeHelper::IsMaxDatabaseDate($activity['DEADLINE']))
		{
			return;
		}

		$userId = $params['AUTHOR_ID'];

		$subject = (string)($activity['SUBJECT'] ?? '');
		$title = $subject ?: Loc::getMessage('CRM_TIMELINE_LOG_PING_NOTIFICATION_TITLE');

		$now = new DateTime();
		$deadline = DateTime::createFromUserTime($activity['DEADLINE']);

		$timeToDeadline = $deadline->getTimestamp() - $now->getTimestamp();

		// skip notification if deadline was more than 5 minutes ago
		if ($timeToDeadline <= -300)
		{
			return;
		}

		if ($timeToDeadline > 0)
		{
			$minutesToDeadline = ceil($timeToDeadline / 60);
			$body = Loc::getMessagePlural(
				'CRM_TIMELINE_LOG_PING_STARTS_IN',
				$minutesToDeadline,
				['#OFFSET#' => $minutesToDeadline]
			);
		}
		else
		{
			$body = Loc::getMessage('CRM_TIMELINE_LOG_PING_STARTED');
		}

		$payload = [
			'entityTypeId' => $entityTypeId,
			'entityId' => $params['ENTITY_ID'],
		];

		Notifier::sendImmediate(Notifier::PING_CREATED_MESSAGE_TYPE, $userId, $title, $body, $payload);
	}

	public function prepareHistoryDataModel(array $data, array $options = null): array
	{
		return $data;
	}
}
