<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\AI\ShareRole\Model\OwnerTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\Result;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\SystemException;

class OwnerRepository extends BaseRepository
{
	public function unsetDeletedFlagsForUsers(array $userIds, int $roleId): Result
	{
		$idList = implode(',', $userIds);

		return $this->getConnection()->query("
			UPDATE
				{$this->getOwnerTableName()}
			SET 
				IS_DELETED = '0'
			WHERE
				ROLE_ID = {$roleId}
				AND USER_ID IN ($idList)
		");
	}

	private function getOwnerTableName(): string
	{
		return OwnerTable::getTableName();
	}

	public function setDeleteFlagOnRow(int $userId, int $roleId, bool $isDelete = true): Result
	{
		$isDeleteFlag = (int)$isDelete;

		return $this->getConnection()->query("
			UPDATE
				{$this->getOwnerTableName()}
			SET 
				IS_DELETED = {$isDeleteFlag}
			WHERE
				ROLE_ID = {$roleId}
				AND USER_ID = {$userId} 
			");
	}

	/**
	 * @param int[] $ownerIdsForUpdate
	 * @param bool $isDelete
	 * @return UpdateResult
	 */
	public function setDeleteFlagOnRows(array $ownerIdsForUpdate, bool $isDelete = true): UpdateResult
	{
		return OwnerTable::updateMulti($ownerIdsForUpdate, ['IS_DELETED' => (int)$isDelete]);
	}

	public function createWithDeleteFlag(int $userId, int $roleId, bool $isDelete = true): AddResult
	{
		return OwnerTable::add(
			[
				'USER_ID' => $userId,
				'ROLE_ID' => $roleId,
				'IS_DELETED' => (int)$isDelete,
			]
		);
	}

	/**
	 * @param int $userId
	 * @param int[] $roleIds
	 * @param bool $isDeleted
	 * @return AddResult
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function createWithDeleteFlagMulti(int $userId, array $roleIds, bool $isDeleted): AddResult
	{
		$data = [];
		foreach ($roleIds as $roleId)
		{
			$data[] = [
				'USER_ID' => $userId,
				'ROLE_ID' => $roleId,
				'IS_DELETED' => (int)$isDeleted,
			];
		}

		return OwnerTable::addMulti($data, true);
	}
}
