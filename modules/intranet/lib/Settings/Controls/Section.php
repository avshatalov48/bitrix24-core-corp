<?php

namespace Bitrix\Intranet\Settings\Controls;

class Section extends Control
{

	public function __construct(
		string $id,
		private string $title,
		private ?string $icon = null,
		private bool $isOpen = true,
		private bool $isEnable = true,
		private bool $canCollapse = true,
		private ?string $bannerCode = null,
	)
	{
		parent::__construct($id);
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function setTitle(string $title): void
	{
		$this->title = $title;
	}

	public function isOpen(): bool
	{
		return $this->isOpen;
	}

	public function setIsOpen(bool $isOpen): void
	{
		$this->isOpen = $isOpen;
	}

	public function isEnable(): bool
	{
		return $this->isEnable;
	}

	public function setIsEnable(bool $isEnable): void
	{
		$this->isEnable = $isEnable;
	}

	public function isCanCollapse(): bool
	{
		return $this->canCollapse;
	}

	public function setCanCollapse(bool $canCollapse): void
	{
		$this->canCollapse = $canCollapse;
	}

	public function getIcon(): ?string
	{
		return $this->icon;
	}

	public function setIcon(?string $icon): void
	{
		$this->icon = $icon;
	}

	public function getBannerCode(): ?string
	{
		return $this->bannerCode;
	}

	public function setBannerCode(?string $bannerCode): void
	{
		$this->bannerCode = $bannerCode;
	}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->getId(),
			'title' => $this->title,
			'isOpen' => $this->isOpen,
			'isEnable' => $this->isEnable,
			'canCollapse' => $this->canCollapse,
			'icon' => $this->icon,
			'titleIconClasses' => $this->icon,
			'bannerCode' => $this->bannerCode,
		];
	}
}