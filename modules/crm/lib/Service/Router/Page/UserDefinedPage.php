<?php

namespace Bitrix\Crm\Service\Router\Page;

use Bitrix\Crm\Service\Router\Component\Component;
use Bitrix\Crm\Service\Router\Contract;
use Bitrix\Crm\Service\Router\Route;

final class UserDefinedPage implements Contract\Page\UserDefinedPage, Contract\Page\HasComponent
{
	private array $parameters = [];

	public function __construct(
		private readonly string $componentName,
		private readonly string $url,
	)
	{
	}

	public function title(): ?string
	{
		return null;
	}

	public function canUseFavoriteStar(): ?bool
	{
		return null;
	}

	public function render(?\CBitrixComponent $parentComponent = null): void
	{
		$this
			->component()
			->setParent($parentComponent)
			->render();
	}

	public function component(): Contract\Component
	{
		return new Component(
			name: $this->componentName,
			parameters: $this->parameters,
		);
	}

	public function route(): Route
	{
		return (new Route($this->url))
			->setRelatedComponent($this->componentName)
		;
	}

	public function setParameters(array $parameters = []): static
	{
		$this->parameters = $parameters;

		return $this;
	}
}
