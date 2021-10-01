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

	/**
	 * @return string
	 */
	public static function getAddUrl(): string
	{
		return Common::getContactCenterPublicFolder() . 'lines_edit/?ID=?ID=0';
	}

	/**
	 * @param int $lineId
	 * @return string
	 */
	public static function getEditUrl($lineId = 0):string
	{
		$lineId = (int)$lineId;
		return Common::getContactCenterPublicFolder() . ($lineId? 'lines_edit/?ID=' . $lineId: '');
	}

	/**
	 * @return string
	 */
	public static function getListUrl(): string
	{
		return Common::getContactCenterPublicFolder();
	}

	/**
	 * @param $connectorId
	 * @param int $lineId
	 * @return string
	 */
	public static function getConnectorUrl($connectorId, $lineId = 0): string
	{
		$lineId = (int)$lineId;

		if(!empty($connectorId))
		{
			return Common::getContactCenterPublicFolder() . 'connector/?ID=' . $connectorId . ($lineId ? '&LINE=' . $lineId : '');
		}
		else
		{
			return Common::getContactCenterPublicFolder() . 'lines_edit/?ID=' . ($lineId ?: '0');
		}
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