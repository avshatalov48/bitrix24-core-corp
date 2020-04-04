<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// todo: remove this when use object with array access instead of ['ITEMS']['DATA']
if(\Bitrix\Tasks\Util\Type::isIterable($arResult['TEMPLATE_DATA']['ITEMS']['DATA']))
{
	foreach($arResult['TEMPLATE_DATA']['ITEMS']['DATA'] as &$item)
	{
		$item['TITLE_HTML'] = \Bitrix\Tasks\UI::convertBBCodeToHtmlSimple($item['TITLE']);
	}
	unset($item);
}