<?php

namespace Bitrix\SalesCenter;

use Bitrix\Landing\Internals\LandingTable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\SalesCenter\Fields\Manager;
use Bitrix\SalesCenter\Integration\Bitrix24Manager;
use Bitrix\SalesCenter\Integration\CrmManager;
use Bitrix\SalesCenter\Integration\IntranetManager;
use Bitrix\SalesCenter\Integration\LandingManager;
use Bitrix\SalesCenter\Integration\SaleManager;
use Bitrix\SalesCenter\Model\PageTable;

final class Driver
{
	const MODULE_ID = 'salescenter';

	protected static $instance;
	protected $fieldsManager;

	private function __construct()
	{
		$this->fieldsManager = new Manager();
	}

	/**
	 * @return Driver
	 */
	public static function getInstance()
	{
		if(static::$instance === null)
		{
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * @return int
	 */
	public function getUserId()
	{
		global $USER;
		if(is_object($USER))
		{
			return CurrentUser::get()->getId();
		}

		return 0;
	}

	/**
	 * @return array
	 */
	public function getManagerParams()
	{
		$params = [];

		if(LandingManager::getInstance()->isEnabled())
		{
			$params['siteTemplateCode'] = LandingManager::SITE_TEMPLATE_CODE;
			$params['connectedSiteId'] = LandingManager::getInstance()->getConnectedSiteId();
			$params['isSitePublished'] = LandingManager::getInstance()->isSitePublished();
			$params['isSiteExists'] = LandingManager::getInstance()->isSiteExists();
			$params['isOrderPublicUrlAvailable'] = LandingManager::getInstance()->isOrderPublicUrlAvailable();
		}

		$params['isSalesInChatActive'] = $this->isSalesInChatActive();
		$params['connectPath'] = $this->getConnectPath();

		return $params;
	}

	/**
	 * @param \CBitrixComponentTemplate $template
	 */
	public function addTopPanel(\CBitrixComponentTemplate $template)
	{
		$template->setViewTarget('above_pagetitle');
		$menuId = static::MODULE_ID;
		global $APPLICATION;
		$APPLICATION->IncludeComponent(
			'bitrix:main.interface.buttons',
			'',
			array(
				'ID' => $menuId,
				'ITEMS' => $this->getTopPanelItems(),
			)
		);
		$template->endViewTarget();
	}

	/**
	 * @return array
	 */
	public function getTopPanelItems()
	{
		$items = [
			[
				'TEXT' => Loc::getMessage('SALESCENTER_DRIVER_TOP_PANEL_PANEL'),
				'URL' => '/saleshub/',
				'URL_CONSTANT' => true,
				'SORT' => 10,
			]
		];
		$dealsLink = CrmManager::getInstance()->getDealsLink();
		if($dealsLink)
		{
			$items[] = [
				'TEXT' => Loc::getMessage('SALESCENTER_DRIVER_TOP_PANEL_DEAL'),
				'URL' => $dealsLink,
				'URL_CONSTANT' => true,
				'SORT' => 20,
				'ON_CLICK' => 'BX.Salescenter.Manager.openSlider(\''.$dealsLink.'\');',
			];
		}
		$ordersList = '/shop/orders/list/';
		$items[] = [
				'TEXT' => Loc::getMessage('SALESCENTER_DRIVER_TOP_PANEL_ORDER'),
				'URL' => $ordersList,
				'URL_CONSTANT' => true,
				'SORT' => 30,
				'ON_CLICK' => 'BX.Salescenter.Manager.openSlider(\''.$ordersList.'\');',
		];
		$contactsLink = CrmManager::getInstance()->getContactsLink();
		if($contactsLink)
		{
			$items[] = [
				'TEXT' => Loc::getMessage('SALESCENTER_DRIVER_TOP_PANEL_CONTACT'),
				'URL' => $contactsLink,
				'URL_CONSTANT' => true,
				'SORT' => 40,
				'ON_CLICK' => 'BX.Salescenter.Manager.openSlider(\''.$contactsLink.'\');',
			];
		}

		$pages = CrmManager::getInstance()->getSaleAdminPages();
		if(empty($pages))
		{
			return $items;
		}

		if(isset($pages['catalog']))
		{
			$items[] = [
				'TEXT' => Loc::getMessage('SALESCENTER_DRIVER_TOP_PANEL_CATALOG'),
				'URL' => $pages['catalog'],
				'URL_CONSTANT' => true,
				'SORT' => 50,
				'ON_CLICK' => 'window.open(\''.$pages['catalog'].'\', \'_blank\');',
			];
		}

		$cashboxCheckUrl = $pages['sale_cashbox_check'];
		$cashboxIsSlider = false;
		$cashboxPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.cashbox');
		$cashboxPath = getLocalPath('components'.$cashboxPath.'/slider.php');
		if($cashboxPath)
		{
			$cashboxCheckUrl = $cashboxPath.'?show_checks=y';
			$cashboxIsSlider = true;
		}

		if($cashboxIsSlider)
		{
			$cashboxOnClick = 'BX.Salescenter.Manager.openSlider(\''.$cashboxCheckUrl.'\');';
		}
		else
		{
			$cashboxOnClick = 'window.open(\''.$cashboxCheckUrl.'\', \'_blank\');';
		}

		if(SaleManager::getInstance()->isFullAccess() && $this->isCashboxEnabled())
		{
			$items[] = [
				'TEXT' => Loc::getMessage('SALESCENTER_DRIVER_TOP_PANEL_CHECK'),
				'URL' => $cashboxCheckUrl,
				'URL_CONSTANT' => true,
				'SORT' => 60,
				'ON_CLICK' => $cashboxOnClick,
			];
		}

		$taxesItems = [
			[
				'ID' => 'cat_vat_admin',
				'TEXT' => Loc::getMessage('SALESCENTER_DRIVER_TOP_PANEL_TAXES_VAT_RATES'),
				'PARENT_ID' => 'taxes',
				'SORT' => 90,
				'ON_CLICK' => 'window.open(\''.$pages['cat_vat_admin'].'\', \'_blank\');',
			],
		];

		if($this->isExtendedTaxesSettingsEnabled())
		{
			$taxesItems = array_merge($taxesItems, [
				[
					'ID' => 'sale_tax',
					'TEXT' => Loc::getMessage('SALESCENTER_DRIVER_TOP_PANEL_TAXES_LIST'),
					'PARENT_ID' => 'taxes',
					'SORT' => 100,
					'ON_CLICK' => 'window.open(\''.$pages['sale_tax'].'\', \'_blank\');',
				],
				[
					'ID' => 'sale_tax_rate',
					'TEXT' => Loc::getMessage('SALESCENTER_DRIVER_TOP_PANEL_TAXES_RATES'),
					'PARENT_ID' => 'taxes',
					'SORT' => 110,
					'ON_CLICK' => 'window.open(\''.$pages['sale_tax_rate'].'\', \'_blank\');',
				],
				[
					'ID' => 'sale_tax_exempt',
					'TEXT' => Loc::getMessage('SALESCENTER_DRIVER_TOP_PANEL_TAXES_EXEMPT'),
					'PARENT_ID' => 'taxes',
					'SORT' => 120,
					'ON_CLICK' => 'window.open(\''.$pages['sale_tax_exempt'].'\', \'_blank\');',
				],
			]);
		}

		$items[] = [
			'TEXT' => Loc::getMessage('SALESCENTER_DRIVER_TOP_PANEL_SETTINGS'),
			'ID' => 'settings',
			'PARENT_ID' => '',
			'SORT' => 70,
			'ITEMS' => [
				[
					'ID' => 'taxes',
					'TEXT' => Loc::getMessage('SALESCENTER_DRIVER_TOP_PANEL_TAXES'),
					'PARENT_ID' => 'settings',
					'SORT' => 80,
					'ITEMS' => $taxesItems,
				],
				[
					'ID' => 'prices',
					'TEXT' => Loc::getMessage('SALESCENTER_DRIVER_TOP_PANEL_PRICES'),
					'PARENT_ID' => 'settings',
					'SORT' => 130,
					'ITEMS' => [
						[
							'ID' => 'cat_group_admin',
							'TEXT' => Loc::getMessage('SALESCENTER_DRIVER_TOP_PANEL_PRICES_TYPE'),
							'PARENT_ID' => 'prices',
							'SORT' => 140,
							'ON_CLICK' => 'window.open(\''.$pages['cat_group_admin'].'\', \'_blank\');',
						],
						[
							'ID' => 'cat_round_list',
							'TEXT' => Loc::getMessage('SALESCENTER_DRIVER_TOP_PANEL_PRICES_ROUND'),
							'PARENT_ID' => 'prices',
							'SORT' => 150,
							'ON_CLICK' => 'window.open(\''.$pages['cat_round_list'].'\', \'_blank\');',
						],
						[
							'ID' => 'cat_extra',
							'TEXT' => Loc::getMessage('SALESCENTER_DRIVER_TOP_PANEL_PRICES_EXTRA'),
							'PARENT_ID' => 'prices',
							'SORT' => 160,
							'ON_CLICK' => 'window.open(\''.$pages['cat_extra'].'\', \'_blank\');',
						],
					],
				],
				[
					'ID' => 'cat_measure_list',
					'TEXT' => Loc::getMessage('SALESCENTER_DRIVER_TOP_PANEL_MEASURE_RATES'),
					'PARENT_ID' => 'settings',
					'SORT' => 170,
					'ON_CLICK' => 'window.open(\''.$pages['cat_measure_list'].'\', \'_blank\');',
				],
			],
		];


		return $items;
	}

	/**
	 * @return array
	 */
	public function getFilterForCustomUrlPages()
	{
		return [
			'=HIDDEN' => 'N',
			'=LANDING_ID' => null,
		];
	}

	/**
	 * @return \Bitrix\Main\ORM\Query\Filter\ConditionTree|array
	 */
	public function getFilterForAnotherSitePages()
	{
		if(LandingManager::getInstance()->isEnabled())
		{
			return Query::filter()
				->addCondition(Query::filter()
					->where('HIDDEN', '=', 'N')
				)
				->addCondition(Query::filter()
					->logic('or')
					->whereNull('LANDING_ID')
					->whereIn('LANDING_ID',
						LandingTable::query()
							->addSelect('ID')
							->whereNot('SITE_ID', LandingManager::getInstance()->getConnectedSiteId())
							->whereNot('DELETED', 'Y')
							->whereNot('SITE.DELETED', 'Y'))
				);
		}

		return ['=ID' => 0];
	}

	/**
	 * @return bool
	 */
	public function isSalesInChatActive()
	{
		if(LandingManager::getInstance()->isEnabled())
		{
			if(LandingManager::getInstance()->isSiteExists())
			{
				return true;
			}
			else
			{
				$filter = $this->getFilterForAnotherSitePages();
			}
		}
		else
		{
			$filter = $this->getFilterForCustomUrlPages();
		}

		return (PageTable::getCount($filter) > 0);
	}

	/**
	 * @return array
	 */
	public static function onGetDependentModule()
	{
		return [
			'MODULE_ID' => static::MODULE_ID,
			'USE' => ['PUBLIC_SECTION'],
		];
	}

	/**
	 * @return bool
	 */
	public function isExtendedTaxesSettingsEnabled()
	{
		if(Bitrix24Manager::getInstance()->isEnabled())
		{
			return Bitrix24Manager::getInstance()->isCurrentZone(['ru', 'ua', 'by', 'kz']);
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function isCashboxEnabled()
	{
		if (
			Bitrix24Manager::getInstance()->isEnabled()
			|| IntranetManager::getInstance()->isEnabled()
		)
		{
			return
				Bitrix24Manager::getInstance()->isCurrentZone('ru')
				|| IntranetManager::getInstance()->isCurrentZone('ru')
			;
		}

		return true;
	}

	/**
	 * @return false|string
	 */
	public function getConnectPath()
	{
		$connectPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.connect');
		return getLocalPath('components'.$connectPath.'/slider.php');

	}

	/**
	 * @return bool
	 */
	public function isEnabled()
	{
		if(Bitrix24Manager::getInstance()->isEnabled())
		{
			return Bitrix24Manager::getInstance()->isSalescenterFeatureEnabled();
		}

		return true;
	}

	public function getFieldsManager(): Fields\Manager
	{
		return $this->fieldsManager;
	}
}