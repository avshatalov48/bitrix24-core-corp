<?php

namespace Bitrix\Intranet\Settings\Tools;

use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

class Crm extends Tool
{
	private ?bool $isBiAnalyticsAvailable = null;
	private const CRM_SUBGROUPS_ID = [
		'crm' => 'menu_crm_favorite',
		'marketing' => 'menu_marketing',
		'analytics' => 'analytics',
		// There is a difficulties when disabling it without disabling inventory management
		// 'crm_inventory' => 'STORE_DOCUMENTS',
		// 'crm_sign' => '',
		'tracking' => 'CRM_TRACKING',
		'invoices' => 'INVOICE',
		'offers' => 'QUOTE',
		'saleshub' => 'SALES_CENTER',
		'terminal' => 'TERMINAL',
		'dynamic_items' => 'DYNAMIC_ITEMS',
		'bi_analytics' => 'ANALYTICS_BI',
		'bi_analytics_microsoft' => 'crm_microsoft_power_bi',
		'bi_analytics_google' => 'crm_google_datastudio',
		'bi_analytics_yandex' => 'crm_yandex_datalens',
		'report_construct' => 'REPORT',
	];

	private function isInvoicesAvailable(): bool
	{
		return !Loader::includeModule('bitrix24') || Feature::isFeatureEnabled('crm_invoices');
	}

	private function isTerminalAvailable(): bool
	{
		return Loader::includeModule('crm') && \Bitrix\Crm\Terminal\AvailabilityManager::getInstance()->isAvailable();
	}

	private function isBiAnalyticsAvailable(): bool
	{
		$this->isBiAnalyticsAvailable ??= !Loader::includeModule('bitrix24') || Feature::isFeatureEnabled('biconnector');

		return $this->isBiAnalyticsAvailable;
	}

	public function getSettingsPath(): ?string
	{
		return '/crm/configs/';
	}

	public function getLeftMenuPath(): ?string
	{
		return '/crm/';
	}

	public function getSubgroupSettingsPath(): array
	{
		return [
			'crm' => '/crm/',
			'marketing' => '/marketing/',
			'analytics' => '/report/analytics/',
			'crm_inventory' => '/shop/documents/receipt_adjustment/?inventoryManagementSource=crm',
			'crm_sign' => '/sign/',
			'tracking' => '/report/analytics/?analyticBoardKey=crm-ad-payback',
			'invoices' => $this->isInvoicesAvailable() ? '/crm/type/31/list/category/0/' : null,
			'offers' => '/crm/quote/kanban/',
			'saleshub' => '/saleshub/',
			'terminal' => '/crm/terminal/',
			'dynamic_items' => '/crm/type/',
			'bi_analytics' => $this->isBiAnalyticsAvailable() ? '/report/analytics/?analyticBoardKey=crm_bi_templates' : null,
			'bi_analytics_microsoft' => $this->isBiAnalyticsAvailable() ? '/report/analytics/?analyticBoardKey=crm_microsoft_power_bi' : null,
			'bi_analytics_google' => $this->isBiAnalyticsAvailable() ? '/report/analytics/?analyticBoardKey=crm_google_datastudio' : null,
			'bi_analytics_yandex' => $this->isBiAnalyticsAvailable() ? '/report/analytics/?analyticBoardKey=crm_yandex_datalens' : null,
			'report_construct' => '/crm/reports/report/',
		];
	}

	public function getSubgroupsInfoHelperSlider(): array
	{
		return [
			'invoices' => 'limit_crm_free_invoices',
			'bi_analytics' => 'limit_crm_BI_analytics',
			'bi_analytics_microsoft' => 'limit_crm_BI_analytics',
			'bi_analytics_google' => 'limit_crm_BI_analytics',
			'bi_analytics_yandex' => 'limit_crm_BI_analytics',
		];
	}

	public function getSettingsTitle(): ?string
	{
		return Loc::getMessage('INTRANET_SETTINGS_TOOLS_CRM_SETTINGS_TITLE');
	}

	protected function getSubgroupSettingsTitle(): array
	{
		return [];
	}

	public function getName(): string
	{
		return Loc::getMessage('INTRANET_SETTINGS_TOOLS_CRM_MAIN') ?? '';
	}

	public function isAvailable(): bool
	{
		return ModuleManager::isModuleInstalled('bitrix24') || ModuleManager::isModuleInstalled('crm');
	}

	public function isEnabled(): bool
	{
		return Option::get('intranet', $this->getOptionCode(), 'Y') === 'Y' && ModuleManager::isModuleInstalled('crm');
	}

	public function getSubgroupsIds(): array
	{
		$subgroupsId = self::CRM_SUBGROUPS_ID;

		if (!ModuleManager::isModuleInstalled('biconnector'))
		{
			$biConnectorSubgroups = ['bi_analytics', 'bi_analytics_microsoft', 'bi_analytics_google', 'bi_analytics_yandex'];
			$subgroupsId = array_diff_key($subgroupsId, array_flip($biConnectorSubgroups));
		}

		return $subgroupsId;
	}

	public function enable(): void
	{
		if (Loader::includeModule('bitrix24'))
		{
			$manualModulesChangedList = [];

			if (!ModuleManager::isModuleInstalled('crm'))
			{
				ModuleManager::add('crm');
				$manualModulesChangedList['crm'] = 'Y';
			}

			if (!ModuleManager::isModuleInstalled('crmmobile'))
			{
				ModuleManager::add('crmmobile');
				$manualModulesChangedList['crmmobile'] = 'Y';
			}

			if (!empty($manualModulesChangedList))
			{
				$event = new Event('bitrix24', 'OnManualModuleAddDelete', [
					'modulesList' => $manualModulesChangedList,
				]);
				$event->send();
			}
		}

		parent::enable();
	}

	public function getSubgroups(): array
	{
		$result = [];

		$settingsPath = $this->getSubgroupSettingsPath();
		$settingsTitle = $this->getSubgroupSettingsTitle();
		$infoHelperSlider = $this->getSubgroupsInfoHelperSlider();
		$license = \Bitrix\Main\Application::getInstance()->getLicense();

		foreach ($this->getSubgroupsIds() as $id => $menuId)
		{
			if (
				$id === 'bi_analytics'
				&& !($license->getRegion() === 'ru' || $license->getRegion() === 'by')
			)
			{
				continue;
			}

			if (
				$id === 'bi_analytics_yandex'
				&& !($license->getRegion() === 'ru' || $license->getRegion() === 'kz')
			)
			{
				continue;
			}

			$result[$id] = [
				'name' => Loc::getMessage('INTRANET_SETTINGS_TOOLS_CRM_SUBGROUP_' . strtoupper($id)),
				'code' => $this->getSubgroupCode($id),
				'id' => $id,
				'enabled' => $this->isEnabledSubgroupById($id),
				'menu_item_id' => $menuId,
				'settings_path' => $settingsPath[$id] ?? null,
				'settings_title' => $settingsTitle[$id] ?? null,
				'infohelper-slider' => $infoHelperSlider[$id] ?? null,
				'default' => $id === 'crm',
			];
		}

		if (!$this->isTerminalAvailable())
		{
			unset($result['terminal']);
		}

		return $result;
	}

	public function getId(): string
	{
		return 'crm';
	}

	public function getMenuItemId(): ?string
	{
		return 'menu_crm_favorite';
	}
}