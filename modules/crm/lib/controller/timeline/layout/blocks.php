<?php

namespace Bitrix\Crm\Controller\Timeline\Layout;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Controller\Validator\Timeline\BindingExists;
use Bitrix\Crm\Engine\ActionFilter\CheckRestApplicationContext;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Item\Configurable;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Timeline\Controller;
use Bitrix\Crm\Timeline\Entity\Object\TimelineBinding;
use Bitrix\Crm\Timeline\Entity\Repository\RestAppLayoutBlocksRepository;
use Bitrix\Main\Error;

class Blocks extends Base
{
	protected RestAppLayoutBlocksRepository $restAppLayoutBlocksRepository;

	protected function getDefaultPreFilters(): array
	{
		return [
			...parent::getDefaultPreFilters(),
			new CheckRestApplicationContext(),
		];
	}

	protected function init(): void
	{
		parent::init();

		$this->restAppLayoutBlocksRepository = new RestAppLayoutBlocksRepository();
	}

	public function getAction(\CRestServer $server, int $entityTypeId, int $entityId, int $timelineId): ?array
	{
		$bindings = [$timelineId, $entityTypeId, $entityId];
		$itemIdentifier = ItemIdentifier::createByParams($entityTypeId, $entityId);
		if ($itemIdentifier === null)
		{
			$this->addError(ErrorCode::getOwnerNotFoundError());

			return null;
		}

		if (
			!$this->validateBindings(...$bindings)
			|| !$this->validateReadPermission($itemIdentifier)
		)
		{
			return null;
		}

		$layout = $this->restAppLayoutBlocksRepository
			->fetchLayoutBlocksForTimelineItem($timelineId, $server->getClientId())
		;

		return [
			'layout' => $layout,
		];
	}

	public function setAction(
		\CRestServer $server,
		int $entityTypeId,
		int $entityId,
		int $timelineId,
		array $layout = [],
	): array|null
	{
		$bindings = [$timelineId, $entityTypeId, $entityId];
		$itemIdentifier = ItemIdentifier::createByParams($entityTypeId, $entityId);
		if ($itemIdentifier === null)
		{
			$this->addError(ErrorCode::getOwnerNotFoundError());

			return null;
		}

		if (
			!$this->validateBindings(...$bindings)
			|| !$this->validateTimelineItem(...$bindings)
			|| !$this->validateUpdatePermission($itemIdentifier)
		)
		{
			return null;
		}

		$updateResult = $this->restAppLayoutBlocksRepository
			->setLayoutBlocksForTimelineItem(
				$timelineId,
				$server->getClientId(),
				$layout,
			)
		;

		if (!$updateResult->isSuccess())
		{
			$this->addErrors($updateResult->getErrors());

			return null;
		}

		$this->sendPullEvent(...$bindings);

		return [
			'success' => true,
		];
	}

	public function deleteAction(\CRestServer $server, int $entityTypeId, int $entityId, int $timelineId): array|null
	{
		$bindings = [$timelineId, $entityTypeId, $entityId];
		$itemIdentifier = ItemIdentifier::createByParams($entityTypeId, $entityId);
		if ($itemIdentifier === null)
		{
			$this->addError(ErrorCode::getOwnerNotFoundError());

			return null;
		}

		if (
			!$this->validateBindings(...$bindings)
			|| !$this->validateUpdatePermission($itemIdentifier)
		)
		{
			return null;
		}

		$deleteResult = $this->restAppLayoutBlocksRepository
			->deleteLayoutBlocksForTimelineItem($timelineId, $server->getClientId())
		;

		if ($deleteResult === null)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return null;
		}

		if (!$deleteResult->isSuccess())
		{
			$this->addErrors($deleteResult->getErrors());

			return null;
		}

		$this->sendPullEvent(...$bindings);

		return [
			'success' => true,
		];
	}

	private function validateBindings(
		int $timelineId,
		int $entityTypeId,
		int $entityId,
	): bool
	{
		$timelineBindings = (new TimelineBinding())
			->setOwnerId($timelineId)
			->setEntityTypeId($entityTypeId)
			->setEntityId($entityId)
		;

		$validationResult = (new BindingExists())->validate($timelineBindings);
		if (!$validationResult->isSuccess())
		{
			$this->addErrors($validationResult->getErrors());
		}

		return $validationResult->isSuccess();
	}

	private function validateTimelineItem(int $timelineId, int $entityTypeId, int $entityId): bool
	{
		$timelineEntry = Container::getInstance()->getTimelineEntryFacade()->getById($timelineId);
		if (!$timelineEntry)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return false;
		}

		$itemIdentifier = new ItemIdentifier($entityTypeId, $entityId);
		$context = new Context($itemIdentifier, Context::REST);
		$item = Container::getInstance()->getTimelineHistoryItemFactory()::createItem($context, $timelineEntry);

		if (
			!($item instanceof Configurable)
			|| $item instanceof Activity
			|| $item instanceof LogMessage
		)
		{
			$this->addError(self::getTimelineItemWithUnavailableType());

			return false;
		}

		return true;
	}

	private function sendPullEvent(int $timelineId, int $entityTypeId, int $entityId): void
	{
		Controller::getInstance()->sendPullEventOnUpdate(
			new ItemIdentifier($entityTypeId, $entityId),
			$timelineId,
		);
	}

	public static function getTimelineItemWithUnavailableType(): Error
	{
		return new Error(
			"Timeline item does not suitable for add content blocks",
			'UNAVAILABLE_TIMELINE_ITEM',
		);
	}
}
