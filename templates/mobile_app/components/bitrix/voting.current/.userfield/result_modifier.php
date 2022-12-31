<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage mobile
 * @copyright 2001-2016 Bitrix
 *
 * Bitrix vars
 * @var array $arParams
 * @var array $arResult
 */

$this->__component->arParams["UID"] = $arParams["UID"] = randString(5);
ob_start();