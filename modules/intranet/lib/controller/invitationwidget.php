<?php

namespace Bitrix\Intranet\Controller;

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Intranet;
use Bitrix\Main\Engine;
use Bitrix\Main\Engine\Response;

class InvitationWidget extends Engine\Controller
{

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

		$collabFeatureOn = Loader::includeModule('socialnetwork') &&
			\Bitrix\Socialnetwork\Collab\CollabFeature::isOn();
		$intranetUser = new Intranet\User();

		return [
			'isCurrentUserAdmin' => $intranetUser->isAdmin(),
			'isInvitationAvailable' => \CBitrix24::isInvitingUsersAllowed(),
			'structureLink' => '/company/vis_structure.php',
			'invitationLink' => Intranet\CurrentUser::get()->isAdmin() || \CBitrix24::isInvitingUsersAllowed()
				? Engine\UrlManager::getInstance()->create('getSliderContent', [
					'c' => 'bitrix:intranet.invitation',
					'mode' => Engine\Router::COMPONENT_MODE_AJAX,
					'analyticsLabel[source]' => 'headerPopup',
				]) : '',
			'isExtranetAvailable' => Loader::includeModule('extranet') && !$collabFeatureOn,
			'isCollabAvailable' => Loader::includeModule('extranet')
				&& $collabFeatureOn
				&& ToolsManager::getInstance()->checkAvailabilityByToolId('collab'),
			'invitationCounter' => $intranetUser->getTotalInvitationCounterValue(),
			'counterId' => Intranet\Invitation::getTotalInvitationCounterId(),
			'shouldShowStructureCounter' => $this->shouldShowStructureCounter(),
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

	public function analyticsLabelAction(): void
	{

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

	private function shouldShowStructureCounter(): bool
	{
		return
			(bool)Option::get('humanresources', 'public_structure_is_available', false) === true
			&& \CUserOptions::GetOption("humanresources", 'first_time_opened', 'N') === 'N'
			;
	}
}