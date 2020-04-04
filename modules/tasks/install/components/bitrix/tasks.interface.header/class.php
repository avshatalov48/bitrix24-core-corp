<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 */

/** !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */
/** This is alfa version of component! Don't use it! */
/** !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! */

use Bitrix\Tasks\Util\User;

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksInterfaceHeaderComponent extends TasksBaseComponent
{
	protected function checkParameters()
	{
		self::tryParseStringParameter($this->arParams['SHOW_QUICK_FORM'], 'Y');
		self::tryParseStringParameter($this->arParams['SHOW_VIEW_MODE'], 'Y');
		self::tryParseArrayParameter($this->arParams['POPUP_MENU_ITEMS'], array());
		return parent::checkParameters();
	}
}