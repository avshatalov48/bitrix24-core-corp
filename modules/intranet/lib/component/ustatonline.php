<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2021 Bitrix
 */
namespace Bitrix\Intranet\Component;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Intranet\Integration\Timeman\Worktime;
use Bitrix\Main\UserTable;

class UstatOnline extends \CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{
	protected $errorCollection;

	public function __construct($component = null)
	{
		$this->errorCollection = new ErrorCollection();

		parent::__construct($component);
	}

	protected function listKeysSignedParameters()
	{
		return array();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	protected function getDefaultPreFilters()
	{
		return [ new \Bitrix\Intranet\ActionFilter\UserType(['employee']) ];
	}

	public function configureActions(): array
	{
		$defaultPrefilters = [
			new \Bitrix\Intranet\ActionFilter\UserType(['employee']),
		];

		return [
			'getAllOnlineUser' => [
				'prefilters' => $defaultPrefilters
			],
			'getOpenedTimemanUser' => [
				'prefilters' => $defaultPrefilters
			],
			'getClosedTimemanUser' => [
				'prefilters' => $defaultPrefilters
			],
			'checkTimeman' => [
				'prefilters' => $defaultPrefilters
			]
		];
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function onPrepareComponentParams($arParams)
	{
		if (!isset($this->arParams["PATH_TO_USER"]))
		{
			$arParams['PATH_TO_USER'] = SITE_DIR."company/personal/user/#user_id#/";
		}

		return parent::onPrepareComponentParams($arParams);
	}

	protected function getUserCount()
	{
		global $CACHE_MANAGER;

		$cache = new \CPHPCache;
		$cacheId = "USER_COUNT";
		$cacheDir = "/intranet/ustat_online/";

		if($cache->initCache(86400, $cacheId, $cacheDir))
		{
			$cacheVars = $cache->getVars();
			$userCount = $cacheVars["USER_COUNT"];
		}
		else
		{
			$cache->startDataCache();

			$CACHE_MANAGER->StartTagCache($cacheDir);
			$CACHE_MANAGER->RegisterTag('user_count');

			$users = UserTable::getList(array(
				"select" => ["ID"],
				"filter" => [
					"=ACTIVE" => "Y",
					"=CONFIRM_CODE" => false,
					"!UF_DEPARTMENT" => false,
					"!=EXTERNAL_AUTH_ID" => UserTable::getExternalUserTypes()
				],
				'count_total' => true,
			));

			$userCount = $users->getCount();

			$CACHE_MANAGER->EndTagCache();
			$cache->endDataCache([
				"USER_COUNT" => $userCount
			]);
		}

		return $userCount;
	}

	public function isFullAnimationMode()
	{
		if (
			!isset($this->arResult['DISPLAY_MODE'])
			|| $this->arResult['DISPLAY_MODE'] !== 'sidebar'
			|| self::getUserCount() > 100
		)
		{
			return false;
		}

		return true;
	}

	public function getCurrentOnlineUserData()
	{
		global $CACHE_MANAGER;

		$cache = new \CPHPCache;
		$cacheId = "ONLINE_USER_DATA";
		$cacheDir = "/intranet/ustat_online/";

		if($cache->initCache(300, $cacheId, $cacheDir))
		{
			$cacheVars = $cache->getVars();
			$users = $cacheVars["USERS"];
			$count = $cacheVars["COUNT"];
		}
		else
		{
			$cache->startDataCache();
			$CACHE_MANAGER->StartTagCache($cacheDir);

			$users = [];
			$date = new DateTime;

			$filter = [
				'=ACTIVE'       => true,
				'=IS_REAL_USER' => true,
				'>=LAST_ACTIVITY_DATE' => $date->add('-'.$this->getLimitOnlineSeconds().' seconds'),
				"!UF_DEPARTMENT" => false,
			];

			$select = [
				"ID", "LAST_NAME", "NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO", "LAST_ACTIVITY_DATE",
			];

			$result = UserTable::getList([
				'select' => $select,
				'filter' => $filter,
				'limit' => 7,
				'count_total' => true,
				'order' => ['LAST_ACTIVITY_DATE' => 'DESC']
			]);

			$count = $result->getCount();

			while ($user = $result->fetch())
			{
				$element = self::prepareUser($user);
				$element = array_change_key_case($element, CASE_LOWER);
				foreach ($element as $key => $value)
				{
					if ($value instanceof DateTime)
					{
						$element[$key] = date('c', $value->getTimestamp());
					}
				}
				$users[] = $element;
			}

			$CACHE_MANAGER->EndTagCache();
			$cache->endDataCache([
				"USERS" => $users,
				"COUNT" => $count
			]);
		}

		return [
			"users" => $users,
			"count" => $count
		];
	}

	public function prepareList(): array
	{
		$users = [];

		$select = [
			"ID", "LAST_NAME", "NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO", "LAST_ACTIVITY_DATE",
		];

		$date = new DateTime;

		$filter = [
			'=ACTIVE'       => true,
			'=IS_REAL_USER' => true,
			'>=LAST_ACTIVITY_DATE' => $date->add('-'.$this->arResult["LIMIT_ONLINE_SECONDS"].' seconds'),
			"!UF_DEPARTMENT" => false,
		];

		$result = UserTable::getList([
			'select' => $select,
			'filter' => $filter,
			'order' => ['LAST_ACTIVITY_DATE' => 'DESC']
		]);

		while ($user = $result->fetch())
		{
			$users[$user['ID']] = self::prepareUser($user);
			$this->arResult['ONLINE_USERS_ID'][] = $user['ID'];
		}

		if (count($users) < 10)
		{
			unset($filter['>=LAST_ACTIVITY_DATE']);
			$result = UserTable::getList([
				'select' => $select,
				'filter' => $filter,
				'limit'  => 10,
			]);

			while ($user = $result->fetch())
			{
				$users[$user['ID']] = self::prepareUser($user);
			}
		}

		return array_values($users);
	}

	private static function getAvatar($userId, $personalPhotoId)
	{
		$avatarSrc = "";

		if(defined("BX_COMP_MANAGED_CACHE"))
			$ttl = 2592000;
		else
			$ttl = 600;
		$cache_id = 'user_avatar_'.$userId;
		$cache_dir = '/bx/user_avatar_'.substr(md5($userId), -2).'/';

		$obCache = new \CPHPCache;

		if($obCache->InitCache($ttl, $cache_id, $cache_dir))
		{
			$avatarSrc = $obCache->GetVars();
		}
		else
		{
			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->StartTagCache($cache_dir);
			}

			if (intval($personalPhotoId) > 0)
			{
				$imageFile = \CFile::GetFileArray($personalPhotoId);
				if ($imageFile !== false)
				{
					$arFileTmp = \CFile::ResizeImageGet(
						$imageFile,
						array("width" => 100, "height" => 100),
						BX_RESIZE_IMAGE_EXACT,
						false
					);
					$avatarSrc = $arFileTmp["src"];
				}
			}
			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->RegisterTag("USER_NAME_".$userId);
				$CACHE_MANAGER->EndTagCache();
			}

			if($obCache->StartDataCache())
			{
				$obCache->EndDataCache($avatarSrc);
			}
		}

		return $avatarSrc;
	}

