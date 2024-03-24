<?php

namespace Bitrix\Crm\Counter\CounterQueryBuilder;

use Bitrix\Crm\Counter\CounterQueryBuilder\BuilderParams\QueryParams;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\ORM\Query\Query;

interface CounterQueryBuilder
{
	public const SELECT_TYPE_QUANTITY = 'QTY';
	public const SELECT_TYPE_ENTITIES = 'ENTY';

	public function build(Factory $factory, QueryParams $params): Query;
}