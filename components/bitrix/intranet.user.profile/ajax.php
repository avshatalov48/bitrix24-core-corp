<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die;

use Bitrix\Intranet\Invitation;
use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Intranet\User;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Bitrix24\Integrator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Response\Component;
use Bitrix\UI;

class CIntranetUserProfileComponentAjaxController extends \Bitrix\Main\Engine\Controller
{
	protected $userId;

	protected function processBeforeAction(\Bitrix\Main\Engine\Action $action)
	{
		parent::processBeforeAction($action);

		if ($action->getName() === 'showWidget')
		{
			return true;
		}

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
				\CSocNetUser::isCurrentUserModuleAdmin(SITE_ID, false),
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

	/**
	 * @deprecated use \Bitrix\Intranet\Infrastructure\Controller\User::fireAction
	 */
	public function fireUserAction()
	{
		$currentUser = CurrentUser::get();

		$result = \Bitrix\Intranet\Util::deactivateUser([
			'userId' => $this->userId,
			'currentUserId' => $currentUser->getId(),
			'isCurrentUserAdmin' => $currentUser->isAdmin(),
		]);

		if ($result && $this->userId > 0)
		{
			$deactivateUser = new User($this->userId);
			Invitation::fullSyncCounterByUser($deactivateUser->fetchOriginatorUser());
		}

		return $result;
	}

	/**
	 * @deprecated use \Bitrix\Intranet\Infrastructure\Controller\User::restoreAction
	 */
	public function hireUserAction()
	{
		$currentUser = CurrentUser::get();

		return \Bitrix\Intranet\Util::activateUser([
			'userId' => $this->userId,
			'currentUserId' => $currentUser->getId(),
			'isCurrentUserAdmin' => $currentUser->isAdmin(),
		]);
	}

	public function confirmNotifyUserAction($userId, $isAccept): bool
	{
		return Invitation::confirmUserRequest((int)$userId, $isAccept === 'Y')->isSuccess();
	}

	/**
	 * @deprecated use \Bitrix\Intranet\Infrastructure\Controller\User::deleteAction
	 */
	public function deleteUserAction()
	{
		global $APPLICATION;

		if (!$this->canEditProfile())
		{
			return false;
		}

		$userData = \Bitrix\Main\UserTable::query()
			->setSelect(['ID', 'CONFIRM_CODE', 'ACTIVE'])
			->addFilter('ID', $this->userId)
			->fetch();

		if (empty($userData['CONFIRM_CODE']) || $userData['ACTIVE'] !== 'Y')
		{
			$this->addError(new \Bitrix\Main\Error(GetMessage('INTRANET_USER_PROFILE_DELETE_ERROR')));

			return false;
		}

		$user = new CUser();
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
				$error = ($ex instanceof CApplicationException)
					? $ex->GetString() : GetMessage('INTRANET_USER_PROFILE_DELETE_ERROR');
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
			$ID_TRANSFERRED = CIntranetInviteDialog::TransferEmailUser($this->userId, [
				'UF_DEPARTMENT' => (int)$departmentId,
			]);

			if (!$ID_TRANSFERRED)
			{
				if($e = $GLOBALS["APPLICATION"]->GetException())
				{
					$strError = $e->GetString();

					return [$strError];
				}
			}
			else
			{
				return $ID_TRANSFERRED;
			}
		}
		else
		{
			$isCollaber = Loader::includeModule('extranet')
				&& \Bitrix\Extranet\Service\ServiceContainer::getInstance()->getCollaberService()->isCollaberById($this->userId);
			$obUser = new CUser();
			$arGroups = $obUser->GetUserGroup(intval($this->userId));
			$ID = 0;
			if (is_array($arGroups))
			{
				$arGroups = array_diff($arGroups, [11, 13]);
				$arGroups[] = "11";

				$arNewFields = [
					"GROUP_ID" => $arGroups,
					"UF_DEPARTMENT" => [intval($departmentId)],
				];

				$ID = $obUser->Update($this->userId, $arNewFields);
			}
			if(!$ID)
			{
				$this->addError(new \Bitrix\Main\Error(preg_split("/<br>/", $obUser->LAST_ERROR)));

				return false;
			}
			else
			{
				if (Loader::includeModule("im"))
				{
					$arMessageFields = [
						"TO_USER_ID" => $this->userId,
						"FROM_USER_ID" => 0,
						"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
						"NOTIFY_MODULE" => "bitrix24",
						"NOTIFY_MESSAGE" => $isCollaber
							? Loc::getMessage("INTRANET_USER_PROFILE_MOVE_COLLAB_TO_INTRANET_NOTIFY_MSGVER_1")
							: Loc::getMessage("INTRANET_USER_PROFILE_MOVE_TO_INTRANET_NOTIFY"),
					];
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

		$userData = \Bitrix\Main\UserTable::getList([
			'select' => ['ID', 'PERSONAL_PHOTO'],
			'filter' => [
				'=ID' => $this->userId,
			],
		])->fetch();

		$user = new CUser();
		$newPhotoFile = $this->getRequest()->getFile('newPhoto');

		//region delete after ui 22.1200.0
		if (class_exists(UI\Avatar\Mask\Helper::class)
			&& ($maskInfo = UI\Avatar\Mask\Helper::getDataFromRequest('newPhoto', $this->getRequest()))
		)
		{
			[$newPhotoFile, $newPhotoMaskFile] = $maskInfo;
		}
		//endregion

		if (isset($userData['PERSONAL_PHOTO']) && $userData['PERSONAL_PHOTO'] > 0)
		{
			$newPhotoFile['old_file'] = $userData['PERSONAL_PHOTO'];
			$newPhotoFile['del'] = $userData['PERSONAL_PHOTO'];
		}

		$res = $user->Update($this->userId, ['PERSONAL_PHOTO' => $newPhotoFile]);

		if (!$res)
		{
			$this->addError(new \Bitrix\Main\Error($user->LAST_ERROR));

			return false;
		}

		if (Loader::includeModule('intranet'))
		{
			\Bitrix\Intranet\Composite\CacheProvider::deleteUserCache();
		}

		$newUserData = \Bitrix\Main\UserTable::getList([
			'select' => ['ID', 'PERSONAL_PHOTO'],
			'filter' => [
				'=ID' => $this->userId,
			],
		])->fetch();


		if (
			//region TODO: delete after ui 22.1200.0
			!isset($newPhotoMaskFile)
			&& class_exists(UI\Avatar\Mask\Helper::class)
			&& method_exists(UI\Avatar\Mask\Helper::class, 'getMaskedFile')
			//endregion
			&& ($newPhotoMaskFile = UI\Avatar\Mask\Helper::getMaskedFile('newPhoto'))
		)
		{
			UI\Avatar\Mask\Helper::save($newUserData["PERSONAL_PHOTO"], $newPhotoMaskFile);
		}

		if ($newUserData['PERSONAL_PHOTO'] > 0)
		{
			$file = \CFile::GetFileArray($newUserData['PERSONAL_PHOTO']);
			if ($file !== false)
			{
				$fileTmp = \CFile::ResizeImageGet(
					$file,
					['width' => 512, 'height' => 512],
					BX_RESIZE_IMAGE_PROPORTIONAL,
					false,
					false,
					true,
				);

				return $fileTmp['src'];
			}
		}
	}

	public function deletePhotoAction()
	{
		if (!$this->canEditProfile())
		{
			return false;
		}

		$userData = \Bitrix\Main\UserTable::getList([
			"select" => ['ID', 'PERSONAL_PHOTO'],
			"filter" => [
				"=ID" => $this->userId,
			],
		])->fetch();

		if (!$userData["PERSONAL_PHOTO"])
		{
			return;
		}

		$fields = [
			"PERSONAL_PHOTO" => [
				"old_file" => $userData["PERSONAL_PHOTO"],
				"del" => $userData["PERSONAL_PHOTO"],
			],
		];

		$user = new CUser();
		$res = $user->Update($this->userId, $fields);

		if (!$res)
		{
			$this->addError(new \Bitrix\Main\Error($user->LAST_ERROR));

			return false;
		}
	}

	protected function getGroupsId(&$employeesGroupId, &$portalAdminGroupId)
	{
		[$employeesGroupId, $portalAdminGroupId] = \Bitrix\Intranet\Util::getGroupsId();
	}

	public function setAdminRightsAction()
	{
		$currentUser = CurrentUser::get();

		return \Bitrix\Intranet\Util::setAdminRights([
			'userId' => $this->userId,
			'currentUserId' => $currentUser->getId(),
			'isCurrentUserAdmin' => $currentUser->isAdmin(),
		]);
	}

	public function removeAdminRightsAction()
	{
		$currentUser = CurrentUser::get();

		return \Bitrix\Intranet\Util::removeAdminRights([
			'userId' => $this->userId,
			'currentUserId' => $currentUser->getId(),
			'isCurrentUserAdmin' => $currentUser->isAdmin(),
		]);
	}

	public function sendSmsForAppAction($phone = "")
	{
		return false;
	}

	public function setIntegratorRightsAction()
	{
		global $USER;

		if (!(Loader::includeModule("bitrix24") && \CBitrix24::IsPortalAdmin(CurrentUser::get()->getId())))
		{
			return false;
		}

		$userData = \Bitrix\Main\UserTable::getList([
			"select" => ['ID', 'EMAIL', 'UF_DEPARTMENT', 'ACTIVE'],
			"filter" => [
				"=ID" => $this->userId,
			],
		])->fetch();

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

		$error = new \Bitrix\Main\Error('');
		if (!Integrator::checkPartnerEmail($userData["EMAIL"], $error))
		{
			$this->addError($error);

			return false;
		}

		$fields = ["ACTIVE" => "Y"];

		if (empty($userData["UF_DEPARTMENT"]))
		{
			$departmentRepository = \Bitrix\Intranet\Service\ServiceContainer::getInstance()
				->departmentRepository()
			;
			if ($department = $departmentRepository->getRootDepartment())
			{
				$fields["UF_DEPARTMENT"][] = $department->getId();
			}
		}

		//prepare groups
		$arGroups = [1];
		$groupId = CGroup::GetIDByCode("PORTAL_ADMINISTRATION_" . SITE_ID);
		if ($groupId)
		{
			$arGroups[] = $groupId;
		}

		$integratorGroupId = \Bitrix\Bitrix24\Integrator::getIntegratorGroupId();
		$arGroups[] = $integratorGroupId;
		$fields["GROUP_ID"] = $arGroups;

		$USER->Update($this->userId, $fields);

		ServiceContainer::getInstance()->getUserService()->clearCache();

		return true;
	}

	public function fieldsSettingsAction($fieldsView = [], $fieldsEdit = [])
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

		$newFieldsView = [];

		if (is_array($fieldsView))
		{
			foreach ($fieldsView as $field)
			{
				$newFieldsView[] = $field["VALUE"];
			}
		}
		Option::set("intranet", "user_profile_view_fields", implode(",", $newFieldsView), SITE_ID);

		$newFieldsEdit = [];

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

	public function showWidgetAction(string $targetId, string $siteTemplateId, array $urls): Component
	{
		return new Component(
			'bitrix:intranet.user.profile',
			'widget',
			[
				'ID' => CurrentUser::get()->getId(),
				'TARGET_ID' => $targetId,
				'SITE_TEMPLATE_ID' => $siteTemplateId,
				'PATH_TO_USER_PROFILE' => $urls['PATH_TO_USER_PROFILE'] ?? '',
				'PATH_TO_USER_STRESSLEVEL' => $urls['PATH_TO_USER_STRESSLEVEL'] ?? '',
				'PATH_TO_USER_COMMON_SECURITY' => $urls['PATH_TO_USER_COMMON_SECURITY'] ?? '',
			],
		);
	}

}
