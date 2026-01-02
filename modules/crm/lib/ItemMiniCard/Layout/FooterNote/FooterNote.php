<?php

namespace Bitrix\Crm\ItemMiniCard\Layout\FooterNote;

final class FooterNote extends AbstractFooterNote
{
	public function __construct(
		public string $content,
	)
	{
	}

	public function getName(): string
	{
		return 'CommonFooterNote';
	}

	public function getProps(): array
	{
		return [
			'content' => $this->content,
		];
	}
}
