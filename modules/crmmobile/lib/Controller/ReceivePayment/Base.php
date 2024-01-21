<?php

namespace Bitrix\CrmMobile\Controller\ReceivePayment;

use Bitrix\Crm\Engine\ActionFilter\CheckReadPermission;
use Bitrix\CrmMobile\Controller\PrimaryAutoWiredEntity;
use Bitrix\CrmMobile\Controller\PublicErrorsTrait;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\JsonController;

class Base extends JsonController
{
	use PrimaryAutoWiredEntity;
	use PublicErrorsTrait;

	protected function getDefaultPreFilters(): array
	{
		return [
			new ActionFilter\Authentication(),
			new ActionFilter\Csrf(),
			new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
			new ActionFilter\ContentType([ActionFilter\ContentType::JSON]),
			new ActionFilter\Scope(ActionFilter\Scope::NOT_REST),
			new CheckReadPermission(),
		];
	}
}
