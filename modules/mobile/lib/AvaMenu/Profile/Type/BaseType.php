<?php

namespace Bitrix\Mobile\AvaMenu\Profile\Type;

class BaseType
{
	protected string $image;
	protected string $title;

	public function __construct($title, $image)
	{
		$this->title = $title;
		$this->image = $image;
	}

	public function setTitle(string $title): void
	{
		$this->title = $title;
	}

	public function setImage(string $image): void
	{
		$this->image = $image;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function getImageUri(): string
	{
		return $this->image;
	}

	public function getAvatar(): array
	{
		$accentType = $this->getAccentType();

		return [
			'type' => $this->getType(),
			'title' => $this->getTitle(),
			'uri' => $this->getImageUri(),
			'accentType' => $accentType,
			'hideOutline' => !$accentType,
			'placeholder' => $this->getPlaceholder(),
		];
	}

	public function getType(): string
	{
		return 'circle';
	}

	public function getAccentType(): ?string
	{
		return null;
	}

	public function getStyle(): ?array
	{
		return null;
	}

	private function getPlaceholderType(): string
	{
		return 'auto';
	}

	private function getPlaceholder(): array
	{
		$placeholderType = $this->getPlaceholderType();

		return [
			'type' => $placeholderType,
			'backgroundColor' => $this->getPlaceholderBackgroundColor()
		];
	}

	protected function getPlaceholderBackgroundColor(): string
	{
		global $USER;

		$backgroundColors = [
			'#df532d',
			'#64a513',
			'#4ba984',
			'#4ba5c3',
			'#3e99ce',
			'#8474c8',
			'#1eb4aa',
			'#f76187',
			'#58cc47',
			'#ab7761',
			'#29619b',
			'#728f7a',
			'#ba9c7b',
			'#e8a441',
			'#556574',
			'#909090',
			'#5e5f5e',
		];

		return $backgroundColors[$USER->getId() % count($backgroundColors)];
	}

}
