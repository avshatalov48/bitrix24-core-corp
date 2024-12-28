<?php

namespace Bitrix\Intranet\Component;

use Bitrix\HumanResources\Compatibility\Utils\DepartmentBackwardAccessCode;
use Bitrix\HumanResources\Item\NodeMember;
use Bitrix\HumanResources\Service\Container;
use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Intranet\Util;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use Bitrix\Socialnetwork\ComponentHelper;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\UI;

class UserProfile extends \CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{
	private const STATUS_INVITED = 'invited';
	public const ON_PROFILE_CONFIG_ADDITIONAL_BLOCKS = 'onProfileConfigAdditionalBlocks';

	/** @var ErrorCollection errorCollection */
	protected $errorCollection;
	protected $form;
	protected $grats;
	protected $tags;
	protected $profilePost;
	protected $stressLevel;
	private ?array $userData;

	public function __construct($component = null)
	{
		parent::__construct($component);
	}

	public function getUserId()
	{
		return isset($this->arParams['ID']) ? (int)$this->arParams['ID'] : 0;
	}

	protected function listKeysSignedParameters()
	{
		$result = [
			'ID', 'PATH_TO_USER', 'PATH_TO_POST', 'GRAT_POST_LIST_PAGE_SIZE', 'PATH_TO_USER_STRESSLEVEL'
		];

		if (Option::get("intranet", "user_profile_view_fields", false, SITE_ID) === false)
		{
			$result = array_merge($result, [ 'USER_FIELDS_MAIN', 'USER_PROPERTY_MAIN', 'USER_FIELDS_CONTACT', 'USER_PROPERTY_CONTACT', 'USER_FIELDS_PERSONAL', 'USER_PROPERTY_PERSONAL' ]);
		}

		if (Option::get("intranet", "user_profile_edit_fields", false, SITE_ID) === false)
		{
			$result = array_merge($result, [ 'EDITABLE_FIELDS' ]);
		}

		return $result;
	}

