<?php

namespace Bitrix\Voximplant\Integration\Report\View;

use Bitrix\Main\Localization\Loc;
use Bitrix\Report\VisualConstructor\Config\Common;
use Bitrix\Report\VisualConstructor\Views\JsComponent\AmChart\LinearGraph;

/**
 * Class LinearGraphBase
 * @package Bitrix\Voximplant\Integration\Report\View
 */
class LinearGraphBase extends LinearGraph
{
	public const USE_IN_VISUAL_CONSTRUCTOR = false;

	/**
	 * LinearGraphBase constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setDraggable(false);
		$this->setCompatibleDataType(Common::MULTIPLE_REPORT_TYPE);
	}

	/**
	 * Returns the graph configuration for the view.
	 *
	 * @return array
	 */
	public function getConfig()
	{
		return [
			'type' => 'serial',
			'theme' => 'none',
			'language' => 'ru',
			'pathToImages' => self::AM_CHART_LIB_PATH.'/images/',
			'zoomOutText' => Loc::getMessage('TELEPHONY_REPORT_GRAPH_SHOW_ALL'),
			'dataProvider' => [],
			'valueAxes' => [
				[
					'integersOnly' => true,
					'reversed' => false,
					'axisAlpha' => 0,
					'position' => 'left'
				]
			],
			'startDuration' => 0.5,
			'graphs' => [],
			'categoryField' => 'groupingField',
			'categoryAxis' => [
				'axisAlpha' => 0,
				'fillAlpha' => 0.05,
				'gridAlpha' => 0,
				'position' => 'bottom',
				'dashLength' => 1,
				'minorGridEnabled' => true
			],
			'export' => [
				'enabled' => true,
				'position' => 'bottom-right'
			],
			'legend' => [
				'useGraphSettings' => true,
				'equalWidths' => false,
				'position' => 'bottom',
				'valueText' => '',
			],
			'zoomOutButton' => [
				'disabled' => true
			],
			'chartCursor' => [
				'enabled' => true,
				'oneBalloonOnly' => true,
				'categoryBalloonEnabled' => true,
				'categoryBalloonColor' => '#000000',
				'cursorAlpha' => 1,
				'zoomable' => true,
			],
		];
	}

	/**
	 * Returns the graph line with the specified parameters.
	 *
	 * @param $id
	 * @param $title
	 * @param $color
	 * @param $handlerClassName
	 *
	 * @return array
	 */
	protected function getGraph($id, $title, $color, $handlerClassName)
	{
		return [
			'bullet' => 'round',
			'title' => $title,
			'fillColors' => $color,
			'lineColor' => $color,
			'valueField' => 'value_' . $id,
			'descriptionField' => 'label_' . $id,
			'fillAlphas' => 0,
			'balloonFunction' => "BX.Voximplant.Report.Dashboard.Content.$handlerClassName.renderBalloon",
			'balloon' => [
				'borderThickness' => 0,
			],
		];
	}
}