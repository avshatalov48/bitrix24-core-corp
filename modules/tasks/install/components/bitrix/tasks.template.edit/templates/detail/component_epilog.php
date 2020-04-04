<?
use \Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

Loc::loadMessages(__FILE__);

if(intval($this->arResult['DATA']['ID']))
{
	$GLOBALS['APPLICATION']->SetTitle(Loc::getMessage('TASK_TEMPLATE_DETAIL_TITLE', array('#TITLE#' => $this->arResult['DATA']['TITLE'])));
}