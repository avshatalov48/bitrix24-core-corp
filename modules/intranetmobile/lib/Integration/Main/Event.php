<?php

namespace Bitrix\IntranetMobile\Integration\Main;

use Bitrix\Main\Config\Option;

class Event
{
	public static function onAfterUserAuthorizeHandler($params): void
	{
		if (
			!defined('BX_MOBILE')
			|| !$params['update'] // rest auth
			|| !array_key_exists('LAST_LOGIN', $params['user_fields'])
			|| $params['user_fields']['LAST_LOGIN'] !== null
			|| !array_key_exists('LAST_ACTIVITY_DATE', $params['user_fields'])
			|| $params['user_fields']['LAST_ACTIVITY_DATE'] !== null
			|| Option::get('intranetmobile', 'isMiniProfileEnabled', 'Y') !== 'Y'
			|| in_array($params['user_fields']['EXTERNAL_AUTH_ID'], \Bitrix\Main\UserTable::getExternalUserTypes())
			|| \CUserOptions::GetOption('intranetmobile', 'isMiniProfileShowed', false, $params['user_fields']['ID']) === true
		)
		{
			return;
		}

		\CUserOptions::SetOption(
			'intranetmobile',
			'isNeedToShowMiniProfile',
			true,
			false,
			$params['user_fields']['ID']
		);
	}
}