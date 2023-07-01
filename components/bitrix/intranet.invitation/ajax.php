<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Iblock;
use Bitrix\Intranet\Invitation\Register;
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
use Bitrix\Intranet;

class CIntranetInvitationComponentAjaxController extends \Bitrix\Main\Engine\Controller
{
	protected function isInvitingUsersAllowed(): bool
	{
		if (!Invitation::canCurrentUserInvite())
		{
			return false;
		}

		if (Loader::includeModule("bitrix24"))
		{
			if (!CBitrix24::isEmailConfirmed())
			{
				return false;
			}

			$licensePrefix = CBitrix24::getLicensePrefix();
			$licenseType = CBitrix24::getLicenseType();
			if (
				$licenseType === "project"
				&& in_array($licensePrefix, ['cn', 'en', 'vn', 'jp'])
			)
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

				if ((int)$res['CNT'] >= 10)
				{
					$this->addError(new \Bitrix\Main\Error(Loc::getMessage('INTRANET_INVITE_DIALOG_USER_COUNT_ERROR')));
					return false;
				}
			}
		}

		return true;
	}

	protected function getDefaultPreFilters()
	{
		return array_merge(
			parent::getDefaultPreFilters(),
			[ new \Bitrix\Intranet\ActionFilter\UserType(['employee']) ]
		);
	}

	public function configureActions()
	{
		return [
			'getSliderContent' => [
				'-prefilters' => [
					ActionFilter\Csrf::class,
				]
			],
		];
	}

	public function processAfterAction(\Bitrix\Main\Engine\Action $action, $result)
	{
		parent::processAfterAction($action, $result);

		if ($action->getName() === 'getSliderContent' && !$this->errorCollection->isEmpty())
		{
			$errorText = '';
			foreach ($this->errorCollection as $error)
			{
				/** @var Error $error */
				$errorText .= '<span style="color: red">' . $error->getMessage() . '</span><br/>';
			}

			return (new HttpResponse())->setContent($errorText);
		}

		return $result;
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
					'USER_OPTIONS' => $params['USER_OPTIONS'] ?? []
				],
				'IFRAME_MODE' => true
			]
		);

		$response = new HttpResponse();
		$response->setContent($content);

		return $response;
	}

	protected function isExtranetInstalled(): bool
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

	protected function isInvitationBySmsAvailable(): bool
	{
		return Loader::includeModule("bitrix24") && Option::get('bitrix24', 'phone_invite_allowed', 'N') === 'Y';
	}

	protected function isMoreUserAvailable(): bool
	{
		return !(Loader::includeModule("bitrix24") && !CBitrix24::isMoreUserAvailable());
	}

	protected function getHeadDepartmentId(): ?int
	{
		return Intranet\DepartmentStructure::getInstance(SITE_ID)->getBaseDepartmentId();
	}

	private function getCurrentUserDepartment(): array
	{
		$result = [];
		global $USER;
		if ($USER->isAuthorized())
		{
			$res = \CUser::getById($USER->getId());
			if ($user = $res->fetch())
			{
				if (!empty($user['UF_DEPARTMENT']))
				{
					if (is_array($user['UF_DEPARTMENT']))
					{
						$result = $user['UF_DEPARTMENT'];
					}
					elseif ((int)$user['UF_DEPARTMENT'] > 0)
					{
						$result = [(int)$user['UF_DEPARTMENT']];
					}
				}
			}
		}

		return $result;
	}

	private function filterDepartment(?array $departmentList): ?array
	{
		$result = null;

		if (empty($departmentList))
		{
			$result = null;
		}
		else if (Intranet\CurrentUser::get()->isAdmin())
		{
			$result = $departmentList;
		}
		elseif (Loader::includeModule('iblock'))
		{
			$result = null;
			if ($userDepartmentList = Intranet\CurrentUser::get()->getDepartmentIds())
			{
				$departmentAllList = Iblock\SectionTable::getList([
					'select' => ['ID', 'LEFT_MARGIN', 'RIGHT_MARGIN'],
					'filter' => [
						'=ID' => array_diff(
							array_merge($userDepartmentList, $departmentList),
							[$this->getHeadDepartmentId()]
						),
						'=ACTIVE' => 'Y',
					],
					'order' => ['LEFT_MARGIN' => 'ASC']
				])->fetchAll();

				$userDepartmentListExtended = array_filter($departmentAllList, function($dep) use ($userDepartmentList) {
						return in_array($dep['ID'], $userDepartmentList);
					})
				;
				$result = array_column(array_filter(
					$departmentAllList,
					function($checkedDepartment) use ($departmentList, $userDepartmentList, $userDepartmentListExtended)
					{
						$found = in_array($checkedDepartment['ID'], $departmentList) ?
							array_filter($userDepartmentListExtended, function ($userDepartment) use ($checkedDepartment) {
								return $userDepartment['LEFT_MARGIN'] <= $checkedDepartment['LEFT_MARGIN']
									&& $checkedDepartment['RIGHT_MARGIN'] <= $userDepartment['RIGHT_MARGIN'];
							})
							: [];
						return !empty($found);
					}),
					'ID'
				);
			}
		}

		return $result;
	}

	protected function prepareUsersForResponse($userIds): array
	{
		if (
			empty($userIds)
			|| !Loader::includeModule("socialnetwork")

		)
		{
			return [];
		}

		$userOptions = isset($_POST["userOptions"]) && is_array($_POST["userOptions"]) ? $_POST["userOptions"] : [];
		return EntitySelector\UserProvider::makeItems(EntitySelector\UserProvider::getUsers([
			'userId' => $userIds,
		]), $userOptions);
	}

	protected function prepareGroupIds($groups): array
	{
		$formattedGroups = [];
		foreach ($groups as $key => $id)
		{
			$formattedGroups[$key] = "SG".$id;
		}

		return $formattedGroups;
	}

	protected function registerNewUser($newUsers, &$strError): array
	{
		$arError = [];
		$invitedUserIds = Register::inviteNewUsers(SITE_ID, $newUsers, $arError);

		if (
			is_array($arError)
			&& count($arError) > 0
		)
		{
			foreach($arError as $strErrorText)
			{
				if ((string)$strErrorText !== '')
				{
					$strError .= $strErrorText . " ";
				}
			}
		}
		else
		{
			$isExtranet = !isset($newUsers["UF_DEPARTMENT"]);
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
		$departmentId = $this->filterDepartment($userData["UF_DEPARTMENT"]) ?: [$this->getHeadDepartmentId()];

		foreach ($userData["ITEMS"] as $key => $item)
		{
			$userData["ITEMS"][$key]["UF_DEPARTMENT"] = $departmentId;
		}

		$newUsers = [
			"ITEMS" => $userData["ITEMS"],
			"UF_DEPARTMENT" => $departmentId,
			"SONET_GROUPS_CODE" => $userData["SONET_GROUPS_CODE"] ?? []
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
		if (!$this->isExtranetInstalled())
		{
			return false;
		}

		if (!$this->isMoreUserAvailable())
		{
			$this->addError(new \Bitrix\Main\Error("User limit"));
			return "user_limit";
		}

		$userOptions = \Bitrix\Main\Context::getCurrent()->getRequest()->getPost('userOptions');

		if (
			(
				!isset($_POST["SONET_GROUPS_CODE"])
				|| empty($_POST["SONET_GROUPS_CODE"])
			)
			&& (
				!is_array($userOptions)
				|| !isset($userOptions['checkWorkgroupWhenInvite'])
				|| $userOptions['checkWorkgroupWhenInvite'] !== 'false'
			)
		)
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("BX24_INVITE_DIALOG_ERROR_EXTRANET_NO_SONET_GROUP_INVITE")));
			return false;
		}

		$strError = "";
		$userData = $_POST;

		$newUsers = [
			"ITEMS" => $userData["ITEMS"],
			"SONET_GROUPS_CODE" => $userData["SONET_GROUPS_CODE"] ?? []
		];

		foreach ($newUsers as $key => $item)
		{
			if (!empty($item['UF_DEPARTMENT']))
			{
				unset($newUsers[$key]);
			}
		}

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

		$isCurrentUserAdmin = Intranet\CurrentUser::get()->isAdmin();

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
		$userData["DEPARTMENT_ID"] = $this->filterDepartment($userData["UF_DEPARTMENT"] ?? null) ?: [$this->getHeadDepartmentId()];

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
		if (!Loader::includeModule("bitrix24"))
		{
			return false;
		}

		if (!$this->isInvitingUsersAllowed())
		{
			return false;
		}

		if (!check_email($_POST["integrator_email"] ?? ''))
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("BX24_INVITE_DIALOG_ERROR_EMAIL")));
			return false;
		}

		if (!Integrator::isMoreIntegratorsAvailable())
		{
			$this->addError(
				new \Bitrix\Main\Error(
					Loc::getMessage(
						"BX24_INVITE_DIALOG_INTEGRATOR_COUNT_ERROR",
						[
							"#LINK_START#" => "",
							"#LINK_END#" => "",
						]
					)
				)
			);
			return false;
		}

		$error = "";
		if (!Integrator::checkPartnerEmail($_POST["integrator_email"], $error))
		{
			$this->addError(new \Bitrix\Main\Error($error));
			return false;
		}

		$messageText = Loc::getMessage("BX24_INVITE_DIALOG_INTEGRATOR_INVITE_TEXT");

		$strError = "";
		$newIntegratorId = CIntranetInviteDialog::inviteIntegrator(SITE_ID, $_POST["integrator_email"], $messageText, $strError);

		if (!empty($strError))
		{
			$this->addError(new \Bitrix\Main\Error($strError));
			return false;
		}

		CIntranetInviteDialog::logAction($newIntegratorId, 'intranet', 'invite_user', 'integrator_dialog');

		return $this->prepareUsersForResponse([$newIntegratorId]);
	}
}
