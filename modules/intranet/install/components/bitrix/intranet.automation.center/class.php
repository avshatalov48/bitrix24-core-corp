<?php

use Bitrix\Intranet\Site\Sections\AutomationSection;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class IntranetAutomationCenter extends \CBitrixComponent
{
	public function executeComponent()
	{
		if (!Loader::includeModule('intranet'))
		{
			ShowError('The Intranet module is not installed.');
			return;
		}

		$this->initParams();
		$this->prepareResult();
		$this->includeComponentTemplate();
	}

	protected function initParams(): void
	{
		$this->arParams['SET_TITLE'] =
			isset($this->arParams['SET_TITLE'])
				? $this->arParams['SET_TITLE'] === 'Y'
				: true
		;
	}

	protected function prepareResult(): void
	{
		if ($this->arParams['SET_TITLE'])
		{
			$GLOBALS['APPLICATION']->setTitle(Loc::getMessage('INTRANET_AUTOMATION_CENTER_PAGE_TITLE'));
		}

		$this->arResult['ITEMS'] = $this->getItems();
	}

	protected function getItems(): array
	{
		$items = [];
		foreach (AutomationSection::getItems() as $item)
		{
			if ($item['available'])
			{
				$tileData = $item['tileData'] ?? [];
				if (!isset($tileData['url']))
				{
					$tileData['url'] = $item['url'] ?? '';
				}

				$items[] = [
					'id' => $item['id'],
					'name' => $item['title'] ?? '',
					'iconClass' => $item['iconClass'] ?? '',
					'iconColor' => $item['iconColor'] ?? '',
					'comingSoon' => $item['comingSoon'] ?? false,
					'data' => $tileData,
					'selected' => $item['tileSelected'] ?? false,
				];
			}
		}

		return $items;
	}
}
