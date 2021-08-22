<?php

namespace Bitrix\Voximplant\Integration\Report;

use Bitrix\Main\Localization\Loc;
use Bitrix\Report\VisualConstructor;
use Bitrix\Report\VisualConstructor\AnalyticBoard;
use Bitrix\Report\VisualConstructor\AnalyticBoardBatch;
use Bitrix\Report\VisualConstructor\Category;
use Bitrix\Report\VisualConstructor\Handler\BaseReport;
use Bitrix\Report\VisualConstructor\RestAppBoard;
use Bitrix\Voximplant\Integration\Report\Dashboard\AverageCallTime\AverageCallTimeBoard;
use Bitrix\Voximplant\Integration\Report\Dashboard\CallActivity\CallActivityBoard;
use Bitrix\Voximplant\Integration\Report\Dashboard\CallDynamics\CallDynamicsBoard;
use Bitrix\Voximplant\Integration\Report\Dashboard\CallDuration\CallDurationBoard;
use Bitrix\Voximplant\Integration\Report\Dashboard\EmployeesWorkload\EmployeesWorkloadBoard;
use Bitrix\Voximplant\Integration\Report\Dashboard\LostCalls\LostCallsBoard;
use Bitrix\Voximplant\Integration\Report\Dashboard\MissedReaction\MissedReactionBoard;
use Bitrix\Voximplant\Integration\Report\Dashboard\PeriodCompare\PeriodCompareBoard;
use Bitrix\Voximplant\Integration\Report\Filter\AverageCallTimeFilter;
use Bitrix\Voximplant\Integration\Report\Filter\CallActivityFilter;
use Bitrix\Voximplant\Integration\Report\Filter\CallDurationFilter;
use Bitrix\Voximplant\Integration\Report\Filter\CallDynamicsFilter;
use Bitrix\Voximplant\Integration\Report\Filter\EmployeesWorkloadFilter;
use Bitrix\Voximplant\Integration\Report\Filter\LostCallsFilter;
use Bitrix\Voximplant\Integration\Report\Filter\MissedReactionFilter;
use Bitrix\Voximplant\Integration\Report\Filter\PeriodCompareFilter;
use Bitrix\Voximplant\Integration\Report\Handler;
use Bitrix\Voximplant\Integration\Report\View;
use Bitrix\Voximplant\Integration\Rest\AppPlacementManager;
use Bitrix\Voximplant\Integration\Rest\AppPlacement;

/**
 * Class EventHandler
 * @package Bitrix\Voximplant\Integration\Report
 */
class EventHandler
{
	public const BATCH_GROUP_TELEPHONY_GENERAL = 'telephony_general';

	public const BATCH_GENERAL_ANALYSIS = 'telephony_general_analysis';
	public const BATCH_MANAGERS_EFFICIENCY = 'telephony_managers_efficiency';
	public const BATCH_CALL_COSTS = 'telephony_call_costs';

	public const REST_BOARD_BATCH_KEY_TEMPLATE = 'rest_placement_';
	public const REST_BOARD_KEY_TEMPLATE = 'rest_app_#APP_ID#_#HANDLER_ID#';

	/**
	 * Collects groups of reports.
	 *
	 * @return AnalyticBoardBatch[]
	 */
	public static function onAnalyticPageBatchCollect(): array
	{
		$batches = [];

		$generalAnalysis = new AnalyticBoardBatch();
		$generalAnalysis->setKey(self::BATCH_GENERAL_ANALYSIS);
		$generalAnalysis->setGroup(self::BATCH_GROUP_TELEPHONY_GENERAL);
		$generalAnalysis->setTitle(Loc::getMessage('TELEPHONY_REPORT_BATCH_GENERAL_CALL_ANALYSIS_TITLE'));
		$generalAnalysis->setOrder(100);
		$batches[] = $generalAnalysis;

		$managersEfficiency = new AnalyticBoardBatch();
		$managersEfficiency->setKey(self::BATCH_MANAGERS_EFFICIENCY);
		$managersEfficiency->setGroup(self::BATCH_GROUP_TELEPHONY_GENERAL);
		$managersEfficiency->setTitle(Loc::getMessage('TELEPHONY_REPORT_BATCH_GENERAL_MANAGERS_EFFICIENCY_TITLE'));
		$managersEfficiency->setOrder(110);
		$batches[] = $managersEfficiency;

//		//In developing
//		$callCosts = new AnalyticBoardBatch();
//		$callCosts->setKey(self::BATCH_CALL_COSTS);
//		$callCosts->setGroup(self::BATCH_GROUP_TELEPHONY_GENERAL);
//		$callCosts->setTitle(Loc::getMessage('TELEPHONY_REPORT_BATCH_GENERAL_CALL_COSTS_TITLE'));
//		$callCosts->setOrder(120);
//		$batches[] = $callCosts;

		$restApps = AppPlacementManager::getHandlerInfos(AppPlacement::ANALYTICS_MENU);
		$i = 0;
		foreach (array_keys($restApps) as $categoryName)
		{
			$key = static::REST_BOARD_BATCH_KEY_TEMPLATE . $categoryName;

			$restCategory = new AnalyticBoardBatch();
			$restCategory->setKey($key);
			$restCategory->setTitle($categoryName);
			$restCategory->setOrder(400 + $i);
			$restCategory->setGroup(self::BATCH_GROUP_TELEPHONY_GENERAL);

			$batches[] = $restCategory;
			$i += 10;
		}

		return $batches;
	}

