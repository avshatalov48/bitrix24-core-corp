<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage xmpp
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Xmpp;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class XmppApplication extends \Bitrix\Main\Authentication\Application
{
	/**
	 * Event handler for application passwords.
	 * @return array
	 */
	public static function onApplicationsBuildList()
	{
		return array(
			"ID" => "xmpp",
			"NAME" => Loc::getMessage("xmpp_app_name"),
			"DESCRIPTION" => Loc::getMessage("xmpp_app_desc"),
			"SORT" => 2000,
			"CLASS" => '\Bitrix\Xmpp\XmppApplication',
		);
	}
}
