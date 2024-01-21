<?php

namespace Bitrix\Socialnetwork\Space\List\Item;

use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Socialnetwork\Helper\Avatar;

final class Space implements Arrayable
{
	private int $id;
	private string $name = '';
	private bool $isPinned = false;
	private ?DateTime $dateActivity = null;
	private string $lastActivityDescription = '';
	private ?Avatar $avatar = null;
	private string $visibilityType = '';
	private int $counter = 0;
	private ?Datetime $lastSearchDate = null;
	private string $userRole = '';
	private bool $follow = false;

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): self
	{
		$this->name = $name;

		return $this;
	}

	public function setIsPinned(bool $isPinned): self
	{
		$this->isPinned = $isPinned;

		return $this;
	}

	public function setDateActivity(DateTime $dateActivity): self
	{
		$this->dateActivity = $dateActivity;

		return $this;
	}

	public function setLastActivityDescription(string $lastActivityDescription): self
	{
		$this->lastActivityDescription = $lastActivityDescription;

		return $this;
	}

	public function setAvatar(Avatar $avatar): self
	{
		$this->avatar = $avatar;

		return $this;
	}

	public function setVisibilityType(string $visibilityType): self
	{
		$this->visibilityType = $visibilityType;

		return $this;
	}

	public function setCounter(int $counter): self
	{
		$this->counter = $counter;

		return $this;
	}

	public function setLastSearchDate(?DateTime $lastSearchDate): self
	{
		$this->lastSearchDate = $lastSearchDate;

		return $this;
	}

	public function setUserRole(string $userRole): self
	{
		$this->userRole = $userRole;

		return $this;
	}

	public function setFollow(bool $follow): self
	{
		$this->follow = $follow;

		return $this;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'isPinned' => $this->isPinned,
			'dateActivity' => $this->dateActivity,
			'dateActivityTimestamp' => $this->dateActivity?->getTimestamp(),
			'lastActivityDescription' => $this->lastActivityDescription,
			'avatar' => $this->avatar?->toArray(),
			'visibilityType' => $this->visibilityType,
			'counter' => $this->counter,
			'lastSearchDate' => $this->lastSearchDate,
			'lastSearchDateTimestamp' => $this->lastSearchDate?->getTimestamp(),
			'userRole' => $this->userRole,
			'follow' => $this->follow,
		];
	}
}