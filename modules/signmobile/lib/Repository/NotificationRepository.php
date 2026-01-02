<?php

namespace Bitrix\SignMobile\Repository;

use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;
use Bitrix\SignMobile\Item\Notification;
use Bitrix\SignMobile\Model\SignMobileNotificationsTable;
use Bitrix\Main\ORM;

class NotificationRepository
{

	public function getOne(int $userId, int $typeId): ?Notification
	{
		$row = SignMobileNotificationsTable::getRow(
			[
				'select' => [
					'ID',
					'USER_ID',
					'TYPE',
					'SIGN_MEMBER_ID',
					'DATE_UPDATE',
				],
				'filter' => [
					'=USER_ID' => $userId,
					'=TYPE' => $typeId,
				],
			]
		);

		if (!is_null($row))
		{
			return new Notification(
				(int)$row['TYPE'],
				(int)$row['USER_ID'],
				(int)$row['SIGN_MEMBER_ID'],
				(isset($row['DATE_UPDATE']) && $row['DATE_UPDATE'] instanceof DateTime) ? $row['DATE_UPDATE'] : null,
				id: (int)$row['ID'],
			);
		}

		return null;
	}

	public function update(Notification $item): ORM\Data\UpdateResult
	{
		return SignMobileNotificationsTable::update(
			[
				'ID' => $item->getId(),
			],
			[
				'DATE_UPDATE' => $item->getDateUpdate(),
				'SIGN_MEMBER_ID' => $item->getSignMemberId(),
			],
		);
	}

	/**
	 * @param Notification $item
	 * @return int Affected rows count.
	 */
	public function insertIgnore(Notification $item): int
	{
		$connection = Application::getConnection();
		$fields = [
			'USER_ID' => $item->getUserId(),
			'TYPE' => $item->getType(),
			'DATE_UPDATE' => $item->getDateUpdate(),
			'SIGN_MEMBER_ID' => $item->getSignMemberId(),
		];
		$sqlHelper = $connection->getSqlHelper();

		$table = SignMobileNotificationsTable::getTableName();
		[$columns, $values] = $sqlHelper->prepareInsert($table, $fields);
		$query = $sqlHelper->getInsertIgnore($table, "($columns)", " VALUES ($values)");
		$connection->queryExecute($query);

		return $connection->getAffectedRowsCount();
	}

}