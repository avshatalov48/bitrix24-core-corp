<?php

namespace Bitrix\Crm\Timeline;


use Bitrix\Crm\Data\EntityFieldsHelper;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Recurring\Manager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use CPullWatch;

class DynamicRecurringController extends DynamicController
{
	public function onExpose(int $entityId, array $params): void
	{
		$fields = $params['FIELDS'] ?? null;
		$newItemFields = is_array($fields) ? $fields : null;

		if (!is_array($newItemFields))
		{
			return;
		}

		$recurringEntityId = (int)($newItemFields['RECURRING_ID'] ?? 0);
		$settings = [];

		if ($recurringEntityId > 0)
		{
			$settings['BASE'] = [
				'ENTITY_TYPE_ID' => $this->entityTypeId,
				'ENTITY_ID' => $recurringEntityId,
			];
		}

		$historyEntryId = CreationEntry::create(
			[
				'ENTITY_TYPE_ID' => $this->entityTypeId,
				'ENTITY_ID' => $entityId,
				'AUTHOR_ID' => $this->resolveCreatorId($newItemFields),
				'SETTINGS' => $settings,
				'BINDINGS' => [
					[
						'ENTITY_TYPE_ID' => $this->entityTypeId,
						'ENTITY_ID' => $entityId,
					],
				],
			],
		);

		$this->pushHistory($historyEntryId, $entityId, self::ADD_EVENT_NAME);

		if ($recurringEntityId > 0)
		{
			$historyEntryId = ConversionEntry::create(
				[
					'ENTITY_TYPE_ID' => $this->entityTypeId,
					'ENTITY_ID' => $recurringEntityId,
					'AUTHOR_ID' => $this->resolveCreatorId($newItemFields),
					'SETTINGS' => [
						'ENTITIES' => [
							[
								'ENTITY_TYPE_ID' => $this->entityTypeId,
								'ENTITY_ID' => $entityId,
							],
						],
					],
				],
			);

			$this->sendPullEventOnAdd(
				new ItemIdentifier($this->entityTypeId, $recurringEntityId),
				$historyEntryId,
			);
		}
	}

	public function onModify($entityId, array $params): void
	{
		$entityId = (int)$entityId;

		if ($entityId <= 0)
		{
			throw new ArgumentException('Owner ID must be greater than zero.', 'entityId');
		}

		$currentFields = (
			isset($params['CURRENT_FIELDS']) && is_array($params['CURRENT_FIELDS'])
				? $params['CURRENT_FIELDS']
				: []
		);
		$previousFields = (
			isset($params['PREVIOUS_FIELDS']) && is_array($params['PREVIOUS_FIELDS'])
				? $params['PREVIOUS_FIELDS']
				: []
		);

		$fieldsMap = $params['FIELDS_MAP'] ?? null;
		if (is_array($fieldsMap))
		{
			$currentFields = EntityFieldsHelper::replaceFieldNamesByMap($currentFields, $fieldsMap);
			$previousFields = EntityFieldsHelper::replaceFieldNamesByMap($previousFields, $fieldsMap);
		}

		if (empty($params['FIELD_NAME']))
		{
			return;
		}

		$fieldName = $params['FIELD_NAME'];

		$previousValue = $previousFields['VALUE'] ?? '';
		$currentValue = $currentFields['VALUE'] ?? $previousValue;

		if ($previousValue !== $currentValue)
		{
			$historyEntryID = ModificationEntry::create(
				[
					'ENTITY_TYPE_ID' => $this->entityTypeId,
					'ENTITY_ID' => $entityId,
					'AUTHOR_ID' => $this->resolveEditorId($currentFields),
					'SETTINGS' => [
						'FIELD' => $fieldName,
						'START' => $previousValue,
						'FINISH' => $currentValue,
					],
				],
			);
			$this->sendPullEventOnAdd(
				new ItemIdentifier($this->entityTypeId, $entityId),
				$historyEntryID,
			);
		}
	}

