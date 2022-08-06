<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');

\Bitrix\Main\Page\Asset::getInstance()->addString(
	'<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">'
);