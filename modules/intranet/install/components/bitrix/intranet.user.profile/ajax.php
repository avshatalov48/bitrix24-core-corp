<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Component\ParameterSigner;
use Bitrix\Bitrix24\Integrator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Event;

class CIntranetUserProfileComponentAjaxController extends \Bitrix\Main\Engine\Controller
{
	protected $userId;

	protected function processBeforeAction(\Bitrix\Main\Engine\Action $action)
	{
		parent::processBeforeAction($action);

		if (!$this->getRequest()->isPost() || !$this->getRequest()->getPost('signedParameters'))
		{
			return false;
		}

		$parameters = $this->getUnsignedParameters();

		if (isset($parameters['ID']))
		{
			$this->userId = $parameters['ID'];
		}
		else
		{
			return false;
		}

		return true;
	}

	protected function canEditProfile()
	{
		global $USER;

		if (Loader::includeModule("socialnetwork"))
		{
			$currentUserPerms = \CSocNetUserPerms::initUserPerms(
				$USER->GetID(),
				$this->userId,
				\CSocNetUser::isCurrentUserModuleAdmin(SITE_ID, false)
			);

			if (
				$currentUserPerms["IsCurrentUser"]
				|| (
					$currentUserPerms["Operations"]["modifyuser"]
					&& $currentUserPerms["Operations"]["modifyuser_main"]
				)
			)
			{
				return true;
			}
		}

		return false;
	}

	public function fireUserAction()
	{
		if (!$this->canEditProfile())
		{
			return false;
		}

		if (
			Loader::includeModule("bitrix24")
			&& !Bitrix\Bitrix24\Feature::isFeatureEnabled("user_dismissal")
			&& !Integrator::isIntegrator($this->userId)
		)
		{
			return false;
		}

		$user = new CUser;
		$res = $user->Update($this->userId, array("ACTIVE" => "N"));

		if (!$res)
		{
			$this->addError(new \Bitrix\Main\Error($user->LAST_ERROR));
			return false;
		}

		return true;
	}

	public function hireUserAction()
	{
		if (!$this->canEditProfile())
		{
			return false;
		}

		$user = new CUser;
		$res = $user->Update($this->userId, array("ACTIVE" => "Y"));

		if (!$res)
		{
			$this->addError(new \Bitrix\Main\Error($user->LAST_ERROR));
			return false;
		}

		return true;
	}

	public function deleteUserAction()
	{
		global $APPLICATION;

		if (!$this->canEditProfile())
		{
			return false;
		}

		$user = new CUser;
		$res = $user->Delete($this->userId);

		if (!$res)
		{
			$error = "";
			if (!empty($user->LAST_ERROR))
			{
				$error = $user->LAST_ERROR;
			}
			else
			{
				$ex = $APPLICATION->GetException();
				$error = $ex->GetString();
			}

			$this->addError(new \Bitrix\Main\Error($error));

			return false;
		}

		return true;
	}

