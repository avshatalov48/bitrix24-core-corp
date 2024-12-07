<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\AI\Model\PromptTranslateNameTable;

class TranslateNameRepository extends BaseRepository
{
	public function deleteByPromptId(int $promptId): void
	{
		PromptTranslateNameTable::deleteByFilter([
			'PROMPT_ID' => $promptId
		]);
	}

	public function addNamesForPrompt(int $promptId, array $names): void
	{
		$data = [];
		foreach ($names as $lang => $text)
		{
			$data[] = [
				'PROMPT_ID' => $promptId,
				'LANG' => $lang,
				'TEXT' => $text,
			];
		}

		if (empty($data))
		{
			return;
		}

		PromptTranslateNameTable::addMulti($data, true);
	}
}
