<?php

namespace Bitrix\Crm\Service\Timeline\Item;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item;

class Pusher
{
	public const ACTION_ADD = 'add';
	public const ACTION_UPDATE = 'update';
	public const ACTION_DELETE = 'delete';
	public const ACTION_MOVE = 'move';
	public const ACTION_CHANGE_PINNED = 'changePinned';

	public const PULL_EVENT_NAME = 'timeline_item_action';

	public const STREAM_SCHEDULED = 'scheduled';
	public const STREAM_FIXED_HISTORY = 'fixedHistory';
	public const STREAM_HISTORY = 'history';

	private Item $item;
	private \Bitrix\Crm\Timeline\Pusher $pusher;

	public function __construct(Item $item)
	{
		$this->item = $item;
		$this->pusher = Container::getInstance()->getTimelinePusher();
	}

	public function sendAddEvent(): void
	{
		$this->sendPullEvent(self::ACTION_ADD);
	}

	public function sendUpdateEvent(): void
	{
		$this->sendPullEvent(self::ACTION_UPDATE);
	}

	public function sendDeleteEvent(): void
	{
		$this->sendPullEvent(self::ACTION_DELETE);
	}

	public function sendMoveEvent(string $sourceStreamName, string $sourceItemId): void
	{
		$this->sendPullEvent(self::ACTION_MOVE, [
			'fromStream' => $sourceStreamName,
			'fromId' => $sourceItemId,
		]);
	}

	public function sendChangePinnedEvent(string $sourceStreamName, string $sourceItemId): void
	{
		$this->sendPullEvent(self::ACTION_CHANGE_PINNED, [
			'fromStream' => $sourceStreamName,
			'fromId' => $sourceItemId,
		]);
	}

	private function sendPullEvent(string $action, array $actionParams = []): void
	{
		$this->pusher->sendPullActionEvent(
			$this->item->getContext()->getEntityTypeId(),
			$this->item->getContext()->getEntityId(),
			$action,
			$this->preparePullEventParams($actionParams)
		);
	}

	private function preparePullEventParams(array $actionParams = []): array
	{
		$result = [
			'id' => $this->item->getModel()->getId(),
			'item' => $this->item->jsonSerialize(),
			'stream' => $this->getItemStreamName(),
		];
		if (!empty($actionParams))
		{
			$result['params'] = $actionParams;
		}

		return $result;
	}

	private function getItemStreamName(): string
	{
		if ($this->item->getModel()->isScheduled())
		{
			return self::STREAM_SCHEDULED;
		}
		if ($this->item->getModel()->isFixed())
		{
			return self::STREAM_FIXED_HISTORY;
		}

		return self::STREAM_HISTORY;
	}
}
