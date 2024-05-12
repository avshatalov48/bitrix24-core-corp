<?php

namespace Bitrix\Crm\Security\Controller;

use Bitrix\Crm\Security\AccessAttribute\Collection;
use Bitrix\Crm\Security\Controller\QueryBuilder\QueryBuilderData;
use Bitrix\Crm\Security\QueryBuilder\QueryBuilderOptions;

abstract class QueryBuilder
{
	abstract public function build(
		Collection $attributes,
		QueryBuilderOptions $options
	): QueryBuilderData;
}
