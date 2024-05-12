<?php

namespace Bitrix\Crm\Security\Controller\QueryBuilder\RestrictionByAttributes;

use Bitrix\Crm\Security\Controller;
use Bitrix\Crm\Traits\Singleton;

class RestrictionAttributesFactory
{
	use Singleton;

	public function make(Controller\Base $controller): RestrictionsByAttributes
	{
		return new RestrictionsByAttributes($controller);
	}
}