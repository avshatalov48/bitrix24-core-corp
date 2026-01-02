<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arTemplate = [
	'NAME' => Loc::getMessage('TEMPLATE_NAME'),
	'DESCRIPTION' => Loc::getMessage('TEMPLATE_DESCRIPTION'),
];
