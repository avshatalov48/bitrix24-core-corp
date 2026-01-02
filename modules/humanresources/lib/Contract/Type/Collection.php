<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Contract\Type;

interface Collection extends \Countable
{
	public function getItems(): array;
}