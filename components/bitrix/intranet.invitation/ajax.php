<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Bitrix24\Integrator;
use Bitrix\Intranet\Invitation;
use Bitrix\Main\Config\Option;
use Bitrix\Socialnetwork\Integration\UI\EntitySelector;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Intranet\Internals\InvitationTable;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Entity;

class CIntranetInvitationComponentAjaxController extends \Bitrix\Main\Engine\Controller
{
	protected function isInvitingUsersAllowed()
	{
		if (!Invitation::canCurrentUserInvite())
		{
			return false;
		}

		if (Loader::includeModule("bitrix24"))
		{
			if (!\CBitrix24::isEmailConfirmed())
			{
				return false;
			}

			$licensePrefix = \CBitrix24::getLicensePrefix();
			$licenseType = \CBitrix24::getLicenseType();
			if ($licensePrefix === "cn" && $licenseType === "project")
			{
				$res = InvitationTable::getList([
					'filter' => [
						'>=DATE_CREATE' => new Date
					],
					'select' => ['CNT'],
					'runtime' => array(
						new Entity\ExpressionField('CNT', 'COUNT(*)')
					)
				])->fetch();

				if ((int)$res['CNT'] >= 5)
				{
					return false;
				}
			}
		}

		return true;
	}

	public function configureActions()
	{
		return [
			'getSliderContent' => [
				'-prefilters' => [
					ActionFilter\Csrf::class,
				],
			],
		];
	}

	public function getSliderContentAction(string $componentParams = '')
	{
		$params =
			$componentParams
				? Json::decode(Encoding::convertEncoding($componentParams, SITE_CHARSET, 'UTF-8'))
				: []
		;

		$content = $GLOBALS['APPLICATION']->includeComponent(
			'bitrix:ui.sidepanel.wrapper',
			'',
			[
				'RETURN_CONTENT' => true,
				'POPUP_COMPONENT_NAME' => 'bitrix:intranet.invitation',
				'POPUP_COMPONENT_TEMPLATE_NAME' => '',
				'POPUP_COMPONENT_PARAMS' => [
					'USER_OPTIONS' => isset($params['USER_OPTIONS']) ? $params['USER_OPTIONS'] : []
				],
				'IFRAME_MODE' => true
			]
		);

		$response = new HttpResponse();
		$response->setContent($content);

		return $response;
	}

	protected function isExtranetInstalled()
	{
		$bExtranetInstalled = ModuleManager::IsModuleInstalled("extranet");
		if ($bExtranetInstalled)
		{
			$extranetSiteId = Option::get("extranet", "extranet_site");
			if (empty($extranetSiteId))
			{
				$bExtranetInstalled = false;
			}
		}

		return $bExtranetInstalled;
	}

	protected function isInvitationBySmsAvailable()
	{
		return Loader::includeModule("bitrix24") && Option::get('bitrix24', 'phone_invite_allowed', 'N') === 'Y';
	}

	protected function isMoreUserAvailable()
	{
		if (Loader::includeModule("bitrix24") && !CBitrix24::isMoreUserAvailable())
		{
			return false;
		}

		return true;
	}

	protected function getHeadDepartmentId()
	{
		if (Loader::includeModule('iblock'))
		{
			$rsIBlock = CIBlock::GetList(array(), array("CODE" => "departments"));
			$arIBlock = $rsIBlock->Fetch();
			$iblockID = $arIBlock["ID"];

			$dbUpDepartment = CIBlockSection::GetList(
				array(),
				array(
					"SECTION_ID" => 0,
					"IBLOCK_ID" => $iblockID
				)
			);
			if ($upDepartment = $dbUpDepartment->Fetch())
			{
				return $upDepartment['ID'];
			}
		}

		return false;
	}

	protected function prepareUsersForResponse($userIds)
	{
		if (!Loader::includeModule("socialnetwork"))
		{
			return [];
		}

		$userOptions = isset($_POST["userOptions"]) && is_array($_POST["userOptions"]) ? $_POST["userOptions"] : [];
		$users = EntitySelector\UserProvider::makeItems(EntitySelector\UserProvider::getUsers(['userId' => $userIds]), $userOptions);
		return $users;
	}

	protected function prepareGroupIds($groups)
	{
		$formattedGroups = [];
		foreach ($groups as $key => $id)
		{
			$formattedGroups[$key] = "SG".$id;
		}

		return $formattedGroups;
	}

