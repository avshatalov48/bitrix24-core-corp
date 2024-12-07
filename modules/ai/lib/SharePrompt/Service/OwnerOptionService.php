<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Service;

use Bitrix\AI\SharePrompt\Repository\OwnerOptionRepository;
use Bitrix\Main\DB\Result;
use Bitrix\Main\ORM\Data\AddResult;

class OwnerOptionService
{
	public function __construct(
		protected OwnerOptionRepository $ownerOptionRepository
	)
	{
	}

	/**
	 * @param int $userId
	 * @return array{int[], bool}
	 */
	public function getSortingInFavoriteList(int $userId): array
	{
		$result = $this->ownerOptionRepository->getFavoritesListSorting($userId);
		if (empty($result['SORTING_IN_FAVORITE_LIST']))
		{
			return [[], isset($result['ID'])];
		}

		return [
			json_decode($result['SORTING_IN_FAVORITE_LIST'], true),
			isset($result['ID'])
		];
	}

	/**
	 * @param int[] $sortingInTable
	 * @param int[] $favoritePromptIdsReal
	 * @return array{bool, int[]}
	 */
	public function getUpdatingDataForFavoritesSortingList(array $sortingInTable, array $favoritePromptIdsReal): array
	{
		if (empty($favoritePromptIdsReal) && empty($sortingInTable))
		{
			return [false, []];
		}

		if (empty($favoritePromptIdsReal))
		{
			return [true, []];
		}

		if (empty($sortingInTable))
		{
			return [true, $favoritePromptIdsReal];
		}

		$hasUpdate = false;
		foreach ($sortingInTable as $key => $promptId)
		{
			if (!in_array($promptId, $favoritePromptIdsReal))
			{
				unset($sortingInTable[$key]);
				$hasUpdate = true;
			}
		}

		$diff = array_diff($favoritePromptIdsReal, $sortingInTable);
		if (empty($diff))
		{
			return [$hasUpdate, $sortingInTable];
		}

		return [true, array_merge($diff, $sortingInTable)];
	}

	/**
	 * @param int $userId
	 * @param int[] $sorting
	 * @param bool $hasRowOption
	 * @return Result|AddResult
	 */
	public function updateFavoritesListSorting(int $userId, array $sorting, bool $hasRowOption): Result|AddResult
	{
		$sorting = array_values($sorting);

		if ($hasRowOption)
		{
			return $this->ownerOptionRepository->updateFavoritesListSortingForUser($userId, $sorting);
		}

		return $this->ownerOptionRepository->insertFavoriteSortingForUser($userId, $sorting);
	}

	/**
	 * @param int $userId
	 * @param int[] $sorting
	 * @return Result|AddResult
	 */
	public function updateFavoriteListSortingForce(int $userId, array $sorting): Result|AddResult
	{
		$listData = $this->ownerOptionRepository->getFavoritesListSorting($userId);

		return $this->updateFavoritesListSorting($userId, $sorting, !empty($listData['ID']));
	}
}
