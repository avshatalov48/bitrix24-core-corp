<?php

namespace Bitrix\Sign\Item\Hr;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Type\Hr\NodeSyncStatus;

final class NodeSync implements Contract\Item
{
	public function __construct(
		public readonly int $id,
		public readonly int $documentId,
		public readonly int $nodeId,
		public readonly NodeSyncStatus $status,
		public readonly int $page,
		public readonly bool $isFlat,
	)
	{
	}

	public function createUpdated(NodeSyncStatus $status, int $page): self
	{
		return new NodeSync(
			$this->id,
			$this->documentId,
			$this->nodeId,
			$status,
			$page,
			$this->isFlat,
		);
	}
}
