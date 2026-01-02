<?php

namespace Bitrix\Crm\RepeatSale\Segment\Controller;

use Bitrix\Crm\RepeatSale\Segment\Entity\RepeatSaleSegmentAssignmentUserTable;
use Bitrix\Crm\RepeatSale\Segment\SegmentAssignmentUserItem;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Application;
use Bitrix\Main\DB\Result;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\ORM\Data\UpdateResult;

final class RepeatSaleSegmentAssignmentUserController
{
	use Singleton;

	public function add(SegmentAssignmentUserItem $item): AddResult
	{
		return RepeatSaleSegmentAssignmentUserTable::add($this->getFields($item));
	}

	public function update(int $id, SegmentAssignmentUserItem $item): UpdateResult
	{
		return RepeatSaleSegmentAssignmentUserTable::update($id, $this->getFields($item));
	}

	private function getFields(SegmentAssignmentUserItem $item): array
	{
		return [
			'SEGMENT_ID' => $item->getSegmentId(),
			'USER_ID' => $item->getUserId(),
		];
	}

	public function delete(int $id): DeleteResult
	{
		return RepeatSaleSegmentAssignmentUserTable::delete($id);
	}

	public function deleteBySegmentId(int $segmentId): Result
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();
		$table = RepeatSaleSegmentAssignmentUserTable::getTableName();

		$sql ='DELETE FROM ' . $sqlHelper->quote($table) . ' WHERE SEGMENT_ID =' . $segmentId;

		return Application::getConnection()->query($sql);
	}

	public function deleteByUserId(int $userId): Result
	{
		$connection = Application::getConnection();

		$sqlHelper = $connection->getSqlHelper();
		$table = RepeatSaleSegmentAssignmentUserTable::getTableName();

		$sql = 'DELETE FROM ' . $sqlHelper->quote($table) . ' WHERE USER_ID =' . $userId;

		return $connection->query($sql);
	}
}
