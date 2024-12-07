<?php

namespace Bitrix\Crm\Service\Communication\Channel;

final class Channel
{
	public function __construct(
		private readonly int $id,
		private readonly string $moduleId,
		private readonly int $categoryId,
		private readonly string $code,
		private readonly string $handlerClass,
		private readonly bool $isEnabled,
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

	public function getCategoryId(): int
	{
		return $this->categoryId;
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getHandlerClass(): string
	{
		return $this->handlerClass;
	}

	public function isEnabled(): bool
	{
		return $this->isEnabled;
	}

	public function toArray(): array
	{
		return [
			'moduleId' => $this->getModuleId(),
			'code' => $this->getCode(),
			'categoryId' => $this->getCategoryId(),
			'handlerClass' => $this->getHandlerClass(),
			'isEnabled' => $this->isEnabled(),
		];
	}
}
