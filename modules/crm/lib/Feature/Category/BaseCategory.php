<?php

namespace Bitrix\Crm\Feature\Category;

use Bitrix\Crm\Traits\Singleton;

abstract class BaseCategory
{
	use Singleton;

	/**
	 * Category name in russian. Used to display feature switchers grouped by category
	 *
	 * @return string
	 */
	abstract public function getName():string;

	/**
	 * Category sort index. Used to set correct categories and features order in list
	 *
	 * @return int
	 */
	abstract public function getSort(): int;


	/**
	 * Category unique id
	 *
	 * @return string
	 */
	public function getId(): string
	{
		$classParts = explode('\\', static::class);

		return end($classParts);
	}
}
