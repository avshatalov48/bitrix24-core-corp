<?php

namespace Bitrix\Crm\ItemMiniCard\Layout\Avatar;

final class ImageAvatar extends AbstractAvatar
{
	public function __construct(
		public string $url,
	)
	{
	}

	public function getName(): string
	{
		return 'ImageAvatar';
	}

	public function getProps(): array
	{
		return [
			'url' => $this->url,
		];
	}
}