	/**
	 * Collects report boards.
	 *
	 * @return AnalyticBoard[]
	 */
	public static function onAnalyticPageCollect(): array
	{
		$boards = [];

		$callDynamics = new AnalyticBoard(CallDynamicsBoard::BOARD_KEY);
		$callDynamics->setBatchKey(self::BATCH_GENERAL_ANALYSIS);
		$callDynamics->setGroup(self::BATCH_GROUP_TELEPHONY_GENERAL);
		$callDynamics->setTitle(Loc::getMessage('TELEPHONY_REPORT_CALL_DYNAMICS_TITLE'));
		$callDynamics->setFilter(new CallDynamicsFilter(CallDynamicsBoard::BOARD_KEY));
		$callDynamics->addFeedbackButton();
		$boards[] = $callDynamics;

		$periodCompare = new AnalyticBoard(PeriodCompareBoard::BOARD_KEY);
		$periodCompare->setBatchKey(self::BATCH_GENERAL_ANALYSIS);
		$periodCompare->setGroup(self::BATCH_GROUP_TELEPHONY_GENERAL);
		$periodCompare->setTitle(Loc::getMessage('TELEPHONY_REPORT_PERIOD_COMPARE_TITLE'));
		$periodCompare->setFilter(new PeriodCompareFilter(PeriodCompareBoard::BOARD_KEY));
		$periodCompare->addFeedbackButton();
		$boards[] = $periodCompare;

		$lostCalls = new AnalyticBoard(LostCallsBoard::BOARD_KEY);
		$lostCalls->setBatchKey(self::BATCH_GENERAL_ANALYSIS);
		$lostCalls->setGroup(self::BATCH_GROUP_TELEPHONY_GENERAL);
		$lostCalls->setTitle(Loc::getMessage('TELEPHONY_REPORT_LOST_CALLS_TITLE_2'));
		$lostCalls->setFilter(new LostCallsFilter(LostCallsBoard::BOARD_KEY));
		$lostCalls->addFeedbackButton();
		$boards[] = $lostCalls;

		$callActivity = new AnalyticBoard(CallActivityBoard::BOARD_KEY);
		$callActivity->setBatchKey(self::BATCH_GENERAL_ANALYSIS);
		$callActivity->setGroup(self::BATCH_GROUP_TELEPHONY_GENERAL);
		$callActivity->setTitle(Loc::getMessage('TELEPHONY_REPORT_LOST_CALL_ACTIVITY'));
		$callActivity->setFilter(new CallActivityFilter(CallActivityBoard::BOARD_KEY));
		$callActivity->addFeedbackButton();
		$boards[] = $callActivity;

		$employeesWorkload = new AnalyticBoard(EmployeesWorkloadBoard::BOARD_KEY);
		$employeesWorkload->setBatchKey(self::BATCH_MANAGERS_EFFICIENCY);
		$employeesWorkload->setGroup(self::BATCH_GROUP_TELEPHONY_GENERAL);
		$employeesWorkload->setTitle(Loc::getMessage('TELEPHONY_REPORT_EMPLOYEES_WORKLOAD_TITLE'));
		$employeesWorkload->setFilter(new EmployeesWorkloadFilter(EmployeesWorkloadBoard::BOARD_KEY));
		$employeesWorkload->addFeedbackButton();
		$boards[] = $employeesWorkload;

		$callDuration = new AnalyticBoard(CallDurationBoard::BOARD_KEY);
		$callDuration->setBatchKey(self::BATCH_MANAGERS_EFFICIENCY);
		$callDuration->setGroup(self::BATCH_GROUP_TELEPHONY_GENERAL);
		$callDuration->setTitle(Loc::getMessage('TELEPHONY_REPORT_CALL_DURATION_TITLE'));
		$callDuration->setFilter(new CallDurationFilter(EmployeesWorkloadBoard::BOARD_KEY));
		$callDuration->addFeedbackButton();
		$boards[] = $callDuration;

		$missedReaction = new AnalyticBoard(MissedReactionBoard::BOARD_KEY);
		$missedReaction->setBatchKey(self::BATCH_MANAGERS_EFFICIENCY);
		$missedReaction->setGroup(self::BATCH_GROUP_TELEPHONY_GENERAL);
		$missedReaction->setTitle(Loc::getMessage('TELEPHONY_REPORT_MISSED_REACTION_TITLE'));
		$missedReaction->setFilter(new MissedReactionFilter(MissedReactionBoard::BOARD_KEY));
		$missedReaction->addFeedbackButton();
		$boards[] = $missedReaction;

		$averageCallTime = new AnalyticBoard(AverageCallTimeBoard::BOARD_KEY);
		$averageCallTime->setBatchKey(self::BATCH_MANAGERS_EFFICIENCY);
		$averageCallTime->setGroup(self::BATCH_GROUP_TELEPHONY_GENERAL);
		$averageCallTime->setTitle(Loc::getMessage('TELEPHONY_REPORT_AVERAGE_CALL_TIME_TITLE'));
		$averageCallTime->setFilter(new AverageCallTimeFilter(AverageCallTimeBoard::BOARD_KEY));
		$averageCallTime->addFeedbackButton();
		$boards[] = $averageCallTime;

		$restApps = AppPlacementManager::getHandlerInfos(AppPlacement::ANALYTICS_MENU);
		foreach ($restApps as $categoryName => $apps)
		{
			foreach ($apps as $appInfo)
			{
				$boardKey = str_replace(['#APP_ID#', '#HANDLER_ID#'],[$appInfo['APP_ID'], $appInfo['ID']], static::REST_BOARD_KEY_TEMPLATE) ;
				$batchKey = static::REST_BOARD_BATCH_KEY_TEMPLATE . $categoryName;
				$board = new RestAppBoard();
				$board->setTitle($appInfo['TITLE']);
				$board->setBoardKey($boardKey);
				$board->setBatchKey($batchKey);
				$board->setGroup(self::BATCH_GROUP_TELEPHONY_GENERAL);

				$board->setPlacement(AppPlacement::ANALYTICS_MENU);
				$board->setRestAppId($appInfo['APP_ID']);
				$board->setPlacementHandlerId($appInfo['ID']);
				$boards[] = $board;
			}
		}

		return $boards;
	}

