<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\AI\SharePrompt\Model\OwnerOptionTable;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\DB\Result;

class OwnerOptionRepository extends BaseRepository
{
	public function getFavoritesListSorting(int $userId): array|bool
	{
		return OwnerOptionTable::query()
			->setSelect([
				'ID',
				'SORTING_IN_FAVORITE_LIST'
			])
			->where('USER_ID', $userId)
			->fetch()
		;
	}

	public function insertFavoriteSortingForUser(int $userId, array $sorting): AddResult
	{
		$sortingText = json_encode($sorting);

		return OwnerOptionTable::add([
			'USER_ID' => $userId,
			'SORTING_IN_FAVORITE_LIST' => $sortingText
		]);
	}

	public function updateFavoritesListSortingForUser(int $userId, array $sorting): Result
	{
		$sortingText = json_encode($sorting);

		return $this->getConnection()->query("
			UPDATE 
				{$this->getOwnerOptionTableName()} 
			SET 
				SORTING_IN_FAVORITE_LIST = '{$this->getSqlHelper()->forSql($sortingText)}'
			WHERE 
				USER_ID = {$userId}
		");
	}

	public function getOwnerOptionTableName(): string
	{
		return OwnerOptionTable::getTableName();
	}
}
