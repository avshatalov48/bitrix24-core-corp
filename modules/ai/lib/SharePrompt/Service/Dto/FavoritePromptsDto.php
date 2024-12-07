<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Service\Dto;

class FavoritePromptsDto
{
	/**
	 * @param int $userId
	 * @param bool $needUpdateSortingInFavoriteList
	 *    Flag indicating that sorting information in favorites should be updated
	 *
	 * @param bool $hasRowOption
	 *    Flag indicating that there is a line in db for writing options
	 *
	 * @param int[] $sortingList List of prompts for sorting
	 *
	 * @param array $promptsUsers List of all users available prompts
	 *
	 * @param array $promptsSystem List of all systems available prompts
	 *
	 * @param array $favoritePrompts List of favorite prompts
	 */
	public function __construct(
		public int $userId,
		public bool $needUpdateSortingInFavoriteList,
		public bool $hasRowOption,
		public array $sortingList,
		public array $promptsUsers,
		public array $promptsSystem,
		public array $favoritePrompts,
	)
	{
	}
}
