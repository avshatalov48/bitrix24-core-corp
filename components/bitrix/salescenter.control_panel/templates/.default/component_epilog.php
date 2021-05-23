<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if(\Bitrix\Main\Loader::includeModule('salescenter'))
{
	\Bitrix\SalesCenter\Driver::getInstance()->addTopPanel($this->getTemplate());
}