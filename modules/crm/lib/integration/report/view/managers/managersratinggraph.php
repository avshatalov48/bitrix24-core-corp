<?php

namespace Bitrix\Crm\Integration\Report\View\Managers;

use Bitrix\Main\UI\Extension;
use Bitrix\Report\VisualConstructor\Views\JsComponent\AmChart;
use Bitrix\Report\VisualConstructor\Views\JsComponent\AmCharts4;

//class FinancialRatingGraph extends AmChart\ColumnLogarithmic
class ManagersRatingGraph extends AmCharts4\Column
{
	const VIEW_KEY = 'CRM_MANAGERS_RATING';
	const ENABLE_SORTING = false;

	public function __construct()
	{
		parent::__construct();
		$this->setDraggable(false);
		$this->setJsClassName('BX.Crm.Report.Dashboard.Content.ManagersRating');

		Extension::load(["crm.report.managersrating"]);
	}

	public function handlerFinallyBeforePassToView($calculatedPerformedData)
	{
		$result = parent::handlerFinallyBeforePassToView($calculatedPerformedData);

		if (is_array($result['data']))
		{
			$result['series'][0]['heatRules'] = [[
				"target" => "columns.template",
				"property" => "fill",
				"min" => "#00DEBA",
				"max" => "#2FCEF6",
				"dataField" => "valueY"
			]];

			$result['series'][0]['columns']['width'] = '50%';
			$result['series'][0]['columns']['maxWidth'] = 66;
			$result['series'][0]['columns']['strokeOpacity'] = 0;
			$result['series'][0]['columns']['column']['cornerRadiusTopLeft'] = 60;
			$result['series'][0]['columns']['column']['cornerRadiusTopRight'] = 60;
			$result['series'][0]['columns']['column']['cornerRadiusBottomLeft'] = 60;
			$result['series'][0]['columns']['column']['cornerRadiusBottomRight'] = 60;


			foreach ($result['data'] as $k => $item)
			{
				$result['data'][$k]['bullet'] = $result['data'][$k]['balloon']['icon'];
			}
		}

		return $result;
	}
}
