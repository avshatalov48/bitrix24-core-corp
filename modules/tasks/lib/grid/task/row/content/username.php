<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content;

use Bitrix\Main;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Grid\Task\Row\Content;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\User;

/**
 * Class UserName
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content
 */
class UserName extends Content
{
	/**
	 * @return string
	 * @throws Main\ArgumentException
	 */
	protected function prepareUserName(): string
	{
		$row = $this->getRowData();
		$parameters = $this->getParameters();

		static $cache = [];

		$userId = (int)$row[static::USER_ROLE];

		if (isset($row['MEMBERS'][static::USER_ROLE]))
		{
			$user = $row['MEMBERS'][static::USER_ROLE];
		}
		elseif (isset($cache[$userId]))
		{
			$user = $cache[$userId];
		}
		else
		{
			$select = [
				'ID',
				'PERSONAL_PHOTO',
				'LOGIN',
				'NAME',
				'LAST_NAME',
				'SECOND_NAME',
				'TITLE',
			];
			$users = User::getData([$userId], $select);
			$user = $users[$userId];

			$cache[$userId] = $user;
		}

		$user['AVATAR'] = UI::getAvatar($user['PERSONAL_PHOTO'], 100, 100);
		$user['IS_EXTERNAL'] = $user['IS_EXTRANET_USER'];
		$user['IS_CRM'] = $user['IS_CRM_EMAIL_USER'];

		$userIcon = '';
		if ($user['IS_EXTRANET_USER'])
		{
			$userIcon = ' tasks-grid-avatar-extranet';
		}
		if ($user["IS_EMAIL_USER"])
		{
			$userIcon = ' tasks-grid-avatar-mail';
		}
		if ($user["IS_CRM_EMAIL_USER"])
		{
			$userIcon = ' tasks-grid-avatar-crm';
		}

		$userAvatar = '';
		$userEmptyAvatar = ' tasks-grid-avatar-empty';

		if ($avatar = $user['AVATAR'])
		{
			$userAvatar = ' style="background-image: url(\'' . Uri::urnEncode($avatar) . '\')"';
			$userEmptyAvatar = '';
		}

		$userName = htmlspecialcharsbx(User::formatName($user));
		$userNameElement = "<span class='tasks-grid-avatar ui-icon ui-icon-common-user{$userEmptyAvatar}{$userIcon}'><i{$userAvatar}></i></span>"
			."<span class='tasks-grid-username-inner{$userIcon}'>{$userName}</span>"
			."<span class='tasks-grid-filter-remove'></span>";

		$encodedData = Json::encode([
			static::USER_ROLE => [$user['ID']],
			static::USER_ROLE.'_label' => [$userName],
		]);

		$selected = 0;
		$selector = 'tasks-grid-username';
		if (
			isset($parameters['FILTER_FIELDS'][static::USER_ROLE])
			&& is_array($parameters['FILTER_FIELDS'][static::USER_ROLE])
			&& count($parameters['FILTER_FIELDS'][static::USER_ROLE]) === 1
			&& (int)$parameters['FILTER_FIELDS'][static::USER_ROLE][0] === $userId
		)
		{
			$selected = 1;
			$selector .= ' tasks-grid-filter-active';
		}

		return "<div class='tasks-grid-username-wrapper'>"
			."<a class='$selector' onclick='BX.PreventDefault(); BX.Tasks.GridActions.toggleFilter({$encodedData}, {$selected})' href='javascript:void(0)'>{$userNameElement}</a>"
			."</div>";
	}
}