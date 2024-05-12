<?php

namespace Bitrix\CrmMobile\Controller\ReceivePayment;

use Bitrix\Crm\Engine\ActionFilter\CheckReadPermission;
use Bitrix\CrmMobile\Controller\PrimaryAutoWiredEntity;
use Bitrix\CrmMobile\Controller\BaseJson;
use Bitrix\Mobile\Trait\PublicErrorsTrait;
use Bitrix\Main\Engine\ActionFilter;

class Base extends BaseJson
{
	use PrimaryAutoWiredEntity;
	use PublicErrorsTrait;

	protected function getDefaultPreFilters(): array
	{
		return [
			...parent::getDefaultPreFilters(),
			new ActionFilter\Scope(ActionFilter\Scope::NOT_REST),
			new CheckReadPermission(),
		];
	}
}
