<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Operation\FieldAfterSaveResult;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class Category extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$result = new Result();
		if (!$item->isChanged($this->getName()))
		{
			return $result;
		}

		$factory = Container::getInstance()->getFactory($item->getEntityTypeId());
		if (!$factory)
		{
			return $result->addError($this->getFactoryNotFoundError($item->getEntityTypeId()));
		}

		$this->validateCategoryId($factory, $item->get($this->getName()), $result);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$eventNames = $this->getSettings()['eventNames'] ?? [];
		if (!empty($eventNames['onBeforeChange']) && !$item->isNew())
		{
			$this->sendOnBeforeCategoryChangeEvent($eventNames['onBeforeChange'], $item);

			$this->validateCategoryId($factory, $item->get($this->getName()), $result);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		return $result;
	}

	private function validateCategoryId(Factory $factory, ?int $categoryId, Result $result): void
	{
		if (!is_int($categoryId) || !$factory->isCategoryExists($categoryId))
		{
			$result->addError($this->getValueNotValidError());

			return;
		}

		if (!$factory->isCategoryAvailable($categoryId))
		{
			$result->addError(
				new Error('New category is not available', static::ERROR_CODE_CATEGORY_NOT_AVAILABLE)
			);
		}
	}

	public function processAfterSave(Item $itemBeforeSave, Item $item, Context $context = null): FieldAfterSaveResult
	{
		$result = new FieldAfterSaveResult();

		if ($itemBeforeSave->get($this->getName()) === $item->get($this->getName()))
		{
			return $result;
		}

		$permissionEntityType = \Bitrix\Crm\Service\UserPermissions::getItemPermissionEntityType($itemBeforeSave);
		\Bitrix\Crm\Security\Manager::resolveController($permissionEntityType)
			->unregister($permissionEntityType, $item->getId())
		;

		$eventNames = $this->getSettings()['eventNames'] ?? [];
		if (!empty($eventNames['onAfterChange']) && !$itemBeforeSave->isNew())
		{
			$this->sendOnAfterCategoryChangeEvent($eventNames['onAfterChange'], $item);
		}

		return $result;
	}

	private function sendOnBeforeCategoryChangeEvent(string $onBeforeEventName, Item $item): void
	{
		$event = new \Bitrix\Main\Event(
			'crm',
			$onBeforeEventName,
			[
				'id' => $item->getId(),
				'categoryId' => $item->get($this->getName()),
				'stageId' => $item->hasField(Item::FIELD_NAME_STAGE_ID) ? (string)$item->getStageId() : null,
			],
		);

		$event->send();

		foreach ($event->getResults() as $eventResult)
		{
			if ($eventResult->getType() === \Bitrix\Main\EventResult::ERROR)
			{
				continue;
			}

			$parameters = $eventResult->getParameters();

			if (isset($parameters['categoryId']))
			{
				$categoryIdFromEvent = (int)$parameters['categoryId'];

				$item->set($this->getName(), $categoryIdFromEvent);
			}

			if (isset($parameters['stageId']))
			{
				$stageIdFromEvent = (string)$parameters['stageId'];

				if ($item->hasField(Item::FIELD_NAME_STAGE_ID))
				{
					$item->setStageId($stageIdFromEvent);
				}
			}
		}
	}

	private function sendOnAfterCategoryChangeEvent(string $onAfterEventName, Item $item): void
	{
		$event = new \Bitrix\Main\Event(
			'crm',
			$onAfterEventName,
			[
				'id' => $item->getId(),
				'categoryId' => $item->get($this->getName()),
				'stageId' => $item->hasField(Item::FIELD_NAME_STAGE_ID) ? (string)$item->getStageId() : null,
			],
		);

		$event->send();
	}
}
