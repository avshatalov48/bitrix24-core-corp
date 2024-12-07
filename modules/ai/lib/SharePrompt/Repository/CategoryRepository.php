<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\AI\Model\PromptCategoryTable;
use Bitrix\Main\ORM\Data\AddResult;

class CategoryRepository extends BaseRepository
{
	public function getCodesByPromptId(int $promptId): array
	{
		return PromptCategoryTable::query()
			->setSelect([
				'CODE'
			])
			->where('PROMPT_ID', '=', $promptId)
			->fetchAll()
		;
	}

	public function deleteByPromptId(int $promptId): void
	{
		PromptCategoryTable::deleteByFilter([
			'PROMPT_ID' => $promptId
		]);
	}

	public function addCategoriesForPrompt(int $promptId, array $categories): AddResult
	{
		$rows = [];
		foreach (array_unique($categories) as $category)
		{
			$rows[] = [
				'PROMPT_ID' => $promptId,
				'CODE' => $category,
			];
		}

		return PromptCategoryTable::addMulti($rows, true);
	}
}