	private static function prepareUser(array $params): array
	{
		$user = [];

		$user['ID'] = (int)$params["ID"];
		$user["NAME"] = \CUser::FormatName("#NAME# #LAST_NAME#", array(
			"NAME" => $params["NAME"],
			"LAST_NAME" => $params["LAST_NAME"],
			"SECOND_NAME" => $params["SECOND_NAME"],
			"LOGIN" => $params["LOGIN"]
		));

		$user['AVATAR'] = self::getAvatar($params["ID"], $params["PERSONAL_PHOTO"]);
		$user['LAST_ACTIVITY_DATE'] = $params["LAST_ACTIVITY_DATE"];

		return $user;
	}

	public static function prepareToJson(array $list): string
	{
		foreach ($list as $index => $element)
		{
			foreach ($element as $key => $value)
			{
				if (is_array($value))
				{
					$list[$index][$key] = array_change_key_case($value, CASE_LOWER);
				}
				else if ($value instanceof DateTime)
				{
					$list[$index][$key] = date('c', $value->getTimestamp());
				}
				else
				{
					$list[$index][$key] = $value;
				}
			}

			$list[$index] = array_change_key_case($list[$index], CASE_LOWER);
		}

		return \Bitrix\Main\Web\Json::encode($list);
	}

	public function getAllOnlineUserAction($pageNum)
	{
		$pageNum = intval($pageNum);
		if ($pageNum <= 0)
		{
			$pageNum = 1;
		}

		$select = [
			"ID", "LAST_NAME", "NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO", "LAST_ACTIVITY_DATE",
		];

		$users = [];
		$pageSize = 10;
		$date = new DateTime;

		$filter = [
			'=ACTIVE'       => true,
			'=IS_REAL_USER' => true,
			'>=LAST_ACTIVITY_DATE' => $date->add('-'.$this->getLimitOnlineSeconds().' seconds'),
			"!UF_DEPARTMENT" => false,
		];

		$result = UserTable::getList([
			'select' => $select,
			'filter' => $filter,
			'limit'  => $pageSize,
			'offset' => ($pageNum - 1) * $pageSize,
			'order' => [
				'LAST_LOGIN' => 'DESC'
			],
		]);
		while ($user = $result->fetch())
		{
			$userResult = self::prepareUser($user);
			$userResult["PATH_TO_USER_PROFILE"] = \CComponentEngine::MakePathFromTemplate(
				$this->arParams["PATH_TO_USER"], array("user_id" => $user['ID'])
			);

			$users[] = $userResult;
		}

		if (!empty($users))
		{
			return $users;
		}
		else
		{
			return false;
		}
	}

