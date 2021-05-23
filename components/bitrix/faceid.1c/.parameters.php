<?
use Bitrix\Main\Loader;
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arCurrentValues */
/** @global CUserTypeManager $USER_FIELD_MANAGER */

if (!Loader::includeModule("faceid"))
	return;

