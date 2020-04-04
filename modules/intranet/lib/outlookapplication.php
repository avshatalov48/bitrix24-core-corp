<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Intranet;

use Bitrix\Main\Authentication\ApplicationPasswordTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class OutlookApplication extends \Bitrix\Main\Authentication\Application
{
	const ID = "ws_outlook";

	protected $validUrls = array(
		"/bitrix/tools/ws_calendar/",
		"/bitrix/tools/ws_calendar_extranet/",
		"/bitrix/tools/ws_contacts/",
		"/bitrix/tools/ws_contacts_crm/",
		"/bitrix/tools/ws_contacts_extranet/",
		"/bitrix/tools/ws_contacts_extranet_emp/",
		"/bitrix/tools/ws_tasks/",
		"/bitrix/tools/ws_tasks_extranet/",
		"/bitrix/services/stssync/",
		"/stssync/",
	);

	public static function OnApplicationsBuildList()
	{
		return array(
			"ID" => static::ID,
			"NAME" => Loc::getMessage("WS_OUTLOOK_APP_TITLE"),
			"DESCRIPTION" => Loc::getMessage("WS_OUTLOOK_APP_DESC"),
			"SORT" => 1000,
			"CLASS" => __CLASS__,
			"OPTIONS_CAPTION" => Loc::getMessage('WS_OUTLOOK_APP_OPTIONS_CAPTION'),
			"OPTIONS" => array(
				Loc::getMessage("WS_OUTLOOK_APP_TITLE_OPTION"),
			)
		);
	}

	/**
	 * Generates AP for REST access.
	 *
	 * @param string $siteTitle Site title for AP description.
	 *
	 * @return bool|string password or false
	 * @throws \Exception
	 */
	public static function generateAppPassword($type = '')
	{
		global $USER;

		$password = ApplicationPasswordTable::generatePassword();

		$message = Loc::getMessage('WS_OUTLOOK_APP_SYSCOMMENT');
		if($type)
		{
			$typeTitle = Loc::getMessage('WS_OUTLOOK_APP_TYPE_'.$type);
			if(strlen($typeTitle) > 0)
			{
				$message = Loc::getMessage('WS_OUTLOOK_APP_SYSCOMMENT_TYPE', array(
					'#TYPE#' => $typeTitle,
				));
			}
		}

		$res = ApplicationPasswordTable::add(array(
			'USER_ID' => $USER->getID(),
			'APPLICATION_ID' => static::ID,
			'PASSWORD' => $password,
			'DATE_CREATE' => new DateTime(),
			'COMMENT' => Loc::getMessage('WS_OUTLOOK_APP_COMMENT'),
			'SYSCOMMENT' => $message,
		));

		if($res->isSuccess())
		{
			return $password;
		}

		return false;
	}
}
