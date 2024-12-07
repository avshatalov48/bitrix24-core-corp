<?php

namespace Bitrix\SignMobile\Repository;

use Bitrix\Main\Application;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Type\DateTime;
use Bitrix\SignMobile\Item\Notification;
use Bitrix\SignMobile\Model\SignMobileNotificationsTable;

class NotificationRepository
{
	private function getModelRow(Notification $item): ?array
	{
		return SignMobileNotificationsTable::getRow(
			[
				'select' => [
					'ID',
					'USER_ID',
					'TYPE',
					'SIGN_MEMBER_ID',
					'DATE_UPDATE',
				],
				'filter' => [
					'=USER_ID' => $item->getUserId(),
					'=TYPE' => $item->getType(),
				],
			]
		);
	}

	private function updateModelRow(int $id, Notification $item): void
	{
		SignMobileNotificationsTable::update(
			[
				'ID' => $id,
			],
			[
				'DATE_UPDATE' => $item->getDateUpdate(),
				'SIGN_MEMBER_ID' => $item->getSignMemberId(),
			],
		);
	}

	private function addModelRow(Notification $item): bool
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

		return (bool)$connection->getAffectedRowsCount();
	}

	public function getByType(int $type): ?Notification
	{
		$currentUserId = (int)CurrentUser::get()->getId();

		$prototypeForFind = new Notification(
			$type,
			$currentUserId,
		);

		$row = $this->getModelRow($prototypeForFind);

		if (!is_null($row))
		{
			return new Notification(
				(int)$row['TYPE'],
				(int)$row['USER_ID'],
				(int)$row['SIGN_MEMBER_ID'],
				(isset($row['DATE_UPDATE']) && $row['DATE_UPDATE'] instanceof DateTime) ? $row['DATE_UPDATE'] : null,
			);
		}

		return null;
	}

	public function insertIfDifferent(Notification $item): bool
	{
		if ($this->addModelRow($item))
		{
			return true;
		}

		$row = $this->getModelRow($item);
		if (is_array($row) && isset($row['SIGN_MEMBER_ID']) && isset($row['ID']))
		{
			if ((int) $row['SIGN_MEMBER_ID'] !== $item->getSignMemberId())
			{
				$this->updateModelRow((int)$row['ID'], $item);
				return true;
			}
		}

		return false;
	}
}