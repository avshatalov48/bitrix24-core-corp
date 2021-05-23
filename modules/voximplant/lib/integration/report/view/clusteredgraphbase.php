<?php

namespace Bitrix\Voximplant\Integration\Report\View;

use Bitrix\Report\VisualConstructor\Config\Common;
use Bitrix\Report\VisualConstructor\Views\JsComponent\AmCharts4;

/**
 * Class StackGraphBase
 * @package Bitrix\Voximplant\Integration\Report\View
 */
class ClusteredGraphBase extends AmCharts4\Column
{
	public const USE_IN_VISUAL_CONSTRUCTOR = false;

	/**
	 * StackGraphBase constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setDraggable(false);
		$this->setHeight(480);
		$this->setCompatibleDataType(Common::MULTIPLE_GROUPED_REPORT_TYPE);
	}

	/**
	 * Returns the graph configuration for the view.
	 *
	 * @return array
	 */
	public function getConfig()
	{
		return [
			'type' => $this->getAmChartType(),
			'data' => [],
			'xAxes' => [
				[
					'type' => 'CategoryAxis',
					'dataFields' => ['category' => 'groupingField'],
					'renderer' => [
						'labels' => [
							'truncate' => true,
        					'maxWidth' => 180,
        					'tooltipText' => '{category}',
							'ellipsis' => '...'
						],
						'grid' => [
							'template' => [
								'location' => 0,
							]
						]
					],
					'cursorTooltipEnabled' => false,
				]
			],
			'yAxes' => [
				[
					'type' => 'ValueAxis',
					'cursorTooltipEnabled' => false
				]
			],
			'series' => [],
			'legend' => [
				'dx' => 46,
				'position' => 'bottom',
				'contentAlign' => 'left',
				'markers' => [
					'width' => 8,
					'height' => 8
				],
				'labels' => [
					'fontSize' => '11',
				],
			],
			'zoomOutButton' => [
				'disabled' => true
			],
		];
	}

	public function getSeries($id, $name, $color)
	{
		return [
			'type' => 'ColumnSeries',
			'name' => $name,
			'stacked' => false,
			'dataFields' => [
				'valueY' => 'value_' . $id,
				'categoryX' => 'groupingField'
			],
			'columns' => [
				'stroke' => $color,
				'fill' => $color,
				'width' => '85%',
				'propertyFields' => [
					'valueUrl' => 'targetUrl_' . $id,
				],
			],
			'tooltip' => [
				'background' => ['disabled' => true],
				'filters' => [
					[
						'type' => 'DropShadowFilter',
						'opacity' => 0.1
					]
				],
      			'locationY' => 0.5
			]
		];
	}
}