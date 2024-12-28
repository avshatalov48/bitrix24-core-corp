<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\AI\Enum\LibraryType;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class AiLibraryTopMenuComponent extends CBitrixComponent
{
	public function executeComponent(): void
	{
		$this->getMenuItems();
		$this->includeComponentTemplate();
	}

	private function getMenuItems(): void
	{
		$promptUrl = '/bitrix/components/bitrix/ai.prompt.library.grid/slider.php?IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER';
		$roleUrl = '/bitrix/components/bitrix/ai.role.library.grid/slider.php?IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER';

		$this->arResult = [
			'menuItems' => [
				[
					'TEXT' => Loc::getMessage('PROMPT_LIBRARY_TITLE'),
					'URL' => $promptUrl,
					'ID' => 'prompt_library',
					'IS_ACTIVE' => $this->arParams['parent'] === LibraryType::PromptLibrary->value,
					'ON_CLICK' => "BX.AI.Top.Menu.Controller.sendAnalytics('prompt_saving','{$promptUrl}')",
				],
				[
					'TEXT' => Loc::getMessage('ROLE_LIBRARY_TITLE'),
					'URL' => $roleUrl,
					'ID' => 'role_library',
					'IS_ACTIVE' => $this->arParams['parent'] === LibraryType::RoleLibrary->value,
					'ON_CLICK' => "BX.AI.Top.Menu.Controller.sendAnalytics('roles_saving','{$roleUrl}')",
				],
			],
		];
	}
}
