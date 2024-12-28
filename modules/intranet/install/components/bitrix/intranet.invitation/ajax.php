<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

require_once($_SERVER["DOCUMENT_ROOT"].$componentPath."/analytics.php");

use Bitrix\Iblock;
use Bitrix\Intranet\Invitation\Register;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Bitrix24\Integrator;
use Bitrix\Main\Config\Option;
use Bitrix\Socialnetwork\Collab\CollabFeature;
use Bitrix\Socialnetwork\Integration\UI\EntitySelector;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Intranet;

class CIntranetInvitationComponentAjaxController extends \Bitrix\Main\Engine\Controller
{
	private Analytics $analytics;

	protected function getDefaultPreFilters()
	{
		return array_merge(
			parent::getDefaultPreFilters(),
			[
				new Intranet\ActionFilter\UserType(['employee']),
				new Intranet\ActionFilter\InviteIntranetAccessControl(),
				new Intranet\ActionFilter\InviteLimitControl(),
			]
		);
	}

	public function configureActions()
	{
		return [
			'getSliderContent' => [
				'-prefilters' => [
					ActionFilter\Csrf::class,
					Intranet\ActionFilter\InviteIntranetAccessControl::class,
					Intranet\ActionFilter\InviteLimitControl::class,
				]
			],
			'extranet' => [
				'-prefilters' => [
					Intranet\ActionFilter\InviteIntranetAccessControl::class,
				],
				'+prefilters' => [
					new Intranet\ActionFilter\InviteExtranetAccessControl(
						$this->request->getPost('SONET_GROUPS_CODE')
					),
				],
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
				? Json::decode($componentParams)
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

	protected function getHeadDepartmentId(): ?int
	{
		return \Bitrix\Intranet\Service\ServiceContainer::getInstance()
			->departmentRepository()
			->getRootDepartment()
			?->getId();
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
				if (in_array($this->getHeadDepartmentId(), $userDepartmentList))
				{
					return $departmentList;
				}

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

	protected function registerNewUser($newUsers, $type, &$strError): array
	{
		$result = Register::inviteNewUsers(SITE_ID, $newUsers, $type);

		$invitedUserIds = $result->getData();
		if (!$result->isSuccess())
		{
			foreach($result->getErrors() as $error)
			{
				if ($error->getMessage() !== '')
				{
					$strError .= $error->getMessage() . " ";
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
		$departmentId = $this->getHeadDepartmentId();
		$strError = "";
		$items = $_POST["ITEMS"];
		$analyticEmails = 0;
		$analyticPhones = 0;

		foreach ($items as $key => $item)
		{
			$items[$key]["UF_DEPARTMENT"] = [$departmentId];
			if (isset($item['EMAIL']))
			{
				$analyticEmails++;
			}
			if (isset($item['PHONE']))
			{
				$analyticPhones++;
			}
		}

		$newUsers = [
			"ITEMS" => $items
		];

		$res = $this->registerNewUser($newUsers, 'email', $strError);

		if (!empty($strError))
		{
			$this->getAnalyticsInstance()->sendInvitation(
				0,
				Analytics::ANALYTIC_INVITATION_TYPE_C_SUB_SECTION_EMAIL,
				false
			);
			$this->addError(new \Bitrix\Main\Error($strError));
			return false;
		}

		foreach ($res as $obj)
		{
			$isEmail = true;

			if (empty($obj->getId()))
			{
				continue;
			}

			if (!isset($obj->getCustomData()['email']) || empty($obj->getCustomData()['email']))
			{
				$isEmail = false;
			}

			$this->getAnalyticsInstance()->sendInvitation(
				$obj->getId(),
				Analytics::ANALYTIC_INVITATION_TYPE_C_SUB_SECTION_EMAIL,
				true,
				$isEmail ? $analyticEmails : 0,
				$isEmail ? 0 : $analyticPhones
			);
		}

		return $res;
	}

	public function inviteWithGroupDpAction()
	{
		$userData = $_POST;
		$departmentId = $this->filterDepartment($userData["UF_DEPARTMENT"]) ?: [$this->getHeadDepartmentId()];
		$countEmails = 0;
		$countPhones = 0;
		foreach ($userData["ITEMS"] as $key => $item)
		{
			$userData["ITEMS"][$key]["UF_DEPARTMENT"] = $departmentId;
			if (isset($item['EMAIL']))
			{
				$countEmails++;
			}
			if (isset($item['PHONE']))
			{
				$countPhones++;
			}
		}

		$newUsers = [
			"ITEMS" => $userData["ITEMS"],
			"UF_DEPARTMENT" => $departmentId,
			"SONET_GROUPS_CODE" => $userData["SONET_GROUPS_CODE"] ?? []
		];

		$res = $this->registerNewUser($newUsers, 'group', $strError);

		foreach ($res as $obj)
		{
			$this->getAnalyticsInstance()->sendInvitation(
				$obj->getId(),
				Analytics::ANALYTIC_INVITATION_TYPE_C_SUB_SECTION_DEPARTMENT,
				true,
				$countEmails,
				$countPhones
			);
		}

		if (!empty($strError))
		{
			$this->getAnalyticsInstance()->sendInvitation(
				0,
				Analytics::ANALYTIC_INVITATION_TYPE_C_SUB_SECTION_DEPARTMENT,
				false
			);
			$this->addError(new \Bitrix\Main\Error($strError));

			return false;
		}

		return $res;
	}

	public function massInviteAction()
	{
		$strError = "";
		$errorFormatItems = [];
		$errorLengthItems = [];
		$newUsers = [];
		$departmentId = $this->getHeadDepartmentId();
		$isInvitationBySmsAvailable = $this->isInvitationBySmsAvailable();

		$data = preg_split("/[\n\r\t\\,;\\ ]+/", trim($_POST["ITEMS"]));
		$countEmails = 0;
		$countPhones = 0;
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
					$countEmails++;
				}
			}
			else if ($isInvitationBySmsAvailable && preg_match("/^[\d+][\d\(\)\ -]{4,22}\d$/", $item))
			{
				$newUsers["ITEMS"][] = [
					"PHONE" => $item,
					"PHONE_COUNTRY" => "",
					"UF_DEPARTMENT" => [$departmentId]
				];
				$countPhones++;
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
			$res = $this->registerNewUser($newUsers, 'mass', $strError);

			foreach ($res as $obj)
			{
				$this->getAnalyticsInstance()->sendInvitation(
					$obj->getId(),
					Analytics::ANALYTIC_INVITATION_TYPE_C_SUB_SECTION_MASS,
					true,
					$countEmails,
					$countPhones
				);
			}
		}

		if (!empty($strError))
		{
			$this->getAnalyticsInstance()->sendInvitation(
				0,
				Analytics::ANALYTIC_INVITATION_TYPE_C_SUB_SECTION_MASS,
				false
			);
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

		$res = $this->registerNewUser($newUsers, 'extranet', $strError);

		if (!empty($strError))
		{
			$this->addError(new \Bitrix\Main\Error($strError));
			return false;
		}

		return $res;
	}

	public function selfAction()
	{
		$this->getAnalyticsInstance()->sendRegistration(
			0,
			Analytics::ANALYTIC_CATEGORY_SETTINGS,
			Analytics::ANALYTIC_EVENT_CHANGE_QUICK_REG,
			Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getPost('allow_register')
		);

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
		$userData = $_POST;
		$userData["DEPARTMENT_ID"] = $this->filterDepartment($userData["UF_DEPARTMENT"] ?? null) ?: [$this->getHeadDepartmentId()];

		$idAdded = CIntranetInviteDialog::AddNewUser(SITE_ID, $userData, $strError, 'register');

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
			$this->getAnalyticsInstance()->sendRegistration(0, status: 'N', userData: $userData);
			return false;
		}

		$this->getAnalyticsInstance()->sendRegistration($idAdded, status: 'Y', userData: $userData);

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

		$error = new \Bitrix\Main\Error('');
		if (!Integrator::checkPartnerEmail($_POST["integrator_email"], $error))
		{
			$this->addError($error);

			return false;
		}

		$messageText = Loc::getMessage("BX24_INVITE_DIALOG_INTEGRATOR_INVITE_TEXT");

		$strError = "";
		$newIntegratorId = CIntranetInviteDialog::inviteIntegrator(SITE_ID, $_POST["integrator_email"], $messageText, $strError);

		if (!empty($strError))
		{
			$this->getAnalyticsInstance()->sendInvitation(
				0,
				Analytics::ANALYTIC_INVITATION_TYPE_C_SUB_SECTION_INTEGRATOR,
				false
			);
			$this->addError(new \Bitrix\Main\Error($strError));

			return false;
		}

		if ($newIntegratorId > 0)
		{
			$this->getAnalyticsInstance()->sendInvitation(
				$newIntegratorId,
				Analytics::ANALYTIC_INVITATION_TYPE_C_SUB_SECTION_INTEGRATOR,
			true,
				1
			);
		}

		CIntranetInviteDialog::logAction($newIntegratorId, 'intranet', 'invite_user', 'integrator_dialog');

		return $this->prepareUsersForResponse([$newIntegratorId]);
	}

	private function getAnalyticsInstance(): Analytics
	{
		if (!isset($this->analytics))
		{
			$this->analytics = new Analytics();
		}

		return $this->analytics;
	}
}
