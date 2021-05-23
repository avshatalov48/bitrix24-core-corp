<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Intranet\Component\UstatOnline;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

class CIntranetUstatOnlineComponent extends UstatOnline
{
	public function executeComponent(): void
	{
		if (!$this->checkModules())
		{
			$this->showErrors();
			return;
		}

		$this->arResult["LIMIT_ONLINE_SECONDS"] = $this->getLimitOnlineSeconds();
		$this->arResult["IS_FULL_ANIMATION_MODE"] = self::isFullAnimationMode();

		$this->arResult['ONLINE_USERS_ID'] = [];
		$this->arResult['USERS'] = self::prepareToJson(
			$this->prepareList()
		);

		$this->checkMaxOnlineOption();

		if ($this->checkTimeman())
		{
			$this->prepareTimemanData();
		}

		$this->includeComponentTemplate();

		return;
	}


	/* utils functions */

	protected function checkModules(): bool
	{
		if (!Loader::includeModule('pull'))
		{
			$this->errors[] = Loc::getMessage('INTRANET_USTAT_ONLINE_COMPONENT_MODULE_NOT_INSTALLED');

			return false;
		}

		return true;
	}

	protected function hasErrors(): bool
	{
		return (count($this->errors) > 0);
	}

	protected function showErrors(): void
	{
		if (count($this->errors) <= 0)
		{
			return;
		}

		foreach ($this->errors as $error)
		{
			ShowError($error);
		}

		return;
	}
}