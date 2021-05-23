<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Authentication\ApplicationPasswordTable;

class CIntranetUserProfilePasswordAjaxController extends \Bitrix\Main\Engine\Controller
{
	public function logoutAction()
	{
		global $USER;

		$USER->SetParam("AUTH_ACTION_SKIP_LOGOUT", true);
		\Bitrix\Main\UserAuthActionTable::addLogoutAction($USER->GetID());

		$passwordsList = ApplicationPasswordTable::getList([
			"filter" => [
				"=USER_ID" => $USER->GetID(),
				"=APPLICATION_ID" => ["desktop", "mobile"]
			]
		]);

		while($password = $passwordsList->fetch())
		{
			ApplicationPasswordTable::delete($password["ID"]);
		}
	}
}
