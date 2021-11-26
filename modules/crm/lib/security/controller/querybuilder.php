<?php

namespace Bitrix\Crm\Security\Controller;

use Bitrix\Crm\Security\AccessAttribute\Collection;
use Bitrix\Crm\Security\QueryBuilder\Options;

abstract class QueryBuilder
{
	abstract public function build(
		Collection $attributes,
		Options $options
	): string;
}
