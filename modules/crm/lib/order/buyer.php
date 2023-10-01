<?php

namespace Bitrix\Crm\Order;

use Bitrix\Main\UserTable;

class Buyer
{
	const AUTH_ID = 'shop';

	/**
	 * Event handler for buyer authorization when api checks external users.
	 * @see \CUser::Login
	 *
	 * @param $arParams
	 * @return int|null
	 */
	public static function onUserLoginExternalHandler(&$arParams)
	{
		if (isset($arParams['EXTERNAL_AUTH_ID']) && $arParams['EXTERNAL_AUTH_ID'] !== self::AUTH_ID)
		{
			return 0;
		}

		$loginParams = $arParams;
		$loginParams['EXTERNAL_AUTH_ID'] = self::AUTH_ID;

		$resultMessage = true;

		$userId = (int)\CUser::LoginInternal($loginParams, $resultMessage);

		//if ($resultMessage !== true)
		//{
		//	$arParams['RESULT_MESSAGE'] = $resultMessage;
		//}

		return $userId;
	}

	/**
	 * Event handler for buyer creation when api checks user fields.
	 * @see \CUser::CheckFields
	 *
	 * @param $fields
	 * @return bool
	 */
	public static function onBeforeUserAddHandler($fields)
	{
		if (isset($fields['EXTERNAL_AUTH_ID']) && $fields['EXTERNAL_AUTH_ID'] === self::AUTH_ID)
		{
			$errorMsg = \CUser::CheckInternalFields($fields);

			if ($errorMsg !== '')
			{
				global $APPLICATION;

				$APPLICATION->ThrowException($errorMsg);

				return false;
			}
		}

		return true;
	}

	/**
	 * Event handler for buyer editing when api checks user fields.
	 * @see \CUser::CheckFields
	 *
	 * @param $fields
	 * @return bool
	 */
	public static function onBeforeUserUpdateHandler($fields)
	{
		if (isset($fields['EXTERNAL_AUTH_ID']) && $fields['EXTERNAL_AUTH_ID'] === self::AUTH_ID)
		{
			$errorMsg = \CUser::CheckInternalFields($fields, $fields['ID']);

			if ($errorMsg !== '')
			{
				global $APPLICATION;

				$APPLICATION->ThrowException($errorMsg);

				return false;
			}
		}

		return true;
	}

	/**
	 * Event handler for buyer password restore.
	 * @see \CUser::SendPassword
	 *
	 * @param $params
	 */
	public static function onBeforeUserSendPasswordHandler(&$params)
	{
		if (isset($params['LOGIN']) && $params['LOGIN'] !== '')
		{
			$filter = [
				'=LOGIN' => $params['LOGIN'],
				'=EXTERNAL_AUTH_ID' => self::AUTH_ID,
			];
		}
		elseif (isset($params['EMAIL']) && $params['EMAIL'] !== '')
		{
			$filter = [
				'=EMAIL' => $params['EMAIL'],
				'=EXTERNAL_AUTH_ID' => self::AUTH_ID,
			];
		}

		if (!empty($filter))
		{
			$user = UserTable::getRow([
				'select' => ['ID'],
				'filter' => $filter,
			]);

			if ($user !== null)
			{
				$params['EXTERNAL_AUTH_ID'] = self::AUTH_ID;
			}
		}
	}

	/**
	 * Event handler for buyer password restore.
	 * @see \CUser::ChangePassword
	 *
	 * @param $params
	 */
	public static function OnBeforeUserChangePasswordHandler(&$params)
	{
		static::onBeforeUserSendPasswordHandler($params);
	}

	/**
	 * Event handler for buyer password restore.
	 * @see \CUser::SendUserInfo
	 *
	 * @param $params
	 */
	public static function OnBeforeSendUserInfoHandler(&$params)
	{
		if (isset($params['ID']) && $params['ID'] !== '')
		{
			$user = UserTable::getRow([
				'select' => ['ID'],
				'filter' => [
					'=ID' => $params['ID'],
					'=EXTERNAL_AUTH_ID' => self::AUTH_ID,
				],
			]);

			if ($user !== null)
			{
				$params['EXTERNAL_AUTH_ID'] = self::AUTH_ID;
			}
		}
	}
}