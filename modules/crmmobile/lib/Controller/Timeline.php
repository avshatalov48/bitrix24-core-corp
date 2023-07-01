<?php

declare(strict_types=1);

namespace Bitrix\CrmMobile\Controller;

use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;
use Bitrix\CrmMobile\Timeline\Controller;
use Bitrix\CrmMobile\Timeline\HistoryItemsQuery;
use Bitrix\CrmMobile\Timeline\Pagination;
use Bitrix\CrmMobile\Timeline\PinnedItemsQuery;
use Bitrix\CrmMobile\Timeline\ScheduledItemsQuery;
use Bitrix\Crm\Service\Timeline\Repository;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Config\Option;

class Timeline extends Controller
{
	public function loadTimelineAction(
		Repository  $repository,
		Item        $entity,
		Factory     $factory,
		Pagination  $pagination,
		CurrentUser $currentUser
	): array
	{
		$scheduled = (new ScheduledItemsQuery($repository))->execute();
		$pinned = (new PinnedItemsQuery($repository))->execute();
		$history = (new HistoryItemsQuery($repository, $entity, $pagination))->execute();

		$pushTag = null;
		if (Loader::includeModule('pull'))
		{
			$pushTag = TimelineEntry::prepareEntityPushTag($entity->getEntityTypeId(), $entity->getId());
			\CPullWatch::Add($currentUser->getId(), $pushTag);
		}

		return [
			'entity' => [
				'id' => $entity->getId(),
				'typeId' => $entity->getEntityTypeId(),
				'categoryId' => $factory->isCategoriesSupported() ? $entity->getCategoryId() : null,
				'title' => $entity->getHeading(),
				'pushTag' => $pushTag,
				'detailPageUrl' => \CCrmOwnerType::GetDetailsUrl($entity->getEntityTypeId(), $entity->getId()),
				'isEditable' => $this->isEntityEditable($entity),
				'documentGeneratorProvider' => $this->getDocumentGeneratorProvider($entity->getEntityTypeId()),
				'isDocumentPreviewerAvailable' => Option::get('crmmobile', 'release-spring-2023', true),
				'isGoToChatAvailable' => Option::get('crmmobile', 'release-spring-2023', true),
			],
			'scheduled' => $scheduled,
			'pinned' => $pinned,
			'history' => $history,
			'user' => \CCrmViewHelper::getUserInfo(),
		];
	}

	public function loadScheduledAction(Repository $repository): ?\Bitrix\Crm\Service\Timeline\Item
	{
		$supportedTypes = ['Activity:OpenLine'];
		$schedules = (new ScheduledItemsQuery($repository))->execute();
		$lastScheduled = null;

		foreach ($schedules as $scheduled)
		{
			if (in_array($scheduled->getType(), $supportedTypes))
			{
				$lastScheduled = $scheduled;
				break;
			}
		}

		return $lastScheduled;
	}

	/**
	 * @param int $activityId
	 * @param Item $entity Required to auto-check read permissions
	 * @return array
	 * @throws ObjectNotFoundException
	 */
	public function loadActivityAction(int $activityId, Item $entity): array
	{
		$activity = \CCrmActivity::GetByID($activityId);
		if (!is_array($activity))
		{
			throw new ObjectNotFoundException("Activity $activityId not found");
		}

		return [
			'activity' => $activity,
			'typeId' => (int)$activity['TYPE_ID'],
			'associatedEntityId' => isset($activity['ASSOCIATED_ENTITY_ID']) ? (int)$activity['ASSOCIATED_ENTITY_ID'] : 0,
		];
	}

	private function getDocumentGeneratorProvider(int $entityTypeId): ?string
	{
		$manager = DocumentGeneratorManager::getInstance();
		if (!$manager->isEnabled())
		{
			return null;
		}

		$providersMap = $manager->getCrmOwnerTypeProvidersMap();
		return $providersMap[$entityTypeId] ?? null;
	}
}
