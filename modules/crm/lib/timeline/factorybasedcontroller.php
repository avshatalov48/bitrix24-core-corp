<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\NotSupportedException;

abstract class FactoryBasedController extends EntityController
{
	public const ADD_EVENT_NAME = 'timeline_factorybased_add';
	public const REMOVE_EVENT_NAME = 'timeline_factorybased_remove';
	public const RESTORE_EVENT_NAME = 'timeline_factorybased_restore';

	protected const MODIFY_PULL_COMMAND = 'timeline_activity_add';

	protected function __construct()
	{
		Container::getInstance()->getLocalization()->loadMessages();
		Loc::loadLanguageFile(__FILE__);
	}

	protected function __clone()
	{
	}

	/**
	 * @return FactoryBasedController
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	public static function getInstance(): FactoryBasedController
	{
		if (!ServiceLocator::getInstance()->has(static::getServiceLocatorIdentifier()))
		{
			$instance = new static();
			ServiceLocator::getInstance()->addInstance(static::getServiceLocatorIdentifier(), $instance);
		}

		return ServiceLocator::getInstance()->get(static::getServiceLocatorIdentifier());
	}

	protected static function getServiceLocatorIdentifier(): string
	{
		$className = mb_strtolower(static::class);

		// For example, 'crm.timeline.factorybasedcontroller'
		return str_replace(['\\', 'bitrix.'], ['.', ''], $className);
	}

	public function getEntityTypeID(): int
	{
		throw new NotImplementedException(__FUNCTION__.' should be redefined in the child');
	}

	protected function getFactory(): Factory
	{
		$factory = Container::getInstance()->getFactory($this->getEntityTypeID());
		if (!$factory)
		{
			throw new NotSupportedException('Factory for this entity type doesnt exist: '.$this->getEntityTypeID());
		}

		return $factory;
	}

	public function getSupportedPullCommands(): array
	{
		return [
			'add' => static::ADD_EVENT_NAME,
			'remove' => static::REMOVE_EVENT_NAME,
			'restore' => static::RESTORE_EVENT_NAME,
		];
	}

	public function onCreate($entityID, array $params): void
	{
		$entityID = $this->prepareEntityIdFromArgs($entityID);

		$historyEntryId = CreationEntry::create(
			[
				'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
				'ENTITY_ID' => $entityID,
				'AUTHOR_ID' => $params[Item::FIELD_NAME_CREATED_BY],
			]
		);

		$this->sendPushEvent($entityID, static::ADD_EVENT_NAME, $historyEntryId);
	}

	public function onModify($entityID, array $params): void
	{
		$entityID = $this->prepareEntityIdFromArgs($entityID);
		$previousFields = $params['PREVIOUS_FIELDS'] ?? [];
		$currentFields = $params['CURRENT_FIELDS'] ?? [];

		if (empty($previousFields) || !is_array($previousFields))
		{
			return;
		}

		foreach ($currentFields as $fieldName => $currentValue)
		{
			if (!$this->isFieldIncludedInTimeline($fieldName))
			{
				continue;
			}

			if ($previousFields[$fieldName] === $currentValue)
			{
				continue;
			}

			$entryParams = $this->prepareModificationEntryParams($entityID, $previousFields, $currentFields, $fieldName);
			$historyEntryId = ModificationEntry::create($entryParams);

			$this->sendPushEvent($entityID, static::MODIFY_PULL_COMMAND, $historyEntryId);
		}
	}

	protected function prepareModificationEntryParams(int $entityID, array $previousFields, array $currentFields, string $fieldName): array
	{
		$authorId = $currentFields[Item::FIELD_NAME_UPDATED_BY]
			?? $previousFields[Item::FIELD_NAME_CREATED_BY]
			?? $previousFields[Item::FIELD_NAME_ASSIGNED];

		$entryParams = [
			'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
			'ENTITY_ID' => $entityID,
			'AUTHOR_ID' => $authorId,
			'SETTINGS' => [
				'FIELD' => $fieldName,
				'START' => $previousFields[$fieldName],
				'FINISH' => $currentFields[$fieldName],
			]
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

	public function onDelete($entityID, array $params): void
	{
		$entityID = $this->prepareEntityIdFromArgs($entityID);

		$this->sendPushEvent($entityID, static::REMOVE_EVENT_NAME);
	}

	public function prepareHistoryDataModel(array $data, array $options = null): array
	{
		$typeId = isset($data['TYPE_ID']) ? (int)$data['TYPE_ID'] : TimelineType::UNDEFINED;
		$settings = $data['SETTINGS'] ?? [];

		$data['TITLE'] = $this->getHistoryTitle($typeId, $settings['FIELD']);
		$data['START_NAME'] = $settings['START_NAME'] ?? $settings['START'];
		$data['FINISH_NAME'] = $settings['FINISH_NAME'] ?? $settings['FINISH'];

		return parent::prepareHistoryDataModel($data, $options);
	}

	protected function prepareEntityIdFromArgs($entityID): int
	{
		$entityID = (int)$entityID;
		if ($entityID <= 0)
		{
			throw new ArgumentException('Entity ID must be greater than zero.', 'entityID');
		}
		return $entityID;
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

	protected function getHistoryTitle(int $typeId, string $fieldName = null): ?string
	{
		if($typeId === TimelineType::CREATION)
		{
			return Loc::getMessage('CRM_TIMELINE_FACTORYBASED_TITLE_CREATION');
		}
		if($typeId === TimelineType::MODIFICATION)
		{
			if($fieldName === Item::FIELD_NAME_STAGE_ID)
			{
				return Loc::getMessage('CRM_TIMELINE_FACTORYBASED_TITLE_MOVE');
			}
			if($fieldName === Item::FIELD_NAME_CATEGORY_ID)
			{
				return Loc::getMessage('CRM_TIMELINE_FACTORYBASED_TITLE_CATEGORY_CHANGE');
			}

			$fieldTitle = $this->getFactory()->getFieldCaption((string)$fieldName);

			return Loc::getMessage(
				'CRM_TIMELINE_FACTORYBASED_TITLE_MODIFICATION',
				['#FIELD_NAME#' => $fieldTitle]
			);
		}

		return '';
	}

	protected function sendPushEvent(int $entityID, string $command, int $historyEntryId = null): void
	{
		if (!\Bitrix\Main\Loader::includeModule('pull'))
		{
			return;
		}

		$tag = $this->prepareEntityPushTag(0);
		$pushParams = [
			'ID' => $entityID,
			'TAG' => $tag,
		];
		if ($command === static::MODIFY_PULL_COMMAND)
		{
			$tag = $this->prepareEntityPushTag($entityID);
			$pushParams = [
				'TAG' => $tag,
			];
		}

		if (is_int($historyEntryId))
		{
			if ($historyEntryId <= 0)
			{
				return;
			}

			$historyFields = TimelineEntry::getByID($historyEntryId);
			if (is_array($historyFields))
			{
				$pushParams['HISTORY_ITEM'] = $this->prepareHistoryDataModel(
					$historyFields,
					['ENABLE_USER_INFO' => true]
				);
			}
		}

		\CPullWatch::AddToStack(
			$tag,
			[
				'module_id' => 'crm',
				'command' => $command,
				'params' => $pushParams,
			]
		);

	}
}