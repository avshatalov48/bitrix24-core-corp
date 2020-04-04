<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$basketIds = (is_array($_REQUEST['BID']) ? $_REQUEST['BID'] : []);

foreach($arResult['PRODUCTS'] as $basketId => $product)
{
	if(in_array($basketId, $basketIds))
	{
		unset($arResult['PRODUCTS'][$basketId]);
	}
}