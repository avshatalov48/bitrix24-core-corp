<?php

namespace Bitrix\Intranet\Controller;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Intranet;
use Bitrix\Main\Engine;
use Bitrix\Main\Engine\Response;

class InvitationWidget extends Engine\Controller
{
	public function configureActions(): array
	{
		$configureActions = parent::configureActions();

		$accessControl = new Intranet\ActionFilter\InviteIntranetAccessControl();
		$configureActions['saveInvitationRight'] = [
			'+prefilters' => [
				$accessControl
			]
		];

		return $configureActions;
	}

	protected function getDefaultPreFilters()
	{
		$preFilters = parent::getDefaultPreFilters();
		$preFilters[] = new Intranet\ActionFilter\UserType(['employee']);

		return $preFilters;
	}

	public function getDataAction(): array
	{
		$currentUserCount = 0;
		$currentExtranetUserCount = 0;
		$maxUserCount = 0;
		$currentExtranetUserCountMessage = '';

		if (Loader::includeModule('bitrix24'))
		{
			$currentUserCount = \CBitrix24::getActiveUserCount();

			if (!\CBitrix24BusinessTools::isAvailable())
			{
				$maxUserCount = \CBitrix24::getMaxBitrix24UsersCount();
			}

			$currentExtranetUserCount = \CBitrix24::getActiveExtranetUserCount();
			$currentExtranetUserCountMessage = Loc::getMessage('INTRANET_INVITATION_WIDGET_USER_COUNT_EXTRANET', [
				'#COUNT#' => $currentExtranetUserCount
			]);
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
			'users' => [
				'rightType' => $this->getInvitationRight(),
				'currentUserCountMessage' => $currentUserCountMessage,
				'currentUserCount' => $currentUserCount,
				'currentExtranetUserCountMessage' => $currentExtranetUserCountMessage,
				'currentExtranetUserCount' => $currentExtranetUserCount,
				'leftCountMessage' => $leftCountMessage,
				'maxUserCount' => $maxUserCount,
				'isLimit' => $maxUserCount > 0 && $currentUserCount > $maxUserCount,
			],
		];
	}

	public function analyticsLabelAction(): void
	{

	}

	public function saveInvitationRightAction($type): void
	{
		if (!Intranet\CurrentUser::get()->isAdmin())
		{
			return;
		}

		Option::set('bitrix24', 'allow_invite_users', ($type === 'all' ? 'Y' : 'N'));
	}

	public function getInvitationRight(): string
	{
		$rightSetting = Option::get('bitrix24', 'allow_invite_users', 'N');

		return ($rightSetting === 'N' ? 'admin' : 'all');
	}

	public function getUserOnlineComponentAction(): Response\Component
	{
		$componentName = 'bitrix:intranet.ustat.online';

		$params = [
			'MODE' => 'popup',
			'MAX_USER_TO_SHOW' => 9,
		];

		return new Response\Component($componentName, '', $params, []);
	}
}
