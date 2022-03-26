<?php
namespace Bitrix\Intranet\UI\LeftMenu\Preset;

use \Bitrix\Intranet\UI\LeftMenu;

interface PresetInterface
{
	public static function isAvailable(): bool;

	public function getName(): string;

	public function getCode(): string;

	public function getStructure(): array;

	public function getSortForItem($itemId, $parentId): ?int;

	public function getParentForItem($itemId, LeftMenu\MenuItem\Basic $item): ?string;

	public function getItems(): array;
}