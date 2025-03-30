<?php

namespace Bitrix\Crm\Service\Timeline\Item\Factory;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item;
use Bitrix\Crm\Service\Timeline\Item\Model;

class ScheduledItem
{
	/**
	 * Create timeline item for scheduled stream
	 *
	 * @param Context $context
	 * @param array $rawData
	 * @return Item
	 */
	public static function createItem(Context $context, array $rawData): Item
	{
		$typeId = (int)($rawData['TYPE_ID'] ?? 0);
		$providerId = (string)($rawData['PROVIDER_ID'] ?? '');

		$model = Model::createFromScheduledActivityArray($rawData);

		return
			Container::getInstance()->getTimelineActivityItemFactory()::create($typeId, $providerId, $context, $model)
			?? new Item\Compatible\ScheduledActivity(
				$context,
				(new Item\Compatible\Model())
					->setData($rawData)
					->setId(\Bitrix\Crm\Service\Timeline\Item\Model::getScheduledActivityModelId($rawData['ID']))
					->setIsScheduled(true)
			)
		;
	}

	/**
	 * Create empty item for deletion pull event
	 *
	 * @param Context $context
	 * @param int $id
	 * @return Item
	 */
	public static function createEmptyItem(Context $context, int $id): Item
	{
		$model = Model::createFromScheduledActivityArray(['ID' => $id]);

		return new class($context, $model) extends Item
		{
			public function jsonSerialize(): ?array
			{
				return null;
			}

			public function getSort(): array
			{
				return [];
			}
		};
	}
}
