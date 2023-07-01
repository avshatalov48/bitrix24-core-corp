<?php

namespace Bitrix\Crm\Integration\Report\View;

use Bitrix\Crm\Integration\Report\Handler\SalesDynamics;
use Bitrix\Report\VisualConstructor\Config\Common;
use Bitrix\Report\VisualConstructor\Views\Component\Base;

class SalesDynamicsGrid extends Base
{
	const VIEW_KEY = 'crm_sales_dynamics_grid';
	const MAX_RENDER_REPORT_COUNT = 15;
	const USE_IN_VISUAL_CONSTRUCTOR = false;

	public function __construct()
	{
		parent::__construct();

		$this->setDraggable(false);
		$this->setComponentName('bitrix:crm.report.salesdynamics.grid');
		$this->setPreviewImageUri('/bitrix/images/report/visualconstructor/preview/grid.svg');
		$this->setCompatibleDataType(Common::MULTIPLE_REPORT_TYPE);
	}

	/**
	 * Handle all data prepared for this view.
	 *
	 * @param array $dataFromReport Calculated data from report handler.
	 * @return array
	 */
	public function handlerFinallyBeforePassToView($dataFromReport)
	{
		$result = [];

		$sumReport = $dataFromReport[0];
		$countReport = $dataFromReport[1];
		$prevSumReport = $dataFromReport[2];
		$emptyValue = [
			SalesDynamics\WonLostAmount::PRIMARY_WON => 0,
			SalesDynamics\WonLostAmount::PRIMARY_LOST => 0,
			SalesDynamics\WonLostAmount::RETURN_WON => 0,
			SalesDynamics\WonLostAmount::RETURN_LOST => 0,
			SalesDynamics\WonLostAmount::TOTAL_WON => 0,
			SalesDynamics\WonLostAmount::TOTAL_LOST => 0,
			SalesDynamics\WonLostPrevious::PRIMARY_WON => 0,
			SalesDynamics\WonLostPrevious::PRIMARY_LOST => 0,
			SalesDynamics\WonLostPrevious::RETURN_WON => 0,
			SalesDynamics\WonLostPrevious::RETURN_LOST => 0,
			SalesDynamics\WonLostPrevious::TOTAL_WON => 0,
			SalesDynamics\WonLostPrevious::TOTAL_LOST => 0,
			SalesDynamics\Conversion::COUNT_PRIMARY_WON => 0,
			SalesDynamics\Conversion::COUNT_PRIMARY_LOST => 0,
			SalesDynamics\Conversion::COUNT_PRIMARY_IN_WORK => 0,
			SalesDynamics\Conversion::COUNT_RETURN_WON => 0,
			SalesDynamics\Conversion::COUNT_RETURN_LOST => 0,
			SalesDynamics\Conversion::COUNT_RETURN_IN_WORK => 0,
		];

		$emptyTargetUrl = [
			SalesDynamics\WonLostAmount::PRIMARY_WON => "",
			SalesDynamics\WonLostAmount::PRIMARY_LOST => "",
			SalesDynamics\WonLostAmount::RETURN_WON => "",
			SalesDynamics\WonLostAmount::RETURN_LOST => "",
			SalesDynamics\WonLostAmount::TOTAL_WON => "",
			SalesDynamics\WonLostAmount::TOTAL_LOST => "",
		];

		$userIds = array_unique(array_merge(array_keys($sumReport), array_keys($countReport)));
		foreach ($userIds as $userId)
		{
			$sumReportForUser = is_array($sumReport[$userId]) && is_array($sumReport[$userId]['value']) ? $sumReport[$userId]['value'] : [];
			$prevSumReportForUser = is_array($prevSumReport[$userId]) && is_array($prevSumReport[$userId]['value']) ? $prevSumReport[$userId]['value'] : [];
			$countReportForUser = is_array($countReport[$userId]) && is_array($countReport[$userId]['value']) ? $countReport[$userId]['value'] : [];

			$result[$userId]['value'] = array_merge($emptyValue, $sumReportForUser, $prevSumReportForUser, $countReportForUser);

			$targetUrlForUser = is_array($sumReport[$userId]) && is_array($sumReport[$userId]['targetUrl']) ? $sumReport[$userId]['targetUrl'] : [];

			$result[$userId]['targetUrl'] = array_merge($emptyTargetUrl, $targetUrlForUser);
		}

		return $result;
	}
}