<?php
namespace Bitrix\Tasks\Grid\Row\Content;

use Bitrix\Main;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Grid\Row\Content;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\User;

/**
 * Class UserName
 *
 * @package Bitrix\Tasks\Grid\Row\Content
 */
class UserName extends Content
{
	/**
	 * @param array $parameters
	 * @return string
	 * @throws Main\ArgumentException
	 */
	protected static function prepareUserName(array $parameters): string
	{
		static $cache = [];

		$userId = $parameters['USER_ID'];

		if (!is_array($parameters))
		{
			return '';
		}

		if (isset($cache[$userId]))
		{
			$user = $cache[$userId];
		}
		else
		{
			$users = User::getData([$userId]);
			$user = $users[$userId];

			$cache[$userId] = $user;
		}

		$user['AVATAR'] = UI::getAvatar($user['PERSONAL_PHOTO'], 100, 100);
		$user['IS_EXTERNAL'] = User::isExternalUser($user['ID']);
		$user['IS_CRM'] = array_key_exists('UF_USER_CRM_ENTITY', $user) && !empty($user['UF_USER_CRM_ENTITY']);

		$userIcon = '';
		if ($user['IS_EXTERNAL'])
		{
			$userIcon = ' tasks-grid-avatar-extranet';
		}
		if ($user["EXTERNAL_AUTH_ID"] === 'email')
		{
			$userIcon = ' tasks-grid-avatar-mail';
		}
		if ($user["IS_CRM"])
		{
			$userIcon = ' tasks-grid-avatar-crm';
		}

		$userAvatar = '';
		$userEmptyAvatar = ' tasks-grid-avatar-empty';

		if ($avatar = $user['AVATAR'])
		{
			$userAvatar = " style='background-image: url(\"{$avatar}\")'";
			$userEmptyAvatar = '';
		}

		$userRole = $parameters['USER_ROLE'];
		$userName = htmlspecialcharsbx(User::formatName($user));
		$userNameElement = "<span class='tasks-grid-avatar{$userEmptyAvatar}{$userIcon}'{$userAvatar}></span>"
			."<span class='tasks-grid-username-inner{$userIcon}'>{$userName}</span>";
		$encodedData = Json::encode([$userRole => $user['ID'], $userRole.'_label' => $userName]);

		return "<div class='tasks-grid-username-wrapper'>"
			."<a class='tasks-grid-username' onclick='BX.PreventDefault(); BX.Tasks.GridActions.filter({$encodedData})' href='javascript:void(0)'>{$userNameElement}</a>"
			."</div>";
	}
}