	public function configureActions()
	{
		return [
			'getTagsList' => [
				'+prefilters' => [
					new CloseSession()
				]
			],
			'getGratitudePostList' => [
				'+prefilters' => [
					new CloseSession()
				]
			],
			'getProfileBlogPost' => [
				'+prefilters' => [
					new CloseSession()
				]
			],
			'getTagData' => [
				'prefilters' => [
					new \Bitrix\Intranet\ActionFilter\UserType(['employee']),
				]
			]
		];
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	protected function getFormInstance()
	{
		if ($this->form === null)
		{
			$this->form = new \Bitrix\Intranet\Component\UserProfile\Form($this->getUserId());
		}
		return $this->form;
	}

	protected function getGratsInstance()
	{
		if ($this->grats === null)
		{
			$this->grats = new \Bitrix\Intranet\Component\UserProfile\Grats([
				'profileId' => $this->arParams['ID'],
				'pathToPostEdit' => $this->arParams['PATH_TO_POST_EDIT_GRAT'],
				'pathToUserGrat' => $this->arParams['PATH_TO_USER_GRAT'],
				'pathToPost' => $this->arParams['PATH_TO_POST'],
				'pathToUser' => $this->arParams['PATH_TO_USER'],
				'pageSize' => $this->arParams['GRAT_POST_LIST_PAGE_SIZE'],
			]);
		}
		return $this->grats;
	}

	protected function getProfilePostInstance()
	{
		if ($this->profilePost === null)
		{
			$this->profilePost = new \Bitrix\Intranet\Component\UserProfile\ProfilePost([
				'profileId' => $this->arParams['ID'],
				'pathToPostEdit' => $this->arParams['PATH_TO_POST_EDIT_PROFILE'],
				'pathToUser' => $this->arParams['PATH_TO_USER'],
				'permissions' => $this->getPermissions(),
			]);
		}
		return $this->profilePost;
	}

	protected function getTagsInstance()
	{
		if ($this->tags === null)
		{
			$this->tags = new \Bitrix\Intranet\Component\UserProfile\Tags([
				'profileId' => $this->arParams['ID'],
				'permissions' => $this->getPermissions(),
				'pathToUser' => $this->arParams['PATH_TO_USER'],
			]);
		}
		return $this->tags;
	}

	protected function getStressLevelInstance()
	{
		if ($this->stressLevel === null)
		{
			$this->stressLevel = new \Bitrix\Intranet\Component\UserProfile\StressLevel();
		}
		return $this->stressLevel;
	}


	protected function getUrls()
	{
		global $APPLICATION;

		$urls = [
			'Security' => \CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_USER_SECURITY'] ?? null, [
				"user_id" => (int) $this->arParams["ID"]
			]),
			'Passwords' => \CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_USER_PASSWORDS'] ?? null, [
				'user_id' => (int) $this->arParams['ID']
			]),
			'CommonSecurity' => \CComponentEngine::MakePathFromTemplate($this->arParams["PATH_TO_USER_COMMON_SECURITY"] ?? null, [
				'user_id' => (int) $this->arParams['ID']
			]),
		];
		if (Loader::includeModule('dav'))
		{
			$this->arParams["PATH_TO_USER_SYNCHRONIZE"] = trim($this->arParams["PATH_TO_USER_SYNCHRONIZE"]);
			if ($this->arParams["PATH_TO_USER_SYNCHRONIZE"] == '')
				$this->arParams["PATH_TO_USER_SYNCHRONIZE"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$this->arParams["PAGE_VAR"]."=user_synchronize&".$this->arParams["USER_VAR"]."=#user_id#");

			$urls['Synchronize'] = \CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_USER_SYNCHRONIZE'], [
				'user_id' => $this->arParams['ID']
			]);
		}

		$uri = new \Bitrix\Main\Web\Uri($this->arParams["LIST_URL"]);
		$uri->addParams([
			'apply_filter' => 'Y',
			'TAGS' => '#TAG#'
		]);
		$urls["PathToTag"] = $uri->getUri();

		return $urls;
	}

	protected function printErrors()
	{
		foreach ($this->errorCollection as $error)
		{
			ShowError($error);
		}
	}

	public function onPrepareComponentParams($params)
	{
		$this->errorCollection = new ErrorCollection();

		if (
			empty($params['GRAT_POST_LIST_PAGE_SIZE'])
			|| (int)$params['GRAT_POST_LIST_PAGE_SIZE'] <= 0
			|| (int)$params['GRAT_POST_LIST_PAGE_SIZE'] > 20
		)
		{
			$params['GRAT_POST_LIST_PAGE_SIZE'] = 5;
		}

		if (empty($params['LIST_URL']))
		{
			$params['LIST_URL'] = Option::get('intranet', 'list_user_url', (Loader::includeModule('extranet') && \CExtranet::isExtranetSite() ? SITE_DIR.'contacts/' : SITE_DIR.'company/'), SITE_ID);
		}

		$params['PATH_TO_POST_EDIT_PROFILE'] ??= null;
		$params['PATH_TO_POST_EDIT_GRAT'] ??= null;
		$params['PATH_TO_USER_GRAT'] ??= null;
		$params['CACHE_TIME'] ??= 3600;
		$params['PATH_TO_CONPANY_DEPARTMENT'] ??= null;
		$params['PATH_TO_USER_SECURITY'] ??= null;
		$params['PATH_TO_USER_PASSWORDS'] ??= null;
		$params['PATH_TO_USER_SYNCHRONIZE'] ??= null;
		$params['PAGE_VAR'] ??= null;
		$params['USER_VAR'] ??= null;
		$params['PATH_TO_POST'] ??= null;
		$params['PATH_TO_USER'] ??= null;

		return $params;
	}

	protected function init()
	{
		\CJSCore::Init([ 'ajax', 'webrtc_adapter', 'avatar_editor', 'loader' ]);

		return true;
	}

	/**
	* Getting array of errors.
	* @return Error[]
	*/
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * @param bool $resetCache if `TRUE` reset cache and returns actual data.
	 *
	 * @return array|null
	 */
	protected function getUserData(bool $resetCache = false): ?array
	{
		global $USER;

		if ($this->arParams["ID"] <= 0)
		{
			return null;
		}
		elseif (isset($this->userData) && !$resetCache)
		{
			return $this->userData;
		}

		$filter = [
			"ID_EQUAL_EXACT" => $this->arParams["ID"]
		];

		$params = [
			"FIELDS" => [
				'ID', 'ACTIVE', 'CONFIRM_CODE', 'EXTERNAL_AUTH_ID', 'LAST_ACTIVITY_DATE', 'DATE_REGISTER',
				'LOGIN', 'EMAIL', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'WORK_POSITION',
				'PERSONAL_PHOTO', 'PERSONAL_BIRTHDAY', 'PERSONAL_GENDER',
				'PERSONAL_WWW', 'PERSONAL_MOBILE', 'WORK_PHONE', 'PERSONAL_CITY',
				'TIME_ZONE', 'AUTO_TIME_ZONE', 'TIME_ZONE_OFFSET',
				'PERSONAL_COUNTRY', 'PERSONAL_FAX', 'PERSONAL_MAILBOX',
				'PERSONAL_PHONE', 'PERSONAL_STATE', 'PERSONAL_STREET', 'PERSONAL_ZIP',
				'WORK_CITY', 'WORK_COUNTRY', 'WORK_COMPANY', 'WORK_DEPARTMENT',
				'PERSONAL_PROFESSION', 'WORK_NOTES', 'WORK_PROFILE', 'LANGUAGE_ID',
			],
			'SELECT' => [ 'UF_DEPARTMENT', 'UF_PHONE_INNER', 'UF_SKYPE', 'UF_SKYPE_LINK', 'UF_ZOOM', 'UF_PUBLIC' ]
		];

		$dbUser = \CUser::GetList("id", "asc", $filter, $params);
		$user = $dbUser->fetch();

		foreach ($user as $field => $value)
		{
			if ($value === null)
			{
				$user[$field] = "";
			}

			if ($field === "LAST_ACTIVITY_DATE" && $value != '')
			{
				$user["LAST_ACTIVITY_DATE_FROM_DB"] = $value;
				$user[$field] = DateTime::createFromTimestamp(MakeTimeStamp($value, 'YYYY-MM-DD HH:MI:SS'));
				$user[$field] = FormatDateFromDB($user[$field]);
			}
		}

		if ((int)$user["PERSONAL_PHOTO"] <= 0)
		{
			switch($user["PERSONAL_GENDER"])
			{
				case "M":
					$suffix = "male";
					break;
				case "F":
					$suffix = "female";
					break;
				default:
					$suffix = "unknown";
				}
				$user["PERSONAL_PHOTO"] = Option::get('socialnetwork', 'default_user_picture_'.$suffix, false, SITE_ID);
		}

		$user["PHOTO"] = self::getUserPhoto($user["PERSONAL_PHOTO"], 512);

		$user["FULL_NAME"] = \CUser::FormatName(\CSite::GetNameFormat(), $user, true, false);

		if ($user["PERSONAL_WWW"] <> '')
		{
			$user["PERSONAL_WWW"] = ((mb_strpos($user["PERSONAL_WWW"], "http") === false) ? "http://" : "").$user["PERSONAL_WWW"];
		}

		$user["ONLINE_STATUS"] = \CUser::GetOnlineStatus($this->arParams["ID"],
			MakeTimeStamp($user["LAST_ACTIVITY_DATE_FROM_DB"] ?? null, "YYYY-MM-DD HH-MI-SS"));

		$user["SHOW_SONET_ADMIN"] = false;
		if (
			$this->arParams["ID"] == $USER->GetID()
			&& Loader::includeModule("socialnetwork")
			&& \CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false)
		)
		{
			$user["SHOW_SONET_ADMIN"] = true;
			$user["IS_SESSION_ADMIN"] = \CSocNetUser::IsEnabledModuleAdmin();
		}

		$this->setUserStatus($user);
		$this->checkVoximplantPhone($user);
		$this->prepareSubordination($user);
		$user += Util::getAppsInstallationConfig($this->arParams['ID']);

		if (Loader::includeModule("bitrix24") && \Bitrix\Bitrix24\Integrator::isIntegrator($user["ID"]))
		{
			$user["IS_INTEGRATOR"] = true;
		}

		$this->userData = $user;

		return $this->userData;
	}

	protected function isCurrentUserAdmin()
	{
		return (
			(
				Loader::includeModule('bitrix24')
				&& \CBitrix24::IsPortalAdmin(CurrentUser::get()->getId())
			)
			||CurrentUser::get()->isAdmin()
		);
	}

	public function saveAction(array $data)
	{
		global $USER, $USER_FIELD_MANAGER;

		if (empty($data))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('INTRANET_USER_PROFILE_NOTHING_TO_SAVE'));
			return null;
		}

		$this->arResult['Permissions'] = $this->getPermissions();
		if (!$this->arResult['Permissions']['edit'])
		{
			$this->errorCollection[] = new Error(Loc::getMessage('INTRANET_USER_PROFILE_ACCESS_DENIED'));
			return null;
		}

		if (isset($data["UF_DEPARTMENT"]))
		{
			if (
				empty($data["UF_DEPARTMENT"][0])
				|| !$this->isCurrentUserAdmin()
			)
			{
				unset($data["UF_DEPARTMENT"]);
			}
		}

		$fields = [
			'NAME', 'LAST_NAME', 'SECOND_NAME', 'PERSONAL_GENDER', 'PERSONAL_BIRTHDAY',
			'EMAIL', 'PERSONAL_MOBILE', 'PERSONAL_WWW',  'PERSONAL_COUNTRY', 'PERSONAL_CITY', 'PERSONAL_STATE',
			'WORK_PHONE', 'WORK_POSITION', 'AUTO_TIME_ZONE', 'TIME_ZONE',
			'LOGIN', 'PASSWORD', 'CONFIRM_PASSWORD',
			'PERSONAL_FAX',  'PERSONAL_MAILBOX', 'PERSONAL_PHONE', 'PERSONAL_STATE', 'PERSONAL_STREET', 'PERSONAL_ZIP',
			'WORK_CITY', 'WORK_COUNTRY', 'WORK_COMPANY', 'WORK_DEPARTMENT',
			'PERSONAL_PROFESSION', 'WORK_NOTES', 'WORK_PROFILE', 'LANGUAGE_ID',
		];

		$newFields = [];
		foreach ($fields as $key)
		{
			if ($key === 'PASSWORD' && isset($data['PASSWORD']) && $data['PASSWORD'] == '')
			{
				unset($data['PASSWORD']);
				unset($data['CONFIRM_PASSWORD']);
			}

			if (isset($data[$key]))
			{
				if (in_array($key, [ 'NAME', 'LAST_NAME', 'SECOND_NAME' ]))
				{
					$data[$key] = trim($data[$key]);
				}

				$newFields[$key] = $data[$key];
			}
		}

		$changedFields = $this->getChangedFields($newFields);
		if (Loader::includeModule('bitrix24') && $this->isUserInvited())
		{
			if (isset($changedFields['EMAIL']))
			{
				$this->errorCollection[] = new Error(
					Loc::getMessage('INTRANET_USER_PROFILE_CANNOT_CHANGE_EMAIL_FOR_INVITED_USER')
				);

				return null;
			}
			elseif (isset($changedFields['PERSONAL_MOBILE']))
			{
				$this->errorCollection[] = new Error(
					Loc::getMessage('INTRANET_USER_PROFILE_CANNOT_CHANGE_PHONE_FOR_INVITED_USER')
				);

				return null;
			}
		}

		$ufReserved = $this->getFormInstance()->getReservedUfFields();
		foreach ($data as $fieldName => $fieldValue)
		{
			if (in_array($fieldName, $ufReserved))
			{
				unset($data[$fieldName]);
			}
		}
		$USER_FIELD_MANAGER->EditFormAddFields('USER', $newFields, [ 'FORM' => $data ]);
		if (!$USER->Update($this->arParams['ID'], $newFields))
		{
			$this->errorCollection[] = new Error($USER->LAST_ERROR);
			return null;
		}

		if (
			isset($newFields['UF_DEPARTMENT']) &&
			is_array($newFields['UF_DEPARTMENT']) &&
			(int)$this->arParams['ID'] > 0
		)
		{
			$departmentRepository = ServiceContainer::getInstance()
				->departmentRepository();
			$departmentCollection = $departmentRepository->getDepartmentByHeadId((int)$this->arParams['ID']);
			foreach ($departmentCollection as $department)
			{
				if (in_array($department->getId(), $newFields['UF_DEPARTMENT']))
				{
					continue;
				}
				$departmentRepository->unsetHead($department->getId());
			}
		}

		if (defined('BX_COMP_MANAGED_CACHE'))
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->ClearByTag('USER_CARD_'.(int)($this->arParams['ID'] / TAGGED_user_card_size));
		}

		if (Loader::includeModule('intranet'))
		{
			\Bitrix\Intranet\Composite\CacheProvider::deleteUserCache();
		}

		$this->arResult['User'] = $this->getUserData(true);
		return [
			'ENTITY_DATA' => $this->getFormInstance()->getData($this->arResult),
			'SUCCESS' => 'Y'
		];
	}

	private function checkVoximplantPhone(&$user)
	{
		$user["VOXIMPLANT_ENABLE_PHONES"] = [
			"PERSONAL_MOBILE" => "N",
			"WORK_PHONE" => "N"
		];

		if (Loader::includeModule('voximplant'))
		{
			$userPermissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
			if (
				$userPermissions->canPerform(
					\Bitrix\Voximplant\Security\Permissions::ENTITY_CALL,
					\Bitrix\Voximplant\Security\Permissions::ACTION_PERFORM,
					\Bitrix\Voximplant\Security\Permissions::PERMISSION_CALL_USERS
				)
			)
			{
				if (!empty($user["PERSONAL_MOBILE"]) && \CVoxImplantMain::Enable($user["PERSONAL_MOBILE"]))
				{
					$user["VOXIMPLANT_ENABLE_PHONES"]["PERSONAL_MOBILE"] = "Y";
				}

				if (!empty($user["WORK_PHONE"]) && \CVoxImplantMain::Enable($user["WORK_PHONE"]))
				{
					$user["VOXIMPLANT_ENABLE_PHONES"]["WORK_PHONE"] = "Y";
				}
			}
		}
	}

	public static function getUserPhoto($photoId, $size = 100)
	{
		if ($photoId > 0)
		{
			$file = \CFile::GetFileArray($photoId);
			if ($file !== false)
			{
				$fileTmp = \CFile::ResizeImageGet(
					$file,
					[ 'width' => $size, 'height' => $size ],
					BX_RESIZE_IMAGE_PROPORTIONAL,
					false
				);

				return $fileTmp["src"];
			}
		}

		return false;
	}

	private function setUserStatus(&$user)
	{
		$obUser = new \CUser();
		$arGroups = $obUser->getUserGroup($user["ID"]);

		$extranetGroupId = (
			Loader::includeModule('extranet')
				? (int)\CExtranet::getExtranetUserGroupId()
				: 0
		);

		$user["IS_EXTRANET"] = false;

		if (in_array(1, $arGroups))
		{
			$user["STATUS"] = "admin";
		}
		else
		{
			$user["STATUS"] = "employee";

			if (
				!is_array($user['UF_DEPARTMENT'])
				|| empty($user['UF_DEPARTMENT'][0])
			)
			{
				if (
					$extranetGroupId
					&& in_array($extranetGroupId, $arGroups)
				)
				{
					$isCollaber = \Bitrix\Extranet\Service\ServiceContainer::getInstance()
						->getCollaberService()
						->isCollaberById((int)$user["ID"]);
					$user["STATUS"] = $isCollaber ? "collaber" : "extranet";
					$user["IS_EXTRANET"] = true;
				}
				else
				{
					$user["STATUS"] = "visitor";
				}
			}
		}

		if (Loader::includeModule("bitrix24") && \Bitrix\Bitrix24\Integrator::isIntegrator($user["ID"]))
		{
			$user["STATUS"] = "integrator";
		}

		if ($user["ACTIVE"] === "N")
		{
			$user["STATUS"] = "fired";
		}

		if (
			$user["ACTIVE"] === "Y"
			&& !empty($user["CONFIRM_CODE"])
		)
		{
			$user["STATUS"] = self::STATUS_INVITED;
		}

		if (
			$user["ACTIVE"] === "N"
			&& !empty($user["CONFIRM_CODE"])
		)
		{
			$user["STATUS"] = 'waiting';
		}

		if (in_array($user["EXTERNAL_AUTH_ID"], [ 'email' ]))
		{
			$user["STATUS"] = $user["EXTERNAL_AUTH_ID"];
		}
		elseif (in_array($user["EXTERNAL_AUTH_ID"], [ 'shop', 'sale', 'saleanonymous' ]))
		{
			$user["STATUS"] = 'shop';
		}
	}

	protected function getCurrentUserStatus()
	{
		static $result = null;

		if ($result === null)
		{
			$result = (
				Loader::includeModule("extranet")
				&& \CExtranet::isExtranetSite()
				&& !\CExtranet::isIntranetUser()
					? 'extranet'
					: ''
			);
		}

		return $result;
	}

	private function prepareSubordination(&$user)
	{
		global $CACHE_MANAGER;

		$obCache = new \CPHPCache;
		$path = "/user_card_".(int)($user["ID"] / TAGGED_user_card_size);

		if ( $this->arParams["CACHE_TIME"] == 0 || $obCache->StartDataCache($this->arParams["CACHE_TIME"], $user["ID"], $path))
		{
			if ($this->arParams["CACHE_TIME"] > 0 && defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->StartTagCache($path);
				$CACHE_MANAGER->RegisterTag("USER_CARD_".(int)($user["ID"] / TAGGED_user_card_size));
			}

			//departments and subordinate users
			$user['DEPARTMENTS'] = [];
			$user["SUBORDINATE"] = [];

			$this->prepareDepartmentAndSubordinate($user);

			//managers
			$user['MANAGERS'] = [];
			$managers = \CIntranetUtils::GetDepartmentManager($user["UF_DEPARTMENT"], $user["ID"], true);
			foreach ($managers as $key => $manager)
			{
				if ((int)$manager["PERSONAL_PHOTO"] <= 0)
				{
					switch($manager["PERSONAL_GENDER"])
					{
						case "M":
							$suffix = "male";
							break;
						case "F":
							$suffix = "female";
							break;
						default:
							$suffix = "unknown";
					}
					$manager["PERSONAL_PHOTO"] = Option::get('socialnetwork', 'default_user_picture_'.$suffix, false, SITE_ID);
				}

				$manager["FULL_NAME"] = \CUser::FormatName(\CSite::GetNameFormat(), $manager, true, false);
				$manager["PHOTO"] = self::getUserPhoto($manager["PERSONAL_PHOTO"], 100);
				$manager["LINK"] = \CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_USER'], [
					'user_id' => $manager['ID'],
				]);

				$user['MANAGERS'][$key] = $manager;
			}

			if ($this->arParams["CACHE_TIME"] > 0)
			{
				$obCache->EndDataCache([
					'DEPARTMENTS' => $user['DEPARTMENTS'],
					'MANAGERS' => $user['MANAGERS'],
					'SUBORDINATE' => $user['SUBORDINATE'],
				]);
				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->EndTagCache();
				}
			}
		}
		elseif ($this->arParams["CACHE_TIME"] > 0)
		{
			$vars = $obCache->GetVars();
			$user['DEPARTMENTS'] = $vars['DEPARTMENTS'];
			$user['MANAGERS'] = $vars['MANAGERS'];
			$user['SUBORDINATE'] = $vars['SUBORDINATE'];
		}

		if (
			$user["STATUS"] === 'extranet'
			|| $this->getCurrentUserStatus() === 'extranet'
		)
		{
			$user['MANAGERS'] = [];
			$user['SUBORDINATE'] = [];
		}
	}

	protected function getPermissions()
	{
		global $USER;

		static $cache = false;

		if (
			$cache === false
			&& Loader::includeModule('socialnetwork')
		)
		{
			global $USER;

			$currentUserPerms = \CSocNetUserPerms::initUserPerms(
				$USER->getId(),
				$this->arParams["ID"],
				\CSocNetUser::isCurrentUserModuleAdmin(SITE_ID, false)
			);

			$result = [
				'view' => (
					$currentUserPerms['IsCurrentUser']
					|| \CSocNetUser::canProfileView($USER->getId(), $this->getUserData(), SITE_ID, ComponentHelper::getUrlContext())
				),
				'edit' => (
					$currentUserPerms["IsCurrentUser"]
					|| (
						$currentUserPerms["Operations"]["modifyuser"]
						&& $currentUserPerms["Operations"]["modifyuser_main"]
					)
				)
			];

			if (
				!ModuleManager::isModuleInstalled("bitrix24")
				&& $USER->isAdmin()
				&& !$currentUserPerms["IsCurrentUser"]
			)
			{
				$result['edit'] = (
					$result['edit']
					&& \CSocNetUser::isCurrentUserModuleAdmin(SITE_ID, false)
				);
			}

			//check for integrator
			if (
				Loader::includeModule("bitrix24")
				&& $this->arParams["ID"] != $USER->GetID()
				&& \Bitrix\Bitrix24\Integrator::isIntegrator($USER->GetID())
				&& \CBitrix24::IsPortalAdmin($this->arParams["ID"])
				&& !\Bitrix\Bitrix24\Integrator::isIntegrator($this->arParams["ID"])
			)
			{
				$result['edit'] = false;
			}

			$cache = $result;
		}
		else
		{
			$result = $cache;
		}

		return $result;
	}

	public function getProfileBlogPostAction()
	{
		return $this->getProfilePostInstance()->getProfileBlogPostAction($this->errorCollection);
	}

	public function getProfileBlogPostFormAction()
	{
		return $this->getProfilePostInstance()->getProfileBlogPostFormAction();
	}

	public function sendProfileBlogPostFormAction(array $params = [])
	{
		return $this->getProfilePostInstance()->sendProfileBlogPostFormAction($params);
	}

	public function deleteProfileBlogPostAction(array $params = [])
	{
		return $this->getProfilePostInstance()->deleteProfileBlogPostAction($params);
	}

	public function getGratitudePostListAction(array $params = [])
	{
		return $this->getGratsInstance()->getGratitudePostListAction($params);
	}

	public function getTagsListAction(array $params = [])
	{
		return $this->getTagsInstance()->getTagsListAction();
	}

	public function getTagDataAction(array $params = [])
	{
		return $this->getTagsInstance()->getTagDataAction($params);
	}

	public function searchTagsAction(array $params = [])
	{
		return $this->getTagsInstance()->searchTagsAction($params);
	}

	public function addTagAction(array $params = [])
	{
		return $this->getTagsInstance()->addTagAction($params);
	}

	public function removeTagAction(array $params = [])
	{
		return $this->getTagsInstance()->removeTagAction($params);
	}

	public function reindexUserAction(array $params = [])
	{
		global $USER;

		$result = false;

		$userId = (!empty($params['userId']) && (int)$params['userId'] > 0 ? (int)$params['userId'] : $this->arParams["ID"]);
		if (
			$userId != $this->arParams["ID"]
			&& $userId != $USER->getId()
		)
		{
			return $result;
		}

		if ($userId == $this->arParams["ID"])
		{
			$permissions = $this->getPermissions();
			if (!$permissions['edit'])
			{
				return $result;
			}
		}

		\Bitrix\Main\UserTable::indexRecord($userId);

		return true;
	}

	protected function checkNumAdminRestrictions()
	{
		$this->arResult["adminRightsRestricted"] = false;
		$this->arResult["delegateAdminRightsRestricted"] = false;

		if ($this->arResult["isCloud"])
		{
			$this->arResult["isCompanyTariff"] = false;

			if ($this->arResult["User"]["STATUS"] !== "admin")
			{
				$this->arResult["adminLimitsEnabled"] = \COption::GetOptionString("bitrix24", "admin_limits_enabled", "N") == "Y"
					? true : false;

				if ($this->arResult["adminLimitsEnabled"])
				{
					$numAdmins = 1;
					$curAdmins = \CBitrix24::getAllAdminId();
					if (is_array($curAdmins))
					{
						$numAdmins = count($curAdmins);
					}

					$maxAdmins = \CBitrix24::getMaxAdminCount();

					if ($maxAdmins > 0 && $numAdmins >= $maxAdmins)
					{
						$this->arResult["adminRightsRestricted"] = true;
					}

					if ($this->arResult["adminRightsRestricted"])
					{
						\CBitrix24::initLicenseInfoPopupJS();

						$licenseType = \CBitrix24::getLicenseFamily();
						if ($licenseType == "company")
						{
							$this->arResult["isCompanyTariff"] = true;
						}

						if (!\Bitrix\Bitrix24\Feature::isFeatureEnabled("delegation_admin_rights"))
						{
							$this->arResult["delegateAdminRightsRestricted"] = true;
						}
					}
				}
			}
		}
	}

	private static function setWhiteListOption(array $value = [])
	{
		$optionValue = self::getWhiteListOption();
		$diff1 = array_diff($value, $optionValue);
		$diff2 = array_diff($optionValue, $value);
		if (
			!empty($diff1)
			|| !empty($diff2)
		)
		{
			Option::set('intranet', 'user_profile_whitelist', serialize($value), SITE_ID);
		}
	}

	public static function getWhiteListOption()
	{
		$result = [];

		$viewFields = \Bitrix\Main\Config\Option::get("intranet", "user_profile_view_fields", false, SITE_ID);
		if ($viewFields !== false)
		{
			return explode(",", $viewFields);
		}

		$val = Option::get('intranet', 'user_profile_whitelist', false, SITE_ID);
		if (!empty($val))
		{
			$val = unserialize($val, ["allowed_classes" => false]);
			if (
				is_array($val)
				&& !empty($val)
			)
			{
				$result = $val;
			}
		}

		return $result;
	}

	public function getAvailableFields()
	{
		$result = [];

		if (!$this->arResult["AllowAllUserProfileFields"])
		{
			$result[] = 'FULL_NAME';

			if (!empty($this->arParams['USER_FIELDS_MAIN']))
			{
				$result = array_merge($result, array_values($this->arParams['USER_FIELDS_MAIN']));
			}
			if (!empty($this->arParams['USER_PROPERTY_MAIN']))
			{
				$result = array_merge($result, array_values($this->arParams['USER_PROPERTY_MAIN']));
			}
			if (!empty($this->arParams['USER_FIELDS_CONTACT']))
			{
				$result = array_merge($result, array_values($this->arParams['USER_FIELDS_CONTACT']));
			}
			if (!empty($this->arParams['USER_PROPERTY_CONTACT']))
			{
				$result = array_merge($result, array_values($this->arParams['USER_PROPERTY_CONTACT']));
			}
			if (!empty($this->arParams['USER_FIELDS_PERSONAL']))
			{
				$result = array_merge($result, array_values($this->arParams['USER_FIELDS_PERSONAL']));
			}
			if (!empty($this->arParams['USER_PROPERTY_PERSONAL']))
			{
				$result = array_merge($result, array_values($this->arParams['USER_PROPERTY_PERSONAL']));
			}
			if (!empty($this->arParams['EDITABLE_FIELDS']))
			{
				$result = array_merge($result, array_values($this->arParams['EDITABLE_FIELDS']));
			}

			$result = array_unique($result);
		}

		self::setWhiteListOption($result);

		return $result;
	}

	public function fieldsSettingsAction()
	{
		return new \Bitrix\Main\Engine\Response\Component($this->getName(), 'settings', $this->arParams);
	}

	protected function processShowYear()
	{
		$existingValue = Option::get("intranet", "user_profile_show_year", "Y");
		$actualValue = 'Y';
		if (ModuleManager::isModuleInstalled('bitrix24'))
		{
			if (Option::get("intranet", "show_year_for_female", "N") === "N")
			{
				$actualValue = 'N';
			}
		}
		elseif (
			isset($this->arParams['SHOW_YEAR'])
			&& in_array($this->arParams['SHOW_YEAR'], ['Y', 'M', 'N'])
		)
		{
			$actualValue = $this->arParams['SHOW_YEAR'];
		}
		if ($actualValue != $existingValue)
		{
			Option::set("intranet", "user_profile_show_year", $actualValue);
		}
	}

	protected function getDiskInfo()
	{
		if (!Loader::includeModule("disk"))
		{
			return;
		}

		$diskObj = new \Bitrix\Disk\Bitrix24Disk\Information($this->arParams["ID"]);
		$installationDate = $diskObj->getInstallationDatetime();
		$space = $diskObj->getDiskSpaceUsage();

		$diskInfo = [];
		if (!empty($installationDate))
		{
			$diskInfo['INSTALLATION_DATE'] = $installationDate->toString(new \Bitrix\Main\Context\Culture([ 'FORMAT_DATETIME' => FORMAT_DATE ]));
		}

		if (!empty($space))
		{
			$diskInfo["SPACE"] = \CFile::FormatSize($space);
		}

		return $diskInfo;
	}

	protected function getUserGdpr()
	{
		$gdprResult = [
			"email" => [
				"value" => "Y",
				"date" => ""
			],
			"training" => [
				"value" => "Y",
				"date" => ""
			],
		];

		$gdprEmail = \CUserOptions::GetOption('bitrix24', "gdpr_email_info", []);
		$gdprTraining = \CUserOptions::GetOption('bitrix24', "gdpr_email_training", []);

		if (!empty($gdprEmail))
		{
			$gdprResult["email"]["value"] = ($gdprEmail["value"] === "N" ? "N" : "Y");
			if ((int)$gdprEmail["date"] > 0)
			{
				$gdprResult["email"]["date"] = \ConvertTimeStamp($gdprEmail["date"]);
			}
		}

		if (!empty($gdprTraining))
		{
			$gdprResult["training"]["value"] = ($gdprTraining["value"] === "N" ? "N" : "Y");
			if ((int)$gdprTraining["date"] > 0)
			{
				$gdprResult["training"]["date"] = \ConvertTimeStamp($gdprTraining["date"]);
			}
		}

		return $gdprResult;
	}

	public function changeGdprAction($type = "", $value = "")
	{
		if (in_array($type, ["gdpr_email_info", "gdpr_email_training"]))
		{
			$currentDate = new Date;
			$currentDate = $currentDate->getTimestamp();

			$data = [
				"value" => ($value === "Y" ? "Y" : "N"),
				"date" => $currentDate
			];
			\CUserOptions::SetOption('bitrix24', $type, $data);

			if (Loader::includeModule("bitrix24") && \CBitrix24::isPortalCreator())
			{
				Option::set("bitrix24", $type, $value);
			}
		}
	}

	/**
	 * Changed fields.
	 *
	 * @param array $newFields
	 *
	 * @return array
	 */
	private function getChangedFields(array $newFields): array
	{
		$result = [];

		$currentFields = $this->getUserData();
		foreach ($newFields as $name => $value)
		{
			if ((string)$currentFields[$name] !== (string)$newFields[$name])
			{
				$result[$name] = $value;
			}
		}

		return $result;
	}

	/**
	 * Checks if current user is invited.
	 *
	 * @return bool
	 */
	private function isUserInvited(): bool
	{
		return $this->getUserData()['STATUS'] === self::STATUS_INVITED;
	}

	public function getAdditionalBlocks(): array
	{
		$result = [];
		$event = new Event(
			'intranet',
			self::ON_PROFILE_CONFIG_ADDITIONAL_BLOCKS,
			[
				'profileId' => $this->getUserId()
			]
		);
		EventManager::getInstance()->send($event);
		foreach ($event->getResults() as $eventResult)
		{
			if (
				($eventResult->getType() === EventResult::SUCCESS)
				&& ($additionalBlock = $eventResult->getParameters()['additionalBlock'])
				&& isset($additionalBlock['COMPONENT_NAME'])
				&& is_string($additionalBlock['COMPONENT_NAME'])
			)
			{
				$result[] = $additionalBlock;
			}
		}

		return $result;
	}

	private function prepareDepartmentAndSubordinate(array &$user)
	{
		if (!Loader::includeModule('humanresources'))
		{
			$dbDepartments = \CIntranetUtils::GetSubordinateDepartmentsList($user["ID"]);

			while ($department = $dbDepartments->GetNext())
			{
				$department['URL'] =
					str_replace('#ID#', $department['ID'], $this->arParams['PATH_TO_CONPANY_DEPARTMENT']);
				$department['EMPLOYEE_COUNT'] = 0;

				$user['DEPARTMENTS'][$department['ID']] = $department;

				$dbUsers = \CUser::GetList(
					"",
					"",
					[
						'!ID' => $user['ID'],
						'UF_DEPARTMENT' => $department['ID'],
						'ACTIVE' => 'Y',
						'CONFIRM_CODE' => false,
						'IS_REAL_USER' => 'Y',
					],
					[
						'FIELDS' => [
							'ID',
							'NAME',
							'LAST_NAME',
							'SECOND_NAME',
							'LOGIN',
							'WORK_POSITION',
							'PERSONAL_PHOTO',
							'PERSONAL_GENDER',
						],
					],
				);

				while ($subUser = $dbUsers->GetNext())
				{
					if ((int)$subUser["PERSONAL_PHOTO"] <= 0)
					{
						switch ($subUser["PERSONAL_GENDER"])
						{
							case "M":
								$suffix = "male";
								break;
							case "F":
								$suffix = "female";
								break;
							default:
								$suffix = "unknown";
						}
						$subUser["PERSONAL_PHOTO"] =
							Option::get('socialnetwork', 'default_user_picture_' . $suffix, false, SITE_ID);
					}

					$subUser["FULL_NAME"] = \CUser::FormatName(\CSite::GetNameFormat(), $subUser, true);
					$subUser["PHOTO"] = self::getUserPhoto($subUser["PERSONAL_PHOTO"], 100);
					$subUser["LINK"] = \CComponentEngine::MakePathFromTemplate(
						$this->arParams['PATH_TO_USER'],
						[
							'user_id' => $subUser['ID'],
						],
					);
					$user["SUBORDINATE"][$subUser["ID"]] = $subUser;
					$user['DEPARTMENTS'][$department['ID']]['EMPLOYEE_COUNT']++;
				}
			}

			return;
		}

		$roleRepository  = Container::getRoleRepository();
		static $headRole = null;

		if (!$headRole)
		{
			$headRole = $roleRepository->findByXmlId(NodeMember::DEFAULT_ROLE_XML_ID['HEAD']);
		}

		if (!$headRole)
		{
			return;
		}

		$relativeDepartments = Container::getNodeRepository()->findAllByUserIdAndRoleId($user["ID"], $headRole->id);
		$nodeMemberService = Container::getNodeMemberService();
		$userService = Container::getUserService();

		foreach ($relativeDepartments as $relativeDepartment)
		{
			$departmentId = DepartmentBackwardAccessCode::extractIdFromCode($relativeDepartment->accessCode);
			$employees = $nodeMemberService->getAllEmployees($relativeDepartment->id);

			$department = [
				'URL' => str_replace('#ID#', $departmentId, $this->arParams['PATH_TO_CONPANY_DEPARTMENT']),
				'EMPLOYEE_COUNT' => 0,
				'NAME' => $relativeDepartment->name,
				'ID' => $departmentId,
			];

			$user['DEPARTMENTS'][$department['ID']] = $department;

			$userCollection = $userService->getUserCollectionFromMemberCollection($employees);

			foreach ($userCollection as $preparedUser)
			{
				if ($preparedUser->id === (int)$user["ID"])
				{
					continue;
				}

				$subUser = [];

				$subUser["ID"] = $preparedUser->id;
				$subUser["PERSONAL_PHOTO"] = $userService->getUserAvatar($preparedUser, 100);
				$subUser["FULL_NAME"] = $userService->getUserName($preparedUser);
				$subUser["PHOTO"] = $userService->getUserAvatar($preparedUser, 100);
				$subUser["LINK"] = \CComponentEngine::MakePathFromTemplate(
					$this->arParams['PATH_TO_USER'],
					[
						'user_id' => $preparedUser->id,
					],
				);
				$user["SUBORDINATE"][$subUser["ID"]] = $subUser;
			}

			$user['DEPARTMENTS'][$departmentId]['EMPLOYEE_COUNT']++;
		}
	}
}
