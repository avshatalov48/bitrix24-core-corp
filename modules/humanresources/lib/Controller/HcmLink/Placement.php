<?php

namespace Bitrix\HumanResources\Controller\HcmLink;

use Bitrix\HumanResources\Config\Feature;
use Bitrix\HumanResources\Engine\Controller;
use Bitrix\HumanResources\Engine\HcmLinkController;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\HcmLink\PlacementType;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main;

class Placement extends HcmLinkController
{
	public function loadSalaryVacationAction(): array
	{
		$userId = CurrentUser::get()->getId();
		if (!$userId)
		{
			$this->addError(new Main\Error('Access denied', 'ACCESS_DENIED'));
			return [];
		}

		return Container::getHcmLinkSalaryAndVacationService()->getSettingsForFrontendByUser($userId);
	}
}