	public function moveToIntranetAction($departmentId, $isEmail = false)
	{
		if (
			!(
				Loader::includeModule("bitrix24") && \CBitrix24::IsPortalAdmin(CurrentUser::get()->getId())
				|| CurrentUser::get()->isAdmin()
			)
		)
		{
			return false;
		}

		if (intval($departmentId) <= 0)
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("INTRANET_USER_PROFILE_EMPTY_DEPARTMENT_ERROR")));
			return false;
		}

		if ($isEmail == 'Y')
		{
			$ID_TRANSFERRED = CIntranetInviteDialog::TransferEmailUser($this->userId, array(
				'UF_DEPARTMENT' => (int) $departmentId
			));

			if (!$ID_TRANSFERRED)
			{
				if($e = $GLOBALS["APPLICATION"]->GetException())
				{
					$strError = $e->GetString();
					return array($strError);
				}
			}
			else
			{
				return $ID_TRANSFERRED;
			}
		}
		else
		{
			$obUser = new CUser;
			$arGroups = $obUser->GetUserGroup(intval($this->userId));
			$ID = 0;
			if (is_array($arGroups))
			{
				$arGroups = array_diff($arGroups, array(11, 13));
				$arGroups[] = "11";

				$arNewFields = array(
					"GROUP_ID" => $arGroups,
					"UF_DEPARTMENT" => array(intval($departmentId))
				);

				$ID = $obUser->Update($this->userId, $arNewFields);
			}
			if(!$ID)
			{
				$this->addError(new \Bitrix\Main\Error(preg_split("/<br>/", $obUser->LAST_ERROR)));
				return false;
			}
			else
			{
				if (\Bitrix\Main\Loader::includeModule("im"))
				{
					$arMessageFields = array(
						"TO_USER_ID" => $this->userId,
						"FROM_USER_ID" => 0,
						"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
						"NOTIFY_MODULE" => "bitrix24",
						"NOTIFY_MESSAGE" => Loc::getMessage("INTRANET_USER_PROFILE_MOVE_TO_INTRANET_NOTIFY"),
					);
					\CIMNotify::Add($arMessageFields);
				}

				\CIntranetEventHandlers::ClearAllUsersCache($this->userId);

				return Loc::getMessage("INTRANET_USER_PROFILE_MOVE_TO_INTRANET_SUCCESS");
			}
		}
	}

	public function loadPhotoAction()
	{
		if (!$this->canEditProfile())
		{
			return false;
		}

		$userData = \Bitrix\Main\UserTable::getList(array(
			"select" => array('ID', 'PERSONAL_PHOTO'),
			"filter" => array(
				"=ID" => $this->userId
			),
		))->fetch();

		$newPhotoFile = $this->getRequest()->getFile("newPhoto");
		if ($userData["PERSONAL_PHOTO"])
		{
			$newPhotoFile["old_file"] = $userData["PERSONAL_PHOTO"];
			$newPhotoFile["del"] = $userData["PERSONAL_PHOTO"];
		}

		$user = new CUser;
		$res = $user->Update($this->userId, array("PERSONAL_PHOTO" => $newPhotoFile));

		if (!$res)
		{
			$this->addError(new \Bitrix\Main\Error($user->LAST_ERROR));
			return false;
		}

		$newUserData = \Bitrix\Main\UserTable::getList(array(
			"select" => array('ID', 'PERSONAL_PHOTO'),
			"filter" => array(
				"=ID" => $this->userId
			),
		))->fetch();

		if ($newUserData["PERSONAL_PHOTO"] > 0)
		{
			$file = \CFile::GetFileArray($newUserData["PERSONAL_PHOTO"]);
			if ($file !== false)
			{
				$fileTmp = \CFile::ResizeImageGet(
					$file,
					array("width" => 212, "height" => 212),
					BX_RESIZE_IMAGE_PROPORTIONAL,
					false
				);

				return $fileTmp["src"];
			}
		}
	}

	public function deletePhotoAction()
	{
		if (!$this->canEditProfile())
		{
			return false;
		}

		$userData = \Bitrix\Main\UserTable::getList(array(
			"select" => array('ID', 'PERSONAL_PHOTO'),
			"filter" => array(
				"=ID" => $this->userId
			),
		))->fetch();

		if (!$userData["PERSONAL_PHOTO"])
		{
			return;
		}

		$fields = array(
			"PERSONAL_PHOTO" => array(
				"old_file" => $userData["PERSONAL_PHOTO"],
				"del" => $userData["PERSONAL_PHOTO"]
			)
		);

		$user = new CUser;
		$res = $user->Update($this->userId, $fields);

		if (!$res)
		{
			$this->addError(new \Bitrix\Main\Error($user->LAST_ERROR));
			return false;
		}
	}

	protected function getGroupsId(&$employeesGroupId, &$portalAdminGroupId)
	{
		[ $employeesGroupId, $portalAdminGroupId ] = \Bitrix\Intranet\Util::getGroupsId();
	}

	public function setAdminRightsAction()
	{
		$currentUser = CurrentUser::get();

		return \Bitrix\Intranet\Util::setAdminRights([
			'userId' => $this->userId,
			'currentUserId' => $currentUser->getId(),
			'isCurrentUserAdmin' => $currentUser->isAdmin()
		]);
	}

	public function removeAdminRightsAction()
	{
		$currentUser = CurrentUser::get();

		return \Bitrix\Intranet\Util::removeAdminRights([
			'userId' => $this->userId,
			'currentUserId' => $currentUser->getId(),
			'isCurrentUserAdmin' => $currentUser->isAdmin()
		]);
	}

	public function sendSmsForAppAction($phone = "")
	{
		if (!$this->canEditProfile())
		{
			return false;
		}

		if (empty($phone))
		{
			return false;
		}

		if (Loader::includeModule('socialservices'))
		{
			\Bitrix\Socialservices\Network::sendMobileApplicationLink($phone, LANGUAGE_ID);
		}

		return true;
	}

	public function setIntegratorRightsAction()
	{
		global $USER;

		if (!(Loader::includeModule("bitrix24") && \CBitrix24::IsPortalAdmin(CurrentUser::get()->getId())))
		{
			return false;
		}

		$userData = \Bitrix\Main\UserTable::getList(array(
			"select" => array('ID', 'EMAIL', 'UF_DEPARTMENT', 'ACTIVE'),
			"filter" => array(
				"=ID" => $this->userId
			),
		))->fetch();

		if (!check_email($userData["EMAIL"]))
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("INTRANET_USER_PROFILE_EMAIL_ERROR")));
			return false;
		}

		if (!Integrator::isMoreIntegratorsAvailable())
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("INTRANET_USER_PROFILE_INTEGRATOR_COUNT_ERROR")));
			return false;
		}

		$error = "";
		if (!Integrator::checkPartnerEmail($userData["EMAIL"], $error))
		{
			$this->addError(new \Bitrix\Main\Error($error));
			return false;
		}

		$fields = array("ACTIVE" => "Y");

		if (empty($userData["UF_DEPARTMENT"]) && Loader::includeModule('iblock'))
		{
			$rsIBlock = CIBlock::GetList(array(), array("CODE" => "departments"));
			$arIBlock = $rsIBlock->Fetch();
			$iblockID = $arIBlock["ID"];

			$db_up_department = CIBlockSection::GetList(
				array(),
				array(
					"SECTION_ID" => 0,
					"IBLOCK_ID" => $iblockID
				)
			);
			if ($ar_up_department = $db_up_department->Fetch())
			{
				$fields["UF_DEPARTMENT"][] = $ar_up_department['ID'];
			}
		}

		//prepare groups
		$arGroups = array(1);
		$rsGroups = CGroup::GetList(
			$o="",
			$b="",
			array(
				"STRING_ID" => "PORTAL_ADMINISTRATION_".SITE_ID
			)
		);
		while($arGroup = $rsGroups->Fetch())
		{
			$arGroups[] = $arGroup["ID"];
		}

		$integratorGroupId = \Bitrix\Bitrix24\Integrator::getIntegratorGroupId();
		$arGroups[] = $integratorGroupId;
		$fields["GROUP_ID"] = $arGroups;

		/*$oldIntegratorId = \CBitrix24::getIntegratorId();
		if ($oldIntegratorId)
		{
			$USER->Update($oldIntegratorId, array("ACTIVE" => "N"));
		}*/

		$USER->Update($this->userId, $fields);

		return true;
	}

	public function fieldsSettingsAction($fieldsView = array(), $fieldsEdit = array())
	{
		if (
			!(
				Loader::includeModule("bitrix24")
				&& \CBitrix24::IsPortalAdmin(CurrentUser::get()->getId())
				|| CurrentUser::get()->isAdmin()
			)
		)
		{
			return false;
		}

		$newFieldsView = array();

		if (is_array($fieldsView))
		{
			foreach ($fieldsView as $field)
			{
				$newFieldsView[] = $field["VALUE"];
			}
		}
		Option::set("intranet", "user_profile_view_fields", implode(",", $newFieldsView), SITE_ID);

		$newFieldsEdit = array();

		if (is_array($fieldsEdit))
		{
			foreach ($fieldsEdit as $field)
			{
				$newFieldsEdit[] = $field["VALUE"];
			}
		}
		Option::set("intranet", "user_profile_edit_fields", implode(",", $newFieldsEdit), SITE_ID);

		return true;
	}

	public function onUserFieldAddAction($fieldName = "")
	{
		if (!CurrentUser::get()->isAdmin())
		{
			return false;
		}

		if (empty($fieldName))
		{
			return false;
		}

		$viewFieldsSettings = Option::get("intranet", "user_profile_view_fields", false);
		if ($viewFieldsSettings !== false)
		{
			$viewFieldsSettings = explode(",", $viewFieldsSettings);
			$viewFieldsSettings[] = $fieldName;
			Option::set("intranet", "user_profile_view_fields", implode(",", $viewFieldsSettings), SITE_ID);
		}

		$editFieldsSettings = Option::get("intranet", "user_profile_edit_fields", false);
		if ($editFieldsSettings !== false)
		{
			$editFieldsSettings = explode(",", $editFieldsSettings);
			$editFieldsSettings[] = $fieldName;
			Option::set("intranet", "user_profile_edit_fields", implode(",", $editFieldsSettings), SITE_ID);
		}

		return true;
	}
}
