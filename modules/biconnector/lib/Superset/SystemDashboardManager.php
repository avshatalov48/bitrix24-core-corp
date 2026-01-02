<?php

namespace Bitrix\BIConnector\Superset;

use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboard;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupTable;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Superset\Scope\ScopeService;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Rest\Marketplace\Transport;

final class SystemDashboardManager
{
	/** @deprecated Will be removed in future updates. */
	public const SYSTEM_DASHBOARD_APP_ID_DEALS = 'deals';
	/** @deprecated Will be removed in future updates. */
	public const SYSTEM_DASHBOARD_APP_ID_LEADS = 'leads';
	/** @deprecated Will be removed in future updates. */
	public const SYSTEM_DASHBOARD_APP_ID_SALES = 'sales';
	/** @deprecated Will be removed in future updates. */
	public const SYSTEM_DASHBOARD_APP_ID_SALES_STRUCT = 'sales_struct';
	/** @deprecated Will be removed in future updates. */
	public const SYSTEM_DASHBOARD_APP_ID_TELEPHONY = 'telephony';

	public const SYSTEM_VENDOR_MAIN = 'bitrix';

	private const SYSTEM_VENDOR_ALAIO = 'alaio';

	public const SYSTEM_DASHBOARDS_DELETED_CODES_OPTION = 'deleted_system_dashboard_codes';

	private const SYSTEM_DASHBOARDS_TAG = 'bi_system_dashboard';

	private const SYSTEM_DASHBOARD_GROUP_CRM_TAG = ScopeService::BIC_SCOPE_CRM;
	private const SYSTEM_DASHBOARD_GROUP_SHOP_TAG = ScopeService::BIC_SCOPE_SHOP;
	private const SYSTEM_DASHBOARD_GROUP_PROFILE_TAG = ScopeService::BIC_SCOPE_PROFILE;
	private const SYSTEM_DASHBOARD_GROUP_BIZPROC_TAG = ScopeService::BIC_SCOPE_BIZPROC;
	private const SYSTEM_DASHBOARD_GROUP_TASKS_TAG = ScopeService::BIC_SCOPE_TASKS;

	/** @deprecated Will be removed in future updates. */
	public const OPTION_NEW_DASHBOARD_NOTIFICATION_LIST = 'new_dashboard_notification_list';

	/** @deprecated Will be removed in future updates. */
	private const RU_ZONE = 'ru';
	/** @deprecated Will be removed in future updates. */
	private const EN_ZONE = 'en';
	/** @deprecated Will be removed in future updates. */
	private const KZ_ZONE = 'kz';
	/** @deprecated Will be removed in future updates. */
	private const BY_ZONE = 'by';

	/** @deprecated Will be removed in future updates. */
	public static function resolveMarketAppId(string $appId): string
	{
		return '';
	}

	/** @deprecated Will be removed in future updates. */
	public static function getDashboardTitleByAppId(string $appId): string
	{
		return '';
	}

	/** @deprecated Will be removed in future updates. */
	private static function getDashboardLanguageCode(): string
	{
		return self::EN_ZONE;
	}

	/** @deprecated Will be removed in future updates. */
	public static function getNewDashboardNotificationUserIds(): array
	{
		return [];
	}

	/** @deprecated Will be removed in future updates. */
	public static function notifyUserDashboardModification(SupersetDashboard $dashboard, bool $isModification): void
	{
		return;
	}

	/**
	 * Adds agent to set admin as dashboard's owner if the previous owner was fired.
	 * @param $fields array User fields ACTIVE (Y/N) and ID.
	 *
	 * @return void
	 */
	public static function onAfterUserUpdateHandler(array $fields): void
	{
		if (!SupersetInitializer::isSupersetReady())
		{
			return;
		}

		if (!isset($fields['ACTIVE']))
		{
			return;
		}

		if ($fields['ACTIVE'] === 'N')
		{
			$userId = (int)($fields['ID'] ?? 0);
			if ($userId)
			{
				\CAgent::addAgent(
					"\\Bitrix\\BIConnector\\Integration\\Superset\\Agent::setDefaultOwnerForDashboards({$userId});",
					'biconnector',
					'N',
					300,
					'',
					'Y',
					convertTimeStamp(time() + \CTimeZone::getOffset() + 300, 'FULL')
				);
			}
		}
	}

