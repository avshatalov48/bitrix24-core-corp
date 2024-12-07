<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Service\Dto;

use Bitrix\AI\Entity\TranslateTrait;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\UI\EntitySelector\Converter;

class PromptForUpdateDto implements Arrayable
{
	use TranslateTrait;

	protected string $code = '';
	protected string $translate = '';
	protected string $icon = '';
	protected string|null $type = null;
	protected string $prompt = '';
	protected array $categories = [];
	protected array $accessCodes = [];
	protected string $authorId = '';

	public function __construct(array $data, int $userId, array $categoriesList)
	{
		$this->prepareData($data, $userId, $categoriesList);
	}

	protected function prepareData(array $data, int $userId, array $categoriesList): void
	{
		if (array_key_exists('CODE', $data) && is_string($data['CODE']))
		{
			$this->code = $data['CODE'];
		}

		if (array_key_exists('TRANSLATE', $data) && is_string($data['TRANSLATE']))
		{
			$this->translate = $data['TRANSLATE'];
		}

		if (array_key_exists('ICON', $data) && is_string($data['ICON']))
		{
			$this->icon = $data['ICON'];
		}

		if (array_key_exists('TYPE', $data) && is_string($data['TYPE']))
		{
			$this->type = $data['TYPE'];
		}

		if (array_key_exists('PROMPT', $data) && is_string($data['PROMPT']))
		{
			$this->prompt = $data['PROMPT'];
		}

		if (array_key_exists('CATEGORIES', $data) && is_string($data['CATEGORIES']))
		{
			$this->categories = array_filter(
				array_unique(explode(',', $data['CATEGORIES'])),
				fn($category) => in_array($category, $categoriesList)
			);

			$this->categories = array_values($this->categories);
		}

		if (array_key_exists('ACCESS_CODES', $data) && is_string($data['ACCESS_CODES']))
		{
			$this->accessCodes = Converter::convertFromFinderCodes(
				array_unique(
					explode(',', $data['ACCESS_CODES'])
				)
			);
		}

		if (array_key_exists('AUTHOR_ID', $data) && is_string($data['AUTHOR_ID']))
		{
			$this->authorId = $data['AUTHOR_ID'];
		}

		if (empty((int)$this->authorId))
		{
			$this->authorId = (string)$userId;
			$userAccessCodes = Converter::convertFromFinderCodes(['U' . $userId]);
			if (!empty($userAccessCodes[0]))
			{
				$this->accessCodes[] = $userAccessCodes[0];
			}
		}
	}

	public function toArray(): array
	{
		return [
			'code' => $this->code,
			'translate' => $this->translate,
			'icon' => $this->icon,
			'type' => $this->type,
			'prompt' => $this->prompt,
			'categories' => $this->categories,
			'accessCodes' => $this->accessCodes,
			'authorId' => $this->authorId,
		];
	}
}
