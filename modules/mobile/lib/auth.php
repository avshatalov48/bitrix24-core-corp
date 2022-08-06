<?php

namespace Bitrix\Mobile;

use Bitrix\Main\Authentication\ApplicationPasswordTable;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;

class Auth
{
	public static function setNotAuthorizedHeaders()
	{
		header("HTTP/1.0 401 Not Authorized");
		header('WWW-Authenticate: Basic realm="Bitrix24"');
		header("Content-Type: application/x-javascript");
		header("BX-Authorize: " . bitrix_sessid());
	}

	public static function getNotAuthorizedResponse()
	{
		return [
			"status" => "failed",
			"bitrix_sessid" => bitrix_sessid()
		];
	}

	public static function getOneTimeAuthHash(int $userId = null)
	{
		$path = '/mobile/';

		if (!$userId)
		{
			global $USER;
			$userId = $USER->getId();
		}

		$siteId = \CSite::GetDefSite();
		$hash = \CUser::GetHitAuthHash($path, $userId, $siteId);
		if ($hash)
		{
			return $hash;
		}
		else
		{
			return \CUser::AddHitAuthHash($path, $userId, $siteId);
		}
	}

	public static function removeOneTimeAuthHash($hash = null)
	{
		if(!empty($hash))
		{
			global $DB;
			global $USER;

			$userId = $USER->getId();
			if ($userId > 0)
			{
				$hash = $DB->ForSql($hash);
				$where = "URL = '/mobile/' AND USER_ID=${userId} AND HASH='${hash}'";
				/** @var \CDBResult $dbResult */
				$dbResult = $DB->Query("DELETE FROM b_user_hit_auth WHERE ${where}");
				if ($dbResult->result && $dbResult->AffectedRowsCount())
				{
					$handlers = EventManager::getInstance()->findEventHandlers('mobile', 'oneTimeHashRemoved');
					foreach ($handlers as $handler) {
						ExecuteModuleEventEx($handler, array($userId, $hash));
					}

					return true;
				}
			}
		}

		return false;
	}
}
