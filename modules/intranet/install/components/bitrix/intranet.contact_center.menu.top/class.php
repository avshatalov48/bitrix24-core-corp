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
	}

	/**
	 * @return array
	 */
	protected function getMenuItems(): array
	{
		$result = [
			[
			'TEXT' => Loc::getMessage('MENU_CONTACT_CENTER'),
			'URL' => $this->arParams['COMPONENT_BASE_DIR'],
			'ID' => 'menu_contact_center'
		]
		];

		if (
			CModule::IncludeModule('imopenlines') &&
			Security\Helper::isStatisticsMenuEnabled()
		)
		{
			$result[] = [
				'TEXT' => Loc::getMessage('MENU_CONTACT_CENTER_IMOL_DETAILED_STATISTICS'),
				'URL' => $this->arParams['COMPONENT_BASE_DIR'] . 'openlines/statistics.php',
				'ID' => 'menu_contact_center_detail_statistics'
			];

			if(Limit::canUseReport())
			{
				$result[] = [
					'TEXT' => Loc::getMessage('MENU_CONTACT_CENTER_IMOL_STATISTICS'),
					'URL' => $this->arParams['COMPONENT_BASE_DIR'] . 'openlines/',
					'ID' => 'menu_contact_center_statistics'
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

			$this->arResult['MENU_ID'] = 'contact_center_menu_top';
			$this->arResult['ITEMS'] = $this->getMenuItems();

			$this->includeComponentTemplate();
		}
	}
};