	public static function getSystemApps(): array
	{
		//TODO remove this and uncomment when all dashboards will be at alaio and new endpoint is ready
		$apps = self::getHardcodeApps();
//		$apps = Transport::instance()->getDictionary(
//			Transport::DICTIONARY_BI_BUILDER_SYSTEM_DASHBOARDS,
//		);

		if (!is_array($apps))
		{
			return [];
		}

		return self::filterSystemApps($apps);
	}

	private static function filterSystemApps(array $marketApps): array
	{
		$systemApps = [];
		foreach ($marketApps as $app)
		{
			if (MarketDashboardManager::isSystemAppByAppCode($app['code']))
			{
				$systemApps[$app['code']] = $app;
			}
		}

		return $systemApps;
	}

	/**
	 * Once a day (by agent) gets all system dashboards from the market and adds rows to dashboard table
	 * or changes dashboard name if name was changed in market.
	 * Dashboards manually deleted by an admin are ignored and will not be re-added.
	 *
	 * @return void
	 */
	public static function actualizeSystemDashboards(): void
	{
		// Existing groups are required for correct saving dashboards.
		if (!SupersetDashboardGroupTable::getRow([]))
		{
			return;
		}

		$apps = self::getSystemApps();

		$appCodes = array_keys($apps);
		$oldVendorCodes = [];
		foreach ($appCodes as $appCode)
		{
			$oldVendorCode = self::mapExistingAppCode($appCode);
			if ($oldVendorCode === $appCode)
			{
				continue;
			}

			$oldVendorCodes[$oldVendorCode] = $appCode;
		}

		$dashboardRows = SupersetDashboardTable::getList([
			'select' => ['ID', 'APP_ID', 'TITLE', 'STATUS', 'TYPE'],
			'filter' => [
				'=TYPE' => SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM,
			],
		])
			->fetchCollection()
		;

		$deletedDashboards = self::getDeletedSystemDashboard();
		$notFoundAppCodes = array_diff($appCodes, $dashboardRows->getAppIdList(), $deletedDashboards, $oldVendorCodes);
		$notFoundApps = array_intersect_key($apps, array_flip($notFoundAppCodes));
		foreach ($notFoundApps as $notFoundApp)
		{
			$dashboard = SupersetDashboardTable::createObject();
			$dashboard
				->setAppId($notFoundApp['code'])
				->setTitle($notFoundApp['name'])
				->setType(SupersetDashboardTable::DASHBOARD_TYPE_SYSTEM)
				->setStatus(SupersetDashboardTable::DASHBOARD_STATUS_NOT_INSTALLED)
				->save()
			;
			$scopes = $notFoundApp['scope'] ?? [];
			$groupCode = $notFoundApp['groupCode'] ?? MarketDashboardManager::getDefaultDashboardGroupScope();
			MarketDashboardManager::getInstance()->applyDashboardSettings($dashboard, [
				'scope' => $scopes,
				'groupCode' => $groupCode,
			]);
		}

		foreach ($dashboardRows as $dashboardRow)
		{
			if ($dashboardRow->getStatus() === SupersetDashboardTable::DASHBOARD_STATUS_NOT_INSTALLED)
			{
				$appData = $apps[$oldVendorCodes[$dashboardRow->getAppId()]] //hack for apps from `alaio` vendor
					?? $apps[$dashboardRow->getAppId()]
					?? null
				;
				if ($appData)
				{
					$dashboardRow->setAppId($appData['code']);
					if ($appData['name'] !== $dashboardRow->getTitle())
					{
						$dashboardRow->setTitle($appData['name']);
					}

					$dashboardRow->save();
				}
			}
		}
	}

