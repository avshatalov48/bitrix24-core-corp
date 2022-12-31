<?php

namespace Bitrix\Crm\Filter;

/**
 * Interface ISettingsSupportsCategory
 *
 * @package Bitrix\Crm\Filter
 */
interface ISettingsSupportsCategory
{
	/**
	 * @return int|null
	 */
	public function getCategoryId(): ?int;
}
