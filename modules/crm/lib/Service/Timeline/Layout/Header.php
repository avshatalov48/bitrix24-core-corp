<?php

namespace Bitrix\Crm\Service\Timeline\Layout;

use Bitrix\Crm\Service\Timeline\Layout\Header\ChangeStreamButton;
use Bitrix\Crm\Service\Timeline\Layout\Header\InfoHelper;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Type\DateTime;

class Header extends Base
{
	protected ?ChangeStreamButton $changeStreamButton = null;
	protected ?string $title = null;
	protected ?Action $titleAction = null;
	protected ?DateTime $date = null;
	protected ?string $datePlaceholder = null;
	/**
	 * @var Tag[]
	 */
	protected array $tags = [];
	protected ?User $user = null;
	protected ?InfoHelper $infoHelper = null;

	public function getChangeStreamButton(): ?ChangeStreamButton
	{
		return $this->changeStreamButton;
	}

	public function setChangeStreamButton(?ChangeStreamButton $changeStreamButton): self
	{
		$this->changeStreamButton = $changeStreamButton;

		return $this;
	}

	public function getTitle(): ?string
	{
		return $this->title;
	}

	public function setTitle(?string $title): self
	{
		$this->title = $title;

		return $this;
	}

	public function getTitleAction(): ?Action
	{
		return $this->titleAction;
	}

	public function setTitleAction(?Action $titleAction): self
	{
		$this->titleAction = $titleAction;

		return $this;
	}

	public function getDate(): ?DateTime
	{
		return $this->date;
	}

	public function setDate(?DateTime $date): self
	{
		$this->date = $date;

		return $this;
	}

	public function getDatePlaceholder(): ?string
	{
		return $this->datePlaceholder;
	}

	public function setDatePlaceholder(?string $placeholder): self
	{
		$this->datePlaceholder = $placeholder;

		return $this;
	}

	public function addTag(string $id, Tag $tag): self
	{
		$this->tags[$id] = $tag;

		return $this;
	}

	/**
	 * @return Tag[]
	 */
	public function getTags(): array
	{
		return $this->tags;
	}

	public function getTagById(string $id): ?Tag
	{
		return ($this->tags[$id] ?? null);
	}

	/**
	 * @param Tag[] $tags
	 *
	 * @return $this
	 */
	public function setTags(array $tags): self
	{
		$this->tags = [];
		foreach ($tags as $id => $tag)
		{
			$this->addTag((string)$id, $tag);
		}

		return $this;
	}

	public function getUser(): ?User
	{
		return $this->user;
	}

	public function setUser(?User $user): self
	{
		$this->user = $user;

		return $this;
	}

	public function getInfoHelper(): ?InfoHelper
	{
		return $this->infoHelper;
	}

	public function setInfoHelper(?InfoHelper $infoHelper): self
	{
		$this->infoHelper = $infoHelper;

		return $this;
	}

	public function toArray(): array
	{
		return [
			'changeStreamButton' => $this->getChangeStreamButton(),
			'title' => $this->getTitle(),
			'titleAction' => $this->getTitleAction(),
			'date' => $this->getDate(),
			'datePlaceholder' => $this->getDatePlaceholder(),
			'tags' => $this->getTags(),
			'user' => $this->getUser(),
			'infoHelper' => $this->getInfoHelper(),
		];
	}
}
