<?php

namespace Bitrix\Mobile\Menu\Entity;

abstract class BaseMenuItem
{
	public function __construct(
		protected readonly string $id,
		protected readonly string $title,
	)
	{}

	public function getId(): string
	{
		return $this->id;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	abstract public function toArray(): array;
}
