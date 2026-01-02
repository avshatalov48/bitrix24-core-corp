<?php

namespace Bitrix\Crm\ItemMiniCard\Layout\Field;

use Bitrix\Crm\ItemMiniCard\Layout\Field\Value\Link;

final class LinkField extends AbstractField
{
	public function __construct(
		public string $title,
		public array $links = [],
	)
	{
	}

	public function addValue(Link $link): self
	{
		$this->links[] = $link;

		return $this;
	}

	public function getName(): string
	{
		return 'LinkField';
	}

	public function getProps(): array
	{
		return [
			'title' => $this->title,
			'links' => $this->links,
		];
	}
}
