<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\AI\SharePrompt\Model\OwnerTable;
use Bitrix\Main\DB\Result;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\UpdateResult;

class OwnerRepository extends BaseRepository
{
	public function setDeleteFlagOnRow(int $userId, int $promptId, bool $isDelete = true): Result
	{
		$isDeleteFlag = (int)$isDelete;

		return $this->getConnection()->query("
			UPDATE 
				{$this->getOwnerTableName()} 
			SET 
				IS_DELETED = {$isDeleteFlag}
			WHERE 
				PROMPT_ID = {$promptId} 
				AND USER_ID = {$userId};
		");
	}

	/**
	 * @param int[] $ownerIdsForUpdate
	 * @param bool $isDelete
	 * @return Result
	 */
	public function setDeleteFlagOnRows(array $ownerIdsForUpdate, bool $isDelete = true): Result
	{
		$isDeleteFlag = (int)$isDelete;
		$idList = implode(',', $ownerIdsForUpdate);

		return $this->getConnection()->query("
			UPDATE 
				{$this->getOwnerTableName()} 
			SET 
				IS_DELETED = {$isDeleteFlag}
			WHERE 
				ID IN ({$idList});
		");
	}

	public function setFavoriteFlagOnRow(int $promptRowId, bool $isFavorite = true): UpdateResult
	{
		return OwnerTable::update(
			$promptRowId,
			[
				'IS_FAVORITE' => (int)$isFavorite
			]
		);
	}

	public function createWithDeleteFlag(int $userId, int $promptId, bool $isDelete = true): AddResult
	{
		return OwnerTable::add(
			[
				'USER_ID' => $userId,
				'PROMPT_ID' => $promptId,
				'IS_DELETED' => (int)$isDelete,
			]
		);
	}

	/**
	 * @param int $userId
	 * @param int[] $promptIds
	 * @param bool $isDelete
	 * @return AddResult
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function createWithDeleteFlagMulti(int $userId, array $promptIds, bool $isDelete = true): AddResult
	{
		$data = [];
		foreach ($promptIds as $promptId)
		{
			$data[] = [
				'USER_ID' => $userId,
				'PROMPT_ID' => $promptId,
				'IS_DELETED' => (int)$isDelete,
			];
		}

		return OwnerTable::addMulti($data, true);
	}

	public function createWithFavoriteFlag(int $userId, int $promptId): AddResult
	{
		return OwnerTable::add(
			[
				'USER_ID' => $userId,
				'PROMPT_ID' => $promptId,
				'IS_FAVORITE' => 1,
			]
		);
	}

	public function getByPromptIdAndUserId(int $userId, int $promptId): bool|array
	{
		return OwnerTable::query()
			->setSelect(['ID'])
			->where('USER_ID', $userId)
			->where('PROMPT_ID', $promptId)
			->fetch()
		;
	}

	public function getFavoriteByPromptIdAndUserId(int $userId, int $promptId): bool|array
	{
		return OwnerTable::query()
			->setSelect(['ID'])
			->where('IS_FAVORITE', 1)
			->where('USER_ID', $userId)
			->where('PROMPT_ID', $promptId)
			->fetch()
		;
	}

	public function getFavoriteFlag(int $userId, int $promptId): bool|array
	{
		return OwnerTable::query()
			->setSelect(['IS_FAVORITE'])
			->where('USER_ID', $userId)
			->where('PROMPT_ID', $promptId)
			->fetch()
		;
	}

	public function unsetDeletedFlagsForUsers(array $usersIds, int $promptId): Result
	{
		$idList = implode(',', $usersIds);

		return $this->getConnection()->query("
			UPDATE 
				{$this->getOwnerTableName()}
			SET 
				IS_DELETED = 0
			WHERE 
				 PROMPT_ID = {$promptId}
				 AND USER_ID IN ({$idList})
		");
	}

	private function getOwnerTableName(): string
	{
		return OwnerTable::getTableName();
	}
}
