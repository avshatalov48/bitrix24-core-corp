<?php

namespace Bitrix\SignMobile\Repository;

use Bitrix\Main\Type\DateTime;
use Bitrix\SignMobile\Item\Notification;
use Bitrix\SignMobile\Model\SignMobileNotificationQueueTable;
use Bitrix\Main\ORM;
use Bitrix\Main;

class NotificationQueueRepository
{
	public function add(Notification $item): ORM\Data\AddResult
	{
		$alreadyExists = (bool)SignMobileNotificationQueueTable::getCount([
			'=USER_ID' => $item->getUserId(),
			'=TYPE' => $item->getType(),
			'=SIGN_MEMBER_ID' => $item->getSignMemberId(),
		]);

		if ($alreadyExists)
		{
			return new \Bitrix\Main\ORM\Data\AddResult();
		}

		return SignMobileNotificationQueueTable::add(
			[
				'USER_ID' => $item->getUserId(),
				'TYPE' => $item->getType(),
				'DATE_CREATE' => $item->getDataCreate(),
				'SIGN_MEMBER_ID' => $item->getSignMemberId(),
			]
		);
	}

	public function delete(Notification $item): ORM\Data\DeleteResult
	{
 		return SignMobileNotificationQueueTable::deleteBy(
			$item->getUserId(),
			$item->getType(),
			$item->getSignMemberId()
		);
	}

	public function getOne(int $userId, int $typeId, Main\Type\DateTime $startingFromDate): ?Notification
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
					'=TYPE' => $typeId,
					'>DATE_CREATE' => $startingFromDate,
				],
				'order' => [
					'DATE_CREATE' => 'ASC'
				],
			]
		);

		if (!is_null($row))
		{
			return new Notification(
				(int)$row['TYPE'],
				(int)$row['USER_ID'],
				(int)$row['SIGN_MEMBER_ID'],
				dateCreate: (isset($row['DATE_CREATE']) && $row['DATE_CREATE'] instanceof DateTime) ? $row['DATE_CREATE'] : null,
			);
		}

		return null;
	}

	public function deleteOlderThan($userId, Main\Type\DateTime $deleteBeforeDate): Main\DB\Result
	{
		return SignMobileNotificationQueueTable::deleteOlderThan($userId, $deleteBeforeDate);
	}

}