	public function onCreate($entityId, array $params): void
	{
		$entityId = (int)$entityId;

		if ($entityId <= 0)
		{
			throw new ArgumentException('Owner ID must be greater than zero.', 'entityId');
		}

		$fields = (
			isset($params['FIELDS']) && is_array($params['FIELDS'])
				? $params['FIELDS']
				: null
		);

		if (is_array($fields))
		{
			$fieldsMap = $params['FIELDS_MAP'] ?? null;
			if (is_array($fieldsMap))
			{
				$fields = EntityFieldsHelper::replaceFieldNamesByMap($fields, $fieldsMap);
			}
		}

		if (empty($fields))
		{
			return;
		}

		$entityTypeId = $this->getEntityTypeID();
		$item = Container::getInstance()->getFactory($entityTypeId)?->getItem($entityId);

		if ($item?->getIsRecurring() !== true)
		{
			return;
		}

		$recurringFields = (
			isset($params['RECURRING']) && is_array($params['RECURRING'])
				? $params['RECURRING']
				: null
		);
		if (!is_array($recurringFields))
		{
			$fields = Manager::getList(
				[
					'filter' => ['ID' => $entityId],
					'limit' => 1,
				],
				Manager::DYNAMIC,
			);
			$recurringFields = $fields->fetch();
		}

		if (!is_array($recurringFields))
		{
			return;
		}

		$settings = [];
		if ((int)($recurringFields['BASED_ID'] ?? 0) > 0)
		{
			$settings['BASE'] = [
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => (int)$recurringFields['BASED_ID'],
			];
		}

		$historyEntryID = CreationEntry::create(
			[
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId,
				'AUTHOR_ID' => $this->resolveCreatorId($fields),
				'SETTINGS' => $settings,
				'BINDINGS' => [
					[
						'ENTITY_TYPE_ID' => $entityTypeId,
						'ENTITY_ID' => $entityId,
					],
				],
			],
		);

		$this->pushHistory($historyEntryID, $entityId, self::ADD_EVENT_NAME);

		$basedId = (int)($recurringFields['BASED_ID'] ?? 0);
		if ($basedId > 0)
		{
			$historyEntryID = ConversionEntry::create(
				[
					'ENTITY_TYPE_ID' => $entityTypeId,
					'ENTITY_ID' => $basedId,
					'AUTHOR_ID' => $this->resolveCreatorId($fields),
					'SETTINGS' => [
						'ENTITIES' => [
							[
								'ENTITY_TYPE_ID' => $entityTypeId,
								'ENTITY_ID' => $entityId,
							],
						],
					],
				],
			);
			$this->sendPullEventOnAdd(
				new ItemIdentifier($entityTypeId, $entityId),
				$historyEntryID,
			);
		}
	}

	protected function resolveCreatorId(array $fields): int
	{
		$authorId = 0;

		if (isset($fields[Item::FIELD_NAME_CREATED_BY]))
		{
			$authorId = (int)$fields[Item::FIELD_NAME_CREATED_BY];
		}

		if ($authorId <= 0 && isset($fields[Item::FIELD_NAME_UPDATED_BY]))
		{
			$authorId = (int)$fields[Item::FIELD_NAME_UPDATED_BY];
		}

		if ($authorId <= 0 && isset($fields[Item::FIELD_NAME_ASSIGNED]))
		{
			$authorId = (int)$fields[Item::FIELD_NAME_ASSIGNED];
		}

		if ($authorId <= 0)
		{
			//Set portal admin as default creator
			$authorId = 1;
		}

		return $authorId;
	}

	protected function resolveEditorId(array $fields): int
	{
		$editorId = 0;

		if (isset($fields[Item::FIELD_NAME_UPDATED_BY]))
		{
			$editorId = (int)$fields[Item::FIELD_NAME_UPDATED_BY];
		}

		if ($editorId <= 0 && isset($fields[Item::FIELD_NAME_CREATED_BY]))
		{
			$editorId = (int)$fields[Item::FIELD_NAME_CREATED_BY];
		}

		if ($editorId <= 0 && isset($fields[Item::FIELD_NAME_ASSIGNED]))
		{
			$editorId = (int)$fields[Item::FIELD_NAME_ASSIGNED];
		}

		if ($editorId <= 0)
		{
			//Set portal admin as default editor
			$editorId = 1;
		}

		return $editorId;
	}

	protected function pushHistory(int $historyEntryId, int $ownerId, string $command): void
	{
		if ($historyEntryId <= 0 || !Loader::includeModule('pull'))
		{
			return;
		}

		$pushParams = [];
		$historyFields = TimelineEntry::getByID($historyEntryId);
		if (is_array($historyFields))
		{
			$pushParams['HISTORY_ITEM'] = $this->prepareHistoryDataModel(
				$historyFields,
				[
					'ENABLE_USER_INFO' => true,
					'IS_RECURRING' => true,
				],
			);
		}

		if (!empty($historyFields['ASSOCIATED_ENTITY_TYPE_ID']))
		{
			$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag(
				$historyFields['ASSOCIATED_ENTITY_TYPE_ID'],
				$ownerId,
			);

			CPullWatch::AddToStack(
				$tag,
				[
					'module_id' => 'crm',
					'command' => $command,
					'params' => $pushParams,
				],
			);
		}
	}
}
