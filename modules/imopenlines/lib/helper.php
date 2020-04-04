<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\ImOpenLines;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Helper
{
	const ENUM_TEMPLATE_TRANSPARENT = 'transp';
	const ENUM_TEMPLATE_COLORED = 'colored';

	public static function getTemplateList()
	{
		return array(
			static::ENUM_TEMPLATE_TRANSPARENT => Loc::getMessage('IMOL_HELPER_TEMPLATE_TRANSPARENT'),
			static::ENUM_TEMPLATE_COLORED => Loc::getMessage('IMOL_HELPER_TEMPLATE_COLORED'),
		);
	}
	
	public static function getAddUrl()
	{
		return \Bitrix\ImOpenLines\Common::getPublicFolder() . "list/edit.php?ID=0";
	}
	
	public static function getEditUrl($lineId = 0)
	{
		$lineId = intval($lineId);
		return \Bitrix\ImOpenLines\Common::getPublicFolder() . 'list/' . ($lineId? "edit.php?ID=".$lineId: '');
	}
	
	public static function getListUrl()
	{
		return \Bitrix\ImOpenLines\Common::getPublicFolder() . 'list/';
	}

	public static function getConnectorUrl($connectorId, $lineId = 0)
	{
		$lineId = intval($lineId);

		if(!empty($connectorId))
			return \Bitrix\ImOpenLines\Common::getPublicFolder() . 'connector/?ID=' . $connectorId . ($lineId ? "&LINE=" . $lineId : '');
		else
			return \Bitrix\ImOpenLines\Common::getPublicFolder() . 'list/edit.php?ID=' . ($lineId ? $lineId : '0');
	}
	
	public static function isAvailable()
	{
		return \Bitrix\ImOpenLines\Config::available();
	}
	
	public static function isLiveChatAvailable()
	{
		return \Bitrix\ImOpenLines\LiveChatManager::available();
	}
}