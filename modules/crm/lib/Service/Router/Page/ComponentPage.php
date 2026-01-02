<?php

namespace Bitrix\Crm\Service\Router\Page;

use Bitrix\Crm\Service\Router\Contract\Component;
use Bitrix\Crm\Service\Router\Contract\Page;

final class ComponentPage implements Page, Page\HasComponent
{
	public function __construct(
		private readonly Component $component,
	)
	{
	}

	public function render(?\CBitrixComponent $parentComponent = null): void
	{
		$this->component
			->setParent($parentComponent)
			->render();
	}

	public function title(): ?string
	{
		return null;
	}

	public function canUseFavoriteStar(): bool
	{
		return true;
	}

	public function component(): Component
	{
		return $this->component;
	}
}
