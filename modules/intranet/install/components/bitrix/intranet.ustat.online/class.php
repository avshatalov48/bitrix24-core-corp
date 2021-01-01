<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use \Bitrix\Main\Type\Date;
use \Bitrix\Main\Type\DateTime;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Intranet\Integration\Timeman\Worktime;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

class CIntranetUstatOnlineComponent extends CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{
	protected $errorCollection;

	public function __construct($component = null)
	{
		$this->errorCollection = new ErrorCollection();

		parent::__construct($component);
	}

	public function onPrepareComponentParams($arParams)
	{
		if (!isset($this->arParams["PATH_TO_USER"]))
		{
			$arParams['PATH_TO_USER'] = SITE_DIR."company/personal/user/#user_id#/";
		}

		return parent::onPrepareComponentParams($arParams);
	}

	protected function listKeysSignedParameters()
	{
		return array();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	public function configureActions()
	{
		return array();
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	private function prepareList(): array
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

		$result = \Bitrix\Main\UserTable::getList([
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
			$result = \Bitrix\Main\UserTable::getList([
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
		$cache_dir = '/bx/user_avatar';
		$obCache = new CPHPCache;

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
				$imageFile = CFile::GetFileArray($personalPhotoId);
				if ($imageFile !== false)
				{
					$arFileTmp = CFile::ResizeImageGet(
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
				$CACHE_MANAGER->RegisterTag("USER_CARD_".intval($userId / TAGGED_user_card_size));
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
		$user["NAME"] = CUser::FormatName("#NAME# #LAST_NAME#", array(
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
				else if ($value instanceof \Bitrix\Main\Type\DateTime)
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

		$result = \Bitrix\Main\UserTable::getList([
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
			$userResult["PATH_TO_USER_PROFILE"] = CComponentEngine::MakePathFromTemplate(
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

		$result = \Bitrix\Main\UserTable::getList([
			'select' => $select,
			'filter' => $filter,
			'limit'  => $pageSize,
			'offset' => ($pageNum - 1) * $pageSize,
		]);
		while ($user = $result->fetch())
		{
			$userResult = self::prepareUser($user);
			$userResult["PATH_TO_USER_PROFILE"] = CComponentEngine::MakePathFromTemplate(
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
		$res = Bitrix\Intranet\Integration\Timeman\Worktime::getTMDayData();

		return $res;
	}

	private function checkMaxOnlineOption()
	{
		$newValue = [];
		$currentDate = new Date;
		$currentDate = $currentDate->getTimestamp();

		$option = Option::get('intranet', 'ustat_online_users', '');
		if (!empty($option))
		{
			$data = unserialize($option);
			$newValue = $data;
			$optionDate = is_numeric($data["date"]) ? $data["date"] : 0;

			if ($currentDate > $optionDate)
			{
				$newValue = [
					"date" => $currentDate,
					"userIds" => $this->arResult['ONLINE_USERS_ID']
				];
			}
			else
			{
				$newValue["userIds"] = array_unique(array_merge($data["userIds"], $this->arResult['ONLINE_USERS_ID']));
			}
		}
		else
		{
			$newValue = [
				"date" => $currentDate,
				"userIds" => $this->arResult['ONLINE_USERS_ID']
			];
		}

		Option::set('intranet', 'ustat_online_users', serialize($newValue));

		$this->arResult["MAX_ONLINE_USER_COUNT_TODAY"] = count($newValue["userIds"]);
		$this->arResult["ALL_ONLINE_USER_ID_TODAY"] = $this->arResult['ONLINE_USERS_ID'];
	}

	private function checkTimeman():bool
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

	private function prepareTimemanData()
	{
		$res = Bitrix\Intranet\Integration\Timeman\Worktime::getTMDayData();

		$this->arResult["OPENED_DAY_COUNT"] = $res["OPENED"];
		$this->arResult["CLOSED_DAY_COUNT"] = $res["CLOSED"];
	}

	private function getLimitOnlineSeconds()
	{
		return \Bitrix\Main\UserTable::getSecondsForLimitOnline() ?: 1440;
	}

	public function executeComponent(): void
	{
		if (!$this->checkModules())
		{
			$this->showErrors();

			return;
		}

		$this->arResult["LIMIT_ONLINE_SECONDS"] = $this->getLimitOnlineSeconds();

		$this->arResult['ONLINE_USERS_ID'] = [];
		$this->arResult['USERS'] = self::prepareToJson(
			$this->prepareList()
		);

		$this->checkMaxOnlineOption();

		if ($this->checkTimeman())
		{
			$this->prepareTimemanData();
		}

		$this->includeComponentTemplate();

		return;
	}


	/* utils functions */

	protected function checkModules(): bool
	{
		if (!Loader::includeModule('pull'))
		{
			$this->errors[] = Loc::getMessage('INTRANET_USTAT_ONLINE_COMPONENT_MODULE_NOT_INSTALLED');

			return false;
		}

		return true;
	}

	protected function hasErrors(): bool
	{
		return (count($this->errors) > 0);
	}

	protected function showErrors(): void
	{
		if (count($this->errors) <= 0)
		{
			return;
		}

		foreach ($this->errors as $error)
		{
			ShowError($error);
		}

		return;
	}
}