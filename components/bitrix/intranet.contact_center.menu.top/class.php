<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Main\Loader,
	\Bitrix\Main\LoaderException,
	\Bitrix\Main\Localization\Loc;
use \Bitrix\Imopenlines\Limit,
	\Bitrix\ImOpenlines\Security;

class IntranetContactCenterMenuTop extends \CBitrixComponent
{
	/**
	 * Check the connection of the necessary modules.
	 * @return bool
	 * @throws LoaderException
	 */
	protected function checkModules(): bool
	{
		if (Loader::includeModule('intranet'))
		{
			return true;
		}
		else
		{
			ShowError(Loc::getMessage('CONTACT_CENTER_MENU_TOP_MODULE_NOT_INSTALLED'));
			return false;
		}
	}

	protected function checkParameters(): void
	{
		$arParams = &$this->arParams;

		foreach ($arParams as $key=>$param)
		{
			$arParams[$key] = htmlspecialcharsbx(trim((string)$param));
		}

		$arParams['MENU_MODE'] = isset($arParams['MENU_MODE']) && $arParams['MENU_MODE'] === 'Y';
	}

	protected function isSliderMode(): bool
	{
		return $this->request->get('IFRAME') == 'Y';
	}

	/**
	 * @return array
	 */
	protected function getMenuItems(): array
	{
		$sliderMode = $this->isSliderMode() ? '?IFRAME=Y' : '';
		$result = [
			[
				'TEXT' => Loc::getMessage('MENU_CONTACT_CENTER'),
				'URL' => $this->arParams['COMPONENT_BASE_DIR'] . $sliderMode,
				'ID' => 'menu_contact_center',
				'IS_ACTIVE' => $this->arParams['SECTION_ACTIVE'] === 'contact_center'
			]
		];

		if (
			CModule::IncludeModule('imopenlines') &&
			Security\Helper::isStatisticsMenuEnabled()
		)
		{
			$result[] = [
				'TEXT' => Loc::getMessage('MENU_CONTACT_CENTER_IMOL_DETAILED_STATISTICS'),
				'URL' => $this->arParams['COMPONENT_BASE_DIR'] . 'dialog_list/',
				'ID' => 'menu_contact_center_detail_statistics',
				'IS_ACTIVE' => $this->arParams['SECTION_ACTIVE'] === 'dialog_list',
				'ON_CLICK' => 'top.location="' . CUtil::JSEscape($this->arParams['COMPONENT_BASE_DIR'] . 'dialog_list/') . '"',
			];

			if(Limit::canUseReport())
			{
				$result[] = [
					'TEXT' => Loc::getMessage('MENU_CONTACT_CENTER_IMOL_STATISTICS'),
					'URL' => $this->arParams['COMPONENT_BASE_DIR'] . 'dialog_statistics/',
					'ID' => 'menu_contact_center_statistics',
					'IS_ACTIVE' => $this->arParams['SECTION_ACTIVE'] === 'dialog_statistics',
					'ON_CLICK' => 'top.location="' . CUtil::JSEscape($this->arParams['COMPONENT_BASE_DIR'] . 'dialog_statistics/') . '"',
				];
			}
			else
			{
				$result[] = [
					'TEXT' => Loc::getMessage('MENU_CONTACT_CENTER_IMOL_STATISTICS'),
					'ON_CLICK' => 'BX.UI.InfoHelper.show(\'' . Limit::INFO_HELPER_LIMIT_CONTACT_CENTER_ANALYTICS_REPORTS . '\'); ',
					'ID' => 'menu_contact_center_statistics'
				];
			}
		}

		if (
			CModule::IncludeModule('voximplant') &&
			CModule::IncludeModule('report')
		)
		{
			$result[] = [
				'TEXT' => Loc::getMessage('MENU_CONTACT_CENTER_TELEPHONY_STATISTICS'),
				'URL' => '/report/telephony/?analyticBoardKey=telephony_calls_dynamics',
				'ID' => 'menu_contact_center_telephony_statistics'
			];
		}

		return $result;
	}

	protected function createFileMenuItems($items): array
	{
		$result = [];
		foreach ($items as $item)
		{
			$result[] = [
				$item['NAME'] ?? $item['TEXT'],
				$item['URL'],
				[],
				[
					'onclick' => $item['ON_CLICK'] ?? null,
				]
			];
		}

		return $result;
	}

	/**
	 * @return mixed|void
	 * @throws LoaderException
	 */
	public function executeComponent()
	{
		$this->includeComponentLang('class.php');

		if($this->checkModules())
		{
			$this->checkParameters();

			if ($this->arParams['MENU_MODE'])
			{
				return [
					'ITEMS' => $this->createFileMenuItems($this->getMenuItems()),
				];
			}
			else
			{
				$this->arResult['MENU_ID'] = 'contact_center_menu_top';
				$this->arResult['ITEMS'] = $this->getMenuItems();
				$this->includeComponentTemplate();
			}
		}
	}
};