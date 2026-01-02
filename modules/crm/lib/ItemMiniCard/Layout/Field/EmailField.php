<?php

namespace Bitrix\Crm\ItemMiniCard\Layout\Field;

use Bitrix\Crm\ItemMiniCard\Layout\Field\Value\Email;

final class EmailField extends AbstractField
{
	public function __construct(
		public string $title,
		public array $emails = [],
	)
	{
	}

	public function addValue(Email $email): self
	{
		$this->emails[] = $email;

		return $this;
	}

	public function getName(): string
	{
		return 'EmailField';
	}

	public function getProps(): array
	{
		return [
			'title' => $this->title,
			'emails' => array_map(static fn (Email $email) => $email->toArray(), $this->emails),
		];
	}
}
