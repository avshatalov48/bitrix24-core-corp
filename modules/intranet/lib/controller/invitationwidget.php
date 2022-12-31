<?php

namespace Bitrix\Intranet\Controller;

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Intranet;
use Bitrix\Main;

class InvitationWidget extends Main\Engine\Controller
{
	public function configureActions()
	{
		$configureActions = parent::configureActions();

		$accessControl = new Intranet\ActionFilter\InviteAccessControl();
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

	protected function isCurrentUserAdmin()
	{
		return (
			(
				Loader::includeModule('bitrix24')
				&& \CBitrix24::IsPortalAdmin(CurrentUser::get()->getId())
			)
			|| CurrentUser::get()->isAdmin()
		);
	}

	public function getDataAction()
	{
		$invitationLink = \Bitrix\Main\Engine\UrlManager::getInstance()->create('getSliderContent', [
			'c' => 'bitrix:intranet.invitation',
			'mode' => \Bitrix\Main\Engine\Router::COMPONENT_MODE_AJAX,
			'analyticsLabel[source]' => 'headerPopup',
		]);

		$currentUserCount = 0;
		$currentExtranetUserCount = 0;
		$maxUserCount = 0;
		$isInvitationAvailable = true;

		if (Loader::includeModule('bitrix24'))
		{
			$isInvitationAvailable = \CBitrix24::isInvitingUsersAllowed();

			$currentUserCount = \CBitrix24::getActiveUserCount();

			if (\CBitrix24BusinessTools::isAvailable())
			{
				$maxUserCount = 0;
			}
			else
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
			'invitationLink' => $isInvitationAvailable ? $invitationLink : '',
			'structureLink' => '/company/vis_structure.php',
			'isInvitationAvailable' => $isInvitationAvailable,
			'isExtranetAvailable' => ModuleManager::isModuleInstalled('extranet'),
			'users' => [
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

	public function analyticsLabelAction()
	{

	}

	public function saveInvitationRightAction($type)
	{
		if (!$this->isCurrentUserAdmin())
		{
			return null;
		}

		Option::set('bitrix24', 'allow_invite_users', ($type === 'all' ? 'Y' : 'N'));
	}

	public function getInvitationRightAction()
	{
		$rightSetting = Option::get('bitrix24', 'allow_invite_users', 'N');

		return ($rightSetting === 'N' ? 'admin' : 'all');
	}

	public function getUserOnlineComponentAction()
	{
		$componentName = 'bitrix:intranet.ustat.online';

		$params = [
			'MODE' => 'popup',
			'MAX_USER_TO_SHOW' => 9,
		];

		return new \Bitrix\Main\Engine\Response\Component($componentName, '', $params, []);
	}
}
