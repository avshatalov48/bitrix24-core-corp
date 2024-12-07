<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @deprecated use CatalogConfigSettingsComponent
 */
class CCrmConfigCatalogSettings extends \CBitrixComponent
{
	/**
	 * @inheritDoc
	 */
	public function executeComponent()
	{
		$this->includeComponentTemplate();
	}
}
