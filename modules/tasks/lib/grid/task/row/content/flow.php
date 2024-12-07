<?php

namespace Bitrix\Tasks\Grid\Task\Row\Content;

use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Grid\Task\Row\Content;

class Flow extends Content
{
	public function prepare(): string
	{
		$filterFlowId = (int)$this->getFilterField('FLOW');
		$flowId = (int)$this->getRowDataByName('FLOW_ID');
		$flowTitle = $this->getRowDataByName('FLOW');
		$active = (int)($filterFlowId === $flowId);

		if ($flowId === 0)
		{
			return '';
		}

		$encodedData = Json::encode([
			'FLOW' => $flowId,
			'FLOW_label' => $flowTitle,
		]);

		$flowTitle = HtmlFilter::encode($flowTitle);

		$onClick = "BX.PreventDefault(); BX.Tasks.GridActions.toggleFilter({$encodedData}, {$active})";

		$styleSelected = $active ? 'tasks-grid-flow tasks-grid-filter-active' : 'tasks-grid-flow';

		$iconClass = 'tasks-grid-avatar ui-icon ui-icon-common-user-group';

		$image = Uri::urnEncode('/bitrix/js/tasks/flow/images/flow.svg');
		$image = "<i style=\"background-image: url('{$image}')\"></i>";

		return "<a class='{$styleSelected}' onclick='{$onClick}' href='javascript:void(0)'>
					<span class='{$iconClass}'>{$image}</span>
					<span class='tasks-grid-flow-inner'>{$flowTitle}</span><span class='tasks-grid-filter-remove'></span>
				</a>";
	}
}