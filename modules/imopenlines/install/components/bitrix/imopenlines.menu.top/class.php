<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Main\Loader,
	\Bitrix\Main\LoaderException,
	\Bitrix\Main\Localization\Loc;
use \Bitrix\Imopenlines\Limit,
	\Bitrix\ImOpenLines\Common,
	\Bitrix\ImOpenlines\Security,
	\Bitrix\ImOpenlines\Security\Helper;

use \Bitrix\ImConnector\Connector;

class ImopenlinesMenuTop extends \CBitrixComponent
{
	/**
	 * Check the connection of the necessary modules.
	 * @return bool
	 * @throws LoaderException
	 */
	protected function checkModules(): bool
	{
		if (Loader::includeModule('imopenlines'))
		{
			return true;
		}
		else
		{
			ShowError(Loc::getMessage('IMOL_MENU_TOP_MODULE_NOT_INSTALLED'));
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

		if(empty($arParams['COMPONENT_BASE_DIR']))
		{
			$arParams['COMPONENT_BASE_DIR'] = Common::getPublicFolder();
		}
	}

	/**
	 * @return array
	 * @throws LoaderException
	 */
	protected function getMenuItems(): array
	{
		$result = [];

		if(Helper::isStatisticsMenuEnabled())
		{
			if(Limit::canUseReport())
			{
				$result[] = [
					'TEXT' => Loc::getMessage('IMOL_MENU_TOP_STATISTICS'),
					'URL' => $this->arParams['COMPONENT_BASE_DIR'],
					'ID' => 'menu_contact_center_statistics'
				];
			}
			else
			{
				$result[] = [
					'TEXT' => Loc::getMessage('IMOL_MENU_TOP_STATISTICS'),
					'URL' => $this->arParams['COMPONENT_BASE_DIR'],
					'ON_CLICK' => 'BX.UI.InfoHelper.show(\'' . Limit::INFO_HELPER_LIMIT_CONTACT_CENTER_ANALYTICS_REPORTS . '\'); ',
					'ID' => 'menu_contact_center_statistics'
				];
			}
		}

		if (Helper::isLinesMenuEnabled())
		{
			$result[] = [
				'TEXT' => Loc::getMessage('IMOL_MENU_TOP_LIST_LINES'),
				'URL' => $this->arParams['COMPONENT_BASE_DIR'] . 'list/',
				'ID' => 'menu_openlines_lines'
			];
		}

		if (Loader::includeModule('imconnector'))
		{
			$listActiveConnector = Connector::getListConnectorMenu(true);

			foreach ($listActiveConnector as $idConnector => $fullName)
			{
				$result[] = [
					'TEXT' => empty($listActiveConnector[$idConnector]['short_name']) ? $listActiveConnector[$idConnector]['name'] : $listActiveConnector[$idConnector]['short_name'],
					'TITLE' => $listActiveConnector[$idConnector]['name'],
					'URL' => $this->arParams['COMPONENT_BASE_DIR'] . 'connector/?ID=' . $idConnector,
					'ID' => 'menu_openlines_connector_' . str_replace('.', '_', $idConnector),

				];
			}
		}

		if (Helper::isCrmWidgetEnabled())
		{
			$result[] = [
				'TEXT' => Loc::getMessage('IMOL_MENU_TOP_BUTTON'),
				'URL' => $this->arParams['COMPONENT_BASE_DIR'] . 'button.php',
				'ID' => 'menu_openlines_button'
			];
		}

		if (Helper::isStatisticsMenuEnabled())
		{
			$result[] = [
				'TEXT' => Loc::getMessage('IMOL_MENU_TOP_DETAILED_STATISTICS'),
				'URL' => $this->arParams['COMPONENT_BASE_DIR'] . 'statistics.php',
				'ID' => 'menu_openlines_detail_statistics'
			];
		}

		if (Helper::isSettingsMenuEnabled())
		{
			$result[] = [
				'TEXT' => Loc::getMessage('IMOL_MENU_TOP_PERMISSIONS'),
				'URL' => $this->arParams['COMPONENT_BASE_DIR'] . 'permissions.php',
				'IS_ACTIVE' => (
					mb_strpos($this->request->getRequestUri(), $this->arParams['COMPONENT_BASE_DIR'] . 'editrole.php') === 0 ||
					mb_strpos($this->request->getRequestUri(), $this->arParams['COMPONENT_BASE_DIR'] . 'permissions.php') === 0
				),
				'ID' => 'menu_openlines_permission'
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

			$this->arResult['MENU_ID'] = 'imopenlines_menu_top';
			$this->arResult['ITEMS'] = $this->getMenuItems();

			$this->includeComponentTemplate();
		}
	}
};