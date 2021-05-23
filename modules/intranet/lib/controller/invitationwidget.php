<?php

namespace Bitrix\Intranet\Controller;

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

class InvitationWidget extends \Bitrix\Main\Engine\Controller
{
	public function getDataAction()
	{
		$invitationLink = \Bitrix\Main\Engine\UrlManager::getInstance()->create('getSliderContent', [
			'c' => 'bitrix:intranet.invitation',
			'mode' => \Bitrix\Main\Engine\Router::COMPONENT_MODE_AJAX,
			'analyticsLabel[headerPopup]' => 'Y',
		]);

		$currentUserCount = 0;
		$maxUserCount = 0;
		$isInvaitationAvailable = true;

		if (Loader::includeModule('bitrix24'))
		{
			$isInvitationAvailable = \CBitrix24::isInvitingUsersAllowed();

			$currentUserCount = \CBitrix24::getActiveUserCount();

			if (\CBitrix24BusinessTools::isAvailable())
			{
				$maxUserCount = \CBitrix24::getMaxBusinessUsersCount();
			}
			else
			{
				$maxUserCount = \CBitrix24::getMaxBitrix24UsersCount();
			}
		}

		$leftCountMessage = "";

		if ($maxUserCount > 0)
		{
			$currentUserCountMessage = Loc::getMessage('INTRANET_INVITATION_WIDGET_USER_COUNT', [
				'#CURRENT_COUNT#' => $currentUserCount,
				'#MAX_COUNT#' => $maxUserCount,
			]);

			if ($maxUserCount >= $currentUserCount)
			{
				$leftCountMessage = Loc::getMessage('INTRANET_INVITATION_WIDGET_USER_COUNT_LEFT', [
					'#COUNT#' => $maxUserCount - $currentUserCount
				]);
			}
		}
		else
		{
			$currentUserCountMessage = Loc::getMessage('INTRANET_INVITATION_WIDGET_USER_COUNT_EMPLOYEES', [
				'#COUNT#' => $currentUserCount
			]);
		}

		return [
			'invitationLink' => $invitationLink,
			'structureLink' => '/company/vis_structure.php',
			'isInvitationAvailable' => $isInvitationAvailable,
			'users' => [
				'currentUserCountMessage' => $currentUserCountMessage,
				'currentUserCount' => $currentUserCount,
				'leftCountMessage' => $leftCountMessage,
				'maxUserCount' => $maxUserCount,
				'isLimit' => $maxUserCount > 0 && $currentUserCount > $maxUserCount,
			],
		];
	}

	public function analyticsLabelAction()
	{

	}
}
