<?php

namespace Bitrix\HumanResources\Access\Model;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\HumanResources\Item;

/**
 * Data for updating chat list for a node
 */
final class ChatListModel implements AccessibleItem
{
	private ?NodeModel $nodeModel = null;
	private ?array $idsArray = null;

	public static function createFromId(?int $itemId): ChatListModel
	{
		$model = new self();
		$model->nodeModel = NodeModel::createFromId($itemId);

		return $model;
	}

	public function getId(): int
	{
		return $this->nodeModel?->getId() ?? 0;
	}

	public function getNode(): ?Item\Node
	{
		return $this->nodeModel?->getNode();
	}

	public function getNodeModel(): NodeModel
	{
		return $this->nodeModel;
	}

	public function setNodeModel(NodeModel $nodeModel): self
	{
		$this->nodeModel = $nodeModel;

		return $this;
	}

	public function setIdsArray(?array $idsArray): self
	{
		$this->idsArray = $idsArray ?? [];

		return $this;
	}

	public function getIdsArray(): array
	{
		return $this->idsArray;
	}

	public function getWithChildrenParameter(): bool
	{
		return $this->convertToBoolean($this->idsArray['withChildren'] ?? false);
	}

	private static function convertToBoolean($value): bool
	{
		return $value === true || $value === 'true' || $value === '1' || $value === 'Y' || $value === 1;
	}
}
