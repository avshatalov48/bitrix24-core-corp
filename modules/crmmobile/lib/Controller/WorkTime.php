<?php

declare(strict_types=1);

namespace Bitrix\CrmMobile\Controller;

use Bitrix\Crm\Settings;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\JsonController;
use Bitrix\Main\Request;

class WorkTime extends JsonController
{
	private Settings\WorkTime $workTimeService;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->workTimeService = new Settings\WorkTime();
	}

	protected function getDefaultPreFilters(): array
	{
		return [
			new ActionFilter\Authentication(),
			new ActionFilter\Csrf(),
			new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
			new ActionFilter\ContentType([ActionFilter\ContentType::JSON]),
			new ActionFilter\Scope(ActionFilter\Scope::NOT_REST),
			new ActionFilter\CloseSession(),
		];
	}

	public function getWorkCalendarAction(): array
	{
		$calendar = $this->workTimeService->getData();
		$calendar['TIME_FROM'] = (string)$calendar['TIME_FROM'];
		$calendar['TIME_TO'] = (string)$calendar['TIME_TO'];
		return $calendar;
	}
}
