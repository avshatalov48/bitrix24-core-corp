<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Provider;

use Bitrix\Tasks\CheckList\CheckListFacade;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListFacade;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Repository\CheckListUserOptionRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\CheckListMapper;

class CheckListProvider
{
	public function __construct(
		private readonly CheckListMapper $checkListMapper,
		private readonly CheckListUserOptionRepositoryInterface $checkListUserOptionRepository,
	)
	{

	}

	public function getByEntity(int $entityId, int $userId, Entity\CheckList\Type $type): Entity\CheckList
	{
		// todo: provider
		/** @var CheckListFacade $dataClass */
		$dataClass = match ($type)
		{
			Entity\CheckList\Type::Task => TaskCheckListFacade::class,
			Entity\CheckList\Type::Template => TemplateCheckListFacade::class,
		};

		$items = $dataClass::getByEntityId($entityId);

		$items = $dataClass::fillActionsForItems($entityId, $userId, $items);

		$items = $this->fillVisibilityStateForItems($userId, $items);

		return $this->checkListMapper->mapToEntity($items);
	}

	private function fillVisibilityStateForItems(int $currentUserId, array $items): array
	{
		if (empty($items))
		{
			return $items;
		}

		$itemIds = array_column($items, 'ID');
		$itemIds = array_map('intval', $itemIds);

		$options = $this->checkListUserOptionRepository->isSet(
			$currentUserId,
			$itemIds,
			[
				Entity\CheckList\Option::COLLAPSED,
				Entity\CheckList\Option::EXPANDED
			]
		);

		return array_map(
			function($item) use ($options, $currentUserId)
			{
				$itemId = (int)$item['ID'];

				$item['COLLAPSED'] = isset($options[$itemId][Entity\CheckList\Option::COLLAPSED]);
				$item['EXPANDED'] = isset($options[$itemId][Entity\CheckList\Option::EXPANDED]);

				return $item;
			},
			$items
		);
	}
}
