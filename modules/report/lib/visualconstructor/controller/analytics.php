<?php

namespace Bitrix\Report\VisualConstructor\Controller;

use Bitrix\ImOpenLines\Integrations\Report\Filter;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Report\VisualConstructor\AnalyticBoard;
use Bitrix\Report\VisualConstructor\Entity\Dashboard;
use Bitrix\Report\VisualConstructor\Internal\Engine\Response\Component;
use Bitrix\Report\VisualConstructor\Internal\Manager\AnalyticBoardBatchManager;
use Bitrix\Report\VisualConstructor\Internal\Manager\AnalyticBoardManager;
use Bitrix\Report\VisualConstructor\RuntimeProvider\AnalyticBoardProvider;

/**
 * Controller class for Analytics actions
 * @package Bitrix\Report\VisualConstructor\Controller
 */
class Analytics extends Base
{
	public function openDefaultAnalyticsPageAction()
	{
		//$componentName = 'bitrix:report.analytics.base';
		$componentName = 'bitrix:ui.sidepanel.wrapper';
		$params = [
			'POPUP_COMPONENT_NAME' => 'bitrix:report.analytics.base',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		];
		return new Component($componentName, '', $params);
	}

	public function getBoardComponentByKeyAction($boardKey='')
	{
		$analyticBoard = $this->getAnalyticBoardByKey($boardKey);
		if (!$analyticBoard)
		{
			$this->addError(new Error('Analytic board with this key not exist'));
			return false;
		}

		$additionalParams = [
			'pageTitle' => $analyticBoard->getTitle(),
			'pageControlsParams' => $analyticBoard->getButtonsContent()
		];
		return new Component(
			$analyticBoard->getDisplayComponentName(),
			$analyticBoard->getDisplayComponentTemplate(),
			$analyticBoard->getDisplayComponentParams(),
			$additionalParams
		);
	}

	/**
	 * @param $boardKey
	 * @return AnalyticBoard
	 */
	private function getAnalyticBoardByKey($boardKey)
	{
		$provider = new AnalyticBoardProvider();
		$provider->addFilter('boardKey', $boardKey);
		$board = $provider->execute()->getFirstResult();
		return $board;
	}

	/**
	 * @param $boardKey
	 *
	 * @return Component|bool
	 */
	public function toggleToDefaultByBoardKeyAction($boardKey, CurrentUser $currentUser)
	{
		$analyticBoardProvider = new AnalyticBoardProvider();
		$analyticBoardProvider->addFilter('boardKey', $boardKey);
		$analyticBoard = $analyticBoardProvider->execute()->getFirstResult();
		if (!$analyticBoard)
		{
			$this->addError(new Error('Analytic board with this key does not exist'));
			return false;
		}

		$dashboardForUser = Dashboard::loadByBoardKeyAndUserId($boardKey, $currentUser->getId());
		if ($dashboardForUser)
		{
			$dashboardForUser->delete();
		}

		$defaultDashboard = Dashboard::loadByBoardKeyAndUserId($boardKey, 0);
		if($defaultDashboard)
		{
			$defaultDashboard->delete();
		}

		if (!empty($analyticBoard))
		{
			$filter = $analyticBoard->getFilter();
			if($filter)
			{
				$filterId = $filter->getFilterParameters()['FILTER_ID'];

				$options = new Options($filterId, $filter::getPresetsList());
				$options->restore($filter::getPresetsList());
				$options->save();
			}
			$analyticBoard->resetToDefault();;
		}

		$additionalParams = [
			'pageTitle' => $analyticBoard->getTitle(),
			'pageControlsParams' => $analyticBoard->getButtonsContent()

		];

		return new Component(
			$analyticBoard->getDisplayComponentName(),
			$analyticBoard->getDisplayComponentTemplate(),
			$analyticBoard->getDisplayComponentParams(),
			$additionalParams
		);
	}

	public function toggleBoardOptionAction($boardKey, CurrentUser $currentUser, string $optionName)
	{
		$analyticBoardProvider = new AnalyticBoardProvider();
		$analyticBoardProvider->addFilter('boardKey', $boardKey);
		$analyticBoard = $analyticBoardProvider->execute()->getFirstResult();
		if (!$analyticBoard)
		{
			$this->addError(new Error('Analytic board with this key does not exist'));
			return false;
		}

		$analyticBoard->toggleOption($optionName);

		AnalyticBoardManager::getInstance()->clearCache();
		AnalyticBoardBatchManager::getInstance()->clearCache();

		$analyticBoard = $analyticBoardProvider->execute()->getFirstResult();

		$additionalParams = [
			'pageTitle' => $analyticBoard->getTitle(),
			'pageControlsParams' => $analyticBoard->getButtonsContent()

		];

		return new Component(
			$analyticBoard->getDisplayComponentName(),
			$analyticBoard->getDisplayComponentTemplate(),
			$analyticBoard->getDisplayComponentParams(),
			$additionalParams
		);
	}
}