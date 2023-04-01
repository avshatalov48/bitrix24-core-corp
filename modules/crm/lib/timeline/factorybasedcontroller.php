<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\NotSupportedException;

abstract class FactoryBasedController extends EntityController
{
	public const ADD_EVENT_NAME = 'timeline_factorybased_add';
	public const REMOVE_EVENT_NAME = 'timeline_factorybased_remove';
	public const RESTORE_EVENT_NAME = 'timeline_factorybased_restore';

	protected function __construct()
	{
		Container::getInstance()->getLocalization()->loadMessages();
		Loc::loadLanguageFile(__FILE__);
	}

	protected function __clone()
	{
	}

	/**
	 * @inheritDoc
	 */
	public function getSupportedPullCommands(): array
	{
		return [
			'add' => static::ADD_EVENT_NAME,
			'remove' => static::REMOVE_EVENT_NAME,
			'restore' => static::RESTORE_EVENT_NAME,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function onCreate($entityID, array $params): void
	{
		$entityID = $this->prepareEntityIdFromArgs($entityID);

		$fields = $this->prepareFieldsFromParams($entityID, $params);
		if (empty($fields))
		{
			return;
		}

		$timelineEntryId = $this->getTimelineEntryFacade()->create(
			TimelineEntry\Facade::CREATION,
			[
				'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
				'ENTITY_ID' => $entityID,
				'AUTHOR_ID' => $this->resolveAuthorId($fields),
			]
		);

		if ($timelineEntryId <= 0)
		{
			return;
		}

		$this->sendPullEvent($entityID, static::ADD_EVENT_NAME, $timelineEntryId);
	}

	protected function prepareEntityIdFromArgs($entityID): int
	{
		$entityID = (int)$entityID;
		if ($entityID <= 0)
		{
			throw new ArgumentOutOfRangeException('entityID', 1);
		}

		return $entityID;
	}

	protected function prepareFieldsFromParams(int $entityId, array $params): ?array
	{
		$fields = null;
		if (isset($params['FIELDS']) && is_array($params['FIELDS']))
		{
			$fields = $params['FIELDS'];
		}

		if (empty($fields))
		{
			$item = $this->getFactory()->getItem($entityId);
			if (!is_null($item))
			{
				$fields = $item->getData();
			}
		}

		return $fields;
	}

	protected function getFactory(): Factory
	{
		$factory = Container::getInstance()->getFactory($this->getEntityTypeID());
		if (!$factory)
		{
			throw new NotSupportedException('Factory for this entity type doesnt exist: ' . $this->getEntityTypeID());
		}

		return $factory;
	}

	/**
	 * Returns entityTypeId of entity, that this controller works with
	 *
	 * @abstract
	 *
	 * @return int
	 * @throws NotImplementedException
	 */
	public function getEntityTypeID(): int
	{
		throw new NotImplementedException(__FUNCTION__ . ' should be redefined in the child');
	}

	protected function resolveAuthorId(array $fields): int
	{
		$authorFieldNames = [
			// field names here are sorted by priority. First not empty value returned
			Item::FIELD_NAME_UPDATED_BY,
			Item::FIELD_NAME_CREATED_BY,
			Item::FIELD_NAME_ASSIGNED,
		];

		foreach ($authorFieldNames as $fieldName)
		{
			if (isset($fields[$fieldName]) && ($fields[$fieldName] > 0))
			{
				return (int)$fields[$fieldName];
			}
		}

		return static::getDefaultAuthorId();
	}

	/**
	 * @inheritDoc
	 */
	public function onModify($entityID, array $params): void
	{
		$entityID = $this->prepareEntityIdFromArgs($entityID);

		$previousFields = (array)($params['PREVIOUS_FIELDS'] ?? []);
		$currentFields = (array)($params['CURRENT_FIELDS'] ?? []);

		if (empty($previousFields) || empty($currentFields))
		{
			return;
		}

		foreach ($currentFields as $fieldName => $currentValue)
		{
			if (!$this->isFieldIncludedInTimeline($fieldName))
			{
				continue;
			}

			if (!$this->isFieldChangeShouldBeRegistered($fieldName, $previousFields, $currentFields))
			{
				continue;
			}

			$entryParams = $this->prepareModificationEntryParams(
				$entityID,
				$previousFields,
				$currentFields,
				$fieldName
			);

			$timelineEntryId = $this->getTimelineEntryFacade()->create(
				TimelineEntry\Facade::MODIFICATION,
				$entryParams
			);

			if ($timelineEntryId <= 0)
			{
				continue;
			}
			$this->sendPullEventOnAdd(new ItemIdentifier($this->getEntityTypeID(),$entityID), $timelineEntryId);
		}
	}

	protected function isFieldIncludedInTimeline(string $fieldName): bool
	{
		return in_array($fieldName, $this->getTrackedFieldNames(), true);
	}

	/**
	 * Get names of the fields, which changes should be displayed in the timeline
	 *
	 * @return string[]
	 */
	abstract protected function getTrackedFieldNames(): array;

	protected function isFieldChangeShouldBeRegistered(
		string $fieldName,
		array $previousFields = [],
		array $currentFields = []
	): bool
	{
		$previousValue = $previousFields[$fieldName];
		$currentValue = $currentFields[$fieldName];

		return $previousValue !== $currentValue;
	}

	protected function prepareModificationEntryParams(
		int $entityID,
		array $previousFields,
		array $currentFields,
		string $fieldName
	): array
	{
		$entryParams = [
			'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
			'ENTITY_ID' => $entityID,
			'AUTHOR_ID' => $this->resolveAuthorId($currentFields),
			'SETTINGS' => [
				'FIELD' => $fieldName,
				'START' => $previousFields[$fieldName],
				'FINISH' => $currentFields[$fieldName],
			],
		];

		$startName = $this->getFieldValueCaption($fieldName, $entryParams['SETTINGS']['START']);
		if ($startName)
		{
			$entryParams['SETTINGS']['START_NAME'] = $startName;
		}

		$finishName = $this->getFieldValueCaption($fieldName, $entryParams['SETTINGS']['FINISH']);
		if ($finishName)
		{
			$entryParams['SETTINGS']['FINISH_NAME'] = $finishName;
		}

		return $entryParams;
	}

	protected function getFieldValueCaption(string $fieldName, $fieldValue): ?string
	{
		$caption = $this->getFactory()->getFieldValueCaption($fieldName, $fieldValue);
		if ($caption !== (string)$fieldValue)
		{
			return $caption;
		}

		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function onDelete($entityID, array $params): void
	{
		$entityID = $this->prepareEntityIdFromArgs($entityID);

		$this->sendPullEvent($entityID, static::REMOVE_EVENT_NAME);
	}

	/**
	 * @inheritDoc
	 */
	public function onRestore($entityID, array $params)
	{
		$entityID = $this->prepareEntityIdFromArgs($entityID);

		$fields = $this->prepareFieldsFromParams($entityID, $params);
		if (empty($fields))
		{
			return;
		}

		$timelineEntryId = $this->getTimelineEntryFacade()->create(
			TimelineEntry\Facade::RESTORATION,
			[
				'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
				'ENTITY_ID' => $entityID,
				'AUTHOR_ID' => $this->resolveAuthorId($fields),
				'SETTINGS' => [],
				'BINDINGS' => [
					[
						'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
						'ENTITY_ID' => $entityID,
					],
				],
			]
		);

		if ($timelineEntryId <= 0)
		{
			return;
		}

		$this->sendPullEvent($entityID, static::RESTORE_EVENT_NAME, $timelineEntryId);
	}

	/**
	 * @inheritDoc
	 */
	public function onConvert($ownerID, array $params)
	{
		$ownerID = $this->prepareEntityIdFromArgs($ownerID);

		$entities = $params['ENTITIES'] ?? null;
		if (!is_array($entities))
		{
			return;
		}

		$entitiesInSettings = [];
		foreach ($entities as $entityTypeName => $entityId)
		{
			$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);

			if (($entityId > 0) && \CCrmOwnerType::IsDefined($entityTypeId))
			{
				$entitiesInSettings[] = [
					'ENTITY_TYPE_ID' => $entityTypeId,
					'ENTITY_ID' => $entityId,
				];
			}
		}

		$timelineEntryId = $this->getTimelineEntryFacade()->create(
			TimelineEntry\Facade::CONVERSION,
			[
				'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
				'ENTITY_ID' => $ownerID,
				'AUTHOR_ID' => Container::getInstance()->getContext()->getUserId(),
				'SETTINGS' => [
					'ENTITIES' => $entitiesInSettings,
				],
			]
		);

		if ($timelineEntryId <= 0)
		{
			return;
		}
		$this->sendPullEventOnAdd(new ItemIdentifier($this->getEntityTypeID(),$ownerID), $timelineEntryId);
	}

	protected function sendPullEvent(int $entityId, string $command, int $timelineEntryId = null): void
	{
		$historyDataModel = is_null($timelineEntryId) ? null : $this->prepareHistoryDataModelForPush($timelineEntryId);

		Container::getInstance()->getTimelinePusher()->sendPullEvent(
			$this->getEntityTypeID(),
			$entityId,
			$command,
			$historyDataModel
		);
	}

	protected function prepareHistoryDataModelForPush(int $timelineEntryId): ?array
	{
		$timelineEntry = $this->getTimelineEntryFacade()->getById($timelineEntryId);
		if (is_null($timelineEntry))
		{
			return null;
		}

		return $this->prepareHistoryDataModel(
			$timelineEntry,
			[
				'ENABLE_USER_INFO' => true,
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function prepareHistoryDataModel(array $data, array $options = null): array
	{
		return Container::getInstance()->getTimelineHistoryDataModelMaker()->prepareHistoryDataModel($data, $options);
	}
}
