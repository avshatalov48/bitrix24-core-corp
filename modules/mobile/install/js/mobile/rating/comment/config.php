<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

//use Bitrix\Main\Localization\Loc;

//Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/install/js/main/rating/config.php');

return [
	'js' => 'mobile.rating.comment.js',
	'lang_additional' => [
	],
	'rel' => [ ],
];
