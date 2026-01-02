<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Builder\Structure\Sort;

interface SortInterface extends \Bitrix\Main\Provider\Params\SortInterface
{
	/**
	 * @param string $alias
	 *
	 * @return $this
	 */
	public function setCurrentAlias(string $alias): static;
}