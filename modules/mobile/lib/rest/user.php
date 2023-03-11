<?php

namespace Bitrix\Mobile\Rest;
use Bitrix\Intranet\Util;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UserTable;
use Bitrix\Voximplant\Security\Helper;
use CFile;
use CHTTP;
use CIBlockSection;
use CIntranetUtils;
use CModule;
use CRestServer;
use CSite;
use CUser;

class User extends \IRestService
{
	public static function getMethods()
	{
		return [
			'mobile.user.get' => ['callback' => [__CLASS__, 'get'], 'options' => ['private' => false]],
            'mobile.user.update' => ['callback' => [__CLASS__, 'userUpdate'], 'options' => ['private' => false]],
			'mobile.user.canUseTelephony' => ['callback' => [__CLASS__, 'canUseTelephony'], 'options' => ['private' => false]],
			'mobile.user.canEditUsers' => ['callback' => [__CLASS__, 'canEditUsers'], 'options' => ['private' => false]],
			'mobile.user.setAdminRights' => ['callback' => [__CLASS__, 'setAdminRights'], 'options' => ['private' => false]],
			'mobile.user.removeAdminRights' => ['callback' => [__CLASS__, 'removeAdminRights'], 'options' => ['private' => false]],
			'mobile.user.fireUser' => ['callback' => [__CLASS__, 'fireUser'], 'options' => ['private' => false]],
			'mobile.user.hireUser' => ['callback' => [__CLASS__, 'hireUser'], 'options' => ['private' => false]],
		];
	}

	public static function get($params, $n, CRestServer $server)
	{
		global $USER;

		$users = null;
		$error = null;
		$filter = [];

		//getting resize preset before user data preparing
		$resizePresets = [
			'small' => ['width' => 150, 'height' => 150],
			'medium' => ['width' => 300, 'height' => 300],
			'large' => ['width' => 1000, 'height' => 1000],
		];

		$presetName = $params['image_resize'];
		unset($params['image_resize']);
		$resize = ($presetName && $resizePresets[$presetName]
			? $resizePresets[$presetName]
			: false);

        if (is_array($params['filter']))
        {
            $filterCallback = function ($key) {
                return in_array(mb_strtoupper($key), self::allowedFields());
            };
            $filter = array_filter($params['filter'], $filterCallback , ARRAY_FILTER_USE_KEY);
        }

		if (Loader::includeModule('extranet') )
		{
			$filteredUserIDs = \CExtranet::getMyGroupsUsersSimple(\CExtranet::getExtranetSiteID());
			$filteredUserIDs[] = $USER->getID();

			if (\CExtranet::isIntranetUser())
			{
				if (
					!isset($filter["ID"])
					|| !Loader::includeModule('socialnetwork')
					|| !\CSocNetUser::IsCurrentUserModuleAdmin(\CSite::getDefSite(), false)
				)
				{
					$filter[] = [
						'LOGIC' => 'OR',
						'!UF_DEPARTMENT' => false,
						'ID' => $filteredUserIDs
					];
				}
			}
			else
			{
				$filter['ID'] = (isset($filter['ID'])
					? array_intersect((is_array($filter['ID'])
						? $filter['ID']
						: [$filter['ID']]), $filteredUserIDs)
					: $filteredUserIDs);
			}
		}

		$dbUsers = UserTable::getList([
			'filter' => $filter,
			'select' => self::allowedFields(),
			'limit' => 10,
			'offset' => 0
		]);

		$users = $dbUsers->fetchAll();

		if ($users == null)
		{
			return [];
		}

		foreach ($users as $index => $user)
		{
			if ($user['PERSONAL_PHOTO'])
			{
				$photo = self::getUserPhoto($user['PERSONAL_PHOTO'], $resize);
				$users[$index]['PERSONAL_PHOTO_ORIGINAL'] = $photo['PERSONAL_PHOTO_ORIGINAL'];
				$users[$index]['PERSONAL_PHOTO'] = $photo['PERSONAL_PHOTO'];
			}


			$depsData = self::userDepartmentData($user['ID'], $user['UF_DEPARTMENT'], $resize);
			if ($user['PERSONAL_BIRTHDAY'])
			{
				$format = 'j F Y';
				if (
					$USER->getId() != $user['ID']
					&& Option::get('intranet', 'show_year_for_female', 'N') === 'N'
					&& $user['PERSONAL_GENDER'] == 'F')
				{
					$format = 'j F';
				}

				$date = FormatDate($format, $user['PERSONAL_BIRTHDAY']->getTimestamp());
				$users[$index]['PERSONAL_BIRTHDAY'] = $date;
			}

			$users[$index]['NAME_FORMATTED'] = CUser::FormatName(CSite::GetNameFormat(false), $user);
			$users[$index]['COMPANY_STRUCTURE_RELATIONS'] = $depsData;

			if(Loader::includeModule('intranet'))
			{
				$status = Util::getUserStatus($user['ID']);
				if($status)
				{
					$users[$index]['STATUS'] = $status;
					$status = toUpper($status);
					$users[$index]['STATUS_NAME'] = Loc::getMessage("MOBILE_REST_USER_STATUS_{$status}");
				}
			}
		}

		$result = $users;

		return $result;
	}

