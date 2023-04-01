<?php

namespace Bitrix\Dav;

use Bitrix\Main\Authentication\ApplicationPasswordTable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UrlRewriter;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Context;

Loc::loadMessages(__FILE__);

/**
 * Class Application
 * @package Bitrix\Dav
 */
class Application extends \Bitrix\Main\Authentication\Application
{
	/**
	 * Application passwords event handler.
	 * @return array
	 */
	public static function onApplicationsBuildList()
	{
		return array(
			array(
				"ID" => "caldav",
				"NAME" => Loc::getMessage("dav_app_calendar"),
				"DESCRIPTION" => Loc::getMessage("dav_app_calendar_desc"),
				"SORT" => 100,
				"OPTIONS" => array(Loc::getMessage("dav_app_calendar_phone")),
				"CLASS" => "\\Bitrix\\Dav\\Application"
			),
			array(
				"ID" => "carddav",
				"NAME" => Loc::getMessage("dav_app_card"),
				"DESCRIPTION" => Loc::getMessage("dav_app_card_desc"),
				"SORT" => 200,
				"OPTIONS" => array(Loc::getMessage("dav_app_calendar_phone")),
				"CLASS" => "\\Bitrix\\Dav\\Application"
			),
			array(
				"ID" => "webdav",
				"NAME" => Loc::getMessage("dav_app_doc"),
				"DESCRIPTION" => Loc::getMessage("dav_app_doc_desc"),
				"SORT" => 300,
				"OPTIONS" => array(Loc::getMessage("dav_app_doc_office")),
				"CLASS" => "\\Bitrix\\Dav\\Application"
			),
		);
	}

	/**
	 * Application constructor.
	 */
	public function __construct()
	{
		$this->validUrls = array("/bitrix/groupdav.php", "/index.php", "/.well-known");

		$site = \Bitrix\Main\Application::getInstance()->getContext()->getSite();
		$acceptedUrl = [
			'bitrix:socialnetwork_user',
			'bitrix:socialnetwork_group',
			'bitrix:disk.common',
			'bitrix:webdav',
		];

		if (!empty($site))
		{
			$urls = UrlRewriter::getList($site);
			foreach ($urls as $url)
			{
				if (isset($url['ID']) && in_array($url['ID'], $acceptedUrl, true))
				{
					$this->validUrls[] = $url['PATH'];
				}
			}
		}
	}

	/**
	 * Checks the valid scope for the applicaton.
	 *
	 * @return bool
	 */
	public function checkScope()
	{
		return parent::checkScope() && static::checkDavHeaders();
	}

	/**
	 * @return bool
	 */
	public static function checkDavHeaders()
	{
		$server = Context::getCurrent()->getServer();
		$davHeaders = [
			"DAV",
			"IF",
			"DEPTH",
			"OVERWRITE",
			"DESTINATION",
			"LOCK_TOKEN",
			"TIMEOUT",
			"STATUS_URI"
		];
		foreach ($davHeaders as $header)
		{
			if ($server->get("HTTP_" . $header))
			{
				return true;
			}
		}

		$davMethods = [
			"OPTIONS",
			"PUT",
			"PROPFIND",
			"REPORT",
			"PROPPATCH",
			"MKCOL",
			"COPY",
			"MOVE",
			"LOCK",
			"UNLOCK",
			"DELETE",
			"COPY",
			"MOVE"
		];
		if (in_array($server->getRequestMethod(), $davMethods, true))
		{
			return true;
		}


		$userAgentParam = $server->get('HTTP_USER_AGENT');
		$userAgentString = $userAgentParam ?: '';

		return (
				mb_strpos($userAgentString, "Microsoft Office") !== false
				&& mb_strpos($userAgentString, "Outlook") === false
			)
			|| mb_strpos($userAgentString, "MiniRedir") !== false
			|| mb_strpos($userAgentString, "WebDAVFS") !== false
			|| mb_strpos($userAgentString, "davfs2") !== false
			|| mb_strpos($userAgentString, "Sardine") !== false
			|| mb_strpos($userAgentString, "gvfs") !== false
			|| mb_strpos($userAgentString, "LibreOffice") !== false
			;
	}

	/**
	 * Generates AP for REST access.
	 *
	 * @param int $userId Id of password owner user.
	 * @param string $appId Type of application(caldav, carddav, webdav).
	 * @return bool|string password or false
	 * @throws \Exception
	 */
	public static function generateAppPassword($userId, $appId)
	{

		$password = ApplicationPasswordTable::generatePassword();
		$message = Loc::getMessage('DAV_APP_SYSCOMMENT');
		if ($appId)
		{
			$typeTitle = Loc::getMessage('DAV_APP_TYPE_' . $appId);
			if ($typeTitle)
			{
				$message = Loc::getMessage('DAV_APP_SYSCOMMENT_TYPE', array(
					'#TYPE#' => $typeTitle,
				));
			}
		}


		$res = ApplicationPasswordTable::add(array(
			'USER_ID' => $userId,
			'APPLICATION_ID' => $appId,
			'PASSWORD' => $password,
			'DATE_CREATE' => new DateTime(),
			'COMMENT' => Loc::getMessage('DAV_APP_COMMENT'),
			'SYSCOMMENT' => $message,
		));

		if ($res->isSuccess())
		{
			return $password;
		}

		return false;
	}
}