<?php declare(strict_types=1);

namespace Bitrix\AI\ImageStylePrompt;

use Bitrix\AI\Entity\ImageStylePrompt;
use Bitrix\AI\Model\ImageStylePromptTable;

class ImageStylePromptManager
{
	private string $languageCode;

	/**
	 * @param string $language
	 */
	public function __construct(string $language)
	{
		$this->languageCode = $language;
	}

	/**
	 * Get exists image style prompt by code
	 *
	 * @param string $styleCode
	 *
	 * @return ImageStylePrompt|null
	 */
	public function getByCode(string $styleCode): ?ImageStylePrompt
	{
		$stylePrompt = ImageStylePromptTable::query()->setSelect(['*'])->setFilter(['=CODE' => $styleCode])->fetchObject();
		if (!$stylePrompt)
		{
			return null;
		}

		return $stylePrompt;
	}

	/**
	 * Returns image style list.
	 *
	 * @return array
	 */
	public function list(): array
	{
		$result = [];
		$stylePrompts = ImageStylePromptTable::query()
			->setSelect(['CODE', 'PREVIEW', 'NAME_TRANSLATES'])
			->setOrder(['SORT' => 'ASC', 'CODE' => 'ASC'])
			->fetchCollection()
		;

		foreach ($stylePrompts as $stylePrompt)
		{
			$result[] = [
				'code' => $stylePrompt->getCode(),
				'name' => $stylePrompt->getName($this->languageCode),
				'preview' => $stylePrompt->getPreview(),
			];
		}

		return $result;
	}
}
