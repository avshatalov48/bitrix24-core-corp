<?php

namespace Bitrix\Mobile;

use Bitrix\Main\Authentication\ApplicationPasswordTable;
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

	public static function getOneTimeAuthHash() {
		global $USER;
		$path = "/mobile/";
		$userId = $USER->getId();
		$hash = \CUser::GetHitAuthHash($path, $userId);

		if ($hash)
		{
			return $hash;
		}
		else
		{
			return \CUser::AddHitAuthHash($path, $USER->getId(), SITE_ID);
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
					if(Loader::includeModule("pull"))
					{
						\CPullStack::AddByUser($userId,
							array(
								"module_id" => "mobile",
								"command" => "oneTimeHashRemoved"
							)
						);
					}

					return true;
				}
			}
		}

		return false;
	}
}