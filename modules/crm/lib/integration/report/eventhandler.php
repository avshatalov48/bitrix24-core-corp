<?php

namespace Bitrix\Crm\Integration\Report;

use Bitrix\Crm\Integration\Report\AnalyticBoard\MyReports\ActivityAnalyticBoard;
use Bitrix\Crm\Integration\Report\AnalyticBoard\MyReports\CompanyAnalyticBoard;
use Bitrix\Crm\Integration\Report\AnalyticBoard\MyReports\ContactAnalyticBoard;
use Bitrix\Crm\Integration\Report\AnalyticBoard\MyReports\CrmStartAnalyticBoard;
use Bitrix\Crm\Integration\Report\AnalyticBoard\MyReports\DealAnalyticBoard;
use Bitrix\Crm\Integration\Report\AnalyticBoard\MyReports\InvoiceAnalyticBoard;
use Bitrix\Crm\Integration\Report\AnalyticBoard\MyReports\LeadAnalyticBoard;
use Bitrix\Crm\Integration\Report\Dashboard\Customers\FinancialRating;
use Bitrix\Crm\Integration\Report\Dashboard\Customers\RegularCustomers;
use Bitrix\Crm\Integration\Report\Dashboard\Managers\ManagersRating;
use Bitrix\Crm\Integration\Report\Dashboard\MyReports;
use Bitrix\Crm\Integration\Report\Dashboard\LeadAnalytic\CommonLead;
use Bitrix\Crm\Integration\Report\Dashboard\LeadAnalytic\NewLead;
use Bitrix\Crm\Integration\Report\Dashboard\LeadAnalytic\RepeatLead;
use Bitrix\Crm\Integration\Report\Dashboard\Sales\SalesFunnelByStageHistory;
use Bitrix\Crm\Integration\Report\Dashboard\ShopReports\SalesOrderFunnelBoard;
use Bitrix\Crm\Integration\Report\Dashboard\ShopReports\SalesOrderFunnelByStageHistory;
use Bitrix\Crm\Integration\Report\Dashboard\ShopReports\SalesOrderBuyerBoard;
use Bitrix\Crm\Integration\Report\Dashboard\Sales\SalesPeriodCompare;
use Bitrix\Crm\Integration\Report\Dashboard\Sales\SalesDynamic;
use Bitrix\Crm\Integration\Report\Dashboard\Sales\SalesFunnelBoard;
use Bitrix\Crm\Integration\Report\Dashboard\Sales\SalesPlanBoard;
use Bitrix\Crm\Integration\Report\Filter\ClientBaseFilter;
use Bitrix\Crm\Integration\Report\Filter\Customers\DealBasedFilter;
use Bitrix\Crm\Integration\Report\Filter\Deal\SalesDynamicFilter;
use Bitrix\Crm\Integration\Report\Filter\Deal\SalesPeriodCompareFilter;
use Bitrix\Crm\Integration\Report\Filter\Lead\CommonLead as CommonLeadFilter;
use Bitrix\Crm\Integration\Report\Filter\Lead\NewLead as NewLeadFilter;
use Bitrix\Crm\Integration\Report\Filter\Lead\RepeatLead as RepeatLeadBoard;
use Bitrix\Crm\Integration\Report\Filter\ManagerEfficiencyFilter;
use Bitrix\Crm\Integration\Report\Filter\MyReportsFilter;
use Bitrix\Crm\Integration\Report\Filter\SalesFunnelFilter;
use Bitrix\Crm\Integration\Report\Filter\SalesOrderFunnelFilter;
use Bitrix\Crm\Integration\Report\Handler\Client;
use Bitrix\Crm\Integration\Report\Handler\Company;
use Bitrix\Crm\Integration\Report\Handler\Contact;
use Bitrix\Crm\Integration\Report\Handler\Deal;
use Bitrix\Crm\Integration\Report\Handler\Lead;
use Bitrix\Crm\Integration\Report\Handler\Managers\Rating;
use Bitrix\Crm\Integration\Rest\AppPlacement;
use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Main\Localization\Loc;
use Bitrix\Report\VisualConstructor\AnalyticBoard;
use Bitrix\Report\VisualConstructor\AnalyticBoardBatch;
use Bitrix\Report\VisualConstructor\BoardButton;
use Bitrix\Report\VisualConstructor\Category;
use Bitrix\Report\VisualConstructor\Entity\DashboardRow;
use Bitrix\Report\VisualConstructor\Entity\Report;
use Bitrix\Report\VisualConstructor\Entity\Widget;
use Bitrix\Report\VisualConstructor\Handler\BaseReport;
use Bitrix\Report\VisualConstructor\Handler\BaseWidget;
use Bitrix\Report\VisualConstructor\Helper\Util;
use Bitrix\Report\VisualConstructor\Views\Component\Number;
use Bitrix\Report\VisualConstructor\Views\JsComponent\AmChart\LinearGraph;

use Bitrix\Report\VisualConstructor as VC;

use Bitrix\Crm\Tracking;

use Bitrix\Voximplant\Integration\Report\Dashboard\CallDynamics\CallDynamicsBoard;

/**
 * Class EventHandler
 * @package Bitrix\Crm\Integration\Report
 */
class EventHandler
{
	const BATCH_GROUP_CRM_GENERAL = 'crm_general';
	const BATCH_GROUP_SALES_GENERAL = 'sales_general';

	const BATCH_BOARD_CROSS_ANALYTICS = 'cross_analytics';
	const BATCH_LEAD = 'lead_analytic';
	const BATCH_SALES = 'sales_analytic';
	const CONTACT_DYNAMIC = 'contact_dynamic';
	const BATCH_MANAGER_EFFICIENCY = 'manager_efficiency';
	const BATCH_CLIENTS = 'clients';
	const BATCH_MY_REPORTS = 'my_reports';
	const BATCH_INTERNET_SHOP = 'sale_internet_shop';

	const MANAGER_EFFICIENCY_BOARD_KEY = 'crm_manager_efficiency';
	const CLIENT_BASE_BOARD_KEY = 'crm_client_base';
	const DISTRIBUTION_OF_REPEAT_SALES = 'crm_distribution_of_repeat_sales';

	const REST_BOARD_BATCH_KEY_TEMPLATE = 'rest_placement_';
	const REST_BOARD_KEY_TEMPLATE = 'rest_app_#APP_ID#_#HANDLER_ID#';

