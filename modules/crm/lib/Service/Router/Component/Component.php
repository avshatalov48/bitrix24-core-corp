<?php

namespace Bitrix\Crm\Service\Router\Component;

class Component implements \Bitrix\Crm\Service\Router\Contract\Component
{
	public function __construct(
		public string $name,
		public array $parameters = [],
		public string $template = '',
		public ?\CBitrixComponent $parent = null,
	)
	{
	}

	public function name(): string
	{
		return $this->name;
	}

	public function template(): string
	{
		return $this->template;
	}

	public function parameters(): array
	{
		return $this->parameters;
	}

	public function parent(): ?\CBitrixComponent
	{
		return $this->parent;
	}

	public function setParameter(string $name, mixed $value): static
	{
		$this->parameters[$name] = $value;

		return $this;
	}

	public function parameter(string $name): mixed
	{
		return $this->parameters[$name] ?? null;
	}

	public function setParent(?\CBitrixComponent $parent): static
	{
		$this->parent = $parent;

		return $this;
	}

	public function render(): void
	{
		global $APPLICATION;

		$APPLICATION->IncludeComponent(
			$this->name(),
			$this->template(),
			$this->parameters(),
			$this->parent(),
		);
	}
}
