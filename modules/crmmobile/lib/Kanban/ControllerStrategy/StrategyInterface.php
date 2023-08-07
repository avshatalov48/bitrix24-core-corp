<?php

namespace Bitrix\CrmMobile\Kanban\ControllerStrategy;

use Bitrix\Crm\Kanban\Entity;
use Bitrix\Main\Result;
use Bitrix\Main\UI\PageNavigation;

interface StrategyInterface
{
	public function getList(?PageNavigation $pageNavigation): array;

	public function getItemParams(array $items): array;

	public function updateItemStage(int $id, int $stageId): Result;

	public function deleteItem(int $id, array $params = []): Result;

	public function changeCategory(array $ids, int $categoryId): Result;

	public function prepareFilterPresets(Entity $entity, array $presets, ?string $defaultPresetName): array;

	public function getGridId(): string;

	public function prepareFilter(Entity $entity): void;
}
