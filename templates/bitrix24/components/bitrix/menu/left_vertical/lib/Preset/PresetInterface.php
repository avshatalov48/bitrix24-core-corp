<?php
namespace Bitrix\Intranet\LeftMenu\Preset;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Intranet\LeftMenu;

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