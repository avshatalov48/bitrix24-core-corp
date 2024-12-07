<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Dto;

use Bitrix\AI\SharePrompt\Events\Enums\ShareType;
use Bitrix\AI\SharePrompt\Enums\Category;
use Bitrix\Main\Type\DateTime;

class CreateDto
{
	public int $userCreatorId;
	public int $authorIdInPrompt = 0;
	public bool $needChangeAuthor = false;
	public array $accessCodesData = [];
	public array $accessCodes = [];
	public array $usersIdsInAccessCodes = [];
	public ?DateTime $dateCreate;
	public int $promptId;
	public string $promptType;
	public string $promptTitle;
	public string $promptDescription;
	public string $promptIcon;
	public string $promptCode;

	public array $categoriesForSave;
	/** @var Category[] */
	public array $categoriesForSaveData;

	public string $analyticCategory;
	public Category $analyticCategoryData;
	public ShareType $shareType;

	protected string $hash = '';

	public function getHash(): string
	{
		if (empty($this->hash))
		{
			$this->hash = md5(
				$this->promptType .
				$this->promptTitle .
				$this->promptDescription .
				$this->promptIcon .
				implode(',', array_keys($this->categoriesForSaveData)) .
				implode(',', $this->accessCodesData)
			);
		}

		return $this->hash;
	}
}
