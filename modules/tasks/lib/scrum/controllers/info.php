<?php

namespace Bitrix\Tasks\Scrum\Controllers;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Request;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Util\User;

class Info extends Controller
{
	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->errorCollection = new ErrorCollection;
	}

	public function getTutorInfoAction(int $groupId)
	{
		if (!Loader::includeModule('tasks') || !Loader::includeModule('socialnetwork'))
		{
			return null;
		}

		$userId = User::getId();

		if (!$this->canReadGroupTasks($userId, $groupId))
		{
			return null;
		}

		$url = '';

		$portalName = (
			defined('BX24_HOST_NAME')
				? BX24_HOST_NAME
				: ((defined('SITE_SERVER_NAME') && SITE_SERVER_NAME)
					? SITE_SERVER_NAME
					: \COption::getOptionString('main', 'server_name', '')
				)
		);

		//todo move it to Bitrix\UI\Util after en url will done

		$ruUrl = 'https://helpdesk.bitrix24.ru/manual/scrum/?utm_source=portal&utm_medium=referral&utm_campaign='
			. $portalName . '&utm_content=widget';

		$uaUrl = 'https://helpdesk.bitrix24.ua/manual/scrum/?utm_source=portal&utm_medium=referral&utm_campaign='
			. $portalName . '&utm_content=widget';

		$licensePrefix = Loader::includeModule('bitrix24') ? \CBitrix24::getLicensePrefix() : '';
		$portalZone = Loader::includeModule('intranet') ? \CIntranetUtils::getPortalZone() : '';

		if (Loader::includeModule('bitrix24'))
		{
			$availableList = ['ru', 'by', 'kz', 'ur'];
			if (in_array($licensePrefix, $availableList))
			{
				$url = $ruUrl;
			}

			if ($licensePrefix === 'ua')
			{
				$url = $uaUrl;
			}
		}
		elseif (Loader::includeModule('intranet'))
		{
			if ($portalZone === 'ru')
			{
				$url = $ruUrl;
			}

			if ($portalZone === 'ua')
			{
				$url = $uaUrl;
			}
		}

		return [
			'url' => $url
		];
	}

	public function openTutorAction()
	{
		return '';
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