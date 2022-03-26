<?php

namespace Bitrix\Crm\Integration\Rest;

use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\Service;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Rest\EventTable;
use Bitrix\Rest\RestException;

class EventManager
{
	public const EVENT_DYNAMIC_ITEM_ADD = 'onCrmDynamicItemAdd';
	public const EVENT_DYNAMIC_ITEM_UPDATE = 'onCrmDynamicItemUpdate';
	public const EVENT_DYNAMIC_ITEM_DELETE = 'onCrmDynamicItemDelete';
	public const EVENT_DYNAMIC_TYPE_ADD = 'onCrmTypeAdd';
	public const EVENT_DYNAMIC_TYPE_UPDATE = 'onCrmTypeUpdate';
	public const EVENT_DYNAMIC_TYPE_DELETE = 'onCrmTypeDelete';
	public const EVENT_DOCUMENTGENERATOR_DOCUMENT_ADD = 'onCrmDocumentGeneratorDocumentAdd';
	public const EVENT_DOCUMENTGENERATOR_DOCUMENT_UPDATE = 'onCrmDocumentGeneratorDocumentUpdate';
	public const EVENT_DOCUMENTGENERATOR_DOCUMENT_DELETE = 'onCrmDocumentGeneratorDocumentDelete';
	public const EVENT_USER_FIELD_CONFIG_ADD = 'onCrmTypeUserFieldAdd';
	public const EVENT_USER_FIELD_CONFIG_UPDATE = 'onCrmTypeUserFieldUpdate';
	public const EVENT_USER_FIELD_CONFIG_DELETE = 'onCrmTypeUserFieldDelete';
	public const EVENT_USER_FIELD_CONFIG_SET_ENUM_VALUES = 'onCrmTypeUserFieldSetEnumValues';

	public function getDynamicItemCommonEventNames(): array
	{
		return [
			static::EVENT_DYNAMIC_ITEM_ADD,
			static::EVENT_DYNAMIC_ITEM_UPDATE,
			static::EVENT_DYNAMIC_ITEM_DELETE,
		];
	}

	public function getItemEventNameWithEntityTypeId(string $eventName, int $entityTypeId): string
	{
		return $eventName . '_' . $entityTypeId;
	}

