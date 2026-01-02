<?php

namespace Bitrix\Crm\Service\Router\Contract;

use CBitrixComponent;

interface Component
{
	public function name(): string;

	public function template(): string;

	public function parameters(): array;

	public function parameter(string $name): mixed;

	public function setParameter(string $name, mixed $value): static;

	public function parent(): ?CBitrixComponent;

	public function setParent(?CBitrixComponent $parent): static;

	public function render(): void;
}