	/**
	 * @return AnalyticBoardBatch[]
	 */
	public static function onAnalyticPageBatchCollect()
	{
		$batchList = [];

		$userPermission = \CCrmPerms::GetCurrentUserPermissions();
		if (LeadSettings::isEnabled() && \CCrmAuthorizationHelper::CheckReadPermission(\CCrmOwnerType::Lead, 0, $userPermission))
		{
			$lead = new AnalyticBoardBatch();
			$lead->setKey(self::BATCH_LEAD);
			$lead->setTitle(Loc::getMessage('CRM_REPORT_LEAD_ANALYTIC_BATCH_TITLE'));
			$lead->setOrder(100);

			if (method_exists($lead, 'setGroup'))
			{
				$lead->setGroup(self::BATCH_GROUP_CRM_GENERAL);
			}

			$batchList[] = $lead;
		}


		$sales = new AnalyticBoardBatch();
		$sales->setKey(self::BATCH_SALES);
		$sales->setTitle(Loc::getMessage('CRM_REPORT_SALES_BATCH_TITLE'));
		$sales->setOrder(110);

		if (method_exists($sales, 'setGroup'))
		{
			$sales->setGroup(self::BATCH_GROUP_SALES_GENERAL);
		}

		$batchList[] = $sales;

		$managerEfficiency = new AnalyticBoardBatch();
		$managerEfficiency->setKey(self::BATCH_MANAGER_EFFICIENCY);
		$managerEfficiency->setTitle(Loc::getMessage('CRM_REPORT_MANAGER_EFFICIENCY_BATCH_TITLE'));
		$managerEfficiency->setOrder(120);

		if (method_exists($managerEfficiency, 'setGroup'))
		{
			$managerEfficiency->setGroup(self::BATCH_GROUP_CRM_GENERAL);
		}

		$batchList[] = $managerEfficiency;

		$clients = new AnalyticBoardBatch();
		$clients->setKey(self::BATCH_CLIENTS);
		$clients->setTitle(Loc::getMessage('CRM_REPORT_CLIENTS_BATCH_TITLE'));
		$clients->setOrder(130);

		if (method_exists($clients, 'setGroup'))
		{
			$clients->setGroup(self::BATCH_GROUP_CRM_GENERAL);
		}

		$batchList[] = $clients;

		$crossCutting = new AnalyticBoardBatch();
		$crossCutting->setKey(self::BATCH_BOARD_CROSS_ANALYTICS);
		$crossCutting->setTitle(Loc::getMessage('CRM_REPORT_CROSS_CUTTING_BATCH_TITLE'));
		$crossCutting->setOrder(140);

		if (method_exists($crossCutting, 'setGroup'))
		{
			$crossCutting->setGroup(self::BATCH_GROUP_CRM_GENERAL);
		}

		$batchList[] = $crossCutting;

		$myReports = new AnalyticBoardBatch();
		$myReports->setKey(self::BATCH_MY_REPORTS);;
		$myReports->setTitle(Loc::getMessage("CRM_REPORT_MY_REPORTS_BATCH_TITLE"));
		$myReports->setOrder(150);

		if (method_exists($myReports, 'setGroup'))
		{
			$myReports->setGroup(self::BATCH_GROUP_CRM_GENERAL);
		}

		$batchList[] = $myReports;

		$restApps = \Bitrix\Crm\Integration\Rest\AppPlacementManager::getHandlerInfos(AppPlacement::ANALYTICS_MENU);
		$i = 0;
		foreach (array_keys($restApps) as $categoryName)
		{
			$key = static::REST_BOARD_BATCH_KEY_TEMPLATE . $categoryName;

			$restCategory = new AnalyticBoardBatch();
			$restCategory->setKey($key);
			$restCategory->setTitle($categoryName);
			$restCategory->setOrder(400 + $i);

			if (method_exists($restCategory, 'setGroup'))
			{
				$restCategory->setGroup(self::BATCH_GROUP_CRM_GENERAL);
			}

			$batchList[] = $restCategory;
			$i += 10;
		}

		return $batchList;
	}

	protected static function getLimitComponentParams($board)
	{
		return [
			'NAME' => 'bitrix:crm.report.analytics.limit',
			'PARAMS' => [
				'BOARD_ID' => $board,
				'LIMITS' => Limit::getLimitationParams($board)
			]
		];
	}

