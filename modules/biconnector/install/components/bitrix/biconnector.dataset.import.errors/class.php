<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class DatasetImportErrors extends CBitrixComponent
{
	public function onPrepareComponentParams($arParams)
	{
		if (!in_array($arParams['part'], ['header', 'row', 'footer', 'footer_overflow'], true))
		{
			$arParams['part'] = '';
		}

		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		$this->includeComponentTemplate($this->arParams['part']);
	}
}
