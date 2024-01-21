<?php
namespace Bitrix\Mobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Loader;

class DisabledTools extends Controller
{
	public function configureActions(): array
	{
		return [
			'getDisabledMenuItemListId' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function getDisabledMenuItemListIdAction(): ?array
	{
		if (Loader::includeModule('intranet'))
		{
			return ToolsManager::getInstance()->getDisabledMenuItemListId();
		}

		return null;
	}
}