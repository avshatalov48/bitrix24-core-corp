<?php

namespace Bitrix\Crm\Service\Communication\Category;

final class Category
{
	public function __construct(
		private readonly int $id,
		private readonly string $moduleId,
		private readonly string $code,
		private readonly string $handlerClass,
	)
	{

	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getModuleId(): string
	{
		return $this->moduleId;
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getHandlerClass(): string
	{
		return $this->handlerClass;
	}
}
