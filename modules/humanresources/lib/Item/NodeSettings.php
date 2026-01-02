<?php

namespace Bitrix\HumanResources\Item;

use Bitrix\HumanResources\Contract\Item;
use Bitrix\HumanResources\Type\NodeSettingsType;

class NodeSettings implements Item
{
	public function __construct(
		public int $nodeId,
		public NodeSettingsType $settingsType,
		public ?string $settingsValue = null,
		public ?int $id = null,
		public ?\Bitrix\Main\Type\DateTime $createdAt = null,
		public ?\Bitrix\Main\Type\DateTime $updatedAt = null,
	) {}
}
