<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class TemplatesCollectionComponent extends CBitrixComponent
{
	public function executeComponent()
	{
		$this->arResult['SHOW_TITLE'] = $this->arParams['SHOW_TITLE'];

		$this->includeComponentTemplate();
	}
}