	/**
	 * Collects report handlers.
	 *
	 * @return BaseReport[]
	 */
	public static function onReportHandlerCollect(): array
	{
		$handlers = [];

		$handlers[] = new Handler\CallDynamics\CallDynamicsGraph();
		$handlers[] = new Handler\CallDynamics\CallDynamicsGrid();
		$handlers[] = new Handler\PeriodCompare\PeriodCompareGraph();
		$handlers[] = new Handler\PeriodCompare\PeriodCompareGrid();
		$handlers[] = new Handler\LostCalls\LostCalls();
		$handlers[] = new Handler\CallActivity\CallActivityGraph();
		$handlers[] = new Handler\CallActivity\CallActivityGrid();
		$handlers[] = new Handler\EmployeesWorkload\EmployeesWorkloadGraph();
		$handlers[] = new Handler\EmployeesWorkload\EmployeesWorkloadGrid();
		$handlers[] = new Handler\CallDuration\CallDurationGraph();
		$handlers[] = new Handler\CallDuration\CallDurationGrid();
		$handlers[] = new Handler\MissedReaction\MissedReaction();
		$handlers[] = new Handler\AverageCallTime\AverageCallTime();

		return $handlers;
	}

	/**
	 * Collects report views.
	 *
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function onViewsCollect(): array
	{
		$views = [];

		$views[] = new View\CallDynamics\CallDynamicsGraph();
		$views[] = new View\CallDynamics\CallDynamicsGrid();
		$views[] = new View\PeriodCompare\PeriodCompareGraph();
		$views[] = new View\PeriodCompare\PeriodCompareGrid();
		$views[] = new View\LostCalls\LostCallsGraph();
		$views[] = new View\LostCalls\LostCallsGrid();
		$views[] = new View\CallActivity\CallActivityGraph();
		$views[] = new View\CallActivity\CallActivityGrid();
		$views[] = new View\EmployeesWorkload\EmployeesWorkloadGraph();
		$views[] = new View\EmployeesWorkload\EmployeesWorkloadGrid();
		$views[] = new View\CallDuration\CallDurationGraph();
		$views[] = new View\CallDuration\CallDurationGrid();
		$views[] = new View\MissedReaction\MissedReactionGraph();
		$views[] = new View\MissedReaction\MissedReactionGrid();
		$views[] = new View\AverageCallTime\AverageCallTimeGraph();
		$views[] = new View\AverageCallTime\AverageCallTimeGrid();

		return $views;
	}

	/**
	 * Collects report categories.
	 *
	 * @return Category[]
	 */
	public static function onReportCategoriesCollect(): array
	{
		return [];
	}

	/**
	 * @return VisualConstructor\Entity\Dashboard[]
	 */
	public static function onDefaultBoardsCollect(): array
	{
		$dashboards = [];

		$dashboards[] = CallDynamicsBoard::get();
		$dashboards[] = PeriodCompareBoard::get();
		$dashboards[] = LostCallsBoard::get();
		$dashboards[] = CallActivityBoard::get();
		$dashboards[] = EmployeesWorkloadBoard::get();
		$dashboards[] = CallDurationBoard::get();
		$dashboards[] = MissedReactionBoard::get();
		$dashboards[] = AverageCallTimeBoard::get();

		return $dashboards;
	}
}