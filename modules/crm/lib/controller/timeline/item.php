<?php


namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

class Item extends \Bitrix\Crm\Controller\Base
{
	private const MAX_PINNED_ITEMS_COUNT = 3;

	public function pinAction(int $id, int $ownerTypeId, int $ownerId): void
	{
		if (!$this->checkBinding($id, $ownerTypeId, $ownerId))
		{
			return;
		}
		if (!$this->checkPinnedLimit($id, $ownerTypeId, $ownerId))
		{
			$this->addError(new Error(Loc::getMessage('CRM_TIMELINE_FASTEN_LIMIT_MESSAGE')));

			return;
		}
		$this->setPinned($id, $ownerTypeId, $ownerId, true);
	}

	public function unpinAction(int $id, int $ownerTypeId, int $ownerId): void
	{
		if (!$this->checkBinding($id, $ownerTypeId, $ownerId))
		{
			return;
		}
		$this->setPinned($id, $ownerTypeId, $ownerId, false);
	}

	public function loadAction(
		int $ownerTypeId,
		int $ownerId,
		string $context = Service\Timeline\Context::DESKTOP,
		array $activityIds = [],
		array $historyIds = []
	): array
	{
		$permissions = Container::getInstance()->getUserPermissions();
		if (!$permissions->checkReadPermissions($ownerTypeId, $ownerId))
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return [];
		}

		$identifier = new ItemIdentifier($ownerTypeId, $ownerId);

		$allowedContexts = [
			Service\Timeline\Context::DESKTOP,
			Service\Timeline\Context::MOBILE,
		];
		$context = in_array($context, $allowedContexts) ? $context : Service\Timeline\Context::DESKTOP;
		$context = new Service\Timeline\Context($identifier, $context);

		$repository = new Service\Timeline\Repository($context);

		$activityIds = array_map(function ($id) {
			$id = explode('_', $id);
			return $id[1] && is_numeric($id[1]) ? (int)$id[1] : null;
		}, $activityIds);

		$activityIds = array_filter($activityIds);

		$historyIds = array_map('intval', $historyIds);

		$result = [];

		if (!empty($activityIds))
		{
			$query = (new Service\Timeline\Repository\Query())
				->setFilter([ '@ID' => $activityIds ])
				->setLimit(100);
			$scheduled = $repository->getScheduledItems($query)->getItems();
			foreach ($scheduled as $item)
			{
				$result[$item->getModel()->getId()] = $item;
			}
		}

		if (!empty($historyIds))
		{
			$query = (new Service\Timeline\Repository\Query())
				->setFilter([ 'ID' => $historyIds ])
				->setLimit(100);

			$history = $repository->getHistoryItemsPage($query)->getItems();
			foreach ($history as $item)
			{
				$result[$item->getModel()->getId()] = $item;
			}
		}

		return $result;
	}

	private function checkBinding(int $id, int $ownerTypeId, int $ownerId): bool
	{
		if(!\CCrmOwnerType::IsDefined($ownerTypeId) || $ownerId <= 0)
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getOwnerNotFoundError());

			return false;
		}

		$permissions = Container::getInstance()->getUserPermissions();
		if (!$permissions->checkUpdatePermissions($ownerTypeId, $ownerId))
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return false;
		}

		if (!\Bitrix\Crm\Timeline\TimelineEntry::checkBindingExists($id, $ownerTypeId, $ownerId))
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getNotFoundError());

			return false;
		}

		return true;
	}

	private function checkPinnedLimit(int $id, int $ownerTypeId, int $ownerId): bool
	{
		$existedItems = \Bitrix\Crm\Timeline\Entity\TimelineBindingTable::query()
			->where('ENTITY_TYPE_ID', $ownerTypeId)
			->where('ENTITY_ID', $ownerId)
			->where('IS_FIXED', true)
			->setSelect(['OWNER_ID'])
			->setLimit(self::MAX_PINNED_ITEMS_COUNT)
		;

		return (count($existedItems->exec()->fetchAll()) < self::MAX_PINNED_ITEMS_COUNT);

	}

	private function setPinned(int $id, int $ownerTypeId, int $ownerId, bool $isPinned): void
	{
		$result = \Bitrix\Crm\Timeline\TimelineEntry::setIsFixed($id, $ownerTypeId, $ownerId, $isPinned);

		if ($result->isSuccess())
		{
			\Bitrix\Crm\Timeline\Controller::getInstance()->sendPullEventOnPin(
				new ItemIdentifier($ownerTypeId, $ownerId),
				$id,
				$isPinned
			);
		}
		else
		{
			$this->addError(new \Bitrix\Main\Error(implode(', ', $result->getErrorMessages()), 'CAN_NOT_CHANGE_PINNED'));
		}
	}
}