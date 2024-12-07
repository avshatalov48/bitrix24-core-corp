<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Timeline\TimelineEntry\Facade;

class LogMessageController extends Controller
{
	protected function __construct()
	{
	}

	protected function __clone()
	{
	}

	public function onCreate(array $input, int $typeCategoryId, ?int $authorId = null): ?int
	{
		if (empty($input))
		{
			return null;
		}

		// LEAD or DEAL
		$entityTypeId = $input['ENTITY_TYPE_ID'] ?? null;
		$entityId = $input['ENTITY_ID'] ?? null;

		if (!isset($entityTypeId, $entityId))
		{
			return null;
		}

		$settings = $input['SETTINGS'] ?? [];
		$bindings = [
			[
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId,
			],
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

		if (!empty($input['ASSOCIATED_ENTITY_TYPE_ID']))
		{
			$params['ASSOCIATED_ENTITY_TYPE_ID'] = $input['ASSOCIATED_ENTITY_TYPE_ID'];
		}

		if (!empty($input['ASSOCIATED_ENTITY_ID']))
		{
			$params['ASSOCIATED_ENTITY_ID'] = $input['ASSOCIATED_ENTITY_ID'];
		}

		if (isset($input['CREATED']) && $input['CREATED'])
		{
			$params['CREATED'] = $input['CREATED'];
		}

		$timelineEntryId = $this->getTimelineEntryFacade()->create(Facade::LOG_MESSAGE, $params);

		if ($timelineEntryId <= 0)
		{
			return null;
		}

		foreach ($bindings as $binding)
		{
			$this->sendPullEventOnAdd(
				new ItemIdentifier($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']),
				$timelineEntryId
			);
		}

		return $timelineEntryId;
	}

	public function prepareHistoryDataModel(array $data, array $options = null): array
	{
		return $data;
	}

	public function prepareSearchContent(array $params): string
	{
		return '';
	}
}