	public function getTimemanUser($pageNum, $status)
	{
		$pageNum = intval($pageNum);
		if ($pageNum <= 0)
		{
			$pageNum = 1;
		}

		$userIds = Worktime::getTMUserData($status);

		$select = [
			"ID", "LAST_NAME", "NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO", "LAST_ACTIVITY_DATE",
		];

		$filter = [
			'=ACTIVE'       => true,
			'=IS_REAL_USER' => true,
			'ID' => $userIds,
			"!UF_DEPARTMENT" => false,
		];

		$users = [];

		$pageSize = 10;

		$result = UserTable::getList([
			'select' => $select,
			'filter' => $filter,
			'limit'  => $pageSize,
			'offset' => ($pageNum - 1) * $pageSize,
		]);
		while ($user = $result->fetch())
		{
			$userResult = self::prepareUser($user);
			$userResult["PATH_TO_USER_PROFILE"] = \CComponentEngine::MakePathFromTemplate(
				$this->arParams["PATH_TO_USER"], array("user_id" => $user['ID'])
			);

			$users[] = $userResult;
		}

		if (!empty($users))
		{
			return $users;
		}
		else
		{
			return false;
		}
	}

	public function getOpenedTimemanUserAction($pageNum)
	{
		$res = self::getTimemanUser($pageNum, Worktime::STATUS_OPENED);
		return $res;
	}

	public function getClosedTimemanUserAction($pageNum)
	{
		$res = self::getTimemanUser($pageNum, Worktime::STATUS_CLOSED);
		return $res;
	}

	public function checkTimemanAction()
	{
		$res = Worktime::getTMDayData();

		return $res;
	}

	public function checkMaxOnlineOption()
	{
		$newValue = [];
		$currentDate = new Date;
		$currentDate = $currentDate->getTimestamp();

		$option = Option::get('intranet', 'ustat_online_users', '');
		$isChanged = false;
		if (!empty($option))
		{
			$data = unserialize($option, ["allowed_classes" => false]);
			$newValue = $data;
			$optionDate = is_numeric($data["date"]) ? $data["date"] : 0;

			if ($currentDate > $optionDate)
			{
				$isChanged = true;
				$newValue = [
					"date" => $currentDate,
					"userIds" => $this->arResult['ONLINE_USERS_ID']
				];
			}
			else
			{
				$diffAr = array_diff($data["userIds"], $this->arResult['ONLINE_USERS_ID']);
				if (count($diffAr) > 0)
				{
					$isChanged = true;
					$newValue["userIds"] = array_unique(array_merge($data["userIds"], $this->arResult['ONLINE_USERS_ID']));
				}
			}
		}
		else
		{
			$isChanged = true;
			$newValue = [
				"date" => $currentDate,
				"userIds" => $this->arResult['ONLINE_USERS_ID']
			];
		}

		if ($isChanged)
		{
			Option::set('intranet', 'ustat_online_users', serialize($newValue));
		}

		$this->arResult["MAX_ONLINE_USER_COUNT_TODAY"] = count($newValue["userIds"]);
		$this->arResult["ALL_ONLINE_USER_ID_TODAY"] = $this->arResult['ONLINE_USERS_ID'];
	}

	public function checkTimeman():bool
	{
		$this->arResult["IS_TIMEMAN_INSTALLED"] = Loader::includeModule("timeman");
		$this->arResult["IS_FEATURE_TIMEMAN_AVAILABLE"] = true;

		if (
			$this->arResult["IS_TIMEMAN_INSTALLED"] &&
			Loader::includeModule("bitrix24")
			&& !\Bitrix\Bitrix24\Feature::isFeatureEnabled("timeman")
		)
		{
			$this->arResult["IS_FEATURE_TIMEMAN_AVAILABLE"] = false;
		}

		if ($this->arResult["IS_TIMEMAN_INSTALLED"] && $this->arResult["IS_FEATURE_TIMEMAN_AVAILABLE"])
		{
			return true;
		}

		return false;
	}

	public function prepareTimemanData()
	{
		$res = Worktime::getTMDayData();

		$this->arResult["OPENED_DAY_COUNT"] = $res["OPENED"];
		$this->arResult["CLOSED_DAY_COUNT"] = $res["CLOSED"];
	}

	public function getLimitOnlineSeconds()
	{
		return UserTable::getSecondsForLimitOnline() ?: 1440;
	}
}
