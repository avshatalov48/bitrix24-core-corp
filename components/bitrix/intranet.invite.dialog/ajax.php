<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Bitrix24\Integrator;

class CIntranetInviteDialogComponentAjaxController extends \Bitrix\Main\Engine\Controller
{
	protected function isInvitingUsersAllowed()
	{
		global $USER;

		if (
			(
				Loader::includeModule('bitrix24')
				&& !\CBitrix24::isInvitingUsersAllowed()
			)
			|| (
				!ModuleManager::isModuleInstalled('bitrix24')
				&& !$USER->CanDoOperation('edit_all_users')
			)
		)
		{
			return false;
		}

		return true;
	}

	protected function isExtranetInstalled()
	{
		$bExtranetInstalled = ModuleManager::IsModuleInstalled("extranet");
		if ($bExtranetInstalled)
		{
			$extranetSiteId = \Bitrix\Main\Config\Option::get("extranet", "extranet_site");
			if (empty($extranetSiteId))
			{
				$bExtranetInstalled = false;
			}
		}

		return $bExtranetInstalled;
	}

	protected function isMoreUserAvailable()
	{
		if (Loader::includeModule("bitrix24") && !CBitrix24::isMoreUserAvailable())
		{
			return false;
		}

		return true;
	}

	public function inviteAction()
	{
		if (!$this->isInvitingUsersAllowed())
		{
			return false;
		}

		if (!$this->isMoreUserAvailable())
		{
			$this->addError(new \Bitrix\Main\Error("User limit"));
			return "user_limit";
		}

		if (
			intval($_POST["DEPARTMENT_ID"]) <= 0
			&& (
				!isset($_POST["SONET_GROUPS_CODE"])
				|| empty($_POST["SONET_GROUPS_CODE"])
			)
			&& $this->isExtranetInstalled()
		)
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("BX24_INVITE_DIALOG_ERROR_EXTRANET_NO_SONET_GROUP_INVITE")));
			return false;
		}

		$idInvitedUser = \CIntranetInviteDialog::RegisterNewUser(SITE_ID, $_POST, $arError);

		if(
			is_array($arError)
			&& count($arError) > 0
		)
		{
			$strError = "";

			foreach($arError as $strErrorText)
			{
				if($strErrorText <> '')
				{
					$strError .= '<li style="list-style-position: inside;">'.$strErrorText.'</li>';
				}
			}
		}

		if (!empty($strError))
		{
			$this->addError(new \Bitrix\Main\Error($strError));
			return false;
		}

		CIntranetInviteDialog::RequestToSonetGroups($idInvitedUser, $_POST["SONET_GROUPS_CODE"], $_POST["SONET_GROUPS_NAME"], (intval($_POST["DEPARTMENT_ID"]) <= 0));

		CIntranetInviteDialog::logAction($idInvitedUser, ($_POST["DEPARTMENT_ID"] > 0? 'intranet': 'extranet'), 'invite_user', 'invite_dialog');

		return Loc::getMessage("BX24_INVITE_DIALOG_INVITED", array("#SITE_DIR#" => SITE_DIR));
	}

	public function selfAction()
	{
		if (!$this->isInvitingUsersAllowed())
		{
			return false;
		}

		if (Loader::includeModule("socialservices"))
		{
			\Bitrix\Socialservices\Network::setRegisterSettings(array(
				"REGISTER" => $_POST["allow_register"],
				"REGISTER_CONFIRM" => $_POST["allow_register_confirm"],
				"REGISTER_WHITELIST" => $_POST["allow_register_whitelist"],
				"REGISTER_TEXT" => $_POST["allow_register_text"] && $_POST["allow_register_text"] != GetMessage("BX24_INVITE_DIALOG_REGISTER_TEXT_PLACEHOLDER_N_1") ? $_POST["allow_register_text"] : "",
				"REGISTER_SECRET" => $_POST["allow_register_secret"],
			));
		}

		return Loc::getMessage("BX24_INVITE_DIALOG_SELF", array("#SITE_DIR#" => SITE_DIR));
	}

	public function addAction()
	{
		if (!$this->isInvitingUsersAllowed())
		{
			return false;
		}

		if (!$this->isMoreUserAvailable())
		{
			$this->addError(new \Bitrix\Main\Error("User limit"));
			return "user_limit";
		}

		if (
			intval($_POST["DEPARTMENT_ID"]) <= 0
			&& (
				!isset($_POST["SONET_GROUPS_CODE"])
				|| empty($_POST["SONET_GROUPS_CODE"])
			)
			&& $this->isExtranetInstalled()
		)
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("BX24_INVITE_DIALOG_ERROR_EXTRANET_NO_SONET_GROUP_ADD")));
			return false;
		}

		if (ModuleManager::isModuleInstalled("mail"))
		{
			if (
				isset($_POST["ADD_MAILBOX_PASSWORD"])
				&& $_POST['ADD_MAILBOX_PASSWORD'] != $_POST['ADD_MAILBOX_PASSWORD_CONFIRM']
			)
			{
				$strError = Loc::getMessage["BX24_INVITE_DIALOG_WARNING_CREATE_MAILBOX_ERROR"]." ".GetMessage("BX24_INVITE_DIALOG_WARNING_MAILBOX_PASSWORD_CONFIRM");

				$this->addError(new \Bitrix\Main\Error($strError));
				return false;
			}
			else
			{
				require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/components/bitrix/intranet.mail.setup/helper.php");

				if (
					isset($_POST["ADD_MAILBOX_ACTION"])
					&& $_POST["ADD_MAILBOX_ACTION"] == "create"
				)
				{
					$arMailboxResult = CIntranetMailSetupHelper::createMailbox(
						false,
						false,
						$_POST['ADD_MAILBOX_SERVICE'],
						$_POST['ADD_MAILBOX_DOMAIN'], $_POST['ADD_MAILBOX_USER'],
						$_POST['ADD_MAILBOX_PASSWORD'],
						$strError
					);

					if ($strError)
					{
						$this->addError(new \Bitrix\Main\Error(Loc::getMessage("BX24_INVITE_DIALOG_WARNING_CREATE_MAILBOX_ERROR")." ".$strError));
						return false;
					}
				}
			}
		}

		$idAdded = CIntranetInviteDialog::AddNewUser(SITE_ID, $_POST, $strError);

		if ($idAdded)
		{
			// mailbox
			if (ModuleManager::isModuleInstalled("mail"))
			{
				if (
					isset($_POST["ADD_MAILBOX_ACTION"])
					&& in_array($_POST["ADD_MAILBOX_ACTION"], array('create', 'connect'))
				)
				{
					$arMailboxResult = CIntranetMailSetupHelper::createMailbox(
						true,
						$idAdded,
						$_POST['ADD_MAILBOX_SERVICE'],
						$_POST['ADD_MAILBOX_DOMAIN'], $_POST['ADD_MAILBOX_USER'],
						null,
						$strError
					);

					if ($strError)
					{
						CUser::Delete($idAdded);

						$this->addError(new \Bitrix\Main\Error(GetMessage("BX24_INVITE_DIALOG_WARNING_CREATE_MAILBOX_ERROR")." ".$strError));
						return false;
					}
					// update email?
				}
			}

			CIntranetInviteDialog::RequestToSonetGroups($idAdded, $_POST["SONET_GROUPS_CODE"], $_POST["SONET_GROUPS_NAME"], (intval($_POST["DEPARTMENT_ID"]) <= 0));
		}

		if (!empty($strError))
		{
			$this->addError(new \Bitrix\Main\Error($strError));
			return false;
		}

		CIntranetInviteDialog::logAction($idAdded, ($_POST["DEPARTMENT_ID"] > 0? 'intranet': 'extranet'), 'add_user', 'add_dialog');

		return Loc::getMessage("BX24_INVITE_DIALOG_ADDED", array("#SITE_DIR#" => SITE_DIR));
	}

	public function inviteByPhoneAction()
	{
		if (!$this->isInvitingUsersAllowed())
		{
			return false;
		}

		if (!$this->isMoreUserAvailable())
		{
			$this->addError(new \Bitrix\Main\Error("User limit"));
			return "user_limit";
		}

		if (
			intval($_POST["DEPARTMENT_ID"]) <= 0
			&& (
				!isset($_POST["SONET_GROUPS_CODE"])
				|| empty($_POST["SONET_GROUPS_CODE"])
			)
			&& $this->isExtranetInstalled()
		)
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("BX24_INVITE_DIALOG_ERROR_EXTRANET_NO_SONET_GROUP_INVITE")));
			return false;
		}

		$idInvited = CIntranetInviteDialog::RegisterNewUser(SITE_ID, $_POST, $arError);
		if(
			is_array($arError)
			&& count($arError) > 0
		)
		{
			$strError = "";

			foreach($arError as $strErrorText)
			{
				if($strErrorText <> '')
				{
					$strError .= '<li style="list-style-position: inside;">'.$strErrorText.'</li>';
				}
			}
		}

		if (!empty($strError))
		{
			$this->addError(new \Bitrix\Main\Error($strError));
			return false;
		}

		CIntranetInviteDialog::RequestToSonetGroups($idInvited, $_POST["SONET_GROUPS_CODE"], $_POST["SONET_GROUPS_NAME"], (intval($_POST["DEPARTMENT_ID"]) <= 0));
		CIntranetInviteDialog::logAction($idInvited, ($_POST["DEPARTMENT_ID"] > 0? 'intranet': 'extranet'), 'invite_user', 'sms_dialog');

		return Loc::getMessage("BX24_INVITE_DIALOG_INVITE_PHONE", array("#SITE_DIR#" => SITE_DIR));
	}

	public function inviteIntegratorAction()
	{
		global $USER;

		if (!Loader::includeModule("bitrix24"))
		{
			return false;
		}

		if (!$this->isInvitingUsersAllowed())
		{
			return false;
		}

		if (!check_email($_POST["integrator_email"]))
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("BX24_INVITE_DIALOG_EMAIL_ERROR")));
			return false;
		}

		if (!Integrator::isMoreIntegratorsAvailable())
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("BX24_INVITE_DIALOG_INTEGRATOR_COUNT_ERROR", array(
				"#LINK_START#" => "<a href=\"/company/?apply_filter=Y&INTEGRATOR=Y&FIRED=N\">",
				"#LINK_END#" => "</a>",
			))));
			return false;
		}

		$error = "";
		if (!Integrator::checkPartnerEmail($_POST["integrator_email"], $error))
		{
			$this->addError(new \Bitrix\Main\Error($error));
			return false;
		}

		if (isset($_POST["integrator_message_text"]))
		{
			$messageText = $_POST["integrator_message_text"];
			CUserOptions::SetOption("bitrix24", "integrator_message_text", $messageText);
		}
		else
		{
			$messageText = Loc::getMessage("BX24_INVITE_DIALOG_INTEGRATOR_INVITE_TEXT");
		}

		//$oldIntegratorId = \CBitrix24::getIntegratorId();

		$strError = "";
		$newIntegratorId = CIntranetInviteDialog::inviteIntegrator(SITE_ID, $_POST["integrator_email"], $messageText, $strError);

		if (!empty($strError))
		{
			$this->addError(new \Bitrix\Main\Error($strError));
			return false;
		}

		/*if ($oldIntegratorId && $newIntegratorId)
		{
			$USER->Update($oldIntegratorId, array("ACTIVE" => "N"));
		}*/

		CIntranetInviteDialog::logAction($newIntegratorId, 'intranet', 'invite_user', 'integrator_dialog');

		return Loc::getMessage("BX24_INVITE_DIALOG_INTEGRATOR", array("#SITE_DIR#" => SITE_DIR));
	}
}