    public static function userUpdate($userFields)
    {
        if (array_key_exists('PERSONAL_BIRTHDAY', $userFields))
        {
            $date = new Date($userFields['PERSONAL_BIRTHDAY'], 'd.m.Y');
            $userFields['PERSONAL_BIRTHDAY'] = $date;
        }

		return \Bitrix\Rest\Api\User::userUpdate($userFields);
    }


    public static function canUseTelephony($params, $n, CRestServer $server)
	{
		return Loader::includeModule('voximplant') && Helper::canCurrentUserPerformCalls();
	}

	public static function canEditUsers($params, $n, CRestServer $server)
	{
		return Loader::includeModule('intranet') && Util::isCurrentUserAdmin();
	}

	public static function setAdminRights($params, $n, CRestServer $server)
	{
		$userId = null;
		if (isset($params['id']))
		{
			$userId = intval($params['id']);
			if ($userId <= 0)
			{
				throw new Bitrix\Rest\RestException("User ID can't be empty", 'ID_EMPTY', CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		if (Loader::includeModule('intranet'))
		{
			$currentUser = CurrentUser::get();

			Util::setAdminRights([
				'userId' => $userId,
				'currentUserId' => $currentUser->getId(),
				'isCurrentUserAdmin' => $currentUser->isAdmin()
			]);
		}
	}

	public static function fireUser($params, $n, CRestServer $server)
	{
		$userId = null;
		if (isset($params['id']))
		{
			$userId = intval($params['id']);
			if ($userId <= 0)
			{
				throw new Bitrix\Rest\RestException("User ID can't be empty", 'ID_EMPTY', CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		if (Loader::includeModule('intranet'))
		{
			$currentUser = CurrentUser::get();

			Util::deactivateUser([
				'userId' => $userId,
				'currentUserId' => $currentUser->getId(),
				'isCurrentUserAdmin' => $currentUser->isAdmin()
			]);
		}
	}

	public static function hireUser($params, $n, CRestServer $server)
	{
		$userId = null;
		if (isset($params['id']))
		{
			$userId = intval($params['id']);
			if ($userId <= 0)
			{
				throw new Bitrix\Rest\RestException("User ID can't be empty", 'ID_EMPTY', CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		if (Loader::includeModule('intranet'))
		{
			$currentUser = CurrentUser::get();

			Util::activateUser([
				'userId' => $userId,
				'currentUserId' => $currentUser->getId(),
				'isCurrentUserAdmin' => $currentUser->isAdmin()
			]);
		}
	}

	public static function removeAdminRights($params, $n, CRestServer $server)
	{
		$userId = null;
		if (isset($params['id']))
		{
			$userId = intval($params['id']);
			if ($userId <= 0)
			{
				throw new Bitrix\Rest\RestException("User ID can't be empty", 'ID_EMPTY', CRestServer::STATUS_WRONG_REQUEST);
			}
		}

		if (Loader::includeModule('intranet'))
		{
			$currentUser = CurrentUser::get();

			Util::removeAdminRights([
				'userId' => $userId,
				'currentUserId' => $currentUser->getId(),
				'isCurrentUserAdmin' => $currentUser->isAdmin()
			]);
		}
	}

	public static function allowedFields()
	{
		global $USER_FIELD_MANAGER;

		$userFieldList= $USER_FIELD_MANAGER->GetUserFields('USER');
		$allowedUserFields = array_intersect([
            'UF_PHONE_INNER',
            'UF_SKYPE',
            'UF_TWITTER',
            'UF_FACEBOOK',
            'UF_LINKEDIN',
            'UF_XING',
            'UF_SKILLS',
            'UF_INTERESTS',
            'UF_WEB_SITES',
            'UF_DEPARTMENT'
		], array_keys($userFieldList));

		return array_merge([
            'ID',
            'NAME',
            'SECOND_NAME',
            'LAST_NAME',
            'EMAIL',
            'PERSONAL_BIRTHDAY',
            'PERSONAL_WWW',
            'PERSONAL_GENDER',
            'PERSONAL_PHOTO',
            'PERSONAL_MOBILE',
            'PERSONAL_PHONE',
            'PERSONAL_CITY',
            'WORK_PHONE',
            'WORK_POSITION',
            'EXTERNAL_AUTH_ID'
		], $allowedUserFields);
	}


	private static function getUserPhoto($id, $size)
	{
		$result = ['PERSONAL_PHOTO' => ''];
		if ($id)
		{
			$dbRes = CFile::GetList([], ['@ID' => $id]);
			if($arRes = $dbRes->Fetch())
			{
				if($size)
				{
					$fileArray = CFile::GetFileArray($id);
					$resizeResult = CFile::ResizeImageGet($fileArray, $size, BX_RESIZE_IMAGE_EXACT, false, false, false, 70);
					$result['PERSONAL_PHOTO'] = CHTTP::URN2URI($resizeResult['src']);
				}

				$result['PERSONAL_PHOTO_ORIGINAL'] = CHTTP::URN2URI(CFile::GetFileSrc($arRes));
			}
		}

		return $result;
	}

	private static function userDepartmentData($userId, $departmentIDs = [], $photoSize = false)
	{
		$data = [
			'HEAD_DATA' => [],
			'EMPLOYEES' => [],
		];
		$deps = self::departmentGet($departmentIDs);
		$data['DEPARTMENTS'] = implode(', ', $deps);
		$heads = array_values(CIntranetUtils::GetDepartmentManager($departmentIDs, $userId, true));
		if(!empty($heads[0])) {
			$data['HEAD'] = CUser::FormatName(CSite::GetNameFormat(false), $heads[0]);
			$photoData = self::getUserPhoto($heads[0]['PERSONAL_PHOTO'], $photoSize);
			$headData = [
				'name' => $data['HEAD'],
				'id' => $heads[0]['ID'],
				'position' => $heads[0]['WORK_POSITION'],
			];
			$data['HEAD_DATA'] = array_merge($headData, $photoData);
		}

		$data['EMPLOYEES_LIST'] = "";

		if (Loader::includeModule('extranet') && \CExtranet::isIntranetUser() === true)
		{
			$employees = CIntranetUtils::getSubordinateEmployees($userId);
			$employeeList = [];
			while ($employee = $employees->fetch())
			{
				if($userId == $employee['ID'])
					continue;

				$photos = self::getUserPhoto($employee['PERSONAL_PHOTO'], $photoSize);
				$employeeData = [
					'name' => CUser::FormatName(CSite::GetNameFormat(false), $employee),
					'userId' => $employee['ID'],
					'imageUrl' => $photos['PERSONAL_PHOTO'],

				];

				$data['EMPLOYEES'][] = $employeeData;
				$employeeList[] = $employeeData['name'];
			}

			if(!empty($employeeList))
				$data['EMPLOYEES_LIST'] = implode(', ', $employeeList);
		}

		return $data;
	}

	private static function departmentGet($departmentIDs = [])
	{
		CModule::IncludeModule('iblock');
		$depsChains = [];

		foreach ($departmentIDs as $departmentID)
		{
			$departmentTree = CIBlockSection::GetNavChain(Option::get('intranet', 'iblock_structure', 0), $departmentID, ['ID', 'NAME', 'DEPTH_LEVEL']);
			$chain = [];
			while ($department = $departmentTree->Fetch())
			{
				if ($department['DEPTH_LEVEL'] == 1)
				{
					continue;
				}

				$chain[] = $department['NAME'];
			}

			$depsChains[] = implode(' - ', $chain);
		}

		return $depsChains;

	}


}