<?

use Bitrix\Intranet\Integration\Templates\Air\ChatMenu;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class IntranetChatBarComponent extends \CBitrixComponent
{
	public function executeComponent(): void
	{
		if (!$GLOBALS['USER']->isAuthorized() || !Loader::includeModule('im') || !Loader::includeModule('intranet'))
		{
			return;
		}

		$this->arResult['MENU_ITEMS'] = ChatMenu::getMenuItems();

		$this->includeComponentTemplate();
	}
}