	public function deleteDynamicItemEventsByEntityTypeId(int $entityTypeId): Result
	{
		$result = new Result();

		if (!Loader::includeModule('rest'))
		{
			return $result;
		}

		$eventNames = [];
		foreach ($this->getDynamicItemCommonEventNames() as $eventName)
		{
			$eventNames[] = $this->getItemEventNameWithEntityTypeId($eventName, $entityTypeId);
		}

		$records = EventTable::getList([
			'select' => ['ID'],
			'filter' => [
				'@EVENT_NAME' => $eventNames,
			],
		]);
		while ($object = $records->fetchObject())
		{
			$deleteResult = $object->delete();
			if (!$deleteResult->isSuccess())
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		return $result;
	}

	public function registerEventBindings(array &$bindings): void
	{
		if (!Loader::includeModule('rest'))
		{
			return;
		}
		if(!isset($bindings[\CRestUtil::EVENTS]))
		{
			$bindings[\CRestUtil::EVENTS] = [];
		}

		$this->registerDynamicItemsEvents($bindings);
		$this->registerDynamicTypesEvents($bindings);
		$this->registerDocumentGeneratorEvents($bindings);
		$this->registerUserFieldConfigEvents($bindings);
	}

	protected function getItemEventInfo(string $eventName): array
	{
		$callback = [$this, 'processItemEvent'];

		return [
			'crm',
			$eventName,
			$callback,
			[
				'category' => \Bitrix\Rest\Sqs::CATEGORY_CRM,
			],
		];
	}

	protected function registerDynamicItemsEvents(array &$bindings): void
	{
		// common events
		$eventNames = $this->getDynamicItemCommonEventNames();
		foreach ($eventNames as $eventName)
		{
			$bindings[\CRestUtil::EVENTS][$eventName] = $this->getItemEventInfo($eventName);
		}

		// type specific events
		$typesMap = Service\Container::getInstance()->getDynamicTypesMap()->load([
			'isLoadStages' => false,
			'isLoadCategories' => false,
		]);
		foreach ($typesMap->getTypes() as $type)
		{
			foreach ($eventNames as $eventName)
			{
				$typeSpecificEventName = $this->getItemEventNameWithEntityTypeId($eventName, $type->getEntityTypeId());
				$bindings[\CRestUtil::EVENTS][$typeSpecificEventName] = $this->getItemEventInfo($typeSpecificEventName);
			}
		}
	}

	public function processItemEvent(array $arParams, array $arHandler): array
	{
		$event = $arParams[0] ?? null;
		if (!$event)
		{
			throw new RestException('event object not found trying to process event');
		}
		$item = $event->getParameter('item');
		$id = $event->getParameter('id');

		if (!$id)
		{
			$id = $item->getId();
		}

		if (!$item || !($item instanceof \Bitrix\Crm\Item\Dynamic))
		{
			throw new RestException('item not found trying to process event');
		}

		return [
			'FIELDS' => [
				'ID' => $id,
				'ENTITY_TYPE_ID' => $item->getEntityTypeId(),
			],
		];
	}

	protected function registerDocumentGeneratorEvents(array &$bindings): void
	{
		$eventNames = [
			static::EVENT_DOCUMENTGENERATOR_DOCUMENT_ADD,
			static::EVENT_DOCUMENTGENERATOR_DOCUMENT_UPDATE,
			static::EVENT_DOCUMENTGENERATOR_DOCUMENT_DELETE,
		];
		foreach ($eventNames as $eventName)
		{
			$bindings[\CRestUtil::EVENTS][$eventName] = $this->getDocumentGeneratorDocumentEventInfo($eventName);
		}
	}

	protected function registerDynamicTypesEvents(array &$bindings): void
	{
		$eventNames = [
			static::EVENT_DYNAMIC_TYPE_ADD,
			static::EVENT_DYNAMIC_TYPE_UPDATE,
			static::EVENT_DYNAMIC_TYPE_DELETE,
		];
		foreach ($eventNames as $eventName)
		{
			$bindings[\CRestUtil::EVENTS][$eventName] = $this->getTypeEventInfo($eventName);
		}
	}

	protected function getTypeEntityEventName(string $eventName): ?string
	{
		$map = [
			static::EVENT_DYNAMIC_TYPE_ADD => 'OnAfterAdd',
			static::EVENT_DYNAMIC_TYPE_UPDATE => 'OnAfterUpdate',
			static::EVENT_DYNAMIC_TYPE_DELETE => 'OnAfterDelete',
		];

		return $map[$eventName] ?? null;
	}

	protected function getTypeEventInfo(string $eventName): array
	{
		$callback = [$this, 'processTypeEvent'];

		$typeEntity = TypeTable::getEntity();
		$typeEventName = $this->getTypeEntityEventName($eventName);
		$entityEventName = $typeEntity->getNamespace() . $typeEntity->getName() . '::' . $typeEventName;

		return [
			'crm',
			$entityEventName,
			$callback,
			[
				'category' => \Bitrix\Rest\Sqs::CATEGORY_CRM,
			],
		];
	}

	public function processTypeEvent(array $arParams, array $arHandler): array
	{
		$event = $arParams[0] ?? null;
		if (!$event)
		{
			throw new RestException('event object not found trying to send event');
		}
		$id = $event->getParameter('id');

		if (!$id)
		{
			throw new RestException('type not found trying to process event');
		}

		return [
			'FIELDS' => [
				'ID' => $id,
			],
		];
	}

	protected function getDocumentGeneratorDocumentEventInfo(string $eventName): array
	{
		$callback = [$this, 'processDocumentGeneratorDocumentEvent'];

		return [
			'crm',
			$eventName,
			$callback,
			[
				'category' => \Bitrix\Rest\Sqs::CATEGORY_CRM,
			],
		];
	}

	public function processDocumentGeneratorDocumentEvent(array $arParams, array $arHandler): array
	{
		$event = $arParams[0] ?? null;
		if (!$event)
		{
			throw new RestException('event object not found trying to send event');
		}
		$document = $event->getParameter('document');
		if (!$document)
		{
			throw new RestException('document not found trying to process event');
		}

		return [
			'FIELDS' => [
				'ID' => $document->ID,
				'ENTITY_TYPE_ID' => $event->getParameter('entityTypeId'),
				'ENTITY_ID' => $event->getParameter('entityId'),
			],
		];
	}

	protected function registerUserFieldConfigEvents(array &$bindings): void
	{
		$eventNames = [
			static::EVENT_USER_FIELD_CONFIG_ADD,
			static::EVENT_USER_FIELD_CONFIG_UPDATE,
			static::EVENT_USER_FIELD_CONFIG_DELETE,
			static::EVENT_USER_FIELD_CONFIG_SET_ENUM_VALUES,
		];
		foreach ($eventNames as $eventName)
		{
			$bindings[\CRestUtil::EVENTS][$eventName] = $this->getUserFieldConfigEventInfo($eventName);
		}
	}

	protected function getUserFieldConfigEventInfo(string $eventName): array
	{
		$callback = [$this, 'processUserFieldConfigEvent'];

		return [
			'crm',
			$eventName,
			$callback,
			[
				'category' => \Bitrix\Rest\Sqs::CATEGORY_CRM,
			],
		];
	}

	public function processUserFieldConfigEvent(array $arParams, array $arHandler): array
	{
		$event = $arParams[0] ?? null;
		if (!$event || !($event instanceof Event))
		{
			throw new RestException("Event object not found trying to process event");
		}

		$eventParameters = $event->getParameters();
		$id = $eventParameters['id'] ?? 0;
		$entityId = $eventParameters['entityId'] ?? '';
		$fieldName = $eventParameters['fieldName'] ?? '';

		if (empty($id) || empty($entityId) || empty($fieldName))
		{
			throw new RestException("Wrong event parameters trying to process event");
		}

		return [
			'FIELDS' => [
				'ID' => $id,
				'ENTITY_ID' => $entityId,
				'FIELD_NAME' => $fieldName,
			],
		];
	}
}