	/**
	 * @return AnalyticBoard[]
	 */
	public static function onAnalyticPageCollect()
	{
		$analyticPageList = [];

		$showLeadsInSales = \CUserOptions::GetOption('crm', SalesFunnelBoard::SHOW_LEADS_OPTION, 'Y') === 'Y';

		$userPermission = \CCrmPerms::GetCurrentUserPermissions();
		if (LeadSettings::isEnabled() && \CCrmAuthorizationHelper::CheckReadPermission(\CCrmOwnerType::Lead, 0, $userPermission))
		{
			$leadAnalytics = new AnalyticBoard(CommonLead::BOARD_KEY);
			$leadAnalytics->setBatchKey(self::BATCH_LEAD);
			$leadAnalytics->setTitle(Loc::getMessage('CRM_REPORT_COMMON_LEAD_BOARD_TITLE'));
			$leadAnalytics->setFilter(new CommonLeadFilter(CommonLead::BOARD_KEY));

			if (method_exists($leadAnalytics, 'setGroup'))
			{
				$leadAnalytics->setGroup(self::BATCH_GROUP_CRM_GENERAL);
			}

			$leadAnalytics->addFeedbackButton();
			$leadAnalytics->setLimit(static::getLimitComponentParams(CommonLead::BOARD_KEY), Limit::isAnalyticsLimited(CommonLead::BOARD_KEY));
			$leadAnalytics->setStepperEnabled(true);
			$leadAnalytics->setStepperIds(['crm' => ['Bitrix\Crm\Agent\History\LeadStatusSupposedHistory']]);
			$analyticPageList[] = $leadAnalytics;

			$leadAnalytics = new AnalyticBoard(NewLead::BOARD_KEY);
			$leadAnalytics->setBatchKey(self::BATCH_LEAD);
			$leadAnalytics->setTitle(Loc::getMessage('CRM_REPORT_NEW_LEAD_BOARD_TITLE'));
			$leadAnalytics->setFilter(new NewLeadFilter(NewLead::BOARD_KEY));

			if (method_exists($leadAnalytics, 'setGroup'))
			{
				$leadAnalytics->setGroup(self::BATCH_GROUP_CRM_GENERAL);
			}

			$leadAnalytics->addFeedbackButton();
			$leadAnalytics->setLimit(static::getLimitComponentParams(NewLead::BOARD_KEY), Limit::isAnalyticsLimited(NewLead::BOARD_KEY));
			$leadAnalytics->setStepperEnabled(true);
			$leadAnalytics->setStepperIds(['crm' => ['Bitrix\Crm\Agent\History\LeadStatusSupposedHistory']]);
			$analyticPageList[] = $leadAnalytics;

			if(LeadSettings::getCurrent()->isAutoGenRcEnabled())
			{
				$leadAnalytics = new AnalyticBoard(RepeatLead::BOARD_KEY);
				$leadAnalytics->setBatchKey(self::BATCH_LEAD);
				$leadAnalytics->setTitle(Loc::getMessage('CRM_REPORT_REPEATED_LEAD_BOARD_TITLE'));
				$leadAnalytics->setFilter(new RepeatLeadBoard(RepeatLead::BOARD_KEY));

				if (method_exists($leadAnalytics, 'setGroup'))
				{
					$leadAnalytics->setGroup(self::BATCH_GROUP_CRM_GENERAL);
				}

				$leadAnalytics->addFeedbackButton();
				$leadAnalytics->setStepperEnabled(true);
				$leadAnalytics->setStepperIds(['crm' => ['Bitrix\Crm\Agent\History\LeadStatusSupposedHistory']]);
				$analyticPageList[] = $leadAnalytics;
			}
		}

		$salesFunnelOptions = [];
		if (LeadSettings::isEnabled())
		{
			$salesFunnelOptions[] = [
				'TITLE' => $showLeadsInSales
					? Loc::getMessage('CRM_REPORT_SALES_HIDE_LEADS')
					: Loc::getMessage('CRM_REPORT_SALES_SHOW_LEADS'),
				'NAME' => SalesFunnelBoard::SHOW_LEADS_OPTION,
				'VALUE' => $showLeadsInSales
			];
		}
		$salesFunnel = new AnalyticBoard(SalesFunnelBoard::BOARD_KEY, $salesFunnelOptions);
		$salesFunnel->setBatchKey(self::BATCH_SALES);
		$salesFunnel->setTitle(Loc::getMessage('CRM_REPORT_SALES_FUNNEL_BY_HISTORY_BOARD_TITLE'));
		$salesFunnel->setFilter(new SalesFunnelFilter(SalesFunnelBoard::BOARD_KEY));

		if (method_exists($salesFunnel, 'setGroup'))
		{
			$salesFunnel->setGroup(self::BATCH_GROUP_SALES_GENERAL);
		}

		$salesFunnel->setLimit(static::getLimitComponentParams(SalesFunnelBoard::BOARD_KEY), Limit::isAnalyticsLimited(SalesFunnelBoard::BOARD_KEY));
		$salesFunnel->setStepperEnabled(true);
		$salesFunnel->setStepperIds(['crm' => [
			'Bitrix\Crm\Agent\History\LeadStatusSupposedHistory',
			'Bitrix\Crm\Agent\History\DealStageSupposedHistory'
		]]);
		$salesFunnel->addFeedbackButton();
		if (method_exists($salesFunnel, 'registerSetOptionsCallback'))
		{
			$salesFunnel->registerSetOptionsCallback(function($name, $value)
			{
				if ($name === SalesFunnelBoard::SHOW_LEADS_OPTION)
				{
					\CUserOptions::SetOption('crm', SalesFunnelBoard::SHOW_LEADS_OPTION, $value ? 'Y' : 'N');
				}
			});
		}

		$analyticPageList[] = $salesFunnel;

		$salesFunnelHistoryOptions = [];
		if (LeadSettings::isEnabled())
		{
			$salesFunnelHistoryOptions[] = [
				'TITLE' => $showLeadsInSales
					? Loc::getMessage('CRM_REPORT_SALES_HIDE_LEADS')
					: Loc::getMessage('CRM_REPORT_SALES_SHOW_LEADS'),
				'NAME' => SalesFunnelBoard::SHOW_LEADS_OPTION,
				'VALUE' => $showLeadsInSales
			];
		}
		$salesFunnelWithHistory = new AnalyticBoard(SalesFunnelByStageHistory::BOARD_KEY, $salesFunnelHistoryOptions);
		$salesFunnelWithHistory->setBatchKey(self::BATCH_SALES);
		$salesFunnelWithHistory->setTitle(Loc::getMessage('CRM_REPORT_SALES_FUNNEL_BOARD_TITLE'));
		$salesFunnelWithHistory->setFilter(new SalesFunnelFilter(SalesFunnelByStageHistory::BOARD_KEY));

		if (method_exists($salesFunnelWithHistory, 'setGroup'))
		{
			$salesFunnelWithHistory->setGroup(self::BATCH_GROUP_SALES_GENERAL);
		}

		$salesFunnelWithHistory->addFeedbackButton();
		$salesFunnelWithHistory->setStepperEnabled(true);
		$salesFunnelWithHistory->setStepperIds(['crm' => [
			'Bitrix\Crm\Agent\History\LeadStatusSupposedHistory',
			'Bitrix\Crm\Agent\History\DealStageSupposedHistory'
		]]);
		$salesFunnelWithHistory->setLimit(static::getLimitComponentParams(SalesFunnelByStageHistory::BOARD_KEY), Limit::isAnalyticsLimited(SalesFunnelByStageHistory::BOARD_KEY));
		if (method_exists($salesFunnelWithHistory, 'registerSetOptionsCallback'))
		{
			$salesFunnelWithHistory->registerSetOptionsCallback(function($name, $value)
			{
				if ($name === SalesFunnelByStageHistory::SHOW_LEADS_OPTION)
				{
					\CUserOptions::SetOption('crm', SalesFunnelByStageHistory::SHOW_LEADS_OPTION, $value ? 'Y' : 'N');
				}
			});
		}
		$analyticPageList[] = $salesFunnelWithHistory;

		$salesPlan = new AnalyticBoard(SalesPlanBoard::BOARD_KEY);
		$salesPlan->setBatchKey(self::BATCH_SALES);
		$salesPlan->setTitle(Loc::getMessage('CRM_REPORT_SALES_TARGET_BOARD_TITLE'));

		if (method_exists($salesPlan, 'setGroup'))
		{
			$salesPlan->setGroup(self::BATCH_GROUP_SALES_GENERAL);
		}

		//$salesPlan->setDisabled(false);
		$salesPlan->addFeedbackButton();
		$salesPlan->setLimit(static::getLimitComponentParams(SalesPlanBoard::BOARD_KEY), Limit::isAnalyticsLimited(SalesPlanBoard::BOARD_KEY));
		$analyticPageList[] = $salesPlan;

		$salesDynamic = new AnalyticBoard(SalesDynamic::BOARD_KEY);
		$salesDynamic->setBatchKey(self::BATCH_SALES);
		$salesDynamic->setTitle(Loc::getMessage('CRM_REPORT_SALES_DYNAMIC_BOARD_TITLE'));
		$salesDynamic->setBoardKey(SalesDynamic::BOARD_KEY);
		$salesDynamic->setFilter(new SalesDynamicFilter(SalesDynamic::BOARD_KEY . "_" . SalesDynamic::VERSION));

		if (method_exists($salesDynamic, 'setGroup'))
		{
			$salesDynamic->setGroup(self::BATCH_GROUP_SALES_GENERAL);
		}

		$salesDynamic->addFeedbackButton();
		$salesDynamic->setLimit(static::getLimitComponentParams(SalesDynamic::BOARD_KEY), Limit::isAnalyticsLimited(SalesDynamic::BOARD_KEY));
		$salesDynamic->setStepperEnabled(true);
		$salesDynamic->setStepperIds(['crm' => ['Bitrix\Crm\Agent\History\DealStageSupposedHistory']]);
		$analyticPageList[] = $salesDynamic;

		$salesPeriodCompare = new AnalyticBoard(SalesPeriodCompare::BOARD_KEY);
		$salesPeriodCompare->setBatchKey(self::BATCH_SALES);
		$salesPeriodCompare->setTitle(Loc::getMessage('CRM_REPORT_PERIOD_COMPARE_BOARD_TITLE'));
		$salesPeriodCompare->setBoardKey(SalesPeriodCompare::BOARD_KEY);
		$salesPeriodCompare->setFilter(new SalesPeriodCompareFilter(SalesPeriodCompare::BOARD_KEY));

		if (method_exists($salesPeriodCompare, 'setGroup'))
		{
			$salesPeriodCompare->setGroup(self::BATCH_GROUP_SALES_GENERAL);
		}

		$salesPeriodCompare->addFeedbackButton();
		$salesPeriodCompare->setLimit(static::getLimitComponentParams(SalesPeriodCompare::BOARD_KEY), Limit::isAnalyticsLimited(SalesPeriodCompare::BOARD_KEY));
		$salesPeriodCompare->setStepperEnabled(true);
		$salesPeriodCompare->setStepperIds(['crm' => ['Bitrix\Crm\Agent\History\DealStageSupposedHistory']]);
		$analyticPageList[] = $salesPeriodCompare;

		$managerEfficiency = new AnalyticBoard();
		$managerEfficiency->setBatchKey(self::BATCH_MANAGER_EFFICIENCY);
		$managerEfficiency->setTitle(Loc::getMessage('CRM_REPORT_MANAGER_EFFICIENCY_BOARD_TITLE'));
		$managerEfficiency->setBoardKey(ManagersRating::BOARD_KEY);
		$managerEfficiency->setFilter(new DealBasedFilter(ManagersRating::BOARD_KEY));

		if (method_exists($managerEfficiency, 'setGroup'))
		{
			$managerEfficiency->setGroup(self::BATCH_GROUP_CRM_GENERAL);
		}

		$managerEfficiency->addFeedbackButton();
		$managerEfficiency->setLimit(static::getLimitComponentParams(self::MANAGER_EFFICIENCY_BOARD_KEY), Limit::isAnalyticsLimited(self::MANAGER_EFFICIENCY_BOARD_KEY));
		$analyticPageList[] = $managerEfficiency;

		/*$contactDynamic = new AnalyticBoard();
		$contactDynamic->setBatchKey(self::BATCH_CLIENTS);
		$contactDynamic->setTitle(Loc::getMessage('CRM_REPORT_CONTACT_DYNAMIC_BOARD_TITLE'));
		$contactDynamic->setBoardKey(self::CONTACT_DYNAMIC);
		$contactDynamic->setFilter(new ClientBaseFilter(self::CONTACT_DYNAMIC));
		$contactDynamic->setDisabled(true);
		$contactDynamic->addFeedbackButton();
		$contactDynamic->setLimit(static::getLimitComponentParams(self::CONTACT_DYNAMIC), Limit::isAnalyticsLimited(self::CONTACT_DYNAMIC));
		$analyticPageList[] = $contactDynamic;

		$companyDynamic = new AnalyticBoard();
		$companyDynamic->setBatchKey(self::BATCH_CLIENTS);
		$companyDynamic->setTitle(Loc::getMessage('CRM_REPORT_COMPANY_DYNAMIC_BOARD_TITLE'));
		$companyDynamic->setBoardKey('company_dynamic');
		$companyDynamic->setFilter(new ClientBaseFilter('company_dynamic'));
		$companyDynamic->setDisabled(true);
		$companyDynamic->addFeedbackButton();
		$companyDynamic->setLimit(static::getLimitComponentParams('company_dynamic'), Limit::isAnalyticsLimited('company_dynamic'));
		$analyticPageList[] = $companyDynamic;*/

		$stableCustomers = new AnalyticBoard();
		$stableCustomers->setBatchKey(self::BATCH_CLIENTS);
		$stableCustomers->setTitle(Loc::getMessage('CRM_REPORT_STABLE_CLIENTS_BOARD_TITLE'));
		$stableCustomers->setBoardKey(RegularCustomers::BOARD_KEY);
		$stableCustomers->setFilter(new DealBasedFilter(RegularCustomers::BOARD_KEY));

		if (method_exists($stableCustomers, 'setGroup'))
		{
			$stableCustomers->setGroup(self::BATCH_GROUP_CRM_GENERAL);
		}

		$stableCustomers->addFeedbackButton();
		$analyticPageList[] = $stableCustomers;

		$financeRating = new AnalyticBoard();
		$financeRating->setBatchKey(self::BATCH_CLIENTS);
		$financeRating->setTitle(Loc::getMessage('CRM_REPORT_FINANCE_RATING_BOARD_TITLE'));
		$financeRating->setBoardKey(FinancialRating::BOARD_KEY);
		$financeRating->setFilter(new DealBasedFilter(FinancialRating::BOARD_KEY));

		if (method_exists($financeRating, 'setGroup'))
		{
			$financeRating->setGroup(self::BATCH_GROUP_CRM_GENERAL);
		}

		$financeRating->addFeedbackButton();
		$analyticPageList[] = $financeRating;

		$board = new AnalyticBoard();
		$board->setTitle(Loc::getMessage('CRM_REPORT_ADVERTISE_SUM_EFFECT_BOARD_TITLE'));
		$board->setBoardKey(Dashboard\Ad\AdPayback::BOARD_KEY);
		$board->setFilter(new Filter\TrafficEffectFilter(Dashboard\Ad\AdPayback::BOARD_KEY));

		if (method_exists($board, 'setGroup'))
		{
			$board->setGroup(self::BATCH_GROUP_CRM_GENERAL);
		}

		$board->setBatchKey(self::BATCH_BOARD_CROSS_ANALYTICS);
		$board->setDisabled(!Tracking\Manager::isAccessible());
		$board->setLimit(static::getLimitComponentParams(Dashboard\Ad\AdPayback::BOARD_KEY), Limit::isAnalyticsLimited(Dashboard\Ad\AdPayback::BOARD_KEY));
		$board->addFeedbackButton();
		$board->addButton(
			new BoardButton(
				'<a href="/crm/tracking/" target="_top" class="ui-btn ui-btn-primary">'.
				Loc::getMessage('CRM_REPORT_TRAFFIC_EFFECT_CONFIG_BUTTON_TITLE').
				'</a>'
			)
		);
		$analyticPageList[] = $board;

		$board = new AnalyticBoard();
		$board->setTitle(Loc::getMessage('CRM_REPORT_TRAFFIC_EFFECT_BOARD_TITLE'));
		$board->setBoardKey(Dashboard\Ad\TrafficEfficiency::BOARD_KEY);
		$board->setFilter(new Filter\TrafficEffectFilter(Dashboard\Ad\TrafficEfficiency::BOARD_KEY));

		if (method_exists($board, 'setGroup'))
		{
			$board->setGroup(self::BATCH_GROUP_CRM_GENERAL);
		}

		$board->setBatchKey(self::BATCH_BOARD_CROSS_ANALYTICS);
		$board->setDisabled(!Tracking\Manager::isAccessible());
		$board->setLimit(static::getLimitComponentParams(Dashboard\Ad\TrafficEfficiency::BOARD_KEY), Limit::isAnalyticsLimited(Dashboard\Ad\TrafficEfficiency::BOARD_KEY));
		$board->addFeedbackButton();
		$board->addButton(
			new BoardButton(
				'<a href="/crm/tracking/" target="_top" class="ui-btn ui-btn-primary">'.
				Loc::getMessage('CRM_REPORT_TRAFFIC_EFFECT_CONFIG_BUTTON_TITLE').
				'</a>'
			)
		);
		$analyticPageList[] = $board;

		$myReportsStart = new CrmStartAnalyticBoard(MyReports\CrmStartBoard::BOARD_KEY);
		$myReportsStart->setTitle(Loc::getMessage("CRM_REPORT_MY_REPORTS_START"));
		$myReportsStart->addFeedbackButton();
		$myReportsStart->setBoardKey(MyReports\CrmStartBoard::BOARD_KEY);
		$myReportsStart->setFilter(new MyReportsFilter(MyReports\CrmStartBoard::BOARD_KEY));

		if (method_exists($myReportsStart, 'setGroup'))
		{
			$myReportsStart->setGroup(self::BATCH_GROUP_CRM_GENERAL);
		}

		$myReportsStart->setBatchKey(self::BATCH_MY_REPORTS);
		$myReportsStart->addButton(static::getAddWidgetButton());
		$analyticPageList[] = $myReportsStart;

		if (LeadSettings::isEnabled())
		{
			$myReportsLead = new LeadAnalyticBoard(MyReports\LeadBoard::BOARD_KEY);
			$myReportsLead->setTitle(Loc::getMessage("CRM_REPORT_MY_REPORTS_LEAD"));
			$myReportsLead->setBoardKey(MyReports\LeadBoard::BOARD_KEY);
			$myReportsLead->setFilter(new MyReportsFilter(MyReports\LeadBoard::getPanelGuid()));

			if (method_exists($myReportsLead, 'setGroup'))
			{
				$myReportsLead->setGroup(self::BATCH_GROUP_CRM_GENERAL);
			}

			$myReportsLead->setBatchKey(self::BATCH_MY_REPORTS);
			$myReportsLead->addButton(static::getAddWidgetButton());
			$analyticPageList[] = $myReportsLead;
		}

		$myReportsDeal = new DealAnalyticBoard(MyReports\DealBoard::BOARD_KEY);
		$myReportsDeal->setTitle(Loc::getMessage("CRM_REPORT_MY_REPORTS_DEAL"));
		$myReportsDeal->setBoardKey(MyReports\DealBoard::BOARD_KEY);
		$myReportsDeal->setFilter(new MyReportsFilter(MyReports\DealBoard::getPanelGuid()));

		if (method_exists($myReportsDeal, 'setGroup'))
		{
			$myReportsDeal->setGroup(self::BATCH_GROUP_CRM_GENERAL);
		}

		$myReportsDeal->setBatchKey(self::BATCH_MY_REPORTS);
		$myReportsDeal->addButton(
			new BoardButton('
				<div class="ui-btn-split ui-btn-light-border ui-btn-themes"> 
					<button id="crm-report-deal-category" class="ui-btn-main">
						'.htmlspecialcharsbx(MyReports\DealBoard::getCurrentCategoryName()).'
					</button> 
					<button class="ui-btn-menu" onclick="BX.Crm.Report.DealWidgetBoard.onSelectCategoryButtonClick(this);"></button> 
				</div>
			')
		);
		$myReportsDeal->addButton(static::getAddWidgetButton());
		$analyticPageList[] = $myReportsDeal;

		$myReportsContact = new ContactAnalyticBoard(MyReports\ContactBoard::BOARD_KEY);
		$myReportsContact->setTitle(Loc::getMessage("CRM_REPORT_MY_REPORTS_CONTACT"));
		$myReportsContact->setBoardKey(MyReports\ContactBoard::BOARD_KEY);
		$myReportsContact->setFilter(new MyReportsFilter(MyReports\ContactBoard::getPanelGuid()));

		if (method_exists($myReportsContact, 'setGroup'))
		{
			$myReportsContact->setGroup(self::BATCH_GROUP_CRM_GENERAL);
		}

		$myReportsContact->setBatchKey(self::BATCH_MY_REPORTS);
		$myReportsContact->addButton(static::getAddWidgetButton());
		$analyticPageList[] = $myReportsContact;

		$myReportsCompany = new CompanyAnalyticBoard(MyReports\CompanyBoard::BOARD_KEY);
		$myReportsCompany->setTitle(Loc::getMessage("CRM_REPORT_MY_REPORTS_COMPANY"));
		$myReportsCompany->setBoardKey(MyReports\CompanyBoard::BOARD_KEY);
		$myReportsCompany->setFilter(new MyReportsFilter(MyReports\CompanyBoard::getPanelGuid()));

		if (method_exists($myReportsCompany, 'setGroup'))
		{
			$myReportsCompany->setGroup(self::BATCH_GROUP_CRM_GENERAL);
		}

		$myReportsCompany->setBatchKey(self::BATCH_MY_REPORTS);
		$myReportsCompany->addButton(static::getAddWidgetButton());
		$analyticPageList[] = $myReportsCompany;

		$myReportsInvoice = new InvoiceAnalyticBoard(MyReports\InvoiceBoard::BOARD_KEY);
		$myReportsInvoice->setTitle(Loc::getMessage("CRM_REPORT_MY_REPORTS_INVOICE"));
		$myReportsInvoice->setBoardKey(MyReports\InvoiceBoard::BOARD_KEY);
		$myReportsInvoice->setFilter(new MyReportsFilter(MyReports\InvoiceBoard::getPanelGuid()));

		if (method_exists($myReportsInvoice, 'setGroup'))
		{
			$myReportsInvoice->setGroup(self::BATCH_GROUP_CRM_GENERAL);
		}

		$myReportsInvoice->setBatchKey(self::BATCH_MY_REPORTS);
		$myReportsInvoice->addButton(static::getAddWidgetButton());
		$analyticPageList[] = $myReportsInvoice;

		$myReportActivity = new ActivityAnalyticBoard(MyReports\ActivityBoard::BOARD_KEY);
		$myReportActivity->setTitle(Loc::getMessage("CRM_REPORT_MY_REPORTS_ACTIVITY"));
		$myReportActivity->setBoardKey(MyReports\ActivityBoard::BOARD_KEY);
		$myReportActivity->setFilter(new MyReportsFilter(MyReports\ActivityBoard::getPanelGuid()));

		if (method_exists($myReportActivity, 'setGroup'))
		{
			$myReportActivity->setGroup(self::BATCH_GROUP_CRM_GENERAL);
		}

		$myReportActivity->setBatchKey(self::BATCH_MY_REPORTS);
		$myReportActivity->addButton(static::getAddWidgetButton());
		$analyticPageList[] = $myReportActivity;

		if (\Bitrix\Main\Loader::includeModule('sale'))
		{
			$salesOrderFunnel = new AnalyticBoard(SalesOrderFunnelBoard::BOARD_KEY);
			$salesOrderFunnel->setBatchKey(self::BATCH_INTERNET_SHOP);
			$salesOrderFunnel->setBoardKey(SalesOrderFunnelBoard::BOARD_KEY);
			$salesOrderFunnel->setTitle(Loc::getMessage('CRM_REPORT_SALES_FUNNEL_BY_HISTORY_BOARD_TITLE'));
			$salesOrderFunnel->setFilter(new SalesOrderFunnelFilter(SalesOrderFunnelBoard::BOARD_KEY));

			if (method_exists($salesOrderFunnel, 'setGroup'))
			{
				$salesOrderFunnel->setGroup(self::BATCH_GROUP_SALES_GENERAL);
			}

			$salesOrderFunnel->setLimit(static::getLimitComponentParams(SalesOrderFunnelBoard::BOARD_KEY), Limit::isAnalyticsLimited(SalesOrderFunnelBoard::BOARD_KEY));
			$salesOrderFunnel->addFeedbackButton();

			$analyticPageList[] = $salesOrderFunnel;

			$salesOrderFunnelWithHistory = new AnalyticBoard(SalesOrderFunnelByStageHistory::BOARD_KEY);
			$salesOrderFunnelWithHistory->setBatchKey(self::BATCH_INTERNET_SHOP);
			$salesOrderFunnelWithHistory->setBoardKey(SalesOrderFunnelByStageHistory::BOARD_KEY);
			$salesOrderFunnelWithHistory->setTitle(Loc::getMessage('CRM_REPORT_SALES_FUNNEL_BOARD_TITLE'));
			$salesOrderFunnelWithHistory->setFilter(new SalesOrderFunnelFilter(SalesOrderFunnelByStageHistory::BOARD_KEY));

			if (method_exists($salesOrderFunnelWithHistory, 'setGroup'))
			{
				$salesOrderFunnelWithHistory->setGroup(self::BATCH_GROUP_SALES_GENERAL);
			}

			$salesOrderFunnelWithHistory->addFeedbackButton();
			$salesOrderFunnelWithHistory->setLimit(static::getLimitComponentParams(SalesOrderFunnelByStageHistory::BOARD_KEY), Limit::isAnalyticsLimited(SalesOrderFunnelByStageHistory::BOARD_KEY));
			$analyticPageList[] = $salesOrderFunnelWithHistory;

			$salesOrderBuyer = new AnalyticBoard(SalesOrderBuyerBoard::BOARD_KEY);
			$salesOrderBuyer->setBatchKey(self::BATCH_INTERNET_SHOP);
			$salesOrderBuyer->setBoardKey(SalesOrderBuyerBoard::BOARD_KEY);
			$salesOrderBuyer->setTitle(Loc::getMessage('CRM_REPORT_SALES_ORDER_BUYER_BOARD_TITLE'));
			$salesOrderBuyer->setFilter(new SalesOrderFunnelFilter(SalesOrderBuyerBoard::BOARD_KEY));

			if (method_exists($salesOrderBuyer, 'setGroup'))
			{
				$salesOrderBuyer->setGroup(self::BATCH_GROUP_SALES_GENERAL);
			}

			$salesOrderBuyer->addFeedbackButton();
			$salesOrderBuyer->setLimit(static::getLimitComponentParams(SalesOrderBuyerBoard::BOARD_KEY), Limit::isAnalyticsLimited(SalesOrderBuyerBoard::BOARD_KEY));
			$analyticPageList[] = $salesOrderBuyer;
		}

		$restApps = \Bitrix\Crm\Integration\Rest\AppPlacementManager::getHandlerInfos(AppPlacement::ANALYTICS_MENU);
		foreach ($restApps as $categoryName => $apps)
		{
			foreach ($apps as $appInfo)
			{
				$boardKey = str_replace(['#APP_ID#', '#HANDLER_ID#'],[$appInfo['APP_ID'], $appInfo['ID']], static::REST_BOARD_KEY_TEMPLATE) ;
				$batchKey = static::REST_BOARD_BATCH_KEY_TEMPLATE . $categoryName;
				$board = new VC\RestAppBoard();
				$board->setTitle($appInfo['TITLE']);
				$board->setBoardKey($boardKey);
				$board->setBatchKey($batchKey);

				if (method_exists($board, 'setGroup'))
				{
					$board->setGroup(self::BATCH_GROUP_CRM_GENERAL);
				}

				$board->setPlacement(AppPlacement::ANALYTICS_MENU);
				$board->setRestAppId($appInfo['APP_ID']);
				$board->setPlacementHandlerId($appInfo['ID']);
				$analyticPageList[] = $board;
			}
		}

		if (\Bitrix\Main\Loader::includeModule('voximplant'))
		{
			$telephonyExternalLink = new AnalyticBoard();
			$telephonyExternalLink->setBoardKey('crm_telephony_external_link');
			$telephonyExternalLink->setTitle(Loc::getMessage('CRM_REPORT_EXTERNAL_LINK_TELEPHONY_BOARD_TITLE'));
			$telephonyExternalLink->setExternal(true);
			$telephonyExternalLink->setExternalUrl(
				'/report/telephony/?analyticBoardKey=' . CallDynamicsBoard::BOARD_KEY
			);
			$telephonyExternalLink->setGroup(self::BATCH_GROUP_CRM_GENERAL);
			$analyticPageList[] = $telephonyExternalLink;
		}

		if (\Bitrix\Main\Loader::includeModule('imopenlines'))
		{
			$openlinesExternalLink = new AnalyticBoard();
			$openlinesExternalLink->setBoardKey('crm_openlines_external_link');
			$openlinesExternalLink->setTitle(Loc::getMessage('CRM_REPORT_EXTERNAL_LINK_OPENLINES_BOARD_TITLE'));
			$openlinesExternalLink->setExternal(true);

			if (IsModuleInstalled("bitrix24"))
			{
				$openlinesExternalLink->setExternalUrl('/contact_center/dialog_statistics');
			}
			else
			{
				$openlinesExternalLink->setExternalUrl('/services/contact_center/dialog_statistics');
			}

			$openlinesExternalLink->setSliderSupport(false);

			$openlinesExternalLink->setGroup(self::BATCH_GROUP_CRM_GENERAL);
			$analyticPageList[] = $openlinesExternalLink;
		}

		return $analyticPageList;
	}

	/**
	 * @return BaseReport[]
	 */
	public static function onReportHandlerCollect()
	{
		$reportHandlerCollection = [];
		$reportHandlerCollection[] = new Lead();
		$reportHandlerCollection[] = new Client();
		$reportHandlerCollection[] = new Contact();
		$reportHandlerCollection[] = new Company();
		$reportHandlerCollection[] = new Deal();
		$reportHandlerCollection[] = new Handler\Order\StatusGrid();
		$reportHandlerCollection[] = new Handler\Order\ResponsibleGrid();
		$reportHandlerCollection[] = new Handler\Order\BuyersGrid();
		$reportHandlerCollection[] = new Handler\SalesDynamics\PrimaryGraph();
		$reportHandlerCollection[] = new Handler\SalesDynamics\ReturnGraph();
		$reportHandlerCollection[] = new Handler\SalesDynamics\WonLostAmount();
		$reportHandlerCollection[] = new Handler\SalesDynamics\WonLostPrevious();
		$reportHandlerCollection[] = new Handler\SalesDynamics\Conversion();
		$reportHandlerCollection[] = new Handler\SalesPeriodCompare\GraphCurrent();
		$reportHandlerCollection[] = new Handler\SalesPeriodCompare\GraphPrevious();
		$reportHandlerCollection[] = new Handler\Customers\RegularCustomers();
		$reportHandlerCollection[] = new Handler\Customers\RegularCustomersGrid();
		$reportHandlerCollection[] = new Handler\Customers\FinancialRatingGraph();
		$reportHandlerCollection[] = new Handler\Customers\FinancialRatingGrid();
		$reportHandlerCollection[] = new Handler\Managers\RatingGraph();
		$reportHandlerCollection[] = new Handler\Managers\RatingGrid();

		return $reportHandlerCollection;
	}

	/**
	 * @return array
	 */
	public static function onViewsCollect()
	{
		$viewsList = [];
		$viewsList[] = new View\SalesPlan();
		$viewsList[] = new View\ColumnFunnel();
		$viewsList[] = new View\FunnelGrid();
		$viewsList[] = new View\ComparePeriods();
		$viewsList[] = new View\ComparePeriodsGrid();
		$viewsList[] = new View\AdsFunnel();
		$viewsList[] = new View\AdsGrid();
		$viewsList[] = new View\TrafficFunnel();
		$viewsList[] = new View\TrafficGrid();
		$viewsList[] = new View\SalesDynamicsGraph();
		$viewsList[] = new View\SalesDynamicsGrid();
		$viewsList[] = new View\Managers\ManagersRatingGraph();
		$viewsList[] = new View\Managers\ManagersRatingGrid();
		$viewsList[] = new View\Customers\RegularCustomersGraph();
		$viewsList[] = new View\Customers\RegularCustomersGrid();
		$viewsList[] = new View\Customers\FinancialRatingGraph();
		$viewsList[] = new View\Customers\FinancialRatingGrid();
		$viewsList[] = new View\MyReports\ActivityReport();
		$viewsList[] = new View\MyReports\CompanyReport();
		$viewsList[] = new View\MyReports\ContactReport();
		$viewsList[] = new View\MyReports\CrmStartReport();
		$viewsList[] = new View\MyReports\DealReport();
		$viewsList[] = new View\MyReports\InvoiceReport();
		$viewsList[] = new View\MyReports\LeadReport();
		$viewsList[] = new View\ShopReports\SaleBuyersGrid();

		return $viewsList;
	}

	/**
	 * @return Category[]
	 */
	public static function onReportCategoriesCollect()
	{

		return [];
		/*
		$categories = [];
		$crmCategory = new Category();
		$crmCategory->setKey('crm');
		$crmCategory->setLabel('CRM');
		$crmCategory->setParentKey('main');
		$categories[] = $crmCategory;

		return $categories;
		*/
	}

	/**
	 * @return VC\Entity\Dashboard[]
	 */
	public static function onDefaultBoardsCollect()
	{
		$dashboards = [];
		$dashboards[] = CommonLead::get();
		$dashboards[] = NewLead::get();
		$dashboards[] = RepeatLead::get();

		$dashboards[] = SalesPlanBoard::get();
		$dashboards[] = SalesFunnelBoard::get();
		$dashboards[] = SalesFunnelByStageHistory::get();
		$dashboards[] = SalesDynamic::get();
		$dashboards[] = SalesPeriodCompare::get();

		//$dashboards[] = self::buildClientBaseDefaultBoard();

		$dashboards[] = Dashboard\Ad\TrafficEfficiency::get();
		$dashboards[] = Dashboard\Ad\AdPayback::get();

		$dashboards[] = ManagersRating::get();

		$dashboards[] = RegularCustomers::get();
		$dashboards[] = FinancialRating::get();

		$dashboards[] = MyReports\ActivityBoard::get();
		$dashboards[] = MyReports\CompanyBoard::get();
		$dashboards[] = MyReports\ContactBoard::get();
		$dashboards[] = MyReports\CrmStartBoard::get();
		$dashboards[] = MyReports\DealBoard::get();
		$dashboards[] = MyReports\InvoiceBoard::get();
		$dashboards[] = MyReports\LeadBoard::get();

		$dashboards[] = SalesOrderFunnelBoard::get();
		$dashboards[] = SalesOrderFunnelByStageHistory::get();
		$dashboards[] = SalesOrderBuyerBoard::get();

		return $dashboards;
	}


	/**
	 * @return VC\Entity\Dashboard
	 */
	private static function buildClientBaseDefaultBoard()
	{
		$board = new VC\Entity\Dashboard();
		$board->setVersion('v1');
		$board->setBoardKey(self::CLIENT_BASE_BOARD_KEY);
		$board->setGId(Util::generateUserUniqueId());
		$board->setUserId(0);

		$firstRow = DashboardRow::factoryWithHorizontalCells(1);
		$firstRow->setWeight(1);
		$clientBaseLinearGraph = self::buildClientBaseLinerGraph();
		$clientBaseLinearGraph->setWeight($firstRow->getLayoutMap()['elements'][0]['id']);
		$firstRow->addWidgets($clientBaseLinearGraph);
		$board->addRows($firstRow);

		$secondRow = DashboardRow::factoryWithHorizontalCells(2);
		$secondRow->setWeight(2);
		$newClientCount = self::buildNewClientCountNumberBlock();
		$newClientCount->setWeight($secondRow->getLayoutMap()['elements'][0]['id']);
		$secondRow->addWidgets($newClientCount);
		$board->addRows($secondRow);

		$repeatedClientCount = self::buildRepeatedClientCountNumberBlock();
		$repeatedClientCount->setWeight($secondRow->getLayoutMap()['elements'][1]['id']);
		$secondRow->addWidgets($repeatedClientCount);
		$board->addRows($secondRow);

		return $board;
	}

	/**
	 * @return Widget
	 */
	private static function buildClientBaseLinerGraph()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(LinearGraph::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(self::CLIENT_BASE_BOARD_KEY);

		$widget->getWidgetHandler()->updateFormElementValue('label', Loc::getMessage('CRM_REPORT_CLIENT_BASE_LINEAR_GRAPH_WIDGET_TITLE'));
		$widget->addConfigurations($widget->getWidgetHandler()->getConfigurations());

		$contactsCountDynamics = new Report();
		$contactsCountDynamics->setGId(Util::generateUserUniqueId());
		$contactsCountDynamics->setReportClassName(Client::getClassName());
		$contactsCountDynamics->setWidget($widget);
		$contactsCountDynamics->getReportHandler()->updateFormElementValue('label', self::BATCH_CLIENTS);
		$contactsCountDynamics->getReportHandler()->updateFormElementValue('color', '#ff8792');
		$contactsCountDynamics->getReportHandler()->updateFormElementValue('groupingBy', Client::GROUPING_BY_DATE);
		$contactsCountDynamics->getReportHandler()->updateFormElementValue(
			'calculate',
			Client::WHAT_WILL_CALCULATE_COUNT
		);
		$contactsCountDynamics->addConfigurations($contactsCountDynamics->getReportHandler()->getConfigurations());
		$widget->addReports($contactsCountDynamics);

		return $widget;
	}

	/**
	 * @return Widget
	 */
	private static function buildNewClientCountNumberBlock()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(Number::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(self::CLIENT_BASE_BOARD_KEY);

		$widget->getWidgetHandler()->updateFormElementValue('label', Loc::getMessage('CRM_REPORT_NEW_CLIENT_NUMBER_BLOCK_WIDGET_TITLE'));
		$widget->addConfigurations($widget->getWidgetHandler()->getConfigurations());

		$newClientCount = new Report();
		$newClientCount->setGId(Util::generateUserUniqueId());
		$newClientCount->setReportClassName(Company::getClassName());
		$newClientCount->setWidget($widget);
		$newClientCount->getReportHandler()->updateFormElementValue('label', Loc::getMessage('CRM_REPORT_NEW_CLIENT_NUMBER_BLOCK_WIDGET_TITLE'));
		$newClientCount->getReportHandler()->updateFormElementValue('color', '#4fc3f7');
		$newClientCount->getReportHandler()->updateFormElementValue('calculate', Company::WHAT_WILL_CALCULATE_COUNT);
		$newClientCount->addConfigurations($newClientCount->getReportHandler()->getConfigurations());
		$widget->addReports($newClientCount);

		return $widget;
	}

	/**
	 * @return Widget
	 */
	private static function buildRepeatedClientCountNumberBlock()
	{
		$widget = new Widget();
		$widget->setGId(Util::generateUserUniqueId());
		$widget->setWidgetClass(BaseWidget::getClassName());
		$widget->setViewKey(Number::VIEW_KEY);
		$widget->setCategoryKey('crm');
		$widget->setBoardId(self::CLIENT_BASE_BOARD_KEY);

		$widget->getWidgetHandler()->updateFormElementValue('label', Loc::getMessage('CRM_REPORT_REPEATED_CLIENT_NUMBER_BLOCK_WIDGET_TITLE'));
		$widget->addConfigurations($widget->getWidgetHandler()->getConfigurations());

		$newClientCount = new Report();
		$newClientCount->setGId(Util::generateUserUniqueId());
		$newClientCount->setReportClassName(Company::getClassName());
		$newClientCount->setWidget($widget);
		$newClientCount->getReportHandler()->updateFormElementValue('label', Loc::getMessage('CRM_REPORT_REPEATED_CLIENT_NUMBER_BLOCK_WIDGET_TITLE'));
		$newClientCount->getReportHandler()->updateFormElementValue('color', '#eec200');
		$newClientCount->getReportHandler()->updateFormElementValue('calculate', Company::WHAT_WILL_CALCULATE_COUNT);
		$newClientCount->addConfigurations($newClientCount->getReportHandler()->getConfigurations());
		$widget->addReports($newClientCount);

		return $widget;
	}

	private static function getAddWidgetButton()
	{
		return new BoardButton('
			<button class="ui-btn ui-btn-primary ui-btn-icon-add" onclick="BX.CrmWidgetPanel.current.processAction(\'add\');">
				'.Loc::getMessage('CRM_REPORT_MY_REPORTS_ADD').'
			</button>
		');
	}
}