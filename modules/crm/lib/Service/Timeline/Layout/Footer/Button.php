<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Footer;

class Button extends \Bitrix\Crm\Service\Timeline\Layout\Button
{
	public const TYPE_PRIMARY = 'primary';
	public const TYPE_SECONDARY = 'secondary';

	protected string $type;

	public function __construct(string $title, string $type)
	{
		parent::__construct($title);
		$this->type = $type;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function setType(string $type): self
	{
		$this->type = $type;

		return $this;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'type' => $this->getType(),
			]
		);
	}
}
