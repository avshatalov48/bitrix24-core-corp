<?php

namespace Bitrix\Intranet\Settings\Controls;

class Tab extends Control
{
	/*
	array head = [
		?string title,
		?strings description,
		string? className,
	]
	 */
	public function __construct(
		string $id,
		private array $head,
		private int $sort = 0,
		private bool $active = false,
		private bool $restricted = false,
		private ?string $bannerCode = null,
		private ?string $helpDeskCode = null,

	)
	{
		parent::__construct($id);
	}

	public function getHead(): array
	{
		return $this->head;
	}

	public function setHead(array $head): void
	{
		$this->head = $head;
	}

	public function isActive(): bool
	{
		return $this->active;
	}

	public function setActive(bool $active): void
	{
		$this->active = $active;
	}

	public function getSort(): int
	{
		return $this->sort;
	}

	public function setSort(int $sort): void
	{
		$this->sort = $sort;
	}

	public function isRestricted(): bool
	{
		return $this->restricted;
	}

	public function setRestricted(bool $restricted): void
	{
		$this->restricted = $restricted;
	}

	public function getBannerCode(): ?string
	{
		return $this->bannerCode;
	}

	public function setBannerCode(?string $bannerCode): void
	{
		$this->bannerCode = $bannerCode;
	}

	public function getHelpDeskCode(): ?string
	{
		return $this->helpDeskCode;
	}

	public function setHelpDeskCode(?string $helpDeskCode): void
	{
		$this->helpDeskCode = $helpDeskCode;
	}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->getId(),
			'head' => $this->head,
			'active' => $this->active,
			'sort' => $this->sort,
			'restricted' => $this->restricted,
			'bannerCode' => $this->bannerCode,
			'helpDeskCode' => $this->helpDeskCode,
		];
	}
}