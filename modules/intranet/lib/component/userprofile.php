<?php
namespace Bitrix\Intranet\Component;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;
use Bitrix\Main\Config\Option;
use Bitrix\Socialnetwork\ComponentHelper;
use Bitrix\Main\Engine\ActionFilter\CloseSession;

class UserProfile extends \CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{
	/** @var ErrorCollection errorCollection */
	protected $errorCollection;
	protected $form;
	protected $grats;
	protected $tags;
	protected $profilePost;
	protected $stressLevel;

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

		if (\Bitrix\Main\Config\Option::get("intranet", "user_profile_view_fields", false, SITE_ID) === false)
		{
			$result = array_merge($result, [ 'USER_FIELDS_MAIN', 'USER_PROPERTY_MAIN', 'USER_FIELDS_CONTACT', 'USER_PROPERTY_CONTACT', 'USER_FIELDS_PERSONAL', 'USER_PROPERTY_PERSONAL' ]);
		}

		if (\Bitrix\Main\Config\Option::get("intranet", "user_profile_edit_fields", false, SITE_ID) === false)
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
			$this->stressLevel = new \Bitrix\Intranet\Component\UserProfile\StressLevel([
			]);
		}
		return $this->stressLevel;
	}


	protected function getUrls()
	{
		global $APPLICATION;

		$urls = array(
			"Security" => \CComponentEngine::MakePathFromTemplate($this->arParams["PATH_TO_USER_SECURITY"], array("user_id" => $this->arParams["ID"])),
			"Passwords" => \CComponentEngine::MakePathFromTemplate($this->arParams["PATH_TO_USER_PASSWORDS"], array("user_id" => $this->arParams["ID"])),
			"CommonSecurity" => \CComponentEngine::MakePathFromTemplate($this->arParams["PATH_TO_USER_COMMON_SECURITY"], array("user_id" => $this->arParams["ID"])),
		);
		if (Loader::includeModule('dav'))
		{
			$this->arParams["PATH_TO_USER_SYNCHRONIZE"] = trim($this->arParams["PATH_TO_USER_SYNCHRONIZE"]);
			if (strlen($this->arParams["PATH_TO_USER_SYNCHRONIZE"]) <= 0)
				$this->arParams["PATH_TO_USER_SYNCHRONIZE"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$this->arParams["PAGE_VAR"]."=user_synchronize&".$this->arParams["USER_VAR"]."=#user_id#");

			$urls["Synchronize"] = \CComponentEngine::MakePathFromTemplate($this->arParams["PATH_TO_USER_SYNCHRONIZE"], array("user_id" => $this->arParams["ID"]));
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
			|| intval($params['GRAT_POST_LIST_PAGE_SIZE']) <= 0
			|| intval($params['GRAT_POST_LIST_PAGE_SIZE']) > 20
		)
		{
			$params['GRAT_POST_LIST_PAGE_SIZE'] = 5;
		}

		if (empty($params['LIST_URL']))
		{
			$params['LIST_URL'] = Option::get('intranet', 'list_user_url', (Loader::includeModule('extranet') && \CExtranet::isExtranetSite() ? SITE_DIR.'contacts/' : SITE_DIR.'company/'), SITE_ID);
		}

		return $params;
	}

	protected function init()
	{
		\CJSCore::Init(array("ajax", "webrtc_adapter", "avatar_editor", "loader"));

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

	protected function getUserData()
	{
		global $USER;

		$filter = array(
			"ID_EQUAL_EXACT" => $this->arParams["ID"]
		);

		$params = array(
			"FIELDS" => array('ID', 'ACTIVE', 'CONFIRM_CODE', 'EXTERNAL_AUTH_ID', 'LAST_ACTIVITY_DATE',
				'LOGIN', 'EMAIL', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'WORK_POSITION',
				'PERSONAL_PHOTO', 'PERSONAL_BIRTHDAY', 'PERSONAL_GENDER',
				'PERSONAL_WWW', 'PERSONAL_MOBILE', 'WORK_PHONE', 'PERSONAL_CITY',
				'TIME_ZONE', 'AUTO_TIME_ZONE', 'TIME_ZONE_OFFSET',
				'PERSONAL_COUNTRY', 'PERSONAL_FAX', 'PERSONAL_MAILBOX',
				'PERSONAL_PHONE', 'PERSONAL_STATE', 'PERSONAL_STREET', 'PERSONAL_ZIP'
			),
			"SELECT" => array("UF_DEPARTMENT", "UF_PHONE_INNER", "UF_SKYPE")
		);

		$dbUser = \CUser::GetList(($by="id"), ($order="asc"), $filter, $params);
		$user = $dbUser->fetch();

		foreach ($user as $field => $value)
		{
			if ($value === null)
			{
				$user[$field] = "";
			}
		}

		$user["PHOTO"] = self::getUserPhoto($user["PERSONAL_PHOTO"], 212);

		$fullName = array(
			"NAME" => $user["NAME"],
			"LAST_NAME" => $user["LAST_NAME"],
			"SECOND_NAME" => $user["SECOND_NAME"]
		);
		$user["FULL_NAME"] = \CUser::FormatName(\CSite::GetNameFormat(), $fullName);

		if (strlen($user["PERSONAL_WWW"]) > 0)
		{
			$user["PERSONAL_WWW"] = ((strpos($user["PERSONAL_WWW"], "http") === false) ? "http://" : "").$user["PERSONAL_WWW"];
		}

		$user["ONLINE_STATUS"] = \CUser::GetOnlineStatus($this->arParams["ID"], MakeTimeStamp($user["LAST_ACTIVITY_DATE"], "YYYY-MM-DD HH-MI-SS"));

		$user["SHOW_SONET_ADMIN"] = false;
		if(
			$this->arParams["ID"] == $USER->GetID()
			&& Loader::includeModule("socialnetwork")
			&& \CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false)
		)
		{
			$user["SHOW_SONET_ADMIN"] = true;
			$user["IS_SESSION_ADMIN"] = isset($_SESSION["SONET_ADMIN"]);
		}

		$this->setUserStatus($user);
		$this->prepareSubordination($user);
		$this->checkAppsInstallation($user);

		return $user;
	}

	protected function checkAppsInstallation(&$user)
	{
		$appActivity = array(
			"APP_WINDOWS_INSTALLED" => \CUserOptions::GetOption('im', 'WindowsLastActivityDate', "", $this->arParams["ID"]),
			"APP_MAC_INSTALLED" => \CUserOptions::GetOption('im', 'MacLastActivityDate', "", $this->arParams["ID"]),
			"APP_IOS_INSTALLED" => \CUserOptions::GetOption('mobile', 'iOsLastActivityDate', "", $this->arParams["ID"]),
			"APP_ANDROID_INSTALLED" => \CUserOptions::GetOption('mobile', 'AndroidLastActivityDate', "", $this->arParams["ID"]),
		);

		foreach ($appActivity as $key => $lastActivity)
		{
			if (intval($lastActivity) <= 0 || $lastActivity < time() - 6*30*24*60*60)
			{
				$user[$key] = false;
			}
			else
			{
				$user[$key] = true;
			}
		}
	}

	public function saveAction(array $data)
	{
		global $USER, $USER_FIELD_MANAGER;

		if(empty($data))
		{
			$this->errorCollection[] = new Error(Loc::getMessage('INTRANET_USER_PROFILE_NOTHING_TO_SAVE'));
			return null;
		}

		$this->arResult['Permissions'] = $this->getPermissions();
		if(!$this->arResult['Permissions']['edit'])
		{
			$this->errorCollection[] = new Error(Loc::getMessage('INTRANET_USER_PROFILE_ACCESS_DENIED'));
			return null;
		}

		$fields = array(
			'NAME', 'LAST_NAME', 'SECOND_NAME', 'PERSONAL_GENDER', 'PERSONAL_BIRTHDAY',
			'EMAIL', 'PERSONAL_MOBILE', 'PERSONAL_WWW',  'PERSONAL_COUNTRY', 'PERSONAL_CITY', 'PERSONAL_STATE',
			'WORK_PHONE', 'WORK_POSITION', 'AUTO_TIME_ZONE', 'TIME_ZONE',
			'LOGIN', 'PASSWORD', 'CONFIRM_PASSWORD',
			'PERSONAL_FAX',  'PERSONAL_MAILBOX', 'PERSONAL_PHONE', 'PERSONAL_STATE', 'PERSONAL_STREET', 'PERSONAL_ZIP'
		);

		$newFields = array();
		foreach ($fields as $key)
		{
			if ($key == 'PASSWORD' && strlen($data['PASSWORD']) <= 0)
			{
				unset($data['PASSWORD']);
				unset($data['CONFIRM_PASSWORD']);
			}

			if (isset($data[$key]))
			{
				if (in_array($key, array('NAME', 'LAST_NAME', 'SECOND_NAME')))
				{
					$data[$key] = trim($data[$key]);
				}

				$newFields[$key] = $data[$key];
			}
		}

		$USER_FIELD_MANAGER->EditFormAddFields('USER', $newFields, array('FORM' => $data));
		if(!$USER->Update($this->arParams['ID'], $newFields))
		{
			$this->errorCollection[] = new Error($USER->LAST_ERROR);
			return null;
		}
		else
		{
			if(defined('BX_COMP_MANAGED_CACHE'))
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->ClearByTag('USER_CARD_'.intval($this->arParams['ID'] / TAGGED_user_card_size));
			}

			$this->arResult['User'] = $this->getUserData();
			return array(
				'ENTITY_DATA' => $this->getFormInstance()->getData($this->arResult),
				'SUCCESS' => 'Y'
			);
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
					array("width" => $size, "height" => $size),
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

		$user["IS_EXTRANET"] = false;

		if(in_array(1, $arGroups))
		{
			$user["STATUS"] = "admin";
		}
		else
		{
			$user["STATUS"] = "employee";

			if(
				!is_array($user['UF_DEPARTMENT'])
				|| empty($user['UF_DEPARTMENT'][0])
			)
			{
				$user["STATUS"] = "extranet";
				if ($user["EXTERNAL_AUTH_ID"] !== "email")
				{
					$user["IS_EXTRANET"] = true;
				}
			}
		}

		if (Loader::includeModule("bitrix24") && \Bitrix\Bitrix24\Integrator::isIntegrator($user["ID"]))
		{
			$user["STATUS"] = "integrator";
		}

		if($user["ACTIVE"] == "N")
		{
			$user["STATUS"] = "fired";
		}

		if (
			$user["ACTIVE"] == "Y"
			&& !empty($user["CONFIRM_CODE"])
		)
		{
			$user["STATUS"] = "invited";
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
		$path = "/user_card_".intval($user["ID"] / TAGGED_user_card_size);

		if( $this->arParams["CACHE_TIME"] == 0 || $obCache->StartDataCache($this->arParams["CACHE_TIME"], $user["ID"], $path))
		{
			if($this->arParams["CACHE_TIME"] > 0 && defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->StartTagCache($path);
				$CACHE_MANAGER->RegisterTag("USER_CARD_".intval($user["ID"] / TAGGED_user_card_size));
			}

			//departments and subordinate users
			$user['DEPARTMENTS'] = array();
			$user["SUBORDINATE"] = array();
			$dbDepartments = \CIntranetUtils::GetSubordinateDepartmentsList($user["ID"]);
			while ($department = $dbDepartments->GetNext())
			{
				$department['URL'] = str_replace('#ID#', $department['ID'], $this->arParams['PATH_TO_CONPANY_DEPARTMENT']);
				$department['EMPLOYEE_COUNT'] = 0;

				$user['DEPARTMENTS'][$department['ID']] = $department;

				$dbUsers = \CUser::GetList($o = "", $b = "", array(
					"!ID" => $user["ID"],
					'UF_DEPARTMENT' => $department['ID'],
					'ACTIVE' => 'Y',
					'CONFIRM_CODE' => false,
					'IS_REAL_USER' => "Y"
				), array('FIELDS' => array("ID", "NAME", "LAST_NAME", "SECOND_NAME", "LOGIN", "WORK_POSITION", "PERSONAL_PHOTO")));

				while($subUser = $dbUsers->GetNext())
				{

					$subUser["FULL_NAME"] = \CUser::FormatName(\CSite::GetNameFormat(), $subUser, true, false);
					$subUser["PHOTO"] = self::getUserPhoto($subUser["PERSONAL_PHOTO"], 100);
					$subUser["LINK"] = \CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_USER'], array("user_id" => $subUser["ID"]));
					$user["SUBORDINATE"][$subUser["ID"]] = $subUser;
					$user['DEPARTMENTS'][$department['ID']]['EMPLOYEE_COUNT'] ++;
				}
			}

			//managers
			$user['MANAGERS'] = array();
			$managers = \CIntranetUtils::GetDepartmentManager($user["UF_DEPARTMENT"], $user["ID"], true);
			foreach ($managers as $key => $manager)
			{
				$manager["FULL_NAME"] = \CUser::FormatName(\CSite::GetNameFormat(), $manager, true, false);
				$manager["PHOTO"] = self::getUserPhoto($manager["PERSONAL_PHOTO"], 100);
				$manager["LINK"] = \CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_USER'], array("user_id" => $manager["ID"]));

				$user['MANAGERS'][$key] = $manager;
			}

			if($this->arParams["CACHE_TIME"] > 0)
			{
				$obCache->EndDataCache(array(
					'DEPARTMENTS' => $user['DEPARTMENTS'],
					'MANAGERS' => $user['MANAGERS'],
					'SUBORDINATE' => $user['SUBORDINATE'],
				));
				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->EndTagCache();
				}
			}
		}
		elseif($this->arParams["CACHE_TIME"] > 0)
		{
			$vars = $obCache->GetVars();
			$user['DEPARTMENTS'] = $vars['DEPARTMENTS'];
			$user['MANAGERS'] = $vars['MANAGERS'];
			$user['SUBORDINATE'] = $vars['SUBORDINATE'];
		}

		if ($user["STATUS"] == 'extranet')
		{
			$user['MANAGERS'] = array();
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
					$currentUserPerms["IsCurrentUser"]
					|| \CSocNetUser::canProfileView($USER->GetID(), $this->getUserData(), SITE_ID, ComponentHelper::getUrlContext())
				),
				'edit' => (
					$currentUserPerms["IsCurrentUser"]
					|| (
						$currentUserPerms["Operations"]["modifyuser"]
						&& $currentUserPerms["Operations"]["modifyuser_main"]
					)
				)
			];

			if(
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

	public function sendProfileBlogPostFormAction(array $params = array())
	{
		return $this->getProfilePostInstance()->sendProfileBlogPostFormAction($params);
	}

	public function deleteProfileBlogPostAction(array $params = array())
	{
		return $this->getProfilePostInstance()->deleteProfileBlogPostAction($params);
	}

	public function getGratitudePostListAction(array $params = array())
	{
		return $this->getGratsInstance()->getGratitudePostListAction($params);
	}

	public function getTagsListAction(array $params = array())
	{
		return $this->getTagsInstance()->getTagsListAction();
	}

	public function getTagDataAction(array $params = array())
	{
		return $this->getTagsInstance()->getTagDataAction($params);
	}

	public function searchTagsAction(array $params = array())
	{
		return $this->getTagsInstance()->searchTagsAction($params);
	}

	public function addTagAction(array $params = array())
	{
		return $this->getTagsInstance()->addTagAction($params);
	}

	public function removeTagAction(array $params = array())
	{
		return $this->getTagsInstance()->removeTagAction($params);
	}

	public function reindexUserAction(array $params = array())
	{
		global $USER;

		$result = false;

		$userId = (!empty($params['userId']) && intval($params['userId']) > 0 ? intval($params['userId']) : $this->arParams["ID"]);
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
			$val = unserialize($val);
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
}