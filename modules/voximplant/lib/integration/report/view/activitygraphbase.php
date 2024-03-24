<?php

namespace Bitrix\Voximplant\Integration\Report\View;

use Bitrix\Report\VisualConstructor\Config\Common;
use Bitrix\Report\VisualConstructor\Views\JsComponent\Activity;

/**
 * Class ActivityGraphBase
 * @package Bitrix\Voximplant\Integration\Report\View\CallDynamics
 */
class ActivityGraphBase extends Activity
{
	public const MAX_RENDER_REPORT_COUNT = 1;
	public const USE_IN_VISUAL_CONSTRUCTOR = false;

	/**
	 * ActivityGraphBase constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setDraggable(false);
		$this->setHeight(480);
		$this->setCompatibleDataType(Common::MULTIPLE_BI_GROUPED_REPORT_TYPE);
	}

	/**
	 * Handle all data prepared for this view.
	 *
	 * @see IReportMultipleGroupedData::getMultipleGroupedData().
	 * @param array $dataFromReport Calculated data from report handler.
	 * @return array
	 */
	public function handlerFinallyBeforePassToView($dataFromReport)
	{
		$result = [];
		if (!empty($dataFromReport['items']))
		{
			$items = [];
			foreach ($dataFromReport['items'] as $item)
			{

				if (!empty($items[$item['firstGroupId']][$item['secondGroupId']]))
				{
					$items[$item['firstGroupId']][$item['secondGroupId']]['active'] += (int)$item['value'][0];

					if (is_array($item['value']))
					{
						foreach ($item['value'] as $index => $value)
						{
							$items[$item['firstGroupId']][$item['secondGroupId']]['tooltip'][$index] += $value;
						}
					}
				}
				else
				{
					$items[$item['firstGroupId']][$item['secondGroupId']] = array(
						'labelXid' => (int)$item['firstGroupId'] + 1,
						'labelYid' => (int)$item['secondGroupId'],
						'active' => (int)$item['value'][0],
						'tooltip' => $item['value'],
						'targetUrl' => $item['url']
					);
				}
			}

			foreach ($items as $firstGroupId => $secondGroup)
			{
				foreach ($secondGroup as $secondGroupId => $newItem)
				{
					$result['items'][] = $newItem;
				}
			}
		}

		$result['config']['labelY'] = $this->getWeekDaysMap();
		$result['config']['labelX'] = $this->getHourList();

		$result['config']['workingHours'] = $dataFromReport['workingHours'] ?? 0;

		return $result;
	}
}