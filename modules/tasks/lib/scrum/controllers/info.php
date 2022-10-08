<?php

namespace Bitrix\Tasks\Scrum\Controllers;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\ScrumLimit;
use Bitrix\Tasks\Util\User;

class Info extends Controller
{
	public function getTutorInfoAction(int $groupId)
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return null;
		}

		$userId = User::getId();

		if (!$this->canReadGroupTasks($userId, $groupId))
		{
			return null;
		}

		$portalName = (
			defined('BX24_HOST_NAME')
				? BX24_HOST_NAME
				: ((defined('SITE_SERVER_NAME') && SITE_SERVER_NAME)
					? SITE_SERVER_NAME
					: \COption::getOptionString('main', 'server_name', '')
				)
		);

		return [
			'urlParams' => [
				'utm_source' => 'portal',
				'utm_medium' => 'referral',
				'utm_campaign' => $portalName,
				'utm_content' => 'widget',
			],
		];
	}

	public function saveAnalyticsLabelAction()
	{
		return '';
	}

	public function checkScrumLimitAction(): bool
	{
		return ScrumLimit::isLimitExceeded();
	}

	public function saveScrumStartAction()
	{
		return '';
	}

	private function canReadGroupTasks(int $userId, int $groupId): bool
	{
		return Group::canReadGroupTasks($userId, $groupId);
	}
}