	protected function registerNewUser($newUsers, &$strError)
	{
		$arError = [];
		$invitedUserIds = \Bitrix\Intranet\Invitation\Register::inviteNewUsers(SITE_ID, $newUsers, $arError);

		if(
			is_array($arError)
			&& count($arError) > 0
		)
		{
			foreach($arError as $strErrorText)
			{
				if(strlen($strErrorText) > 0)
				{
					$strError .= $strErrorText." ";
				}
			}
		}
		else
		{
			$isExtranet = isset($newUsers["UF_DEPARTMENT"]) ? false : true;
			if (isset($newUsers["SONET_GROUPS_CODE"]) && is_array($newUsers["SONET_GROUPS_CODE"]))
			{
				CIntranetInviteDialog::RequestToSonetGroups(
					$invitedUserIds,
					$this->prepareGroupIds($newUsers["SONET_GROUPS_CODE"]),
					"",
					$isExtranet
				);
			}

			$type = $isExtranet ? 'extranet' : 'intranet';
			CIntranetInviteDialog::logAction($invitedUserIds, $type, 'invite_user', 'invite_dialog');
		}

		return $this->prepareUsersForResponse($invitedUserIds);
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

		$departmentId = $this->getHeadDepartmentId();
		$strError = "";
		$items = $_POST["ITEMS"];

		foreach ($items as $key => $item)
		{
			$items[$key]["UF_DEPARTMENT"] = [$departmentId];
		}

		$newUsers = [
			"ITEMS" => $items
		];

		$res = $this->registerNewUser($newUsers, $strError);

		if (!empty($strError))
		{
			$this->addError(new \Bitrix\Main\Error($strError));
			return false;
		}

		return $res;
	}

	public function inviteWithGroupDpAction()
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

		$userData = $_POST;

		if (!isset($userData["UF_DEPARTMENT"]) || empty($userData["UF_DEPARTMENT"]))
		{
			$departmentId = [$this->getHeadDepartmentId()];
		}
		else
		{
			$departmentId = $userData["UF_DEPARTMENT"];
		}

		foreach ($userData["ITEMS"] as $key => $item)
		{
			$userData["ITEMS"][$key]["UF_DEPARTMENT"] = $departmentId;
		}

		$newUsers = [
			"ITEMS" => $userData["ITEMS"],
			"UF_DEPARTMENT" => $departmentId,
			"SONET_GROUPS_CODE" => isset($userData["SONET_GROUPS_CODE"]) ? $userData["SONET_GROUPS_CODE"] : []
		];

		$res = $this->registerNewUser($newUsers, $strError);

		if (!empty($strError))
		{
			$this->addError(new \Bitrix\Main\Error($strError));
			return false;
		}

