<?php

namespace Bitrix\Crm\Integration\Report\View\Customers;

use Bitrix\Main\UI\Extension;
use Bitrix\Report\VisualConstructor\Views\JsComponent\AmChart;
use Bitrix\Report\VisualConstructor\Views\JsComponent\AmCharts4;

//class FinancialRatingGraph extends AmChart\ColumnLogarithmic
class FinancialRatingGraph extends AmCharts4\Column
{
	const VIEW_KEY = 'CRM_FINANCIAL_RATING';
	const ENABLE_SORTING = false;

	protected static $stars = [
		'data:image/svg+xml;charset=US-ASCII,%3Csvg%20viewBox%3D%220%200%2031%2031%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Cg%20fill%3D%22none%22%20fill-rule%3D%22evenodd%22%3E%3Ccircle%20fill%3D%22%23FDFDFD%22%20cx%3D%2215.5%22%20cy%3D%2215.5%22%20r%3D%2215.5%22/%3E%3Cpath%20d%3D%22M15.195%2022.734l-5.154%201.653a1%201%200%2001-1.306-.949l-.02-5.412a1%201%200%2000-.189-.581l-3.165-4.391a1%201%200%2001.499-1.535L11%209.827a1%201%200%2000.494-.359l3.198-4.366a1%201%200%20011.614%200l3.198%204.366a1%201%200%2000.494.36l5.141%201.691a1%201%200%2001.499%201.535l-3.165%204.39a1%201%200%2000-.189.582l-.02%205.412a1%201%200%2001-1.306.949l-5.154-1.653a1%201%200%2000-.61%200z%22%20fill%3D%22%239DCF00%22%20opacity%3D%22.948%22/%3E%3Ctext%20font-family%3D%22OpenSans-Bold%2C%20Open%20Sans%22%20font-size%3D%2213%22%20font-weight%3D%22bold%22%20fill%3D%22%23FDFDFD%22%3E%3Ctspan%20x%3D%2212.399%22%20y%3D%2219.694%22%3E1%3C/tspan%3E%3C/text%3E%3C/g%3E%3C/svg%3E',
		'data:image/svg+xml;charset=US-ASCII,%3Csvg%20viewBox%3D%220%200%2031%2031%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Cg%20fill%3D%22none%22%20fill-rule%3D%22evenodd%22%3E%3Ccircle%20fill%3D%22%23FDFDFD%22%20cx%3D%2215.5%22%20cy%3D%2215.5%22%20r%3D%2215.5%22/%3E%3Cpath%20d%3D%22M15.195%2022.734l-5.154%201.653a1%201%200%2001-1.306-.949l-.02-5.412a1%201%200%2000-.189-.581l-3.165-4.391a1%201%200%2001.499-1.535L11%209.827a1%201%200%2000.494-.359l3.198-4.366a1%201%200%20011.614%200l3.198%204.366a1%201%200%2000.494.36l5.141%201.691a1%201%200%2001.499%201.535l-3.165%204.39a1%201%200%2000-.189.582l-.02%205.412a1%201%200%2001-1.306.949l-5.154-1.653a1%201%200%2000-.61%200z%22%20fill%3D%22%2314CBC3%22%20opacity%3D%22.948%22/%3E%3Ctext%20font-family%3D%22OpenSans-Bold%2C%20Open%20Sans%22%20font-size%3D%2213%22%20font-weight%3D%22bold%22%20fill%3D%22%23FDFDFD%22%3E%3Ctspan%20x%3D%2212.399%22%20y%3D%2219.694%22%3E2%3C/tspan%3E%3C/text%3E%3C/g%3E%3C/svg%3E',
		'data:image/svg+xml;charset=US-ASCII,%3Csvg%20viewBox%3D%220%200%2031%2031%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Cg%20fill%3D%22none%22%20fill-rule%3D%22evenodd%22%3E%3Ccircle%20fill%3D%22%23FDFDFD%22%20cx%3D%2215.5%22%20cy%3D%2215.5%22%20r%3D%2215.5%22/%3E%3Cpath%20d%3D%22M15.195%2022.734l-5.154%201.653a1%201%200%2001-1.306-.949l-.02-5.412a1%201%200%2000-.189-.581l-3.165-4.391a1%201%200%2001.499-1.535L11%209.827a1%201%200%2000.494-.359l3.198-4.366a1%201%200%20011.614%200l3.198%204.366a1%201%200%2000.494.36l5.141%201.691a1%201%200%2001.499%201.535l-3.165%204.39a1%201%200%2000-.189.582l-.02%205.412a1%201%200%2001-1.306.949l-5.154-1.653a1%201%200%2000-.61%200z%22%20fill%3D%22%23F7A700%22%20opacity%3D%22.948%22/%3E%3Ctext%20font-family%3D%22OpenSans-Bold%2C%20Open%20Sans%22%20font-size%3D%2213%22%20font-weight%3D%22bold%22%20fill%3D%22%23FDFDFD%22%3E%3Ctspan%20x%3D%2212.399%22%20y%3D%2219.694%22%3E3%3C/tspan%3E%3C/text%3E%3C/g%3E%3C/svg%3E'
	];

	public function __construct()
	{
		parent::__construct();
		$this->setDraggable(false);
		$this->setHeight(480);
		$this->setJsClassName('BX.Crm.Report.Dashboard.Content.FinancialRating');

		Extension::load(["crm.report.financialrating"]);
	}

	public function handlerFinallyBeforePassToView($calculatedPerformedData)
	{
		$result = parent::handlerFinallyBeforePassToView($calculatedPerformedData);

		if (is_array($result['data']))
		{
			$result['topAxesContainer']['paddingBottom'] = 10;
			foreach ($result['data'] as $k => $item)
			{
				$result['data'][$k]['color'] = $item['balloon']['color'];

				if (isset(self::$stars[$k]))
				{
					$result['data'][$k]['bullet'] = self::$stars[$k];
				}
			}
		}
		$result['zoomOutButton'] = [
			'disabled' => true
		];


		return $result;
	}
}
