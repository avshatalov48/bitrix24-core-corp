<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Loader::includeModule('im');

return [
	'limitOnline' => \CUser::GetSecondsForLimitOnline(),
	'colors' => \Bitrix\Im\Color::getColors(),
];
