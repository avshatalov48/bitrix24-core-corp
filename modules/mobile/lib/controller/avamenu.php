<?php

namespace Bitrix\Mobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use Bitrix\Mobile\AvaMenu\Items\GoToWeb;

class AvaMenu extends Controller
{
	public function configureActions(): array
	{
		return [
			'setAhaMomentStatus' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'getAhaMomentStatus' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'getUserInfo' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'setShouldShowGoToWebCounter' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function setAhaMomentStatusAction(string $shouldBeShown)
	{
		\CUserOptions::SetOption('mobile', 'avamenu_aha-moment_enabled_v2', $shouldBeShown);
	}

	public function getAhaMomentStatusAction()
	{
		return \CUserOptions::GetOption('mobile', 'avamenu_aha-moment_enabled_v2', 'Y');
	}

	public function getUserInfoAction($reloadFromDb = false)
	{
		return (new \Bitrix\Mobile\AvaMenu\Profile\Profile())->getMainData($reloadFromDb);
	}

	public function setShouldShowGoToWebCounterAction(string $option): void
	{
		GoToWeb::setShouldShowGoToWebCounter($option === 'Y');
	}
}
