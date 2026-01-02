<?php

namespace Bitrix\Crm\ItemMiniCard\Layout\Avatar;

final class IconAvatar extends AbstractAvatar
{
	private array $iconOptions = [
		'size' => 32,
		'color' => 'rgb(31, 134, 255)',
	];

	public ?string $bgColor = '--ui-color-accent-soft-blue-3';

	public function __construct(string $iconName)
	{
		$this->iconOptions['name'] = $iconName;
	}

	public function size(?int $size): self
	{
		$this->iconOptions['size'] = $size;

		return $this;
	}

	public function color(?string $color): self
	{
		$this->iconOptions['color'] = $color;

		return $this;
	}

	public function bgColor(?string $bgColor): self
	{
		$this->bgColor = $bgColor;

		return $this;
	}

	public function getName(): string
	{
		return 'IconAvatar';
	}

	public function getProps(): array
	{
		return [
			'iconOptions' => $this->iconOptions,
			'bgColor' => $this->bgColor,
		];
	}
}
