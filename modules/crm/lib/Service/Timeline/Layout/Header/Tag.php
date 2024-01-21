<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Header;

use Bitrix\Crm\Service\Timeline\Layout\Button;

class Tag extends Button
{
	public const TYPE_SUCCESS = 'success';
	public const TYPE_FAILURE = 'failure';
	public const TYPE_WARNING = 'warning';
	public const TYPE_PRIMARY = 'primary';
	public const TYPE_SECONDARY = 'secondary';
	public const TYPE_LAVENDER = 'lavender';

	protected string $type;
	protected string $hint = '';

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

	public function getHint(): string
	{
		return $this->hint;
	}

	public function setHint(string $hint): self
	{
		$this->hint = $hint;

		return $this;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'type' => $this->getType(),
				'hint' => $this->getHint(),
			]
		);
	}
}
