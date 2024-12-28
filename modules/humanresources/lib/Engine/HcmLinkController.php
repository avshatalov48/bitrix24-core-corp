<?php

namespace Bitrix\HumanResources\Engine;

use Bitrix\HumanResources\Config\Feature;
use Bitrix\Main;
use Bitrix\Intranet;
use Bitrix\Main\Localization\Loc;

abstract class HcmLinkController extends Main\Engine\Controller
{
	protected function getDefaultPreFilters()
	{
		return [
				new Main\Engine\ActionFilter\Authentication(),
				new Main\Engine\ActionFilter\Csrf(),
				new Main\Engine\ActionFilter\HttpMethod(
					[Main\Engine\ActionFilter\HttpMethod::METHOD_GET, Main\Engine\ActionFilter\HttpMethod::METHOD_POST],
				),
				new Intranet\ActionFilter\IntranetUser(),
				new Main\Engine\ActionFilter\CloseSession(),
		];
	}

	protected function processBeforeAction(Main\Engine\Action $action): bool
	{
		if (!Feature::instance()->isHcmLinkAvailable())
		{
			$this->addError($this->makeAccessDeniedError());

			return false;
		}

		return parent::processBeforeAction($action);
	}

	protected function makeAccessDeniedError(): Main\Error
	{
		return new Main\Error(
			Loc::getMessage('HR_HCMLINK_ACTION_ACCESS_DENIED'),
			'HCMLINK_ACCESS_DENIED',
		);
	}
}