<?php


namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\ItemIdentifier;
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