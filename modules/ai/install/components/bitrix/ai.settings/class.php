<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\AI\Facade\User;
use Bitrix\AI\Tuning\Manager;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;

class AiSettingsComponent extends \CBitrixComponent
{
	/**
	 * Saves all settings and return true if this is occurred.
	 *
	 * @return bool
	 */
	private function save(): bool
	{
		$context = Application::getInstance()->getContext();
		$request = $context->getRequest();

		if ($request->get('save') && is_array($request->get('items')) && check_bitrix_sessid())
		{
			$config = new Manager();
			foreach ($request->get('items') as $code => $value)
			{
				$config->getItem($code)?->setValue($value);
			}
			$config->save();

			return true;
		}

		return false;
	}

	/**
	 * Base executable method.
	 *
	 * @return void
	 */
	public function executeComponent(): void
	{
		if (!Loader::includeModule('ai'))
		{
			return;
		}
		if (!User::isAdmin())
		{
			return;
		}

		$this->arResult['SAVE'] = $this->save();
		$this->arResult['LIST'] = (new Manager())->getList();

		$this->includeComponentTemplate();
	}
}
