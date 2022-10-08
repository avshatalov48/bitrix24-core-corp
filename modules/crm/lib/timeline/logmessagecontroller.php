<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline\TimelineEntry\Facade;
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

		$entityTypeId = $input['ENTITY_TYPE_ID'] ?? null;
		$entityId = $input['ENTITY_ID'] ?? null;
		if (!isset($entityTypeId, $entityId))
		{
			return;
		}
		$settings = [];
		$bindings[] = [
			'ENTITY_TYPE_ID' => $entityTypeId,
			'ENTITY_ID' => $entityId
		];

		$baseEntityTypeId = $input['BASE_ENTITY_TYPE_ID'] ?? null;
		$baseEntityId = $input['BASE_ENTITY_ID'] ?? null;
		if (isset($entityTypeId, $entityId))
		{
			$base = [
				'ENTITY_TYPE_ID' => $baseEntityTypeId,
				'ENTITY_ID' => $baseEntityId
			];

			if (isset($input['BASE_SOURCE']))
			{
				$base['SOURCE'] = $input['BASE_SOURCE'];
			}

			$settings['BASE'] = $base;

			$bindings[] = [
				'ENTITY_TYPE_ID' => $baseEntityTypeId,
				'ENTITY_ID' => $baseEntityId
			];
		}

		$timelineEntryId = Container::getInstance()->getTimelineEntryFacade()->create(
			Facade::LOG_MESSAGE,
			[
				'TYPE_CATEGORY_ID' => $typeCategoryId,
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId,
				'AUTHOR_ID' => ($authorId > 0) ? $authorId : static::getCurrentOrDefaultAuthorId(),
				//'CREATED' => (new DateTime())->add('PT1S'), // for the correct order of records in the timeline
				'SETTINGS' => $settings,
				'BINDINGS' => $bindings,
			]
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
	}
}
