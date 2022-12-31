<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Authentication\ApplicationPasswordTable;

class CIntranetUserProfilePasswordAjaxController extends \Bitrix\Main\Engine\Controller
{
	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws Exception
	 */
	public function logoutAction(?int $userId = null): void
    {
        global $USER;

		$currentUserId = (int)\Bitrix\Main\Engine\CurrentUser::get()->getId();

		if ($userId === $currentUserId)
		{
			$USER->SetParam("AUTH_ACTION_SKIP_LOGOUT", true);
		}
		elseif (is_null($userId))
		{
			$userId = $currentUserId;
			$USER->SetParam("AUTH_ACTION_SKIP_LOGOUT", true);
		}
		elseif (!\Bitrix\Intranet\CurrentUser::get()->isAdmin())
		{
			return;
		}

		\Bitrix\Main\UserAuthActionTable::addLogoutAction($userId);

		$passwordsList = ApplicationPasswordTable::getList([
			"filter" => [
				"=USER_ID" => $userId,
				"=APPLICATION_ID" => ["desktop", "mobile"],
			],
		]);

		while ($password = $passwordsList->fetch())
		{
			ApplicationPasswordTable::delete($password["ID"]);
		}
	}
}

