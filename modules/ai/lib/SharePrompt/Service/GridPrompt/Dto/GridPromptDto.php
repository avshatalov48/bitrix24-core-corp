<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Service\GridPrompt\Dto;

use Bitrix\Main\Type\Contract\Arrayable;

class GridPromptDto implements Arrayable
{
	protected string $code = '';
	protected string $title = '';
	protected string|null $type = null;
	protected string $author = '';
	protected string $authorPhoto = '';
	protected string $editor = '';
	protected string $editorPhoto = '';
	protected string|null $dateCreate = null;
	protected string|null $dateModify = null;
	/** @var array{name: string, code: string}  */
	protected array $categories = [];
	protected bool $isFavorite = false;
	protected bool $isDeleted = false;
	protected bool $isActive = false;
	protected int $countInShare = 0;

	/** @var ShareDto[] */
	protected array $share = [];

	/** @var int[]  */
	protected array $userIdsInShare = [];

	public function __construct(array $data)
	{
		$this->prepareData($data);
	}

	private function prepareData(array $data): void
	{
		if (array_key_exists('CODE', $data) && is_string($data['CODE']))
		{
			$this->code = $data['CODE'];
		}

		if (array_key_exists('TITLE', $data) && is_string($data['TITLE']))
		{
			$this->title = $data['TITLE'];
		}

		if (array_key_exists('TYPE', $data) && is_string($data['TYPE']))
		{
			$this->type = $data['TYPE'];
		}

		if (array_key_exists('AUTHOR', $data) && is_string($data['AUTHOR']))
		{
			$this->author = $data['AUTHOR'];
		}

		if (array_key_exists('AUTHOR_PHOTO_URL', $data) && is_string($data['AUTHOR_PHOTO_URL']))
		{
			$this->authorPhoto = $data['AUTHOR_PHOTO_URL'];
		}

		if (array_key_exists('EDITOR', $data) && is_string($data['EDITOR']))
		{
			$this->editor = $data['EDITOR'];
		}

		if (array_key_exists('EDITOR_PHOTO_URL', $data) && is_string($data['EDITOR_PHOTO_URL']))
		{
			$this->editorPhoto = $data['EDITOR_PHOTO_URL'];
		}

		if (array_key_exists('DATE_CREATE_STRING', $data) && is_string($data['DATE_CREATE_STRING']))
		{
			$this->dateCreate = $data['DATE_CREATE_STRING'];
		}

		if (array_key_exists('DATE_MODIFY_STRING', $data) && is_string($data['DATE_MODIFY_STRING']))
		{
			$this->dateModify = $data['DATE_MODIFY_STRING'];
		}

		if (array_key_exists('CATEGORIES', $data) && is_array($data['CATEGORIES']))
		{
			$this->categories = $data['CATEGORIES'];
		}

		if (array_key_exists('IS_FAVORITE', $data))
		{
			$this->isFavorite = (bool)$data['IS_FAVORITE'];
		}

		if (array_key_exists('IS_DELETED', $data))
		{
			$this->isDeleted = (bool)$data['IS_DELETED'];
		}

		if (array_key_exists('IS_ACTIVE', $data))
		{
			$this->isActive = (bool)(int)$data['IS_ACTIVE'];
		}

	}

	public function toArray(): array
	{
		return [
			'code' => $this->getCode(),
			'title' => $this->getTitle(),
			'type' => $this->getType(),
			'author' => $this->getAuthor(),
			'authorPhoto' => $this->getAuthorPhoto(),
			'editor' => $this->getEditor(),
			'editorPhoto' => $this->getEditorPhoto(),
			'dateCreate' => $this->getDateCreate(),
			'dateModify' => $this->getDateModify(),
			'categories' => $this->getCategories(),
			'isFavorite' => $this->isFavorite(),
			'isDeleted' => $this->isDeleted(),
			'isActive' => $this->isActive(),
			'share' => $this->getShare(),
		];
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function getType(): ?string
	{
		return $this->type;
	}

	public function getAuthor(): string
	{
		return $this->author;
	}

	public function getEditor(): string
	{
		return $this->editor;
	}

	public function getDateCreate(): ?string
	{
		return $this->dateCreate;
	}

	public function getDateModify(): ?string
	{
		return $this->dateModify ?? '-';
	}

	public function getCategories(): array
	{
		return $this->categories;
	}

	public function isFavorite(): bool
	{
		return $this->isFavorite;
	}

	public function isDeleted(): bool
	{
		return $this->isDeleted;
	}

	public function isActive(): bool
	{
		return $this->isActive;
	}

	public function getAuthorPhoto(): string
	{
		return $this->authorPhoto;
	}

	public function getEditorPhoto(): string
	{
		return $this->editorPhoto;
	}

	public function incrementCountShare(): void
	{
		$this->countInShare++;
	}

	public function getCountShare(): int
	{
		return $this->countInShare;
	}

	public function getCountInFillShare(): int
	{
		return count($this->share);
	}

	/**
	 * @return ShareDto[]
	 */
	public function getShare(): array
	{
		return array_values($this->share);
	}

	public function addUserIdInShare(int $userId): void
	{
		$this->userIdsInShare[] = $userId;
	}

	public function getUserIdsInShare(): array
	{
		return $this->userIdsInShare;
	}

	public function addInShare($code, ShareDto $shareDto): void
	{
		$this->share[$code] = $shareDto;
	}

	public function setShare(ShareDto $shareDto): void
	{
		$this->share = [$shareDto];
		$this->userIdsInShare = [];
		$this->countInShare = 0;
	}
}
