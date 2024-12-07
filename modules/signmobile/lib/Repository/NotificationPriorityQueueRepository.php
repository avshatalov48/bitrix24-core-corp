<?php

namespace Bitrix\SignMobile\Repository;

use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\Type\DateTime;
use Bitrix\SignMobile\Item\Notification;
use Bitrix\SignMobile\Model\SignMobileNotificationQueueTable;
use Bitrix\SignMobile\Type\NotificationType;
use Bitrix\Sign;

class NotificationPriorityQueueRepository
{
	public function insertIfDifferent(Notification $item): void
	{
		$this->addModelRow($item);
	}

	private function addModelRow(Notification $item): void
	{
		SignMobileNotificationQueueTable::add(
			[
				'USER_ID' => $item->getUserId(),
				'TYPE' => $item->getType(),
				'DATE_CREATE' => $item->getDataCreate(),
				'SIGN_MEMBER_ID' => $item->getSignMemberId(),
			]
		);
	}

	public function getPriorityLinkNotification(int $userId, int $type = NotificationType::PUSH_RESPONSE_SIGNING, bool $checkCanBeConfirmed = true, DateTime $startingFromDate = null): ?Sign\Item\Mobile\Link
	{
		if (is_null($startingFromDate))
		{
			$startingFromDate = (new DateTime())->add('-1D');
		}

		$service = Sign\Service\Container::instance()->getMobileService();

		$notification = $this->getPriorityNotificationByType($userId, $type, $startingFromDate);

		if (is_null($notification))
		{
			return null;
		}

		$linkResult = $service->getLinkForSigning($notification->getSignMemberId());

		if ($linkResult->isSuccess() && $link = $linkResult->getLink())
		{
			if ($checkCanBeConfirmed === false || $link->canBeConfirmed())
			{
				return $link;
			}
		}

		if ($this->deleteModelRow($notification)->isSuccess())
		{
			return $this->getPriorityLinkNotification($userId, checkCanBeConfirmed: $checkCanBeConfirmed);
		}

		return null;
	}

	public function deleteModelRow(Notification $item): DeleteResult
	{
 		return SignMobileNotificationQueueTable::deleteBy(
			$item->getUserId(),
			$item->getType(),
			$item->getSignMemberId()
		);
	}

	private function getPriorityNotificationByType(int $userId, int $type, DateTime $startingFromDate): ?Notification
	{
		$model = $this->getPriorityNotificationModel($userId, $type, $startingFromDate);

		if (is_null($model))
		{
			return null;
		}

		return new Notification(
			(int)$model['TYPE'],
			(int)$model['USER_ID'],
			(int)$model['SIGN_MEMBER_ID'],
			dateCreate: (isset($model['DATE_CREATE']) && $model['DATE_CREATE'] instanceof DateTime) ? $model['DATE_CREATE'] : null,
		);
	}

	private function getPriorityNotificationModel(int $userId, int $type, DateTime $startingFromDate): ?array
	{
		$row = SignMobileNotificationQueueTable::getRow(
			[
				'select' => [
					'USER_ID',
					'DATE_CREATE',
					'SIGN_MEMBER_ID',
					'TYPE',
				],
				'filter' => [
					'=USER_ID' => $userId,
					'=TYPE' => $type,
					'>DATE_CREATE' => $startingFromDate,
				],
				'order' => [
					'DATE_CREATE' => 'ASC'
				],
			]
		);

		if (!is_null($row))
		{
			return $row;
		}

		$this->clearOldNotifications($userId, beforeDate: $startingFromDate);

		return null;
	}

	private function clearOldNotifications(int $userId, DateTime $beforeDate): void
	{
		SignMobileNotificationQueueTable::deleteOlderThan($userId, $beforeDate);
	}

}