	public static function saveDeletedSystemDashboard(string $appCode): void
	{
		if (!self::canDeleteSystemDashboard())
		{
			return;
		}

		$deletedDashboards = self::getDeletedSystemDashboard();

		$deletedDashboards[] = $appCode;
		$jsonString = Json::encode(array_unique($deletedDashboards));
		Option::set(
			'biconnector',
			self::SYSTEM_DASHBOARDS_DELETED_CODES_OPTION,
			$jsonString
		);
	}

	private static function getDeletedSystemDashboard(): array
	{
		if (!self::canDeleteSystemDashboard())
		{
			return [];
		}

		try
		{
			return Json::decode(Option::get('biconnector', self::SYSTEM_DASHBOARDS_DELETED_CODES_OPTION, '[]'));
		}
		catch (ArgumentException $e)
		{
			return [];
		}
	}

	public static function getAdditionalSystemVendors(): array
	{
		return [
			self::SYSTEM_VENDOR_ALAIO,
		];
	}

	public static function tryToGetAdditionalSystemVendorByAppCode(string $appCode): null|string
	{
		$vendors = self::getAdditionalSystemVendors();
		$currentVendor = substr($appCode, 0, strpos($appCode, '.'));
		$index = array_search($currentVendor, $vendors);

		return is_int($index) ? $vendors[$index] : null;
	}

	/**
	 * Returns the existing app code used for system dashboards when vendor prefixes differ.
	 *
	 * @param string $appCode The app code of the dashboard being installed.
	 * @param string $vendorCode The vendor code extracted from the app code.
	 * @return string The existing app code with "bitrix" prefix.
	 */
	public static function getExistingAppCode(string $appCode, string $vendorCode): string
	{
		return str_replace($vendorCode, self::SYSTEM_VENDOR_MAIN, $appCode);
	}

	/**
	 * Maps the installing app code to the existing one if it's a system dashboard installed by an alternative vendor (e.g., alaio).
	 * If it's not a system vendor (or the original Bitrix vendor), returns the provided app code unchanged.
	 *
	 * @param string $appCode The app code of the dashboard being installed.
	 * @return string The mapped (or original) app code to be used.
	 */
	public static function mapExistingAppCode(string $appCode): string
	{
		$vendor = self::tryToGetAdditionalSystemVendorByAppCode($appCode);
		if (!$vendor)
		{
			return $appCode;
		}

		$existingAppCode = self::getExistingAppCode($appCode, $vendor);

		$existingDashboard = SupersetDashboardTable::getList([
			'select' => ['ID', 'APP_ID', 'EXTERNAL_ID', 'STATUS'],
			'filter' => ['=APP_ID' => $existingAppCode],
			'limit' => 1,
		])
			->fetchObject()
		;

		if (!$existingDashboard)
		{
			return $appCode;
		}

		return $existingAppCode;
	}

	public static function canDeleteSystemDashboard(): bool
	{
		return Option::get('biconnector', 'allow_delete_system_dashboard', 'N') === 'Y';
	}

	// region toRemove
	private static function getHardcodeApps(): array
	{
		$licence = new \Bitrix\Main\License();
		$isNeedBitrixVendor = $licence->isCis();
		$region = $licence->getRegion();

		return $isNeedBitrixVendor
			? self::getHardcodeBitrixVendor($region)
			: self::getHardcodeAlaioVendor($region)
		;
	}

