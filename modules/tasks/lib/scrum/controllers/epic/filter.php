<?php

namespace Bitrix\Tasks\Scrum\Controllers\Epic;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\HttpResponse;
use Bitrix\Tasks\Scrum\Controllers\BaseController;
use Bitrix\Tasks\Scrum\Filter\EpicFilter;

class Filter extends BaseController
{
	/**
	 * Returns a component with a filter for grid.
	 *
	 * @ajaxAction tasks.scrum.epic.filter.get
	 *
	 * @return HttpResponse
	 * @throws ArgumentTypeException
	 */
	public function getAction(): HttpResponse
	{
		$post = $this->request->getPostList()->toArray();

		$groupId = is_numeric($post['groupId']) ? (int) $post['groupId'] : 0;

		$gridId = is_string($post['gridId']) ? $post['gridId'] : '';

		$filter = new EpicFilter($this->userId, $groupId);

		$component = new Component('bitrix:main.ui.filter', '', [
			'FILTER_ID' => $gridId,
			'GRID_ID' => $gridId,
			'FILTER' => $filter->getFields(),
			'FILTER_PRESETS' => $filter->getPresets(),
			'ENABLE_LABEL' => true,
			'ENABLE_LIVE_SEARCH' => true,
			'RESET_TO_DEFAULT_MODE' => true,
		]);

		$response = new HttpResponse();

		$response->addHeader('Content-Type', 'application/json');

		$response->setContent($component->getContent());

		return $response;
	}
}
