<?php

namespace Bitrix\Crm\Integration\Report\View\Customers;

use Bitrix\Main\UI\Extension;
use Bitrix\Report\VisualConstructor\Views\JsComponent\AmChart\ColumnLogarithmic;

class RegularCustomersGraph extends ColumnLogarithmic
{
	const VIEW_KEY = 'CRM_REGULAR_CUSTOMERS';

	public function __construct()
	{
		parent::__construct();
		$this->setDraggable(false);
		Extension::load(["crm.report.regularcustomers"]);
	}

	public function handlerFinallyBeforePassToView($calculatedPerformedData)
	{
		$result = parent::handlerFinallyBeforePassToView($calculatedPerformedData);

		$result['legend'] = [
			"data" => []
		];
		if(is_array($result['graphs']))
		{
			foreach ($result['graphs'] as $k => $graph)
			{
				$result['graphs'][$k]["bullet"] = "none";
				$result['graphs'][$k]["fillColorsField"] = "color";
				$result['graphs'][$k]["balloonFunction"] = "BX.Crm.Report.Dashboard.Content.RegularCustomers.renderBalloon";
				$result['graphs'][$k]["balloon"]["fillAlpha"] = 0;
				$result['graphs'][$k]["balloon"]["borderThickness"] = 0;
				$result['graphs'][$k]["balloon"]["maxWidth"] = 350;

				$result['legend']['data'][] = [
					'title' => $graph['title'],
					'color' => $graph['lineColor']
				];
			}
		}

		if(is_array($result['dataProvider']))
		{
			foreach ($result['dataProvider'] as $k => $item)
			{
				if($result['dataProvider'][$k]['value_1'] == 1)
				{
					// logarithmic scale workaround (log(1) == 0)
					$result['dataProvider'][$k]['value_1'] = 1.5;
				}
				$result['dataProvider'][$k]['color'] = $item['balloon']['color'];
			}
		}

		if ($result['chartCursor'])
		{
			$result['chartCursor']['categoryBalloonEnabled'] = false;
		}

		$result["clickGraphItem"] = "BX.Crm.Report.Dashboard.Content.RegularCustomers.onItemClick";

		return $result;
	}
}