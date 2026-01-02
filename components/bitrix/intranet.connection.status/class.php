<?

use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class IntranetConnectionStatusComponent extends \CBitrixComponent
{
	public function executeComponent(): void
	{
		if (!$GLOBALS['USER']->isAuthorized() || !Loader::includeModule('pull'))
		{
			return;
		}

		$rootContainer = (
			isset($this->arParams['rootContainer']) && is_string($this->arParams['rootContainer'])
				? $this->arParams["rootContainer"]
				: ''
		);

		if (empty($rootContainer))
		{
			return;
		}

		$this->arResult['rootContainer'] = $rootContainer;

		$this->includeComponentTemplate();
	}
}
