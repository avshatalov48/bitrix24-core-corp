<?php

namespace Bitrix\ImOpenLines\V2\Transfer;

use Bitrix\ImOpenLines\V2\Queue\QueueItem;

class QueueTransfer extends TransferItem
{
	protected QueueItem $entity;

	public function __construct(QueueItem $entity)
	{
		$this->entity = $entity;
	}

	public function getId(): ?int
	{
		return $this->entity->getId();
	}

	public function getTransferId(): string
	{
		return 'queue' . $this->getId();
	}

	public static function getInstance(mixed $id): ?self
	{
		if (mb_strpos($id, 'queue') === 0)
		{
			$queueId = mb_substr($id, 5);
			$queue = (new QueueItem())
				->setId($queueId)
			;

			return new QueueTransfer($queue);
		}

		return null;
	}
}