		return $res;
	}

	public function massInviteAction()
	{
		if (!$this->isInvitingUsersAllowed())
		{
			return false;
		}

		$strError = "";
		$errorFormatItems = [];
		$errorLengthItems = [];
		$newUsers = [];
		$departmentId = $this->getHeadDepartmentId();
		$isInvitationBySmsAvailable = $this->isInvitationBySmsAvailable();

		$data = preg_split("/[\n\r\t\\,;\\ ]+/", trim($_POST["ITEMS"]));

		foreach ($data as $item)
		{
			if (check_email($item))
			{
				if (mb_strlen($item) > 50)
				{
					$errorLengthItems[] = $item;
				}
				else
				{
					$newUsers["ITEMS"][] = [
						"EMAIL" => $item,
						"UF_DEPARTMENT" => [$departmentId]
					];
				}
			}
			else if ($isInvitationBySmsAvailable && preg_match("/^[\d+][\d\(\)\ -]{4,22}\d$/", $item))
			{
				$newUsers["ITEMS"][] = [
					"PHONE" => $item,
					"PHONE_COUNTRY" => "",
					"UF_DEPARTMENT" => [$departmentId]
				];
			}
			else
			{
				$errorFormatItems[] = $item;
			}
		}

		if (!empty($errorFormatItems))
		{
			$strError = Loc::getMessage("BX24_INVITE_DIALOG_ERROR_"
										.($this->isInvitationBySmsAvailable() ? "EMAIL_OR_PHONE" : "EMAIL"));
			$strError.= ": ".implode(", ", $errorFormatItems);
			$this->addError(new \Bitrix\Main\Error($strError));
			return false;
		}

		if (!empty($errorLengthItems))
		{
			$strError = Loc::getMessage("INTRANET_INVITE_DIALOG_ERROR_LENGTH");
			$strError.= ": ".implode(", ", $errorLengthItems);
			$this->addError(new \Bitrix\Main\Error($strError));
			return false;
		}

		if (!empty($newUsers))
		{
			$res = $this->registerNewUser($newUsers, $strError);
		}

		if (!empty($strError))
		{
			$this->addError(new \Bitrix\Main\Error($strError));
			return false;
		}

		return $res;
	}

	public function extranetAction()
	{
		if (!$this->isInvitingUsersAllowed() || !$this->isExtranetInstalled())
		{
			return false;
		}

		if (!$this->isMoreUserAvailable())
		{
			$this->addError(new \Bitrix\Main\Error("User limit"));
			return "user_limit";
		}

		if (
			!isset($_POST["SONET_GROUPS_CODE"])
			|| empty($_POST["SONET_GROUPS_CODE"])
		)
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("BX24_INVITE_DIALOG_ERROR_EXTRANET_NO_SONET_GROUP_INVITE")));
			return false;
		}

		$strError = "";
		$userData = $_POST;

		$newUsers = [
			"ITEMS" => $userData["ITEMS"],
			"SONET_GROUPS_CODE" => isset($userData["SONET_GROUPS_CODE"]) ? $userData["SONET_GROUPS_CODE"] : []
		];

		$res = $this->registerNewUser($newUsers, $strError);

		if (!empty($strError))
		{
			$this->addError(new \Bitrix\Main\Error($strError));
			return false;
		}

		return $res;
	}

	public function selfAction()
	{
		if (!$this->isInvitingUsersAllowed())
		{
			return false;
		}

		$isCurrentUserAdmin = (
				Loader::includeModule("bitrix24") && \CBitrix24::IsPortalAdmin(\Bitrix\Main\Engine\CurrentUser::get()->getId())
				|| \Bitrix\Main\Engine\CurrentUser::get()->isAdmin()
			)
			? true : false;

		if (Loader::includeModule("socialservices"))
		{
			$settings = [
				"REGISTER" => $_POST["allow_register"],
				"REGISTER_SECRET" => $_POST["allow_register_secret"]
			];

			if ($isCurrentUserAdmin)
			{
				$settings["REGISTER_CONFIRM"] = $_POST["allow_register_confirm"];
				$settings["REGISTER_WHITELIST"] = $_POST["allow_register_whitelist"];
			}

			\Bitrix\Socialservices\Network::setRegisterSettings($settings);
		}

		return Loc::getMessage("BX24_INVITE_DIALOG_SELF_SUCCESS", array("#SITE_DIR#" => SITE_DIR));
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

		$userData = $_POST;

		if (empty($userData["UF_DEPARTMENT"]))
		{
			$departmentId = $this->getHeadDepartmentId();
			$userData["DEPARTMENT_ID"] = [$departmentId];
		}
		else
		{
			$userData["DEPARTMENT_ID"] = $userData["UF_DEPARTMENT"];
		}

		$idAdded = CIntranetInviteDialog::AddNewUser(SITE_ID, $userData, $strError);

		if ($idAdded && isset($_POST["SONET_GROUPS_CODE"]) && is_array($_POST["SONET_GROUPS_CODE"]))
		{
			CIntranetInviteDialog::RequestToSonetGroups(
				$idAdded,
				$this->prepareGroupIds($_POST["SONET_GROUPS_CODE"]),
				""
			);
		}

		if (!empty($strError))
		{
			$strError = str_replace("<br>", " ", $strError);
			$this->addError(new \Bitrix\Main\Error($strError));
			return false;
		}

		$res = $this->prepareUsersForResponse([$idAdded]);

		CIntranetInviteDialog::logAction($idAdded, 'intranet', 'add_user', 'add_dialog');

		return $res;
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
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("BX24_INVITE_DIALOG_ERROR_EMAIL")));
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

		$messageText = Loc::getMessage("BX24_INVITE_DIALOG_INTEGRATOR_INVITE_TEXT");

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

		$res = $this->prepareUsersForResponse([$newIntegratorId]);

		return $res;
	}
}
