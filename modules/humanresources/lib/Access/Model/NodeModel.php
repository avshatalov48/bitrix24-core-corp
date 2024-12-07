<?php

namespace Bitrix\HumanResources\Access\Model;

use Bitrix\HumanResources\Service\Container;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\HumanResources\Item;

final class NodeModel implements AccessibleItem
{
	private ?Item\Node $node = null;
	private ?int $targetNodeId = null;

	public static function createFromId(?int $itemId): NodeModel
	{
		$model = new self();

		if ($itemId !== null)
		{
			$nodeRepository = Container::getNodeRepository();
			$model->node = $nodeRepository->getById($itemId);
		}

		return $model;
	}

	public function getId(): int
	{
		return $this->node?->id ?? 0;
	}

	public function getParentId(): ?int
	{
		return $this->node?->parentId ?? null;
	}

	public function getTargetId(): ?int
	{
		return $this->targetNodeId;
	}

	public function setTargetNodeId(int $itemId): void
	{
		$nodeRepository = Container::getNodeRepository();
		$this->targetNodeId =  $nodeRepository->getById($itemId)?->id ?? null;
	}
}