	private static function getHardcodeBitrixVendor(string $region): array
	{
		return [
			"bitrix.bic_deals_ru" => [
				"code" => "bitrix.bic_deals_ru",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_DEALS_TITLE", language: $region) ?? "Аналитика сделок",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"bitrix.bic_sum_eff" => [
				"code" => "bitrix.bic_sum_eff",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_SUM_EFF_EMP_NO_CRM", language: $region) ?? "Суммарная эффективность сотрудника без CRM",
				"groupCode" => "profile",
				"scope" => [
					"profile",
				],
			],
			"bitrix.bic_seasonsales" => [
				"code" => "bitrix.bic_seasonsales",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_SEASONSALES_TRENDS", language: $region) ?? "Сезонные тренды продаж",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"bitrix.bic_emp_season" => [
				"code" => "bitrix.bic_emp_season",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_EMP_SEASON_PATTERNS", language: $region) ?? "Сезонность в эффективности сотрудников",
				"groupCode" => "tasks",
				"scope" => [
					"tasks",
				],
			],
			"bitrix.bic_throughput_flow" => [
				"code" => "bitrix.bic_throughput_flow",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_THROUGHPUT_FLOW_CAPACITY", language: $region) ?? "Пропускная способность потока",
				"groupCode" => "tasks",
				"scope" => [
					"tasks",
					"tasks_flows_flow",
				],
			],
			"bitrix.bic_flow_param" => [
				"code" => "bitrix.bic_flow_param",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_FLOW_EFFICIENCY_DASHBOARD_PARAM", language: $region) ?? "Потоки: анализ загрузки и эффективности конкретного потока",
				"groupCode" => "tasks",
				"scope" => [
					"tasks",
					"tasks_flows_flow",
				],
			],
			"bitrix.bic_sourceperf" => [
				"code" => "bitrix.bic_sourceperf",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_SOURCEPERF_EFFICIENCY", language: $region) ?? "Эффективность рекламных источников",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"bitrix.bic_flow" => [
				"code" => "bitrix.bic_flow",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_FLOW_EFFICIENCY_DASHBOARD", language: $region) ?? "Потоки: анализ загрузки и эффективности",
				"groupCode" => "tasks",
				"scope" => [
					"tasks",
				],
			],
			"bitrix.bic_combination" => [
				"code" => "bitrix.bic_combination",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_COMBINATION_ANALYTICS", language: $region) ?? "Аналитика товарных пар",
				"groupCode" => "shop",
				"scope" => [
					"crm",
					"shop",
				],
			],
			"bitrix.bic_bizproc_param" => [
				"code" => "bitrix.bic_bizproc_param",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_BIZPROC_PARAM_PROC_DYNAMIC", language: $region) ?? "Динамика выполнения бизнес-процесса",
				"groupCode" => "bizproc",
				"scope" => [
					"bizproc",
					"workflow_template",
				],
			],
			"bitrix.bic_taskeff_param" => [
				"code" => "bitrix.bic_taskeff_param",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_TASKEFF_MGMT_LOAD_EFF2", language: $region) ?? "Моя нагрузка и эффективность",
				"groupCode" => "profile",
				"scope" => [
					"profile",
				],
			],
			"bitrix.bic_perkpi" => [
				"code" => "bitrix.bic_perkpi",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_PERKPI_MY_PERF_METRICS", language: $region) ?? "Мои персональные показатели",
				"groupCode" => "profile",
				"scope" => [
					"profile",
				],
			],
			"bitrix.bic_taskeff" => [
				"code" => "bitrix.bic_taskeff",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_TASKEFF_MGMT_LOAD_EFF", language: $region) ?? "Управление нагрузкой и эффективностью",
				"groupCode" => "tasks",
				"scope" => [
					"tasks",
				],
			],
			"bitrix.bic_actual_time" => [
				"code" => "bitrix.bic_actual_time",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_ACTUAL_EXECUTION_TIME_TASKS", language: $region) ?? "Задачи: фактическое время выполнения",
				"groupCode" => "tasks",
				"scope" => [
					"tasks",
				],
			],
			"bitrix.bic_taskdeadline" => [
				"code" => "bitrix.bic_taskdeadline",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_TASKDEADLINE_SPEED", language: $region) ?? "Задачи: скорость завершения",
				"groupCode" => "tasks",
				"scope" => [
					"tasks",
				],
			],
			"bitrix.bic_taskload" => [
				"code" => "bitrix.bic_taskload",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_TASKLOAD_TASKS_LOAD", language: $region) ?? "Задачи: загрузка исполнителей и отделов",
				"groupCode" => "tasks",
				"scope" => [
					"tasks",
				],
			],
			"bitrix.bic_bizproceff" => [
				"code" => "bitrix.bic_bizproceff",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_BIZPROCEFF_ORG_PROC_EFF", language: $region) ?? "Эффективность организационных процессов",
				"groupCode" => "bizproc",
				"scope" => [
					"bizproc",
				],
			],
			"bitrix.bic_smartproc" => [
				"code" => "bitrix.bic_smartproc",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_SMARTPROC_TITLE", language: $region) ?? "Аналитика смарт-процессов",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"bitrix.bic_compsales" => [
				"code" => "bitrix.bic_compsales",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_COMPSALES_COMP_ANALYTICS", language: $region) ?? "Сравнительная аналитика продаж товаров",
				"groupCode" => "shop",
				"scope" => [
					"crm",
					"shop",
				],
			],
			"bitrix.bic_catdeal" => [
				"code" => "bitrix.bic_catdeal",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_CATDEAL_TITLE", language: $region) ?? "Товарная аналитика сделок",
				"groupCode" => "shop",
				"scope" => [
					"crm",
					"shop",
				],
			],
			"bitrix.bic_bizproc" => [
				"code" => "bitrix.bic_bizproc",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_BIZPROC_ANALYTICS", language: $region) ?? "Аналитика бизнес-процессов",
				"groupCode" => "bizproc",
				"scope" => [
					"bizproc",
				],
			],
			"bitrix.bic_cohort" => [
				"code" => "bitrix.bic_cohort",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_COHORT_TITLE", language: $region) ?? "Когортный анализ клиентов",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"bitrix.bic_abcsku" => [
				"code" => "bitrix.bic_abcsku",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_ABCSKU_ABC_ANALYSIS", language: $region) ?? "ABC-анализ товаров",
				"groupCode" => "shop",
				"scope" => [
					"shop",
					"crm",
				],
			],
			"bitrix.bic_telephony" => [
				"code" => "bitrix.bic_telephony",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_TELEPHONY_CALL_ANALYTICS", language: $region) ?? "Аналитика звонков",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"bitrix.bic_retention" => [
				"code" => "bitrix.bic_retention",
				"name" => Loc::getMessage("BX_DASHBOARD_RETENTION_TITLE", language: $region) ?? "Удержание и отток клиентов",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"bitrix.bic_abcanalysis" => [
				"code" => "bitrix.bic_abcanalysis",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_ABCANALYSIS_TITLE", language: $region) ?? "ABC-анализ клиентов",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"bitrix.bic_general_stat" => [
				"code" => "bitrix.bic_general_stat",
				"name" => Loc::getMessage("BX_DASHBOARD_GENERAL_STAT_TITLE", language: $region) ?? "Основные показатели бизнеса",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"bitrix.bic_lead_generation" => [
				"code" => "bitrix.bic_lead_generation",
				"name" => Loc::getMessage("BX_DASHBOARD_LEAD_GENERATION_TITLE", language: $region) ?? "Аналитика лидогенерации",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"bitrix.bic_deals_complex" => [
				"code" => "bitrix.bic_deals_complex",
				"name" => Loc::getMessage("BX_DASHBOARD_DEALS_COMPLEX_TITLE", language: $region) ?? "Комплексная аналитика сделок",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"bitrix.bic_sum_eff_crm" => [
				"code" => "bitrix.bic_sum_eff_crm",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_SUM_EFF_CRM_EMP_EFF_CRM", language: $region) ?? "Суммарная эффективность сотрудника в CRM",
				"groupCode" => "profile",
				"scope" => [
					"profile",
				],
			],
		];
	}

	private static function getHardcodeAlaioVendor(string $region): array
	{
		return [
			"alaio.bic_sum_eff" => [
				"code" => "alaio.bic_sum_eff",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_SUM_EFF_EMP_NO_CRM", language: $region) ?? "Employee performance outside CRM",
				"groupCode" => "profile",
				"scope" => [
					"profile",
				],
			],
			"alaio.bic_seasonsales" => [
				"code" => "alaio.bic_seasonsales",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_SEASONSALES_TRENDS", language: $region) ?? "Sales trend",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"alaio.bic_emp_season" => [
				"code" => "alaio.bic_emp_season",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_EMP_SEASON_PATTERNS", language: $region) ?? "Employee performance",
				"groupCode" => "tasks",
				"scope" => [
					"tasks",
				],
			],
			"alaio.bic_throughput_flow" => [
				"code" => "alaio.bic_throughput_flow",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_THROUGHPUT_FLOW_CAPACITY", language: $region) ?? "Flow performance",
				"groupCode" => "tasks",
				"scope" => [
					"tasks",
					"tasks_flows_flow",
				],
			],
			"alaio.bic_flow_param" => [
				"code" => "alaio.bic_flow_param",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_FLOW_EFFICIENCY_DASHBOARD_PARAM", language: $region) ?? "Flow workload and efficiency",
				"groupCode" => "tasks",
				"scope" => [
					"tasks",
					"tasks_flows_flow",
				],
			],
			"alaio.bic_sourceperf" => [
				"code" => "alaio.bic_sourceperf",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_SOURCEPERF_EFFICIENCY", language: $region) ?? "Ad source efficiency and ROAS",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"alaio.bic_flow" => [
				"code" => "alaio.bic_flow",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_FLOW_EFFICIENCY_DASHBOARD", language: $region) ?? "Flow tasks and efficiency",
				"groupCode" => "tasks",
				"scope" => [
					"tasks",
				],
			],
			"alaio.bic_combination" => [
				"code" => "alaio.bic_combination",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_COMBINATION_ANALYTICS", language: $region) ?? "Product pairs",
				"groupCode" => "shop",
				"scope" => [
					"crm",
					"shop",
				],
			],
			"alaio.bic_bizproc_param" => [
				"code" => "alaio.bic_bizproc_param",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_BIZPROC_PARAM_PROC_DYNAMIC", language: $region) ?? "Workflow statistics",
				"groupCode" => "bizproc",
				"scope" => [
					"bizproc",
					"workflow_template",
				],
			],
			"alaio.bic_taskeff_param" => [
				"code" => "alaio.bic_taskeff_param",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_TASKEFF_MGMT_LOAD_EFF2", language: $region) ?? "My workload and efficiency",
				"groupCode" => "profile",
				"scope" => [
					"profile",
				],
			],
			"alaio.bic_perkpi" => [
				"code" => "alaio.bic_perkpi",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_PERKPI_MY_PERF_METRICS", language: $region) ?? "My performance",
				"groupCode" => "profile",
				"scope" => [
					"profile",
				],
			],
			"alaio.bic_taskeff" => [
				"code" => "alaio.bic_taskeff",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_TASKEFF_MGMT_LOAD_EFF", language: $region) ?? "Workload and efficiency",
				"groupCode" => "tasks",
				"scope" => [
					"tasks",
				],
			],
			"bitrix.bic_actual_time" => [
				"code" => "bitrix.bic_actual_time",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_ACTUAL_EXECUTION_TIME_TASKS", language: $region) ?? "Tasks: actual completion time",
				"groupCode" => "tasks",
				"scope" => [
					"tasks",
				],
			],
			"bitrix.bic_taskdeadline" => [
				"code" => "bitrix.bic_taskdeadline",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_TASKDEADLINE_SPEED", language: $region) ?? "Tasks: completion time",
				"groupCode" => "tasks",
				"scope" => [
					"tasks",
				],
			],
			"bitrix.bic_taskload" => [
				"code" => "bitrix.bic_taskload",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_TASKLOAD_TASKS_LOAD", language: $region) ?? "Tasks: assignee and department involvement",
				"groupCode" => "tasks",
				"scope" => [
					"tasks",
				],
			],
			"bitrix.bic_bizproceff" => [
				"code" => "bitrix.bic_bizproceff",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_BIZPROCEFF_ORG_PROC_EFF", language: $region) ?? "Workflow performance",
				"groupCode" => "bizproc",
				"scope" => [
					"bizproc",
				],
			],
			"bitrix.bic_smartproc" => [
				"code" => "bitrix.bic_smartproc",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_SMARTPROC_TITLE", language: $region) ?? "SPA Summary",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"bitrix.bic_compsales" => [
				"code" => "bitrix.bic_compsales",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_COMPSALES_COMP_ANALYTICS", language: $region) ?? "Comparative sales analysis",
				"groupCode" => "shop",
				"scope" => [
					"crm",
					"shop",
				],
			],
			"bitrix.bic_catdeal" => [
				"code" => "bitrix.bic_catdeal",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_CATDEAL_TITLE", language: $region) ?? "Deals and products",
				"groupCode" => "shop",
				"scope" => [
					"crm",
					"shop",
				],
			],
			"bitrix.bic_bizproc" => [
				"code" => "bitrix.bic_bizproc",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_BIZPROC_ANALYTICS", language: $region) ?? "Workflow analytics",
				"groupCode" => "bizproc",
				"scope" => [
					"bizproc",
				],
			],
			"bitrix.bic_cohort" => [
				"code" => "bitrix.bic_cohort",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_COHORT_TITLE", language: $region) ?? "Cohort analysis",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"bitrix.bic_abcsku" => [
				"code" => "bitrix.bic_abcsku",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_ABCSKU_ABC_ANALYSIS", language: $region) ?? "ABC Analysis (Products)",
				"groupCode" => "shop",
				"scope" => [
					"shop",
					"crm",
				],
			],
			"bitrix.bic_telephony" => [
				"code" => "bitrix.bic_telephony",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_TELEPHONY_CALL_ANALYTICS", language: $region) ?? "Call analytics",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"bitrix.bic_retention" => [
				"code" => "bitrix.bic_retention",
				"name" => Loc::getMessage("BX_DASHBOARD_RETENTION_TITLE", language: $region) ?? "Customer churn and retention",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"bitrix.bic_abcanalysis" => [
				"code" => "bitrix.bic_abcanalysis",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_ABCANALYSIS_TITLE", language: $region) ?? "ABC Analysis",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"bitrix.bic_general_stat" => [
				"code" => "bitrix.bic_general_stat",
				"name" => Loc::getMessage("BX_DASHBOARD_GENERAL_STAT_TITLE", language: $region) ?? "Key performance indicators",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"bitrix.bic_lead_generation" => [
				"code" => "bitrix.bic_lead_generation",
				"name" => Loc::getMessage("BX_DASHBOARD_LEAD_GENERATION_TITLE", language: $region) ?? "Lead generation",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"bitrix.bic_deals_complex" => [
				"code" => "bitrix.bic_deals_complex",
				"name" => Loc::getMessage("BX_DASHBOARD_DEALS_COMPLEX_TITLE", language: $region) ?? "Deal analytics",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"bitrix.bic_deals_en" => [
				"code" => "bitrix.bic_deals_en",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_DEALS_TITLE", language: $region) ?? "Deal analytics summary",
				"groupCode" => "crm",
				"scope" => [
					"crm",
				],
			],
			"alaio.bic_sum_eff_crm" => [
				"code" => "alaio.bic_sum_eff_crm",
				"name" => Loc::getMessage("BX_DASHBOARD_BIC_SUM_EFF_CRM_EMP_EFF_CRM", language: $region) ?? "Employee performance inside CRM",
				"groupCode" => "profile",
				"scope" => [
					"profile",
				],
			],
		];
	}

	// endregion
}
