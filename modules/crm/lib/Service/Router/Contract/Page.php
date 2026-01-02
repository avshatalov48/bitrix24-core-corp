<?php

namespace Bitrix\Crm\Service\Router\Contract;

use Bitrix\Crm\Tour;

interface Page
{
	public function title(): ?string;

	public function canUseFavoriteStar(): ?bool;

	public function render(?\CBitrixComponent $parentComponent = null): void;
}
